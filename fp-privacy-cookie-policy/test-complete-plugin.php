<?php
/**
 * Test Completo Plugin FP Privacy & Cookie Policy
 * Verifica tutte le funzionalità e genera un report
 */

// Carica WordPress
require_once __DIR__ . '/../../../../wp-load.php';

// Header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Completo - FP Privacy & Cookie Policy</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #333; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #0073aa; margin-top: 30px; }
        .test-section { 
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #46b450; font-weight: bold; }
        .error { color: #dc3232; font-weight: bold; }
        .warning { color: #ffb900; font-weight: bold; }
        .info { color: #0073aa; }
        code { 
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre { 
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #0073aa;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-card {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-align: center;
        }
        .summary-card h3 {
            margin: 0;
            font-size: 36px;
        }
        .summary-card p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <h1>🔍 Test Completo - FP Privacy & Cookie Policy</h1>
    <p><strong>Data Test:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>

    <?php
    $results = [];
    $total_tests = 0;
    $passed_tests = 0;
    $failed_tests = 0;
    $warnings = 0;

    /**
     * Helper function per test
     */
    function run_test($name, $callback) {
        global $results, $total_tests, $passed_tests, $failed_tests, $warnings;
        
        $total_tests++;
        try {
            $result = $callback();
            if ($result['status'] === 'success') {
                $passed_tests++;
            } elseif ($result['status'] === 'warning') {
                $warnings++;
            } else {
                $failed_tests++;
            }
            $results[] = array_merge(['name' => $name], $result);
        } catch (Exception $e) {
            $failed_tests++;
            $results[] = [
                'name' => $name,
                'status' => 'error',
                'message' => 'Eccezione: ' . $e->getMessage()
            ];
        }
    }

    // ======================================
    // TEST 1: Verifica Installazione
    // ======================================
    run_test('Plugin Installato', function() {
        if (defined('FP_PRIVACY_VERSION')) {
            return [
                'status' => 'success',
                'message' => 'Plugin installato correttamente',
                'details' => 'Versione: ' . FP_PRIVACY_VERSION
            ];
        }
        return [
            'status' => 'error',
            'message' => 'Costante FP_PRIVACY_VERSION non definita'
        ];
    });

    // TEST 2: Verifica Autoload Composer
    run_test('Autoload Composer', function() {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            return [
                'status' => 'success',
                'message' => 'Autoload Composer presente'
            ];
        }
        return [
            'status' => 'warning',
            'message' => 'Autoload Composer non trovato (opzionale)'
        ];
    });

    // TEST 3: Verifica File di Traduzione
    run_test('File di Traduzione', function() {
        $lang_dir = __DIR__ . '/languages/';
        $files = [
            'fp-privacy-it_IT.po' => 'Italiano',
            'fp-privacy-it_IT.mo' => 'Italiano (compilato)',
            'fp-privacy-en_US.po' => 'Inglese',
            'fp-privacy-en_US.mo' => 'Inglese (compilato)',
            'fp-privacy.pot' => 'Template'
        ];
        
        $missing = [];
        foreach ($files as $file => $desc) {
            if (!file_exists($lang_dir . $file)) {
                $missing[] = "$desc ($file)";
            }
        }
        
        if (empty($missing)) {
            return [
                'status' => 'success',
                'message' => 'Tutti i file di traduzione presenti (' . count($files) . ' file)'
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'File di traduzione mancanti: ' . implode(', ', $missing)
        ];
    });

    // TEST 4: Verifica Asset (CSS e JS)
    run_test('Asset Frontend', function() {
        $assets_dir = __DIR__ . '/assets/';
        $required = [
            'css/banner.css' => 'CSS Banner',
            'js/banner.js' => 'JavaScript Banner',
            'js/consent-mode.js' => 'Google Consent Mode'
        ];
        
        $missing = [];
        $sizes = [];
        foreach ($required as $file => $desc) {
            if (file_exists($assets_dir . $file)) {
                $size = filesize($assets_dir . $file);
                $sizes[$desc] = number_format($size / 1024, 2) . ' KB';
            } else {
                $missing[] = $desc;
            }
        }
        
        if (empty($missing)) {
            return [
                'status' => 'success',
                'message' => 'Tutti gli asset presenti',
                'details' => implode(', ', array_map(function($k, $v) {
                    return "$k: $v";
                }, array_keys($sizes), $sizes))
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Asset mancanti: ' . implode(', ', $missing)
        ];
    });

    // TEST 5: Verifica Classi PHP Principali
    run_test('Classi PHP', function() {
        $classes = [
            'FP\\Privacy\\Plugin' => 'Plugin principale',
            'FP\\Privacy\\Frontend\\Banner' => 'Banner frontend',
            'FP\\Privacy\\Consent\\ConsentState' => 'Gestione stato consenso',
            'FP\\Privacy\\Utils\\Options' => 'Gestione opzioni'
        ];
        
        $missing = [];
        foreach ($classes as $class => $desc) {
            if (!class_exists($class)) {
                $missing[] = "$desc ($class)";
            }
        }
        
        if (empty($missing)) {
            return [
                'status' => 'success',
                'message' => 'Tutte le classi PHP caricate (' . count($classes) . ' classi)'
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Classi mancanti: ' . implode(', ', $missing)
        ];
    });

    // TEST 6: Verifica Opzioni Plugin
    run_test('Opzioni Plugin', function() {
        $options = get_option('fp_privacy_plugin_options', []);
        
        if (empty($options)) {
            return [
                'status' => 'warning',
                'message' => 'Opzioni plugin non ancora configurate'
            ];
        }
        
        $required_keys = ['consent_lifetime', 'revision', 'enabled'];
        $has_all = true;
        foreach ($required_keys as $key) {
            if (!isset($options[$key])) {
                $has_all = false;
                break;
            }
        }
        
        if ($has_all) {
            return [
                'status' => 'success',
                'message' => 'Opzioni configurate correttamente',
                'details' => 'Banner: ' . ($options['enabled'] ? 'Attivo' : 'Disattivo')
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'Alcune opzioni mancanti'
        ];
    });

    // TEST 7: Verifica Tabelle Database
    run_test('Tabelle Database', function() {
        global $wpdb;
        $table = $wpdb->prefix . 'fp_privacy_consent_log';
        
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            return [
                'status' => 'success',
                'message' => "Tabella presente con $count consensi registrati"
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'Tabella non ancora creata (verrà creata al primo salvataggio)'
        ];
    });

    // TEST 8: Verifica Integrazione FP Performance
    run_test('Integrazione FP Performance', function() {
        if (defined('FP_PERF_SUITE_VERSION')) {
            return [
                'status' => 'success',
                'message' => 'FP Performance attivo - Integrazione disponibile',
                'details' => 'Versione FP Performance: ' . FP_PERF_SUITE_VERSION
            ];
        }
        
        return [
            'status' => 'info',
            'message' => 'FP Performance non attivo (opzionale)'
        ];
    });

    // TEST 9: Verifica Hook WordPress
    run_test('Hook WordPress', function() {
        global $wp_filter;
        
        $required_hooks = [
            'wp_enqueue_scripts' => 'Caricamento asset',
            'wp_footer' => 'Rendering banner',
            'rest_api_init' => 'API REST'
        ];
        
        $registered = [];
        foreach ($required_hooks as $hook => $desc) {
            if (isset($wp_filter[$hook])) {
                $registered[] = $desc;
            }
        }
        
        if (count($registered) >= 2) {
            return [
                'status' => 'success',
                'message' => 'Hook WordPress registrati: ' . implode(', ', $registered)
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'Alcuni hook potrebbero non essere registrati'
        ];
    });

    // TEST 10: Verifica File JavaScript
    run_test('JavaScript Banner', function() {
        $js_file = __DIR__ . '/assets/js/banner.js';
        if (!file_exists($js_file)) {
            return [
                'status' => 'error',
                'message' => 'File JavaScript non trovato'
            ];
        }
        
        $content = file_get_contents($js_file);
        
        // Verifica funzioni critiche
        $required_functions = [
            'handleAcceptAll',
            'handleRejectAll',
            'handleSavePreferences',
            'setConsentCookie',
            'readConsentIdFromCookie'
        ];
        
        $missing = [];
        foreach ($required_functions as $func) {
            if (strpos($content, "function $func") === false) {
                $missing[] = $func;
            }
        }
        
        if (empty($missing)) {
            $size = filesize($js_file);
            return [
                'status' => 'success',
                'message' => 'Tutte le funzioni JavaScript presenti',
                'details' => 'Dimensione: ' . number_format($size / 1024, 2) . ' KB'
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Funzioni JavaScript mancanti: ' . implode(', ', $missing)
        ];
    });

    // ======================================
    // MOSTRA RISULTATI
    // ======================================
    ?>

    <!-- Summary Cards -->
    <div class="summary">
        <div class="summary-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <h3><?php echo $passed_tests; ?></h3>
            <p>Test Superati</p>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);">
            <h3><?php echo $failed_tests; ?></h3>
            <p>Test Falliti</p>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h3><?php echo $warnings; ?></h3>
            <p>Avvisi</p>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h3><?php echo $total_tests; ?></h3>
            <p>Test Totali</p>
        </div>
    </div>

    <!-- Risultati Dettagliati -->
    <div class="test-section">
        <h2>📋 Risultati Dettagliati</h2>
        <table>
            <thead>
                <tr>
                    <th>Test</th>
                    <th>Stato</th>
                    <th>Messaggio</th>
                    <th>Dettagli</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                <tr>
                    <td><strong><?php echo esc_html($result['name']); ?></strong></td>
                    <td>
                        <?php
                        $badge_class = 'badge-success';
                        $badge_text = '✓';
                        if ($result['status'] === 'error') {
                            $badge_class = 'badge-error';
                            $badge_text = '✗';
                        } elseif ($result['status'] === 'warning' || $result['status'] === 'info') {
                            $badge_class = 'badge-warning';
                            $badge_text = '!';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?> <?php echo strtoupper($result['status']); ?></span>
                    </td>
                    <td><?php echo esc_html($result['message']); ?></td>
                    <td>
                        <?php
                        if (isset($result['details'])) {
                            echo '<code>' . esc_html($result['details']) . '</code>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Raccomandazioni -->
    <div class="test-section">
        <h2>💡 Raccomandazioni</h2>
        <?php if ($failed_tests > 0): ?>
            <p class="error">⚠️ Ci sono <?php echo $failed_tests; ?> test falliti che richiedono attenzione immediata.</p>
        <?php endif; ?>
        
        <?php if ($warnings > 0): ?>
            <p class="warning">⚠️ Ci sono <?php echo $warnings; ?> avvisi da verificare.</p>
        <?php endif; ?>
        
        <?php if ($failed_tests === 0 && $warnings === 0): ?>
            <p class="success">✅ Tutti i test sono stati superati! Il plugin è completamente funzionante.</p>
        <?php endif; ?>
        
        <h3>Prossimi Passi:</h3>
        <ol>
            <li>Se ci sono test falliti, correggi gli errori indicati</li>
            <li>Configura le opzioni del plugin da <code>Settings → Privacy & Cookie</code></li>
            <li>Testa il banner su frontend in modalità incognito</li>
            <li>Verifica che i consensi vengano salvati correttamente</li>
            <li>Testa l'integrazione con FP Performance (se installato)</li>
        </ol>
    </div>

    <!-- Info Sistema -->
    <div class="test-section">
        <h2>🖥️ Informazioni Sistema</h2>
        <table>
            <tr>
                <th>Parametro</th>
                <th>Valore</th>
            </tr>
            <tr>
                <td>Versione WordPress</td>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <td>Versione PHP</td>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <td>Versione Plugin</td>
                <td><?php echo defined('FP_PRIVACY_VERSION') ? FP_PRIVACY_VERSION : 'N/A'; ?></td>
            </tr>
            <tr>
                <td>Database</td>
                <td><?php global $wpdb; echo $wpdb->db_version(); ?></td>
            </tr>
            <tr>
                <td>Tema Attivo</td>
                <td><?php echo wp_get_theme()->get('Name'); ?></td>
            </tr>
            <tr>
                <td>Locale</td>
                <td><?php echo get_locale(); ?></td>
            </tr>
        </table>
    </div>

    <p style="text-align: center; color: #666; margin-top: 40px;">
        <small>FP Privacy & Cookie Policy v<?php echo defined('FP_PRIVACY_VERSION') ? FP_PRIVACY_VERSION : '0.1.2'; ?> | Test eseguito il <?php echo date('d/m/Y H:i:s'); ?></small>
    </p>

</body>
</html>

