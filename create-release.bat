@echo off
REM Script per creare tag e release su Git

echo ========================================
echo CREAZIONE TAG GIT PER FP-PRIVACY
echo ========================================
echo.

REM Versione corrente
set VERSION=0.2.0

echo Versione corrente: %VERSION%
echo.

REM Cambia directory alla cartella LAB
cd "C:\Users\franc\OneDrive\Desktop\FP-Privacy-and-Cookie-Policy-1"

echo Directory: %CD%
echo.

REM Verifica stato Git
echo Verifico stato Git...
git status
echo.

REM Chiedi conferma
set /p CONFIRM="Vuoi creare il tag v%VERSION% e fare push su GitHub? (S/N): "
if /i not "%CONFIRM%"=="S" (
    echo Operazione annullata.
    pause
    exit /b
)

echo.
echo Creo tag v%VERSION%...
git tag -a v%VERSION% -m "Release v%VERSION% - Italiano di Default"

echo.
echo Faccio push del tag su GitHub...
git push origin v%VERSION%

echo.
echo ========================================
echo TAG CREATO CON SUCCESSO!
echo ========================================
echo.
echo Ora vai su GitHub e crea la Release:
echo https://github.com/franpass87/FP-Privacy-and-Cookie-Policy/releases/new
echo.
echo Seleziona il tag: v%VERSION%
echo Title: v%VERSION% - Italiano di Default
echo.
echo Poi il plugin fp-git-updater vedra' la versione corretta!
echo.

pause

