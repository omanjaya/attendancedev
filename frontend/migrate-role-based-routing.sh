#!/bin/bash

# Role-Based Routing Migration Script
# Migrates pages from mixed structure to role-based (admin/ vs employee/)

set -e  # Exit on error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PAGES_DIR="$SCRIPT_DIR/src/pages"

echo "================================"
echo "🚀 Role-Based Routing Migration"
echo "================================"
echo ""

# Check if we're in the right directory
if [ ! -d "$PAGES_DIR" ]; then
  echo "❌ Error: pages directory not found at $PAGES_DIR"
  exit 1
fi

cd "$PAGES_DIR"

# Create backup
BACKUP_NAME="pages_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
echo "📦 Creating backup: $BACKUP_NAME"
cd ..
tar -czf "$BACKUP_NAME" pages/
cd pages
echo "✅ Backup created: src/$BACKUP_NAME"
echo ""

# Confirm with user
echo "⚠️  This will move files and reorganize the pages directory."
echo "📍 Current directory: $PAGES_DIR"
echo ""
echo "Files will be moved:"
echo "  - employees/           → admin/employees/"
echo "  - schedules/           → admin/schedules/"
echo "  - payroll/             → admin/payroll/"
echo "  - leave/               → admin/leave/"
echo "  - reports/             → admin/reports/"
echo "  - face-recognition/    → admin/face-recognition/"
echo "  - settings/            → admin/settings/"
echo "  - security/            → admin/security/"
echo "  - profile/             → employee/profile/"
echo "  - attendance/verify-*  → shared/"
echo ""
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  echo "❌ Migration cancelled"
  exit 1
fi

echo ""
echo "================================"
echo "📂 Moving files to admin/..."
echo "================================"

# Function to move directory if it exists
move_to_admin() {
  local dir_name=$1
  if [ -d "$dir_name" ]; then
    echo "  Moving $dir_name/ → admin/$dir_name/"
    mv "$dir_name" admin/
  else
    echo "  ⚠️  Skipping $dir_name/ (not found)"
  fi
}

# Move to admin/
move_to_admin "employees"
move_to_admin "schedules"
move_to_admin "payroll"
move_to_admin "leave"
move_to_admin "reports"
move_to_admin "face-recognition"
move_to_admin "settings"
move_to_admin "security"

echo ""
echo "================================"
echo "👤 Moving files to employee/..."
echo "================================"

# Move to employee/
if [ -d "profile" ]; then
  echo "  Moving profile/ → employee/profile/"
  mv profile employee/
else
  echo "  ⚠️  Skipping profile/ (not found)"
fi

echo ""
echo "================================"
echo "🌐 Moving files to shared/..."
echo "================================"

# Move to shared/
if [ -f "attendance/verify-face.tsx" ]; then
  echo "  Moving attendance/verify-face.tsx → shared/"
  mv attendance/verify-face.tsx shared/
else
  echo "  ⚠️  Skipping verify-face.tsx (not found)"
fi

if [ -f "attendance/verify-location.tsx" ]; then
  echo "  Moving attendance/verify-location.tsx → shared/"
  mv attendance/verify-location.tsx shared/
else
  echo "  ⚠️  Skipping verify-location.tsx (not found)"
fi

echo ""
echo "================================"
echo "📊 Migration Summary"
echo "================================"
echo ""

# Show new structure
echo "New structure:"
echo ""
echo "📁 pages/"
echo "  ├── admin/"
ls -1 admin/ 2>/dev/null | sed 's/^/  │   ├── /' || echo "  │   (empty)"
echo "  ├── employee/"
ls -1 employee/ 2>/dev/null | sed 's/^/  │   ├── /' || echo "  │   (empty)"
echo "  ├── shared/"
ls -1 shared/ 2>/dev/null | sed 's/^/  │   ├── /' || echo "  │   (empty)"
echo "  └── ..."
echo ""

echo "✅ File migration complete!"
echo ""
echo "================================"
echo "📝 Next Steps"
echo "================================"
echo ""
echo "1. ✅ Create new pages:"
echo "   - pages/admin/dashboard.tsx (admin dashboard)"
echo "   - pages/admin/attendance/ (admin attendance management)"
echo "   - pages/employee/dashboard.tsx (employee dashboard)"
echo "   - pages/employee/attendance/ (employee attendance view)"
echo "   - pages/employee/schedule/ (employee schedule view)"
echo "   - pages/employee/leave/ (employee leave requests)"
echo "   - pages/employee/payroll/ (employee payslips view)"
echo "   - pages/unauthorized.tsx"
echo ""
echo "2. ✅ Update router configuration:"
echo "   - Add role guards (requireAdmin, requireEmployee)"
echo "   - Configure /admin/* routes"
echo "   - Configure /employee/* routes"
echo "   - Configure /shared/* routes"
echo ""
echo "3. ✅ Update imports in all files:"
echo "   - Search for old import paths"
echo "   - Replace with new paths"
echo ""
echo "4. ✅ Update sidebar navigation:"
echo "   - Create AdminSidebar (admin menu items)"
echo "   - Create EmployeeSidebar (employee menu items)"
echo "   - Update layouts to use role-based sidebars"
echo ""
echo "5. ✅ Test:"
echo "   - Login as admin → should go to /admin/dashboard"
echo "   - Login as employee → should go to /employee/dashboard"
echo "   - Test all routes and redirects"
echo ""
echo "📦 Backup location: src/$BACKUP_NAME"
echo ""
echo "To rollback:"
echo "  cd src"
echo "  rm -rf pages"
echo "  tar -xzf $BACKUP_NAME"
echo ""
echo "🎉 Done!"
