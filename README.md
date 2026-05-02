# WA Gateway SaaS

Laravel 12 + NodeJS Baileys monorepo untuk WhatsApp Gateway multi-tenant. Platform ini punya CMS tenant, owner CMS, API token Sanctum, package/subscription rules, queue, scheduled messages, broadcast, group/contact directory, dan Docker deployment.

## Fitur Utama

- Multi-user SaaS dengan role `client`, `admin`, `superadmin`
- Owner CMS: `/owner`
- Tenant CMS: dashboard, sessions, messages, API tokens, API docs
- WhatsApp session via QR
- Kirim pesan text/media ke kontak atau group
- Broadcast ke banyak target
- Scheduled messages: one-time, daily, weekly, monthly, custom interval
- Product plans: Free, Starter, Media, Complete, Custom
- Free plan otomatis menambahkan footer platform
- API token per pelanggan
- Datatable search, pagination, dan filter per kolom

## Akun Owner Default

Migration akan menjadikan akun ini sebagai `superadmin` bila user sudah ada:

```text
tiomuhamadnur@gmail.com
```

Jika perlu set manual:

```bash
php artisan tinker
```

```php
\App\Models\User::where('email', 'tiomuhamadnur@gmail.com')->update(['role' => 'superadmin']);
```

## Instalasi Dev Tanpa Docker

Syarat:

- PHP 8.2+
- Composer
- NodeJS 20 LTS disarankan untuk `node-wa`
- MySQL lokal
- Redis opsional

Setup Laravel:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Contoh `.env` lokal:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_gateway
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

NODE_INTERNAL_SECRET=dev-internal-secret
NODE_WA_URL=http://127.0.0.1:3000
WEBHOOK_SECRET=dev-webhook-secret
```

Buat database lalu migrate:

```bash
php artisan migrate
npm run build
```

Setup Node WA:

```bash
cd node-wa
npm install
```

File `node-wa/.env`:

```env
PORT=3000
NODE_ENV=development
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=whatsapp_gateway
DB_USER=root
DB_PASSWORD=
LARAVEL_WEBHOOK_URL=http://127.0.0.1:8000/webhook/whatsapp
INTERNAL_SECRET=dev-internal-secret
WEBHOOK_SECRET=dev-webhook-secret
SESSION_PATH=./sessions
MESSAGE_DELAY_MIN=1000
MESSAGE_DELAY_MAX=3000
```

Jalankan 4 terminal:

```bash
php artisan serve
```

```bash
php artisan queue:work database --tries=3 --timeout=30
```

```bash
php artisan schedule:work
```

```bash
cd node-wa
npm run dev
```

Buka:

```text
http://127.0.0.1:8000
http://127.0.0.1:8000/owner
```

## Instalasi Dev Dengan Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec laravel-app php artisan key:generate
docker compose exec laravel-app php artisan migrate --force
docker compose exec laravel-app npm run build
```

Queue worker sudah ada sebagai service `queue`. Untuk scheduler production, tambahkan cron host:

```cron
* * * * * docker compose exec -T laravel-app php artisan schedule:run >> /dev/null 2>&1
```

## Deployment Production Docker

1. Set `.env` production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com
DB_HOST=mysql
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
NODE_WA_URL=http://node-wa:3000
NODE_INTERNAL_SECRET=secret-panjang
WEBHOOK_SECRET=secret-hmac-panjang
```

2. Build dan start:

```bash
docker compose up -d --build
docker compose exec laravel-app php artisan migrate --force
docker compose exec laravel-app php artisan optimize
```

3. Pasang reverse proxy/SSL di depan Nginx atau expose Nginx Docker ke publik.

## API Singkat

Base URL:

```text
http://127.0.0.1:8000/api
```

Header:

```http
Authorization: Bearer {token}
Content-Type: application/json
```

Buat token dari CMS:

```text
/api-tokens
```

Atau dari owner:

```text
/owner/users
```

### Create Session

```http
POST /api/wa/sessions
```

```json
{
  "name": "CS 1"
}
```

### Get QR

```http
GET /api/wa/sessions/{session_id}/qr
```

### List Group ID

```http
GET /api/wa/sessions/{session_id}/groups
```

### List Contacts

```http
GET /api/wa/sessions/{session_id}/contacts
```

### Kirim Text ke Kontak

```http
POST /api/messages/send
```

```json
{
  "session_id": "uuid-session",
  "target_type": "contact",
  "to": "6281234567890",
  "type": "text",
  "message": "Halo"
}
```

### Kirim ke Group

```json
{
  "session_id": "uuid-session",
  "target_type": "group",
  "to": "120363xxxxx@g.us",
  "type": "text",
  "message": "Halo group"
}
```

### Broadcast

```json
{
  "session_id": "uuid-session",
  "target_type": "broadcast",
  "targets": ["6281234567890", "6289876543210", "120363xxxxx@g.us"],
  "type": "text",
  "message": "Promo hari ini"
}
```

### Scheduled Message

```json
{
  "session_id": "uuid-session",
  "target_type": "contact",
  "to": "6281234567890",
  "type": "text",
  "message": "Reminder",
  "scheduled_at": "2026-05-03 09:00:00"
}
```

### Recurring Message

```json
{
  "session_id": "uuid-session",
  "target_type": "contact",
  "to": "6281234567890",
  "type": "text",
  "message": "Daily reminder",
  "scheduled_at": "2026-05-03 09:00:00",
  "recurrence": "daily",
  "recurrence_interval": 1,
  "recurrence_until": "2026-06-03 09:00:00"
}
```

## Postman

Import collection:

```text
public/postman/wa-gateway.postman_collection.json
```

Atau download dari CMS:

```text
/api-docs
```

## Troubleshooting

- QR muncul tapi tidak connected: lihat log terminal `node-wa`, pastikan NodeJS 20 LTS, scan QR baru, dan route `/webhook/whatsapp` bisa diakses Laravel.
- `ENOTFOUND mysql` saat local tanpa Docker: buat `node-wa/.env` dengan `DB_HOST=127.0.0.1`.
- Scheduled message tidak terkirim: jalankan `php artisan schedule:work` atau cron `schedule:run`.
- Queue tidak jalan: jalankan `php artisan queue:work database` atau Redis sesuai `.env`.

## Verifikasi

```bash
php artisan test
npm run build
node --check node-wa/src/services/baileys.service.js
```
