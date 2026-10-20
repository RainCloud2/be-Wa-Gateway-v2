param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('start', 'stop', 'restart', 'status')]
    [string]$Action,

    [Parameter(Mandatory = $true)]
    [string]$GatewayPath,

    [Parameter(Mandatory = $true)]
    [string]$NodePath,

    [Parameter(Mandatory = $true)]
    [string]$Script,

    [Parameter(Mandatory = $true)]
    [string]$PidFile,

    [Parameter(Mandatory = $true)]
    [string]$OutLog,

    [Parameter(Mandatory = $true)]
    [string]$ErrLog
)

$ErrorActionPreference = 'Stop'

function New-Result {
    param(
        [bool]$Ok,
        [bool]$Running,
        [Nullable[int]]$ProcessId,
        [string]$Message
    )

    return [pscustomobject]@{
        ok = $Ok
        running = $Running
        pid = $ProcessId
        message = $Message
    }
}

function Get-ManagedProcess {
    if (-not (Test-Path -LiteralPath $PidFile)) {
        return $null
    }

    $raw = Get-Content -LiteralPath $PidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    $processId = 0

    if ($raw -and [int]::TryParse([string]$raw, [ref]$processId)) {
        $process = Get-Process -Id $processId -ErrorAction SilentlyContinue

        if ($process -and $process.ProcessName -eq 'node') {
            return $process
        }
    }

    Remove-Item -LiteralPath $PidFile -ErrorAction SilentlyContinue

    return $null
}

function Start-Gateway {
    $existing = Get-ManagedProcess

    if ($existing) {
        return New-Result -Ok $true -Running $true -ProcessId $existing.Id -Message 'Gateway sudah berjalan.'
    }

    if (-not (Test-Path -LiteralPath $GatewayPath)) {
        return New-Result -Ok $false -Running $false -ProcessId $null -Message "Folder gateway tidak ditemukan: $GatewayPath"
    }

    if (-not (Test-Path -LiteralPath (Join-Path $GatewayPath $Script))) {
        return New-Result -Ok $false -Running $false -ProcessId $null -Message "Script gateway tidak ditemukan: $Script"
    }

    if (-not (Test-Path -LiteralPath $NodePath)) {
        return New-Result -Ok $false -Running $false -ProcessId $null -Message "Node.js tidak ditemukan: $NodePath"
    }

    $pidDirectory = Split-Path -Parent $PidFile
    $logDirectory = Split-Path -Parent $OutLog
    $errorLogDirectory = Split-Path -Parent $ErrLog

    if ($pidDirectory) {
        New-Item -ItemType Directory -Path $pidDirectory -Force | Out-Null
    }

    if ($logDirectory) {
        New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
    }

    if ($errorLogDirectory) {
        New-Item -ItemType Directory -Path $errorLogDirectory -Force | Out-Null
    }

    $process = Start-Process -FilePath $NodePath `
        -ArgumentList @($Script) `
        -WorkingDirectory $GatewayPath `
        -WindowStyle Hidden `
        -RedirectStandardOutput $OutLog `
        -RedirectStandardError $ErrLog `
        -PassThru

    Set-Content -LiteralPath $PidFile -Value $process.Id
    Start-Sleep -Milliseconds 700
    $process.Refresh()

    if ($process.HasExited) {
        Remove-Item -LiteralPath $PidFile -ErrorAction SilentlyContinue

        return New-Result -Ok $false -Running $false -ProcessId $null -Message 'Gateway gagal dijalankan. Periksa log error gateway.'
    }

    return New-Result -Ok $true -Running $true -ProcessId $process.Id -Message 'Gateway berhasil dijalankan.'
}

function Stop-Gateway {
    $process = Get-ManagedProcess

    if (-not $process) {
        return New-Result -Ok $true -Running $false -ProcessId $null -Message 'Tidak ada proses gateway yang dikelola dashboard.'
    }

    Stop-Process -Id $process.Id -Force
    Remove-Item -LiteralPath $PidFile -ErrorAction SilentlyContinue

    return New-Result -Ok $true -Running $false -ProcessId $null -Message 'Gateway berhasil dihentikan.'
}

try {
    switch ($Action) {
        'start' {
            $result = Start-Gateway
        }
        'stop' {
            $result = Stop-Gateway
        }
        'restart' {
            $stopResult = Stop-Gateway

            if (-not $stopResult.ok) {
                $result = $stopResult
            }
            else {
                Start-Sleep -Milliseconds 500
                $result = Start-Gateway
            }
        }
        'status' {
            $process = Get-ManagedProcess

            if ($process) {
                $result = New-Result -Ok $true -Running $true -ProcessId $process.Id -Message 'Gateway berjalan.'
            }
            else {
                $result = New-Result -Ok $true -Running $false -ProcessId $null -Message 'Gateway berhenti.'
            }
        }
    }
}
catch {
    $result = New-Result -Ok $false -Running $false -ProcessId $null -Message $_.Exception.Message
}

$result | ConvertTo-Json -Compress

if (-not $result.ok) {
    exit 1
}
