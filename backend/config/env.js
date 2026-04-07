const dotenv = require('dotenv');

dotenv.config();

module.exports = {
  nodeEnv: process.env.NODE_ENV || 'development',
  port: Number(process.env.PORT || 3001),
  jwt: {
    accessSecret: process.env.JWT_ACCESS_SECRET || 'dev_access_secret_change_me',
    refreshSecret: process.env.JWT_REFRESH_SECRET || 'dev_refresh_secret_change_me',
    accessTtl: process.env.JWT_ACCESS_TTL || '15m',
    refreshTtlDays: Number(process.env.JWT_REFRESH_TTL_DAYS || 14)
  },
  db: {
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    name: process.env.DB_NAME || 'noasoft_game',
    connectionLimit: Number(process.env.DB_POOL_SIZE || 10)
  },
  corsOrigin: process.env.CORS_ORIGIN || '*',
  geoipProvider: process.env.GEOIP_PROVIDER || 'mock',
  geoipMockCountryCode: process.env.GEOIP_MOCK_COUNTRY_CODE || 'TR',
  geoipMockCity: process.env.GEOIP_MOCK_CITY || 'İstanbul'
};
