const baileys = require('../services/baileys.service');
const webhook = require('../services/webhook.service');

async function send(req, res, next) {
  try {
    const payload = req.body;

    if (!payload.message_id || !payload.session_id || !payload.to || !payload.message) {
      return res.status(422).json({
        success: false,
        message: 'message_id, session_id, to, and message are required'
      });
    }

    try {
      const result = await baileys.sendMessage(payload);

      return res.json({ success: true, data: result });
    } catch (error) {
      await webhook.sendWebhook('message.failed', {
        message_id: payload.message_id,
        error: error.message
      });

      throw error;
    }
  } catch (error) {
    next(error);
  }
}

module.exports = { send };
