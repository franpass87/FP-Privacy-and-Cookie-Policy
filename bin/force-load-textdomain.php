<?php
/**
 * FORCE LOAD textdomain with absolute path
 * Bypasses plugin code and loads MO file directly
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
.section { background: #2d2d2d; padding: 20px; margin: 20px 0; border-radius: 6px; }
code { color: #ce9178; background: #333; padding: 3px 8px; border-radius: 3px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; border: 1px solid #444; }
th { background: #333; font-weight: bold; }
</style></head><body>';

echo '<h1>🔥 Force Load Textdomain</h1>';

$locale = get_locale();

echo '<div class="section">';
echo '<h2>📍 Info:</h2>';
echo 'Locale: <code>' . htmlspecialchars( $locale ) . '</code><br>';
echo 'Plugin path: <code>' . htmlspecialchars( FP_PRIVACY_PLUGIN_PATH ) . '</code>';
echo '</div>';

// FORCE UNLOAD
echo '<div class="section">';
echo '<h2>1️⃣ Unloading existing textdomain...</h2>';

global $l10n;
if ( isset( $l10n['fp-privacy'] ) ) {
	unset( $l10n['fp-privacy'] );
	echo '<span class="ok">✅ Rimosso da $l10n</span><br>';
}

unload_textdomain( 'fp-privacy' );
echo '<span class="ok">✅ unload_textdomain() chiamato</span>';
echo '</div>';

// FORCE LOAD with ABSOLUTE PATH
echo '<div class="section">';
echo '<h2>2️⃣ Loading with ABSOLUTE path...</h2>';

$mofile = FP_PRIVACY_PLUGIN_PATH . 'languages/fp-privacy-' . $locale . '.mo';

echo 'MO File: <code>' . htmlspecialchars( $mofile ) . '</code><br>';
echo 'Exists: ' . ( file_exists( $mofile ) ? '<span class="ok">✅ YES</span>' : '<span class="fail">❌ NO</span>' ) . '<br><br>';

if ( file_exists( $mofile ) ) {
	$loaded = load_textdomain( 'fp-privacy', $mofile );
	echo 'load_textdomain() result: ' . ( $loaded ? '<span class="ok">✅ SUCCESS</span>' : '<span class="fail">❌ FAILED</span>' ) . '<br>';
	echo 'is_textdomain_loaded(): ' . ( is_textdomain_loaded( 'fp-privacy' ) ? '<span class="ok">✅ YES</span>' : '<span class="fail">❌ NO</span>' );
}

echo '</div>';

// TEST TRANSLATIONS
echo '<div class="section">';
echo '<h2>3️⃣ Testing translations NOW:</h2>';

$tests = array(
	'We value your privacy' => 'Rispettiamo la tua privacy',
	'Banner background' => 'Sfondo banner',
	'Accept all' => 'Accetta tutti',
	'Reject all' => 'Rifiuta tutti',
	'Manage preferences' => 'Gestisci preferenze',
	'Primary button background' => 'Sfondo pulsante principale',
	'Link color' => 'Colore link',
	'Border' => 'Bordo',
);

echo '<table>';
echo '<tr><th>Original</th><th>Translated</th><th>Expected</th><th>OK?</th></tr>';

$passed = 0;
$total = count( $tests );

foreach ( $tests as $original => $expected ) {
	$translated = __( $original, 'fp-privacy' );
	$works = ( $translated === $expected );
	
	if ( $works ) $passed++;
	
	echo '<tr>';
	echo '<td><code>' . htmlspecialchars( substr( $original, 0, 40 ) ) . '</code></td>';
	echo '<td><strong>' . htmlspecialchars( $translated ) . '</strong></td>';
	echo '<td>' . htmlspecialchars( $expected ) . '</td>';
	echo '<td>' . ( $works ? '<span class="ok">✅</span>' : '<span class="fail">❌</span>' ) . '</td>';
	echo '</tr>';
}

echo '</table>';

echo '<br><strong>Result: ' . $passed . '/' . $total . ' traduzioni corrette</strong>';

echo '</div>';

// Check $l10n object
echo '<div class="section">';
echo '<h2>4️⃣ Global $l10n object:</h2>';

if ( isset( $l10n['fp-privacy'] ) ) {
	$mo_obj = $l10n['fp-privacy'];
	echo '<strong>Class:</strong> <code>' . htmlspecialchars( get_class( $mo_obj ) ) . '</code><br><br>';
	
	if ( get_class( $mo_obj ) === 'NOOP_Translations' ) {
		echo '<span class="fail">❌ PROBLEMA: Usando NOOP_Translations (dummy)!</span><br>';
		echo 'Il textdomain è "caricato" ma senza traduzioni reali.';
	} else if ( get_class( $mo_obj ) === 'MO' ) {
		echo '<span class="ok">✅ Usando classe MO (traduzioni reali)</span><br><br>';
		
		// Check entries
		if ( isset( $mo_obj->entries ) && is_array( $mo_obj->entries ) ) {
			echo 'Entries count: <strong>' . count( $mo_obj->entries ) . '</strong><br>';
			
			// Check if our strings are in entries
			echo '<br><strong>Checking if strings are in MO entries:</strong><br>';
			echo '<ul>';
			foreach ( array( 'We value your privacy', 'Banner background', 'Accept all' ) as $key ) {
				$in_entries = isset( $mo_obj->entries[ $key ] );
				echo '<li>' . htmlspecialchars( $key ) . ': ' . ( $in_entries ? '<span class="ok">✓ Found</span>' : '<span class="fail">✗ Not found</span>' ) . '</li>';
			}
			echo '</ul>';
		}
	}
} else {
	echo '<span class="fail">❌ fp-privacy NON è in $l10n!</span>';
}

echo '</div>';

echo '<br><p>';
echo '<a href="test-translations.php" style="color: #4ec9b0; font-weight: bold;">→ Test traduzioni complete</a> | ';
echo '<a href="./" style="color: #4ec9b0; font-weight: bold;">← Dashboard</a>';
echo '</p>';

echo '</body></html>';

