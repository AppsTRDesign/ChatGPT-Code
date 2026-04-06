const express = require('express');
const cors = require('cors');
const cookieParser = require('cookie-parser');

const env = require('../config/env');
const authRoutes = require('../modules/auth/auth.routes');
const usersRoutes = require('../modules/users/users.routes');
const mapRoutes = require('../modules/map/map.routes');
const { getEnabledLanguageCodes, detectLanguageFromHeaders, resolveLanguagePreference } = require('../modules/i18n/i18n.service');

const app = express();

app.use(cors({ origin: env.corsOrigin, credentials: true }));
app.use(express.json());
app.use(cookieParser());

app.use(async function (req, res, next) {
  try {
    const enabled = await getEnabledLanguageCodes();
    const headerDetected = detectLanguageFromHeaders(req.headers['accept-language'], enabled);
    const resolved = resolveLanguagePreference({
      userPreferred: req.headers['x-user-language'],
      guestOverride: req.headers['x-guest-language'],
      headerDetected: headerDetected,
      enabledCodes: enabled
    });

    req.language = resolved;
    next();
  } catch (error) {
    next(error);
  }
});

app.get('/api/v1/health', function (req, res) {
  res.status(200).json({ ok: true, environment: env.nodeEnv, language: req.language });
});

app.use('/api/v1/auth', authRoutes);
app.use('/api/v1/users', usersRoutes);
app.use('/api/v1/map', mapRoutes);

app.use(function (req, res) {
  res.status(404).json({ ok: false, error: 'Not Found' });
});

app.use(function (err, req, res, next) {
  if (res.headersSent) {
    next(err);
    return;
  }

  const status = err.status || 500;
  res.status(status).json({
    ok: false,
    error: err.message || 'Internal server error',
    details: err.details || null
  });
});

module.exports = app;
