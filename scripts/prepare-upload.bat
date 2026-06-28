@echo off
REM Prepare project for upload to shared hosting (run on Windows / Laragon).
cd /d "%~dp0.."
echo ==^> Toko Plastik — prepare upload package

where composer >nul 2>&1
if errorlevel 1 (
  echo ERROR: composer not in PATH. Use Laragon terminal.
  exit /b 1
)

call composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
if errorlevel 1 exit /b 1

call composer dump-autoload --optimize --no-scripts

php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo OK. Upload the ENTIRE project folder to server EXCEPT:
echo   - node_modules/
echo   - .git/
echo   - tests/ (optional)
echo.
echo On server: configure .env, then run scripts/deploy-on-server.sh
echo Or manually: php artisan migrate --force ^&^& php artisan config:cache
pause
