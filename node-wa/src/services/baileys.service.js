const fs = require('fs');
const path = require('path');
const makeWASocket = require('@whiskeysockets/baileys').default;
const {
  DisconnectReason,
  fetchLatestBaileysVersion,
  useMultiFileAuthState
} = require('@whiskeysockets/baileys');
const pino = require('pino');
const QRCode = require('qrcode');
const db = require('./db.service');
const webhook = require('./webhook.service');

const sessions = new Map();
const logger = pino({ level: process.env.LOG_LEVEL || (process.env.NODE_ENV === 'development' ? 'info' : 'silent') });
const sessionRoot = process.env.SESSION_PATH || path.join(process.cwd(), 'sessions');

function sessionPath(sessionId) {
  return path.join(sessionRoot, sessionId);
}

async function createSession(sessionId) {
  if (sessions.has(sessionId)) {
    return sessionSnapshot(sessionId);
  }

  console.log(`[session:${sessionId}] creating WhatsApp socket`);
  fs.mkdirSync(sessionPath(sessionId), { recursive: true });

  const { state, saveCreds } = await useMultiFileAuthState(sessionPath(sessionId));
  const { version } = await fetchLatestBaileysVersion();

  console.log(`[session:${sessionId}] using Baileys version ${version.join('.')}`);

  const sock = makeWASocket({
    version,
    auth: state,
    logger,
    printQRInTerminal: false,
    browser: ['WA Gateway', 'Chrome', '1.0.0']
  });

  sessions.set(sessionId, {
    sock,
    status: 'connecting',
    qr: null,
    contacts: new Map()
  });

  sock.ev.on('creds.update', async () => {
    await saveCreds();
    await db.updateSession(sessionId, {
      session_data: JSON.stringify({ storage: 'multi_file', path: sessionPath(sessionId) })
    });
  });

  sock.ev.on('connection.update', async (update) => {
    try {
      await handleConnectionUpdate(sessionId, update);
    } catch (error) {
      console.error(`[session:${sessionId}] failed to handle connection update`, error);
    }
  });

  sock.ev.on('messages.upsert', async ({ messages, type }) => {
    if (type !== 'notify') {
      return;
    }

    for (const message of messages) {
      await handleInboundMessage(sessionId, message);
    }
  });

  return sessionSnapshot(sessionId);
}

function getSession(sessionId) {
  return sessions.get(sessionId) || null;
}

async function disconnectSession(sessionId) {
  const session = getSession(sessionId);

  if (session?.sock) {
    await session.sock.logout();
  }

  sessions.delete(sessionId);
  await db.updateSession(sessionId, {
    status: 'disconnected',
    qr_code: null
  });
  await safeWebhook('session.update', {
    session_id: sessionId,
    status: 'disconnected'
  });
}

async function restoreAllSessions() {
  const sessionIds = await db.connectedSessions();

  for (const sessionId of sessionIds) {
    try {
      await createSession(sessionId);
    } catch (error) {
      console.error(`Failed to restore session ${sessionId}`, error);
    }
  }
}

async function sendMessage(payload) {
  const session = getSession(payload.session_id);

  if (!session?.sock || session.status !== 'connected') {
    throw Object.assign(new Error('Session disconnected'), { status: 422 });
  }

  await randomDelay();

  const jid = normalizeJid(payload.to);
  const content = buildMessageContent(payload);
  const result = await retry(() => session.sock.sendMessage(jid, content), 3);
  const waMessageId = result?.key?.id || null;

  await webhook.sendWebhook('message.sent', {
    message_id: payload.message_id,
    wa_message_id: waMessageId,
    status: 'sent',
    sent_at: new Date().toISOString()
  });

  return {
    status: 'sent',
    wa_message_id: waMessageId
  };
}

async function listGroups(sessionId) {
  const session = getSession(sessionId);

  if (!session?.sock || session.status !== 'connected') {
    throw Object.assign(new Error('Session disconnected'), { status: 422 });
  }

  const groups = await session.sock.groupFetchAllParticipating();

  return Object.values(groups).map((group) => ({
    id: group.id,
    name: group.subject,
    participants_count: group.participants?.length || 0,
    owner: group.owner || null,
    created_at: group.creation || null
  }));
}

async function listContacts(sessionId) {
  const session = getSession(sessionId);

  if (!session?.sock || session.status !== 'connected') {
    throw Object.assign(new Error('Session disconnected'), { status: 422 });
  }

  return Array.from(session.contacts.values());
}

async function handleConnectionUpdate(sessionId, update) {
  const session = getSession(sessionId);

  if (!session) {
    return;
  }

  console.log(`[session:${sessionId}] connection.update`, {
    connection: update.connection,
    hasQr: Boolean(update.qr),
    statusCode: update.lastDisconnect?.error?.output?.statusCode,
    message: update.lastDisconnect?.error?.message
  });

  if (update.qr) {
    const qr = await QRCode.toDataURL(update.qr);
    session.status = 'qr_ready';
    session.qr = qr;

    await db.updateSession(sessionId, {
      status: 'qr_ready',
      qr_code: qr
    });
    await safeWebhook('session.qr', {
      session_id: sessionId,
      qr
    });
  }

  if (update.connection === 'open') {
    const phoneNumber = session.sock.user?.id?.split(':')[0] || null;
    session.status = 'connected';
    session.qr = null;

    if (session.sock.user?.id) {
      session.contacts.set(session.sock.user.id, {
        id: session.sock.user.id,
        number: phoneNumber,
        name: session.sock.user.name || 'Me',
        source: 'self'
      });
    }

    await db.updateSession(sessionId, {
      status: 'connected',
      qr_code: null,
      phone_number: phoneNumber,
      last_active_at: new Date()
    });
    await safeWebhook('session.update', {
      session_id: sessionId,
      status: 'connected',
      phone_number: phoneNumber
    });

    console.log(`[session:${sessionId}] connected as ${phoneNumber || 'unknown phone'}`);
  }

  if (update.connection === 'close') {
    const statusCode = update.lastDisconnect?.error?.output?.statusCode;
    const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
    session.status = 'disconnected';

    console.warn(`[session:${sessionId}] connection closed`, {
      statusCode,
      shouldReconnect,
      reason: update.lastDisconnect?.error?.message
    });

    await db.updateSession(sessionId, {
      status: 'disconnected',
      qr_code: null
    });
    await safeWebhook('session.update', {
      session_id: sessionId,
      status: 'disconnected'
    });

    sessions.delete(sessionId);

    if (shouldReconnect) {
      setTimeout(() => createSession(sessionId).catch(console.error), 3000);
    }
  }
}

async function handleInboundMessage(sessionId, message) {
  if (!message.message || message.key.fromMe) {
    return;
  }

  const from = message.key.remoteJid?.replace('@s.whatsapp.net', '') || null;
  const text = extractText(message.message);
  const session = getSession(sessionId);

  if (session && message.key.remoteJid) {
    session.contacts.set(message.key.remoteJid, {
      id: message.key.remoteJid,
      number: from,
      name: message.pushName || null,
      source: 'message'
    });
  }

  await safeWebhook('message.received', {
    session_id: sessionId,
    from,
    type: 'text',
    message: text,
    timestamp: Number(message.messageTimestamp || Math.floor(Date.now() / 1000))
  });
}

async function safeWebhook(event, data) {
  try {
    await webhook.sendWebhook(event, data);
  } catch (error) {
    console.error(`[webhook:${event}] failed`, error.response?.status || error.message);
  }
}

function sessionSnapshot(sessionId) {
  const session = getSession(sessionId);

  return {
    session_id: sessionId,
    status: session?.status || 'disconnected',
    qr: session?.qr || null
  };
}

function normalizeJid(number) {
  if (String(number).includes('@g.us') || String(number).includes('@s.whatsapp.net')) {
    return String(number);
  }

  const clean = String(number).replace(/\D/g, '');

  return `${clean}@s.whatsapp.net`;
}

function buildMessageContent(payload) {
  if (payload.type === 'image') {
    return {
      image: { url: payload.media_url },
      caption: payload.message || ''
    };
  }

  if (payload.type === 'document') {
    return {
      document: { url: payload.media_url },
      fileName: payload.file_name || 'document',
      caption: payload.message || ''
    };
  }

  if (payload.type === 'audio') {
    return {
      audio: { url: payload.media_url },
      mimetype: payload.mimetype || 'audio/mpeg'
    };
  }

  if (payload.type === 'video') {
    return {
      video: { url: payload.media_url },
      caption: payload.message || ''
    };
  }

  return { text: payload.message };
}

function extractText(message) {
  return message.conversation
    || message.extendedTextMessage?.text
    || message.imageMessage?.caption
    || message.videoMessage?.caption
    || '';
}

async function randomDelay() {
  const min = Number(process.env.MESSAGE_DELAY_MIN || 1000);
  const max = Number(process.env.MESSAGE_DELAY_MAX || 5000);
  const delay = Math.floor(Math.random() * (max - min + 1)) + min;

  await new Promise((resolve) => setTimeout(resolve, delay));
}

async function retry(callback, attempts) {
  let lastError;

  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    try {
      return await callback();
    } catch (error) {
      lastError = error;
      await new Promise((resolve) => setTimeout(resolve, attempt * 1000));
    }
  }

  throw lastError;
}

module.exports = {
  createSession,
  getSession,
  disconnectSession,
  restoreAllSessions,
  sendMessage,
  listGroups,
  listContacts
};
