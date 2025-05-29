@echo off
title Content Scheduler - Automated Publishing System
color 0A

echo ===================================================
echo              CONTENT SCHEDULER
echo            Automated Publishing System
echo ===================================================
echo.
echo This application will automatically publish your scheduled posts
echo when they reach their scheduled time.
echo.
echo Current time: %date% %time%
echo.
echo Options:
echo [1] Run the scheduler (publishes posts every minute)
echo [2] Check scheduled posts status
echo [3] Exit
echo.

:menu
set /p choice=Enter your choice (1-3): 

if "%choice%"=="1" goto run_scheduler
if "%choice%"=="2" goto check_posts
if "%choice%"=="3" goto exit

echo Invalid choice. Please try again.
goto menu

:run_scheduler
cls
echo ===================================================
echo              RUNNING SCHEDULER
echo ===================================================
echo The scheduler will check for posts to publish every minute.
echo Press Ctrl+C to stop the scheduler and return to menu.
echo.
echo Starting at: %date% %time%
echo.

:loop
echo [%date% %time%] Running scheduler...
php artisan schedule:run
echo [%date% %time%] Scheduler completed. Waiting 60 seconds...
echo.
timeout /t 60
goto loop

:check_posts
cls
echo ===================================================
echo          CHECKING SCHEDULED POSTS
echo ===================================================
echo.
php artisan debug:scheduled-posts
echo.
pause
cls
goto menu

:exit
echo.
echo Thank you for using Content Scheduler!
echo.
pause
exit 