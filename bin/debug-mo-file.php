<?php
/**
 * Debug .mo file - Read and verify MO file structure
 */

$mo_file = dirname( __FILE__ ) . '/../languages/fp-privacy-it_IT.mo';

echo '<html><head><meta charset="UTF-8"><style>
body { font-family: monospace; padding: 40px; background: #1e1e1e; color: #ddd; font-size: 13px; }
h1, h2 { color: #4ec9b0; }
.ok { color: #4ec9b0; }
.fail { color: #f48771; }
.section { background: #2d2d2d; padding: 20px; margin: 20px 0; border-radius: 6px; }
code { color: #ce9178; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 8px; border: 1px solid #444; }
th { background: #333; }
</style></head><body>';

echo '<h1>🔍 MO File Debugger</h1>';
echo '<p>Reading and analyzing: <code>' . htmlspecialchars( $mo_file ) . '</code></p>';

if ( ! file_exists( $mo_file ) ) {
	echo '<div class="fail">❌ File non trovato!</div>';
	exit;
}

// Read file
$fp = fopen( $mo_file, 'rb' );
if ( ! $fp ) {
	echo '<div class="fail">❌ Impossibile aprire il file!</div>';
	exit;
}

// Read header (28 bytes)
$header = fread( $fp, 28 );
$unpacked = unpack( 'Vmagic/Vrevision/Vtotal/Vorig_offset/Vtrans_offset/Vhash_size/Vhash_offset', $header );

echo '<div class="section">';
echo '<h2>📋 Header MO File:</h2>';
echo '<table>';
echo '<tr><th>Field</th><th>Value</th><th>Expected</th><th>OK?</th></tr>';
echo '<tr><td>Magic</td><td>0x' . dechex( $unpacked['magic'] ) . '</td><td>0x950412de</td><td>' . ( $unpacked['magic'] === 0x950412de ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' ) . '</td></tr>';
echo '<tr><td>Revision</td><td>' . $unpacked['revision'] . '</td><td>0</td><td>' . ( $unpacked['revision'] === 0 ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' ) . '</td></tr>';
echo '<tr><td>Total strings</td><td><strong>' . $unpacked['total'] . '</strong></td><td>~240</td><td>' . ( $unpacked['total'] > 200 ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' ) . '</td></tr>';
echo '<tr><td>Orig offset</td><td>' . $unpacked['orig_offset'] . '</td><td>28</td><td>' . ( $unpacked['orig_offset'] === 28 ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' ) . '</td></tr>';
echo '<tr><td>Trans offset</td><td>' . $unpacked['trans_offset'] . '</td><td>~' . ( 28 + $unpacked['total'] * 8 ) . '</td><td><span class="ok">✓</span></td></tr>';
echo '</table>';
echo '</div>';

// Read a few entries to verify
echo '<div class="section">';
echo '<h2>🔎 Sample Translations (first 10):</h2>';
echo '<table>';
echo '<tr><th>#</th><th>Original (msgid)</th><th>Translation (msgstr)</th><th>Lengths</th></tr>';

$total = min( $unpacked['total'], 10 );

for ( $i = 0; $i < $total; $i++ ) {
	// Read original string table entry
	fseek( $fp, $unpacked['orig_offset'] + ( $i * 8 ) );
	$orig_entry = unpack( 'Vlength/Voffset', fread( $fp, 8 ) );
	
	// Read original string
	fseek( $fp, $orig_entry['offset'] );
	$orig_string = $orig_entry['length'] > 0 ? fread( $fp, $orig_entry['length'] ) : '';
	
	// Read translation string table entry
	fseek( $fp, $unpacked['trans_offset'] + ( $i * 8 ) );
	$trans_entry = unpack( 'Vlength/Voffset', fread( $fp, 8 ) );
	
	// Read translation string
	fseek( $fp, $trans_entry['offset'] );
	$trans_string = $trans_entry['length'] > 0 ? fread( $fp, $trans_entry['length'] ) : '';
	
	echo '<tr>';
	echo '<td>' . ( $i + 1 ) . '</td>';
	echo '<td><code>' . htmlspecialchars( substr( $orig_string, 0, 50 ) ) . ( strlen( $orig_string ) > 50 ? '...' : '' ) . '</code></td>';
	echo '<td><code>' . htmlspecialchars( substr( $trans_string, 0, 50 ) ) . ( strlen( $trans_string ) > 50 ? '...' : '' ) . '</code></td>';
	echo '<td>' . $orig_entry['length'] . ' / ' . $trans_entry['length'] . '</td>';
	echo '</tr>';
}

echo '</table>';
echo '</div>';

// Search for specific strings
echo '<div class="section">';
echo '<h2>🎯 Searching for test strings:</h2>';
echo '<table>';
echo '<tr><th>Search</th><th>Found?</th><th>Translation</th></tr>';

$search_strings = array(
	'We value your privacy',
	'Banner background',
	'Accept all',
);

foreach ( $search_strings as $search ) {
	$found = false;
	$translation = '';
	
	// Search through all entries
	for ( $i = 0; $i < $unpacked['total']; $i++ ) {
		fseek( $fp, $unpacked['orig_offset'] + ( $i * 8 ) );
		$orig_entry = unpack( 'Vlength/Voffset', fread( $fp, 8 ) );
		
		fseek( $fp, $orig_entry['offset'] );
		$orig_string = $orig_entry['length'] > 0 ? fread( $fp, $orig_entry['length'] ) : '';
		
		if ( $orig_string === $search ) {
			$found = true;
			
			// Get translation
			fseek( $fp, $unpacked['trans_offset'] + ( $i * 8 ) );
			$trans_entry = unpack( 'Vlength/Voffset', fread( $fp, 8 ) );
			
			fseek( $fp, $trans_entry['offset'] );
			$translation = $trans_entry['length'] > 0 ? fread( $fp, $trans_entry['length'] ) : '';
			break;
		}
	}
	
	echo '<tr>';
	echo '<td><code>' . htmlspecialchars( $search ) . '</code></td>';
	if ( $found ) {
		echo '<td><span class="ok">✅ FOUND</span></td>';
		echo '<td><strong>' . htmlspecialchars( $translation ) . '</strong></td>';
	} else {
		echo '<td><span class="fail">❌ NOT FOUND</span></td>';
		echo '<td>-</td>';
	}
	echo '</tr>';
}

echo '</table>';
echo '</div>';

fclose( $fp );

echo '<br><p><a href="test-translations.php" style="color: #4ec9b0; font-weight: bold;">→ Test traduzioni complete</a></p>';
echo '</body></html>';

