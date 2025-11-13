param(
    [string]$Root = $(Split-Path -Parent $MyInvocation.MyCommand.Path)
)

$ErrorActionPreference = 'Stop'
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

$venv = Join-Path $Root '.venv'
$requirements = Join-Path $Root 'requirements.txt'
$python = 'py'
$venvPython = Join-Path $venv 'Scripts\python.exe'
$steps = @(
    @{ Text = 'Python kurulumu doğrulanıyor'; Action = { & $python '--version' | Out-Null } },
    @{ Text = 'Sanal ortam hazırlanıyor'; Action = { if (-not (Test-Path $venv)) { & $python '-m' 'venv' $venv } } },
    @{ Text = 'pip güncelleniyor'; Action = { & $venvPython '-m' 'pip' 'install' '--upgrade' 'pip' | Out-Null } },
    @{ Text = 'Bağımlılıklar yükleniyor'; Action = { & $venvPython '-m' 'pip' 'install' '-r' $requirements } },
    @{ Text = 'Uygulama doğrulanıyor'; Action = { & $venvPython '-m' 'compileall' (Join-Path $Root 'app') | Out-Null } }
)
$progress.Maximum = $steps.Count

function Update-Status([int]$index, [string]$text) {
    $label.Text = "Adım $($index + 1)/$($steps.Count): $text"
    $progress.Value = [Math]::Min($index, $progress.Maximum)
    [System.Windows.Forms.Application]::DoEvents()
}

try {
    for ($i = 0; $i -lt $steps.Count; $i++) {
        $step = $steps[$i]
        Update-Status -index $i -text $step.Text
        & $step.Action
    }
    $label.Text = 'Kurulum tamamlandı'
    $progress.Value = $steps.Count
    [System.Windows.Forms.Application]::DoEvents()
    Start-Sleep -Seconds 2
}
catch {
    [System.Windows.Forms.MessageBox]::Show("Kurulum başarısız:\n" + $_.Exception.Message, 'Kurulum Hatası', [System.Windows.Forms.MessageBoxButtons]::OK, [System.Windows.Forms.MessageBoxIcon]::Error) | Out-Null
    $form.Close()
    exit 1
}
finally {
    $form.Close()
}
