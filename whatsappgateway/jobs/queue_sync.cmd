@echo off
setlocal
chcp 65001 >nul

set "JOBROOT=C:\xampp\htdocs\jibas\whatsappgateway\jobs"
set "SCRIPTROOT=C:\xampp\htdocs\jibas\whatsappgateway\script"

if not exist "%JOBROOT%\logs" mkdir "%JOBROOT%\logs"

pushd "%SCRIPTROOT%" || (
  echo [ERROR] Script folder tidak ditemukan: %SCRIPTROOT%>>"%JOBROOT%\logs\queue_sync.log"
  exit /b 3
)

"C:\PHP\php.exe" -v >nul 2>&1 || (
  echo [ERROR] PHP tidak ditemukan di C:\PHP\php.exe>>"%JOBROOT%\logs\queue_sync.log"
  popd & exit /b 4
)

echo [%date% %time%] START queue_sync>>"%JOBROOT%\logs\queue_sync.log"
"C:\PHP\php.exe" -d display_errors=1 -d error_reporting=32767 -f queue_sync.php >>"%JOBROOT%\logs\queue_sync.log" 2>&1
set "ec=%errorlevel%"
echo [%date% %time%] END (exit %ec%)>>"%JOBROOT%\logs\queue_sync.log"

popd
exit /b %ec%
