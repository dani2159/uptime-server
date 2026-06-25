# WatchTower — Uptime Monitor

Aplikasi monitoring uptime website, server, dan kesehatan API — dibangun dengan Laravel 12.
Mendukung monitoring domain beserta IP address-nya, cek konektivitas endpoint BPJS & Satu Sehat,
notifikasi otomatis via Telegram, WhatsApp & Webhook, serta pencatatan insiden dan laporan SLA.

---

## Fitur

- **Monitor HTTP** — cek uptime dengan HTTP request; UP selama merespons (termasuk 4xx/5xx), DOWN hanya jika koneksi gagal total
- **Monitor Ping** — ICMP ping ke host/IP; cocok untuk server yang memblokir HTTP
- **Monitor TCP** — cek konektivitas port TCP (database, SMTP, dll)
- **Monitor DNS** — resolve DNS dan bandingkan dengan nilai yang diharapkan
- **Monitor Keyword** — HTTP request + cek keberadaan string di response body
- **Monitor Push** — endpoint heartbeat untuk monitoring cronjob / background worker
- **DNS IP Lookup** — resolve semua IP (A & AAAA) dari domain secara otomatis
- **API Health Dashboard** — cek konektivitas endpoint BPJS, Satu Sehat, dan service lainnya dengan speedometer response time
- **BPJS CDN Switch** — toggle CDN / Non-CDN langsung dari dashboard; cache hasil dibersihkan otomatis
- **Notifikasi Telegram, WhatsApp & Webhook** — alert otomatis saat down/recover; tiap channel dipilih per monitor
- **Webhook HMAC** — header `X-WatchTower-Signature: sha256=<hmac>` untuk verifikasi keaslian payload
- **Status Page Builder** — halaman status publik dengan sections/grup monitor; drag reorder, kombinasi monitor uptime + API health
- **Maintenance Window** — jadwalkan downtime terjadwal; notifikasi dan insiden tidak aktif selama window berlangsung
- **Incident Tracking** — insiden tercatat otomatis saat transisi DOWN/UP; tambah manual untuk gangguan umum atau laporan client
- **SLA Report** — availability %, total downtime, jumlah insiden, MTTR — per periode 7/30/90 hari
- **Settings** — konfigurasi interval pengecekan API health (5 menit s/d 24 jam) dan on/off auto-check langsung dari UI
- **Server IP & ISP** — public IP dan nama ISP server ditampilkan di header semua halaman; refresh tanpa reload
- **History & Log** — riwayat pengecekan dan grafik response time 48 jam per monitor
- **Heartbeat Bar** — visualisasi 90 pengecekan terakhir
- **Dark Mode** — toggle tema gelap/terang dengan persistensi localStorage
- **Live Clock** — jam dan tanggal real-time di semua halaman (update setiap detik)

---

## Teknologi

| Komponen | Teknologi |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2) |
| Database | MySQL 8.0 |
| Frontend | Blade + Tailwind CSS (CDN) + Alpine.js v3 |
| Chart | Chart.js |
| Icon | Font Awesome 6.5 Free |
| Alert | SweetAlert2 |
| Notifikasi WA | Fonnte API |
| Notifikasi Telegram | Telegram Bot API |
| Container | Docker + Docker Compose |
| Process Manager | Supervisor (nginx + php-fpm + scheduler) |

---

## Login Default

| Field | Value |
| --- | --- |
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

## Tipe Monitor

| Tipe | Cara Cek | Field | Cocok Untuk |
| --- | --- | --- | --- |
| `HTTP` | HTTP GET | URL lengkap | Website, REST API |
| `Ping` | ICMP ping | Hostname / IP | Server yang blokir HTTP, infrastruktur jaringan |
| `TCP` | Koneksi TCP | Host + Port | Database, mail server, port arbitrer |
| `DNS` | DNS lookup | Domain + expected IP | DNS resolver, zone check |
| `Keyword` | HTTP GET + string check | URL + kata kunci | Validasi konten halaman |
| `Push` | Heartbeat endpoint | Token otomatis | Cronjob, background worker |

> Tipe Ping dan TCP menerima IP langsung (contoh: `10.6.0.11`) — tidak perlu `http://`.

---

## Logika UP / DOWN

### HTTP & Keyword

Server dianggap **UP** selama merespons dengan kode apapun (200, 403, 503, dll). **DOWN** hanya jika koneksi gagal total (timeout, connection refused, DNS fail). Ini disengaja — tujuannya cek konektivitas jaringan, bukan validasi aplikasi. Contoh: BPJS mengembalikan 503 dari IP non-whitelist — server tetap aktif.

### Ping

UP jika ICMP reply diterima. DOWN jika timeout / host unreachable.

### Retry & Notifikasi

Notifikasi dikirim **hanya saat** jumlah kegagalan berturut-turut mencapai nilai **Retry** yang dikonfigurasi:

| Retry | Perilaku |
| --- | --- |
| 1 | 1× check DOWN → langsung notifikasi |
| 3 | butuh 3× DOWN berturut-turut → notifikasi |

Ini mencegah gangguan sesaat (packet loss singkat) membanjiri notifikasi.

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

BPJS_HOST_NON_CDN=https://apijkn.bpjs-kesehatan.go.id
BPJS_HOST_CDN=https://new-apijkn.bpjs-kesehatan.go.id
BPJS_CDN_MODE=non_cdn
BPJS_PCARE_URL=https://apijkn.kesehatan.go.id/pcare-rest

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

Scheduler menjalankan `monitor:check` tiap menit dan `api:health-check` sesuai interval di **Settings**.

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
# Edit .env: APP_URL, DB_PASSWORD, BPJS URL, dll

docker compose up -d --build

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

Akses di `http://localhost:8080` (atau sesuai `APP_PORT` di `.env`).

---

## Menggunakan Database Eksternal

### Tanpa Docker

Set di `.env`:

```env
DB_HOST=192.168.1.100
DB_PORT=3306
DB_DATABASE=uptime_monitor
DB_USERNAME=user_db
DB_PASSWORD=password_db
```

Jalankan migrasi seperti biasa.

### Docker + DB Eksternal

Edit `docker-compose.yml` — hapus service `db` dan `depends_on`, biarkan `app` pakai DB eksternal:

```yaml
services:
  app:
    build: .
    restart: unless-stopped
    ports:
      - "${APP_PORT:-8080}:80"
    env_file: .env
    volumes:
      - app_storage:/var/www/html/storage/app
      - app_logs:/var/www/html/storage/logs

volumes:
  app_storage:
  app_logs:
```

Set `DB_HOST` di `.env` ke IP server DB. Pastikan MySQL mengizinkan koneksi dari IP container.

---

## Update Aplikasi

### Server Langsung

```bash
cd /path/to/uptime-monitor

git pull origin main

composer install --optimize-autoloader --no-dev --no-interaction

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika ada asset baru di `public/`:

```bash
php artisan storage:link
```

### Docker

```bash
git pull origin main

docker compose build

docker compose up -d

docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

> Build image dulu (`build`) sebelum restart container (`up -d`) agar downtime minimal — container lama masih jalan selama proses build berlangsung.
> Semua migration bersifat non-destructive. Data yang sudah ada aman setelah `migrate --force`.

---

## Cara Menggunakan Fitur Utama

### Tambah Monitor

**Dashboard → Tambah Monitor**, isi:

| Field | Keterangan |
| --- | --- |
| Nama | Label deskriptif |
| Tipe | HTTP / Ping / TCP / DNS / Keyword / Push |
| URL / Host | URL lengkap (HTTP), hostname atau IP (Ping/TCP) |
| Interval | Seberapa sering dicek (menit) |
| Timeout | Batas waktu tunggu respons (detik) |
| Retry | Kegagalan berturut-turut sebelum dianggap DOWN |
| Channel Notifikasi | Pilih WA / Telegram / Webhook yang menerima alert |

IP domain di-resolve otomatis dan ditampilkan di halaman detail monitor.

### Status Page Builder

**Status Pages → Buat Status Page:**

1. Isi judul, slug URL, dan deskripsi
2. Tambah section/grup (contoh: "Layanan Web", "API Services")
3. Tambah monitor ke tiap section dari dropdown
4. Pilih layanan API (BPJS, Satu Sehat) yang ditampilkan
5. Atur urutan dengan tombol panah
6. Klik **Buat** — halaman publik aktif di `/status/{slug}`

### Maintenance Window

**Maintenance → Tambah:** tentukan nama, monitor terdampak, waktu mulai dan selesai. Selama window aktif: notifikasi tidak dikirim, insiden tidak dicatat.

### Incident Tracking

**Insiden** — insiden DOWN/UP tercatat otomatis. Tambah manual untuk gangguan yang tidak terdeteksi monitor (pemadaman listrik, gangguan jaringan, laporan client).

| Field | Keterangan |
| --- | --- |
| Kategori | `monitor_downtime` / Insiden Umum IT / Laporan Client |
| Severity | Low / Medium / High / Critical |
| Durasi | Dihitung otomatis dari started\_at ke resolved\_at |
| Pelapor | Nama dan kontak (khusus Laporan Client) |

### SLA Report

**SLA Report** — pilih periode 7 / 30 / 90 hari:

| Metrik | Keterangan |
| --- | --- |
| Availability % | `(periode - downtime) / periode × 100` |
| Jumlah Insiden | Insiden kategori monitor\_downtime |
| Total Downtime | Akumulasi durasi insiden di periode |
| MTTR | Rata-rata durasi insiden yang selesai |

### Settings — Interval API Health

**Settings** → atur interval pengecekan BPJS dan layanan API:

| Preset | Jadwal cron |
| --- | --- |
| 10 menit | `*/10 * * * *` |
| 15 menit | `*/15 * * * *` |
| 30 menit | `*/30 * * * *` |
| 1 jam | `0 */1 * * *` |
| 6 jam | `0 */6 * * *` |

Bisa juga isi manual (min 5, max 1440 menit). Toggle **Auto Check** untuk nonaktifkan pengecekan otomatis — hanya cek manual dari dashboard.

> **Rekomendasi BPJS:** gunakan minimal 15–30 menit agar IP tidak diblokir server BPJS akibat terlalu sering hit.

### Server IP & ISP

Public IP dan nama ISP server tampil otomatis di sudut kanan header semua halaman. Klik **Refresh** di halaman Settings untuk force-update (cache 5 menit). Berguna untuk memverifikasi IP mana yang sedang digunakan server sebelum whitelist di BPJS.

---

## Monitoring ISP / Konektivitas Jaringan

Gunakan tipe **Ping** ke beberapa target sekaligus untuk mendiagnosis letak masalah:

| Monitor | Target | Arti jika DOWN |
| --- | --- | --- |
| Gateway Lokal | IP gateway lokal (contoh `192.168.1.1`) | Router/switch lokal bermasalah |
| ISP Gateway | IP hop pertama dari traceroute | ISP upstream bermasalah |
| Internet | `8.8.8.8` atau `1.1.1.1` | Koneksi internet tidak terjangkau |

Cari IP first-hop ISP di Windows:

```cmd
tracert 8.8.8.8
```

Lihat hop ke-2 atau ke-3.

---

## Notifikasi

### WhatsApp (Fonnte)

1. Daftar di [fonnte.com](https://fonnte.com), tambah device, scan QR
2. Salin Token dari halaman device
3. **Notifikasi → Tambah Channel** → Tipe: `WhatsApp`, isi Token dan Target (format `628xxxxxxxxxx`)
4. Saat tambah/edit monitor, centang channel ini

Format pesan:

```text
🔴 *Nama Monitor* is *DOWN*
URL: https://contoh.com
Waktu: 12-06-2026 10:30:00
```

### Telegram Bot

1. Buka Telegram → cari **@BotFather** → `/newbot` → salin Bot Token
2. Dapatkan Chat ID dengan buka URL berikut di browser:

   ```text
   https://api.telegram.org/bot<TOKEN>/getUpdates
   ```

   Cari `"chat":{"id": 123456789}` di response JSON
3. **Notifikasi → Tambah Channel** → Tipe: `Telegram`, isi Token dan Chat ID

> Untuk grup: tambahkan bot ke grup, gunakan Chat ID grup (diawali `-`).

### Webhook (HTTP POST)

1. **Notifikasi → Tambah Channel** → Tipe: `Webhook`
2. Isi Webhook URL dan Secret Key (opsional untuk HMAC)

Payload yang dikirim:

```json
{
  "event": "monitor.down",
  "monitor": {
    "id": 1,
    "name": "SIMRS",
    "url": "https://simrs.mitraplumbon.com",
    "type": "http",
    "status": "down",
    "last_checked_at": "2026-06-12T10:30:00+07:00"
  },
  "timestamp": "2026-06-12T10:30:00+07:00",
  "message": "🔴 SIMRS is DOWN\nURL: https://simrs.mitraplumbon.com\nWaktu: 12-06-2026 10:30:00"
}
```

| Event | Kondisi |
| --- | --- |
| `monitor.down` | Monitor terdeteksi DOWN |
| `monitor.up` | Monitor pulih (recovered) |
| `monitor.ssl_expiry` | SSL certificate mendekati kedaluwarsa |

Verifikasi HMAC di sisi penerima:

```php
$expected = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), $secretKey);
if (!hash_equals($expected, $_SERVER['HTTP_X_WATCHTOWER_SIGNATURE'] ?? '')) {
    http_response_code(401); exit;
}
```

```python
import hmac, hashlib
expected = 'sha256=' + hmac.new(secret.encode(), body, hashlib.sha256).hexdigest()
assert hmac.compare_digest(expected, request.headers['X-WatchTower-Signature'])
```

---

## Tambah API Service Baru

Edit `config/services_custom.php`:

```php
'nama_service' => [
    'label'     => 'Nama Service',
    'base_url'  => env('NAMA_BASE_URL', 'https://api.contoh.com'),
    'timeout'   => 15,
    'endpoints' => [
        ['key' => 'health', 'label' => 'Health Check', 'method' => 'GET', 'path' => '/health'],
    ],
],
```

Set di `.env`:

```env
NAMA_BASE_URL=https://api.contoh.com
```

Service langsung muncul di **API Health Dashboard** tanpa restart.

---

## BPJS CDN / Non-CDN

| Mode | Host |
| --- | --- |
| Non-CDN | `https://apijkn.bpjs-kesehatan.go.id` |
| CDN | `https://new-apijkn.bpjs-kesehatan.go.id` |

Toggle di halaman **API Health Dashboard**. Saat mode diganti, cache hasil pengecekan dibersihkan otomatis. PCare menggunakan domain tersendiri dan tidak terpengaruh toggle ini.

---

## Artisan Commands

```bash
# Cek semua monitor
php artisan monitor:check

# Cek monitor tertentu
php artisan monitor:check --id=1

# Cek semua API health service
php artisan api:health-check

# Cek service tertentu
php artisan api:health-check --service=bpjs_vclaim

# Cek SSL certificate semua monitor
php artisan monitor:ssl-check

# Lihat jadwal scheduler aktif
php artisan schedule:list
```

---

## Catatan Keamanan

Aplikasi ini **tidak menyimpan** credential apapun (cons\_id, secret\_key, client\_id, client\_secret, token API BPJS/Satu Sehat). Fungsinya hanya mengecek konektivitas jaringan ke endpoint — bukan mengintegrasikan atau mengakses data dari layanan tersebut.

---

## Struktur Docker

```text
uptime-monitor/
├── Dockerfile                  ← PHP 8.2-fpm Alpine + Nginx + Supervisor
├── docker-compose.yml          ← App + MySQL 8.0 (opsional)
├── .dockerignore
└── docker/
    ├── nginx.conf
    └── supervisord.conf        ← php-fpm + nginx + scheduler
```

---

## License

MIT
