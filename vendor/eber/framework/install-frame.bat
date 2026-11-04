@echo off
setlocal
title Instalador de Frame CLI
chcp 65001 >nul

echo ======================================================
echo  FRAME CLI - REGISTRO EN WINDOWS
echo ======================================================
echo.

set "CURRENT_DIR=%~dp0"
if "%CURRENT_DIR:~-1%"=="\" set "CURRENT_DIR=%CURRENT_DIR:~0,-1%"

echo Directorio de Frame:
echo   %CURRENT_DIR%
echo.
echo Registrando en PATH de Windows...

powershell -NoProfile -ExecutionPolicy Bypass -Command "$dir = $env:CURRENT_DIR; $u = [Environment]::GetEnvironmentVariable('Path', 'User'); $p = $u -split ';' | Where-Object { $_.Trim() -ne '' }; if ($p -notcontains $dir) { [Environment]::SetEnvironmentVariable('Path', (($p + $dir) -join ';'), 'User'); Write-Host '[OK] Ruta agregada exitosamente al PATH del usuario.' -ForegroundColor Green } else { Write-Host '[OK] La ruta ya estaba registrada en el PATH.' -ForegroundColor Cyan }; $lb = Join-Path $env:USERPROFILE '.local\bin'; if (Test-Path $lb) { Copy-Item -Path (Join-Path $dir 'frame.bat') -Destination (Join-Path $lb 'frame.bat') -Force; Copy-Item -Path (Join-Path $dir 'frame.bat') -Destination (Join-Path $lb 'frame.ps1') -Force }"

echo.
echo ======================================================
echo  INSTALACION COMPLETADA CON EXITO
echo ======================================================
echo.
echo Ya puedes abrir cualquier terminal de PowerShell o CMD
echo y escribir desde cualquier carpeta:
echo.
echo    frame new mi-proyecto
echo    frame new cuaderno "G:\Mi unidad\Proyectos\cuaderno"
echo.
echo ======================================================
echo.
pause
