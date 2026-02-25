#!/bin/bash

# =============================================================================
# SAFE DEPLOYMENT SCRIPT - DOMAINESIA SERVER
# =============================================================================

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║     SAFE DEPLOYMENT TO PRODUCTION - DOMAINESIA              ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to ask for confirmation
confirm() {
    read -p "$1 (y/n): " choice
    case "$choice" in 
        y|Y ) return 0;;
        * ) return 1;;
    esac
}

# =============================================================================
# STEP 1: PRE-DEPLOYMENT BACKUP
# =============================================================================
echo -e "${YELLOW}═══ STEP 1: PRE-DEPLOYMENT BACKUP ═══${NC}"

if confirm "📦 Backup database sekarang?"; then
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    echo "Creating database backup..."
    
    # Read database credentials from .env
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
    
    echo "Database: $DB_NAME"
    read -sp "Enter MySQL password: " DB_PASS
    echo ""
    
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "backup_${TIMESTAMP}.sql"
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Database backup created: backup_${TIMESTAMP}.sql${NC}"
    else
        echo -e "${RED}❌ Backup failed! STOP DEPLOYMENT${NC}"
        exit 1
    fi
fi

# Backup .env
if confirm "💾 Backup .env file?"; then
    cp .env .env.backup
    echo -e "${GREEN}✅ .env backed up${NC}"
fi

echo ""

# =============================================================================
# STEP 2: PULL LATEST CODE
# =============================================================================
echo -e "${YELLOW}═══ STEP 2: PULL LATEST CODE ═══${NC}"

if confirm "🔄 Pull dari GitHub?"; then
    # Stash local changes
    git stash
    
    # Pull latest
    git pull origin main
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Code pulled successfully${NC}"
    else
        echo -e "${RED}❌ Git pull failed! Check conflicts${NC}"
        exit 1
    fi
fi

echo ""

# =============================================================================
# STEP 3: INSTALL DEPENDENCIES
# =============================================================================
echo -e "${YELLOW}═══ STEP 3: INSTALL DEPENDENCIES ═══${NC}"

if confirm "📦 Run composer install?"; then
    composer install --optimize-autoloader --no-dev
    echo -e "${GREEN}✅ Dependencies installed${NC}"
fi

echo ""

# =============================================================================
# STEP 4: CHECK MIGRATIONS
# =============================================================================
echo -e "${YELLOW}═══ STEP 4: CHECK MIGRATIONS ═══${NC}"

echo "Checking migration status..."
php artisan migrate:status

echo ""
echo "Preview migrations (dry run)..."
php artisan migrate --pretend

echo ""

if confirm "⚠️  RUN MIGRATIONS? (This will modify database)"; then
    php artisan migrate --force
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Migrations completed${NC}"
    else
        echo -e "${RED}❌ Migration failed! Consider rollback${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⏭️  Skipping migrations${NC}"
fi

echo ""

# =============================================================================
# STEP 5: CLEAR & CACHE
# =============================================================================
echo -e "${YELLOW}═══ STEP 5: CLEAR & OPTIMIZE ═══${NC}"

echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize

echo -e "${GREEN}✅ Optimization complete${NC}"
echo ""

# =============================================================================
# STEP 6: SET PERMISSIONS
# =============================================================================
echo -e "${YELLOW}═══ STEP 6: SET PERMISSIONS ═══${NC}"

if confirm "🔐 Fix storage permissions?"; then
    chmod -R 775 storage bootstrap/cache
    echo -e "${GREEN}✅ Permissions set${NC}"
fi

echo ""

# =============================================================================
# STEP 7: VERIFICATION
# =============================================================================
echo -e "${YELLOW}═══ STEP 7: VERIFICATION ═══${NC}"

echo "Checking routes..."
php artisan route:list --path=attendance --columns=uri,name | head -10

echo ""
echo "Checking database connection..."
php artisan tinker --execute="echo 'DB: ' . \DB::connection()->getDatabaseName() . PHP_EOL;"

echo ""

# =============================================================================
# DEPLOYMENT COMPLETE
# =============================================================================
echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║              DEPLOYMENT COMPLETED SUCCESSFULLY!              ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✅ Next Steps:${NC}"
echo "1. Test login di browser"
echo "2. Check /attendance/face"
echo "3. Monitor logs: tail -f storage/logs/laravel.log"
echo "4. Test fitur-fitur utama"
echo ""
echo -e "${YELLOW}⚠️  Rollback command (if needed):${NC}"
echo "   git reset --hard 85c23f0"
echo "   mysql -u user -p database < backup_${TIMESTAMP}.sql"
echo ""
