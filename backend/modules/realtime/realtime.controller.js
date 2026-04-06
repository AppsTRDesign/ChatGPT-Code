const realtimeService = require('./realtime.service');

async function snapshot(req, res, next) {
  try {
    const data = await realtimeService.userRealtimeSnapshot(req.auth.sub);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function publishDemo(req, res, next) {
  try {
    const data = await realtimeService.publishDemoEvents(req.auth.sub);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  snapshot,
  publishDemo
};
