# 📱 WhatsApp Gateway SaaS — Blueprint & Build Guide

> **Dokumen ini adalah panduan lengkap untuk membangun, melanjutkan, dan men-debug sistem WhatsApp Gateway SaaS.**
> Siapapun yang membaca dokumen ini (manusia maupun AI) harus bisa langsung paham konteks dan melanjutkan pekerjaan dari titik manapun.

---

## 🧭 Daftar Isi

1. [Konteks & Tujuan Sistem](#1-konteks--tujuan-sistem)
2. [Keputusan Arsitektur](#2-keputusan-arsitektur)
3. [Tech Stack](#3-tech-stack)
4. [Struktur Folder (Monorepo)](#4-struktur-folder-monorepo)
5. [Alur Kerja Sistem (Flow)](#5-alur-kerja-sistem-flow)
6. [Database Schema](#6-database-schema)
7. [Docker Setup](#7-docker-setup)
8. [NodeJS Service (Baileys)](#8-nodejs-service-baileys)
9. [Laravel App](#9-laravel-app)
10. [API Documentation](#10-api-documentation)
11. [Environment Variables](#11-environment-variables)
12. [Progress Tracker](#12-progress-tracker)
13. [Panduan Lanjut Build](#13-panduan-lanjut-build)

---

## 1. Konteks & Tujuan Sistem

### Apa ini?
Sebuah **SaaS WhatsApp Gateway** yang memungkinkan banyak pengguna (multi-tenant) untuk:
- Menghubungkan akun WhatsApp mereka via QR code
- Mengirim pesan WhatsApp secara programatik via REST API
- Memiliki lebih dari satu sesi/nomor WhatsApp sekaligus
- Dibatasi kuota pesan berdasarkan paket langganan (subscription)

### Target pengguna
UMKM, developer, atau bisnis yang butuh otomasi WhatsApp tanpa biaya tinggi WhatsApp Business API resmi.

### Target deployment
**VPS / server production** — bukan hanya development lokal.

---

## 2. Keputusan Arsitektur

| Keputusan | Pilihan | Alasan |
|-----------|---------|--------|
| Bahasa backend utama | Laravel 12 (PHP 8.2+) | Sudah terinstall, ekosistem matang |
| WhatsApp engine | NodeJS + Baileys | Library terbaik untuk WA unofficial |
| Struktur project | Monorepo (NodeJS di dalam Laravel) | Lebih mudah di-deploy satu repo |
| Session storage Baileys | MySQL | Lebih robust, tidak hilang jika container restart |
| Queue | Redis | Standard untuk Laravel queue production |
| Auth | Laravel Sanctum | API token, cocok untuk SaaS |
| Multi-tenant | Per-user data isolation | Setiap user punya sessions & data sendiri |
| Komunikasi Laravel ↔ NodeJS | Internal REST API (HTTP) | Simple, tidak perlu message broker |
| NodeJS hanya bisa diakses | Dari Laravel saja (internal Docker network) | Security — tidak expose ke publik |

### Prinsip Keamanan
- NodeJS **tidak pernah** diexpose ke internet
- Semua request publik masuk lewat **Laravel dulu**
- Komunikasi Laravel → NodeJS menggunakan **Bearer Token internal**
- Komunikasi NodeJS → Laravel (webhook) menggunakan **HMAC signature**

---

## 3. Tech Stack

```
┌─────────────────────────────────────────┐
│              Tech Stack                  │
├──────────────┬──────────────────────────┤
│ Laravel 12   │ PHP 8.2+, Sanctum, Queue │
│ NodeJS       │ v20 LTS, Baileys (WA)    │
│ MySQL        │ 8.0                      │
│ Redis        │ 7 Alpine                 │
│ Nginx        │ Alpine (reverse proxy)   │
│ Docker       │ Docker Compose v2        │
└──────────────┴──────────────────────────┘
```

### Package penting

**NodeJS (`node-wa/package.json`):**
```json
{
  "@whiskeysockets/baileys": "^6.x",
  "express": "^4.x",
  "mysql2": "^3.x",
  "axios": "^1.x",
  "qrcode": "^1.x",
  "pino": "^8.x"
}
```

**Laravel (composer.json tambahan):**
```
- laravel/sanctum
- laravel/breeze (untuk UI minimal)
```

---

## 4. Struktur Folder (Monorepo)

```
your-laravel-project/
│
├── 📁 app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Api/
│   │       │   ├── MessageController.php       ← kirim pesan
│   │       │   └── WhatsAppSessionController.php ← manage sesi
│   │       ├── WebhookController.php           ← terima event dari NodeJS
│   │       └── DashboardController.php
│   ├── Jobs/
│   │   └── SendWhatsAppMessage.php             ← async queue job
│   ├── Models/
│   │   ├── User.php
│   │   ├── WhatsappSession.php
│   │   ├── Message.php
│   │   ├── MessageLog.php
│   │   └── Subscription.php
│   └── Services/
│       ├── WhatsAppNodeService.php             ← HTTP client ke NodeJS
│       └── QuotaService.php                    ← cek & kurangi kuota
│
├── 📁 database/
│   └── migrations/
│       ├── xxxx_create_whatsapp_sessions_table.php
│       ├── xxxx_create_messages_table.php
│       ├── xxxx_create_message_logs_table.php
│       └── xxxx_create_subscriptions_table.php
│
├── 📁 node-wa/                                 ← NodeJS Baileys Service
│   ├── src/
│   │   ├── controllers/
│   │   │   ├── sessionController.js            ← handle /sessions/*
│   │   │   └── messageController.js            ← handle /messages/send
│   │   ├── services/
│   │   │   ├── baileys.service.js              ← core Baileys logic
│   │   │   ├── db.service.js                   ← MySQL connection
│   │   │   └── webhook.service.js              ← kirim event ke Laravel
│   │   ├── routes/
│   │   │   ├── session.routes.js
│   │   │   └── message.routes.js
│   │   └── middleware/
│   │       └── auth.middleware.js              ← validasi Bearer Token
│   ├── index.js                                ← entry point
│   ├── package.json
│   └── Dockerfile
│
├── 📁 docker/
│   ├── nginx/
│   │   └── default.conf                        ← nginx config
│   ├── php/
│   │   └── Dockerfile                          ← PHP-FPM custom
│   └── mysql/
│       └── init.sql                            ← initial DB setup (opsional)
│
├── 📁 sessions/                                ← volume untuk Baileys session files
│
├── docker-compose.yml
├── .env.example
└── BLUEPRINT.md                                ← file ini
```

---

## 5. Alur Kerja Sistem (Flow)

### 5.1 Alur Connect WhatsApp (QR Login)

```
User (Browser/API)
    │
    ▼
[Laravel] POST /api/wa/sessions/connect
    │  Simpan session ke DB (status: connecting)
    │
    ▼
[NodeJS] POST /sessions/connect  (internal)
    │  Baileys buat koneksi baru
    │  Generate QR code
    │
    ▼
[NodeJS → Laravel] POST /webhook/whatsapp
    │  event: session.qr
    │  data: { session_id, qr_base64 }
    │
    ▼
[Laravel] Simpan QR ke cache/DB
    │
    ▼
User GET /api/wa/sessions/qr?session_id=xxx
    │
    ▼
[Laravel] Ambil QR dari cache → return ke user
    │
    ▼
User scan QR pakai HP → WhatsApp connected
    │
    ▼
[NodeJS → Laravel] POST /webhook/whatsapp
    event: session.update
    data: { session_id, status: "connected" }
    │
    ▼
[Laravel] Update status session di DB → "connected"
```

### 5.2 Alur Kirim Pesan

```
Client App / User
    │
    ▼
POST /api/messages/send
    Headers: Authorization: Bearer {sanctum_token}
    Body: { session_id, to, type, message }
    │
    ▼
[Laravel] MessageController
    │  1. Validasi token (Sanctum)
    │  2. Cek kepemilikan session (user_id)
    │  3. Cek kuota subscription (QuotaService)
    │  4. Simpan message ke DB (status: queued)
    │
    ▼
[Laravel] Dispatch Job → Redis Queue
    SendWhatsAppMessage::dispatch($message)
    │
    ▼
[Queue Worker] SendWhatsAppMessage Job
    │  1. Ambil message dari DB
    │  2. Call WhatsAppNodeService
    │
    ▼
[NodeJS] POST /messages/send  (internal)
    │  1. Validasi Bearer Token
    │  2. Cek session aktif
    │  3. Random delay 1–5 detik (anti-spam)
    │  4. Kirim via Baileys
    │  5. Retry 3x jika gagal
    │
    ▼
[NodeJS → Laravel] POST /webhook/whatsapp
    event: message.sent | message.failed
    data: { message_id, status, error? }
    │
    ▼
[Laravel] WebhookController
    Update status message di DB
    Catat di message_logs
```

### 5.3 Alur Terima Pesan (Inbound)

```
WhatsApp → Baileys (NodeJS)
    │
    ▼
[NodeJS → Laravel] POST /webhook/whatsapp
    event: message.received
    data: { session_id, from, message, timestamp }
    │
    ▼
[Laravel] WebhookController
    Simpan ke message_logs (direction: inbound)
    (Future: trigger user webhook jika ada)
```

---

## 6. Database Schema

### Tabel: `users` (default Laravel + tambahan)
```sql
ALTER TABLE users ADD COLUMN role ENUM('admin', 'client') DEFAULT 'client';
```

### Tabel: `subscriptions`
```sql
CREATE TABLE subscriptions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    plan_name       VARCHAR(50) NOT NULL DEFAULT 'free',
    message_quota   INT NOT NULL DEFAULT 100,          -- quota per period
    messages_used   INT NOT NULL DEFAULT 0,            -- used this period
    max_sessions    INT NOT NULL DEFAULT 1,            -- max WA sessions
    starts_at       TIMESTAMP NOT NULL,
    ends_at         TIMESTAMP NOT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_ends_at (ends_at)
);
```

### Tabel: `whatsapp_sessions`
```sql
CREATE TABLE whatsapp_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    session_id      VARCHAR(36) NOT NULL UNIQUE,       -- UUID
    name            VARCHAR(100) NULL,                  -- label dari user
    phone_number    VARCHAR(20) NULL,                   -- nomor WA setelah login
    status          ENUM('connecting','qr_ready','connected','disconnected','banned') DEFAULT 'connecting',
    qr_code         TEXT NULL,                          -- base64 QR (temporary)
    session_data    LONGTEXT NULL,                      -- Baileys session (JSON, encrypted)
    last_active_at  TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_session_id (session_id)
);
```

### Tabel: `messages`
```sql
CREATE TABLE messages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    session_id      VARCHAR(36) NOT NULL,
    direction       ENUM('outbound','inbound') DEFAULT 'outbound',
    to_number       VARCHAR(20) NULL,                   -- null if inbound
    from_number     VARCHAR(20) NULL,                   -- null if outbound
    type            ENUM('text','image','document','audio','video') DEFAULT 'text',
    content         TEXT NOT NULL,
    media_url       VARCHAR(500) NULL,
    status          ENUM('queued','sending','sent','failed','received') DEFAULT 'queued',
    wa_message_id   VARCHAR(100) NULL,                  -- ID dari WhatsApp
    error_message   TEXT NULL,
    retry_count     TINYINT DEFAULT 0,
    scheduled_at    TIMESTAMP NULL,
    sent_at         TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_session (user_id, session_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### Tabel: `message_logs`
```sql
CREATE TABLE message_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id      BIGINT UNSIGNED NOT NULL,
    event           VARCHAR(50) NOT NULL,               -- queued, sent, failed, etc.
    description     TEXT NULL,
    meta            JSON NULL,                          -- data tambahan (error detail, dll)
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    INDEX idx_message_id (message_id),
    INDEX idx_event (event)
);
```

---

## 7. Docker Setup

### Layanan Docker

| Service | Image | Internal Port | External Port |
|---------|-------|--------------|---------------|
| `nginx` | nginx:alpine | 80 | 80, 443 |
| `laravel-app` | custom PHP-FPM | 9000 | — |
| `node-wa` | custom NodeJS | 3000 | — (internal only) |
| `mysql` | mysql:8.0 | 3306 | 3306 (dev only) |
| `redis` | redis:7-alpine | 6379 | — |

### Volumes

| Volume | Tujuan |
|--------|--------|
| `mysql_data` | Persistensi database MySQL |
| `sessions_data` | File session Baileys (backup) |
| `laravel_storage` | Storage Laravel |

### Network

Semua service dalam network internal `wa_network`. Hanya nginx yang expose ke host.

### File yang perlu dibuat:
- [x] `docker-compose.yml`
- [x] `docker/nginx/default.conf`
- [x] `docker/php/Dockerfile`
- [x] `node-wa/Dockerfile`

---

## 8. NodeJS Service (Baileys)

### Entry point: `node-wa/index.js`
- Express server di port 3000
- Load semua routes
- Connect ke MySQL
- Auto-restore semua session yang statusnya `connected` dari DB saat startup

### Core Logic: `baileys.service.js`

```
SessionManager (Map)
    ├── createSession(sessionId)    → init Baileys, listen events
    ├── getSession(sessionId)       → ambil instance aktif
    ├── disconnectSession(sessionId)
    └── restoreAllSessions()        → dipanggil saat startup
```

### Baileys Event yang dihandle:

| Event Baileys | Aksi |
|---------------|------|
| `connection.update` | Update status, kirim webhook ke Laravel |
| `creds.update` | Simpan session ke MySQL |
| `messages.upsert` | Kirim webhook inbound message ke Laravel |

### API Endpoints (internal):

| Method | Path | Deskripsi |
|--------|------|-----------|
| POST | `/sessions/connect` | Buat sesi baru |
| GET | `/sessions/qr` | Ambil QR code |
| GET | `/sessions/status` | Cek status sesi |
| POST | `/sessions/disconnect` | Putus koneksi |
| POST | `/messages/send` | Kirim pesan |

### Auth NodeJS
Semua request ke NodeJS harus menyertakan:
```
Authorization: Bearer {NODE_INTERNAL_SECRET}
```
Value dari env `NODE_INTERNAL_SECRET` — sama antara Laravel dan NodeJS.

---

## 9. Laravel App

### Auth: Sanctum
- User register/login → dapat API token
- Token dikirim di header: `Authorization: Bearer {token}`

### Services

#### `WhatsAppNodeService.php`
```php
// HTTP Client ke NodeJS internal
- connectSession(string $sessionId): array
- getQr(string $sessionId): string
- getStatus(string $sessionId): string
- disconnectSession(string $sessionId): void
- sendMessage(array $payload): array
```

#### `QuotaService.php`
```php
// Cek dan kurangi kuota
- hasQuota(User $user): bool
- decrementQuota(User $user): void
- getRemainingQuota(User $user): int
```

### Jobs

#### `SendWhatsAppMessage.php`
```
Queue: default
Tries: 3
Timeout: 30s
Backoff: [10, 30, 60] detik

Logic:
1. Load message dari DB
2. Cek status session masih connected
3. Call WhatsAppNodeService::sendMessage()
4. Update status message
5. Log ke message_logs
```

### Controllers

#### `WhatsAppSessionController.php` (API)
```
POST   /api/wa/sessions              → store()   buat sesi baru
GET    /api/wa/sessions              → index()   list sesi milik user
GET    /api/wa/sessions/{id}/qr      → qr()      ambil QR code
GET    /api/wa/sessions/{id}/status  → status()  cek status
DELETE /api/wa/sessions/{id}         → destroy() hapus sesi
```

#### `MessageController.php` (API)
```
POST /api/messages/send   → send()   kirim pesan (dispatch ke queue)
GET  /api/messages        → index()  history pesan
```

#### `WebhookController.php`
```
POST /webhook/whatsapp    → handle()  terima event dari NodeJS
    - Validasi HMAC signature
    - Route ke handler berdasarkan event type
    - Update DB sesuai event
```

### Middleware
- `auth:sanctum` — proteksi semua `/api/*`
- `CheckSubscriptionQuota` — cek kuota sebelum kirim pesan

---

## 10. API Documentation

### Base URL
```
https://yourdomain.com/api
```

### Authentication
```
Authorization: Bearer {sanctum_token}
```

---

### Auth Endpoints

#### Register
```http
POST /auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```
Response: `{ "token": "...", "user": {...} }`

#### Login
```http
POST /auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```
Response: `{ "token": "...", "user": {...} }`

---

### WhatsApp Session Endpoints

#### Create Session
```http
POST /api/wa/sessions
Authorization: Bearer {token}

{
    "name": "Toko Online Saya"
}
```
Response:
```json
{
    "success": true,
    "data": {
        "session_id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "connecting"
    }
}
```

#### Get QR Code
```http
GET /api/wa/sessions/{session_id}/qr
Authorization: Bearer {token}
```
Response:
```json
{
    "success": true,
    "data": {
        "qr": "data:image/png;base64,iVBOR..."
    }
}
```

#### Get Status
```http
GET /api/wa/sessions/{session_id}/status
Authorization: Bearer {token}
```
Response:
```json
{
    "success": true,
    "data": {
        "session_id": "550e8400-...",
        "status": "connected",
        "phone_number": "6281234567890",
        "last_active_at": "2024-01-15T10:30:00Z"
    }
}
```

#### Disconnect Session
```http
DELETE /api/wa/sessions/{session_id}
Authorization: Bearer {token}
```

---

### Message Endpoints

#### Send Message
```http
POST /api/messages/send
Authorization: Bearer {token}

{
    "session_id": "550e8400-e29b-41d4-a716-446655440000",
    "to": "6281234567890",
    "type": "text",
    "message": "Halo! Ada yang bisa kami bantu?"
}
```

Response (success):
```json
{
    "success": true,
    "data": {
        "message_id": 1234,
        "status": "queued"
    }
}
```

Response (quota exceeded):
```json
{
    "success": false,
    "message": "Kuota pesan habis. Silakan upgrade paket Anda.",
    "code": "QUOTA_EXCEEDED"
}
```

#### Send Image
```http
POST /api/messages/send
Authorization: Bearer {token}

{
    "session_id": "550e8400-...",
    "to": "6281234567890",
    "type": "image",
    "message": "Caption gambar",
    "media_url": "https://example.com/image.jpg"
}
```

#### Get Message History
```http
GET /api/messages?session_id={id}&page=1&per_page=20
Authorization: Bearer {token}
```

---

### Webhook (NodeJS → Laravel)

```http
POST /webhook/whatsapp
X-Webhook-Signature: sha256={hmac_signature}
Content-Type: application/json
```

#### Event: session.qr
```json
{
    "event": "session.qr",
    "data": {
        "session_id": "550e8400-...",
        "qr": "data:image/png;base64,..."
    }
}
```

#### Event: session.update
```json
{
    "event": "session.update",
    "data": {
        "session_id": "550e8400-...",
        "status": "connected",
        "phone_number": "6281234567890"
    }
}
```

#### Event: message.sent
```json
{
    "event": "message.sent",
    "data": {
        "message_id": 1234,
        "wa_message_id": "ABCD1234",
        "status": "sent",
        "sent_at": "2024-01-15T10:30:00Z"
    }
}
```

#### Event: message.failed
```json
{
    "event": "message.failed",
    "data": {
        "message_id": 1234,
        "error": "Session disconnected"
    }
}
```

#### Event: message.received
```json
{
    "event": "message.received",
    "data": {
        "session_id": "550e8400-...",
        "from": "6281234567890",
        "type": "text",
        "message": "Halo",
        "timestamp": 1705312200
    }
}
```

---

## 11. Environment Variables

### Laravel `.env`
```env
APP_NAME="WA Gateway"
APP_ENV=production
APP_KEY=              # generate dengan: php artisan key:generate
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=wa_gateway
DB_USERNAME=wa_user
DB_PASSWORD=secret_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PORT=6379

# Internal secret untuk komunikasi Laravel ↔ NodeJS
NODE_INTERNAL_SECRET=your-super-secret-internal-token-change-this

# URL NodeJS (internal Docker network)
NODE_WA_URL=http://node-wa:3000

# HMAC secret untuk validasi webhook dari NodeJS
WEBHOOK_SECRET=your-webhook-hmac-secret-change-this
```

### NodeJS `node-wa/.env`
```env
PORT=3000
NODE_ENV=production

# MySQL (sama dengan Laravel)
DB_HOST=mysql
DB_PORT=3306
DB_NAME=wa_gateway
DB_USER=wa_user
DB_PASSWORD=secret_password

# Laravel webhook URL (internal Docker network)
LARAVEL_WEBHOOK_URL=http://laravel-app:9000
# ATAU jika via nginx internal:
# LARAVEL_WEBHOOK_URL=http://nginx/webhook/whatsapp

# Secret yang sama dengan NODE_INTERNAL_SECRET di Laravel
INTERNAL_SECRET=your-super-secret-internal-token-change-this

# HMAC secret untuk sign webhook ke Laravel
WEBHOOK_SECRET=your-webhook-hmac-secret-change-this

# Rate limit per session (pesan per menit)
RATE_LIMIT_PER_SESSION=20

# Delay random antar pesan (ms)
MESSAGE_DELAY_MIN=1000
MESSAGE_DELAY_MAX=5000
```

---

## 12. Progress Tracker

Gunakan section ini untuk tracking progress build. Update setiap sesi kerja.

### ✅ Selesai
- [x] Blueprint & dokumentasi (file ini)
- [x] Backend Laravel API
- [x] NodeJS service scaffold
- [x] Docker scaffold
- [x] CMS Laravel: login, register, dashboard, sessions, messages, API tokens
- [x] API documentation inside CMS
- [x] Postman collection: `public/postman/wa-gateway.postman_collection.json`
- [x] Owner CMS: dashboard platform, all users, all sessions/devices, all chat history
- [x] Product plan management: Free, Starter, Media, Complete, Custom
- [x] Daily quota enforcement, message-type rules, forced footer for Free plan
- [x] Group ID and contact listing endpoints
- [x] Data table search + pagination component
- [x] Default owner: `tiomuhamadnur@gmail.com` as `superadmin`
- [x] Persistent group/contact cache tables
- [x] Scheduled message and recurring scheduler command
- [x] Broadcast and group-target message API
- [x] Subscription pricing and active/expired dates
- [x] Modal-based owner CRUD
- [x] Collapsible icon sidebar and top account/subscription header
- [x] Modern non-copyright SVG logo
- [x] README dev/prod installation guide

### 🔄 Sedang Dikerjakan
- [ ] End-to-end WhatsApp/device testing

### 📋 Belum Dikerjakan

#### Docker & Infrastructure
- [x] `docker-compose.yml`
- [x] `docker/nginx/default.conf`
- [x] `docker/php/Dockerfile`
- [x] `node-wa/Dockerfile`

#### Database (Laravel Migrations)
- [x] Migration: `personal_access_tokens`
- [x] Migration: `whatsapp_sessions`
- [x] Migration: `messages`
- [x] Migration: `message_logs`
- [x] Migration: `subscriptions`
- [x] Migration: alter `users` add `role`

#### NodeJS Service
- [x] `node-wa/package.json`
- [x] `node-wa/index.js`
- [x] `node-wa/src/services/db.service.js`
- [x] `node-wa/src/services/baileys.service.js`
- [x] `node-wa/src/services/webhook.service.js`
- [x] `node-wa/src/middleware/auth.middleware.js`
- [x] `node-wa/src/controllers/sessionController.js`
- [x] `node-wa/src/controllers/messageController.js`
- [x] `node-wa/src/routes/session.routes.js`
- [x] `node-wa/src/routes/message.routes.js`

#### Laravel App
- [x] Install Sanctum
- [x] CMS auth tanpa Breeze
- [x] Model: `WhatsappSession`
- [x] Model: `Message`
- [x] Model: `MessageLog`
- [x] Model: `Subscription`
- [x] Service: `WhatsAppNodeService`
- [x] Service: `QuotaService`
- [x] Job: `SendWhatsAppMessage`
- [x] Controller: `WhatsAppSessionController`
- [x] Controller: `MessageController`
- [x] Controller: `WebhookController`
- [x] Middleware: `CheckSubscriptionQuota`
- [x] Routes: `routes/api.php`
- [x] Routes: `routes/web.php` CMS
- [x] CMS: Dashboard
- [x] CMS: Sessions
- [x] CMS: Messages
- [x] CMS: API Tokens
- [x] CMS: API Documentation
- [x] Owner CMS: All Users
- [x] Owner CMS: Product Plans
- [x] Owner CMS: All Sessions / Devices
- [x] Owner CMS: All Messages / Chat History
- [x] API: `GET /api/wa/sessions/{session_id}/groups`
- [x] API: `GET /api/wa/sessions/{session_id}/contacts`
- [x] API: group message via `target_type=group`
- [x] API: broadcast message via `target_type=broadcast`
- [x] API: scheduled and recurring messages
- [x] Command: `messages:dispatch-scheduled`
- [x] Scheduler: dispatch scheduled messages every minute
- [x] Tables: `whatsapp_groups`, `whatsapp_contacts`
- [x] Tables: pricing fields on plans/subscriptions

#### Testing & Deployment
- [ ] Test connect WA via QR
- [ ] Test kirim pesan text
- [ ] Test queue worker
- [ ] Test multi-user
- [ ] Test session restore setelah restart

---

## 13. Panduan Lanjut Build

### Cara melanjutkan sesi build dengan AI

Copy paste instruksi berikut ke AI model saat memulai sesi baru:

```
Saya sedang membangun WhatsApp Gateway SaaS. 
Baca file BLUEPRINT.md untuk memahami konteks lengkap sistem ini.

Ini adalah [Laravel 12 monorepo / NodeJS service / Docker setup].
Progress saat ini ada di section "Progress Tracker" di BLUEPRINT.md.

Lanjutkan build dari: [sebutkan item yang belum selesai]
```

### Urutan build yang disarankan

```
1. docker-compose.yml + semua Dockerfile
        ↓
2. Database migrations (jalankan: php artisan migrate)
        ↓
3. NodeJS service (node-wa/) - test standalone dulu
        ↓
4. Install Sanctum + setup auth Laravel
        ↓
5. Models + Services Laravel
        ↓
6. Job + Queue setup
        ↓
7. Controllers + Routes
        ↓
8. End-to-end test
```

### Perintah penting

```bash
# Start semua service
docker compose up -d

# Lihat logs
docker compose logs -f node-wa
docker compose logs -f laravel-app

# Jalankan migration
docker compose exec laravel-app php artisan migrate

# Jalankan queue worker
docker compose exec laravel-app php artisan queue:work redis --tries=3 --timeout=30

# Masuk ke container
docker compose exec laravel-app bash
docker compose exec node-wa sh

# Test NodeJS API internal (dari dalam container Laravel)
curl -H "Authorization: Bearer {NODE_INTERNAL_SECRET}" \
     http://node-wa:3000/sessions/status?session_id=test
```

### Troubleshooting umum

| Masalah | Kemungkinan Penyebab | Solusi |
|---------|---------------------|--------|
| NodeJS tidak bisa connect ke MySQL | DB belum siap saat node start | Tambah `depends_on` + health check di docker-compose |
| QR tidak muncul | Webhook gagal dikirim ke Laravel | Cek `LARAVEL_WEBHOOK_URL` di .env NodeJS |
| Session hilang setelah restart | Session tidak tersimpan ke DB | Cek handler `creds.update` di baileys.service.js |
| Queue tidak jalan | Worker belum distart | Jalankan `php artisan queue:work` |
| Pesan gagal semua | Session disconnected | Cek status session, minta user scan ulang QR |

---

> **Last updated:** Sesi build kelima — modal CRUD, persistent directory cache, scheduled/recurring/broadcast/group messaging, pricing, collapsible sidebar, logo, README install guide, dan UI shell modern selesai dibuat.
> **Next step:** Test end-to-end scheduled delivery dengan `php artisan schedule:work`, test broadcast quota, dan validasi UX di mobile/tablet.
