@echo off
setlocal
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0install.ps1"
set "connector_exit=%ERRORLEVEL%"
if not "%connector_exit%"=="0" pause
exit /b %connector_exit%

