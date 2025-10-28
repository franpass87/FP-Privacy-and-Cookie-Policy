<?php
/**
 * Robust .po to .mo compiler
 * Based on gettext MO file format specification
 * 
 * @package FP\Privacy\Tools
 */

class FP_Privacy_MO_Compiler_V2 {
	
	const MAGIC_LITTLE_ENDIAN = 0x950412de;
	const MAGIC_BIG_ENDIAN = 0xde120495;
	
	private $errors = array();
	
	/**
	 * Compile .po to .mo
	 */
	public function compile( $po_file, $mo_file ) {
		if ( ! file_exists( $po_file ) ) {
			return $this->error( "File .po non trovato: {$po_file}" );
		}
		
		if ( ! is_readable( $po_file ) ) {
			return $this->error( "File .po non leggibile: {$po_file}" );
		}
		
		$entries = $this->parse_po_file( $po_file );
		
		if ( empty( $entries ) ) {
			return $this->error( 'Nessuna traduzione trovata nel file .po' );
		}
		
		// Remove empty translations
		$entries = array_filter( $entries, function( $value ) {
			return $value !== '';
		});
		
		if ( empty( $entries ) ) {
			return $this->error( 'Nessuna traduzione valida trovata' );
		}
		
		$mo_data = $this->generate_mo_data( $entries );
		
		if ( ! $mo_data ) {
			return $this->error( 'Impossibile generare dati .mo' );
		}
		
		$result = file_put_contents( $mo_file, $mo_data );
		
		if ( ! $result ) {
			return $this->error( "Impossibile scrivere file .mo: {$mo_file}" );
		}
		
		return array(
			'success' => true,
			'entries' => count( $entries ),
			'size' => filesize( $mo_file ),
			'file' => $mo_file,
		);
	}
	
	/**
	 * Parse .po file
	 */
	private function parse_po_file( $file ) {
		$content = file_get_contents( $file );
		$lines = explode( "\n", str_replace( "\r\n", "\n", $content ) );
		
		$entries = array();
		$msgid = '';
		$msgstr = '';
		$in_msgid = false;
		$in_msgstr = false;
		
		foreach ( $lines as $line ) {
			$line = trim( $line );
			
			// Skip empty lines and comments
			if ( $line === '' || $line[0] === '#' ) {
				continue;
			}
			
			// msgid start
			if ( preg_match( '/^msgid\s+"(.*)"\s*$/', $line, $matches ) ) {
				// Save previous entry
				if ( $msgid !== '' ) {
					$entries[ $msgid ] = $msgstr;
				}
				
				$msgid = $this->unescape_string( $matches[1] );
				$msgstr = '';
				$in_msgid = true;
				$in_msgstr = false;
				continue;
			}
			
			// msgstr start
			if ( preg_match( '/^msgstr\s+"(.*)"\s*$/', $line, $matches ) ) {
				$msgstr = $this->unescape_string( $matches[1] );
				$in_msgid = false;
				$in_msgstr = true;
				continue;
			}
			
			// Continuation line
			if ( preg_match( '/^"(.*)"\s*$/', $line, $matches ) ) {
				$str = $this->unescape_string( $matches[1] );
				if ( $in_msgid ) {
					$msgid .= $str;
				} elseif ( $in_msgstr ) {
					$msgstr .= $str;
				}
			}
		}
		
		// Save last entry
		if ( $msgid !== '' ) {
			$entries[ $msgid ] = $msgstr;
		}
		
		// Remove header entry (empty msgid)
		if ( isset( $entries[''] ) ) {
			unset( $entries[''] );
		}
		
		return $entries;
	}
	
	/**
	 * Unescape string from .po format
	 */
	private function unescape_string( $str ) {
		// Handle common escape sequences
		$replacements = array(
			'\\n' => "\n",
			'\\r' => "\r",
			'\\t' => "\t",
			'\\"' => '"',
			'\\\\' => '\\',
		);
		
		return str_replace( array_keys( $replacements ), array_values( $replacements ), $str );
	}
	
	/**
	 * Generate .mo file data
	 */
	private function generate_mo_data( $entries ) {
		// Sort entries by original string (required by spec)
		ksort( $entries );
		
		$originals = array_keys( $entries );
		$translations = array_values( $entries );
		$count = count( $entries );
		
		// Calculate table sizes
		$orig_length = 0;
		$trans_length = 0;
		
		foreach ( $originals as $str ) {
			$orig_length += strlen( $str ) + 1; // +1 for null terminator
		}
		
		foreach ( $translations as $str ) {
			$trans_length += strlen( $str ) + 1;
		}
		
		// Calculate offsets
		$header_size = 28;
		$orig_table_offset = $header_size;
		$trans_table_offset = $orig_table_offset + ( $count * 8 );
		$orig_strings_offset = $trans_table_offset + ( $count * 8 );
		$trans_strings_offset = $orig_strings_offset + $orig_length;
		
		// Build header (28 bytes)
		$header = pack( 'V', self::MAGIC_LITTLE_ENDIAN );  // Magic number
		$header .= pack( 'V', 0 );                         // File format revision (0)
		$header .= pack( 'V', $count );                    // Number of strings
		$header .= pack( 'V', $orig_table_offset );        // Offset of original strings table
		$header .= pack( 'V', $trans_table_offset );       // Offset of translation strings table
		$header .= pack( 'V', 0 );                         // Size of hash table (0 = no hash)
		$header .= pack( 'V', 0 );                         // Offset of hash table
		
		// Build original strings table and data
		$orig_table = '';
		$orig_data = '';
		$current_offset = $orig_strings_offset;
		
		foreach ( $originals as $str ) {
			$len = strlen( $str );
			$orig_table .= pack( 'V', $len );           // String length
			$orig_table .= pack( 'V', $current_offset ); // String offset
			$orig_data .= $str . "\0";                  // String + null
			$current_offset += $len + 1;
		}
		
		// Build translation strings table and data
		$trans_table = '';
		$trans_data = '';
		$current_offset = $trans_strings_offset;
		
		foreach ( $translations as $str ) {
			$len = strlen( $str );
			$trans_table .= pack( 'V', $len );           // String length
			$trans_table .= pack( 'V', $current_offset ); // String offset
			$trans_data .= $str . "\0";                  // String + null
			$current_offset += $len + 1;
		}
		
		// Combine all parts
		return $header . $orig_table . $trans_table . $orig_data . $trans_data;
	}
	
	/**
	 * Error helper
	 */
	private function error( $message ) {
		$this->errors[] = $message;
		return array(
			'success' => false,
			'message' => $message,
			'errors' => $this->errors,
		);
	}
}

// Execute if run directly
if ( ! class_exists( 'WP_CLI' ) || ! defined( 'WP_CLI' ) ) {
	$languages_dir = dirname( __FILE__ ) . '/../languages';
	
	$files = array(
		'it_IT' => array(
			'po' => $languages_dir . '/fp-privacy-it_IT.po',
			'mo' => $languages_dir . '/fp-privacy-it_IT.mo',
		),
		'en_US' => array(
			'po' => $languages_dir . '/fp-privacy-en_US.po',
			'mo' => $languages_dir . '/fp-privacy-en_US.mo',
		),
	);
	
	echo '<html><head><meta charset="UTF-8"><style>
	body { font-family: monospace; padding: 40px; background: #1e1e1e; color: #ddd; }
	h1 { color: #4ec9b0; }
	.ok { color: #4ec9b0; }
	.fail { color: #f48771; }
	.result { background: #2d2d2d; padding: 15px; margin: 15px 0; border-radius: 6px; border-left: 4px solid #666; }
	.result.success { border-left-color: #4ec9b0; }
	.result.error { border-left-color: #f48771; }
	code { color: #ce9178; }
	</style></head><body>';
	
	echo '<h1>🔨 Robust MO Compiler</h1>';
	echo '<p>Compiling .po files to .mo with improved algorithm...</p><br>';
	
	$compiler = new FP_Privacy_MO_Compiler_V2();
	
	foreach ( $files as $lang => $paths ) {
		echo '<div class="result ' . ( file_exists( $paths['po'] ) ? 'success' : 'error' ) . '">';
		echo '<strong>📝 ' . htmlspecialchars( $lang ) . '</strong><br><br>';
		
		$result = $compiler->compile( $paths['po'], $paths['mo'] );
		
		if ( $result['success'] ) {
			echo '<span class="ok">✅ Compilato con successo!</span><br>';
			echo 'Traduzioni: <code>' . $result['entries'] . '</code><br>';
			echo 'Dimensione: <code>' . number_format( $result['size'] ) . ' bytes</code><br>';
			echo 'File: <code>' . htmlspecialchars( basename( $result['file'] ) ) . '</code>';
		} else {
			echo '<span class="fail">❌ Errore!</span><br>';
			echo htmlspecialchars( $result['message'] );
		}
		
		echo '</div>';
	}
	
	echo '<br><p><a href="test-translations.php" style="color: #4ec9b0; font-weight: bold;">→ Test traduzioni adesso</a></p>';
	echo '</body></html>';
}

