const pool = require('../../core/db');
const HttpError = require('../../core/http-error');

const DEFAULT_JOBS = [
  { code: 'factory_worker', title: 'Factory Worker', baseSalary: 120, energyCost: 6 },
  { code: 'office_clerk', title: 'Office Clerk', baseSalary: 140, energyCost: 5 },
  { code: 'logistics_operator', title: 'Logistics Operator', baseSalary: 160, energyCost: 7 },
  { code: 'construction_worker', title: 'Construction Worker', baseSalary: 180, energyCost: 8 }
];

async function ensureDefaultJobs(cityId, countryId) {
  for (const job of DEFAULT_JOBS) {
    await pool.query(
      `INSERT INTO jobs (city_id, country_id, code, title, base_salary, energy_cost, required_level, is_active)
       VALUES (?, ?, ?, ?, ?, ?, 1, 1)
       ON DUPLICATE KEY UPDATE title = VALUES(title), base_salary = VALUES(base_salary), energy_cost = VALUES(energy_cost), is_active = 1`,
      [Number(cityId), Number(countryId), job.code, job.title, job.baseSalary, job.energyCost]
    );
  }
}

async function getUserEconomyContext(userId) {
  const [rows] = await pool.query(
    `SELECT u.id, u.level, u.energy, u.current_city_id, u.current_country_id,
            up.cash_balance
     FROM users u
     LEFT JOIN user_profiles up ON up.user_id = u.id
     WHERE u.id = ?
     LIMIT 1`,
    [Number(userId)]
  );

  if (!rows.length) {
    throw new HttpError(404, 'User not found');
  }

  const user = rows[0];
  if (!user.current_city_id || !user.current_country_id) {
    throw new HttpError(400, 'User has no active city/country');
  }

  return user;
}

async function getJobsByCity(cityId, countryId) {
  await ensureDefaultJobs(cityId, countryId);

  const [rows] = await pool.query(
    `SELECT id, code, title, base_salary, energy_cost, required_level
     FROM jobs
     WHERE city_id = ? AND country_id = ? AND is_active = 1
     ORDER BY base_salary ASC`,
    [Number(cityId), Number(countryId)]
  );

  return rows;
}

async function getTaxRate(countryId) {
  const [policyRows] = await pool.query('SELECT tax_rate FROM country_policies WHERE country_id = ? LIMIT 1', [Number(countryId)]);
  if (policyRows.length) return Number(policyRows[0].tax_rate || 10);

  const [countryRows] = await pool.query('SELECT base_tax_rate FROM countries WHERE id = ? LIMIT 1', [Number(countryId)]);
  return Number((countryRows[0] && countryRows[0].base_tax_rate) || 10);
}

async function performWork(userId, jobId) {
  const user = await getUserEconomyContext(userId);

  const [jobRows] = await pool.query(
    `SELECT id, city_id, country_id, code, title, base_salary, energy_cost, required_level
     FROM jobs
     WHERE id = ? AND is_active = 1
     LIMIT 1`,
    [Number(jobId)]
  );

  const job = jobRows[0];
  if (!job) throw new HttpError(404, 'Job not found');
  if (Number(job.city_id) !== Number(user.current_city_id)) {
    throw new HttpError(400, 'Job is not in user current city');
  }
  if (Number(user.level) < Number(job.required_level)) {
    throw new HttpError(400, 'User level is too low for this job');
  }
  if (Number(user.energy) < Number(job.energy_cost)) {
    throw new HttpError(400, 'Insufficient energy');
  }

  const [cityRows] = await pool.query('SELECT industry_level, employment_rate FROM cities WHERE id = ? LIMIT 1', [Number(user.current_city_id)]);
  const city = cityRows[0] || { industry_level: 1, employment_rate: 50 };

  const industryMultiplier = 1 + (Number(city.industry_level || 1) - 1) * 0.04;
  const employmentMultiplier = 0.7 + Number(city.employment_rate || 50) / 100;

  const grossSalary = Number((Number(job.base_salary) * industryMultiplier * employmentMultiplier).toFixed(2));
  const taxRate = await getTaxRate(user.current_country_id);
  const taxAmount = Number((grossSalary * (taxRate / 100)).toFixed(2));
  const netSalary = Number((grossSalary - taxAmount).toFixed(2));

  const countryShare = Number((taxAmount * 0.6).toFixed(2));
  const cityShare = Number((taxAmount - countryShare).toFixed(2));

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    await connection.query(
      `UPDATE users
       SET energy = GREATEST(energy - ?, 0), experience = experience + 15
       WHERE id = ?`,
      [Number(job.energy_cost), Number(userId)]
    );

    await connection.query(
      'UPDATE user_profiles SET cash_balance = cash_balance + ? WHERE user_id = ?',
      [netSalary, Number(userId)]
    );

    await connection.query('UPDATE countries SET treasury = treasury + ? WHERE id = ?', [countryShare, Number(user.current_country_id)]);
    await connection.query('UPDATE cities SET local_treasury = local_treasury + ? WHERE id = ?', [cityShare, Number(user.current_city_id)]);

    await connection.query(
      `INSERT INTO work_sessions
       (user_id, city_id, country_id, job_id, gross_salary, tax_amount, net_salary)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [Number(userId), Number(user.current_city_id), Number(user.current_country_id), Number(job.id), grossSalary, taxAmount, netSalary]
    );

    await connection.query(
      `INSERT INTO transactions
       (user_id, country_id, city_id, tx_type, amount, currency_code, reference_type, reference_id)
       VALUES (?, ?, ?, 'work_income', ?, 'GMC', 'job', ?)`,
      [Number(userId), Number(user.current_country_id), Number(user.current_city_id), netSalary, Number(job.id)]
    );

    await connection.commit();

    return {
      jobId: Number(job.id),
      jobTitle: job.title,
      grossSalary,
      taxRate,
      taxAmount,
      netSalary,
      countryShare,
      cityShare
    };
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

async function overview(userId) {
  const user = await getUserEconomyContext(userId);
  const [cityRows] = await pool.query(
    `SELECT id, name, local_treasury, economy_score, employment_rate
     FROM cities
     WHERE id = ? LIMIT 1`,
    [Number(user.current_city_id)]
  );
  const [countryRows] = await pool.query(
    `SELECT id, name, treasury, economy_score
     FROM countries
     WHERE id = ? LIMIT 1`,
    [Number(user.current_country_id)]
  );

  const taxRate = await getTaxRate(user.current_country_id);

  const [recentRows] = await pool.query(
    `SELECT id, gross_salary, tax_amount, net_salary, performed_at
     FROM work_sessions
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 10`,
    [Number(userId)]
  );

  return {
    user: {
      id: user.id,
      level: user.level,
      energy: user.energy,
      cashBalance: Number(user.cash_balance || 0)
    },
    city: cityRows[0] || null,
    country: countryRows[0] || null,
    taxRate,
    recentWorkSessions: recentRows
  };
}

async function transferCountrySupport(userId, cityId, amount) {
  const transferAmount = Number(amount || 0);
  if (transferAmount <= 0) throw new HttpError(400, 'amount must be positive');

  const [uRows] = await pool.query('SELECT current_country_id FROM users WHERE id = ? LIMIT 1', [Number(userId)]);
  const user = uRows[0];
  if (!user) throw new HttpError(404, 'User not found');

  const [presidentRows] = await pool.query(
    'SELECT id FROM presidents WHERE country_id = ? AND user_id = ? AND is_active = 1 LIMIT 1',
    [Number(user.current_country_id), Number(userId)]
  );

  if (!presidentRows.length) {
    throw new HttpError(403, 'Only active president can transfer country support');
  }

  const [cityRows] = await pool.query('SELECT id, country_id FROM cities WHERE id = ? LIMIT 1', [Number(cityId)]);
  const city = cityRows[0];
  if (!city) throw new HttpError(404, 'City not found');
  if (Number(city.country_id) !== Number(user.current_country_id)) {
    throw new HttpError(400, 'City is not in president country');
  }

  const [countryRows] = await pool.query('SELECT treasury FROM countries WHERE id = ? LIMIT 1', [Number(user.current_country_id)]);
  if (Number(countryRows[0].treasury) < transferAmount) {
    throw new HttpError(400, 'Country treasury is insufficient');
  }

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    await connection.query('UPDATE countries SET treasury = treasury - ? WHERE id = ?', [transferAmount, Number(user.current_country_id)]);
    await connection.query('UPDATE cities SET local_treasury = local_treasury + ? WHERE id = ?', [transferAmount, Number(city.id)]);

    await connection.query(
      `INSERT INTO transactions (user_id, country_id, city_id, tx_type, amount, currency_code, reference_type, reference_id)
       VALUES (?, ?, ?, 'country_support_transfer', ?, 'GMC', 'city_support', ?)`,
      [Number(userId), Number(user.current_country_id), Number(city.id), transferAmount, Number(city.id)]
    );

    await connection.commit();

    return {
      countryId: Number(user.current_country_id),
      cityId: Number(city.id),
      amount: transferAmount
    };
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

module.exports = {
  getJobsByCity,
  performWork,
  overview,
  transferCountrySupport
};
