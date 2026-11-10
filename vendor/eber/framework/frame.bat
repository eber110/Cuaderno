@echo off
setlocal
chcp 65001 >nul

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP no se encuentra en el PATH de Windows.
    echo Asegurese de tener PHP instalado y agregado a las variables de entorno.
    exit /b 1
)

php "%~dp0Base\ScriptComposer\FrameCli.php" %*
exit /b %ERRORLEVEL%
