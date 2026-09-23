@echo off
setlocal
title Falcons Academy (local network)
cd /d "%~dp0"
set "LOG=%~dp0start-log.txt"
set "PORT=8000"
> "%LOG%" echo [%date% %time%] started from: %~dp0

rem ---- Use the portable PHP bundled in .\php (no installation needed) ----
set "PHPDIR=%~dp0php"
set "PHPEXE=%PHPDIR%\php.exe"
if not exist "%PHPEXE%" (
  where php >nul 2>nul || (echo PHP not found. & pause & exit /b 1)
  set "PHPEXE=php"
) else (
  rem Write php.ini next to php.exe with an absolute extension path
  > "%PHPDIR%\php.ini" (
    echo extension_dir="%PHPDIR%\ext"
    echo extension=openssl
    echo extension=mbstring
    echo extension=fileinfo
    echo extension=pdo_sqlite
    echo extension=sqlite3
    echo extension=gd
    echo extension=intl
    echo memory_limit=512M
    echo upload_max_filesize=20M
    echo post_max_size=25M
    echo date.timezone=Africa/Cairo
  )
  set "PATH=%PHPDIR%;%PATH%"
)

echo Checking PHP...
"%PHPEXE%" -v
"%PHPEXE%" -v >> "%LOG%" 2>&1
>> "%LOG%" echo php exit code: %errorlevel%
"%PHPEXE%" -m >> "%LOG%" 2>&1
if errorlevel 1 (
  echo.
  echo  PHP could not start. If Windows says VCRUNTIME140.dll is missing, install the
  echo  "Microsoft Visual C++ Redistributable 2015-2022 (x64)" from Microsoft, then run this again.
  echo.
  pause
  exit /b 1
)

"%PHPEXE%" artisan optimize:clear >nul 2>nul
echo Updating database...
"%PHPEXE%" artisan migrate --force >> start-log.txt 2>&1
rem Make public\storage a junction to storage\app\public (uploaded images)
rmdir /s /q "public\storage" >nul 2>nul
mklink /J "public\storage" "storage\app\public" >nul 2>nul

rem ---- Find this PC's address on the Wi-Fi / local network ----
set "LANIP="
for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notmatch '^(127\.|169\.254\.)' -and $_.InterfaceAlias -notmatch 'vEthernet|VirtualBox|VMware|Loopback|WSL' -and $_.PrefixOrigin -ne 'WellKnown' } | Sort-Object InterfaceMetric | Select-Object -First 1).IPAddress"`) do set "LANIP=%%i"
if "%LANIP%"=="" set "LANIP=YOUR-PC-IP"
>> "%LOG%" echo LAN IP: %LANIP%

echo.
echo  ==============================================================
echo   Falcons Academy is running on your local network
echo.
echo   On THIS computer:        http://127.0.0.1:%PORT%
echo   On a phone / laptop:     http://%LANIP%:%PORT%
echo     (the other device must be on the SAME Wi-Fi as this PC)
echo.
echo   If Windows asks about the firewall, tick "Private networks"
echo   and press "Allow access".
echo.
echo   Admin:  admin@falcons-organization.com  /  Admin12345
echo   Keep this window open. Close it (or Ctrl+C) to stop.
echo  ==============================================================
echo.

rem Open the browser 3 seconds after the server starts
start "" /b cmd /c "timeout /t 3 /nobreak >nul & start http://127.0.0.1:%PORT%"

>> "%LOG%" echo starting server...
pushd public
"%PHPEXE%" -S 0.0.0.0:%PORT% "%~dp0vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php" 2>> "%LOG%"
>> "%LOG%" echo server exited with code %errorlevel%
popd

echo.
echo  The server stopped. If there is an error above, send a screenshot of this window.
pause
