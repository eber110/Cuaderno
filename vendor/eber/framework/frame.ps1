[CmdletBinding()]
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Arguments
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Verificar disponibilidad de PHP
$phpPath = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpPath) {
    Write-Host "[ERROR] PHP no se encuentra en el PATH de Windows." -ForegroundColor Red
    Write-Host "Asegúrese de tener PHP instalado y agregado a las variables de entorno." -ForegroundColor Yellow
    exit 1
}

$cliScript = Join-Path $scriptDir "Base\ScriptComposer\FrameCli.php"

if (-not (Test-Path $cliScript)) {
    Write-Host "[ERROR] No se encontró el script de Frame CLI en: $cliScript" -ForegroundColor Red
    exit 1
}

if ($Arguments) {
    & php $cliScript @Arguments
} else {
    & php $cliScript
}

exit $LASTEXITCODE
