# Panduan Penggunaan WatchTower

Panduan lengkap cara menggunakan semua fitur WatchTower Uptime Monitor.

---

## Daftar Isi

1. [Dashboard](#1-dashboard)
2. [Tambah Monitor](#2-tambah-monitor)
3. [Tipe Monitor & Konfigurasi Khusus](#3-tipe-monitor--konfigurasi-khusus)
4. [Pengaturan Lanjutan Monitor](#4-pengaturan-lanjutan-monitor)
5. [Notifikasi Channel](#5-notifikasi-channel)
6. [On-Call Schedule](#6-on-call-schedule)
7. [Eskalasi Insiden](#7-eskalasi-insiden)
8. [SLA Contract](#8-sla-contract)
9. [Incident Tracking](#9-incident-tracking)
10. [Maintenance Window](#10-maintenance-window)
11. [Status Page](#11-status-page)
12. [Tags & Organisasi Monitor](#12-tags--organisasi-monitor)
13. [Template Monitor](#13-template-monitor)
14. [Import / Export](#14-import--export)
15. [API Token & REST API](#15-api-token--rest-api)
16. [Webhook Inbound](#16-webhook-inbound)
17. [Business Hours](#17-business-hours)
18. [Audit Log](#18-audit-log)
19. [Topology / Dependency Map](#19-topology--dependency-map)
20. [Alert Test Simulator](#20-alert-test-simulator)
21. [Laporan Otomatis](#21-laporan-otomatis)
22. [Contoh Kasus Nyata](#22-contoh-kasus-nyata)

---

## 1. Dashboard

Halaman utama menampilkan:

- **Sidebar kiri** — daftar semua monitor dengan status UP/DOWN/SLOW/FLAPPING, response time, dan uptime 24h
- **Panel kanan** — detail monitor yang dipilih: heartbeat bar 90 titik, grafik response time 48 jam, info IP, status SSL/domain
- **Header** — public IP server, tombol Cek Semua, toggle Dark Mode

### Aksi Cepat dari Dashboard

Klik monitor di sidebar → panel detail muncul. Di atas panel:

| Tombol | Fungsi |
|---|---|
| Pause / Resume | Nonaktifkan/aktifkan pengecekan |
| Cek | Jalankan pengecekan manual sekarang |
| Silence | Hentikan notifikasi 1/4/24 jam |
| Edit | Buka form edit monitor |
| Hapus | Hapus monitor (konfirmasi SweetAlert) |

### Filter Tag

Jika sudah ada tag, muncul filter di atas sidebar. Klik tag untuk tampilkan hanya monitor dengan tag tersebut.

---

## 2. Tambah Monitor

Ada dua cara menambah monitor:

### A. Dari Dashboard

Klik tombol **+ Tambah Monitor** di header atau sidebar. Modal muncul dengan field:

- Nama, Tipe, URL/Host, Interval, Timeout, Retry
- Notifikasi channel (centang yang diinginkan)
- Tags

### B. Dari Halaman Monitors

**Menu → Monitors → Tambah Monitor** — form lengkap dengan semua opsi termasuk pengaturan lanjutan.

### Field Utama

| Field | Keterangan | Default |
|---|---|---|
| Nama | Label deskriptif untuk monitor | — |
| Tipe | Pilih tipe monitor (lihat bagian 3) | http |
| Interval | Seberapa sering dicek (menit) | 5 |
| Timeout | Batas waktu tunggu respons (detik) | 10 |
| Gagal → DOWN | Kegagalan berturut-turut sebelum DOWN + notifikasi | 1 |
| Batas Lambat (ms) | Alert terpisah jika response > nilai ini | kosong |
| Environment | Production / Staging / Development / Testing | production |
| Channel Notifikasi | Channel yang menerima alert DOWN/UP/SLOW | — |
| Tags | Grup/label monitor | — |

---

## 3. Tipe Monitor & Konfigurasi Khusus

### HTTP / HTTPS

```
URL: https://simrs.rumahsakit.com
```

Monitor dianggap UP selama server merespons (kode apapun). DOWN hanya jika koneksi gagal total.

Opsi tambahan (di Pengaturan Lanjutan):
- **HTTP Method** — GET, POST, PUT, PATCH, DELETE, HEAD
- **Request Body** — untuk POST/PUT (JSON atau form)
- **Accepted Status Codes** — contoh `200,201,301` — hanya kode ini yang dianggap UP
- **HTTP Auth** — Basic Auth (username + password) atau Bearer Token
- **Custom Headers** — JSON: `{"X-API-Key": "secret", "Accept": "application/json"}`

### Keyword

```
URL: https://simrs.rumahsakit.com
Keyword: RUNNING
```

Sama seperti HTTP, tapi tambahan: response body harus mengandung string keyword. DOWN jika string tidak ditemukan.

Gunakan untuk validasi konten halaman (bukan sekadar konektivitas).

### Ping (ICMP)

```
Host: 192.168.1.1
atau
Host: server.rumahsakit.com
```

Isi hostname atau IP langsung — **tanpa** `http://`. Cocok untuk server yang memblokir HTTP tapi bisa di-ping.

### TCP Port

```
Host: db.rumahsakit.com
Port: 3306
```

Cek apakah port terbuka dan bisa dikoneksi. Cocok untuk database, mail server, atau port non-HTTP.

### DNS

```
Domain: example.com
Record Type: A
Expected Value: 1.2.3.4
```

Resolve domain dan bandingkan hasilnya. DOWN jika resolve gagal atau nilai berbeda dari expected.

### Push Heartbeat

```
Token: [generate otomatis]
URL Heartbeat: https://monitor.namadomain.com/push/{token}
```

1. Klik **Generate Token** atau isi manual
2. Salin URL heartbeat
3. Panggil URL dari proses yang ingin dimonitor (cronjob, script backup, dll)
4. Atur **Interval** — DOWN jika tidak ada heartbeat selama N menit

Contoh cronjob Linux:
```bash
*/5 * * * * curl -s https://monitor.namadomain.com/push/TOKEN > /dev/null
```

Contoh Windows Task Scheduler:
```powershell
Invoke-WebRequest -Uri "https://monitor.namadomain.com/push/TOKEN" -UseBasicParsing | Out-Null
```

### Cron Job Monitor

Mirip Push, tapi khusus cron. Sama-sama pakai token heartbeat, tapi ada field **Heartbeat Interval** (menit) — DOWN otomatis jika tidak ada ping dalam interval tersebut.

Gunakan tipe ini untuk memastikan cron job (backup, sync, laporan) berjalan sesuai jadwal.

Contoh di script backup:
```bash
#!/bin/bash
mysqldump -u root database_name > /backup/db_$(date +%Y%m%d).sql.gz
# Kirim heartbeat HANYA jika backup sukses
if [ $? -eq 0 ]; then
    curl -s https://monitor.namadomain.com/push/TOKEN > /dev/null
fi
```

### Database

```
Connection String: mysql://user:password@host:3306/dbname
                   pgsql://user:password@host:5432/dbname
                   redis://host:6379
                   redis://:password@host:6379/0
```

WatchTower mencoba koneksi langsung ke database. UP jika koneksi berhasil, DOWN jika gagal.

**Catatan keamanan:** simpan connection string dengan hati-hati. Gunakan user DB dengan hak akses minimal (hanya CONNECT, tanpa SELECT/INSERT).

### Docker Container

```
Container: nama_container
atau
Socket: unix:///var/run/docker.sock
Remote:  http://docker-host:2375/containers/nama/json
```

Cek apakah container dalam status `running`. DOWN jika container stop, exit, atau tidak ditemukan.

Untuk Docker remote, pastikan API Docker terbuka (port 2375) hanya di jaringan internal.

### WHOIS / Domain Expiry

```
Domain: rumahsakit.com
Alert X hari sebelum expired: 30
```

WatchTower query WHOIS dan baca tanggal expiry. Alert dikirim jika sisa hari kurang dari threshold yang dikonfigurasi.

---

## 4. Pengaturan Lanjutan Monitor

Klik **Tampilkan Pengaturan Lanjutan** di form monitor (hanya di halaman `/monitors/create` dan `/monitors/{id}/edit`).

### HTTP & Body

| Field | Keterangan |
|---|---|
| HTTP Method | GET/POST/PUT/PATCH/DELETE/HEAD/OPTIONS |
| Request Body | Body untuk POST/PUT (JSON, form, plain text) |
| Custom User-Agent | Override UA browser default |
| Accepted Status Codes | Kode HTTP yang dianggap UP, pisah koma: `200,201,301` |
| Ignore TLS Error | Bypass validasi SSL cert (server internal/self-signed) |
| Follow Redirects | Toggle redirect; isi Max Redirect Count |
| HTTP Proxy | `http://proxy:3128` atau `socks5://proxy:1080` |

### Auth

| Field | Keterangan |
|---|---|
| Auth Type | None / Basic / Bearer |
| Username | Untuk Basic Auth |
| Password / Token | Password (Basic) atau token (Bearer) |
| Custom Headers | JSON object header tambahan |

### Body Assertion

Validasi nilai dari response body JSON:

| Field | Keterangan |
|---|---|
| JSON Path | Contoh: `$.status` atau `$.data.health` |
| Operator | equals / contains / not_contains |
| Expected Value | Nilai yang diharapkan: `ok`, `healthy`, dll |

Contoh: `$.status` equals `ok` — DOWN jika `{"status":"error"}`.

### Ukuran Response

| Field | Keterangan |
|---|---|
| Min Response Size (bytes) | DOWN jika response lebih kecil dari ini |
| Max Response Size (bytes) | DOWN jika response lebih besar dari ini |

Gunakan untuk deteksi halaman kosong (min) atau response tidak wajar besar (max).

### Flap Detection

Cegah notifikasi spam jika monitor UP-DOWN berulang:

| Field | Keterangan | Contoh |
|---|---|---|
| Aktifkan Flap Detection | Toggle | ✓ |
| Window (menit) | Periode observasi | 10 |
| Count Threshold | Jumlah flap yang memicu status FLAPPING | 3 |

Jika dalam 10 menit terjadi 3 kali bolak-balik UP/DOWN → status `FLAPPING` → notifikasi ditahan.

### Suppress Pattern

Regex pada response body untuk mencegah false positive DOWN:

```
# Contoh: anggap UP meski koneksi sukses tapi body mengandung "maintenance"
suppress_pattern: maintenance|under.*construction
```

Jika response body match pattern → status tidak berubah jadi DOWN.

### Latency Trend Alert

Aktifkan untuk mendapat alert jika response time naik konsisten dalam 5 pengecekan terakhir (meski masih di bawah threshold batas lambat). Berguna untuk deteksi dini degradasi performa.

---

## 5. Notifikasi Channel

**Menu → Notifikasi → Tambah Channel**

### Telegram Bot

1. Buka Telegram → cari **@BotFather** → `/newbot` → salin Token
2. Dapatkan Chat ID:
   ```
   https://api.telegram.org/bot<TOKEN>/getUpdates
   ```
   Kirim pesan ke bot dulu, lalu buka URL di atas. Cari `"chat":{"id":123456789}`.
3. Isi Token dan Chat ID di form channel
4. Klik **Test** untuk verifikasi

Untuk grup: tambah bot ke grup → gunakan Chat ID grup (diawali `-`).

### WhatsApp (Fonnte)

1. Daftar di [fonnte.com](https://fonnte.com) → tambah device → scan QR
2. Salin Token dari halaman device
3. Isi Token dan Target: `628xxxxxxxxxx` (format internasional tanpa `+`)
4. Klik **Test**

### Email (SMTP)

Konfigurasi SMTP di `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=akun@gmail.com
MAIL_PASSWORD=app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=watchtower@namadomain.com
MAIL_FROM_NAME=WatchTower
```

Di form channel: isi alamat email tujuan.

### Slack

1. Buat Incoming Webhook di Slack: **Apps → Incoming Webhooks → Add to Slack**
2. Pilih channel → salin Webhook URL
3. Isi URL di form channel

### Discord

1. Di Discord: klik roda gigi channel → **Integrations → Webhooks → New Webhook**
2. Salin Webhook URL
3. Isi URL di form channel

### ntfy.sh

1. Install [ntfy](https://ntfy.sh) app di HP atau gunakan ntfy.sh
2. Subscribe ke topic (nama unik)
3. Isi URL: `https://ntfy.sh/nama-topic-anda`
4. Isi Token jika topic private

### Pushover

1. Daftar di [pushover.net](https://pushover.net) → salin User Key
2. Buat Application → salin API Token
3. Isi kedua nilai di form channel

### Webhook (HTTP POST)

1. Isi URL endpoint yang menerima POST
2. Isi Secret Key (opsional) untuk HMAC signature
3. Payload dikirim sebagai JSON (lihat format di README)

---

## 6. On-Call Schedule

Jadwal siaga tim — alert otomatis dikirim ke channel yang sedang shift aktif.

**Menu → On-Call**

### Buat Jadwal

1. Klik **Buat Jadwal Baru**
2. Isi nama jadwal (misal: "Tim IT Malam") dan deskripsi
3. Tambah shift:

| Field | Keterangan |
|---|---|
| Nama Shift | Label shift (misal: "Shift Pagi") |
| Channel | Channel notifikasi yang dihubungi saat shift ini |
| Hari | Semua hari / Hari tertentu (Senin–Minggu) |
| Jam Mulai | Format HH:MM |
| Jam Selesai | Format HH:MM |
| Info Kontak | Nomor HP / email penanggung jawab shift |

4. Tambah beberapa shift untuk menutup 24 jam

### Cara Kerja

Saat ada alert, sistem cek jadwal on-call aktif:
- Cocokkan hari + jam sekarang dengan shift yang terdaftar
- Kirim notifikasi ke channel shift yang aktif
- Jika tidak ada shift aktif → notifikasi ke channel default monitor

---

## 7. Eskalasi Insiden

Alert otomatis ke channel berbeda jika insiden tidak di-handle dalam waktu tertentu.

**Menu → Eskalasi**

### Buat Rule Eskalasi

| Field | Keterangan |
|---|---|
| Nama | Label rule (misal: "Eskalasi ke Manager 30 mnt") |
| Channel | Channel tujuan eskalasi |
| Delay (menit) | Kirim notifikasi eskalasi setelah X menit DOWN |
| Monitor | Kosong = berlaku global; isi untuk monitor tertentu saja |
| Aktif | Toggle aktif/nonaktif |

### Cara Kerja

1. Monitor DOWN → insiden dibuka
2. `EscalateIncidentJob` di-dispatch dengan delay sesuai rule
3. Saat delay tercapai → cek apakah insiden masih open
4. Jika masih open → kirim notifikasi ke channel eskalasi
5. Jika insiden sudah tutup → tidak ada eskalasi

---

## 8. SLA Contract

Pantau target SLA per layanan dan tracking sisa downtime budget.

**Menu → SLA**

### Buat SLA Contract

| Field | Keterangan |
|---|---|
| Monitor | Pilih monitor yang di-cover |
| Nama SLA | Label kontrak (misal: "SLA SIMRS 99.9%") |
| Target Uptime (%) | Target SLA dalam persen (misal: 99.9) |
| Periode Mulai | Tanggal mulai periode SLA |
| Periode Selesai | Tanggal akhir periode SLA |
| Downtime Budget (menit) | Total downtime yang diizinkan dalam periode |
| Catatan | Informasi tambahan atau referensi kontrak |

### Cara Membaca SLA Card

- **Progress bar hijau** — sisa downtime budget masih aman
- **Progress bar kuning** — budget terpakai >70%
- **Progress bar merah** — budget terpakai >90% atau sudah habis
- **Uptime %** — dihitung dari heartbeat log dalam periode

---

## 9. Incident Tracking

**Menu → Insiden**

### Insiden Otomatis

Tercatat otomatis saat:
- Monitor transisi DOWN (setelah retry terpenuhi)
- Monitor pulih UP (insiden otomatis ditutup, durasi dihitung)

### Tambah Insiden Manual

Untuk gangguan yang tidak terdeteksi monitor (pemadaman listrik, laporan klien):

| Field | Keterangan |
|---|---|
| Monitor | Monitor terkait (opsional) |
| Judul | Deskripsi singkat insiden |
| Severity | Low / Medium / High / Critical |
| Kategori | monitor_downtime / Insiden Umum / Laporan Client |
| Waktu Mulai | Kapan insiden dimulai |
| Waktu Selesai | Kosong jika masih berlangsung |
| Pelapor | Nama dan kontak (untuk Laporan Client) |
| Deskripsi | Detail insiden, langkah penanganan |
| Post-Mortem / RCA | Analisis akar penyebab setelah insiden selesai |

### Auto-Close

Insiden otomatis ditutup jika monitor UP selama periode tertentu (konfigurasi di `monitor:auto-close-incidents` — berjalan tiap 5 menit).

---

## 10. Maintenance Window

Jadwalkan downtime terjadwal — selama window aktif, notifikasi tidak dikirim dan insiden tidak dicatat.

**Menu → Maintenance → Tambah**

| Field | Keterangan |
|---|---|
| Judul | Label maintenance (misal: "Update Server DB") |
| Deskripsi | Detail pekerjaan yang dilakukan |
| Monitor | Monitor mana yang terdampak (bisa pilih semua) |
| Waktu Mulai | Tanggal dan jam mulai |
| Waktu Selesai | Tanggal dan jam selesai |

Window aktif → badge "MAINTENANCE" muncul di dashboard untuk monitor terdampak.

### Silence Cepat

Untuk silence cepat tanpa form maintenance, gunakan tombol **Silence** di dashboard atau detail monitor:
- Pilih durasi: 1 jam / 4 jam / 24 jam
- Notifikasi langsung dihentikan; monitor tetap dicek

---

## 11. Status Page

Buat halaman status publik untuk manajemen atau klien — tanpa login.

**Menu → Status Pages → Buat**

### Setup

1. Isi **Judul**, **Slug** (URL: `/status/slug-ini`), dan **Deskripsi**
2. Tambah section/grup (contoh: "Layanan Web", "API Services", "Infrastruktur")
3. Tambah monitor ke tiap section
4. Toggle **Publik** untuk aktifkan halaman
5. Klik **Simpan**

Halaman aktif di: `https://monitor.namadomain.com/status/{slug}`

### Custom Domain

Isi field **Custom Domain** (misal: `status.rumahsakit.com`). Arahkan DNS CNAME ke server WatchTower. Halaman akan muncul saat akses via domain tersebut.

### Widget Embed

Embed status mini ke website lain:
```html
<iframe src="https://monitor.namadomain.com/status/{slug}/widget"
        width="320" height="200" frameborder="0"
        style="border-radius:12px;border:1px solid #e5e7eb"></iframe>
```

### Uptime Badge

Embed badge SVG ke README atau portal:
```markdown
![Status](https://monitor.namadomain.com/status/{slug}/badge.svg)
```

---

## 12. Tags & Organisasi Monitor

**Menu → Tags** — buat tag dengan nama dan warna.

### Buat Tag

1. Klik **+ Tag Baru**
2. Isi nama dan pilih warna
3. Simpan

### Assign Tag ke Monitor

Di form tambah/edit monitor → section **Tags** → centang tag yang diinginkan.

### Filter Dashboard

Di sidebar dashboard, klik nama tag untuk tampilkan hanya monitor dengan tag tersebut. Klik **Semua** untuk reset filter.

---

## 13. Template Monitor

Preset konfigurasi monitor siap pakai. Cocok untuk setup cepat tanpa perlu isi ulang setting yang sama.

**Menu → Templates**

Template tersedia per kategori:
- **Web** — WordPress, Laravel, SatuSehat, BPJS
- **Database** — MySQL, PostgreSQL, Redis
- **Infrastruktur** — Nginx, Apache, server Linux
- **API** — REST API generic, JSON health check
- **Domain** — WHOIS expiry, SSL check
- **Heartbeat** — Cron job, backup notifier

### Gunakan Template

1. Klik **Gunakan** pada template yang dipilih
2. Form tambah monitor terbuka dengan field pre-filled
3. Sesuaikan nama, URL, dan channel notifikasi
4. Simpan

---

## 14. Import / Export

**Menu → Monitors → Import / Export**

### Export JSON

Backup semua konfigurasi monitor (tanpa data history/log):

1. Klik **Export JSON**
2. File `watchtower-monitors-{tanggal}.json` terunduh

### Import JSON

Restore dari backup atau copy ke server lain:

1. Pilih file JSON hasil export
2. Klik **Import** — monitor baru dibuat, monitor yang sudah ada dilewati (berdasarkan nama)

### Import CSV

Upload daftar monitor dari spreadsheet:

Kolom CSV:
```
name,type,url,check_interval,timeout
SIMRS,http,https://simrs.rs.com,5,10
DB Server,ping,192.168.1.10,2,5
```

### Smoke Test

Trigger pengecekan semua monitor sekaligus:

1. Klik **Smoke Test**
2. Tunggu hasil (bisa 1–2 menit tergantung jumlah monitor)
3. Laporan pass/fail muncul per monitor

Gunakan setelah deploy besar untuk verifikasi semua layanan masih UP.

---

## 15. API Token & REST API

**Menu → API Tokens**

### Buat Token

1. Klik **+ Buat Token Baru**
2. Isi nama token dan tanggal expired (opsional)
3. Pilih kemampuan (abilities): `read`, `write`, atau keduanya
4. Token tampil sekali — salin dan simpan

### Gunakan REST API

Header semua request:
```
Authorization: Bearer {token}
Accept: application/json
```

Endpoint tersedia:

| Method | URL | Keterangan |
|---|---|---|
| GET | `/api/monitors` | List semua monitor |
| GET | `/api/monitors/{id}` | Detail monitor |
| GET | `/api/monitors/{id}/logs` | Log pengecekan |
| GET | `/api/incidents` | List insiden aktif |
| GET | `/api/status` | Ringkasan status global |

Contoh:
```bash
curl -H "Authorization: Bearer token123" \
     -H "Accept: application/json" \
     https://monitor.namadomain.com/api/monitors
```

---

## 16. Webhook Inbound

Terima alert dari sistem monitoring lain (Grafana, Zabbix, Prometheus, Alertmanager).

**Menu → Webhook In**

### Buat Receiver

1. Klik **+ Buat Receiver**
2. Isi nama (misal: "Grafana Alerts")
3. Token otomatis dibuat
4. URL endpoint: `https://monitor.namadomain.com/webhook-in/{token}`

### Konfigurasi di Sistem Sumber

**Grafana:**
```json
{
  "url": "https://monitor.namadomain.com/webhook-in/{token}",
  "httpMethod": "POST"
}
```

**Prometheus Alertmanager:**
```yaml
receivers:
  - name: watchtower
    webhook_configs:
      - url: https://monitor.namadomain.com/webhook-in/{token}
```

### Lihat Payload Terakhir

Klik **Detail** pada receiver untuk lihat payload terakhir yang diterima — berguna untuk debugging.

Atau akses langsung:
```
GET https://monitor.namadomain.com/webhook-in/{token}
```

---

## 17. Business Hours

Konfigurasi jam kerja untuk routing alert berbeda di jam kerja vs luar jam kerja.

**Menu → Business Hours**

### Setup

Untuk tiap hari dalam seminggu:

1. Toggle **Hari Kerja** (aktif/nonaktif)
2. Isi **Jam Mulai** dan **Jam Selesai**

Contoh konfigurasi umum:
| Hari | Jam Kerja |
|---|---|
| Senin–Jumat | 08:00 – 17:00 |
| Sabtu | 08:00 – 13:00 |
| Minggu | Libur |

### Cara Kerja

Sistem `CheckMonitors` cek jam saat ini vs Business Hours:
- **Jam kerja** → notifikasi ke channel normal
- **Luar jam kerja** → bisa di-route ke on-call schedule yang berbeda, atau notifikasi ditahan tergantung konfigurasi channel

---

## 18. Audit Log

Rekam semua aksi penting — siapa melakukan apa, kapan.

**Menu → Audit Log**

### Aksi yang Tercatat

| Aksi | Keterangan |
|---|---|
| `monitor.created` | Monitor baru dibuat |
| `monitor.updated` | Setting monitor diubah |
| `monitor.deleted` | Monitor dihapus |
| `monitor.checked` | Cek manual dijalankan |
| `monitor.toggled` | Monitor di-pause atau di-resume |
| `monitor.cloned` | Monitor diduplikat |
| `incident.opened` | Insiden DOWN dibuka |
| `incident.closed` | Insiden ditutup |
| `maintenance.created` | Maintenance window dibuat |

### Filter

- Filter berdasarkan tanggal (mulai/selesai)
- Filter berdasarkan tipe aksi
- Cari berdasarkan nama monitor

---

## 19. Topology / Dependency Map

Visualisasi dependency antar monitor — skip notifikasi jika parent DOWN.

**Menu → Topology**

### Setup Dependency

Di form edit monitor → section **Dependencies** → pilih monitor yang jadi parent.

Contoh: Monitor "SIMRS API" bergantung pada "DB Server". Jika "DB Server" DOWN, monitor "SIMRS API" skip notifikasi (karena penyebab root sudah jelas: DB mati).

### Membaca Topology

- **Node hijau** — monitor UP
- **Node merah** — monitor DOWN
- **Node abu-abu** — monitor nonaktif
- **Garis panah** — arah dependency (A → B artinya A bergantung pada B)

---

## 20. Alert Test Simulator

Simulasi kondisi DOWN/UP/SLOW tanpa mematikan server asli.

**Menu → Monitors → Detail Monitor → Simulate** atau via artisan:

```bash
# Simulasi DOWN
php artisan monitor:simulate {id} down

# Simulasi UP (pulih)
php artisan monitor:simulate {id} up

# Simulasi SLOW (response lambat)
php artisan monitor:simulate {id} slow
```

Gunakan untuk:
- Verifikasi notifikasi channel terkirim dengan benar
- Test template pesan notifikasi
- Demo ke manajemen bagaimana sistem bekerja saat insiden

---

## 21. Laporan Otomatis

Laporan ringkasan dikirim otomatis ke channel notifikasi yang dikonfigurasi.

**Menu → Settings → Notifikasi → Laporan Otomatis**

### Konfigurasi

| Setting | Keterangan |
|---|---|
| Aktifkan Laporan | Toggle on/off |
| Laporan Harian | Kirim tiap hari |
| Laporan Mingguan | Kirim tiap Senin |
| Jam Kirim | Format HH:MM (contoh: `07:00`) |
| Channel Tujuan | Centang channel yang menerima laporan |

### Isi Laporan

- Total monitor aktif
- Jumlah UP / DOWN saat ini
- Insiden dalam 24 jam terakhir (harian) atau 7 hari (mingguan)
- Top 5 monitor dengan uptime terendah
- Total downtime periode

### Kirim Manual

```bash
php artisan monitor:report --period=daily
php artisan monitor:report --period=weekly
```

---

## 22. Contoh Kasus Nyata

### Kasus 1 — Monitoring SIMRS dan dependensinya

Setup monitoring berlapis untuk Sistem Informasi Rumah Sakit:

```
DB Server (MySQL)     ← ping + TCP 3306
    ↓
SIMRS Backend API     ← HTTP keyword "status":"ok"
    ↓
SIMRS Frontend Web    ← HTTP
    ↓
SIMRS Publik (pasien) ← HTTP + keyword "Jadwal Dokter"
```

Konfigurasi dependency: Frontend bergantung pada Backend, Backend bergantung pada DB.

Jika DB mati:
- DB DOWN → insiden dibuka, notifikasi terkirim
- Backend → skip notifikasi (parent DOWN)
- Frontend → skip notifikasi (parent DOWN)
- Hanya 1 notifikasi yang dikirim, bukan 3

### Kasus 2 — Monitoring BPJS dengan Push dari Server WhiteList

BPJS memblokir IP yang tidak di-whitelist. Solusi: push heartbeat dari server yang sudah di-whitelist.

1. Tambah monitor tipe **Cron** di WatchTower, salin URL heartbeat
2. Di server yang sudah whitelist BPJS:
   ```bash
   */5 * * * * curl -s https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/peserta/noKartu/xxxx -H "Authorization: ..." && curl -s https://monitor.namadomain.com/push/TOKEN
   ```
3. Heartbeat dikirim hanya jika BPJS berhasil diakses
4. WatchTower DOWN jika tidak ada heartbeat → berarti BPJS tidak bisa diakses dari server whitelist

### Kasus 3 — Status Page untuk Direksi

Tim IT ingin memberikan visibility ke direktur tanpa akses ke dashboard internal:

1. Buat Status Page "Dashboard Layanan RS"
2. Section: "Layanan Pasien", "Sistem Internal", "Koneksi Eksternal"
3. Masukkan monitor per section
4. Share URL `/status/dashboard-rs` ke grup WhatsApp direksi
5. Halaman auto-refresh tiap 60 detik, tidak butuh login

### Kasus 4 — Deteksi Backup Tidak Jalan

```bash
#!/bin/bash
# /usr/local/bin/backup-db.sh (berjalan tiap hari jam 02.00)

BACKUP_FILE="/backup/db_$(date +%Y%m%d_%H%M).sql.gz"

mysqldump -u backup_user -pPASSWORD database_prod | gzip > "$BACKUP_FILE"

if [ $? -eq 0 ] && [ -s "$BACKUP_FILE" ]; then
    # Backup sukses dan file tidak kosong → kirim heartbeat
    curl -s "https://monitor.namadomain.com/push/TOKEN_BACKUP" > /dev/null
    echo "[$(date)] Backup sukses: $BACKUP_FILE"
else
    echo "[$(date)] BACKUP GAGAL!" >&2
    # Tidak kirim heartbeat → WatchTower akan DOWN setelah 25 jam tanpa ping
fi
```

Monitor Cron di WatchTower: interval 1500 menit (25 jam). Jika backup tidak jalan 2 malam berturut-turut → alert.

### Kasus 5 — Monitor Port Database yang Harus Tertutup

Pastikan MySQL (3306) tidak bocor ke internet:

```
Nama: [SECURITY] MySQL Port Public Check
Tipe: TCP
Host: IP publik server database
Port: 3306
Channel: channel khusus security alert
```

Logika terbalik: monitor ini **seharusnya selalu DOWN**. Jika tiba-tiba UP → firewall bocor → alert keamanan segera dikirim.

### Kasus 6 — On-Call Shift Malam

**Senin–Jumat:**
- Shift Pagi (08.00–17.00): Channel Telegram "Tim IT Siang"
- Shift Malam (17.00–08.00): Channel WhatsApp "Piket Malam"
- Sabtu–Minggu: Channel WhatsApp "Piket Weekend"

Setup:
1. Buat jadwal "Jadwal Piket IT"
2. Tambah shift Senin–Jumat pagi → channel Telegram siang
3. Tambah shift Senin–Jumat malam → channel WA malam
4. Tambah shift Sabtu–Minggu (day_of_week = null, jam 00:00–23:59) → channel WA weekend
5. Aktifkan jadwal

Alert DOWN jam 02.00 Selasa → dikirim ke channel WA piket malam otomatis.

---

## Tips & Best Practice

**Interval pengecekan:**
- Layanan kritis (SIMRS, BPJS): 1–2 menit
- Layanan penting: 5 menit
- Layanan non-kritis: 10–15 menit
- BPJS/Satu Sehat (IP publik, bukan whitelist): minimal 15 menit agar tidak diblokir

**Retry count:**
- Layanan stabil: 1–2
- Layanan yang sering fluktuasi sesaat: 3–5
- BPJS (sering timeout sekali lalu OK): 3

**Threshold batas lambat (ms):**
- API internal LAN: 200–500 ms
- API internet: 1000–3000 ms
- BPJS/Satu Sehat: 5000–10000 ms

**Flap Detection:**
- Aktifkan untuk semua layanan yang terhubung ke internet
- Window: 10 menit, threshold: 3 flap

**SLA:**
- Buat SLA contract per layanan kritis
- Set periode per kuartal atau per bulan
- Review progress bar setiap Senin pagi
