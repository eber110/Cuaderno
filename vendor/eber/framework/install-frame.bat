@echo off
setlocal
title Instalador de Frame CLI
chcp 65001 >nul

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install-frame.ps1"

echo.
pause
