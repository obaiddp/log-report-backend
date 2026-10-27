For a LAN deployment I’d use:

nginx serving the built React app from dist/ and passing /api to PHP-FPM
PostgreSQL on the same server (or your existing DB host)
Laravel in /var/www/log-report-backend, with APP_ENV=production, APP_DEBUG=false, caches built, and storage writable by www-data
A systemd service for php artisan queue:work, so notifications actually send
A systemd timer or cron for schedule:run, only if you use scheduled jobs