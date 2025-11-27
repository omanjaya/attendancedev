# Development Setup Guide

## Recommended Development Environment

### 1. Use WSL for Development

- Code editing (VS Code with WSL extension)
- Running artisan commands
- Git operations
- Installing packages (composer, npm)

### 2. Use Windows CMD/PowerShell for Running Servers

```cmd
# In Windows CMD/PowerShell
cd D:\devv\attendance-system
php artisan serve
```

### 3. Database Configuration

Keep using Windows paths in .env:

```
DB_CONNECTION=sqlite
DB_DATABASE=D:\devv\attendance-system\database\database.sqlite
```

### 4. Common Commands

**From WSL (for development):**

```bash
# Database operations
php artisan migrate
php artisan db:seed

# Cache clearing
php artisan cache:clear
php artisan config:clear

# Package management
composer install
npm install
npm run build
```

**From Windows CMD (for running server):**

```cmd
php artisan serve
```

### 5. Troubleshooting

If you encounter database sync issues:

1. Always run migrations from the same environment (preferably Windows)
2. Clear all caches after making changes
3. Restart the server after database changes

### 6. Best Practices

1. **DO NOT** run multiple servers (one from WSL, one from Windows)
2. **DO** keep all file paths in Windows format in .env
3. **DO** use WSL for all development tasks except running the server
4. **DO** backup your database regularly

## Why This Setup?

- **Performance**: Avoids WSL2 filesystem overhead for server operations
- **Compatibility**: Windows server accesses Windows filesystem natively
- **Development Experience**: WSL provides better dev tools
- **Stability**: Reduces cross-platform issues

## Quick Start

1. Open two terminals:
   - Terminal 1: WSL (for development commands)
   - Terminal 2: Windows CMD (for `php artisan serve`)

2. In VS Code:
   - Use "Remote - WSL" extension
   - Open project from WSL path: `/mnt/d/devv/attendance-system`

3. Access application:
   - Browser: `http://localhost:8000`
   - Database: Managed through Windows path

This hybrid approach gives you the best of both worlds!
