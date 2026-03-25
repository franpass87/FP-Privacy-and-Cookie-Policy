#Requires -Version 5.1
<#
.SYNOPSIS
    Smoke test HTTP/HTTPS e REST fp-privacy su un sito WordPress locale (es. Local by Flywheel).

.DESCRIPTION
    Non richiede mysqli nel PHP della shell: usa solo Invoke-WebRequest / Invoke-RestMethod.
    Per HTTPS con certificato non attendibile, usare -SkipTls (PowerShell 7+).

.PARAMETER SiteUrl
    Base URL del sito (default: http://fp-development.local).

.PARAMETER SkipTls
    Se impostato, le richieste HTTPS usano -SkipCertificateCheck (PS 7+).
#>
param(
    [string] $SiteUrl = "http://fp-development.local",
    [switch] $SkipTls
)

$ErrorActionPreference = "Stop"
function Test-Url {
    param([string]$Uri, [int[]]$Expected = @(200))
    try {
        $params = @{ Uri = $Uri; UseBasicParsing = $true; TimeoutSec = 20 }
        if ($Uri -match '^https:' -and $SkipTls -and $PSVersionTable.PSVersion.Major -ge 7) {
            $params['SkipCertificateCheck'] = $true
        }
        $r = Invoke-WebRequest @params
        $ok = $Expected -contains $r.StatusCode
        [pscustomobject]@{ Url = $Uri; Status = $r.StatusCode; Ok = $ok }
    } catch {
        $code = $null
        if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
        $ok = ($null -ne $code) -and ($Expected -contains $code)
        [pscustomobject]@{ Url = $Uri; Status = $code; Ok = $ok; Error = $(if ($ok) { $null } else { $_.Exception.Message }) }
    }
}

Write-Host "=== FP Privacy - verify-local ($SiteUrl) ===" -ForegroundColor Cyan

$results = @()
$results += Test-Url -Uri "$SiteUrl/"
$results += Test-Url -Uri "$SiteUrl/wp-json/"
$results += Test-Url -Uri "$SiteUrl/wp-json/fp-privacy/v1/" -Expected @(200)
$results += Test-Url -Uri "$SiteUrl/wp-json/fp-privacy/v1/consent/summary" -Expected @(200, 401)

# Pagine policy (slug canonici tipici; 404 = FAIL se usi altri permalink).
$results += Test-Url -Uri "$SiteUrl/privacy-policy/" -Expected @(200)
$results += Test-Url -Uri "$SiteUrl/cookie-policy/" -Expected @(200)

# HTTPS solo con -SkipTls (PS 7+), altrimenti il certificato locale fallisce spesso.
if ($SkipTls -and $SiteUrl -match '^http:') {
    $httpsBase = $SiteUrl -replace '^http:', 'https:'
    $results += Test-Url -Uri "$httpsBase/" -Expected @(200)
}

$fail = $false
foreach ($row in $results) {
    if ($row.Ok) {
        $mark = "[OK]"
    } else {
        $mark = "[FAIL]"
        $fail = $true
    }
    $st = if ($null -ne $row.Status) { $row.Status } else { "?" }
    Write-Host "$mark $st $($row.Url)"
    if ($row.Error) { Write-Host "      $($row.Error)" -ForegroundColor DarkYellow }
}

if ($fail) {
    Write-Host ""
    Write-Host "Alcuni check sono falliti. Se HTTPS fallisce, prova HTTP o -SkipTls con PS 7+." -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "Tutti i check richiesti sono passati." -ForegroundColor Green
exit 0
