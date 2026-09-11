@echo off
setlocal
set "temporary_uninstaller=%TEMP%\SupportPortalConnector-uninstall-%RANDOM%-%RANDOM%.ps1"
copy /y "%~dp0uninstall.ps1" "%temporary_uninstaller%" >nul
if errorlevel 1 (
  echo Unable to prepare the uninstaller.
  pause
  exit /b 1
)
cd /d "%TEMP%"
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%temporary_uninstaller%"
set "connector_exit=%ERRORLEVEL%"
del /q "%temporary_uninstaller%" >nul 2>&1
if not "%connector_exit%"=="0" pause
exit /b %connector_exit%

