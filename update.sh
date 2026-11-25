#!/usr/bin/env bash
# ==============================================================================
# SADUCI CBT - Smart Incremental VPS Update Script (Tanpa Build/Install Ulang)
# SMP NEGERI SATU ATAP 2 CIPATUJAH
# Modernized Edition (2024-2026)
# ==============================================================================
# Fitur:
#   - Hanya menarik & menerapkan file yang berubah dari GitHub (incremental)
#   - TIDAK install ulang PHP/Apache/MariaDB & TIDAK rebuild Docker image
#   - Database, .env, uploads/, backups/ 100% AMAN (tidak disentuh)
#   - Composer otomatis jalan HANYA jika composer.json / composer.lock berubah
#   - Mendukung VPS Apache (native) maupun container Docker (saduci_cbt_app)
#
# Usage:
#   sudo bash update.sh              (Update cepat hanya file yang berubah)
#   sudo bash update.sh --force      (Paksa sinkron ulang permission & cache)
#   sudo bash update.sh --branch X   (Update dari branch tertentu, default: main)
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

FORCE_SYNC=false
GIT_BRANCH="main"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --force|-f)
            FORCE_SYNC=true
            shift
            ;;
        --branch|-b)
            GIT_BRANCH="$2"
            shift 2
            ;;
        -h|--help)
            echo "Penggunaan: sudo bash update.sh [OPSI]"
            echo "  -f, --force       Tetap bersihkan cache & reload meski tidak ada commit baru"
            echo "  -b, --branch NAME Branch Git target (default: main)"
            exit 0
            ;;
        *)
            shift
            ;;
    esac
done

if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] Jalankan skrip ini dengan hak akses root atau sudo!${NC}"
    echo -e "Silakan jalankan: ${CYAN}sudo bash update.sh${NC}"
    exit 1
fi

echo -e "${CYAN}${BOLD}"
echo "======================================================================"
echo "     SADUCI CBT - SMART UPDATE CEPAT (HANYA FILE BERUBAH)             "
echo "              SMP NEGERI SATU ATAP 2 CIPATUJAH                        "
echo "======================================================================"
echo -e "${NC}"

# Detect application directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$SCRIPT_DIR/index.php" ] && [ -d "$SCRIPT_DIR/.git" ]; then
    APP_DIR="$SCRIPT_DIR"
elif [ -f "/var/www/saduci_cbt/index.php" ]; then
    APP_DIR="/var/www/saduci_cbt"
elif [ -f "$SCRIPT_DIR/index.php" ]; then
    APP_DIR="$SCRIPT_DIR"
elif [ -f "$SCRIPT_DIR/main/index.php" ]; then
    APP_DIR="$SCRIPT_DIR/main"
else
    APP_DIR="$PWD"
fi

cd "$APP_DIR"
echo -e "${BLUE}[1/5] Memeriksa repositori Git di ${CYAN}$APP_DIR${NC}..."

if [ ! -d ".git" ]; then
    echo -e "${RED}[ERROR] Folder $APP_DIR bukan repositori Git (.git tidak ditemukan)!${NC}"
    echo -e "${YELLOW}Tips:${NC} Jika ingin mengaktifkan update cepat, jalankan:"
    echo -e "  ${CYAN}git init && git remote add origin <URL_REPO_GITHUB> && git fetch origin $GIT_BRANCH${NC}"
    exit 1
fi

# Izinkan git diakses oleh root meski pemilik folder adalah www-data
git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true

OLD_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "")

# 1. Fetch perubahan dari GitHub (tanpa menyentuh untracked .env, uploads, backups)
echo -e "${BLUE}[2/5] Mengecek pembaruan dari GitHub (branch: ${CYAN}$GIT_BRANCH${BLUE})...${NC}"
git fetch origin "$GIT_BRANCH"
NEW_COMMIT=$(git rev-parse "origin/$GIT_BRANCH")

if [ "$OLD_COMMIT" = "$NEW_COMMIT" ] && [ "$FORCE_SYNC" = false ]; then
    CURRENT_COMMIT=$(git log -1 --format="%h - %s (%ci)")
    echo -e "\n${GREEN}${BOLD}[INFO] Aplikasi SADUCI CBT sudah berada di versi terbaru!${NC}"
    echo -e "  Commit Aktif : ${CYAN}$CURRENT_COMMIT${NC}"
    echo -e "  Tidak ada file yang berubah, tidak ada rebuild/restart yang diperlukan."
    echo -e "  (Gunakan ${CYAN}sudo bash update.sh --force${NC} jika tetap ingin clear cache & reload)\n"
    exit 0
fi

# Safety Guard: cegah update jika repo GitHub kehilangan folder inti CodeIgniter (system/)
if ! git cat-file -e "origin/$GIT_BRANCH:system/core/CodeIgniter.php" 2>/dev/null; then
    echo -e "${RED}${BOLD}[ERROR] Commit di origin/$GIT_BRANCH tidak memiliki folder 'system/' CodeIgniter!${NC}"
    echo -e "${YELLOW}Update dibatalkan otomatis agar website tidak rusak/503.${NC}"
    echo -e "Pastikan folder 'system/' sudah ada dan ikut di-push ke GitHub terlebih dahulu."
    exit 1
fi

# Catat daftar file yang berubah sebelum reset
CHANGED_FILES=""
if [ -n "$OLD_COMMIT" ] && [ "$OLD_COMMIT" != "$NEW_COMMIT" ]; then
    CHANGED_FILES=$(git diff --name-only "$OLD_COMMIT".."$NEW_COMMIT" || true)
    CHANGED_COUNT=$(echo "$CHANGED_FILES" | grep -c . || true)
    echo -e "${YELLOW}--> Ditemukan ${BOLD}$CHANGED_COUNT${NC}${YELLOW} file yang berubah:${NC}"
    git diff --stat "$OLD_COMMIT".."$NEW_COMMIT" | head -n 30
    if [ "$CHANGED_COUNT" -gt 30 ]; then
        echo -e "    ... dan $((CHANGED_COUNT - 30)) file lainnya."
    fi
fi

# Amankan .env jika ada (untuk proteksi ekstra)
ENV_BACKUP=""
if [ -f "$APP_DIR/.env" ]; then
    ENV_BACKUP=$(mktemp)
    cp -p "$APP_DIR/.env" "$ENV_BACKUP"
fi

# Terapkan hanya perubahan file dari GitHub
git reset --hard "origin/$GIT_BRANCH" >/dev/null

# Pastikan .env tetap utuh
if [ -n "$ENV_BACKUP" ] && [ -f "$ENV_BACKUP" ]; then
    if [ ! -f "$APP_DIR/.env" ]; then
        cp -p "$ENV_BACKUP" "$APP_DIR/.env"
    fi
    rm -f "$ENV_BACKUP"
fi

CURRENT_COMMIT=$(git log -1 --format="%h - %s (%ci)")
echo -e "${GREEN}--> Berhasil update ke commit:${NC} ${CYAN}$CURRENT_COMMIT${NC}"

# Guard .htaccess against unshielded php_value syntax
if [ -f "$APP_DIR/.htaccess" ]; then
    sed -i 's/^php_value max_input_vars/#php_value max_input_vars/g' "$APP_DIR/.htaccess" 2>/dev/null || true
fi

# 2. Jalankan Composer HANYA jika composer.json atau composer.lock ikut berubah
echo -e "${BLUE}[3/5] Memeriksa perubahan dependensi (Composer)...${NC}"
if echo "$CHANGED_FILES" | grep -qE '^composer\.(json|lock)$'; then
    if command -v composer >/dev/null 2>&1; then
        echo -e "${YELLOW}--> composer.json/lock berubah, memperbarui library vendor...${NC}"
        COMPOSER_ALLOW_SUPERUSER=1 composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction || true
        chown -R www-data:www-data "$APP_DIR/vendor" 2>/dev/null || true
    fi
else
    echo -e "--> Tidak ada perubahan dependensi Composer (dilewati)."
fi

# 3. Deteksi mode Docker vs VPS Apache Native
DOCKER_RUNNING=false
if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' 2>/dev/null | grep -qx 'saduci_cbt_app'; then
    DOCKER_RUNNING=true
fi

if [ "$DOCKER_RUNNING" = true ]; then
    echo -e "${BLUE}[4/5] Terdeteksi container Docker (${CYAN}saduci_cbt_app${BLUE}) aktif — menyalin hanya file yang berubah tanpa rebuild image...${NC}"

    if [ -n "$CHANGED_FILES" ]; then
        while IFS= read -r rel_path; do
            [ -z "$rel_path" ] && continue
            # Abaikan folder mobile app / file git / docker host scripts
            case "$rel_path" in
                exambro-app/*|.git/*|.gitignore|.gitattributes) continue ;;
            esac

            if [ -f "$APP_DIR/$rel_path" ]; then
                target_dir=$(dirname "/var/www/html/$rel_path")
                docker exec saduci_cbt_app mkdir -p "$target_dir"
                docker cp "$APP_DIR/$rel_path" "saduci_cbt_app:/var/www/html/$rel_path"
                docker exec saduci_cbt_app chown www-data:www-data "/var/www/html/$rel_path"
            elif [ ! -e "$APP_DIR/$rel_path" ]; then
                # File dihapus di commit terbaru
                docker exec saduci_cbt_app rm -f "/var/www/html/$rel_path" 2>/dev/null || true
            fi
        done <<< "$CHANGED_FILES"
    fi

    # Bersihkan cache & pastikan folder upload dinamis writable di dalam container
    docker exec saduci_cbt_app bash -c "mkdir -p /var/www/html/uploads/wa_banner /var/www/html/uploads/rekap /var/www/html/uploads/exambro_snapshots /var/www/html/uploads/temp && chown -R www-data:www-data /var/www/html/uploads/wa_banner /var/www/html/uploads/rekap /var/www/html/uploads/exambro_snapshots /var/www/html/uploads/temp && chmod -R 775 /var/www/html/uploads/wa_banner /var/www/html/uploads/rekap /var/www/html/uploads/exambro_snapshots /var/www/html/uploads/temp && rm -rf /var/www/html/application/cache/*" 2>/dev/null || true

    echo -e "${BLUE}[5/5] Me-reload Apache di dalam container Docker (tanpa rebuild)...${NC}"
    docker exec saduci_cbt_app apachectl graceful 2>/dev/null || docker restart saduci_cbt_app

    if echo "$CHANGED_FILES" | grep -qE '^(Dockerfile|docker-compose\.yml)$'; then
        echo -e "${YELLOW}[CATATAN] File Dockerfile/docker-compose.yml berubah.${NC}"
        echo -e "          Jika ada ekstensi PHP/konfigurasi container baru, jalankan:"
        echo -e "          ${CYAN}docker compose up -d --build${NC}"
    fi
    SERVER_STATUS="Docker Container Updated (Tanpa Rebuild Image)"
else
    # Mode VPS Apache Native (paling umum)
    echo -e "${BLUE}[4/5] Mengatur permission hanya pada file berubah & membersihkan cache...${NC}"
    rm -rf "$APP_DIR/application/cache"/* 2>/dev/null || true

    # Pastikan direktori writable penting ada
    mkdir -p "$APP_DIR/uploads/import/format" \
             "$APP_DIR/uploads/bank_soal" \
             "$APP_DIR/uploads/foto_siswa" \
             "$APP_DIR/uploads/profiles" \
             "$APP_DIR/uploads/temp" \
             "$APP_DIR/uploads/wa_banner" \
             "$APP_DIR/uploads/rekap" \
             "$APP_DIR/uploads/exambro_snapshots" \
             "$APP_DIR/backups/daily" \
             "$APP_DIR/application/logs" \
             "$APP_DIR/application/cache"

    # Atur permission HANYA pada file yang berubah agar super cepat (tidak scan ulang semua uploads/)
    if [ -n "$CHANGED_FILES" ]; then
        while IFS= read -r rel_path; do
            [ -z "$rel_path" ] && continue
            if [ -f "$APP_DIR/$rel_path" ]; then
                chown www-data:www-data "$APP_DIR/$rel_path" 2>/dev/null || true
                chmod 644 "$APP_DIR/$rel_path" 2>/dev/null || true
            fi
        done <<< "$CHANGED_FILES"
    fi

    # Pastikan folder upload dinamis (banner WA, rekap Excel, snapshot kamera, temp) selalu writable oleh www-data
    chown -R www-data:www-data "$APP_DIR/uploads/wa_banner" "$APP_DIR/uploads/rekap" "$APP_DIR/uploads/exambro_snapshots" "$APP_DIR/uploads/temp" "$APP_DIR/uploads/import" 2>/dev/null || true
    chmod -R 775 "$APP_DIR/uploads/wa_banner" "$APP_DIR/uploads/rekap" "$APP_DIR/uploads/exambro_snapshots" "$APP_DIR/uploads/temp" "$APP_DIR/uploads/import" 2>/dev/null || true

    chmod -R 775 "$APP_DIR/application/cache" "$APP_DIR/application/logs" 2>/dev/null || true
    chown -R www-data:www-data "$APP_DIR/application/cache" "$APP_DIR/application/logs" 2>/dev/null || true
    chmod 600 "$APP_DIR/.env" 2>/dev/null || true
    chmod +x "$APP_DIR"/*.sh 2>/dev/null || true

    echo -e "${BLUE}[5/5] Me-reload Apache secara graceful & memastikan cron notifikasi WA aktif...${NC}"
    if [ -d /etc/cron.d ]; then
        PHP_BIN=$(command -v php || echo "/usr/bin/php")
        cat <<CRONEOF > /etc/cron.d/saduci_wa_schedule
# SADUCI CBT - Notifikasi Otomatis Jadwal Ujian & Pengawas WA (Sore & Pagi)
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
*/5 * * * * www-data $PHP_BIN $APP_DIR/index.php cbtwa cron_schedule >/dev/null 2>&1
CRONEOF
        chmod 644 /etc/cron.d/saduci_wa_schedule
    fi
    if systemctl is-active --quiet apache2 2>/dev/null; then
        systemctl reload apache2 2>/dev/null || systemctl restart apache2
        SERVER_STATUS="Apache Reloaded & Cron WA Aktif (Graceful, Tanpa Build Ulang)"
    else
        SERVER_STATUS="File Diperbarui & Cron WA Terpasang (Apache belum aktif)"
    fi
fi

echo -e "\n${GREEN}${BOLD}======================================================================${NC}"
echo -e "${GREEN}${BOLD} [SUKSES] UPDATE CEPAT SADUCI CBT SELESAI (DATA & DB TETAP AMAN!)     ${NC}"
echo -e "${GREEN}${BOLD}======================================================================${NC}"
echo -e "  Versi Commit : ${CYAN}$CURRENT_COMMIT${NC}"
echo -e "  Waktu Update : ${YELLOW}$(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "  Status Server: ${GREEN}$SERVER_STATUS${NC}"
echo -e "${GREEN}======================================================================${NC}\n"
