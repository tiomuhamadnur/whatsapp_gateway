const crypto = require('crypto');
const axios = require('axios');

async function sendWebhook(event, data) {
  const url = process.env.LARAVEL_WEBHOOK_URL;
  const secret = process.env.WEBHOOK_SECRET;

  if (!url || !secret) {
    console.warn('Webhook skipped: LARAVEL_WEBHOOK_URL or WEBHOOK_SECRET is missing.');
    return;
  }

  const payload = JSON.stringify({ event, data });
  const signature = `sha256=${crypto.createHmac('sha256', secret).update(payload).digest('hex')}`;

  await axios.post(url, payload, {
    headers: {
      'Content-Type': 'application/json',
      'X-Webhook-Signature': signature
    },
    timeout: 10000
  });
}

module.exports = { sendWebhook };
