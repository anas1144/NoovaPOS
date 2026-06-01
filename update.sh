#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# NoovaPOS — Update Script (Linux / Mac / WSL)
# Usage:  bash update.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'   # no colour

STEP=0
TOTAL=9

step() {
    STEP=$((STEP + 1))
    echo ""
    echo -e "${CYAN}[$STEP/$TOTAL] $1${NC}"
    echo "  ─────────────────────────────────────────────────────"
}

echo ""
echo -e "${GREEN}  NoovaPOS — Multi-Tenant SaaS POS + ERP${NC}"
echo -e "${GREEN}  Update Script${NC}"
echo ""

# ── Step 1: Node / npm versions ───────────────────────────────────────────────
step "Checking runtime versions"
echo "  Node: $(node --version 2>/dev/null || echo 'NOT FOUND')"
echo "  npm:  $(npm --version 2>/dev/null || echo 'NOT FOUND')"
echo "  PHP:  $(php --version 2>/dev/null | head -1 || echo 'NOT FOUND')"

# ── Step 2: Composer update ───────────────────────────────────────────────────
step "Updating PHP dependencies (composer update)"
composer update --no-interaction --prefer-dist

# ── Step 3: Clear caches ──────────────────────────────────────────────────────
step "Clearing old config / route / view caches"
php artisan optimize:clear

# ── Step 4: Migrate ───────────────────────────────────────────────────────────
step "Running database migrations"
php artisan migrate --force

# ── Step 5: Default seeders ───────────────────────────────────────────────────
step "Re-seeding default data (safe — uses firstOrCreate)"
php artisan db:seed --class=DefaultPermissionsSeeder --force
php artisan db:seed --class=EnsurePlatformRolesSeeder --force

# ── Step 6: Permission cache ──────────────────────────────────────────────────
step "Refreshing Spatie permission cache"
php artisan permission:cache-reset

# ── Step 7: npm install ───────────────────────────────────────────────────────
step "Installing / updating Node.js packages"
npm install --legacy-peer-deps

# ── Step 8: Build frontend ────────────────────────────────────────────────────
step "Building frontend assets"
npm run dev

# ── Step 9: Re-cache ─────────────────────────────────────────────────────────
step "Final: re-caching config + routes"
php artisan optimize

echo ""
echo -e "${GREEN}  ═══════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓  NoovaPOS update complete!${NC}"
echo -e "  Visit: http://127.0.0.1:8000"
echo -e "  Admin: supperadmin@noovapos.com / 123456"
echo -e "${GREEN}  ═══════════════════════════════════════════════════${NC}"
echo ""
