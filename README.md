# WatchTower — Uptime Monitor

Aplikasi monitoring uptime website, server, dan kesehatan API — dibangun dengan Laravel 12.
Mendukung monitoring domain beserta IP address-nya, cek konektivitas endpoint BPJS & Satu Sehat,
serta notifikasi otomatis via Telegram dan WhatsApp.

---

## Fitur

- **Monitor HTTP** — cek uptime dengan mengirim HTTP request; server dianggap UP selama merespons (termasuk 4xx/5xx), DOWN hanya jika koneksi gagal total
- **Monitor Ping** — cek uptime dengan ICMP ping; cocok untuk server/IP yang membatasi akses HTTP
- **Monitor TCP** — cek konektivitas port TCP (contoh: database, SMTP, dll)
- **Monitor DNS** — resolve DNS dan bandingkan hasilnya dengan nilai yang diharapkan
- **Monitor Keyword** — HTTP request + cek keberadaan kata kunci di response body
- **Monitor Push** — endpoint push heartbeat untuk monitoring cronjob / proses scheduled
- **DNS IP Lookup** — resolve semua IP (A & AAAA record) dari domain secara otomatis saat monitor ditambahkan
- **API Health Dashboard** — cek konektivitas endpoint BPJS, Satu Sehat, dan service lainnya dengan tampilan speedometer response time
- **BPJS CDN Switch** — toggle antara host CDN dan Non-CDN untuk layanan BPJS langsung dari dashboard
- **Notifikasi Telegram, WhatsApp & Webhook** — alert otomatis saat server down atau recover; Telegram format HTML, WhatsApp plain text, Webhook JSON payload
- **Webhook dengan HMAC Signature** — setiap request webhook bisa disertai header `X-WatchTower-Signature: sha256=<hmac>` untuk verifikasi keaslian
- **Channel Notifikasi per Monitor** — pilih channel mana saja yang aktif menerima alert (WA, Telegram, Webhook, atau kombinasinya) saat menambah/edit monitor
- **History & Log** — riwayat pengecekan disimpan ke database dengan grafik response time 48 jam
- **Heartbeat Bar** — visualisasi 90 pengecekan terakhir per monitor
- **Status Page Builder** — buat halaman status publik dengan sections/grup monitor, bisa diatur urutan dan kelompoknya seperti Uptime Kuma
- **Maintenance Window** — jadwalkan jendela maintenance agar notifikasi tidak dikirim saat downtime terjadwal
- **Dark Mode** — toggle tema gelap/terang dengan penyimpanan di localStorage
- **Scheduler** — pengecekan berjalan otomatis sesuai interval yang dikonfigurasi per monitor

---

## Teknologi yang Digunakan

| Komponen | Teknologi |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2) |
| Database | MySQL 8.0 |
| Frontend | Blade + Tailwind CSS (CDN) + Alpine.js v3 |
| Chart | Chart.js (response time history) |
| Icon | Font Awesome 6.5 Free |
| Alert | SweetAlert2 |
| Notifikasi WA | Fonnte API (`api.fonnte.com`) |
| Notifikasi Telegram | Telegram Bot API |
| Containerisasi | Docker + Docker Compose |
| Process Manager | Supervisor (nginx + php-fpm + scheduler) |
| Web Server | Nginx |

---

## Logika UP / DOWN

### Monitor HTTP & Keyword

| Kondisi | Status |
| --- | --- |
| Server merespons (200, 301, 403, 404, 503, dll) | **UP** |
| Koneksi gagal (timeout, connection refused, DNS fail) | **DOWN** |

Server dianggap UP selama merespons dengan kode HTTP apapun. Ini disengaja karena tujuannya adalah mengecek **konektivitas jaringan**, bukan validasi aplikasi. Contoh: BPJS API mengembalikan 503 dari IP yang tidak terdaftar — server tetap aktif, hanya membatasi akses.

### Monitor Ping

| Kondisi | Status |
| --- | --- |
| ICMP ping reply diterima | **UP** |
| Request timeout / host unreachable | **DOWN** |

Gunakan tipe Ping untuk server yang memblokir HTTP tetapi mengizinkan ICMP (misalnya server BPJS dari IP non-whitelist, atau server dengan IP private).

---

## Tipe Monitor

Saat menambah monitor, pilih tipe sesuai kebutuhan:

| Tipe | Cara Cek | Field Utama | Cocok Untuk |
| --- | --- | --- | --- |
| `HTTP` | HTTP GET ke URL | URL lengkap | Website, REST API, web server |
| `Ping` | ICMP ping ke host | Hostname atau IP address | Server/IP yang block HTTP, infrastruktur jaringan |
| `TCP` | Koneksi TCP ke port | TCP Host + Port | Database, mail server, port arbitrer |
| `DNS` | DNS lookup | Domain + expected value | DNS resolver, zone check |
| `Keyword` | HTTP GET + cek string | URL + kata kunci | Pastikan konten halaman ada |
| `Push` | Endpoint heartbeat | Token otomatis | Cronjob, background worker |

> **Catatan:** Tipe Ping dan TCP menerima alamat IP langsung (contoh: `10.6.0.11`) — tidak perlu format `http://...`.

---

## Aplikasi Pendukung

### Fonnte (WhatsApp Notification)

[Fonnte](https://fonnte.com) adalah layanan gateway WhatsApp berbasis nomor HP pribadi.
Tidak memerlukan WhatsApp Business API — cukup scan QR dari dashboard Fonnte.

**Cara setup Fonnte:**

1. Daftar akun di [fonnte.com](https://fonnte.com)
2. Tambahkan device → scan QR code dengan WhatsApp yang akan digunakan sebagai pengirim
3. Salin **Token** dari halaman device
4. Masuk ke aplikasi → **Notifikasi → Tambah Channel**
   - Tipe: `WhatsApp`
   - Token: token dari Fonnte
   - Target: nomor tujuan (format `628xxxxxxxxxx`, tanpa `+`)
5. Saat tambah/edit monitor, centang channel WhatsApp ini

**Format pesan WA (plain text):**

```text
🔴 *Nama Monitor* is *DOWN*
URL: https://contoh.com
Waktu: 12-06-2026 10:30:00
```

**Catatan:** nomor pengirim harus tetap online dan terhubung ke Fonnte. Jika WhatsApp logout, notifikasi tidak terkirim.

---

### Telegram Bot (Telegram Notification)

**Cara setup Telegram Bot:**

1. Buka Telegram, cari **@BotFather**
2. Ketik `/newbot` → ikuti instruksi → salin **Bot Token** (format `123456:ABC-DEF...`)
3. Cari bot yang baru dibuat → klik Start
4. Dapatkan **Chat ID** dengan membuka URL berikut di browser:

   ```text
   https://api.telegram.org/bot<TOKEN>/getUpdates
   ```

   Cari nilai `"chat":{"id": 123456789}` dari response JSON

5. Masuk ke aplikasi → **Notifikasi → Tambah Channel**
   - Tipe: `Telegram`
   - Token: Bot Token dari BotFather
   - Target: Chat ID

**Format pesan Telegram (HTML bold):**

```text
🔴 Nama Monitor is DOWN
URL: https://contoh.com
Waktu: 12-06-2026 10:30:00
```

**Tip:** untuk mengirim ke grup, tambahkan bot ke grup lalu gunakan Chat ID grup (biasanya diawali `-`).

---

### Webhook (HTTP POST)

Webhook mengirim HTTP POST dengan payload JSON ke URL yang dikonfigurasi — cocok untuk integrasi ke sistem lain (Discord, Slack, custom API, dsb.) tanpa memerlukan bot atau API key pihak ketiga.

**Cara setup:**

1. Masuk ke aplikasi → **Notifikasi → Tambah Channel**
   - Tipe: `Webhook (HTTP POST)`
   - Webhook URL: URL endpoint yang menerima POST (contoh: `https://hooks.example.com/watchtower`)
   - Secret Key *(opsional)*: jika diisi, setiap request menyertakan header HMAC untuk verifikasi keaslian

2. Saat tambah/edit monitor, centang channel webhook ini

**Format payload yang dikirim:**

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

| Field `event` | Kondisi |
| --- | --- |
| `monitor.down` | Monitor baru saja terdeteksi DOWN |
| `monitor.up` | Monitor pulih kembali (recovered) |
| `monitor.ssl_expiry` | SSL certificate mendekati kedaluwarsa |

**Verifikasi signature (jika Secret Key diisi):**

Header yang disertakan:

```text
X-WatchTower-Signature: sha256=<hmac_hex>
```

Cara verifikasi di sisi penerima:

```php
$expected = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), $secretKey);
$received = $_SERVER['HTTP_X_WATCHTOWER_SIGNATURE'] ?? '';
if (!hash_equals($expected, $received)) {
    http_response_code(401);
    exit;
}
```

```python
import hmac, hashlib
expected = 'sha256=' + hmac.new(secret.encode(), body, hashlib.sha256).hexdigest()
assert hmac.compare_digest(expected, request.headers['X-WatchTower-Signature'])
```

---

## Requirement

- PHP >= 8.2
- Composer
- MySQL >= 8.0

---

## Instalasi di Server Langsung

### 1. Clone repository

```bash
git clone <repo-url> uptime-monitor
cd uptime-monitor
```

### 2. Install dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Konfigurasi environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuaikan database dan URL service:

```env
APP_URL=https://monitor.namadomain.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uptime_monitor
DB_USERNAME=root
DB_PASSWORD=your_password

# BPJS Host (CDN Switch)
BPJS_HOST_NON_CDN=https://apijkn.bpjs-kesehatan.go.id
BPJS_HOST_CDN=https://new-apijkn.bpjs-kesehatan.go.id
BPJS_CDN_MODE=non_cdn

# PCare menggunakan domain berbeda
BPJS_PCARE_URL=https://apijkn.kesehatan.go.id/pcare-rest

# Satu Sehat
SATUSEHAT_BASE_URL=https://api-satusehat.kemkes.go.id
```

### 4. Jalankan migrasi

```bash
php artisan migrate
php artisan storage:link
```

### 5. Optimasi untuk production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Setup Scheduler (Crontab)

Tambahkan satu baris crontab agar Laravel Scheduler berjalan setiap menit:

```bash
crontab -e
```

```cron
* * * * * cd /path/to/uptime-monitor && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Konfigurasi Web Server

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

**Apache** — pastikan `mod_rewrite` aktif, file `.htaccess` sudah tersedia di folder `public/`.

---

## Update Aplikasi (Server Langsung)

### Prosedur standar

Saat ada update dari repository:

```bash
cd /path/to/uptime-monitor
git pull origin main

composer install --optimize-autoloader --no-dev --no-interaction

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika ada perubahan pada file `public/` (asset baru, dll):

```bash
php artisan storage:link
```

### Update dari versi sebelumnya (riwayat perubahan penting)

Berikut langkah tambahan yang mungkin diperlukan tergantung dari versi mana kamu update:

#### Versi dengan Status Page Builder (tambah kolom `sections`)

Jika sebelumnya sudah menggunakan fitur Status Pages, jalankan migration untuk menambah kolom sections:

```bash
php artisan migrate --force
```

Migration ini bersifat non-destructive — data `monitor_ids` lama tetap ada dan halaman status publik yang sudah dibuat tetap berjalan (backward compatible).

#### Versi dengan Webhook Channel

Tidak ada perubahan schema database. Cukup jalankan langkah standar di atas.
Setelah update, pilihan tipe `Webhook` sudah muncul di form **Notifikasi → Tambah Channel**.

#### Versi dengan Multi-tipe Monitor (Ping, TCP, DNS, Push)

Jika migration `alter_monitors_add_multi_type` belum dijalankan:

```bash
php artisan migrate --force
```

---

## Instalasi dengan Docker

File Docker sudah tersedia di repository (`Dockerfile`, `docker-compose.yml`, `docker/`).

### Pertama kali

```bash
cp .env.example .env
# Edit .env: sesuaikan APP_URL, DB_PASSWORD, BPJS URL, dll

docker compose up -d --build

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

Aplikasi dapat diakses di `http://localhost:8080` (atau sesuai `APP_PORT` di `.env`).

### Update deployment Docker

```bash
git pull origin main
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

---

## Menggunakan Database Server yang Sudah Ada

Jika sudah memiliki server MySQL/MariaDB sendiri (bukan Docker), ada dua cara:

### Opsi A: Instalasi langsung (tanpa Docker)

Cukup set konfigurasi database di `.env` sesuai server yang ada:

```env
DB_HOST=192.168.1.100   # IP atau hostname server DB
DB_PORT=3306             # port MySQL (default 3306)
DB_DATABASE=uptime_monitor
DB_USERNAME=user_db
DB_PASSWORD=password_db
```

Kemudian jalankan migrasi seperti biasa:

```bash
php artisan migrate --force
```

### Opsi B: Docker app — database eksternal (tanpa container `db`)

Edit `docker-compose.yml`, hapus bagian service `db` dan `depends_on`, lalu biarkan `app` menggunakan DB eksternal:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    restart: unless-stopped
    ports:
      - "${APP_PORT:-8080}:80"
    env_file:
      - .env
    volumes:
      - app_storage:/var/www/html/storage/app
      - app_logs:/var/www/html/storage/logs

volumes:
  app_storage:
  app_logs:
```

Lalu set di `.env`:

```env
DB_HOST=192.168.1.100   # IP server DB yang bisa diakses dari container
DB_PORT=3306
DB_DATABASE=uptime_monitor
DB_USERNAME=user_db
DB_PASSWORD=password_db
```

> **Penting:** pastikan host DB mengizinkan koneksi dari IP container Docker (cek `GRANT` privileges dan firewall).

Jalankan:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

---

## Struktur file Docker

```text
uptime-monitor/
├── Dockerfile                  ← PHP 8.2-fpm Alpine + Nginx + Supervisor
├── docker-compose.yml          ← App + MySQL 8.0 (opsional)
├── .dockerignore
└── docker/
    ├── nginx.conf              ← Konfigurasi Nginx
    └── supervisord.conf        ← Jalankan php-fpm + nginx + scheduler
```

---

## Cara Menambah Monitor

### Monitor website/server biasa

Masuk ke **Dashboard → Tambah Monitor**, isi:

- **Nama** — nama deskriptif untuk monitor ini
- **Tipe** — pilih sesuai kebutuhan (lihat tabel Tipe Monitor di atas)
- **URL / Host** — URL lengkap untuk HTTP/Keyword, hostname atau IP untuk Ping/TCP
- **Interval** — seberapa sering dicek (menit)
- **Timeout** — batas waktu tunggu respons (detik)
- **Channel Notifikasi** — centang channel (WA/Telegram) yang akan menerima alert

IP address domain akan otomatis di-resolve dan ditampilkan di halaman detail monitor.

### Status Page Builder

Masuk ke **Status Pages → Buat Status Page**:

1. Isi judul, slug URL, dan deskripsi
2. Di section **Builder**, tambah grup/section (contoh: "Layanan Web", "API Services")
3. Tambahkan monitor ke dalam setiap section dari dropdown
4. Atur urutan section dan monitor dengan tombol panah atas/bawah
5. Klik **Buat Status Page** — halaman publik langsung bisa diakses di `/status/{slug}`

---

## Cara Menambah API Service Baru

Tambahkan entry di `config/services_custom.php`:

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

Set URL di `.env`:

```env
NAMA_BASE_URL=https://api.contoh.com
```

Service otomatis muncul di **API Health Dashboard** tanpa perlu restart.

---

## BPJS CDN / Non-CDN Switch

Di halaman **API Health Dashboard**, tersedia toggle untuk beralih antara host BPJS:

| Mode | Host |
| --- | --- |
| Non-CDN | `https://apijkn.bpjs-kesehatan.go.id` |
| CDN | `https://new-apijkn.bpjs-kesehatan.go.id` |

Saat mode diganti, semua cache hasil pengecekan BPJS dibersihkan otomatis sehingga hasil yang ditampilkan selalu sesuai mode aktif. PCare menggunakan domain tersendiri (`apijkn.kesehatan.go.id`) dan tidak terpengaruh oleh toggle ini.

---

## Catatan Keamanan

Aplikasi ini **tidak menyimpan** credential apapun (cons_id, secret_key, client_id, client_secret, token API BPJS/Satu Sehat). Fungsinya hanya mengecek konektivitas jaringan ke endpoint, bukan mengintegrasikan atau mengakses data dari layanan tersebut.

---

## Artisan Commands

```bash
# Cek semua monitor uptime (HTTP & Ping)
php artisan monitor:check

# Cek monitor tertentu berdasarkan ID
php artisan monitor:check --id=1

# Cek semua API health service (BPJS, Satu Sehat, dll)
php artisan api:health-check

# Cek service tertentu
php artisan api:health-check --service=bpjs_vclaim
```

---

## License

MIT
