@echo off
setlocal
set SCRIPT_DIR=%~dp0

if "%1"=="new" goto :new_project
if "%1"=="new-project" goto :new_project
if "%1"=="package" goto :deploy_package
if "%1"=="deploy" goto :deploy_run
if "%1"=="min" goto :min_script
if "%1"=="" goto :help

rem Comandos make: (ej: frame make:controller User)
echo %1 | findstr /b /c:"make:" >nul
if %errorlevel%==0 goto :make_cli

rem Por defecto pasar al generador
php "%SCRIPT_DIR%Base\ScriptComposer\NewProject.php" %*
goto :eof

:new_project
shift
php "%SCRIPT_DIR%Base\ScriptComposer\NewProject.php" %*
goto :eof

:deploy_package
shift
php "%SCRIPT_DIR%Base\ScriptComposer\DeployPackage.php" package %*
goto :eof

:deploy_run
shift
php "%SCRIPT_DIR%Base\ScriptComposer\DeployPackage.php" deploy %*
goto :eof

:min_script
shift
php "%SCRIPT_DIR%Base\ScriptComposer\MinJsCss.php" %*
goto :eof

:make_cli
set CMD_NAME=%1
shift
set TYPE=%CMD_NAME:~5%
php "%SCRIPT_DIR%Base\ScriptComposer\MakeCli.php" %TYPE% %*
goto :eof

:help
echo ======================================================
echo  FRAME CLI - COMANDOS DISPONIBLES
echo ======================================================
echo   frame new [nombre] [directorio]   - Crear un nuevo proyecto
echo   frame package                     - Generar ZIP de despliegue inicial
echo   frame deploy [--all]              - Desplegar cambios al servidor
echo   frame min                         - Compilar JIT CSS y JS
echo   frame make:controller [Nombre]    - Crear controlador
echo   frame make:model [Nombre]         - Crear modelo
echo   frame make:component [Nombre]     - Crear componente
echo   frame make:middleware [Nombre]    - Crear middleware
echo ======================================================
goto :eof
