@echo off
echo ===================================================
echo Content Scheduler - Laravel Scheduler Runner
echo ===================================================
echo The scheduler will run every minute to check for scheduled posts
echo Press Ctrl+C to stop the scheduler
echo.

:loop
echo [%date% %time%] Running scheduler...
php artisan schedule:run
echo [%date% %time%] Scheduler completed. Waiting 60 seconds...
echo.
timeout /t 60
goto loop 