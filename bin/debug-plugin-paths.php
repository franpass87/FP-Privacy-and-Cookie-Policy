<?php
/**
 * Debug plugin paths and textdomain loading
 */

// Load WordPress
if ( ! defined( 'ABSPATH' ) ) {
	require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
}

echo '<html><head><meta charset="UTF-8"><style>
body { font-family: monospace; padding: 40px; background: #1e1e1e; color: #ddd; }
h1, h2 { color: #4ec9b0; }
.ok { color: #4ec9b0; font-weight: bold; }
.fail { color: #f48771; font-weight: bold; }
.section { background: #2d2d2d; padding: 20px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #4ec9b0; }
code { color: #ce9178; background: #333; padding: 3px 8px; border-radius: 3px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; border: 1px solid #444; }
th { background: #333; }
.highlight { background: #3d3d00; }
</style></head><body>';

echo '<h1>🔍 Plugin Paths & Textdomain Debug</h1>';

// Plugin constants
echo '<div class="section">';
echo '<h2>📌 Plugin Constants:</h2>';
echo '<table>';
echo '<tr><th>Constant</th><th>Value</th></tr>';
echo '<tr><td>FP_PRIVACY_PLUGIN_FILE</td><td><code>' . htmlspecialchars( FP_PRIVACY_PLUGIN_FILE ) . '</code></td></tr>';
echo '<tr><td>FP_PRIVACY_PLUGIN_PATH</td><td><code>' . htmlspecialchars( FP_PRIVACY_PLUGIN_PATH ) . '</code></td></tr>';
echo '<tr><td>FP_PRIVACY_PLUGIN_URL</td><td><code>' . htmlspecialchars( FP_PRIVACY_PLUGIN_URL ) . '</code></td></tr>';
echo '</table>';
echo '</div>';

// Plugin basename
echo '<div class="section">';
echo '<h2>🔧 Plugin Basename Calculation:</h2>';
$plugin_file = FP_PRIVACY_PLUGIN_FILE;
$basename = plugin_basename( $plugin_file );
$dirname = dirname( $basename );

echo '<table>';
echo '<tr><th>Step</th><th>Result</th></tr>';
echo '<tr><td>plugin_basename( FP_PRIVACY_PLUGIN_FILE )</td><td><code>' . htmlspecialchars( $basename ) . '</code></td></tr>';
echo '<tr><td>dirname( plugin_basename() )</td><td><code>' . htmlspecialchars( $dirname ) . '</code></td></tr>';
echo '<tr class="highlight"><td><strong>FINAL PATH for textdomain</strong></td><td><code>' . htmlspecialchars( $dirname . '/languages' ) . '</code></td></tr>';
echo '</table>';
echo '</div>';

// Calculated MO file path
echo '<div class="section">';
echo '<h2>📂 MO File Path WordPress Should Use:</h2>';

$locale = get_locale();
$mofile = WP_PLUGIN_DIR . '/' . $dirname . '/languages/fp-privacy-' . $locale . '.mo';

echo '<table>';
echo '<tr><th>Component</th><th>Value</th></tr>';
echo '<tr><td>WP_PLUGIN_DIR</td><td><code>' . htmlspecialchars( WP_PLUGIN_DIR ) . '</code></td></tr>';
echo '<tr><td>dirname( basename )</td><td><code>' . htmlspecialchars( $dirname ) . '</code></td></tr>';
echo '<tr><td>locale</td><td><code>' . htmlspecialchars( $locale ) . '</code></td></tr>';
echo '<tr class="highlight"><td><strong>CALCULATED MO PATH</strong></td><td><code>' . htmlspecialchars( $mofile ) . '</code></td></tr>';
echo '<tr><td>File exists?</td><td>' . ( file_exists( $mofile ) ? '<span class="ok">✅ YES</span>' : '<span class="fail">❌ NO</span>' ) . '</td></tr>';
if ( file_exists( $mofile ) ) {
	echo '<tr><td>File size</td><td>' . number_format( filesize( $mofile ) ) . ' bytes</td></tr>';
	echo '<tr><td>Modified</td><td>' . date( 'Y-m-d H:i:s', filemtime( $mofile ) ) . '</td></tr>';
}
echo '</table>';
echo '</div>';

// Try manual loading with correct path
echo '<div class="section">';
echo '<h2>🧪 Manual Load Test:</h2>';

// Unload first
unload_textdomain( 'fp-privacy' );

// Try to load manually
$loaded = load_textdomain( 'fp-privacy', $mofile );

echo '<strong>Manual load_textdomain() with exact path:</strong><br>';
echo 'Result: ' . ( $loaded ? '<span class="ok">✅ SUCCESS</span>' : '<span class="fail">❌ FAILED</span>' ) . '<br><br>';

if ( $loaded ) {
	echo '<strong>Testing translations:</strong><br>';
	echo '<table>';
	echo '<tr><th>English</th><th>Translation</th><th>OK?</th></tr>';
	
	$tests = array(
		'We value your privacy',
		'Banner background',
		'Accept all',
	);
	
	foreach ( $tests as $test ) {
		$trans = __( $test, 'fp-privacy' );
		$works = ( $trans !== $test );
		
		echo '<tr>';
		echo '<td><code>' . htmlspecialchars( $test ) . '</code></td>';
		echo '<td><strong>' . htmlspecialchars( $trans ) . '</strong></td>';
		echo '<td>' . ( $works ? '<span class="ok">✅</span>' : '<span class="fail">❌</span>' ) . '</td>';
		echo '</tr>';
	}
	
	echo '</table>';
}

echo '</div>';

echo '<br><p><a href="./" style="color: #4ec9b0; font-weight: bold;">← Torna alla dashboard</a></p>';
echo '</body></html>';

