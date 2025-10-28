<?php
/**
 * Compile .po files to .mo files
 * 
 * Usage:
 * - CLI: php compile-mo-files.php
 * - Browser: http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/compile-mo-files.php
 * - WP-CLI: wp eval-file wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/compile-mo-files.php
 * 
 * @package FP\Privacy\Tools
 */

/**
 * Simple .po to .mo compiler
 */
class FP_Privacy_MO_Compiler {
	
	/**
	 * Compile a .po file to .mo
	 */
	public function compile( $po_file, $mo_file ) {
		if ( ! file_exists( $po_file ) ) {
			return array(
				'success' => false,
				'message' => "File .po non trovato: {$po_file}",
			);
		}
		
		$entries = $this->parse_po_file( $po_file );
		
		if ( empty( $entries ) ) {
			return array(
				'success' => false,
				'message' => 'Nessuna traduzione trovata nel file .po',
			);
		}
		
		$result = $this->write_mo_file( $mo_file, $entries );
		
		if ( ! $result ) {
			return array(
				'success' => false,
				'message' => "Impossibile scrivere il file .mo: {$mo_file}",
			);
		}
		
		return array(
			'success' => true,
			'message' => 'Compilato con successo',
			'entries' => count( $entries ),
			'size' => filesize( $mo_file ),
		);
	}
	
	/**
	 * Parse .po file and extract translations
	 */
	private function parse_po_file( $file ) {
		$content = file_get_contents( $file );
		$lines = explode( "\n", $content );
		
		$entries = array();
		$current_msgid = '';
		$current_msgstr = '';
		$in_msgid = false;
		$in_msgstr = false;
		
		foreach ( $lines as $line ) {
			$line = trim( $line );
			
			// Skip comments and empty lines
			if ( empty( $line ) || $line[0] === '#' ) {
				continue;
			}
			
			// msgid line
			if ( strpos( $line, 'msgid ' ) === 0 ) {
				// Save previous entry
				if ( $current_msgid !== '' && $current_msgstr !== '' ) {
					$entries[ $current_msgid ] = $current_msgstr;
				}
				
				$current_msgid = $this->extract_string( $line );
				$current_msgstr = '';
				$in_msgid = true;
				$in_msgstr = false;
				continue;
			}
			
			// msgstr line
			if ( strpos( $line, 'msgstr ' ) === 0 ) {
				$current_msgstr = $this->extract_string( $line );
				$in_msgid = false;
				$in_msgstr = true;
				continue;
			}
			
			// Continuation line
			if ( isset( $line[0] ) && $line[0] === '"' ) {
				$string = $this->extract_string( $line );
				if ( $in_msgid ) {
					$current_msgid .= $string;
				} elseif ( $in_msgstr ) {
					$current_msgstr .= $string;
				}
			}
		}
		
		// Save last entry
		if ( $current_msgid !== '' && $current_msgstr !== '' ) {
			$entries[ $current_msgid ] = $current_msgstr;
		}
		
		return $entries;
	}
	
	/**
	 * Extract string from .po line
	 */
	private function extract_string( $line ) {
		// Remove msgid/msgstr prefix
		$line = preg_replace( '/^(msgid|msgstr)\s*/', '', $line );
		
		// Extract string from quotes and unescape
		if ( preg_match( '/^"(.*)"\s*$/', $line, $matches ) ) {
			return stripcslashes( $matches[1] );
		}
		
		return '';
	}
	
	/**
	 * Write .mo file in gettext format
	 */
	private function write_mo_file( $file, $entries ) {
		$originals = array_keys( $entries );
		$translations = array_values( $entries );
		$total = count( $entries );
		
		// MO file header
		$magic = 0x950412de; // Little endian magic number
		$revision = 0;
		
		// Calculate offsets
		$originals_table_offset = 28; // After header
		$translations_table_offset = $originals_table_offset + ( $total * 8 );
		$strings_offset = $translations_table_offset + ( $total * 8 );
		
		// Build strings and tables
		$originals_table = '';
		$translations_table = '';
		$originals_data = '';
		$translations_data = '';
		
		$current_orig_offset = $strings_offset;
		$current_trans_offset = $strings_offset;
		
		foreach ( $originals as $i => $original ) {
			$orig_len = strlen( $original );
			$trans_len = strlen( $translations[ $i ] );
			
			// Original string table entry (length, offset)
			$originals_table .= pack( 'V', $orig_len );
			$originals_table .= pack( 'V', $current_orig_offset );
			
			// Add to data
			$originals_data .= $original . "\0";
			$current_orig_offset += $orig_len + 1;
		}
		
		// Recalculate translation offset (after all originals)
		$current_trans_offset = $strings_offset + strlen( $originals_data );
		
		foreach ( $translations as $translation ) {
			$trans_len = strlen( $translation );
			
			// Translation string table entry (length, offset)
			$translations_table .= pack( 'V', $trans_len );
			$translations_table .= pack( 'V', $current_trans_offset );
			
			// Add to data
			$translations_data .= $translation . "\0";
			$current_trans_offset += $trans_len + 1;
		}
		
		// Build header (28 bytes)
		$header = pack( 'V', $magic );                    // Magic number
		$header .= pack( 'V', $revision );                // File format revision
		$header .= pack( 'V', $total );                   // Number of strings
		$header .= pack( 'V', $originals_table_offset );  // Offset of original strings table
		$header .= pack( 'V', $translations_table_offset ); // Offset of translation strings table
		$header .= pack( 'V', 0 );                        // Size of hash table (0 = no hash)
		$header .= pack( 'V', 0 );                        // Offset of hash table
		
		// Combine all parts
		$mo_content = $header . $originals_table . $translations_table . $originals_data . $translations_data;
		
		// Write to file
		return file_put_contents( $file, $mo_content );
	}
}

// Execute compilation
$compiler = new FP_Privacy_MO_Compiler();
$languages_dir = dirname( __FILE__ ) . '/../languages';

$files = array(
	'fp-privacy-it_IT',
	'fp-privacy-en_US',
);

$results = array();

foreach ( $files as $file ) {
	$po_file = $languages_dir . '/' . $file . '.po';
	$mo_file = $languages_dir . '/' . $file . '.mo';
	
	$result = $compiler->compile( $po_file, $mo_file );
	$results[ $file ] = $result;
}

// Output results
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::line( '' );
	WP_CLI::line( '🔧 Compilazione file .mo' );
	WP_CLI::line( str_repeat( '=', 50 ) );
	
	foreach ( $results as $file => $result ) {
		$icon = $result['success'] ? '✅' : '❌';
		WP_CLI::line( '' );
		WP_CLI::line( sprintf( '%s %s', $icon, $file ) );
		WP_CLI::line( '   ' . $result['message'] );
		
		if ( $result['success'] ) {
			WP_CLI::line( sprintf( '   Traduzioni: %d', $result['entries'] ) );
			WP_CLI::line( sprintf( '   Dimensione: %s bytes', number_format( $result['size'] ) ) );
		}
	}
	
	WP_CLI::line( '' );
	WP_CLI::success( 'Compilazione completata!' );
} else {
	?>
	<!DOCTYPE html>
	<html lang="it">
	<head>
		<meta charset="UTF-8">
		<title>FP Privacy - Compilazione MO</title>
		<style>
			body {
				font-family: sans-serif;
				padding: 40px;
				max-width: 900px;
				margin: 0 auto;
				background: #f5f5f5;
			}
			h1 { color: #2271b1; }
			.result {
				background: white;
				padding: 20px;
				margin: 15px 0;
				border-radius: 8px;
				border-left: 4px solid #ddd;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}
			.result.success { border-left-color: #00a32a; }
			.result.fail { border-left-color: #d63638; }
			.icon { font-size: 24px; margin-right: 10px; }
			.details {
				background: #f9fafb;
				padding: 15px;
				margin-top: 15px;
				border-radius: 4px;
				font-size: 13px;
			}
			.btn {
				display: inline-block;
				background: #2271b1;
				color: white;
				padding: 12px 24px;
				text-decoration: none;
				border-radius: 4px;
				margin: 20px 5px 0 0;
				font-weight: 600;
			}
		</style>
	</head>
	<body>
		<h1>🔧 Compilazione file .mo</h1>
		
				<?php foreach ( $results as $file => $result ) : ?>
			<div class="result <?php echo $result['success'] ? 'success' : 'fail'; ?>">
				<div>
					<span class="icon"><?php echo $result['success'] ? '✅' : '❌'; ?></span>
					<strong><?php echo htmlspecialchars( $file ); ?></strong>
				</div>
				<p><?php echo htmlspecialchars( $result['message'] ); ?></p>
				
				<?php if ( $result['success'] ) : ?>
					<div class="details">
						<strong>Dettagli compilazione:</strong><br>
						📊 Traduzioni: <?php echo number_format( $result['entries'] ); ?><br>
						📦 Dimensione: <?php echo number_format( $result['size'] ); ?> bytes<br>
						📅 Compilato: <?php echo date( 'Y-m-d H:i:s' ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		
		<p style="text-align: center;">
			<a href="force-update-translations.php" class="btn">→ Forza aggiornamento traduzioni</a>
			<a href="test-translations.php" class="btn">→ Test traduzioni</a>
		</p>
		
	</body>
	</html>
	<?php
}

