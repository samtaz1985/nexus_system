@echo off
echo ========================================
echo   Sincronizando Nexus System (Git)
echo ========================================
cd /d %~dp0
git add .
set /p mensaje="Ingresa el mensaje del commit: "
git commit -m "%mensaje%"
git push origin main
echo ========================================
echo   ¡Actualizacion completada con exito!
echo ========================================
pause