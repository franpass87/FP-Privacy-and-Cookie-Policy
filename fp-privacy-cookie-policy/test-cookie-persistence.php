<?php
/**
 * Test Cookie Persistence
 * Verifica che i cookie vengano salvati correttamente
 */

// Carica WordPress
require_once __DIR__ . '/../../../../wp-load.php';

// Header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Cookie Persistence - FP Privacy</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        code { background: #f0f0f0; padding: 2px 5px; }
        pre { background: #f9f9f9; padding: 15px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Test Cookie Persistence - FP Privacy</h1>
    
    <div id="results">
        <h2>Test Salvataggio Cookie</h2>
        <button onclick="testSetCookie()">1. Salva Cookie di Test</button>
        <button onclick="testReadCookie()">2. Leggi Cookie</button>
        <button onclick="testLocalStorage()">3. Test localStorage</button>
        <button onclick="clearAllData()">4. Pulisci Tutto</button>
        <div id="output"></div>
    </div>

    <hr>
    
    <div>
        <h2>Cookie Attivi</h2>
        <pre id="current-cookies"><?php echo esc_html($_SERVER['HTTP_COOKIE'] ?? 'Nessun cookie'); ?></pre>
    </div>

    <hr>

    <div>
        <h2>Debug Console</h2>
        <pre id="console-log"></pre>
    </div>

    <script>
        var output = document.getElementById('output');
        var consoleLog = document.getElementById('console-log');

        function log(msg) {
            console.log(msg);
            consoleLog.textContent += msg + '\n';
        }

        function testSetCookie() {
            output.innerHTML = '<h3>Test Salvataggio Cookie</h3>';
            
            var cookieName = 'fp_consent_state_id';
            var consentId = 'test_' + Date.now();
            var revision = 1;
            var expires = new Date();
            expires.setTime(expires.getTime() + (180 * 24 * 60 * 60 * 1000));
            
            var cookieValue = consentId + '|' + revision;
            var cookieString = cookieName + '=' + cookieValue + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
            
            // Aggiungi Secure se HTTPS
            if (window.location.protocol === 'https:') {
                cookieString += '; Secure';
            }
            
            log('Impostando cookie: ' + cookieString);
            document.cookie = cookieString;
            
            // Verifica immediata
            setTimeout(function() {
                var parts = document.cookie.split(';');
                var found = false;
                
                for (var i = 0; i < parts.length; i++) {
                    var cookie = parts[i].trim();
                    if (cookie.indexOf(cookieName + '=') === 0) {
                        found = true;
                        output.innerHTML += '<p class="success">✅ Cookie salvato: ' + cookie + '</p>';
                        log('Cookie trovato: ' + cookie);
                        break;
                    }
                }
                
                if (!found) {
                    output.innerHTML += '<p class="error">❌ Cookie NON salvato!</p>';
                    log('ERRORE: Cookie non trovato nei cookie del browser');
                }
                
                document.getElementById('current-cookies').textContent = document.cookie || 'Nessun cookie';
            }, 100);
        }

        function testReadCookie() {
            output.innerHTML = '<h3>Test Lettura Cookie</h3>';
            
            var cookieName = 'fp_consent_state_id';
            var name = cookieName + '=';
            var parts = document.cookie ? document.cookie.split(';') : [];
            var found = false;
            
            log('Cercando cookie: ' + cookieName);
            log('Cookie totali: ' + document.cookie);
            
            for (var i = 0; i < parts.length; i++) {
                var cookie = parts[i].trim();
                if (cookie.indexOf(name) === 0) {
                    var value = cookie.substring(name.length);
                    var segments = value.split('|');
                    
                    output.innerHTML += '<p class="success">✅ Cookie trovato!</p>';
                    output.innerHTML += '<p>Valore completo: <code>' + value + '</code></p>';
                    output.innerHTML += '<p>Consent ID: <code>' + segments[0] + '</code></p>';
                    output.innerHTML += '<p>Revision: <code>' + (segments[1] || 'N/A') + '</code></p>';
                    
                    log('Cookie letto con successo: ' + value);
                    found = true;
                    break;
                }
            }
            
            if (!found) {
                output.innerHTML += '<p class="error">❌ Cookie non trovato!</p>';
                log('Cookie non trovato');
            }
        }

        function testLocalStorage() {
            output.innerHTML = '<h3>Test localStorage</h3>';
            
            try {
                if (!window.localStorage) {
                    output.innerHTML += '<p class="error">localStorage non disponibile</p>';
                    return;
                }
                
                var cookieName = 'fp_consent_state_id';
                var consentId = 'test_ls_' + Date.now();
                var revision = 1;
                var cookieValue = consentId + '|' + revision;
                
                localStorage.setItem(cookieName, cookieValue);
                log('localStorage salvato: ' + cookieValue);
                
                var retrieved = localStorage.getItem(cookieName);
                if (retrieved === cookieValue) {
                    output.innerHTML += '<p class="success">✅ localStorage funziona correttamente!</p>';
                    output.innerHTML += '<p>Valore: <code>' + retrieved + '</code></p>';
                    log('localStorage letto con successo: ' + retrieved);
                } else {
                    output.innerHTML += '<p class="error">❌ Errore lettura localStorage</p>';
                    log('Errore: valore letto diverso da quello salvato');
                }
            } catch (error) {
                output.innerHTML += '<p class="error">❌ Errore: ' + error.message + '</p>';
                log('Errore localStorage: ' + error.message);
            }
        }

        function clearAllData() {
            output.innerHTML = '<h3>Pulizia Dati</h3>';
            
            // Cancella cookie
            document.cookie = 'fp_consent_state_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            log('Cookie cancellato');
            
            // Cancella localStorage
            try {
                if (window.localStorage) {
                    localStorage.removeItem('fp_consent_state_id');
                    log('localStorage cancellato');
                }
            } catch (error) {
                log('Errore cancellazione localStorage: ' + error.message);
            }
            
            output.innerHTML += '<p class="success">✅ Dati cancellati</p>';
            document.getElementById('current-cookies').textContent = document.cookie || 'Nessun cookie';
        }

        // Log iniziale
        log('Test inizializzato');
        log('Protocol: ' + window.location.protocol);
        log('Hostname: ' + window.location.hostname);
    </script>
</body>
</html>

