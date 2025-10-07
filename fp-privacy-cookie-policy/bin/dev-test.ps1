Param(
    [switch]$SkipInstall
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Test-CommandExists {
    param(
        [Parameter(Mandatory=$true)][string]$Name
    )
    return [bool](Get-Command $Name -ErrorAction SilentlyContinue)
}

function Test-WingetAvailable {
    if (Test-CommandExists -Name 'winget') { return $true }
    Write-Host 'Winget non disponibile. Salto installazioni automatiche di sistema.' -ForegroundColor Yellow
    return $false
}

function Install-PhpIfMissing {
    if (Test-CommandExists -Name 'php') { return }
    if (-not (Test-WingetAvailable)) { throw 'PHP non trovato e winget assente. Installa PHP manualmente e riprova.' }
    Write-Host 'Installo PHP via winget...' -ForegroundColor Cyan
    winget install --id=PHP.PHP -e --source winget --accept-source-agreements --accept-package-agreements | Out-Null
}

function Install-ComposerIfMissing {
    if (Test-CommandExists -Name 'composer') { return }
    if (-not (Test-WingetAvailable)) { throw 'Composer non trovato e winget assente. Installa Composer manualmente e riprova.' }
    Write-Host 'Installo Composer via winget...' -ForegroundColor Cyan
    winget install --id=Composer.Composer -e --source winget --accept-source-agreements --accept-package-agreements | Out-Null
}

function Invoke-ComposerInstall {
    param([string]$Path)
    Push-Location $Path
    try {
        Write-Host 'Eseguo composer install (dev inclusi)...' -ForegroundColor Cyan
        $composer = Get-ComposerPath
        if ($composer -ne '') {
            & $composer install --no-interaction | Write-Host
        } else {
            $php = Get-PhpPath
            $phar = Join-Path $Path 'composer.phar'
            if (-not (Test-Path $phar)) {
                Write-Host 'Scarico composer.phar (fallback)...' -ForegroundColor Yellow
                Invoke-WebRequest -UseBasicParsing -Uri 'https://getcomposer.org/composer.phar' -OutFile $phar
            }
            & $php $phar install --no-interaction | Write-Host
        }
    } finally {
        Pop-Location
    }
}

function Invoke-PhpUnit {
    param([string]$Path)
    $phpunit = Join-Path $Path 'vendor\bin\phpunit'
    if (-not (Test-Path $phpunit)) { throw 'phpunit non trovato in vendor/bin. Assicurati che composer install sia andato a buon fine.' }
    Push-Location $Path
    try {
        Write-Host 'Eseguo test PHPUnit...' -ForegroundColor Cyan
        & $phpunit
        if ($LASTEXITCODE -ne 0) { throw "Test falliti con exit code $LASTEXITCODE" }
    } finally {
        Pop-Location
    }
}

# Percorso root del plugin (cartella padre rispetto a /bin)
$PluginRoot = Split-Path $PSScriptRoot -Parent

Write-Host "Plugin root: $PluginRoot" -ForegroundColor DarkGray

function Get-ComposerPath {
    $cmd = Get-Command composer -ErrorAction SilentlyContinue
    if ($cmd -and $cmd.Source) { return $cmd.Source }

    $candidates = @(
        Join-Path $Env:ProgramData 'ComposerSetup\bin\composer.exe'),
        (Join-Path $Env:LOCALAPPDATA 'ComposerSetup\bin\composer.exe'),
        (Join-Path $Env:APPDATA 'ComposerSetup\bin\composer.exe')
    foreach ($c in $candidates) { if (Test-Path $c) { return $c } }

    return ''
}

function Get-PhpPath {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd -and $cmd.Source) { return $cmd.Source }

    $phpDirs = @(
        'C:\\Program Files\\PHP',
        'C:\\Program Files (x86)\\PHP'
    )
    foreach ($dir in $phpDirs) {
        if (Test-Path $dir) {
            $phpExe = Get-ChildItem -Path $dir -Recurse -Filter 'php.exe' -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
            if ($phpExe) { return $phpExe }
        }
    }

    $winAppsUser = Join-Path $Env:LOCALAPPDATA 'Microsoft\\WindowsApps\\php.exe'
    if (Test-Path $winAppsUser) { return $winAppsUser }

    $scoopShim = Join-Path $Env:USERPROFILE 'scoop\\shims\\php.exe'
    if (Test-Path $scoopShim) { return $scoopShim }

    throw 'PHP non trovato nella sessione. Riapri PowerShell o installa PHP e riprova.'
}

if (-not $SkipInstall) {
    Install-PhpIfMissing
    Install-ComposerIfMissing
    Invoke-ComposerInstall -Path $PluginRoot
} else {
    Write-Host 'SkipInstall attivo: salto installazioni e composer install.' -ForegroundColor Yellow
}

Invoke-PhpUnit -Path $PluginRoot

Write-Host 'Completato.' -ForegroundColor Green

