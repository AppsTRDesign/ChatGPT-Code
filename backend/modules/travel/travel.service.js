const pool = require('../../core/db');
const HttpError = require('../../core/http-error');
const { haversineKm } = require('../../utils/geo');

function computeTravelType(fromCity, toCity, distanceKm) {
  if (fromCity.id === toCity.id) return 'same_city';
  if (fromCity.country_id === toCity.country_id) {
    if (distanceKm < 80) return 'local_travel';
    return 'domestic_flight';
  }
  return 'international_flight';
}

function computeDurationSeconds(distanceKm, travelType, airportLevel) {
  if (travelType === 'same_city') return 0;

  const baseSpeed = travelType === 'local_travel' ? 60 : 780;
  const airportBoost = 1 + Math.min(Math.max((airportLevel - 1) * 0.03, 0), 0.24);
  const effectiveSpeed = baseSpeed * airportBoost;

  const hours = distanceKm / Math.max(effectiveSpeed, 10);
  const baseSeconds = Math.ceil(hours * 3600);

  if (travelType === 'local_travel') return Math.max(baseSeconds, 300);
  if (travelType === 'domestic_flight') return Math.max(baseSeconds + 1800, 1200);
  return Math.max(baseSeconds + 3600, 2400);
}

function computeTicketCost(distanceKm, travelType, airportLevel) {
  if (travelType === 'same_city') return 0;

  const base = travelType === 'local_travel' ? 0.15 : travelType === 'domestic_flight' ? 0.23 : 0.36;
  const airportDiscount = Math.min((airportLevel - 1) * 0.01, 0.08);
  const price = distanceKm * base * (1 - airportDiscount);
  return Number(price.toFixed(2));
}

async function getCityById(cityId) {
  const [rows] = await pool.query(
    `SELECT id, name, country_id, latitude, longitude, airport_level
     FROM cities
     WHERE id = ?
     LIMIT 1`,
    [Number(cityId)]
  );
  return rows[0] || null;
}

async function checkPermitStatus(userId, fromCity, toCity, travelType) {
  if (travelType !== 'international_flight') {
    return {
      canTravel: true,
      permitStatus: 'not_required',
      visaRequired: false,
      workPermitRequired: false
    };
  }

  const [policyRows] = await pool.query(
    `SELECT cpr.visa_required, cpr.work_permit_required
     FROM country_policies cp
     JOIN country_policy_rules cpr ON cpr.country_policy_id = cp.id
     WHERE cp.country_id = ? AND cpr.target_country_id = ?
     LIMIT 1`,
    [toCity.country_id, fromCity.country_id]
  );

  const visaRequired = policyRows[0] ? Boolean(policyRows[0].visa_required) : true;
  const workPermitRequired = policyRows[0] ? Boolean(policyRows[0].work_permit_required) : false;

  if (!visaRequired && !workPermitRequired) {
    return {
      canTravel: true,
      permitStatus: 'approved',
      visaRequired,
      workPermitRequired
    };
  }

  if (visaRequired) {
    const [visaRows] = await pool.query(
      `SELECT id
       FROM visas
       WHERE user_id = ?
         AND from_country_id = ?
         AND to_country_id = ?
         AND status = 'approved'
         AND (valid_until IS NULL OR valid_until > NOW())
       ORDER BY id DESC
       LIMIT 1`,
      [userId, fromCity.country_id, toCity.country_id]
    );

    if (!visaRows.length) {
      return {
        canTravel: false,
        permitStatus: 'visa_required',
        visaRequired,
        workPermitRequired
      };
    }
  }

  if (workPermitRequired) {
    const [permitRows] = await pool.query(
      `SELECT id
       FROM work_permits
       WHERE user_id = ?
         AND country_id = ?
         AND status = 'approved'
         AND (valid_until IS NULL OR valid_until > NOW())
         AND (city_id IS NULL OR city_id = ?)
       ORDER BY id DESC
       LIMIT 1`,
      [userId, toCity.country_id, toCity.id]
    );

    if (!permitRows.length) {
      return {
        canTravel: false,
        permitStatus: 'work_permit_required',
        visaRequired,
        workPermitRequired
      };
    }
  }

  return {
    canTravel: true,
    permitStatus: 'approved',
    visaRequired,
    workPermitRequired
  };
}

async function buildQuote(userId, departureCityId, arrivalCityId) {
  const fromCity = await getCityById(departureCityId);
  const toCity = await getCityById(arrivalCityId);

  if (!fromCity || !toCity) {
    throw new HttpError(404, 'Departure or arrival city not found');
  }

  const distanceKm = Number(
    haversineKm(fromCity.latitude, fromCity.longitude, toCity.latitude, toCity.longitude).toFixed(2)
  );

  const travelType = computeTravelType(fromCity, toCity, distanceKm);
  const durationSeconds = computeDurationSeconds(distanceKm, travelType, fromCity.airport_level);
  const ticketCost = computeTicketCost(distanceKm, travelType, fromCity.airport_level);

  const permit = await checkPermitStatus(userId, fromCity, toCity, travelType);

  return {
    departureCityId: fromCity.id,
    arrivalCityId: toCity.id,
    distanceKm,
    durationSeconds,
    travelType,
    permitStatus: permit.permitStatus,
    canTravel: permit.canTravel,
    ticketCost,
    visaRequired: permit.visaRequired,
    workPermitRequired: permit.workPermitRequired
  };
}

async function startTravel(userId, departureCityId, arrivalCityId) {
  const quote = await buildQuote(userId, departureCityId, arrivalCityId);

  if (!quote.canTravel) {
    throw new HttpError(403, 'Travel blocked by permit policy', {
      permitStatus: quote.permitStatus
    });
  }

  const startTime = new Date();
  const endTime = new Date(startTime.getTime() + quote.durationSeconds * 1000);

  const [insertResult] = await pool.query(
    `INSERT INTO travels
     (user_id, departure_city_id, arrival_city_id, distance_km, duration_seconds,
      travel_status, travel_type, permit_status, ticket_cost, start_time, end_time)
     VALUES (?, ?, ?, ?, ?, 'in_progress', ?, ?, ?, ?, ?)`,
    [
      userId,
      quote.departureCityId,
      quote.arrivalCityId,
      quote.distanceKm,
      quote.durationSeconds,
      quote.travelType,
      quote.permitStatus,
      quote.ticketCost,
      startTime,
      endTime
    ]
  );

  return {
    id: insertResult.insertId,
    ...quote,
    travelStatus: 'in_progress',
    startTime: startTime.toISOString(),
    endTime: endTime.toISOString()
  };
}

async function completeIfFinished(travel) {
  const now = new Date();
  const end = new Date(travel.end_time);
  if (now < end || travel.travel_status !== 'in_progress') {
    return travel;
  }

  await pool.query(
    `UPDATE travels
     SET travel_status = 'completed', completed_at = NOW()
     WHERE id = ?`,
    [travel.id]
  );

  await pool.query(
    `UPDATE users u
     JOIN cities c ON c.id = ?
     SET u.current_city_id = c.id,
         u.current_country_id = c.country_id
     WHERE u.id = ?`,
    [travel.arrival_city_id, travel.user_id]
  );

  return {
    ...travel,
    travel_status: 'completed'
  };
}

async function getActiveTravel(userId) {
  const [rows] = await pool.query(
    `SELECT id, user_id, departure_city_id, arrival_city_id, distance_km, duration_seconds,
            travel_status, travel_type, permit_status, ticket_cost, start_time, end_time, completed_at
     FROM travels
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 1`,
    [userId]
  );

  if (!rows.length) return null;

  const updated = await completeIfFinished(rows[0]);
  const now = new Date();
  const end = new Date(updated.end_time);
  const remainingSeconds = Math.max(Math.floor((end.getTime() - now.getTime()) / 1000), 0);

  return {
    id: updated.id,
    departureCityId: updated.departure_city_id,
    arrivalCityId: updated.arrival_city_id,
    distanceKm: updated.distance_km,
    durationSeconds: updated.duration_seconds,
    travelStatus: updated.travel_status,
    travelType: updated.travel_type,
    permitStatus: updated.permit_status,
    ticketCost: updated.ticket_cost,
    startTime: updated.start_time,
    endTime: updated.end_time,
    remainingSeconds
  };
}

module.exports = {
  buildQuote,
  startTravel,
  getActiveTravel
};
