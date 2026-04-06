const adminService = require('./admin.service');

async function dashboard(req, res, next) {
  try {
    const data = await adminService.dashboard();
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  dashboard
};
