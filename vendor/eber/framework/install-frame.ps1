# Instalador de Frame CLI para PowerShell y CMD
[CmdletBinding()]
param()

$frameworkDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " 🚀 FRAME CLI - INSTALADOR Y REGISTRO EN WINDOWS" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Directorio del Framework:"
Write-Host "  $frameworkDir" -ForegroundColor Yellow
Write-Host ""
Write-Host "Registrando en PATH de Windows..."

# 1. Agregar al PATH del usuario actual si no está
$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
$pathParts = $userPath -split ';' | Where-Object { $_.Trim() -ne '' }

if ($pathParts -notcontains $frameworkDir) {
    $newPath = (($pathParts + $frameworkDir) -join ';')
    [Environment]::SetEnvironmentVariable('Path', $newPath, 'User')
    Write-Host "[OK] Ruta agregada exitosamente al PATH del usuario." -ForegroundColor Green
} else {
    Write-Host "[OK] La ruta ya estaba registrada en el PATH." -ForegroundColor Cyan
}

# 2. Si existe .local\bin en el perfil de usuario, actualizar shims compatibles
$localBin = Join-Path $env:USERPROFILE '.local\bin'
if (Test-Path $localBin) {
    Set-Content -Path (Join-Path $localBin 'frame.bat') -Value "@echo off`r`n`"$frameworkDir\frame.bat`" %*" -Encoding ASCII
    Set-Content -Path (Join-Path $localBin 'frame.ps1') -Value "& `"$frameworkDir\frame.ps1`" @args" -Encoding UTF8
    Write-Host "[OK] Accesos directos frame actualizados en $localBin" -ForegroundColor Green
}

# 3. Actualizar PATH de la sesión actual
if ($env:Path -split ';' -notcontains $frameworkDir) {
    $env:Path = "$env:Path;$frameworkDir"
}

Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " 🎉 INSTALACION COMPLETADA CON EXITO" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Ya puedes ejecutar desde cualquier terminal de PowerShell o CMD:"
Write-Host ""
Write-Host "   frame help                         - Ver todos los comandos disponibles" -ForegroundColor Yellow
Write-Host "   frame new mi-proyecto              - Crear un nuevo proyecto" -ForegroundColor Yellow
Write-Host "   frame min                          - Compilar JIT CSS y minificar JS" -ForegroundColor Yellow
Write-Host "   frame deploy                       - Desplegar cambios al servidor" -ForegroundColor Yellow
Write-Host "   frame make:controller Usuario      - Generar controlador" -ForegroundColor Yellow
Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
