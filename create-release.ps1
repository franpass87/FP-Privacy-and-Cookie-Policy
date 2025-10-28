# Script PowerShell per creare tag e release su Git

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CREAZIONE TAG GIT PER FP-PRIVACY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Versione corrente
$VERSION = "0.1.2"

Write-Host "Versione corrente: $VERSION" -ForegroundColor Yellow
Write-Host ""

# Cambia directory alla cartella LAB
Set-Location "C:\Users\franc\OneDrive\Desktop\FP-Privacy-and-Cookie-Policy-1"

Write-Host "Directory: $PWD" -ForegroundColor Green
Write-Host ""

# Verifica stato Git
Write-Host "Verifico stato Git..." -ForegroundColor Cyan
git status
Write-Host ""

# Chiedi conferma
$CONFIRM = Read-Host "Vuoi creare il tag v$VERSION e fare push su GitHub? (S/N)"
if ($CONFIRM -ne "S" -and $CONFIRM -ne "s") {
    Write-Host "Operazione annullata." -ForegroundColor Red
    Read-Host "Premi ENTER per uscire"
    exit
}

Write-Host ""
Write-Host "Aggiungo tutte le modifiche..." -ForegroundColor Cyan
git add .

Write-Host ""
Write-Host "Creo commit..." -ForegroundColor Cyan
git commit -m "Release v$VERSION - Italiano di Default

- Testi banner in italiano di default
- Cookie Policy e Privacy Policy generate in italiano
- Preview banner multilingua funzionante
- Sezione Palette migliorata
- Fix banner preview per ogni lingua
- Compatibilità completa con FP-Multilanguage"

Write-Host ""
Write-Host "Creo tag v$VERSION..." -ForegroundColor Cyan
git tag -a "v$VERSION" -m "Release v$VERSION - Italiano di Default"

Write-Host ""
Write-Host "Faccio push su GitHub (main branch)..." -ForegroundColor Cyan
git push origin main

Write-Host ""
Write-Host "Faccio push del tag su GitHub..." -ForegroundColor Cyan
git push origin "v$VERSION"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "TAG CREATO CON SUCCESSO!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Ora vai su GitHub e crea la Release:" -ForegroundColor Yellow
Write-Host "https://github.com/franpass87/FP-Privacy-and-Cookie-Policy/releases/new" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Seleziona il tag: v$VERSION" -ForegroundColor White
Write-Host "2. Title: v$VERSION - Italiano di Default" -ForegroundColor White
Write-Host "3. Descrivi le modifiche" -ForegroundColor White
Write-Host "4. Pubblica la release" -ForegroundColor White
Write-Host ""
Write-Host "Poi il plugin fp-git-updater vedra' la versione corretta!" -ForegroundColor Green
Write-Host ""

Read-Host "Premi ENTER per uscire"

