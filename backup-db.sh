#!/usr/bin/env bash
# ==============================================================================
# SADUCI CBT - Automated Database Backup Script
# SMP NEGERI SATU ATAP 2 CIPATUJAH
# Modernized Edition (2024-2026)
# ==============================================================================
# Usage:
#   sudo bash backup-db.sh               (Jalankan pencadangan database sekarang)
#   sudo bash backup-db.sh --cron        (Pasang cron job otomatis harian jam 23:00)
# ==============================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RED='\033[0;31m'
NC='\033[0m'

if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] Jalankan skrip ini dengan hak akses root atau sudo!${NC}"
    exit 1
fi

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

# Handle --cron argument to install daily cron job
if [[ "$1" == "--cron" || "$1" == "--install-cron" ]]; then
    echo -e "${CYAN}${BOLD}[*] Memasang jadwal cron job backup database otomatis...${NC}"
    CRON_FILE="/etc/cron.d/saduci_cbt_backup"
    cat <<EOF > "$CRON_FILE"
# SADUCI CBT - Backup Database Harian Setiap Pukul 23:00 WIB
0 23 * * * root bash $APP_DIR/backup-db.sh >> $APP_DIR/application/logs/backup.log 2>&1
EOF
    chmod 644 "$CRON_FILE"
    echo -e "${GREEN}[OK] Cron job berhasil dipasang di $CRON_FILE!${NC}"
    echo -e "Jadwal: ${YELLOW}Setiap hari pukul 23:00 WIB${NC}"
    echo -e "Log: ${CYAN}$APP_DIR/application/logs/backup.log${NC}\n"
    exit 0
fi

# 1. Read database credentials from .env
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="saduci_cbt"
DB_USER="saduci_cbt"
DB_PASS="saduci123"

if [ -f "$APP_DIR/.env" ]; then
    while IFS='=' read -r key val || [ -n "$key" ]; do
        key=$(echo "$key" | tr -d ' \r\n')
        val=$(echo "$val" | tr -d ' \r\n"' | tr -d "'")
        case "$key" in
            DB_HOST) DB_HOST="$val" ;;
            DB_PORT) DB_PORT="$val" ;;
            DB_NAME) DB_NAME="$val" ;;
            DB_USER) DB_USER="$val" ;;
            DB_PASS) DB_PASS="$val" ;;
        esac
    done < "$APP_DIR/.env"
fi

BACKUP_DIR="$APP_DIR/backups/daily"
mkdir -p "$BACKUP_DIR"
mkdir -p "$APP_DIR/application/logs"

TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql.gz"

echo -e "${BLUE}[*] Memulai pencadangan database ${YELLOW}$DB_NAME${BLUE}...${NC}"

# Execute mysqldump with root maintenance fallback if available
DUMP_CMD=""
if [ -f /etc/mysql/debian.cnf ]; then
    DUMP_CMD="mysqldump --defaults-file=/etc/mysql/debian.cnf"
elif [ -n "$DB_PASS" ]; then
    DUMP_CMD="mysqldump -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS"
else
    DUMP_CMD="mysqldump -h $DB_HOST -P $DB_PORT -u $DB_USER"
fi

$DUMP_CMD --single-transaction --quick --routines --triggers "$DB_NAME" 2>/dev/null | gzip > "$BACKUP_FILE"

FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)

# Rotate old backups (delete files older than 7 days)
DELETED_COUNT=$(find "$BACKUP_DIR" -type f -name "backup_${DB_NAME}_*.sql.gz" -mtime +7 -delete -print 2>/dev/null | wc -l)

# Set permission
chmod 600 "$BACKUP_FILE"
chown -R www-data:www-data "$APP_DIR/backups"

echo -e "${GREEN}[OK] Backup database berhasil!${NC}"
echo -e "  Berkas : ${CYAN}$BACKUP_FILE${NC}"
echo -e "  Ukuran : ${YELLOW}$FILE_SIZE${NC}"
echo -e "  Rotasi : ${YELLOW}$DELETED_COUNT cadangan lama (>7 hari) dibersihkan.${NC}"
echo -e "  Waktu  : $(date '+%Y-%m-%d %H:%M:%S')\n"
