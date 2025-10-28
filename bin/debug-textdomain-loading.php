<?php
/**
 * Debug textdomain loading - Find which MO file WordPress is using
 */

// Load WordPress
if ( ! defined( 'ABSPATH' ) ) {
	$paths = array(
		dirname( __FILE__ ) . '/../../../../wp-load.php',
		dirname( __FILE__ ) . '/../../../../../wp-load.php',
		$_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',
	);
	
	foreach ( $paths as $wp_load ) {
		if ( file_exists( $wp_load ) ) {
			require_once $wp_load;
			break;
		}
	}
}

echo '<html><head><meta charset="UTF-8"><style>
body { font-family: monospace; padding: 40px; background: #1e1e1e; color: #ddd; }
h1, h2 { color: #4ec9b0; }
.ok { color: #4ec9b0; }
.fail { color: #f48771; }
.warning { color: #f0b849; }
.section { background: #2d2d2d; padding: 20px; margin: 20px 0; border-radius: 6px; }
code { color: #ce9178; background: #333; padding: 2px 6px; border-radius: 3px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; border: 1px solid #444; text-align: left; }
th { background: #333; font-weight: bold; }
pre { background: #333; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style></head><body>';

echo '<h1>🔍 Textdomain Loading Debugger</h1>';

// Get current locale
$locale = get_locale();
echo '<div class="section">';
echo '<h2>📍 Current State:</h2>';
echo '<strong>Locale:</strong> <code>' . htmlspecialchars( $locale ) . '</code><br>';
echo '<strong>Textdomain loaded:</strong> ' . ( is_textdomain_loaded( 'fp-privacy' ) ? '<span class="ok">✅ YES</span>' : '<span class="fail">❌ NO</span>' );
echo '</div>';

// Check global $l10n
global $l10n;
echo '<div class="section">';
echo '<h2>🌐 Global $l10n:</h2>';
if ( isset( $l10n['fp-privacy'] ) ) {
	echo '<span class="ok">✅ fp-privacy textdomain è registrato in $l10n</span><br><br>';
	
	// Inspect the MO object
	$mo = $l10n['fp-privacy'];
	echo '<strong>Class:</strong> <code>' . htmlspecialchars( get_class( $mo ) ) . '</code><br>';
	
	// Try to access entries (if it's NOOP_Translations or MO class)
	if ( method_exists( $mo, 'translate' ) ) {
		echo '<strong>Method translate():</strong> <span class="ok">✓ Exists</span><br><br>';
		
		// Test some translations
		$tests = array(
			'We value your privacy',
			'Banner background',
			'Accept all',
		);
		
		echo '<strong>Direct translation tests:</strong><br>';
		echo '<table>';
		echo '<tr><th>Original</th><th>Translated</th><th>Works?</th></tr>';
		
		foreach ( $tests as $test ) {
			$translated = $mo->translate( $test );
			$works = ( $translated !== $test && $translated !== '' );
			
			echo '<tr>';
			echo '<td><code>' . htmlspecialchars( $test ) . '</code></td>';
			echo '<td><strong>' . htmlspecialchars( $translated ) . '</strong></td>';
			echo '<td>' . ( $works ? '<span class="ok">✅</span>' : '<span class="fail">❌</span>' ) . '</td>';
			echo '</tr>';
		}
		
		echo '</table>';
	}
	
	// Check if it's using NOOP (dummy) translations
	if ( get_class( $mo ) === 'NOOP_Translations' ) {
		echo '<br><span class="fail">❌ PROBLEMA: Usando NOOP_Translations (traduzioni dummy)!</span><br>';
		echo 'Questo significa che il file .mo non è stato caricato correttamente.';
	}
	
	// Try to see the entries property
	echo '<br><br><strong>MO Object properties:</strong><br>';
	echo '<pre>';
	print_r( $mo );
	echo '</pre>';
	
} else {
	echo '<span class="fail">❌ fp-privacy textdomain NON è in $l10n</span>';
}
echo '</div>';

// Check which file WordPress would load
echo '<div class="section">';
echo '<h2>📁 MO File Path Resolution:</h2>';

// Unload and reload to see which file it loads
unload_textdomain( 'fp-privacy' );

// Try different loading methods
$test_loads = array(
	'Method 1: plugin_basename' => dirname( plugin_basename( FP_PRIVACY_PLUGIN_FILE ) ) . '/languages',
	'Method 2: relative path' => 'FP-Privacy-and-Cookie-Policy-1/languages',
	'Method 3: basename only' => basename( dirname( FP_PRIVACY_PLUGIN_FILE ) ) . '/languages',
);

echo '<table>';
echo '<tr><th>Method</th><th>Path</th><th>Works?</th></tr>';

foreach ( $test_loads as $method => $path ) {
	unload_textdomain( 'fp-privacy' );
	
	$loaded = load_plugin_textdomain( 'fp-privacy', false, $path );
	$works = is_textdomain_loaded( 'fp-privacy' );
	
	// Test translation
	$test_trans = __( 'We value your privacy', 'fp-privacy' );
	$trans_works = ( $test_trans !== 'We value your privacy' );
	
	echo '<tr>';
	echo '<td><strong>' . htmlspecialchars( $method ) . '</strong></td>';
	echo '<td><code>' . htmlspecialchars( $path ) . '</code></td>';
	echo '<td>';
	echo 'Loaded: ' . ( $works ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' ) . ' | ';
	echo 'Trans: ' . ( $trans_works ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' );
	if ( $trans_works ) {
		echo ' → <strong>' . htmlspecialchars( $test_trans ) . '</strong>';
	}
	echo '</td>';
	echo '</tr>';
}

echo '</table>';
echo '</div>';

// Check actual file WordPress tries to load
echo '<div class="section">';
echo '<h2>🔎 File WordPress cerca:</h2>';

$plugin_dir = dirname( FP_PRIVACY_PLUGIN_FILE );
$expected_paths = array(
	WP_LANG_DIR . '/plugins/fp-privacy-' . $locale . '.mo',
	$plugin_dir . '/languages/fp-privacy-' . $locale . '.mo',
	WP_CONTENT_DIR . '/plugins/FP-Privacy-and-Cookie-Policy-1/languages/fp-privacy-' . $locale . '.mo',
);

echo '<table>';
echo '<tr><th>Path</th><th>Exists?</th><th>Size</th><th>Modified</th></tr>';

foreach ( $expected_paths as $path ) {
	$exists = file_exists( $path );
	
	echo '<tr>';
	echo '<td><code>' . htmlspecialchars( $path ) . '</code></td>';
	echo '<td>' . ( $exists ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' ) . '</td>';
	echo '<td>' . ( $exists ? number_format( filesize( $path ) ) . ' bytes' : '-' ) . '</td>';
	echo '<td>' . ( $exists ? date( 'Y-m-d H:i:s', filemtime( $path ) ) : '-' ) . '</td>';
	echo '</tr>';
}

echo '</table>';
echo '</div>';

echo '<div class="section">';
echo '<h2>💡 Diagnosi:</h2>';
echo '<ul>';
echo '<li>Se ci sono più file .mo, WordPress potrebbe caricare quello sbagliato</li>';
echo '<li>Il file in <code>wp-content/languages/plugins/</code> ha priorità</li>';
echo '<li>Controlla quale metodo di caricamento funziona nella tabella sopra</li>';
echo '</ul>';
echo '</div>';

echo '</body></html>';

