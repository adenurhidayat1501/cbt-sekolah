# SADUCI CBT - SMP Negeri Satu Atap 2 Cipatujah

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](license.txt)
[![Security](https://img.shields.io/badge/Security-Hardened%20%26%20Patched-brightgreen.svg)]()
[![UI/UX](https://img.shields.io/badge/UI%2FUX-ANBK%20Standard%202024--2026-blueviolet.svg)]()

Aplikasi **SADUCI CBT (SMP Negeri Satu Atap 2 Cipatujah, Kab. Tasikmalaya)** berbasis Computer Based Test (CBT) yang telah dimodernisasi dengan antarmuka elegan berstandar **ANBK**, identitas sekolah lengkap, patch keamanan menyeluruh, serta skrip otomatisasi instalasi pada server VPS (Ubuntu/Debian) dengan fitur **Pembersihan Project Lama (Clean Slate)** dan **Kustomisasi Database Gampang (.env & Multi-Mode)**.

---

## ✨ Fitur Unggulan

1. **Antarmuka Modern (UI/UX 2024–2026)**
   - **Identitas SADUCI CBT**: Dilengkapi logo resmi SMP Negeri Satu Atap 2 Cipatujah, badge institusi, dan favicon seragam.
   - **ANBK Exam Interface**: Tampilan ujian siswa berstandar Asesmen Nasional dengan kartu pilihan interaktif (A, B, C, D, E), indikator sisa waktu digital, dan dok navigasi responsif.
   - **Glassmorphic Login**: Halaman login modern dengan efek kaca melayang (*frosted glass*), ikon interaktif, dan *show/hide password toggle*.
   - **Deep Slate Dashboard**: Sidebar dan navbar dashboard Admin & Guru bertema *Slate 900* (`#0f172a`), ramah di mata, serta dilengkapi widget statistik modern.
2. **Keamanan Sistem yang Diperketat (*Hardened Security*)**
   - Perlindungan anti-bocor kunci jawaban pada API ujian siswa.
   - Sanitasi upload file dengan whitelist ekstensi ketat & perisai `.htaccess` anti eksekusi PHP pada folder `uploads/` dan `backups/`.
   - Pencegahan celah *path traversal* pada penghapusan berkas database dan foto.
   - Pengamanan installer dan update route dari akses tanpa otentikasi.
   - Mesin *Anti-Cheat Engine* (deteksi blur window / ganti tab 3x toleransi & auto-watermark NISN).
3. **Live Real-Time Exam & Server Monitoring (`cbtlive`)**
   - **Menu Live Monitor Ujian**: Akses melalui menu *PELAKSANAAN -> Live Monitor Ujian* (dengan badge merah berkedip `LIVE`).
   - **Pemilihan Jadwal Hari Ini & Kelas**: Otomatis memprioritaskan jadwal ujian aktif hari ini dan dapat difilter per kelas.
   - **Data Pengawas Ujian**: Menampilkan informasi lengkap guru pengawas (nama & NIP), ruang, dan sesi ujian.
   - **Real-Time Student Progress**: Menampilkan status siswa (Sedang Mengerjakan [Hijau Berkedip], Selesai [Biru], Belum Mulai [Abu-abu], Terkunci/Perlu Reset [Merah]), progres soal terjawab (misal: 25/30 Soal - 83%), sisa waktu ujian, IP address, dan perangkat.
   - **1-Click Quick Actions**: Buka kunci login siswa seketika (1-click Reset Login) dan 1-click Paksa Selesai, serta dukungan aksi massal (Bulk Action).
   - **Monitor Sistem & Resource Server**: Widget CPU load (1m/5m/15m & %), RAM usage (GB & %), MariaDB active threads, live concurrent students counter, sisa kapasitas disk, dan server clock synchronization.
4. **Penyebaran Otomatis di VPS (Clean Slate & Custom Database)**
   - **Pembersihan Project Lama**: Otomatis menghapus project lama di `/var/www` dan me-reset VirtualHost Apache lama sebelum setup agar tidak bentrok.
   - **Kustomisasi Database Gampang**: Mendukung kustomisasi nama DB, user, password, dan port, serta integrasi file `.env` untuk kemudahan perubahan konfigurasi kapan saja.
   - Skrip `install-vps.sh` untuk instalasi mandiri Apache, MariaDB, PHP, ekstensi, permissions, VirtualHost, dan database dalam 1 perintah.
   - Dukungan `docker-compose.yml` untuk instalasi berbasis container.

---

## 🚀 Cara Upload ke GitHub (Dari Komputer Anda)

### Opsi A: Menggunakan Script Otomatis (Windows)
1. Buka folder proyek ini di Windows Explorer.
2. Dobel-klik file **`push-to-github.bat`**.
3. Masukkan URL repository GitHub: `https://github.com/adenurhidayat1501/cbt-sekolah.git`.
4. Script akan menginisialisasi Git, menambahkan berkas, membuat commit, dan mengunggah ke GitHub secara otomatis.

---

### Opsi B: Menggunakan Perintah Git Manual
Buka Command Prompt (CMD), PowerShell, atau Git Bash di dalam folder proyek, lalu jalankan:

```bash
# 1. Inisialisasi Git repository
git init

# 2. Tambahkan semua berkas ke staging
git add .

# 3. Buat commit
git commit -m "SADUCI CBT - Auto-Install Clean Slate & Custom Database Support"

# 4. Atur branch utama ke 'main'
git branch -M main

# 5. Hubungkan dengan remote repository GitHub Anda
git remote add origin https://github.com/adenurhidayat1501/cbt-sekolah.git

# 6. Push source code ke GitHub
git push -u origin main
```

---

## 🛠️ Cara Install di VPS (Paling Gampang, Bersih & Otomatis)

Setelah proyek berada di GitHub, Anda dapat memasangnya di VPS Linux (Ubuntu 20.04 / 22.04 / 24.04 atau Debian 11 / 12) dengan langkah berikut:

### 🌟 Opsi 1: Panduan Interaktif (Bisa Kustom Nama & Password Database)

Login ke VPS via SSH sebagai `root`:

```bash
# 1. Clone repository dari GitHub
git clone https://github.com/adenurhidayat1501/cbt-sekolah.git /var/www/saduci_cbt

# 2. Masuk ke direktori
cd /var/www/saduci_cbt

# 3. Jalankan auto-installer interaktif
sudo bash install-vps.sh
```

> **Menu interaktif akan menanyakan:**
> 1. *Hapus semua project lama di `/var/www` & reset Apache?* (Tekan Enter untuk Ya -> Fresh Clean VPS)
> 2. *Domain atau IP Server*
> 3. *Host Database* (Default: `localhost`)
> 4. *Port Database* (Default: `3306`)
> 5. *Nama Database* (Kustom sesuai keinginan Anda, contoh: `cbt_sekolah` atau `saduci_cbt`)
> 6. *User Database* (Kustom sesuai keinginan Anda)
> 7. *Password Database* (Kustom sesuai keinginan Anda)
> 8. *Metode Setup* (Pilihan 1: Langsung Buat & Siap Pakai, atau Pilihan 2: Web Installer Wizard di browser)

---

### ⚡ Opsi 2: Auto-Install Cepat 1-Klik (Full Automatic)

Jika ingin instalasi langsung beres tanpa pertanyaan apa pun:

```bash
sudo bash install-vps.sh -y
```

Atau jika ingin kustom kredensial database langsung via flag command line:

```bash
sudo bash install-vps.sh --db-name nama_db --db-user user_db --db-pass password_rahasia -y
```

---

### 🧹 Apa yang Dikerjakan Otomatis oleh Skrip?

1. **Pembersihan Proyek Lama (Clean Slate)**:
   - Menghentikan proses web server (Apache/Nginx) yang sedang berjalan untuk menghindari port conflict.
   - Menonaktifkan dan membersihkan VirtualHost Apache lama (`000-default.conf`, `garudacbt.conf`, dll).
   - Menghapus berkas default `/var/www/html/*` dan project lama yang bentrok.
   - Menghapus database lama jika sudah ada agar struktur tabel 100% bersih dan bebas error duplikasi.
2. **Kustomisasi Database Gampang**:
   - Membuat database UTF8MB4 & user MariaDB sesuai nama dan password yang Anda tentukan.
   - Mengimpor skema `master.sql` dan akun administrator default (`admin` / `admin`).
   - Menyimpan konfigurasi ke file `.env` di direktori web root (`/var/www/saduci_cbt/.env`), sehingga Anda bisa mengubah konfigurasi database kapan saja hanya dengan mengedit file `.env` (`nano /var/www/saduci_cbt/.env`).
   - Menyimpan salinan kredensial di file `/var/www/saduci_cbt/db_credentials.txt`.
3. **Instalasi Paket & Dependensi**:
   - Memasang Apache2, MariaDB Server, PHP 7.4 / 8.1, Composer, dan seluruh ekstensi PHP (`mysqli`, `gd`, `zip`, `mbstring`, `xml`, `curl`, `json`, `intl`).
   - Optimasi parameter `php.ini` (`upload_max_filesize = 128M`, `memory_limit = 512M`, `max_input_vars = 10000`).
   - Mengatur hak akses dan permission folder upload & backup (`775` www-data).
   - Pengujian live konektivitas PHP ke MariaDB langsung di terminal.

---

### 🐳 METODE 2: Menggunakan Docker & Docker Compose

Jika VPS Anda sudah terpasang Docker:

```bash
git clone https://github.com/adenurhidayat1501/cbt-sekolah.git saduci_cbt
cd saduci_cbt
docker-compose up -d
```

Aplikasi langsung dapat diakses di `http://IP_SERVER_ANDA/`.

---

## 🔑 Informasi Login Default

Setelah instalasi selesai, Anda dapat langsung login sebagai Administrator:

| Parameter | Detail |
|:---|:---|
| **URL Akses** | `http://IP_SERVER/` atau `https://domain-anda.com/` |
| **Username Admin** | `admin` |
| **Password Admin** | `admin` |

> [!IMPORTANT]
> Segera ganti password default admin setelah login pertama kali melalui menu **Pengaturan > User Management > Administrator**.

---

## ⚙️ Mengubah Konfigurasi Database Nanti (Sangat Mudah)

Karena sudah dilengkapi integrasi `.env`, untuk mengubah database kapan saja di kemudian hari:

```bash
# Buka file .env
sudo nano /var/www/saduci_cbt/.env

# Ubah parameter:
# DB_HOST=localhost
# DB_PORT=3306
# DB_NAME=nama_database_anda
# DB_USER=user_database_anda
# DB_PASS=password_database_anda

# Simpan dengan Ctrl+O lalu Ctrl+X
# Restart Apache
sudo systemctl restart apache2
```

---

## 🔄 Pembaruan Aplikasi di VPS (1-Klik via `update.sh`)

Setiap kali Anda selesai memperbarui kode di GitHub (melalui `push-to-github.bat`), cukup jalankan 1 perintah di terminal VPS:

```bash
cd /var/www/saduci_cbt
sudo bash update.sh
```

> **Apa yang dikerjakan otomatis oleh `update.sh`?**
> - Mengambil commit terbaru dari GitHub (`git reset --hard origin/main`).
> - Menjaga file `.env`, database, dan berkas `uploads/` tetap utuh.
> - Mengatur permission `775 www-data` pada folder penting secara otomatis.
> - Membersihkan cache CodeIgniter.
> - Me-reload Apache secara aman tanpa memutus koneksi siswa yang sedang ujian.

---

## 💾 Pencadangan Otomatis Database (`backup-db.sh`)

Untuk menjaga seluruh bank soal dan nilai siswa tetap aman:

### A. Pencadangan Manual Instan:
```bash
sudo bash /var/www/saduci_cbt/backup-db.sh
```
*Cadangan disimpan dalam format terkompresi `.sql.gz` di folder `/var/www/saduci_cbt/backups/daily/`.*

### B. Pasang Jadwal Backup Otomatis Harian (Setiap 23:00 WIB):
```bash
sudo bash /var/www/saduci_cbt/backup-db.sh --cron
```
*Sistem otomatis membuat backup setiap malam dan menghapus cadangan lama yang berusia lebih dari 7 hari.*

---

## 🛡️ Fitur Integritas Ujian & Anti-Kecurangan Baru

1. **Deteksi Pindah Tab (Toleransi 3x)**:
   - Memonitor jika siswa membuka tab lain, browser lain, atau meminimalkan layar.
   - Peringatan bertingkat interaktif (Pelanggaran ke-1, 2, dan 3). Jika batas 3x terlampaui, halaman ujian otomatis terkunci dan harus dibuka oleh pengawas.
2. **Perisai Layar (*Screen Shield*)**:
   - Blokir klik kanan, text selection, shortcut `Ctrl+C`, `Ctrl+V`, `Ctrl+U`, `F12`, dan `PrintScreen`.
3. **Watermark Dinamis Transparan**:
   - Menampilkan Nama & NISN siswa berulang secara diagonal di belakang teks soal. Memotret layar dengan kamera ponsel lain akan langsung menampakkan identitas siswa.
4. **Ketahanan Offline (*LocalStorage Dual-Save*)**:
   - Setiap jawaban yang diklik siswa disimpan ganda: di server database dan di browser HP siswa.
   - Jika sinyal WiFi sekolah mendadak terputus, jawaban ditampung dan dikirimkan otomatis (*auto-sync*) begitu internet kembali terhubung.

---

## 📁 Struktur Berkas Penting

```
├── .env                   # File konfigurasi database & environment (mudah diubah)
├── db_credentials.txt     # Catatan kredensial database setelah instalasi VPS
├── update.sh              # Skrip 1-klik pembaruan aplikasi di VPS dari GitHub
├── backup-db.sh           # Skrip pencadangan otomatis database harian
├── install-vps.sh         # Script otomatis instalasi VPS (Clean Slate & Custom DB)
├── push-to-github.bat     # Helper upload ke GitHub untuk Windows
├── push-to-github.sh      # Helper upload ke GitHub untuk Linux/macOS
├── application/           # Logika aplikasi CodeIgniter 3 (Controllers, Models, Views)
│   ├── config/            # Konfigurasi database.php, config.php, routes.php
│   └── views/             # Template UI modern (Login, Siswa CBT, AdminLTE)
├── assets/                # Aset statis CSS, JS, Gambar, Font
│   ├── app/css/
│   │   └── modern-cbt.css # Core Design System Modern SADUCI CBT
│   ├── app/db/
│   │   └── master.sql     # Skema awal database & inisialisasi SADUCI CBT
│   └── img/
│       └── saduci_logo.png# Logo resmi SMPN Satu Atap 2 Cipatujah
├── backups/               # Direktori cadangan database (terlindungi .htaccess)
│   └── daily/             # Folder arsip otomatis harian (.sql.gz)
├── uploads/               # Direktori upload media & format template excel
├── Dockerfile             # Definisi container image PHP Apache
├── docker-compose.yml     # Konfigurasi container App + MariaDB
└── .gitignore             # Standar filter berkas Git
```

---

## 🛡️ Catatan Perawatan & Keamanan Server

- **Restart Web Server**: `sudo systemctl restart apache2`
- **Restart Database**: `sudo systemctl restart mariadb`
- **Cek Log Error**: `sudo tail -f /var/log/apache2/saduci_cbt_error.log`
- **Backup Manual**: Jalankan backup database berkala melalui menu admin atau via CLI:
  ```bash
  mysqldump -u saduci_cbt -p saduci_cbt > backup_$(date +%F).sql
  ```
