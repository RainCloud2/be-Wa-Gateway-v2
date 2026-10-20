param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('start', 'stop', 'restart', 'status')]
    [string]$Action,

    [Parameter(Mandatory = $true)]
    [string]$PhpPath,

    [Parameter(Mandatory = $true)]
    [string]$AppPath,

    [Parameter(Mandatory = $true)]
    [string]$Tries,

    [Parameter(Mandatory = $true)]
    [string]$RunnerPath
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

function Get-WorkerProcesses {
    $artisanPath = Join-Path $AppPath 'artisan'

    return @(
        Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue |
            Where-Object {
                $_.CommandLine -and
                $_.CommandLine -like "*$artisanPath*" -and
                $_.CommandLine -like '*queue:work*'
            } |
            ForEach-Object { Get-Process -Id $_.ProcessId -ErrorAction SilentlyContinue } |
            Where-Object { $_ }
    )
}

function Start-QueueWorker {
    $existing = Get-WorkerProcesses

    if ($existing.Count -gt 0) {
        return New-Result -Ok $true -Running $true -ProcessId $existing[0].Id -Message 'Queue worker sudah berjalan.'
    }

    if (-not (Test-Path -LiteralPath $PhpPath)) {
        return New-Result -Ok $false -Running $false -ProcessId $null -Message "PHP tidak ditemukan: $PhpPath"
    }

    if (-not (Test-Path -LiteralPath (Join-Path $AppPath 'artisan'))) {
        return New-Result -Ok $false -Running $false -ProcessId $null -Message "Artisan tidak ditemukan di: $AppPath"
    }

    if (-not (Test-Path -LiteralPath $RunnerPath)) {
        return New-Result -Ok $false -Running $false -ProcessId $null -Message "Runner tidak ditemukan: $RunnerPath"
    }

    $argument = "/c start `"`" /B `"powershell.exe`" -NoProfile -ExecutionPolicy Bypass -File `"$RunnerPath`" -PhpPath `"$PhpPath`" -AppPath `"$AppPath`" -Tries $Tries"

    Start-Process -FilePath 'cmd.exe' `
        -ArgumentList $argument `
        -WorkingDirectory $AppPath `
        -WindowStyle Hidden

    Start-Sleep -Milliseconds 900
    $processes = Get-WorkerProcesses

    if ($processes.Count -eq 0) {
        return New-Result -Ok $false -Running $false -ProcessId $null -Message 'Queue worker gagal dijalankan. Periksa log worker.'
    }

    return New-Result -Ok $true -Running $true -ProcessId $processes[0].Id -Message 'Queue worker berhasil dijalankan.'
}

function Stop-QueueWorker {
    $processes = Get-WorkerProcesses

    if ($processes.Count -eq 0) {
        return New-Result -Ok $true -Running $false -ProcessId $null -Message 'Tidak ada queue worker yang dikelola dashboard.'
    }

    foreach ($process in $processes) {
        Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
    }

    return New-Result -Ok $true -Running $false -ProcessId $null -Message 'Queue worker berhasil dihentikan.'
}

try {
    switch ($Action) {
        'start' {
            $result = Start-QueueWorker
        }
        'stop' {
            $result = Stop-QueueWorker
        }
        'restart' {
            $stopResult = Stop-QueueWorker

            if (-not $stopResult.ok) {
                $result = $stopResult
            }
            else {
                Start-Sleep -Milliseconds 500
                $result = Start-QueueWorker
            }
        }
        'status' {
            $processes = Get-WorkerProcesses

            if ($processes.Count -gt 0) {
                $result = New-Result -Ok $true -Running $true -ProcessId $processes[0].Id -Message 'Queue worker berjalan.'
            }
            else {
                $result = New-Result -Ok $true -Running $false -ProcessId $null -Message 'Queue worker berhenti.'
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
