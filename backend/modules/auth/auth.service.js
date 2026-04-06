const pool = require('../../core/db');
const HttpError = require('../../core/http-error');
const { hashPassword, comparePassword } = require('../../core/password');
const { signAccessToken, signRefreshToken, verifyRefreshToken } = require('../../core/jwt');
const { resolveGeoLocationFromRequest } = require('./geoip.provider');
const { assignCountryAndCity } = require('./onboarding.service');
const { saveRefreshToken, revokeRefreshToken, isRefreshTokenActive } = require('./token.service');

async function createUserWithOnboarding(payload, req) {
  const email = String(payload.email || '').trim().toLowerCase();
  const username = String(payload.username || '').trim();
  const password = String(payload.password || '');
  const preferredLanguageCode = String(payload.preferredLanguageCode || 'en').toLowerCase();

  if (!email || !username || !password) {
    throw new HttpError(400, 'email, username and password are required');
  }

  const [existing] = await pool.query('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1', [email, username]);
  if (existing.length) {
    throw new HttpError(409, 'Email or username already exists');
  }

  const geo = await resolveGeoLocationFromRequest(req);
  const assignment = await assignCountryAndCity(geo);
  const passwordHash = await hashPassword(password);

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    const [insertUser] = await connection.query(
      `INSERT INTO users
      (email, username, password_hash, preferred_language_code, current_country_id, current_city_id, home_country_id, home_city_id)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        email,
        username,
        passwordHash,
        preferredLanguageCode,
        assignment.assignedCountryId,
        assignment.assignedCityId,
        assignment.assignedCountryId,
        assignment.assignedCityId
      ]
    );

    const userId = insertUser.insertId;

    await connection.query(
      `INSERT INTO user_profiles
      (user_id, detected_country, detected_city, assigned_country_id, assigned_city_id, assignment_reason, geoip_provider, geoip_confidence)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        userId,
        assignment.detectedCountryCode,
        assignment.detectedCityName,
        assignment.assignedCountryId,
        assignment.assignedCityId,
        assignment.assignmentReason,
        geo.provider,
        geo.confidence
      ]
    );

    await connection.query(
      'INSERT INTO inventories (user_id, max_slots, used_slots) VALUES (?, 50, 0)',
      [userId]
    );

    await connection.commit();

    return {
      userId: userId,
      assignment: assignment
    };
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

async function login(payload) {
  const identity = String(payload.identity || '').trim();
  const password = String(payload.password || '');

  if (!identity || !password) {
    throw new HttpError(400, 'identity and password are required');
  }

  const [rows] = await pool.query(
    `SELECT id, email, username, password_hash, preferred_language_code, current_country_id, current_city_id
     FROM users WHERE email = ? OR username = ? LIMIT 1`,
    [identity.toLowerCase(), identity]
  );

  const user = rows[0];
  if (!user) {
    throw new HttpError(401, 'Invalid credentials');
  }

  const isValid = await comparePassword(password, user.password_hash);
  if (!isValid) {
    throw new HttpError(401, 'Invalid credentials');
  }

  const tokenPayload = {
    sub: user.id,
    username: user.username,
    language: user.preferred_language_code
  };

  const accessToken = signAccessToken(tokenPayload);
  const refreshToken = signRefreshToken({ sub: user.id });

  await saveRefreshToken(user.id, refreshToken);

  await pool.query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [user.id]);

  return {
    user: {
      id: user.id,
      email: user.email,
      username: user.username,
      preferredLanguageCode: user.preferred_language_code,
      currentCountryId: user.current_country_id,
      currentCityId: user.current_city_id
    },
    tokens: {
      accessToken: accessToken,
      refreshToken: refreshToken
    }
  };
}

async function refreshTokens(payload) {
  const refreshToken = String(payload.refreshToken || '');
  if (!refreshToken) {
    throw new HttpError(400, 'refreshToken is required');
  }

  const decoded = verifyRefreshToken(refreshToken);
  const active = await isRefreshTokenActive(refreshToken);
  if (!active) {
    throw new HttpError(401, 'Refresh token revoked or expired');
  }

  const [rows] = await pool.query('SELECT id, username, preferred_language_code FROM users WHERE id = ? LIMIT 1', [decoded.sub]);
  const user = rows[0];
  if (!user) {
    throw new HttpError(401, 'User not found');
  }

  await revokeRefreshToken(refreshToken);

  const accessToken = signAccessToken({ sub: user.id, username: user.username, language: user.preferred_language_code });
  const newRefreshToken = signRefreshToken({ sub: user.id });
  await saveRefreshToken(user.id, newRefreshToken);

  return {
    accessToken: accessToken,
    refreshToken: newRefreshToken
  };
}

async function logout(payload) {
  const refreshToken = String(payload.refreshToken || '');
  if (!refreshToken) return;

  await revokeRefreshToken(refreshToken);
}

module.exports = {
  createUserWithOnboarding,
  login,
  refreshTokens,
  logout
};
