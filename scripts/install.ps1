param(
    [string]$Root = $(Split-Path -Parent $MyInvocation.MyCommand.Path)
)

$ErrorActionPreference = 'Stop'

$logDir = Join-Path $Root 'logs'
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}
$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$logPath = Join-Path $logDir "install_$timestamp.log"
$latestLogPath = Join-Path $logDir 'install-latest.log'
$transcriptStarted = $false
try {
    Start-Transcript -Path $logPath -Append | Out-Null
    $transcriptStarted = $true
}
catch {
    Write-Warning "Günlük kaydı başlatılamadı: $($_.Exception.Message)"
}
Write-Host "Kurulum günlüğü: $logPath"

$form = $null
$label = $null
$progress = $null
$uiReady = $false

try {
    Add-Type -AssemblyName System.Windows.Forms
    Add-Type -AssemblyName System.Drawing
    [System.Windows.Forms.Application]::EnableVisualStyles()

    $form = New-Object System.Windows.Forms.Form
    $form.Text = 'Kurulum Yardımcısı'
    $form.Size = New-Object System.Drawing.Size(420,160)
    $form.StartPosition = 'CenterScreen'
    $form.Topmost = $true

    $label = New-Object System.Windows.Forms.Label
    $label.AutoSize = $false
    $label.Dock = 'Top'
    $label.Height = 60
    $label.TextAlign = 'MiddleCenter'
    $label.Font = New-Object System.Drawing.Font('Segoe UI',10,[System.Drawing.FontStyle]::Regular)
    $form.Controls.Add($label)

    $progress = New-Object System.Windows.Forms.ProgressBar
    $progress.Dock = 'Top'
    $progress.Style = 'Continuous'
    $form.Controls.Add($progress)

    $form.Shown.Add({ $form.Activate() })
    $form.Show()
    [System.Windows.Forms.Application]::DoEvents()
    $uiReady = $true

    $venv = Join-Path $Root '.venv'
    $requirements = Join-Path $Root 'requirements.txt'
    $python = 'py'
    $venvPython = Join-Path $venv 'Scripts\python.exe'
    $appPath = Join-Path $Root 'app'

    $steps = @(
        @{ Text = 'Python kurulumu doğrulanıyor'; Action = { & $python '--version' | Out-Null } },
        @{ Text = 'Sanal ortam hazırlanıyor'; Action = { if (-not (Test-Path $venv)) { & $python '-m' 'venv' $venv } } },
        @{ Text = 'pip güncelleniyor'; Action = { & $venvPython '-m' 'pip' 'install' '--upgrade' 'pip' | Out-Null } },
        @{ Text = 'Bağımlılıklar yükleniyor'; Action = { & $venvPython '-m' 'pip' 'install' '-r' $requirements } },
        @{ Text = 'Uygulama doğrulanıyor'; Action = { & $venvPython '-m' 'compileall' $appPath | Out-Null } }
    )
    $progress.Maximum = $steps.Count

    function Update-Status([int]$index, [string]$text, [switch]$Complete) {
        if ($null -ne $label) {
            $label.Text = "Adım $($index + 1)/$($steps.Count): $text"
        }
        if ($null -ne $progress) {
            $progress.Value = if ($Complete) {
                [Math]::Min($index + 1, $progress.Maximum)
            } else {
                [Math]::Min($index, $progress.Maximum)
            }
        }
        [System.Windows.Forms.Application]::DoEvents()
    }

    for ($i = 0; $i -lt $steps.Count; $i++) {
        $step = $steps[$i]
        Write-Host "[INFO] $($step.Text)"
        Update-Status -index $i -text $step.Text
        & $step.Action
        Update-Status -index $i -text $step.Text -Complete
        Write-Host "[OK] $($step.Text)"
    }

    if ($uiReady -and $null -ne $label) {
        $label.Text = 'Kurulum tamamlandı'
    }
    if ($null -ne $progress) {
        $progress.Value = $steps.Count
    }
    [System.Windows.Forms.Application]::DoEvents()
    Start-Sleep -Seconds 2
}
catch {
    $message = "Kurulum başarısız:`n$($_.Exception.Message)`nDetaylar: $logPath"
    Write-Error $message
    if ($uiReady) {
        [System.Windows.Forms.MessageBox]::Show($message, 'Kurulum Hatası', [System.Windows.Forms.MessageBoxButtons]::OK, [System.Windows.Forms.MessageBoxIcon]::Error) | Out-Null
    }
    exit 1
}
finally {
    if ($null -ne $form -and -not $form.IsDisposed) {
        $form.Close()
    }
    if ($transcriptStarted) {
        try { Stop-Transcript | Out-Null } catch { }
    }
    if (Test-Path $logPath) {
        Copy-Item -Path $logPath -Destination $latestLogPath -Force
    }
}
