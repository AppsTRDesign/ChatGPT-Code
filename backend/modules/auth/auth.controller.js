const authService = require('./auth.service');

async function register(req, res, next) {
  try {
    const result = await authService.createUserWithOnboarding(req.body, req);
    res.status(201).json({ ok: true, data: result });
  } catch (error) {
    next(error);
  }
}

async function login(req, res, next) {
  try {
    const result = await authService.login(req.body);
    res.status(200).json({ ok: true, data: result });
  } catch (error) {
    next(error);
  }
}

async function refresh(req, res, next) {
  try {
    const result = await authService.refreshTokens(req.body);
    res.status(200).json({ ok: true, data: result });
  } catch (error) {
    next(error);
  }
}

async function logout(req, res, next) {
  try {
    await authService.logout(req.body);
    res.status(200).json({ ok: true });
  } catch (error) {
    next(error);
  }
}

async function me(req, res, next) {
  try {
    const data = await authService.me(req.auth.sub);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  register,
  login,
  refresh,
  logout,
  me
};
