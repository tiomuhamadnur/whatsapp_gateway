const mysql = require('mysql2/promise');

let pool;

async function connect() {
  pool = mysql.createPool({
    host: process.env.DB_HOST || 'mysql',
    port: Number(process.env.DB_PORT || 3306),
    database: process.env.DB_NAME || 'wa_gateway',
    user: process.env.DB_USER || 'wa_user',
    password: process.env.DB_PASSWORD || '',
    waitForConnections: true,
    connectionLimit: 10,
    namedPlaceholders: true
  });

  await pool.query('SELECT 1');
}

function client() {
  if (!pool) {
    throw new Error('Database is not connected.');
  }

  return pool;
}

async function updateSession(sessionId, values) {
  const allowed = ['status', 'qr_code', 'phone_number', 'session_data', 'last_active_at'];
  const keys = Object.keys(values).filter((key) => allowed.includes(key));

  if (keys.length === 0) {
    return;
  }

  const assignments = keys.map((key) => `\`${key}\` = :${key}`).join(', ');

  await client().execute(
    `UPDATE whatsapp_sessions SET ${assignments}, updated_at = NOW() WHERE session_id = :sessionId`,
    { ...values, sessionId }
  );
}

async function connectedSessions() {
  const [rows] = await client().execute(
    'SELECT session_id FROM whatsapp_sessions WHERE status = :status',
    { status: 'connected' }
  );

  return rows.map((row) => row.session_id);
}

module.exports = {
  connect,
  client,
  updateSession,
  connectedSessions
};
