const baileys = require('../services/baileys.service');

async function connect(req, res, next) {
  try {
    const { session_id: sessionId } = req.body;

    if (!sessionId) {
      return res.status(422).json({ success: false, message: 'session_id is required' });
    }

    const session = await baileys.createSession(sessionId);

    res.status(202).json({ success: true, data: session });
  } catch (error) {
    next(error);
  }
}

function qr(req, res) {
  const session = baileys.getSession(req.query.session_id);

  res.json({
    success: true,
    data: {
      qr: session?.qr || null
    }
  });
}

function status(req, res) {
  const sessionId = req.query.session_id;
  const session = baileys.getSession(sessionId);

  res.json({
    success: true,
    data: {
      session_id: sessionId,
      status: session?.status || 'disconnected'
    }
  });
}

async function disconnect(req, res, next) {
  try {
    const { session_id: sessionId } = req.body;

    if (!sessionId) {
      return res.status(422).json({ success: false, message: 'session_id is required' });
    }

    await baileys.disconnectSession(sessionId);

    res.json({ success: true });
  } catch (error) {
    next(error);
  }
}

async function groups(req, res, next) {
  try {
    const data = await baileys.listGroups(req.query.session_id);

    res.json({ success: true, data });
  } catch (error) {
    next(error);
  }
}

async function contacts(req, res, next) {
  try {
    const data = await baileys.listContacts(req.query.session_id);

    res.json({ success: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  connect,
  qr,
  status,
  disconnect,
  groups,
  contacts
};
