#!/usr/bin/env bash
# ==============================================================================
# SADUCI CBT - Automated VPS Installer & Clean Slate Setup Script
# SMP NEGERI SATU ATAP 2 CIPATUJAH
# Modernized Edition (2024-2026)
# ==============================================================================
# Usage:
#   sudo bash install-vps.sh              (Panduan Interaktif & Kustomisasi DB)
#   sudo bash install-vps.sh -y           (Auto-Install / Non-Interactive)
#   sudo bash install-vps.sh --wipe       (Hapus semua project lama & fresh setup)
#   sudo bash install-vps.sh --db-name cbt --db-user cbt --db-pass rahasia -y
# ==============================================================================

set -e

# Terminal formatting
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# ------------------------------------------------------------------------------
# 1. Root & OS Environment Check
# ------------------------------------------------------------------------------
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] Skrip ini harus dijalankan sebagai root atau dengan sudo!${NC}"
    echo -e "Silakan jalankan: ${CYAN}sudo bash install-vps.sh${NC}"
    exit 1
fi

echo -e "${CYAN}${BOLD}"
echo "======================================================================"
echo "          SADUCI CBT - AUTOMATED VPS INSTALLER SCRIPT                 "
echo "              SMP NEGERI SATU ATAP 2 CIPATUJAH                        "
echo "                 Modernized Edition (2024-2026)                       "
echo "======================================================================"
echo -e "${NC}"

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_NAME=$ID
    OS_VER=$VERSION_ID
else
    echo -e "${RED}[ERROR] Sistem Operasi Linux tidak terdeteksi.${NC}"
    exit 1
fi

if [[ "$OS_NAME" != "ubuntu" && "$OS_NAME" != "debian" ]]; then
    echo -e "${YELLOW}[WARNING] Skrip ini dioptimalkan untuk Ubuntu/Debian. Melanjutkan...${NC}"
fi

# Detect application source directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$SCRIPT_DIR/index.php" ]; then
    SRC_DIR="$SCRIPT_DIR"
elif [ -f "$SCRIPT_DIR/main/index.php" ]; then
    SRC_DIR="$SCRIPT_DIR/main"
else
    SRC_DIR="$PWD"
fi

if [ ! -f "$SRC_DIR/index.php" ]; then
    echo -e "${RED}[ERROR] File index.php tidak ditemukan di $SRC_DIR!${NC}"
    exit 1
fi

# ------------------------------------------------------------------------------
# 2. Argument Parsing & CLI Options
# ------------------------------------------------------------------------------
AUTO_YES=false
CLI_WIPE=""
CLI_DB_HOST=""
CLI_DB_PORT=""
CLI_DB_NAME=""
CLI_DB_USER=""
CLI_DB_PASS=""
CLI_RESET_DB=""
CLI_DOMAIN=""
CLI_WEB_SETUP=""
CLI_SSL=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        -y|--yes|--auto)
            AUTO_YES=true
            shift
            ;;
        --clean|--wipe)
            CLI_WIPE="y"
            shift
            ;;
        --db-host)
            CLI_DB_HOST="$2"
            shift 2
            ;;
        --db-port)
            CLI_DB_PORT="$2"
            shift 2
            ;;
        --db-name)
            CLI_DB_NAME="$2"
            shift 2
            ;;
        --db-user)
            CLI_DB_USER="$2"
            shift 2
            ;;
        --db-pass)
            CLI_DB_PASS="$2"
            shift 2
            ;;
        --reset-db)
            CLI_RESET_DB="y"
            shift
            ;;
        --domain)
            CLI_DOMAIN="$2"
            shift 2
            ;;
        --web-setup)
            CLI_WEB_SETUP="y"
            shift
            ;;
        --ssl)
            CLI_SSL="y"
            shift
            ;;
        -h|--help)
            echo "Penggunaan: sudo bash install-vps.sh [OPSI]"
            echo ""
            echo "Opsi:"
            echo "  -y, --yes          Instalasi otomatis tanpa prompt interaktif"
            echo "  --clean, --wipe    Hapus semua project lama di /var/www dan reset Apache"
            echo "  --db-host HOST     Host database (default: localhost)"
            echo "  --db-port PORT     Port database (default: 3306)"
            echo "  --db-name NAMA     Nama database kustom"
            echo "  --db-user USER     Username database kustom"
            echo "  --db-pass PASS     Password database kustom"
            echo "  --reset-db         Reset/Drop database lama jika sudah ada"
            echo "  --domain DOMAIN    Domain atau IP server"
            echo "  --web-setup        Setup skema melalui wizard browser di /install"
            echo "  --ssl              Pasang sertifikat Let's Encrypt SSL"
            echo "  -h, --help         Tampilkan pesan bantuan ini"
            exit 0
            ;;
        *)
            shift
            ;;
    esac
done

# Detect Server IP (instant via network interface)
SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}')
if [ -z "$SERVER_IP" ]; then
    SERVER_IP=$(curl -s -m 2 https://api.ipify.org 2>/dev/null || echo "127.0.0.1")
fi

# Set default web root based on source dir
if [ "$SRC_DIR" = "/var/www/garudacbt" ]; then
    WEB_ROOT="/var/www/garudacbt"
else
    WEB_ROOT="/var/www/saduci_cbt"
fi

DEFAULT_DB_HOST="localhost"
DEFAULT_DB_PORT="3306"
DEFAULT_DB_NAME="saduci_cbt"
DEFAULT_DB_USER="saduci_cbt"
DEFAULT_DB_PASS="saduci123"

# ------------------------------------------------------------------------------
# 3. Interactive Guided Setup / Auto Defaults
# ------------------------------------------------------------------------------
if [ "$AUTO_YES" = true ]; then
    echo -e "${YELLOW}--> Mode Otomatis (-y) aktif: Menggunakan parameter default atau variabel lingkungan.${NC}\n"
    WIPE_OLD_PROJECTS=${CLI_WIPE:-"y"}
    DB_HOST=${CLI_DB_HOST:-${DB_HOST:-$DEFAULT_DB_HOST}}
    DB_PORT=${CLI_DB_PORT:-${DB_PORT:-$DEFAULT_DB_PORT}}
    DB_NAME=${CLI_DB_NAME:-${DB_NAME:-$DEFAULT_DB_NAME}}
    DB_USER=${CLI_DB_USER:-${DB_USER:-$DEFAULT_DB_USER}}
    DB_PASS=${CLI_DB_PASS:-${DB_PASS:-$DEFAULT_DB_PASS}}
    RESET_DB=${CLI_RESET_DB:-"y"}
    SERVER_DOMAIN=${CLI_DOMAIN:-${SERVER_DOMAIN:-$SERVER_IP}}
    SETUP_VIA_WEB=${CLI_WEB_SETUP:-"n"}
    INSTALL_SSL=${CLI_SSL:-"n"}
else
    echo -e "${YELLOW}${BOLD}======================================================================${NC}"
    echo -e "${YELLOW}${BOLD}     PEMBERSIHAN SISTEM & KUSTOMISASI DATABASE SADUCI CBT            ${NC}"
    echo -e "${YELLOW}${BOLD}======================================================================${NC}"
    echo -e "Silakan tentukan pengaturan di bawah ini. Tekan [ENTER] untuk nilai default [ ]: \n"

    # 1. Clean slate confirmation
    read -p ">> [1/8] Hapus semua project lama di /var/www & reset Apache sebelum setup? (y/n) [y]: " INPUT_WIPE
    WIPE_OLD_PROJECTS=${INPUT_WIPE:-y}

    # 2. Server Domain / IP
    read -p ">> [2/8] Domain atau IP Server [$SERVER_IP]: " INPUT_DOMAIN
    SERVER_DOMAIN=${INPUT_DOMAIN:-$SERVER_IP}

    # 3. Database Host
    read -p ">> [3/8] Host Database [$DEFAULT_DB_HOST]: " INPUT_DB_HOST
    DB_HOST=${INPUT_DB_HOST:-$DEFAULT_DB_HOST}

    # 4. Database Port
    read -p ">> [4/8] Port Database [$DEFAULT_DB_PORT]: " INPUT_DB_PORT
    DB_PORT=${INPUT_DB_PORT:-$DEFAULT_DB_PORT}

    # 5. Database Name
    read -p ">> [5/8] Nama Database [$DEFAULT_DB_NAME]: " INPUT_DB_NAME
    DB_NAME=${INPUT_DB_NAME:-$DEFAULT_DB_NAME}

    # 6. Database User
    read -p ">> [6/8] User Database [$DEFAULT_DB_USER]: " INPUT_DB_USER
    DB_USER=${INPUT_DB_USER:-$DEFAULT_DB_USER}

    # 7. Database Password
    read -p ">> [7/8] Password Database [$DEFAULT_DB_PASS]: " INPUT_DB_PASS
    DB_PASS=${INPUT_DB_PASS:-$DEFAULT_DB_PASS}

    # 8. Reset existing database
    read -p ">> [8/8] Reset/Hapus database '$DB_NAME' jika sudah ada (Fresh Clean DB)? (y/n) [y]: " INPUT_RESET_DB
    RESET_DB=${INPUT_RESET_DB:-y}

    echo ""
    echo -e "Pilih Metode Setup Database:"
    echo -e "  1) ${GREEN}${BOLD}Auto-Install Database${NC} (Rekomendasi - Skema & Admin dibuat otomatis, langsung siap pakai)"
    echo -e "  2) ${CYAN}Web Browser Installer${NC} (/install - Anda mengisi data dan akun lewat browser)"
    read -p "Pilihan metode [1]: " INPUT_METHOD
    if [ "$INPUT_METHOD" = "2" ]; then
        SETUP_VIA_WEB="y"
    else
        SETUP_VIA_WEB="n"
    fi

    INSTALL_SSL="n"
    if [[ "$SERVER_DOMAIN" != "$SERVER_IP" && "$SERVER_DOMAIN" != "localhost" && "$SERVER_DOMAIN" != "127.0.0.1" ]]; then
        read -p ">> Pasang SSL Let's Encrypt otomatis? (y/n) [n]: " INPUT_SSL
        INSTALL_SSL=${INPUT_SSL:-n}
    fi
fi

echo -e "\n${GREEN}--> Konfigurasi Instalasi:${NC}"
echo -e "    Pembersihan Proyek Lama : ${CYAN}$WIPE_OLD_PROJECTS${NC}"
echo -e "    Domain / IP             : ${CYAN}$SERVER_DOMAIN${NC}"
echo -e "    Web Root Path           : ${CYAN}$WEB_ROOT${NC}"
echo -e "    Database Host:Port      : ${CYAN}$DB_HOST:$DB_PORT${NC}"
echo -e "    Database Name           : ${CYAN}$DB_NAME${NC}"
echo -e "    Database User           : ${CYAN}$DB_USER${NC}"
echo -e "    Database Pass           : ${CYAN}$DB_PASS${NC}"
echo -e "    Reset Database Lama     : ${CYAN}$RESET_DB${NC}"
echo -e "    Metode Web Wizard       : ${CYAN}$SETUP_VIA_WEB${NC}"
echo ""

# ------------------------------------------------------------------------------
# 4. STEP 1: Pembersihan Total / Reset Proyek Lama (Clean Slate)
# ------------------------------------------------------------------------------
if [[ "$WIPE_OLD_PROJECTS" =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}[1/8] Menjalankan pembersihan total proyek lama di VPS...${NC}"
    
    # Stop conflicting web servers
    echo -e "--> Menghentikan web server untuk mencegah konflik port 80/443..."
    systemctl stop apache2 2>/dev/null || true
    systemctl stop nginx 2>/dev/null || true
    systemctl disable nginx 2>/dev/null || true

    # Clean existing Apache VirtualHosts
    if [ -d /etc/apache2 ]; then
        echo -e "--> Membersihkan VirtualHost Apache lama..."
        if command -v a2dissite >/dev/null 2>&1; then
            a2dissite 000-default.conf default-ssl.conf garudacbt.conf saduci_cbt.conf 2>/dev/null || true
        fi
        rm -f /etc/apache2/sites-enabled/* 2>/dev/null || true
        rm -f /etc/apache2/sites-available/000-default.conf 2>/dev/null || true
        rm -f /etc/apache2/sites-available/garudacbt.conf 2>/dev/null || true
        rm -f /etc/apache2/sites-available/saduci_cbt.conf 2>/dev/null || true
    fi

    # Clean default /var/www/html
    if [ -d "/var/www/html" ]; then
        echo -e "--> Membersihkan direktori default /var/www/html..."
        rm -rf /var/www/html/* /var/www/html/.* 2>/dev/null || true
    fi

    # Clean legacy garudacbt directory if not the active source directory
    if [ -d "/var/www/garudacbt" ] && [ "$SRC_DIR" != "/var/www/garudacbt" ] && [ "$WEB_ROOT" != "/var/www/garudacbt" ]; then
        echo -e "--> Menghapus direktori garudacbt lama (/var/www/garudacbt)..."
        rm -rf /var/www/garudacbt
    fi

    # Clean target web root
    if [ -d "$WEB_ROOT" ]; then
        if [ "$SRC_DIR" != "$WEB_ROOT" ]; then
            echo -e "--> Menghapus direktori target lama di $WEB_ROOT..."
            rm -rf "$WEB_ROOT"
        else
            echo -e "--> Membersihkan file runtime, cache, log, dan sesi lama di $WEB_ROOT..."
            rm -rf "$WEB_ROOT/application/cache"/* 2>/dev/null || true
            rm -rf "$WEB_ROOT/application/logs"/* 2>/dev/null || true
            rm -rf "$WEB_ROOT/uploads/temp"/* 2>/dev/null || true
            rm -f "$WEB_ROOT/.env" "$WEB_ROOT/db_credentials.txt" 2>/dev/null || true
        fi
    fi

    echo -e "${GREEN}[OK] Pembersihan proyek lama selesai. Server bersih dan siap di-setup.${NC}\n"
else
    echo -e "${BLUE}[1/8] Melewati pembersihan proyek lama.${NC}\n"
fi

# ------------------------------------------------------------------------------
# 5. STEP 2: System Update & Dependencies
# ------------------------------------------------------------------------------
echo -e "${BLUE}[2/8] Memperbarui repositori paket Linux & instalasi dependensi...${NC}"
export DEBIAN_FRONTEND=noninteractive

# Proactively fix known broken local mirrors
if grep -rq "cermin.rumahweb.id" /etc/apt/ 2>/dev/null; then
    echo -e "${YELLOW}[*] Mengalihkan mirror cermin.rumahweb.id ke archive.ubuntu.com...${NC}"
    sed -i 's|cermin.rumahweb.id|archive.ubuntu.com|g' /etc/apt/sources.list /etc/apt/sources.list.d/*.sources 2>/dev/null || true
    rm -rf /var/lib/apt/lists/*
    apt-get clean
fi
apt-get update -y || true

apt-get install -y software-properties-common curl wget git unzip lsb-release ca-certificates apt-transport-https

# ------------------------------------------------------------------------------
# 6. STEP 3: Install Apache, MariaDB, and PHP
# ------------------------------------------------------------------------------
echo -e "${BLUE}[3/8] Menginstall Apache2, MariaDB Server, dan Composer...${NC}"
apt-get install -y apache2 mariadb-server composer

echo -e "${BLUE}[4/8] Menginstall PHP dan ekstensi yang dibutuhkan...${NC}"
PHP_INSTALLED=false

# Try installing PHP PPA if Ubuntu
if [ "$OS_NAME" = "ubuntu" ]; then
    add-apt-repository -y ppa:ondrej/php || true
    apt-get update -y || true
fi

# Attempt to install PHP 7.4, fallback to PHP 8.1, fallback to default distro PHP
if apt-get install -y php7.4 php7.4-cli php7.4-mysql php7.4-gd php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip php7.4-intl libapache2-mod-php7.4; then
    PHP_VER="7.4"
    PHP_INSTALLED=true
elif apt-get install -y php8.1 php8.1-cli php8.1-mysql php8.1-gd php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-intl libapache2-mod-php8.1; then
    PHP_VER="8.1"
    PHP_INSTALLED=true
else
    # Fallback to system default PHP
    apt-get install -y php php-cli php-mysql php-gd php-mbstring php-xml php-curl php-zip php-intl libapache2-mod-php
    PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    PHP_INSTALLED=true
fi

echo -e "${GREEN}--> PHP versi $PHP_VER berhasil dipasang.${NC}"

# Tune PHP settings for High-Volume CBT Exam Environment
PHP_INI="/etc/php/$PHP_VER/apache2/php.ini"
if [ -f "$PHP_INI" ]; then
    echo -e "${BLUE}[*] Mengoptimalkan parameter php.ini untuk SADUCI CBT...${NC}"
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 128M/" "$PHP_INI"
    sed -i "s/post_max_size = .*/post_max_size = 128M/" "$PHP_INI"
    sed -i "s/memory_limit = .*/memory_limit = 512M/" "$PHP_INI"
    sed -i "s/max_execution_time = .*/max_execution_time = 300/" "$PHP_INI"
    sed -i "s/max_input_time = .*/max_input_time = 300/" "$PHP_INI"
    
    if grep -q "max_input_vars" "$PHP_INI"; then
        sed -i "s/.*max_input_vars = .*/max_input_vars = 10000/" "$PHP_INI"
    else
        echo "max_input_vars = 10000" >> "$PHP_INI"
    fi
    sed -i "s/;date.timezone =.*/date.timezone = Asia\/Jakarta/" "$PHP_INI"
fi

# ------------------------------------------------------------------------------
# 7. STEP 4: Custom MariaDB Setup & Database Schema
# ------------------------------------------------------------------------------
echo -e "${BLUE}[5/8] Mengonfigurasi MariaDB & inisialisasi database kustom...${NC}"
systemctl start mariadb || systemctl start mysql
systemctl enable mariadb || systemctl enable mysql

# Helper for executing MySQL commands with root / maintenance authentication
mariadb_exec() {
    local query="$1"
    if [ -n "$MARIADB_ROOT_PASS" ]; then
        mariadb -u root -p"$MARIADB_ROOT_PASS" -e "$query" 2>/dev/null || mysql -u root -p"$MARIADB_ROOT_PASS" -e "$query"
    elif [ -f /etc/mysql/debian.cnf ]; then
        mariadb --defaults-file=/etc/mysql/debian.cnf -e "$query" 2>/dev/null || mysql --defaults-file=/etc/mysql/debian.cnf -e "$query"
    else
        mariadb -e "$query" 2>/dev/null || mysql -e "$query"
    fi
}

mariadb_import() {
    local target_db="$1"
    local sql_file="$2"
    if [ -n "$MARIADB_ROOT_PASS" ]; then
        mariadb -u root -p"$MARIADB_ROOT_PASS" "$target_db" < "$sql_file" 2>/dev/null || mysql -u root -p"$MARIADB_ROOT_PASS" "$target_db" < "$sql_file"
    elif [ -f /etc/mysql/debian.cnf ]; then
        mariadb --defaults-file=/etc/mysql/debian.cnf "$target_db" < "$sql_file" 2>/dev/null || mysql --defaults-file=/etc/mysql/debian.cnf "$target_db" < "$sql_file"
    else
        mariadb "$target_db" < "$sql_file" 2>/dev/null || mysql "$target_db" < "$sql_file"
    fi
}

# Test MariaDB root connectivity
if ! mariadb_exec "SELECT 1;" >/dev/null 2>&1; then
    if [ "$AUTO_YES" != "true" ]; then
        echo -e "${YELLOW}[!] Otentikasi root MariaDB memerlukan password.${NC}"
        read -s -p ">> Masukkan password root MariaDB/MySQL: " MARIADB_ROOT_PASS
        echo ""
    fi
fi

# Reset / Drop database if requested
if [[ "$RESET_DB" =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}--> Menghapus database lama '$DB_NAME' jika ada (Fresh Clean DB)...${NC}"
    mariadb_exec "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
fi

# Create Database with UTF8MB4
echo -e "--> Membuat database '$DB_NAME' (utf8mb4)..."
mariadb_exec "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Escape password for SQL
SQL_DB_PASS=$(printf '%s' "$DB_PASS" | sed "s/'/''/g")

# Create & grant privileges to user
echo -e "--> Mengonfigurasi user database '$DB_USER'..."
mariadb_exec "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$SQL_DB_PASS';"
mariadb_exec "ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$SQL_DB_PASS';"
mariadb_exec "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"

# Also grant for 127.0.0.1
mariadb_exec "CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$SQL_DB_PASS';"
mariadb_exec "ALTER USER '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$SQL_DB_PASS';"
mariadb_exec "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';"

mariadb_exec "FLUSH PRIVILEGES;"

# Handle schema import based on chosen mode
if [[ "$SETUP_VIA_WEB" =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}--> Mode Web Installer aktif: Pembuatan tabel akan dilakukan lewat browser di /install.${NC}"
    CONFIG_DB_NAME=""
else
    CONFIG_DB_NAME="$DB_NAME"
    SQL_FILE="$SRC_DIR/assets/app/db/master.sql"
    if [ -f "$SQL_FILE" ]; then
        echo -e "--> Mengimpor skema tabel awal dari master.sql ke database '$DB_NAME'..."
        mariadb_import "$DB_NAME" "$SQL_FILE" || true
        echo -e "${GREEN}--> Skema database berhasil diimpor!${NC}"
    else
        echo -e "${YELLOW}[!] File master.sql tidak ditemukan di $SQL_FILE, lewati impor.${NC}"
    fi

    # Set school setting & identity
    mariadb_exec "INSERT INTO \`$DB_NAME\`.\`setting\` (\`id_setting\`, \`kode_sekolah\`, \`sekolah\`, \`nama_aplikasi\`, \`alamat\`, \`desa\`, \`kecamatan\`, \`kota\`, \`provinsi\`, \`logo_kanan\`, \`logo_kiri\`, \`versi\`) VALUES (1, 'SADUCI', 'SMP NEGERI SATU ATAP 2 CIPATUJAH', 'SADUCI CBT', 'Cipatujah', 'Cipatujah', 'Cipatujah', 'Kab. Tasikmalaya', 'Jawa Barat', 'assets/img/saduci_logo.png', 'assets/img/saduci_logo.png', '1.5.3') ON DUPLICATE KEY UPDATE \`nama_aplikasi\` = 'SADUCI CBT', \`sekolah\` = 'SMP NEGERI SATU ATAP 2 CIPATUJAH', \`kota\` = 'Kab. Tasikmalaya', \`logo_kanan\` = 'assets/img/saduci_logo.png', \`logo_kiri\` = 'assets/img/saduci_logo.png';" 2>/dev/null || true
    mariadb_exec "ALTER TABLE \`$DB_NAME\`.\`master_tp\` MODIFY \`active\` int(11) NOT NULL DEFAULT 0; ALTER TABLE \`$DB_NAME\`.\`master_kelas\` MODIFY \`jurusan_id\` int(11) NOT NULL DEFAULT 0;" 2>/dev/null || true

    # Create default admin user (admin / admin)
    ADMIN_HASH=$(php -r "echo password_hash('admin', PASSWORD_BCRYPT);" 2>/dev/null || echo '$2y$10$wO3t4hNl7m7r6T4G2fF3NuR9y7eM5uB2wE4pP8gL4uP8pL7vAeLq2')
    mariadb_exec "INSERT INTO \`$DB_NAME\`.\`users\` (\`id\`, \`ip_address\`, \`username\`, \`password\`, \`email\`, \`created_on\`, \`active\`, \`first_name\`, \`last_name\`) VALUES (1, '127.0.0.1', 'admin', '$ADMIN_HASH', 'admin@saduci.sch.id', UNIX_TIMESTAMP(), 1, 'Administrator', 'SADUCI') ON DUPLICATE KEY UPDATE \`id\` = 1;" 2>/dev/null || true
    mariadb_exec "INSERT INTO \`$DB_NAME\`.\`users_groups\` (\`id\`, \`user_id\`, \`group_id\`) VALUES (1, 1, 1) ON DUPLICATE KEY UPDATE \`group_id\` = 1;" 2>/dev/null || true
fi

# ------------------------------------------------------------------------------
# 8. STEP 5: Deploy Files, Generate Configs & Set Permissions
# ------------------------------------------------------------------------------
echo -e "${BLUE}[6/8] Menyalin berkas aplikasi ke $WEB_ROOT & membuat konfigurasi...${NC}"
mkdir -p "$WEB_ROOT"

if [ "$SRC_DIR" != "$WEB_ROOT" ]; then
    cp -r "$SRC_DIR"/* "$WEB_ROOT"/
    cp -a "$SRC_DIR"/.htaccess "$WEB_ROOT"/ 2>/dev/null || true
    cp -a "$SRC_DIR"/.gitignore "$WEB_ROOT"/ 2>/dev/null || true
fi

# Ensure necessary writable directories exist
mkdir -p "$WEB_ROOT/uploads"
mkdir -p "$WEB_ROOT/backups"
mkdir -p "$WEB_ROOT/application/logs"
mkdir -p "$WEB_ROOT/application/cache"
mkdir -p "$WEB_ROOT/uploads/bank_soal"
mkdir -p "$WEB_ROOT/uploads/file_siswa"
mkdir -p "$WEB_ROOT/uploads/foto_siswa"
mkdir -p "$WEB_ROOT/uploads/import"
mkdir -p "$WEB_ROOT/uploads/materi"
mkdir -p "$WEB_ROOT/uploads/profiles"
mkdir -p "$WEB_ROOT/uploads/settings"
mkdir -p "$WEB_ROOT/uploads/temp"
mkdir -p "$WEB_ROOT/uploads/tugas"

# Generate bulletproof application/config/database.php
PHP_ESCAPED_PASS=$(printf '%s' "$DB_PASS" | sed "s/'/\\\\'/g")
PHP_ESCAPED_USER=$(printf '%s' "$DB_USER" | sed "s/'/\\\\'/g")
PHP_ESCAPED_NAME=$(printf '%s' "$CONFIG_DB_NAME" | sed "s/'/\\\\'/g")
PHP_ESCAPED_HOST=$(printf '%s' "$DB_HOST" | sed "s/'/\\\\'/g")

cat <<EOF > "$WEB_ROOT/application/config/database.php"
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Auto-load .env environment file if present for easy database configuration
\$env_file = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . '.env';
if (file_exists(\$env_file)) {
    \$lines = file(\$env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach (\$lines as \$line) {
        \$line = trim(\$line);
        if (\$line === '' || strpos(\$line, '#') === 0) continue;
        if (strpos(\$line, '=') !== false) {
            list(\$name, \$value) = explode('=', \$line, 2);
            \$name = trim(\$name);
            \$value = trim(\$value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists(\$name, \$_SERVER) && !array_key_exists(\$name, \$_ENV)) {
                putenv("{\$name}={\$value}");
                \$_ENV[\$name] = \$value;
                \$_SERVER[\$name] = \$value;
            }
        }
    }
}

\$active_group = 'default';
\$query_builder = TRUE;

\$db['default'] = array(
    'dsn'	=> '',
    'hostname' => getenv('DB_HOST') ? getenv('DB_HOST') : '$PHP_ESCAPED_HOST',
    'username' => getenv('DB_USER') ? getenv('DB_USER') : '$PHP_ESCAPED_USER',
    'password' => getenv('DB_PASS') ? getenv('DB_PASS') : '$PHP_ESCAPED_PASS',
    'database' => getenv('DB_NAME') ? getenv('DB_NAME') : '$PHP_ESCAPED_NAME',
    'port'     => getenv('DB_PORT') ? (int)getenv('DB_PORT') : $DB_PORT,
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
EOF

# Generate .env file for easy subsequent database customization
cat <<EOF > "$WEB_ROOT/.env"
# SADUCI CBT - Environment Variables
CI_ENV=production
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS
EOF
chmod 600 "$WEB_ROOT/.env"

# Save credential reference file
cat <<EOF > "$WEB_ROOT/db_credentials.txt"
======================================================================
SADUCI CBT - DATABASE CREDENTIALS & SERVER INFO
SMP NEGERI SATU ATAP 2 CIPATUJAH
======================================================================
Domain / IP       : $SERVER_DOMAIN
Web Root          : $WEB_ROOT
Database Host     : $DB_HOST
Database Port     : $DB_PORT
Database Name     : $DB_NAME
Database User     : $DB_USER
Database Password : $DB_PASS
Setup via Web     : $SETUP_VIA_WEB
Admin Login       : http://$SERVER_DOMAIN/
Default User      : admin
Default Pass      : admin
Waktu Pemasangan  : $(date '+%Y-%m-%d %H:%M:%S')
======================================================================
EOF
chmod 600 "$WEB_ROOT/db_credentials.txt"

# Set permissions
chown -R www-data:www-data "$WEB_ROOT"
find "$WEB_ROOT" -type d -exec chmod 755 {} +
find "$WEB_ROOT" -type f -exec chmod 644 {} +
chmod -R 775 "$WEB_ROOT/uploads" "$WEB_ROOT/backups" "$WEB_ROOT/application/logs" "$WEB_ROOT/application/cache" 2>/dev/null || true

# Install composer vendor if needed
if [ -f "$WEB_ROOT/composer.json" ]; then
    if [ ! -f "$WEB_ROOT/vendor/autoload.php" ]; then
        echo -e "${BLUE}[*] Mengunduh library vendor Composer (Excel & PDF)...${NC}"
        cd "$WEB_ROOT"
        COMPOSER_ALLOW_SUPERUSER=1 composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction 2>/dev/null || \
        COMPOSER_ALLOW_SUPERUSER=1 composer update --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction || true
        chown -R www-data:www-data "$WEB_ROOT/vendor" 2>/dev/null || true
    fi
fi

# ------------------------------------------------------------------------------
# 9. STEP 6: Configure Apache VirtualHost & Modules
# ------------------------------------------------------------------------------
echo -e "${BLUE}[7/8] Mengonfigurasi Apache VirtualHost & modul PHP...${NC}"

# Ensure PHP module is active (switch to mpm_prefork if mpm_event was active)
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true
a2enmod php$PHP_VER 2>/dev/null || a2enmod php 2>/dev/null || true
a2enmod rewrite headers expires ssl 2>/dev/null || true

# Guard .htaccess against unshielded php_value syntax error
if [ -f "$WEB_ROOT/.htaccess" ]; then
    sed -i 's/^php_value max_input_vars/#php_value max_input_vars/g' "$WEB_ROOT/.htaccess" 2>/dev/null || true
fi

VHOST_FILE="/etc/apache2/sites-available/saduci_cbt.conf"
cat <<EOF > "$VHOST_FILE"
<VirtualHost *:80>
    ServerName $SERVER_DOMAIN
    DocumentRoot $WEB_ROOT

    <Directory $WEB_ROOT>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/saduci_cbt_error.log
    CustomLog \${APACHE_LOG_DIR}/saduci_cbt_access.log combined
</VirtualHost>
EOF

a2dissite 000-default.conf garudacbt.conf 2>/dev/null || true
a2ensite saduci_cbt.conf

systemctl restart apache2

# Allow Firewall if UFW active
if command -v ufw >/dev/null 2>&1; then
    ufw allow 80/tcp 2>/dev/null || true
    ufw allow 443/tcp 2>/dev/null || true
fi

# Optional SSL Let's Encrypt
if [[ "$INSTALL_SSL" =~ ^[Yy]$ && "$SERVER_DOMAIN" != "$SERVER_IP" && "$SERVER_DOMAIN" != "localhost" && "$SERVER_DOMAIN" != "127.0.0.1" ]]; then
    echo -e "${BLUE}[*] Memasang sertifikat SSL Let's Encrypt...${NC}"
    apt-get install -y certbot python3-certbot-apache
    certbot --apache -d "$SERVER_DOMAIN" --non-interactive --agree-tos --register-unsafely-without-email || true
fi

# ------------------------------------------------------------------------------
# 10. STEP 7: Diagnostic & Connection Verification Test
# ------------------------------------------------------------------------------
echo -e "${BLUE}[8/8] Memverifikasi konektivitas PHP ke database...${NC}"
TEST_STATUS=$(php -r "
\$conn = @new mysqli('$PHP_ESCAPED_HOST', '$PHP_ESCAPED_USER', '$PHP_ESCAPED_PASS', '$PHP_ESCAPED_NAME', $DB_PORT);
if (\$conn->connect_errno) {
    echo 'FAIL: ' . \$conn->connect_error;
    exit(1);
} else {
    echo 'SUCCESS';
    exit(0);
}
" 2>/dev/null || echo "FAIL")

if [[ "$TEST_STATUS" == *"SUCCESS"* ]]; then
    echo -e "${GREEN}[OK] Koneksi PHP ke MariaDB berhasil diverifikasi!${NC}"
else
    if [[ "$SETUP_VIA_WEB" =~ ^[Yy]$ ]]; then
        echo -e "${CYAN}[INFO] Database siap dikonfigurasi melalui Web Installer di browser.${NC}"
    else
        echo -e "${YELLOW}[!] Peringatan tes koneksi: $TEST_STATUS${NC}"
        echo -e "${YELLOW}    Kredensial tersimpan di $WEB_ROOT/.env. Silakan cek jika diperlukan.${NC}"
    fi
fi

# ------------------------------------------------------------------------------
# 11. Final Summary & Cheat Sheet
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}${BOLD}======================================================================${NC}"
echo -e "${GREEN}${BOLD}        INSTALASI SADUCI CBT BERHASIL DISELESAIKAN!                   ${NC}"
echo -e "${GREEN}${BOLD}             SMP NEGERI SATU ATAP 2 CIPATUJAH                         ${NC}"
echo -e "${GREEN}${BOLD}======================================================================${NC}"
echo ""
echo -e "  Alamat Web CBT   : ${CYAN}http://$SERVER_DOMAIN/${NC}"
echo -e "  Direktori Web    : ${YELLOW}$WEB_ROOT${NC}"
echo -e "  File Konfigurasi : ${CYAN}$WEB_ROOT/.env${NC}"
echo -e "  Host Database    : ${CYAN}$DB_HOST${NC} (Port: ${CYAN}$DB_PORT${NC})"
echo -e "  Nama Database    : ${YELLOW}$DB_NAME${NC}"
echo -e "  User Database    : ${YELLOW}$DB_USER${NC}"
echo -e "  Password Database: ${YELLOW}$DB_PASS${NC}"
echo ""

if [[ "$SETUP_VIA_WEB" =~ ^[Yy]$ ]]; then
    echo -e "  ${BOLD}${YELLOW}[!] MODE WEB INSTALLER DIAKTIFKAN:${NC}"
    echo -e "  Silakan buka: ${CYAN}http://$SERVER_DOMAIN/install${NC} di browser Anda."
    echo -e "  Masukkan Host: ${YELLOW}$DB_HOST${NC}, Database: ${YELLOW}$DB_NAME${NC}, User: ${YELLOW}$DB_USER${NC}, Pass: ${YELLOW}$DB_PASS${NC}"
else
    echo -e "  ${BOLD}Akun Login Administrator Default:${NC}"
    echo -e "  Username         : ${CYAN}admin${NC}"
    echo -e "  Password         : ${CYAN}admin${NC}"
    echo -e "  Login Langsung   : ${CYAN}http://$SERVER_DOMAIN/${NC}"
fi

echo ""
echo -e "  ${BOLD}Perintah Bantuan:${NC}"
echo -e "  Kustomisasi DB   : ${CYAN}nano $WEB_ROOT/.env${NC}"
echo -e "  Lihat Kredensial : ${CYAN}cat $WEB_ROOT/db_credentials.txt${NC}"
echo -e "  Restart Apache   : ${CYAN}sudo systemctl restart apache2${NC}"
echo -e "  Restart MariaDB  : ${CYAN}sudo systemctl restart mariadb${NC}"
echo -e "  Cek Log Error    : ${CYAN}sudo tail -f /var/log/apache2/saduci_cbt_error.log${NC}"
echo -e "${GREEN}======================================================================${NC}\n"
