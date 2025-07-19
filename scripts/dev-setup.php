<?php

/**
 * Development Setup Script for Windows/Cross-platform
 * PHP version of the bash setup script
 */

class DevSetup
{
    private $isWindows;
    private $commands = [];

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        echo "🏫 School Attendance System - Development Setup\n";
        echo "=================================================\n\n";
    }

    public function run()
    {
        $this->checkPrerequisites();
        $this->setupEnvironment();
        $this->installDependencies();
        $this->setupDatabase();
        $this->buildAssets();
        $this->createAdminUser();
        $this->showCompletionMessage();
    }

    private function checkPrerequisites()
    {
        echo "📋 Checking prerequisites...\n";

        $this->checkCommand('php', 'PHP 8.2+');
        $this->checkCommand('composer', 'Composer');
        $this->checkCommand('node', 'Node.js');
        $this->checkCommand('npm', 'npm');

        echo "✅ All prerequisites are installed\n\n";
    }

    private function checkCommand($command, $name)
    {
        $nullRedirect = $this->isWindows ? '2>nul' : '2>/dev/null';
        exec("$command --version $nullRedirect", $output, $returnCode);
        
        if ($returnCode !== 0) {
            echo "❌ $name is not installed or not in PATH\n";
            exit(1);
        }
    }

    private function setupEnvironment()
    {
        echo "⚙️ Setting up environment file...\n";

        if (!file_exists('.env')) {
            if (file_exists('.env.development')) {
                copy('.env.development', '.env');
                echo "✅ Environment file created from .env.development\n";
            } elseif (file_exists('.env.example')) {
                copy('.env.example', '.env');
                echo "✅ Environment file created from .env.example\n";
            } else {
                echo "❌ No environment template found\n";
                exit(1);
            }
        } else {
            echo "ℹ️ Environment file already exists\n";
        }
        echo "\n";
    }

    private function installDependencies()
    {
        echo "📦 Installing PHP dependencies...\n";
        $this->runCommand('composer install');

        echo "📦 Installing JavaScript dependencies...\n";
        $this->runCommand('npm install');

        echo "🔑 Generating application key...\n";
        $this->runCommand('php artisan key:generate');
        echo "\n";
    }

    private function setupDatabase()
    {
        echo "🗄️ Setting up database...\n";

        $dbPath = 'database/database.sqlite';
        if (!file_exists($dbPath)) {
            // Create directory if it doesn't exist
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            // Create empty SQLite file
            touch($dbPath);
            echo "✅ SQLite database file created\n";
        }

        echo "🔄 Running database migrations...\n";
        $this->runCommand('php artisan migrate');

        echo "🌱 Seeding database with sample data...\n";
        $this->runCommand('php artisan db:seed');

        echo "🔗 Creating storage link...\n";
        $this->runCommand('php artisan storage:link');
        echo "\n";
    }

    private function buildAssets()
    {
        echo "🏗️ Building frontend assets...\n";
        $this->runCommand('npm run build');
        echo "\n";
    }

    private function createAdminUser()
    {
        echo "👤 Creating super admin user...\n";

        echo "Enter admin email (default: admin@school.com): ";
        $adminEmail = trim(fgets(STDIN));
        if (empty($adminEmail)) {
            $adminEmail = 'admin@school.com';
        }

        echo "Enter admin password (default: password): ";
        $adminPassword = trim(fgets(STDIN));
        if (empty($adminPassword)) {
            $adminPassword = 'password';
        }

        $this->runCommand("php artisan make:admin-user --email=\"$adminEmail\" --password=\"$adminPassword\" --force");
        
        $this->adminEmail = $adminEmail;
        $this->adminPassword = $adminPassword;
        echo "\n";
    }

    private function showCompletionMessage()
    {
        echo "🎉 Development setup completed successfully!\n\n";
        echo "📋 What's next:\n";
        echo "1. Start the development server:\n";
        echo "   composer dev\n\n";
        echo "2. Or start services individually:\n";
        echo "   php artisan serve (Laravel server)\n";
        echo "   npm run dev (Frontend with hot reload)\n";
        echo "   php artisan queue:work (Background jobs)\n\n";
        echo "3. Access the application:\n";
        echo "   🌐 Application: http://localhost:8000\n";
        echo "   📚 API Documentation: http://localhost:8000/api/documentation\n";
        echo "   ❤️ Health Check: http://localhost:8000/api/health\n\n";
        echo "4. Login credentials:\n";
        echo "   📧 Email: {$this->adminEmail}\n";
        echo "   🔑 Password: {$this->adminPassword}\n\n";
        echo "💡 Tip: Run 'composer dev' to start all services at once!\n";
        echo "📖 For more information, check LAPTOP_DEVELOPMENT_GUIDE.md\n";
    }

    private function runCommand($command)
    {
        echo "Running: $command\n";
        
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            
            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            if ($returnCode !== 0) {
                echo "❌ Command failed: $command\n";
                echo "Error: $error\n";
                exit(1);
            }

            if (!empty($output)) {
                echo $output;
            }
        }
    }
}

// Check if we're in the right directory
if (!file_exists('composer.json')) {
    echo "❌ Error: Please run this script from the project root directory\n";
    exit(1);
}

// Run setup
$setup = new DevSetup();
$setup->run();