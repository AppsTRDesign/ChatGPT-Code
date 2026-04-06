const pool = require('../../core/db');

async function getEnabledLanguageCodes() {
  const [rows] = await pool.query('SELECT code FROM languages WHERE is_enabled = 1');
  return rows.map(function (r) { return r.code; });
}

function normalizeLang(input) {
  if (!input) return null;
  return String(input).toLowerCase().split('-')[0];
}

function detectLanguageFromHeaders(acceptLanguageHeader, enabledCodes) {
  if (!acceptLanguageHeader) return 'en';

  const normalized = acceptLanguageHeader
    .split(',')
    .map(function (part) { return normalizeLang(part.split(';')[0].trim()); })
    .filter(Boolean);

  var found = normalized.find(function (code) {
    return enabledCodes.includes(code);
  });

  return found || 'en';
}

function resolveLanguagePreference(options) {
  var userPreferred = normalizeLang(options.userPreferred);
  var guestOverride = normalizeLang(options.guestOverride);
  var headerDetected = normalizeLang(options.headerDetected);
  var enabledCodes = options.enabledCodes || ['en'];

  if (userPreferred && enabledCodes.includes(userPreferred)) return userPreferred;
  if (guestOverride && enabledCodes.includes(guestOverride)) return guestOverride;
  if (headerDetected && enabledCodes.includes(headerDetected)) return headerDetected;
  return 'en';
}

module.exports = {
  getEnabledLanguageCodes,
  detectLanguageFromHeaders,
  resolveLanguagePreference,
  normalizeLang
};
