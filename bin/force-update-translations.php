<?php
/**
 * Force update banner translations in database
 * 
 * This script removes any cached English texts from the database
 * and forces the plugin to use the correct translations from .mo files.
 * 
 * Usage:
 * - Browser: http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/force-update-translations.php
 * - WP-CLI: wp eval-file wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/force-update-translations.php
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
		die( 'WordPress not found. Tried: ' . implode( ', ', $paths ) );
	}
}

// Check permissions
if ( ! current_user_can( 'manage_options' ) && ! defined( 'WP_CLI' ) ) {
	wp_die( 'Accesso negato.' );
}

// Clear caches
wp_cache_flush();
if ( function_exists( 'opcache_reset' ) ) {
	opcache_reset();
}

// Unload textdomain
global $l10n;
if ( isset( $l10n['fp-privacy'] ) ) {
	unset( $l10n['fp-privacy'] );
}
unload_textdomain( 'fp-privacy' );

// Get Options instance
if ( ! class_exists( '\\FP\\Privacy\\Utils\\Options' ) ) {
	wp_die( 'Plugin FP Privacy non trovato.' );
}

$options = \FP\Privacy\Utils\Options::instance();

// Get current banner texts
$current_texts = $options->get( 'banner_texts', array() );
$before = $current_texts;

// Force update with translations
$options->force_update_banner_texts_translations();

// Get updated texts
$updated_texts = $options->get( 'banner_texts', array() );

// Clear caches again
wp_cache_flush();

// Output
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::success( 'Traduzioni banner aggiornate!' );
	
	foreach ( $options->get_languages() as $lang ) {
		WP_CLI::line( '' );
		WP_CLI::line( sprintf( '📍 Lingua: %s', $lang ) );
		
		if ( isset( $updated_texts[ $lang ] ) ) {
			foreach ( $updated_texts[ $lang ] as $key => $value ) {
				$changed = ! isset( $before[ $lang ][ $key ] ) || $before[ $lang ][ $key ] !== $value;
				$icon = $changed ? '🔄' : '✓';
				WP_CLI::line( sprintf( '   %s %s: %s', $icon, $key, substr( $value, 0, 50 ) ) );
			}
		}
	}
} else {
	?>
	<!DOCTYPE html>
	<html lang="it">
	<head>
		<meta charset="UTF-8">
		<title>FP Privacy - Aggiornamento Traduzioni</title>
		<style>
			body {
				font-family: sans-serif;
				padding: 40px;
				max-width: 1000px;
				margin: 0 auto;
				background: #f5f5f5;
			}
			h1 { color: #2271b1; }
			.success {
				background: #d4edda;
				border: 1px solid #c3e6cb;
				color: #155724;
				padding: 20px;
				border-radius: 8px;
				margin: 20px 0;
			}
			.card {
				background: white;
				padding: 20px;
				margin: 15px 0;
				border-radius: 8px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}
			table {
				width: 100%;
				border-collapse: collapse;
				margin: 15px 0;
			}
			th, td {
				padding: 10px;
				text-align: left;
				border-bottom: 1px solid #e5e7eb;
			}
			th {
				background: #f9fafb;
				font-weight: 600;
			}
			.changed { background: #fff3cd; }
			code {
				background: #f0f0f0;
				padding: 2px 6px;
				border-radius: 3px;
				font-family: monospace;
				font-size: 12px;
			}
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
		<h1>🔄 Aggiornamento traduzioni completato</h1>
		
		<div class="success">
			<h2 style="margin-top: 0;">✅ Operazioni eseguite:</h2>
			<ul>
				<li>✅ Cache WordPress pulita</li>
				<li>✅ OPcache resettata</li>
				<li>✅ Textdomain ricaricato</li>
				<li>✅ Traduzioni banner forzate nel database</li>
			</ul>
		</div>
		
		<?php
		$languages = $options->get_languages();
		
		foreach ( $languages as $lang ) :
			if ( ! isset( $updated_texts[ $lang ] ) ) continue;
			?>
			<div class="card">
				<h2>📍 Lingua: <?php echo esc_html( $lang ); ?></h2>
				
				<table>
					<thead>
						<tr>
							<th>Campo</th>
							<th>Valore</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $updated_texts[ $lang ] as $key => $value ) :
							$changed = ! isset( $before[ $lang ][ $key ] ) || $before[ $lang ][ $key ] !== $value;
							$row_class = $changed ? 'changed' : '';
							?>
							<tr class="<?php echo esc_attr( $row_class ); ?>">
								<td><code><?php echo esc_html( $key ); ?></code></td>
								<td><strong><?php echo esc_html( $value ); ?></strong></td>
								<td>
									<?php if ( $changed ) : ?>
										🔄 Aggiornato
									<?php else : ?>
										✓ OK
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>
		
		<p style="text-align: center; margin-top: 40px;">
			<a href="<?php echo admin_url( 'admin.php?page=fp-privacy-settings' ); ?>" class="btn">→ Vai alle impostazioni FP Privacy</a>
		</p>
		
	</body>
	</html>
	<?php
}

