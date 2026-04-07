const chatService = require('./chat.service');

async function list(req, res, next) {
  try {
    const data = await chatService.listMessages(req.query.scopeType, req.query.scopeId, req.query.limit);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function send(req, res, next) {
  try {
    const data = await chatService.sendMessage(req.auth.sub, req.body.scopeType, req.body.scopeId, req.body.message);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  list,
  send
};
