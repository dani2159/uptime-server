# WatchTower — Feature Roadmap

> Label: `[done]` sudah implemented · `[general]` ide umum · `[kuma]` fitur Kuma yang belum ada · `[wt+]` keunggulan eksklusif vs Kuma

---

## ✅ Sudah Implemented

| Fitur | Keterangan |
|-------|-----------|
| Monitor HTTP/TCP/DNS/Ping/Keyword/Push | Multi-type monitoring |
| Rapid Recheck on DOWN | Recheck setiap 20 detik hingga retry_count sebelum notif |
| Notification: Telegram, WhatsApp (Fonnte), Webhook | Multi-channel, template kustom |
| Telegram Supergroup Topic | Format `chat_id:thread_id` |
| Custom Notification Template | Template DOWN/UP/SLOW/Eskalasi dengan variabel |
| SSL Certificate Monitor & Alert | Alert X hari sebelum expire |
| Monitor Tags / Grup | Multi-tag, pivot table, filter dashboard |
| Silence Window Cepat | Quick silence dari dashboard, durasi 1h/4h/24h |
| Response Time Warning (SLOW) | Badge kuning, threshold per monitor, notif `notifySlow` |
| Audit Log | Track semua aksi monitor/insiden, filter tanggal+aksi |
| Notifikasi Eskalasi | Global + per-monitor, delay configurable, queue-based |
| Laporan Otomatis Harian/Mingguan | Schedule via AppSetting, test kirim manual |
| Status Page Custom Domain + Widget Embed | iframe widget, fallback route by host |
| Maintenance Window | One-time dan berulang, support multi-monitor |
| Incident Management | Open/close, kategori, severity, reporter |
| SLA Report | Uptime % per monitor, export-ready |
| BPJS/API Health Dashboard | Monitor endpoint BPJS dengan mode check kustom |
| Dark Mode | Tailwind class-based, persist localStorage |

---

## 📋 Backlog — General Improvements

| # | Fitur | Prioritas |
|---|-------|-----------|
| 1 | Uptime Badge SVG — `/status/{slug}/badge.svg` embed README/portal | Tinggi |
| 2 | HTTP Auth & Custom Headers per Monitor — basic/bearer, headers JSON | Tinggi |
| 3 | Cron Monitor — expected heartbeat, DOWN jika tidak ping dalam X menit | Tinggi |
| 4 | On-Call Schedule — jadwal siaga, eskalasi ke petugas yang bertugas | Tinggi |
| 5 | Monitor Dependencies — skip notif jika parent DOWN | Sedang |
| 6 | REST API + Token — GET /api/monitors dengan Bearer token | Sedang |
| 7 | Import/Export Monitor JSON — backup & clone konfigurasi | Sedang |
| 8 | Favicon Indicator — favicon merah jika ada monitor DOWN | Rendah |
| 9 | Notification Digest/Batching — 1 pesan jika banyak DOWN serentak | Sedang |
| 10 | Monitor Notes & Runbook Link — notes + runbook_url di notifikasi | Sedang |
| 11 | SMTP/Email Channel — notifikasi via email | Tinggi |
| 12 | Slack/Discord Webhook Channel — Slack blocks / Discord embeds | Sedang |
| 13 | Response Body Assertion — cek JSON path `$.status === ok` | Tinggi |
| 14 | Incident Auto-Close Timer — auto-close jika UP X menit berturut | Sedang |
| 15 | Monitor Heatmap/Availability Calendar — kalender uptime per hari | Sedang |
| 16 | Latency Trend Alert — warning jika RT naik konsisten 5 cek terakhir | Sedang |
| 17 | Multi-User/Role Access — Admin, Viewer, Operator | Tinggi |
| 18 | Maintenance Recurring — silence berulang otomatis (cron-based) | Sedang |
| 19 | Monitor Clone — duplikat monitor salin semua setting | Rendah |
| 20 | WHOIS/Domain Expiry Monitor — alert X hari sebelum domain expired | Sedang |
| 21 | Alert Suppression Pattern — regex body untuk suppress false positive | Sedang |

---

## 🟡 Fitur Kuma yang Belum Ada di WatchTower

| # | Fitur | Prioritas |
|---|-------|-----------|
| K1 | WebSocket/SSE Live Update — dashboard real-time tanpa refresh | Tinggi |
| K2 | ntfy.sh Channel — push notification gratis ke HP | Sedang |
| K3 | Pushover / Gotify Channel — alternatif notifikasi ringan | Rendah |
| K4 | Database Monitor — koneksi langsung MySQL/PostgreSQL/Redis/MongoDB | Tinggi |
| K5 | Docker Container Monitor — cek status container via Docker daemon | Sedang |
| K6 | gRPC Monitor — health check via gRPC protocol | Rendah |
| K7 | HTTP Method + Request Body — POST/PUT/PATCH dengan custom body | Tinggi |
| K8 | Accepted Status Codes Kustom — set HTTP code yang dianggap UP | Tinggi |
| K9 | Ignore TLS Error per Monitor — bypass SSL invalid untuk server internal | Tinggi |
| K10 | Follow Redirects Toggle + Max Count — kontrol redirect per monitor | Sedang |
| K11 | Custom User-Agent per Monitor — set UA agar tidak diblock | Rendah |
| K12 | HTTP Proxy per Monitor — route request lewat proxy SOCKS5/HTTP | Rendah |

---

## 🚀 WatchTower+ — Keunggulan Eksklusif vs Kuma

> Fitur-fitur ini **tidak ada di Kuma** — diferensiasi utama WatchTower

| # | Fitur | Impact |
|---|-------|--------|
| W1 | **Two-Way Chatbot** — query & kontrol monitor via Telegram/WA chat | ⭐⭐⭐⭐⭐ |
| W2 | **Alert Acknowledgement via Chat** — reply "ack" hentikan eskalasi | ⭐⭐⭐⭐⭐ |
| W3 | **Flap Detection** — tahan notif jika monitor UP-DOWN-UP dalam X menit | ⭐⭐⭐⭐⭐ |
| W4 | **Correlated Major Incident** — group 5+ monitor down jadi 1 major incident | ⭐⭐⭐⭐ |
| W5 | **Business Hours Aware Alerting** — routing notif beda jam kerja vs di luar | ⭐⭐⭐⭐⭐ |
| W6 | **Incident Post-Mortem Auto Template** — form RCA otomatis saat insiden tutup | ⭐⭐⭐⭐ |
| W7 | **Service Topology / Dependency Map** — visualisasi grafik semua monitor | ⭐⭐⭐⭐ |
| W8 | **Monitor Template Library** — preset MySQL/Redis/nginx/SatuSehat/BPJS | ⭐⭐⭐⭐ |
| W9 | **Alert Test Simulator** — simulasi DOWN/UP/SLOW tanpa matikan server | ⭐⭐⭐⭐ |
| W10 | **SLA Contract per Layanan** — target SLA + tracking sisa downtime budget | ⭐⭐⭐⭐⭐ |
| W11 | **Shift-Based Alert Routing** — notif otomatis ke petugas yang sedang shift | ⭐⭐⭐⭐⭐ |
| W12 | **SatuSehat/BPJS Endpoint Preset** — monitor khusus parsing response SatuSehat | ⭐⭐⭐⭐⭐ |

---

## 💡 Saran Baru — Belum di List Manapun

| # | Fitur | Keterangan |
|---|-------|-----------|
| N1 | **Synthetic Transaction Monitor** | Script multi-step: login → aksi → logout dengan assertion tiap step. Test alur bisnis bukan hanya koneksi |
| N2 | **Network Topology Auto-Discovery** | Scan subnet, auto-suggest monitor baru dari IP yang ditemukan. Berguna untuk RS dengan ratusan device |
| N3 | **SNMP Monitor** | Cek SNMP OID dari network device (switch, router, UPS, printer). CPU load, disk, suhu via SNMP |
| N4 | **Windows Service / Process Monitor** | Agent ringan di Windows Server — pantau service (IIS, MSSQL, HL7 engine) berjalan atau tidak |
| N5 | **Log File Monitor** | Agent baca log file, alert jika ada pattern error/exception. Tidak perlu ELK stack |
| N6 | **Disk/CPU/RAM Alert via Agent** | Agent ringan (bash/PowerShell) kirim heartbeat + metrics. Alert jika disk >90%, RAM >85% |
| N7 | **Certificate Pinning Check** | Verifikasi bahwa certificate yang disajikan server adalah certificate yang diharapkan (anti-hijack) |
| N8 | **Smoke Test Post-Deploy** | Trigger manual check semua monitor setelah deployment, generate pass/fail report — integrasi CI/CD |
| N9 | **Monitor Grouping by Environment** | Label environment: Production / Staging / Development. Filter dan alert berbeda per env |
| N10 | **Incident Severity Auto-Escalation** | Severity naik otomatis (Low → Medium → High → Critical) jika insiden tidak di-ack dalam X menit |
| N11 | **Response Size Monitor** | Alert jika response body terlalu kecil (halaman kosong/error tersembunyi) atau terlalu besar (memory leak) |
| N12 | **IP Reputation / Blacklist Check** | Cek apakah IP server masuk DNSBL/Spamhaus — penting untuk server email RS |
| N13 | **Bulk Import via CSV** | Upload CSV daftar monitor sekaligus — berguna untuk RS dengan 50+ endpoint |
| N14 | **Monitor Sharing / Embed per Monitor** | Badge + mini widget per monitor individual, bukan hanya per status page |
| N15 | **Telegram Group Summary Command** | Bot auto-post ringkasan harian ke grup Telegram jam 07:00 tanpa perlu cek dashboard |
| N16 | **Alert Sound / Browser Notification** | Sound alert di browser saat ada monitor DOWN (untuk petugas yang buka dashboard) |
| N17 | **Monitor Speed Index** | Ukur First Byte Time (TTFB) terpisah dari total response time — lebih akurat deteksi server slowness vs network |
| N18 | **Webhook Inbound Receiver** | Terima alert dari Grafana/Zabbix/Prometheus via webhook — tampilkan di WatchTower sebagai insiden |
| N19 | **QR Code Status Page** | Generate QR code tiap status page — tempel di ruang server/NOC, scan langsung buka status |
| N20 | **Monitor Score / Health Index** | Score 0–100 per monitor berdasarkan uptime + RT + frekuensi insiden — ranking worst performers |

---

## 📊 Prioritas Implementasi (Rekomendasi Urutan)

### Fase 1 — Foundation (High Value, Mudah)
1. `K8` Accepted Status Codes Kustom
2. `K9` Ignore TLS Error per Monitor
3. `K7` HTTP Method + Request Body
4. `W3` Flap Detection
5. `N9` Monitor Grouping by Environment
6. `1` Uptime Badge SVG

### Fase 2 — Differentiator
7. `W1` Two-Way Chatbot Telegram
8. `W2` Alert Acknowledgement via Chat
9. `W5` Business Hours Aware Alerting
10. `W11` Shift-Based Alert Routing
11. `K4` Database Monitor (MySQL/Redis)
12. `W10` SLA Contract per Layanan

### Fase 3 — Enterprise
13. `17` Multi-User/Role Access
14. `K1` WebSocket Live Update
15. `W4` Correlated Major Incident
16. `W6` Incident Post-Mortem
17. `N6` Disk/CPU/RAM Agent Monitor
18. `N3` SNMP Monitor

### Fase 4 — Ecosystem
19. `W7` Service Topology Map
20. `N1` Synthetic Transaction Monitor
21. `N18` Webhook Inbound Receiver
22. `W12` SatuSehat/BPJS Preset
