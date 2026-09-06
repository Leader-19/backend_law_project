#!/usr/bin/env bash
#
# deploy.sh — Production deployment script for Law Sharing Project
#
# Usage:
#   ./deploy.sh              # Full deploy (pull + build + migrate + cache)
#   ./deploy.sh --pull       # Pull only (no build/migrate)
#   ./deploy.sh --no-build   # Skip frontend asset build
#   ./deploy.sh --force      # Skip confirmation prompt
#
set -euo pipefail

# ─── Configuration ───────────────────────────────────────────────
APP_DIR="/var/www/backend_law_project"
BRANCH="${DEPLOY_BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
NPM_BIN="${NPM_BIN:-npm}"
SKIP_BUILD=false
PULL_ONLY=false
FORCE=false

# ─── Parse arguments ─────────────────────────────────────────────
for arg in "$@"; do
    case $arg in
        --no-build)  SKIP_BUILD=true ;;
        --pull)      PULL_ONLY=true ;;
        --force)     FORCE=true ;;
        --help|-h)
            echo "Usage: $0 [--pull] [--no-build] [--force]"
            echo "  --pull       Pull code only, skip build/migrate/cache"
            echo "  --no-build   Skip npm build (frontend assets)"
            echo "  --force      Skip confirmation prompt"
            exit 0
            ;;
        *)
            echo "Unknown argument: $arg"
            exit 1
            ;;
    esac
done

# ─── Helpers ─────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

log()  { echo -e "${CYAN}[$(date +'%H:%M:%S')]${NC} $*"; }
ok()   { echo -e "${GREEN}[$(date +'%H:%M:%S')] ✓${NC} $*"; }
warn() { echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠${NC} $*"; }
fail() { echo -e "${RED}[$(date +'%H:%M:%S')] ✗${NC} $*"; exit 1; }

# ─── Pre-flight checks ──────────────────────────────────────────
log "Pre-flight checks..."

if [ ! -d "$APP_DIR" ]; then
    fail "Application directory not found: $APP_DIR"
fi

cd "$APP_DIR"

if [ ! -f "artisan" ]; then
    fail "artisan not found. Are you in the right directory?"
fi

if [ "$FORCE" = false ] && [ "$PULL_ONLY" = false ]; then
    echo ""
    echo -e "${YELLOW}This will deploy to production:${NC}"
    echo "  Directory: $APP_DIR"
    echo "  Branch:    $BRANCH"
    echo "  Build:     $([ "$SKIP_BUILD" = true ] && echo 'skipped' || echo 'yes')"
    echo ""
    read -p "Continue? (y/N) " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Aborted."
        exit 0
    fi
fi

# ─── Step 1: Maintenance mode ───────────────────────────────────
log "Enabling maintenance mode..."
$PHP_BIN artisan down --render="errors::503" --retry=60 --secret="$(openssl rand -hex 16)" 2>/dev/null || warn "Could not enable maintenance mode (might already be down)"
ok "Maintenance mode enabled"

# ─── Step 2: Pull latest code ───────────────────────────────────
log "Pulling latest code from origin/$BRANCH..."
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"
ok "Code updated to $(git log -1 --format='%h %s')"

if [ "$PULL_ONLY" = true ]; then
    log "Pull-only mode. Skipping build, migrate, and cache."
    $PHP_BIN artisan up
    ok "Done! Maintenance mode disabled."
    exit 0
fi

# ─── Step 3: Install PHP dependencies ───────────────────────────
log "Installing Composer dependencies..."
$PHP_BIN "$(which composer)" install --no-dev --optimize-autoloader --no-interaction
ok "Composer dependencies installed"

# ─── Step 4: Run migrations ─────────────────────────────────────
log "Running database migrations..."
$PHP_BIN artisan migrate --force
ok "Migrations complete"

# ─── Step 5: Clear & rebuild caches ─────────────────────────────
log "Clearing all caches..."
$PHP_BIN artisan optimize:clear
ok "All caches cleared"

log "Rebuilding caches..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache
ok "Caches rebuilt"

# ─── Step 6: Fix permissions ────────────────────────────────────
log "Fixing storage and bootstrap/cache permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || warn "Could not fix permissions"
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || warn "Could not chown (run as root or sudo)"
ok "Permissions fixed"

# ─── Step 7: Build frontend assets ──────────────────────────────
if [ "$SKIP_BUILD" = false ]; then
    log "Building frontend assets..."
    if [ -f "package.json" ]; then
        $NPM_BIN ci --prefer-offline 2>/dev/null || $NPM_BIN install --prefer-offline
        $NPM_BIN run build
        ok "Frontend assets built"
    else
        warn "No package.json found. Skipping frontend build."
    fi
else
    warn "Frontend build skipped (--no-build)"
fi

# ─── Step 8: Restart services ───────────────────────────────────
log "Restarting services..."

# Restart queue workers
$PHP_BIN artisan queue:restart 2>/dev/null && ok "Queue workers restarted" || warn "Queue restart skipped"

# Restart Horizon (if using)
$PHP_BIN artisan horizon:terminate 2>/dev/null && ok "Horizon restarted" || warn "Horizon not running"

# Restart PHP-FPM
for version in 8.3 8.2 8.1 8.0; do
    if systemctl is-active --quiet "php${version}-fpm" 2>/dev/null; then
        sudo systemctl restart "php${version}-fpm"
        ok "PHP-FPM ${version} restarted"
        break
    fi
done

# Reload Nginx (if using)
sudo nginx -t 2>/dev/null && sudo systemctl reload nginx 2>/dev/null && ok "Nginx reloaded" || warn "Nginx reload skipped"

# ─── Step 9: Disable maintenance mode ───────────────────────────
log "Disabling maintenance mode..."
$PHP_BIN artisan up
ok "Application is LIVE"

# ─── Step 10: Summary ───────────────────────────────────────────
echo ""
echo -e "${GREEN}═══════════════════════════════════════════${NC}"
echo -e "${GREEN}  Deployment complete!${NC}"
echo -e "${GREEN}  Branch:  ${NC}$(git log -1 --format='%h %s')"
echo -e "${GREEN}  Time:    ${NC}$(date)"
echo -e "${GREEN}═══════════════════════════════════════════${NC}"
