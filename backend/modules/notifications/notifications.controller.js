const notificationsService = require('./notifications.service');

async function list(req, res, next) {
  try {
    const data = await notificationsService.listUserNotifications(req.auth.sub, req.query.limit);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function markRead(req, res, next) {
  try {
    const data = await notificationsService.markAsRead(req.auth.sub, req.params.notificationId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function demo(req, res, next) {
  try {
    const data = await notificationsService.createDemoNotification(req.auth.sub);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  list,
  markRead,
  demo
};
