require('dotenv').config();

const express = require('express');
const sessionRoutes = require('./src/routes/session.routes');
const messageRoutes = require('./src/routes/message.routes');
const authMiddleware = require('./src/middleware/auth.middleware');
const db = require('./src/services/db.service');
const baileys = require('./src/services/baileys.service');

const app = express();
const port = Number(process.env.PORT || 3000);

app.use(express.json({ limit: '10mb' }));

app.get('/health', (_req, res) => {
  res.json({ success: true, service: 'node-wa' });
});

app.use(authMiddleware);
app.use('/sessions', sessionRoutes);
app.use('/messages', messageRoutes);

app.use((error, _req, res, _next) => {
  console.error(error);
  res.status(error.status || 500).json({
    success: false,
    message: error.message || 'Internal server error'
  });
});

async function bootstrap() {
  await db.connect();
  await baileys.restoreAllSessions();

  app.listen(port, () => {
    console.log(`node-wa listening on ${port}`);
  });
}

bootstrap().catch((error) => {
  console.error('Failed to start node-wa', error);
  process.exit(1);
});
