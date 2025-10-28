<?php
/**
 * FP Privacy - Diagnostics & Health Check
 * 
 * Complete diagnostic tool for FP Privacy plugin.
 * Checks: translations, database, files, configuration, and integrations.
 * 
 * Usage:
 * - Browser: http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/diagnostics.php
 * - WP-CLI: wp eval-file wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/diagnostics.php
 * 
 * @package FP\Privacy\Tools
 */

// Load WordPress
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
		die( 'WordPress not found. Tried paths: ' . implode( ', ', $paths ) );
	}
}

// Check permissions
if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) ) {
	wp_die( 'Accesso negato.' );
}

/**
 * Diagnostic runner
 */
class FP_Privacy_Diagnostics {
	
	private $checks = array();
	private $is_cli = false;
	
	public function __construct() {
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;
	}
	
	/**
	 * Run all diagnostics
	 */
	public function run() {
		$this->check_plugin_loaded();
		$this->check_constants();
		$this->check_autoload();
		$this->check_database();
		$this->check_translations();
		$this->check_files();
		$this->check_configuration();
		$this->check_integrations();
		
		return $this->checks;
	}
	
	/**
	 * Check if plugin is loaded
	 */
	private function check_plugin_loaded() {
		$loaded = class_exists( '\\FP\\Privacy\\Plugin' );
		
		$this->checks['plugin_loaded'] = array(
			'category' => 'Core',
			'name' => 'Plugin caricato',
			'status' => $loaded ? 'ok' : 'error',
			'message' => $loaded ? 'FP\\Privacy\\Plugin trovata' : 'Plugin non caricato',
		);
	}
	
	/**
	 * Check plugin constants
	 */
	private function check_constants() {
		$required = array(
			'FP_PRIVACY_PLUGIN_FILE',
			'FP_PRIVACY_PLUGIN_VERSION',
			'FP_PRIVACY_VERSION',
			'FP_PRIVACY_PLUGIN_PATH',
			'FP_PRIVACY_PLUGIN_URL',
		);
		
		$missing = array();
		foreach ( $required as $constant ) {
			if ( ! defined( $constant ) ) {
				$missing[] = $constant;
			}
		}
		
		$this->checks['constants'] = array(
			'category' => 'Core',
			'name' => 'Costanti definite',
			'status' => empty( $missing ) ? 'ok' : 'error',
			'message' => empty( $missing ) 
				? sprintf( '%d costanti OK', count( $required ) )
				: 'Mancanti: ' . implode( ', ', $missing ),
			'data' => array(
				'version' => defined( 'FP_PRIVACY_PLUGIN_VERSION' ) ? FP_PRIVACY_PLUGIN_VERSION : 'N/A',
				'path' => defined( 'FP_PRIVACY_PLUGIN_PATH' ) ? FP_PRIVACY_PLUGIN_PATH : 'N/A',
			),
		);
	}
	
	/**
	 * Check PSR-4 autoload
	 */
	private function check_autoload() {
		$test_classes = array(
			'\\FP\\Privacy\\Plugin',
			'\\FP\\Privacy\\Utils\\Options',
			'\\FP\\Privacy\\Admin\\Settings',
			'\\FP\\Privacy\\Frontend\\Banner',
		);
		
		$loaded = array();
		$missing = array();
		
		foreach ( $test_classes as $class ) {
			if ( class_exists( $class ) ) {
				$loaded[] = $class;
			} else {
				$missing[] = $class;
			}
		}
		
		$this->checks['autoload'] = array(
			'category' => 'Core',
			'name' => 'PSR-4 Autoload',
			'status' => empty( $missing ) ? 'ok' : 'warning',
			'message' => sprintf( '%d/%d classi caricate', count( $loaded ), count( $test_classes ) ),
			'data' => array(
				'loaded' => count( $loaded ),
				'missing' => $missing,
			),
		);
	}
	
	/**
	 * Check database tables
	 */
	private function check_database() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'fp_consent_log';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
		
		$count = 0;
		if ( $table_exists ) {
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		}
		
		$this->checks['database'] = array(
			'category' => 'Database',
			'name' => 'Tabella consensi',
			'status' => $table_exists ? 'ok' : 'error',
			'message' => $table_exists 
				? sprintf( 'Tabella esiste - %d record', $count )
				: 'Tabella non trovata',
			'data' => array(
				'table' => $table_name,
				'exists' => $table_exists,
				'records' => $count,
			),
		);
	}
	
	/**
	 * Check translations
	 */
	private function check_translations() {
		$textdomain_loaded = is_textdomain_loaded( 'fp-privacy' );
		$locale = get_locale();
		
		$mo_file = WP_CONTENT_DIR . '/plugins/FP-Privacy-and-Cookie-Policy-1/languages/fp-privacy-' . $locale . '.mo';
		$mo_exists = file_exists( $mo_file );
		
		// Test a translation
		$test_key = 'We value your privacy';
		$translated = __( $test_key, 'fp-privacy' );
		$works = ( $translated !== $test_key );
		
		$status = 'ok';
		if ( ! $textdomain_loaded || ! $mo_exists || ! $works ) {
			$status = 'warning';
		}
		
		$this->checks['translations'] = array(
			'category' => 'Traduzioni',
			'name' => 'Sistema traduzioni',
			'status' => $status,
			'message' => sprintf( 'Locale: %s | Textdomain: %s', 
				$locale,
				$textdomain_loaded ? 'caricato' : 'non caricato'
			),
			'data' => array(
				'locale' => $locale,
				'textdomain_loaded' => $textdomain_loaded,
				'mo_exists' => $mo_exists,
				'mo_file' => $mo_file,
				'mo_size' => $mo_exists ? filesize( $mo_file ) : 0,
				'test_translation' => $translated,
				'works' => $works,
			),
		);
	}
	
	/**
	 * Check critical files
	 */
	private function check_files() {
		$plugin_path = WP_CONTENT_DIR . '/plugins/FP-Privacy-and-Cookie-Policy-1';
		
		$critical_files = array(
			'fp-privacy-cookie-policy.php',
			'src/Plugin.php',
			'src/Utils/Options.php',
			'assets/js/admin.js',
			'assets/css/admin.css',
		);
		
		$missing = array();
		$sizes = array();
		
		foreach ( $critical_files as $file ) {
			$full_path = $plugin_path . '/' . $file;
			if ( ! file_exists( $full_path ) ) {
				$missing[] = $file;
			} else {
				$sizes[ $file ] = filesize( $full_path );
			}
		}
		
		$this->checks['files'] = array(
			'category' => 'File',
			'name' => 'File critici',
			'status' => empty( $missing ) ? 'ok' : 'error',
			'message' => empty( $missing )
				? sprintf( '%d/%d file presenti', count( $critical_files ), count( $critical_files ) )
				: 'File mancanti: ' . implode( ', ', $missing ),
			'data' => $sizes,
		);
	}
	
	/**
	 * Check configuration
	 */
	private function check_configuration() {
		if ( ! class_exists( '\\FP\\Privacy\\Utils\\Options' ) ) {
			$this->checks['configuration'] = array(
				'category' => 'Configurazione',
				'name' => 'Opzioni plugin',
				'status' => 'error',
				'message' => 'Classe Options non disponibile',
			);
			return;
		}
		
		$options = \FP\Privacy\Utils\Options::instance();
		$all_opts = $options->all();
		
		$languages = $options->get_languages();
		$pages = isset( $all_opts['pages'] ) ? $all_opts['pages'] : array();
		$banner_texts = isset( $all_opts['banner_texts'] ) ? $all_opts['banner_texts'] : array();
		
		// Check if pages exist
		$pages_ok = 0;
		$pages_total = 0;
		foreach ( $pages as $type => $langs ) {
			if ( ! is_array( $langs ) ) continue;
			foreach ( $langs as $lang => $page_id ) {
				$pages_total++;
				if ( get_post_status( $page_id ) === 'publish' ) {
					$pages_ok++;
				}
			}
		}
		
		$this->checks['configuration'] = array(
			'category' => 'Configurazione',
			'name' => 'Opzioni e pagine',
			'status' => ( $pages_ok === $pages_total ) ? 'ok' : 'warning',
			'message' => sprintf( 'Lingue: %d | Pagine: %d/%d', count( $languages ), $pages_ok, $pages_total ),
			'data' => array(
				'languages' => $languages,
				'pages_created' => $pages_ok,
				'pages_total' => $pages_total,
				'banner_texts_langs' => array_keys( $banner_texts ),
			),
		);
	}
	
	/**
	 * Check integrations
	 */
	private function check_integrations() {
		$integrations = array();
		
		// Check FP-Multilanguage
		$fpml_active = defined( 'FPML_VERSION' ) || class_exists( 'FP\\MultiLanguage\\Plugin' );
		$integrations['FP-Multilanguage'] = $fpml_active;
		
		// Check FP Performance
		$fp_perf = defined( 'FP_PERFORMANCE_VERSION' ) || class_exists( 'FP\\Performance\\Plugin' );
		$integrations['FP Performance'] = $fp_perf;
		
		// Check WooCommerce
		$woo = class_exists( 'WooCommerce' );
		$integrations['WooCommerce'] = $woo;
		
		$active_count = count( array_filter( $integrations ) );
		
		$this->checks['integrations'] = array(
			'category' => 'Integrazioni',
			'name' => 'Plugin compatibili',
			'status' => 'ok',
			'message' => sprintf( '%d integrazioni rilevate', $active_count ),
			'data' => $integrations,
		);
	}
	
	/**
	 * Output results
	 */
	public function output() {
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
			WP_CLI::line( '🔍 FP Privacy - Diagnostica Completa' );
			WP_CLI::line( str_repeat( '=', 60 ) );
			
			$categories = array();
			foreach ( $this->checks as $check ) {
				$cat = $check['category'];
				if ( ! isset( $categories[ $cat ] ) ) {
					$categories[ $cat ] = array();
				}
				$categories[ $cat ][] = $check;
			}
			
			foreach ( $categories as $cat_name => $checks ) {
				WP_CLI::line( '' );
				WP_CLI::line( "📁 {$cat_name}" );
				WP_CLI::line( str_repeat( '-', 60 ) );
				
				foreach ( $checks as $check ) {
					$icon = $check['status'] === 'ok' ? '✅' : ( $check['status'] === 'warning' ? '⚠️' : '❌' );
					WP_CLI::line( sprintf( '%s %s: %s', $icon, $check['name'], $check['message'] ) );
				}
			}
			
			WP_CLI::line( '' );
			WP_CLI::line( str_repeat( '=', 60 ) );
			
			$errors = 0;
			$warnings = 0;
			foreach ( $this->checks as $check ) {
				if ( $check['status'] === 'error' ) $errors++;
				if ( $check['status'] === 'warning' ) $warnings++;
			}
			
			if ( $errors === 0 && $warnings === 0 ) {
				WP_CLI::success( 'Tutto OK - Nessun problema rilevato!' );
			} elseif ( $errors > 0 ) {
				WP_CLI::error( sprintf( '%d errori, %d warning', $errors, $warnings ) );
			} else {
				WP_CLI::warning( sprintf( '%d warning', $warnings ) );
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
			<title>FP Privacy - Diagnostica</title>
			<style>
				* { box-sizing: border-box; }
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					padding: 40px;
					max-width: 1400px;
					margin: 0 auto;
					background: #f5f5f5;
				}
				h1 { color: #2271b1; margin-bottom: 10px; }
				.subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
				.grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
					gap: 20px;
					margin: 20px 0;
				}
				.check-card {
					background: white;
					padding: 20px;
					border-radius: 8px;
					border-left: 4px solid #ddd;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				}
				.check-card.ok { border-left-color: #00a32a; }
				.check-card.warning { border-left-color: #f0b849; }
				.check-card.error { border-left-color: #d63638; }
				.check-header {
					display: flex;
					align-items: center;
					gap: 10px;
					margin-bottom: 10px;
				}
				.check-icon { font-size: 24px; }
				.check-name { font-weight: 600; font-size: 15px; }
				.check-category {
					display: inline-block;
					background: #f0f0f0;
					padding: 2px 8px;
					border-radius: 4px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
					color: #666;
					margin-bottom: 8px;
				}
				.check-message { color: #666; font-size: 13px; margin-bottom: 10px; }
				.check-data {
					background: #f9fafb;
					padding: 12px;
					border-radius: 4px;
					font-size: 12px;
					margin-top: 10px;
				}
				.check-data ul {
					margin: 5px 0;
					padding-left: 20px;
				}
				.check-data li {
					margin: 4px 0;
				}
				code {
					background: #e5e7eb;
					padding: 2px 6px;
					border-radius: 3px;
					font-family: 'Courier New', monospace;
					font-size: 11px;
				}
				.summary {
					background: white;
					padding: 30px;
					margin: 30px 0;
					border-radius: 8px;
					text-align: center;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				}
				.summary.ok { border: 3px solid #00a32a; }
				.summary.warning { border: 3px solid #f0b849; }
				.summary.error { border: 3px solid #d63638; }
				.summary-icon { font-size: 48px; margin-bottom: 10px; }
				.summary-text { font-size: 20px; font-weight: 600; margin-bottom: 10px; }
				.btn {
					display: inline-block;
					background: #2271b1;
					color: white;
					padding: 10px 20px;
					text-decoration: none;
					border-radius: 4px;
					margin: 10px 5px 0 0;
					font-weight: 600;
					font-size: 13px;
				}
				.btn:hover { background: #135e96; }
				.btn.secondary {
					background: #6c757d;
				}
				.btn.secondary:hover {
					background: #5a6268;
				}
			</style>
		</head>
		<body>
			<h1>🔍 FP Privacy - Diagnostica Completa</h1>
			<p class="subtitle">
				Verifica dello stato del plugin, traduzioni, database, e integrazioni.<br>
				Eseguito il: <?php echo date( 'Y-m-d H:i:s' ); ?>
			</p>
			
			<?php
			// Group by category
			$categories = array();
			foreach ( $this->checks as $key => $check ) {
				$cat = $check['category'];
				if ( ! isset( $categories[ $cat ] ) ) {
					$categories[ $cat ] = array();
				}
				$categories[ $cat ][ $key ] = $check;
			}
			
			foreach ( $categories as $cat_name => $checks ) :
				?>
				<h2 style="margin-top: 30px; color: #374151;">📁 <?php echo esc_html( $cat_name ); ?></h2>
				<div class="grid">
					<?php foreach ( $checks as $key => $check ) :
						$icon_map = array(
							'ok' => '✅',
							'warning' => '⚠️',
							'error' => '❌',
						);
						$icon = $icon_map[ $check['status'] ];
						?>
						<div class="check-card <?php echo esc_attr( $check['status'] ); ?>">
							<div class="check-category"><?php echo esc_html( $check['category'] ); ?></div>
							<div class="check-header">
								<span class="check-icon"><?php echo $icon; ?></span>
								<span class="check-name"><?php echo esc_html( $check['name'] ); ?></span>
							</div>
							<div class="check-message"><?php echo esc_html( $check['message'] ); ?></div>
							
							<?php if ( isset( $check['data'] ) && ! empty( $check['data'] ) ) : ?>
								<div class="check-data">
									<strong>Dettagli:</strong>
									<ul>
										<?php foreach ( $check['data'] as $k => $v ) : ?>
											<li>
												<strong><?php echo esc_html( $k ); ?>:</strong>
												<?php
												if ( is_bool( $v ) ) {
													echo $v ? '✓ true' : '✗ false';
												} elseif ( is_array( $v ) ) {
													echo '<code>' . esc_html( json_encode( $v ) ) . '</code>';
												} elseif ( is_numeric( $v ) ) {
													echo number_format( $v );
												} else {
													echo '<code>' . esc_html( $v ) . '</code>';
												}
												?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
			
			<?php
			// Calculate summary
			$total = count( $this->checks );
			$ok = 0;
			$warnings = 0;
			$errors = 0;
			
			foreach ( $this->checks as $check ) {
				if ( $check['status'] === 'ok' ) $ok++;
				if ( $check['status'] === 'warning' ) $warnings++;
				if ( $check['status'] === 'error' ) $errors++;
			}
			
			$summary_status = 'ok';
			$summary_icon = '🎉';
			$summary_text = 'Tutto OK!';
			
			if ( $errors > 0 ) {
				$summary_status = 'error';
				$summary_icon = '❌';
				$summary_text = 'Problemi rilevati';
			} elseif ( $warnings > 0 ) {
				$summary_status = 'warning';
				$summary_icon = '⚠️';
				$summary_text = 'Warning presenti';
			}
			?>
			
			<div class="summary <?php echo esc_attr( $summary_status ); ?>">
				<div class="summary-icon"><?php echo $summary_icon; ?></div>
				<div class="summary-text"><?php echo esc_html( $summary_text ); ?></div>
				<div style="font-size: 16px; color: #666; margin-top: 10px;">
					<?php echo sprintf( '%d OK | %d Warning | %d Errori', $ok, $warnings, $errors ); ?>
				</div>
			</div>
			
			<p style="text-align: center; margin-top: 30px;">
				<a href="test-translations.php" class="btn">🧪 Test Traduzioni</a>
				<a href="compile-mo-files.php" class="btn secondary">🔨 Compila .mo</a>
				<a href="force-update-translations.php" class="btn secondary">🔄 Force Update</a>
				<a href="<?php echo admin_url( 'admin.php?page=fp-privacy-settings' ); ?>" class="btn">→ Impostazioni Plugin</a>
			</p>
			
		</body>
		</html>
		<?php
	}
}

// Run diagnostics
$diag = new FP_Privacy_Diagnostics();
$diag->run();
$diag->output();

