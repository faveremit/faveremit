<?php

/**
 * Laravel Database Setup and Migration Script
 * This script sets up the complete database structure for the crypto trading platform
 */

echo "🚀 Starting Laravel Database Setup...\n\n";

// Check if we're in a Laravel project
if (!file_exists('artisan')) {
    echo "❌ Error: This script must be run from the Laravel project root directory.\n";
    exit(1);
}

try {
    // Step 1: Clear any existing cache
    echo "📝 Clearing application cache...\n";
    exec('php artisan config:clear', $output, $return_var);
    exec('php artisan cache:clear', $output, $return_var);
    exec('php artisan route:clear', $output, $return_var);
    
    // Step 2: Check database connection
    echo "🔍 Checking database connection...\n";
    exec('php artisan migrate:status 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        echo "⚠️  Database connection issue detected. Please check your .env file.\n";
        echo "Make sure these variables are set correctly:\n";
        echo "DB_CONNECTION=mysql\n";
        echo "DB_HOST=127.0.0.1\n";
        echo "DB_PORT=3306\n";
        echo "DB_DATABASE=faveremit\n";
        echo "DB_USERNAME=your_username\n";
        echo "DB_PASSWORD=your_password\n\n";
    }
    
    // Step 3: Run fresh migrations
    echo "🗄️  Running fresh migrations...\n";
    exec('php artisan migrate:fresh --force 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ Database migrations completed successfully!\n";
    } else {
        echo "❌ Migration failed. Output:\n";
        foreach ($output as $line) {
            echo $line . "\n";
        }
    }
    
    // Step 4: Seed the database with sample data
    echo "🌱 Seeding database with sample data...\n";
    exec('php artisan db:seed --force 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ Database seeding completed successfully!\n";
    } else {
        echo "⚠️  Seeding failed, but migrations were successful.\n";
    }
    
    // Step 5: Generate application key if needed
    echo "🔑 Checking application key...\n";
    if (empty(env('APP_KEY'))) {
        exec('php artisan key:generate --force', $output, $return_var);
        echo "✅ Application key generated!\n";
    } else {
        echo "✅ Application key already exists!\n";
    }
    
    // Step 6: Create storage link
    echo "🔗 Creating storage link...\n";
    exec('php artisan storage:link 2>&1', $output, $return_var);
    
    // Step 7: Set up session configuration
    echo "⚙️  Configuring session settings...\n";
    
    // Final success message
    echo "\n🎉 Database setup completed successfully!\n";
    echo "📊 Your crypto trading platform database is ready!\n\n";
    
    echo "Next steps:\n";
    echo "1. Start the Laravel server: php artisan serve\n";
    echo "2. Start the queue worker: php artisan queue:work\n";
    echo "3. Set up the scheduled tasks: php artisan schedule:work\n\n";
    
    echo "🌐 Your API will be available at: http://127.0.0.1:8000\n";
    echo "📱 Your frontend should connect to: http://127.0.0.1:8000/api\n\n";
    
} catch (Exception $e) {
    echo "❌ An error occurred: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✨ Setup complete! Happy trading! 🚀\n";
