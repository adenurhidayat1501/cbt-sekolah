#!/usr/bin/env bash
# ==============================================================================
# SADUCI CBT - 1-Click VPS Update Script
# SMP NEGERI SATU ATAP 2 CIPATUJAH
# Modernized Edition (2024-2026)
# ==============================================================================
# Usage:
#   sudo bash update.sh
# ==============================================================================

set -e

# Terminal formatting
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RED='\033[0;31m'
NC='\033[0m'

if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] Jalankan skrip ini dengan hak akses root atau sudo!${NC}"
    echo -e "Silakan jalankan: ${CYAN}sudo bash update.sh${NC}"
    exit 1
fi

echo -e "${CYAN}${BOLD}"
echo "======================================================================"
echo "          SADUCI CBT - 1-CLICK VPS UPDATE HELPER                      "
echo "              SMP NEGERI SATU ATAP 2 CIPATUJAH                        "
echo "======================================================================"
echo -e "${NC}"

# Detect application directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$SCRIPT_DIR/index.php" ]; then
    APP_DIR="$SCRIPT_DIR"
elif [ -f "/var/www/saduci_cbt/index.php" ]; then
    APP_DIR="/var/www/saduci_cbt"
elif [ -f "$SCRIPT_DIR/main/index.php" ]; then
    APP_DIR="$SCRIPT_DIR/main"
else
    APP_DIR="$PWD"
fi

cd "$APP_DIR"
echo -e "${BLUE}[1/5] Memeriksa repositori Git di ${CYAN}$APP_DIR${NC}..."

if [ ! -d ".git" ]; then
    echo -e "${RED}[ERROR] Folder ini bukan repositori Git (.git tidak ditemukan)!${NC}"
    exit 1
fi

# 1. Fetch & Hard Reset to origin/main (safely preserve untracked .env & uploads)
echo -e "${BLUE}[2/5] Mengambil pembaruan terbaru dari GitHub...${NC}"
git fetch origin main
git reset --hard origin/main

CURRENT_COMMIT=$(git log -1 --format="%h - %s (%ci)")
echo -e "${GREEN}--> Berhasil update ke commit:${NC} ${CYAN}$CURRENT_COMMIT${NC}"

# 2. Ensure Apache and PHP modules are in correct configuration
echo -e "${BLUE}[3/5] Memastikan modul Apache dan PHP prefork aktif...${NC}"
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true
a2enmod php7.4 2>/dev/null || a2enmod php8.1 2>/dev/null || a2enmod php 2>/dev/null || true
a2enmod rewrite headers expires ssl 2>/dev/null || true

# Guard .htaccess against unshielded php_value syntax
if [ -f "$APP_DIR/.htaccess" ]; then
    sed -i 's/^php_value max_input_vars/#php_value max_input_vars/g' "$APP_DIR/.htaccess" 2>/dev/null || true
fi

# 3. Clean runtime caches and ensure necessary directories
echo -e "${BLUE}[4/5] Membersihkan cache aplikasi dan memastikan folder upload...${NC}"
rm -rf "$APP_DIR/application/cache"/* 2>/dev/null || true
mkdir -p "$APP_DIR/uploads/import/format"
mkdir -p "$APP_DIR/uploads/bank_soal"
mkdir -p "$APP_DIR/uploads/foto_siswa"
mkdir -p "$APP_DIR/uploads/profiles"
mkdir -p "$APP_DIR/uploads/temp"
mkdir -p "$APP_DIR/backups/daily"

# Set permissions
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} +
find "$APP_DIR" -type f -exec chmod 644 {} +
chmod -R 775 "$APP_DIR/uploads" "$APP_DIR/backups" "$APP_DIR/application/logs" "$APP_DIR/application/cache" 2>/dev/null || true
chmod 600 "$APP_DIR/.env" 2>/dev/null || true
chmod +x "$APP_DIR"/*.sh 2>/dev/null || true

# 4. Graceful reload Apache
echo -e "${BLUE}[5/5] Me-reload web server Apache...${NC}"
systemctl reload apache2 2>/dev/null || systemctl restart apache2

echo -e "\n${GREEN}${BOLD}======================================================================${NC}"
echo -e "${GREEN}${BOLD} [SUKSES] APLIKASI SADUCI CBT BERHASIL DI-UPDATE DARI GITHUB!         ${NC}"
echo -e "${GREEN}${BOLD}======================================================================${NC}"
echo -e "  Versi Commit : ${CYAN}$CURRENT_COMMIT${NC}"
echo -e "  Waktu Update : ${YELLOW}$(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "  Status Server: ${GREEN}Apache Reloaded & Active${NC}"
echo -e "${GREEN}======================================================================${NC}\n"
