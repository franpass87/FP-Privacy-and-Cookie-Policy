<?php
/**
 * Test WordPress loading paths
 * Helps identify the correct path to wp-load.php
 */

echo '<html><head><meta charset="UTF-8"><style>
body { font-family: monospace; padding: 40px; background: #1e1e1e; color: #fff; }
h1 { color: #4ec9b0; }
.ok { color: #4ec9b0; }
.fail { color: #f48771; }
.path { background: #2d2d2d; padding: 10px; margin: 10px 0; border-radius: 4px; }
code { color: #ce9178; }
</style></head><body>';

echo '<h1>🔍 WordPress Path Finder</h1>';
echo '<p>Testing different paths to find wp-load.php...</p><br>';

$base_dir = dirname( __FILE__ );

$paths_to_test = array(
	'../../../wp-load.php' => $base_dir . '/../../../wp-load.php',
	'../../../../wp-load.php' => $base_dir . '/../../../../wp-load.php',
	'../../../../../wp-load.php' => $base_dir . '/../../../../../wp-load.php',
	'../../../../../../wp-load.php' => $base_dir . '/../../../../../../wp-load.php',
);

// Add DOCUMENT_ROOT if available
if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
	$paths_to_test['$_SERVER[DOCUMENT_ROOT]/wp-load.php'] = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
}

echo '<h2>📁 Current Directory:</h2>';
echo '<div class="path"><code>' . htmlspecialchars( $base_dir ) . '</code></div><br>';

if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
	echo '<h2>🌐 Document Root:</h2>';
	echo '<div class="path"><code>' . htmlspecialchars( $_SERVER['DOCUMENT_ROOT'] ) . '</code></div><br>';
}

echo '<h2>🔎 Testing Paths:</h2>';

$found_path = null;

foreach ( $paths_to_test as $description => $path ) {
	$resolved = realpath( $path );
	$exists = file_exists( $path );
	
	echo '<div class="path">';
	echo '<strong>' . htmlspecialchars( $description ) . '</strong><br>';
	echo 'Path: <code>' . htmlspecialchars( $path ) . '</code><br>';
	
	if ( $resolved ) {
		echo 'Resolved: <code>' . htmlspecialchars( $resolved ) . '</code><br>';
	}
	
	if ( $exists ) {
		echo '<span class="ok">✅ FOUND!</span>';
		if ( ! $found_path ) {
			$found_path = $path;
			echo ' <strong>(USING THIS)</strong>';
		}
	} else {
		echo '<span class="fail">❌ Not found</span>';
	}
	
	echo '</div>';
}

echo '<br><h2>🎯 Result:</h2>';

if ( $found_path ) {
	echo '<div class="path ok">';
	echo '✅ WordPress trovato in:<br>';
	echo '<code>' . htmlspecialchars( $found_path ) . '</code><br><br>';
	echo '<strong>Loading WordPress...</strong>';
	echo '</div>';
	
	// Try to load it
	require_once $found_path;
	
	if ( defined( 'ABSPATH' ) ) {
		echo '<div class="path ok">';
		echo '<br>✅ WordPress caricato con successo!<br>';
		echo 'ABSPATH: <code>' . htmlspecialchars( ABSPATH ) . '</code><br>';
		echo 'WP Version: <code>' . get_bloginfo( 'version' ) . '</code><br>';
		echo 'Site URL: <code>' . get_bloginfo( 'url' ) . '</code><br>';
		echo 'Locale: <code>' . get_locale() . '</code>';
		echo '</div>';
		
		// Test FP Privacy
		echo '<br><h2>🔌 Plugin FP Privacy:</h2>';
		if ( class_exists( '\\FP\\Privacy\\Plugin' ) ) {
			echo '<div class="path ok">';
			echo '✅ Plugin caricato!<br>';
			echo 'Version: <code>' . ( defined( 'FP_PRIVACY_PLUGIN_VERSION' ) ? FP_PRIVACY_PLUGIN_VERSION : 'N/A' ) . '</code><br>';
			echo 'Path: <code>' . ( defined( 'FP_PRIVACY_PLUGIN_PATH' ) ? FP_PRIVACY_PLUGIN_PATH : 'N/A' ) . '</code>';
			echo '</div>';
		} else {
			echo '<div class="path fail">❌ Plugin NON caricato</div>';
		}
		
		echo '<br><p><a href="./" style="color: #4ec9b0; font-weight: bold;">→ Vai alla Dashboard Tools</a></p>';
	} else {
		echo '<div class="path fail"><br>❌ WordPress NON caricato correttamente!</div>';
	}
} else {
	echo '<div class="path fail">';
	echo '❌ WordPress NON trovato in nessun path!<br><br>';
	echo '<strong>Possibili soluzioni:</strong><br>';
	echo '1. Esegui lo script dalla root di WordPress<br>';
	echo '2. Copia lo script nella root e eseguilo da lì<br>';
	echo '3. Modifica manualmente il path nel codice';
	echo '</div>';
}

echo '</body></html>';

