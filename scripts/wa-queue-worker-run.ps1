param(
    [Parameter(Mandatory = $true)]
    [string]$PhpPath,

    [Parameter(Mandatory = $true)]
    [string]$AppPath,

    [Parameter(Mandatory = $true)]
    [string]$Tries
)

$ErrorActionPreference = 'Continue'

$logDirectory = Join-Path $AppPath 'storage\logs'
$outputLog = Join-Path $logDirectory 'wa-queue-worker.out.log'
$errorLog = Join-Path $logDirectory 'wa-queue-worker.err.log'

if (-not (Test-Path -LiteralPath $logDirectory)) {
    New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
}

Set-Location -LiteralPath $AppPath

& $PhpPath (Join-Path $AppPath 'artisan') queue:work "--tries=$Tries" --timeout=90 --sleep=1 1>> $outputLog 2>> $errorLog
