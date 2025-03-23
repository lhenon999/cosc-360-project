@echo off
REM Stripe Webhook Starter Script
REM This batch file starts the Stripe webhook listener and can be added to Windows startup

REM Change to the script directory
cd /d "%~dp0"

REM Run the PHP script to start the webhook listener
"C:\xampp\php\php.exe" "%~dp0stripe_webhook_manager.php" start

REM Log the result
echo Stripe webhook listener started at %date% %time% >> "%~dp0webhook_startup.log" 