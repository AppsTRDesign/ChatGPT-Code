const HttpError = require('./http-error');
const { verifyAccessToken } = require('./jwt');

function authRequired(req, res, next) {
  const authHeader = req.headers.authorization || '';
  const token = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : null;

  if (!token) {
    next(new HttpError(401, 'Unauthorized'));
    return;
  }

  try {
    const payload = verifyAccessToken(token);
    req.auth = payload;
    next();
  } catch (error) {
    next(new HttpError(401, 'Invalid token'));
  }
}

module.exports = {
  authRequired
};
