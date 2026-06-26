# WatchTower — Uptime Monitor

Aplikasi monitoring uptime website, server, database, domain, dan kesehatan API — dibangun dengan Laravel 12.
Mendukung 10 tipe monitor, notifikasi multi-channel, on-call schedule, SLA contract, flap detection, incident tracking, dan status page publik.

---

## Fitur

### Monitor
- **HTTP / HTTPS** — cek konektivitas URL; UP selama merespons, DOWN hanya jika koneksi gagal total
- **Keyword** — HTTP + cek keberadaan string di response body
- **Ping (ICMP)** — cocok untuk server yang memblokir HTTP
- **TCP Port** — cek konektivitas port (database, SMTP, dll)
- **DNS** — resolve domain dan bandingkan dengan expected value
- **Push Heartbeat** — endpoint untuk monitoring cronjob / background worker
- **Cron Job Monitor** — DOWN otomatis jika tidak ada heartbeat dalam interval yang dikonfigurasi
- **Database** — koneksi langsung ke MySQL, PostgreSQL, atau Redis
- **Docker Container** — cek status container via Docker socket atau remote API
- **WHOIS / Domain Expiry** — alert X hari sebelum domain expired

### Alerting & Notifikasi
- **Multi-channel notifikasi** — Telegram, WhatsApp (Fonnte), Email (SMTP), Slack, Discord, ntfy.sh, Pushover, Webhook HTTP
- **Flap Detection** — tahan notifikasi jika monitor UP-DOWN berulang dalam window waktu tertentu
- **Business Hours Alerting** — routing notifikasi berbeda di jam kerja vs luar jam kerja
- **On-Call Schedule** — jadwal shift siaga tim; alert otomatis dikirim ke channel siaga yang aktif
- **Eskalasi Insiden** — dispatch notifikasi ke channel eskalasi jika DOWN tidak di-ack dalam X menit
- **Correlated Major Incident** — 5+ monitor DOWN bersamaan otomatis dibuat sebagai Major Incident
- **Alert Suppression** — regex pattern pada response body untuk mencegah false positive

### Monitoring Lanjutan
- **Response Time Warning** — alert terpisah jika response time melebihi threshold (ms)
- **Latency Trend Alert** — warning jika response time naik konsisten 5 cek terakhir
- **Response Size Check** — alert jika response terlalu kecil atau terlalu besar
- **Body Assertion** — validasi JSON path dari response body ($.status === "ok")
- **HTTP Auth** — Basic Auth dan Bearer Token per monitor
- **Custom Headers** — header tambahan per monitor (JSON)
- **SSL Certificate Check** — alert jika SSL hampir expired (≤30 hari); berjalan 2x sehari
- **Domain Expiry Check** — alert jika domain hampir expired
- **Accepted Status Codes** — set HTTP code mana yang dianggap UP (default 200–399)
- **Ignore TLS Error** — bypass SSL cert invalid untuk server internal
- **Follow Redirects** — toggle + max redirect count per monitor
- **Custom User-Agent** — set UA per monitor agar tidak diblock server
- **HTTP Proxy** — routing request lewat proxy SOCKS5/HTTP per monitor

### Incident & SLA
- **Incident Tracking** — insiden DOWN/UP tercatat otomatis; tambah manual untuk gangguan umum
- **Incident Auto-Close** — otomatis tutup insiden jika UP selama X menit berturut-turut
- **SLA Contract** — target SLA per layanan, tracking sisa downtime budget, progress bar
- **Post-Mortem Template** — template RCA otomatis saat insiden ditutup
- **Audit Log** — semua aksi (check, toggle, create, delete) tercatat dengan timestamp dan IP

### Organisasi & Dashboard
- **Tags / Grup** — multi-tag per monitor, filter dashboard per tag, badge warna
- **Monitor Clone** — duplikat monitor beserta semua setting dan tag
- **Monitor Silence** — diam sementara (1/4/24 jam) tanpa mematikan monitor
- **Dependency Map** — skip notifikasi jika parent/dependency DOWN
- **Service Topology** — visualisasi grafik dependency antar monitor
- **Environment Label** — Production / Staging / Development / Testing; alert berbeda per env
- **Monitor Health Score** — score 0–100 berdasarkan uptime + response time + frekuensi insiden

### Status Page & Integrasi
- **Status Page Builder** — halaman publik `/status/{slug}`; sections, embed widget, custom domain
- **Uptime Badge SVG** — badge embed untuk README/portal via `/status/{slug}/badge.svg`
- **REST API** — `GET /api/monitors` dengan Bearer token untuk integrasi eksternal
- **API Token Manager** — buat/hapus token dengan expiry dan scope
- **Webhook Inbound** — terima alert dari Grafana/Zabbix/Prometheus dan trigger aksi
- **Import/Export JSON** — backup dan restore konfigurasi monitor
- **Bulk Import CSV** — upload CSV daftar monitor sekaligus
- **Monitor Template Library** — preset siap pakai: MySQL, Redis, Nginx, SatuSehat, BPJS, dll
- **Smoke Test** — trigger check semua monitor setelah deploy; laporan pass/fail

### Laporan & Settings
- **Laporan Harian/Mingguan** — dikirim otomatis ke channel yang dikonfigurasi
- **Maintenance Window** — jadwalkan downtime; notifikasi dan insiden tidak aktif selama window
- **Business Hours** — konfigurasi jam kerja per hari untuk routing alert
- **Dark Mode** — toggle tema gelap/terang dengan persistensi localStorage
- **API Health Dashboard** — monitor BPJS, Satu Sehat, dan service eksternal lainnya

---

## Teknologi

| Komponen | Teknologi |
|---|---|
| Framework | Laravel 12 (PHP 8.2) |
| Database | MySQL 8.0 |
| Frontend | Blade + Tailwind CSS (CDN) + Alpine.js v3 |
| Chart | Chart.js |
| Icon | Font Awesome 6.5 Free |
| Alert | SweetAlert2 |
| Notifikasi WA | Fonnte API |
| Notifikasi Telegram | Telegram Bot API |
| Notifikasi Email | SMTP (Laravel Mail) |
| Container | Docker + Docker Compose |
| Process Manager | Supervisor (nginx + php-fpm + scheduler) |

---

## Tipe Monitor

| Tipe | Input Utama | Cara Cek | Cocok Untuk |
|---|---|---|---|
| `http` | URL (`https://...`) | HTTP request | Website, REST API |
| `keyword` | URL + kata kunci | HTTP + string check | Validasi konten halaman |
| `ping` | Hostname / IP | ICMP ping | Server, infrastruktur jaringan |
| `tcp` | Host + Port | TCP connection | Database, mail server, port arbitrer |
| `dns` | Domain + expected value | DNS resolve | DNS resolver, zone check |
| `push` | Token (auto) | Terima heartbeat | Cronjob, background worker |
| `cron` | Token + interval (mnt) | Heartbeat berkala | Cron job yang harus jalan rutin |
| `database` | Connection string | Koneksi DB langsung | MySQL, PostgreSQL, Redis |
| `docker` | Container name / socket | Docker API | Container health check |
| `whois` | Domain | WHOIS lookup | Pantau expiry domain |

---

## Logika UP / DOWN

### HTTP & Keyword
UP selama server merespons dengan kode apapun (200, 403, 503, dll). DOWN hanya jika koneksi gagal total (timeout, connection refused, DNS fail). Disengaja — tujuan utamanya cek konektivitas jaringan, bukan validasi aplikasi.

### Retry & Notifikasi
Notifikasi dikirim hanya saat jumlah kegagalan berturut-turut mencapai nilai **Retry**:

| Retry | Perilaku |
|---|---|
| 1 | 1× DOWN → langsung notifikasi |
| 3 | butuh 3× DOWN berturut-turut → notifikasi |

Mencegah gangguan sesaat (packet loss singkat) membanjiri notifikasi.

### Flap Detection
Jika monitor UP-DOWN lebih dari N kali dalam window X menit → notifikasi ditahan, status `FLAPPING` dicatat.

---

## Login Default

| Field | Value |
|---|---|
| Email | `admin@watchtower.local` |
| Password | `watchtower123` |

Jalankan seeder jika belum ada user:
```bash
php artisan db:seed
```

Ganti password setelah login pertama:
```bash
php artisan tinker --execute="App\Models\User::where('email','admin@watchtower.local')->first()->update(['password'=>bcrypt('password_baru')]);"
```

---

## Instalasi — Server Langsung

### 1. Clone & install
```bash
git clone <repo-url> uptime-monitor
cd uptime-monitor
composer install --optimize-autoloader --no-dev
```

### 2. Konfigurasi environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
APP_URL=https://monitor.namadomain.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uptime_monitor
DB_USERNAME=root
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="watchtower@namadomain.com"

BPJS_HOST_NON_CDN=https://apijkn.bpjs-kesehatan.go.id
BPJS_HOST_CDN=https://new-apijkn.bpjs-kesehatan.go.id
BPJS_CDN_MODE=non_cdn

SATUSEHAT_BASE_URL=https://api-satusehat.kemkes.go.id
```

### 3. Migrasi & seeder
```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 4. Optimasi production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Scheduler (crontab)
```bash
crontab -e
```
```cron
* * * * * cd /path/to/uptime-monitor && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler aktif:
| Command | Jadwal |
|---|---|
| `monitor:check` | Setiap menit |
| `monitor:check-cron` | Setiap menit |
| `monitor:ssl-check` | 2x sehari (08.00 & 20.00) |
| `monitor:check-domain-expiry` | Setiap hari 03.00 |
| `monitor:auto-close-incidents` | Setiap 5 menit |
| `monitor:report` | Harian/mingguan sesuai Settings |
| `api:health-check` | Sesuai interval di Settings |

### 6. Web Server

**Nginx:**
```nginx
server {
    listen 80;
    server_name monitor.namadomain.com;
    root /path/to/uptime-monitor/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
}
```

**Apache** — pastikan `mod_rewrite` aktif; `.htaccess` sudah tersedia di `public/`.

---

## Instalasi — Docker

```bash
cp .env.example .env
# Edit .env sesuai kebutuhan

docker compose up -d --build

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

Akses di `http://localhost:8080`.

---

## Update Aplikasi

### Server Langsung
```bash
git pull origin main
composer install --optimize-autoloader --no-dev --no-interaction
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Docker
```bash
git pull origin main
docker compose build --no-cache
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
```

---

## Artisan Commands

```bash
# Monitor
php artisan monitor:check                    # Cek semua monitor
php artisan monitor:check --id=5             # Cek monitor tertentu
php artisan monitor:check-cron               # Cek heartbeat cron yang terlambat
php artisan monitor:ssl-check                # Cek SSL semua monitor
php artisan monitor:check-domain-expiry      # Cek expiry domain
php artisan monitor:auto-close-incidents     # Auto-close insiden yang sudah UP
php artisan monitor:simulate 5 down          # Simulasi DOWN monitor ID 5
php artisan monitor:simulate 5 up            # Simulasi UP monitor ID 5
php artisan monitor:simulate 5 slow          # Simulasi SLOW monitor ID 5

# Laporan
php artisan monitor:report --period=daily    # Laporan harian manual
php artisan monitor:report --period=weekly   # Laporan mingguan manual

# API Health
php artisan api:health-check                 # Cek semua service API
php artisan api:health-check --service=bpjs_vclaim

# Utilitas
php artisan schedule:list                    # Lihat semua jadwal scheduler aktif
```

---

## REST API

Akses dengan Bearer token dari menu **API Tokens**:

```bash
# List semua monitor
curl -H "Authorization: Bearer <token>" https://monitor.namadomain.com/api/monitors

# Detail monitor
curl -H "Authorization: Bearer <token>" https://monitor.namadomain.com/api/monitors/1

# Status insiden aktif
curl -H "Authorization: Bearer <token>" https://monitor.namadomain.com/api/incidents
```

---

## Webhook Outbound

Payload yang dikirim ke channel Webhook:

```json
{
  "event": "monitor.down",
  "monitor": {
    "id": 1,
    "name": "SIMRS",
    "url": "https://simrs.example.com",
    "type": "http",
    "status": "down",
    "last_checked_at": "2026-06-12T10:30:00+07:00"
  },
  "timestamp": "2026-06-12T10:30:00+07:00",
  "message": "🔴 SIMRS is DOWN\nURL: https://simrs.example.com\nWaktu: 12-06-2026 10:30:00"
}
```

| Event | Kondisi |
|---|---|
| `monitor.down` | Monitor DOWN |
| `monitor.up` | Monitor pulih |
| `monitor.slow` | Response time melebihi threshold |
| `monitor.ssl_expiry` | SSL mendekati expired |
| `monitor.domain_expiry` | Domain mendekati expired |
| `incident.escalated` | Insiden dieskalasi |

Verifikasi HMAC (header `X-WatchTower-Signature: sha256=<hmac>`):
```php
$expected = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), $secretKey);
if (!hash_equals($expected, $_SERVER['HTTP_X_WATCHTOWER_SIGNATURE'] ?? '')) {
    http_response_code(401); exit;
}
```

---

## Webhook Inbound

Terima alert dari sistem lain (Grafana, Zabbix, Prometheus) ke endpoint:
```
POST /webhook-in/{token}
GET  /webhook-in/{token}     ← Info endpoint + contoh curl
```

Buat receiver di menu **Webhook In**, salin token, arahkan alert eksternal ke URL tersebut.

---

## Catatan Keamanan

Aplikasi ini **tidak menyimpan** credential apapun (cons_id, secret_key, client_id, client_secret, token API BPJS/Satu Sehat). Fungsinya hanya mengecek konektivitas jaringan ke endpoint — bukan mengintegrasikan atau mengakses data dari layanan tersebut.

---

## Struktur Docker

```
uptime-monitor/
├── Dockerfile                  ← PHP 8.2-fpm Alpine + Nginx + Supervisor
├── docker-compose.yml          ← App + MySQL 8.0 (opsional)
├── .dockerignore
└── docker/
    ├── nginx.conf
    └── supervisord.conf        ← php-fpm + nginx + scheduler
```

---

## Dokumentasi Lanjutan

- [PANDUAN.md](PANDUAN.md) — cara penggunaan lengkap semua fitur

---

## License

MIT
