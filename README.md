# SiCantik Cloud Dashboard

**Dashboard Mandiri Sicantik Cloud** — dashboard web untuk menampilkan dan mengelola data dari API SiCantik melalui SPLP (Sistem Penghubung Layanan Pemerintah).

> Aplikasi pendamping yang dikembangkan secara mandiri. Bukan produk resmi Sicantik Cloud.

## Fitur

### 📊 Dashboard Overview
- Ringkasan statistik: jumlah laporan, koneksi, total record, dan waktu sync terakhir
- Daftar laporan dengan akses cepat ke masing-masing laporan

### 🔌 Koneksi API
- Kelola koneksi ke endpoint API SiCantik (SPLP Gateway)
- Konfigurasi: endpoint UUID, bearer token, API key, salt key, query params
- Test koneksi langsung dari UI
- Auto-detect field dari response API

### 📋 Laporan
- **Report Builder** — buat laporan kustom berbasis koneksi API yang tersedia
- Konfigurasi kolom, filter (text, dropdown, date range), dan chart
- Tampilan tabel data dengan pagination, search, dan sorting
- **Export:** CSV, Excel (.xlsx), PDF — semua tercatat di audit log
- Print langsung dari browser

### 🔄 Sinkronisasi Data
- Sync manual per koneksi atau sync semua sekaligus
- **Auto Sync** — mode interval (15 menit s/d 24 jam) atau harian pada jam tertentu
- Cron job (`cron.php`) untuk eksekusi auto sync
- Log sync dengan status, jumlah record, durasi, dan error message

### 👥 Manajemen User
- Role-based access: **Admin**, **Editor**, **Viewer**
- Kontrol akses per laporan (semua atau pilih laporan tertentu)
- Aktivasi/nonaktifkan user
- Profil self-service (ubah nama dan password sendiri)

### 📝 Audit Log
- Semua aksi tercatat: login, login gagal, CRUD koneksi, CRUD laporan, CRUD user, sync, export (CSV/PDF/Excel), generate API token, dll
- Filter berdasarkan user, aksi, dan tanggal
- Timezone: **Asia/Jakarta (WIB)**

### ⚙️ Pengaturan
- **Identitas** — nama dan subtitle website
- **Urutan Menu** — atur urutan tampilan laporan
- **Kelola Menu** — grup laporan di sidebar
- **Auto Sync** — konfigurasi jadwal sync otomatis
- **API Token** — generate token untuk akses data dari sistem eksternal
- **Info Sistem** — versi PHP, SQLite, ukuran database

### 🔑 Public API
Endpoint REST untuk integrasi dengan sistem eksternal:

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/public.php?action=api_list_reports` | Daftar semua laporan |
| `GET` | `/api/public.php?action=api_report_data&report_id={id}` | Data laporan |

Autentikasi: `Authorization: Bearer {token}` — token di-generate dari halaman Pengaturan.

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP (native, tanpa framework) |
| Database | SQLite |
| Frontend | Tailwind CSS, Vanilla JS |
| Export | jsPDF + AutoTable, SheetJS (XLSX) |
| API Source | [SPLP Gateway](https://api-splp.layanan.go.id) (SiCantik Interop) |
| Proxy | Cloudflare |

## Struktur Direktori

```
├── index.php              # Entry point + routing
├── core.php               # Fungsi inti (DB, auth, sync, audit log)
├── config.php             # Konfigurasi (APP_TITLE, DB_FILE, BASE_API_URL)
├── cron.php               # Auto sync cron job
├── .htaccess              # URL rewrite rules
├── api/
│   ├── handler.php        # API internal (authenticated actions)
│   └── public.php         # API publik (token-based)
├── assets/
│   ├── app.js             # Frontend logic
│   └── app.css            # Custom styles
├── pages/
│   ├── home.php           # Dashboard overview
│   ├── login.php          # Halaman login
│   ├── connections.php    # Kelola koneksi API
│   ├── reports.php        # Daftar laporan
│   ├── view.php           # Tampilan data laporan
│   ├── builder.php        # Report builder
│   ├── sync.php           # Sinkronisasi data
│   ├── users.php          # Manajemen user
│   ├── audit.php          # Log aktivitas
│   ├── settings.php       # Pengaturan sistem
│   └── profile.php        # Profil user
└── templates/
    └── builder_steps.php  # Wizard steps untuk report builder
```

## Setup

### Prasyarat
- PHP 8.0+ dengan ekstensi `sqlite3`, `curl`, `openssl`
- Web server (Nginx/Apache) dengan PHP-FPM

### Instalasi

```bash
# Clone repository
git clone git@github.com:mnoor-project/sicantik-cloud-dashboard.git /var/www/sicantik-dashboard

# Pastikan writable untuk SQLite
chmod 755 /var/www/sicantik-dashboard
```

Database SQLite (`data.sqlite`) akan dibuat otomatis saat pertama kali diakses.

### Login Pertama

Saat database dibuat, akun `admin` dibuat dengan **password acak**. Password tersebut disimpan di file `.initial_admin_password` di folder aplikasi (file tersembunyi, izin `0600`, tidak ikut di-commit).

```bash
cat /var/www/sicantik-dashboard/.initial_admin_password
```

Segera login, ganti password di menu **Profil**, lalu hapus file tersebut.

### Keamanan Web Server

Pastikan web server memblokir akses langsung ke file sensitif (database, log, dan file berawalan titik). Pada Apache sudah ditangani `.htaccess`. Pada **Nginx**, `.htaccess` tidak berlaku, tambahkan pada blok `server`:

```nginx
location ~ /\.                          { deny all; }
location ~* \.(sqlite|sqlite-wal|sqlite-shm|db)$ { deny all; }
location ~* \.log$                      { deny all; }
```

### Cron Job (Auto Sync)

```bash
# Jalankan setiap 5 menit
*/5 * * * * /usr/bin/php /var/www/sicantik-dashboard/cron.php >> /var/log/sicantik-cron.log 2>&1
```

## Demo

Coba langsung: **https://scdemo.ptspkotim.my.id/**

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin` |
| Editor | `editor` | `editor` |
| Viewer | `viewer` | `viewer` |

> Instance demo berisi **data fiktif** dan direset otomatis setiap 6 jam, jadi perubahan yang Anda buat akan hilang. Akun demo bersifat publik: jangan memasukkan data atau kredensial asli. Jika memasang aplikasi ini sendiri, gunakan password acak dari `.initial_admin_password`.

<!-- Bagian donasi disembunyikan dari tampilan GitHub tetapi tetap ada di berkas ini dan di aplikasi.
     Hapus baris pembuka dan penutup komentar ini untuk menampilkannya lagi. -->
<!--
## Dukung Pengembangan ❤️

Aplikasi ini dikembangkan dan dirawat secara mandiri, gratis, dan dijalankan di server masing-masing instansi (tanpa biaya langganan). Jika bermanfaat, dukungan sukarela Anda membantu waktu dan tenaga untuk perbaikan, pengembangan fitur baru, dan dokumentasi.

| | |
|---|---|
| 🏦 Bank Jago | `100891675874` a.n. Muhammad Noor |
| 💬 WhatsApp | [085752735703](https://wa.me/6285752735703) |
| 🌐 Website | [muhammadnoor.com](https://muhammadnoor.com) |

Donasi bersifat sukarela dan tidak wajib. Seluruh fitur tetap dapat digunakan sepenuhnya tanpa donasi. Dukungan bersifat pribadi kepada pengembang dan tidak berkaitan dengan layanan, pungutan, atau kewenangan instansi mana pun. Halaman yang sama tersedia di dalam aplikasi pada `?page=donate`.
-->

## Lisensi

[MIT](LICENSE) © 2026 Muhammad Noor
