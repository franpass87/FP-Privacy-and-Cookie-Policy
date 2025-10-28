<?php
/**
 * Test FP Privacy translations
 * 
 * Usage: 
 * - Browser: http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/test-translations.php
 * - CLI: php test-translations.php
 * - WP-CLI: wp eval-file test-translations.php
 * 
 * @package FP\Privacy\Tests
 */

// Load WordPress if not already loaded
if ( ! defined( 'ABSPATH' ) ) {
	// Try multiple paths to find wp-load.php
	$paths = array(
		dirname( __FILE__ ) . '/../../../../wp-load.php',
		dirname( __FILE__ ) . '/../../../../../wp-load.php',
		$_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',
	);
	
	$wp_loaded = false;
	foreach ( $paths as $wp_load ) {
		if ( file_exists( $wp_load ) ) {
			require_once $wp_load;
			$wp_loaded = true;
			break;
		}
	}
	
	if ( ! $wp_loaded ) {
		die( 'WordPress not found. Tried: ' . implode( ', ', $paths ) );
	}
}

// Prevent direct access if not admin
if ( ! is_admin() && ! defined( 'WP_CLI' ) ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Accesso negato.' );
	}
}

/**
 * Test runner for FP Privacy translations
 */
class FP_Privacy_Translation_Tester {
	
	private $results = array();
	private $is_cli = false;
	
	public function __construct() {
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;
	}
	
	/**
	 * Run all tests
	 */
	public function run_all_tests() {
		$this->test_textdomain_loading();
		$this->test_locale();
		$this->test_mo_files();
		$this->test_banner_translations();
		$this->test_palette_translations();
		$this->test_rendering();
		
		return $this->results;
	}
	
	/**
	 * Test 1: Textdomain loading
	 */
	private function test_textdomain_loading() {
		$loaded = is_textdomain_loaded( 'fp-privacy' );
		
		if ( ! $loaded ) {
			// Try to load it
			load_plugin_textdomain( 'fp-privacy', false, 'FP-Privacy-and-Cookie-Policy-1/languages' );
			$loaded = is_textdomain_loaded( 'fp-privacy' );
		}
		
		$this->results['textdomain'] = array(
			'name' => 'Textdomain Loading',
			'pass' => $loaded,
			'message' => $loaded ? 'Textdomain "fp-privacy" caricato' : 'Textdomain NON caricato',
		);
	}
	
	/**
	 * Test 2: Current locale
	 */
	private function test_locale() {
		$locale = get_locale();
		
		$this->results['locale'] = array(
			'name' => 'Current Locale',
			'pass' => true,
			'message' => 'Locale: ' . $locale,
			'data' => $locale,
		);
	}
	
	/**
	 * Test 3: .mo files existence
	 */
	private function test_mo_files() {
		$languages = array( 'it_IT', 'en_US' );
		$base_path = WP_CONTENT_DIR . '/plugins/FP-Privacy-and-Cookie-Policy-1/languages/fp-privacy-';
		$all_found = true;
		$files_info = array();
		
		foreach ( $languages as $lang ) {
			$mo_file = $base_path . $lang . '.mo';
			$exists = file_exists( $mo_file );
			
			if ( ! $exists ) {
				$all_found = false;
			}
			
			$files_info[ $lang ] = array(
				'exists' => $exists,
				'path' => $mo_file,
				'size' => $exists ? filesize( $mo_file ) : 0,
				'modified' => $exists ? date( 'Y-m-d H:i:s', filemtime( $mo_file ) ) : 'N/A',
			);
		}
		
		$this->results['mo_files'] = array(
			'name' => 'MO Files',
			'pass' => $all_found,
			'message' => $all_found ? 'Tutti i file .mo trovati' : 'File .mo mancanti',
			'data' => $files_info,
		);
	}
	
	/**
	 * Test 4: Banner translations
	 */
	private function test_banner_translations() {
		$tests = array(
			'We value your privacy',
			'We use cookies to improve your experience. You can accept all cookies or manage your preferences.',
			'Accept all',
			'Reject all',
			'Manage preferences',
			'Privacy preferences',
			'Close preferences',
			'Save preferences',
			'Always active',
			'Enabled',
		);
		
		$expected_it = array(
			'Rispettiamo la tua privacy',
			'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
			'Accetta tutti',
			'Rifiuta tutti',
			'Gestisci preferenze',
			'Preferenze privacy',
			'Chiudi preferenze',
			'Salva preferenze',
			'Sempre attivo',
			'Abilitato',
		);
		
		$results = array();
		$failures = 0;
		
		// Test for it_IT
		switch_to_locale( 'it_IT' );
		unload_textdomain( 'fp-privacy' );
		load_plugin_textdomain( 'fp-privacy', false, 'FP-Privacy-and-Cookie-Policy-1/languages' );
		
		foreach ( $tests as $i => $key ) {
			$translated = __( $key, 'fp-privacy' );
			$expected = $expected_it[ $i ];
			$pass = ( $translated === $expected );
			
			if ( ! $pass ) {
				$failures++;
			}
			
			$results[ $key ] = array(
				'translated' => $translated,
				'expected' => $expected,
				'pass' => $pass,
			);
		}
		
		restore_previous_locale();
		
		$this->results['banner_translations'] = array(
			'name' => 'Banner Translations (it_IT)',
			'pass' => ( $failures === 0 ),
			'message' => sprintf( '%d/%d traduzioni corrette', count( $tests ) - $failures, count( $tests ) ),
			'data' => $results,
		);
	}
	
	/**
	 * Test 5: Palette translations
	 */
	private function test_palette_translations() {
		$tests = array(
			'Banner background',
			'Banner text',
			'Primary button background',
			'Primary button text',
			'Secondary buttons background',
			'Secondary buttons text',
			'Link color',
			'Border',
			'Focus color',
		);
		
		$expected_it = array(
			'Sfondo banner',
			'Testo banner',
			'Sfondo pulsante principale',
			'Testo pulsante principale',
			'Sfondo pulsanti secondari',
			'Testo pulsanti secondari',
			'Colore link',
			'Bordo',
			'Colore focus',
		);
		
		$results = array();
		$failures = 0;
		
		// Test for it_IT
		switch_to_locale( 'it_IT' );
		unload_textdomain( 'fp-privacy' );
		load_plugin_textdomain( 'fp-privacy', false, 'FP-Privacy-and-Cookie-Policy-1/languages' );
		
		foreach ( $tests as $i => $key ) {
			$translated = __( $key, 'fp-privacy' );
			$expected = $expected_it[ $i ];
			$pass = ( $translated === $expected );
			
			if ( ! $pass ) {
				$failures++;
			}
			
			$results[ $key ] = array(
				'translated' => $translated,
				'expected' => $expected,
				'pass' => $pass,
			);
		}
		
		restore_previous_locale();
		
		$this->results['palette_translations'] = array(
			'name' => 'Palette Translations (it_IT)',
			'pass' => ( $failures === 0 ),
			'message' => sprintf( '%d/%d traduzioni corrette', count( $tests ) - $failures, count( $tests ) ),
			'data' => $results,
		);
	}
	
	/**
	 * Test 6: Rendering simulation
	 */
	private function test_rendering() {
		if ( ! class_exists( '\\FP\\Privacy\\Admin\\SettingsRenderer' ) ) {
			$this->results['rendering'] = array(
				'name' => 'Rendering Test',
				'pass' => false,
				'message' => 'Classe SettingsRenderer non trovata',
			);
			return;
		}
		
		// Simulate palette rendering
		$palette = array(
			'surface_bg' => '#F9FAFB',
			'surface_text' => '#1F2937',
			'button_primary_bg' => '#2563EB',
		);
		
		$labels = array(
			'surface_bg' => __( 'Banner background', 'fp-privacy' ),
			'surface_text' => __( 'Banner text', 'fp-privacy' ),
			'button_primary_bg' => __( 'Primary button background', 'fp-privacy' ),
		);
		
		$all_translated = true;
		foreach ( $labels as $key => $label ) {
			// Check if translation worked (not English for it_IT locale)
			$is_english = in_array( $label, array( 'Banner background', 'Banner text', 'Primary button background' ), true );
			if ( $is_english && get_locale() === 'it_IT' ) {
				$all_translated = false;
			}
		}
		
		$this->results['rendering'] = array(
			'name' => 'Rendering Simulation',
			'pass' => $all_translated,
			'message' => $all_translated ? 'Label renderizzate correttamente' : 'Alcune label non tradotte',
			'data' => $labels,
		);
	}
	
	/**
	 * Output results
	 */
	public function output_results() {
		if ( $this->is_cli ) {
			$this->output_cli();
		} else {
			$this->output_html();
		}
	}
	
	/**
	 * CLI output
	 */
	private function output_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::line( '' );
			WP_CLI::line( '🧪 FP Privacy - Translation Tests' );
			WP_CLI::line( str_repeat( '=', 50 ) );
			
			foreach ( $this->results as $test ) {
				$icon = $test['pass'] ? '✅' : '❌';
				WP_CLI::line( '' );
				WP_CLI::line( sprintf( '%s %s', $icon, $test['name'] ) );
				WP_CLI::line( '   ' . $test['message'] );
				
				if ( isset( $test['data'] ) && is_array( $test['data'] ) ) {
					foreach ( $test['data'] as $key => $value ) {
						if ( is_array( $value ) ) {
							WP_CLI::line( sprintf( '   - %s:', $key ) );
							foreach ( $value as $k => $v ) {
								WP_CLI::line( sprintf( '      %s: %s', $k, is_bool( $v ) ? ( $v ? 'true' : 'false' ) : $v ) );
							}
						} else {
							WP_CLI::line( sprintf( '   - %s: %s', $key, $value ) );
						}
					}
				}
			}
			
			WP_CLI::line( '' );
			WP_CLI::line( str_repeat( '=', 50 ) );
			
			$all_pass = true;
			foreach ( $this->results as $test ) {
				if ( ! $test['pass'] ) {
					$all_pass = false;
					break;
				}
			}
			
			if ( $all_pass ) {
				WP_CLI::success( 'Tutti i test passati!' );
			} else {
				WP_CLI::error( 'Alcuni test falliti!' );
			}
		} else {
			// Plain text output
			echo "🧪 FP Privacy - Translation Tests\n";
			echo str_repeat( '=', 50 ) . "\n\n";
			
			foreach ( $this->results as $test ) {
				$icon = $test['pass'] ? '✅' : '❌';
				echo "{$icon} {$test['name']}\n";
				echo "   {$test['message']}\n\n";
			}
		}
	}
	
	/**
	 * HTML output
	 */
	private function output_html() {
		?>
		<!DOCTYPE html>
		<html lang="it">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>FP Privacy - Test Traduzioni</title>
			<style>
				body { 
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					padding: 40px;
					max-width: 1200px;
					margin: 0 auto;
					background: #f5f5f5;
				}
				h1 { color: #2271b1; margin-bottom: 10px; }
				.subtitle { color: #666; margin-bottom: 30px; }
				.test-result {
					background: white;
					padding: 20px;
					margin: 15px 0;
					border-radius: 8px;
					border-left: 4px solid #ddd;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				}
				.test-result.pass { border-left-color: #00a32a; }
				.test-result.fail { border-left-color: #d63638; }
				.test-header {
					display: flex;
					align-items: center;
					gap: 10px;
					margin-bottom: 10px;
				}
				.test-icon { font-size: 24px; }
				.test-name { font-weight: 600; font-size: 16px; flex: 1; }
				.test-message { color: #666; margin-bottom: 15px; }
				.test-data {
					background: #f9fafb;
					padding: 15px;
					border-radius: 4px;
					font-size: 13px;
					overflow-x: auto;
				}
				.test-data table {
					width: 100%;
					border-collapse: collapse;
					margin: 10px 0;
				}
				.test-data th,
				.test-data td {
					padding: 8px;
					text-align: left;
					border-bottom: 1px solid #e5e7eb;
				}
				.test-data th {
					background: #f3f4f6;
					font-weight: 600;
				}
				.test-data code {
					background: #e5e7eb;
					padding: 2px 6px;
					border-radius: 3px;
					font-family: 'Courier New', monospace;
					font-size: 12px;
				}
				.status-ok { color: #00a32a; font-weight: 600; }
				.status-fail { color: #d63638; font-weight: 600; }
				.summary {
					background: white;
					padding: 30px;
					margin: 30px 0;
					border-radius: 8px;
					text-align: center;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				}
				.summary.success { border: 3px solid #00a32a; }
				.summary.failure { border: 3px solid #d63638; }
				.summary-icon { font-size: 48px; margin-bottom: 10px; }
				.summary-text { font-size: 20px; font-weight: 600; margin-bottom: 10px; }
				.btn {
					display: inline-block;
					background: #2271b1;
					color: white;
					padding: 12px 24px;
					text-decoration: none;
					border-radius: 4px;
					margin: 10px 5px 0 0;
					font-weight: 600;
				}
				.btn:hover { background: #135e96; }
			</style>
		</head>
		<body>
			<h1>🧪 FP Privacy - Test Traduzioni</h1>
			<p class="subtitle">Verifica automatica del sistema di traduzioni del plugin</p>
			
			<?php
			$total = count( $this->results );
			$passed = 0;
			
			foreach ( $this->results as $key => $result ) {
				if ( $result['pass'] ) {
					$passed++;
				}
				
				$class = $result['pass'] ? 'pass' : 'fail';
				$icon = $result['pass'] ? '✅' : '❌';
				?>
				<div class="test-result <?php echo esc_attr( $class ); ?>">
					<div class="test-header">
						<span class="test-icon"><?php echo $icon; ?></span>
						<span class="test-name"><?php echo esc_html( $result['name'] ); ?></span>
					</div>
					<div class="test-message"><?php echo esc_html( $result['message'] ); ?></div>
					
					<?php if ( isset( $result['data'] ) && is_array( $result['data'] ) ) : ?>
						<div class="test-data">
							<?php
							if ( $key === 'mo_files' ) {
								echo '<table>';
								echo '<tr><th>Lingua</th><th>File</th><th>Dimensione</th><th>Modificato</th></tr>';
								foreach ( $result['data'] as $lang => $info ) {
									$status = $info['exists'] ? '<span class="status-ok">✓</span>' : '<span class="status-fail">✗</span>';
									echo '<tr>';
									echo '<td><strong>' . esc_html( $lang ) . '</strong></td>';
									echo '<td>' . $status . ' <code>' . esc_html( basename( $info['path'] ) ) . '</code></td>';
									echo '<td>' . ( $info['size'] > 0 ? number_format( $info['size'] ) . ' bytes' : 'N/A' ) . '</td>';
									echo '<td>' . esc_html( $info['modified'] ) . '</td>';
									echo '</tr>';
								}
								echo '</table>';
							} elseif ( in_array( $key, array( 'banner_translations', 'palette_translations' ), true ) ) {
								echo '<table>';
								echo '<tr><th>Chiave inglese</th><th>Traduzione</th><th>Atteso</th><th>OK?</th></tr>';
								foreach ( $result['data'] as $eng_key => $trans_info ) {
									$status = $trans_info['pass'] ? '<span class="status-ok">✓</span>' : '<span class="status-fail">✗</span>';
									echo '<tr>';
									echo '<td><code>' . esc_html( $eng_key ) . '</code></td>';
									echo '<td><strong>' . esc_html( $trans_info['translated'] ) . '</strong></td>';
									echo '<td>' . esc_html( $trans_info['expected'] ) . '</td>';
									echo '<td>' . $status . '</td>';
									echo '</tr>';
								}
								echo '</table>';
							} elseif ( $key === 'rendering' ) {
								echo '<table>';
								echo '<tr><th>Chiave</th><th>Label renderizzata</th></tr>';
								foreach ( $result['data'] as $k => $v ) {
									echo '<tr>';
									echo '<td><code>' . esc_html( $k ) . '</code></td>';
									echo '<td><strong>' . esc_html( $v ) . '</strong></td>';
									echo '</tr>';
								}
								echo '</table>';
							} else {
								echo '<pre>';
								print_r( $result['data'] );
								echo '</pre>';
							}
							?>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}
			
			$all_pass = ( $passed === $total );
			$summary_class = $all_pass ? 'success' : 'failure';
			$summary_icon = $all_pass ? '🎉' : '⚠️';
			$summary_text = $all_pass ? 'Tutti i test passati!' : 'Alcuni test falliti';
			?>
			
			<div class="summary <?php echo esc_attr( $summary_class ); ?>">
				<div class="summary-icon"><?php echo $summary_icon; ?></div>
				<div class="summary-text"><?php echo esc_html( $summary_text ); ?></div>
				<div><?php echo esc_html( sprintf( '%d/%d test passati', $passed, $total ) ); ?></div>
			</div>
			
			<p style="text-align: center;">
				<a href="<?php echo admin_url( 'admin.php?page=fp-privacy-settings' ); ?>" class="btn">→ Vai alle impostazioni FP Privacy</a>
				<a href="<?php echo admin_url(); ?>" class="btn">→ Dashboard WordPress</a>
			</p>
			
		</body>
		</html>
		<?php
	}
}

// Run tests
$tester = new FP_Privacy_Translation_Tester();
$tester->run_all_tests();
$tester->output_results();

