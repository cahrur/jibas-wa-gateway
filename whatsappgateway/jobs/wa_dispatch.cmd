@echo off
setlocal
chcp 65001 >nul

set "JOBROOT=C:\xampp\htdocs\jibas\whatsappgateway\jobs"
set "SCRIPTROOT=C:\xampp\htdocs\jibas\whatsappgateway\script"

if not exist "%JOBROOT%\logs" mkdir "%JOBROOT%\logs"

pushd "%SCRIPTROOT%" || (
  echo [ERROR] Script folder tidak ditemukan: %SCRIPTROOT%>>"%JOBROOT%\logs\wa_dispatch.log"
  exit /b 3
)

"C:\PHP\php.exe" -v >nul 2>&1 || (
  echo [ERROR] PHP tidak ditemukan di C:\PHP\php.exe>>"%JOBROOT%\logs\wa_dispatch.log"
  popd & exit /b 4
)

echo [%date% %time%] START wa_dispatch>>"%JOBROOT%\logs\wa_dispatch.log"
"C:\PHP\php.exe" -d display_errors=1 -d error_reporting=32767 -f wa_dispatch.php >>"%JOBROOT%\logs\wa_dispatch.log" 2>&1
set "ec=%errorlevel%"
echo [%date% %time%] END (exit %ec%)>>"%JOBROOT%\logs\wa_dispatch.log"

popd
exit /b %ec%
