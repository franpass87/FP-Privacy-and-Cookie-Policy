<?php
/**
 * FP Privacy - Tools Dashboard
 * 
 * Central hub for all development and testing tools.
 * 
 * @package FP\Privacy\Tools
 */

// Load WordPress
if ( ! defined( 'ABSPATH' ) ) {
	// Try multiple paths to find wp-load.php
	$paths = array(
		dirname( __FILE__ ) . '/../../../../wp-load.php',  // Standard structure
		dirname( __FILE__ ) . '/../../../../../wp-load.php', // Junction/symlink
		$_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',        // Document root
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
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Accesso negato. Devi essere amministratore.' );
}

$plugin_version = defined( 'FP_PRIVACY_PLUGIN_VERSION' ) ? FP_PRIVACY_PLUGIN_VERSION : 'N/A';
$current_locale = get_locale();
$textdomain_loaded = is_textdomain_loaded( 'fp-privacy' );

?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>FP Privacy - Tools Dashboard</title>
	<style>
		* { box-sizing: border-box; }
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			padding: 40px;
			max-width: 1400px;
			margin: 0 auto;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
		}
		.container {
			background: white;
			padding: 40px;
			border-radius: 12px;
			box-shadow: 0 10px 40px rgba(0,0,0,0.2);
		}
		h1 {
			color: #2271b1;
			margin: 0 0 10px 0;
			font-size: 32px;
		}
		.subtitle {
			color: #666;
			margin-bottom: 30px;
			font-size: 16px;
		}
		.info-bar {
			background: #f9fafb;
			padding: 15px 20px;
			border-radius: 8px;
			margin-bottom: 30px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 15px;
		}
		.info-item {
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.info-label {
			font-weight: 600;
			color: #374151;
		}
		.info-value {
			color: #666;
		}
		.status-badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: 600;
		}
		.status-ok { background: #d4edda; color: #155724; }
		.status-error { background: #f8d7da; color: #842029; }
		h2 {
			color: #374151;
			margin: 30px 0 15px 0;
			font-size: 20px;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.tools-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
			gap: 20px;
			margin: 20px 0;
		}
		.tool-card {
			background: #fff;
			border: 2px solid #e5e7eb;
			border-radius: 8px;
			padding: 24px;
			transition: all 0.2s ease;
			cursor: pointer;
		}
		.tool-card:hover {
			border-color: #2271b1;
			box-shadow: 0 4px 12px rgba(34, 113, 177, 0.15);
			transform: translateY(-2px);
		}
		.tool-icon {
			font-size: 36px;
			margin-bottom: 12px;
		}
		.tool-title {
			font-size: 18px;
			font-weight: 600;
			color: #1f2937;
			margin-bottom: 8px;
		}
		.tool-desc {
			font-size: 13px;
			color: #666;
			line-height: 1.6;
			margin-bottom: 15px;
		}
		.tool-link {
			display: inline-block;
			background: #2271b1;
			color: white;
			padding: 10px 20px;
			text-decoration: none;
			border-radius: 6px;
			font-weight: 600;
			font-size: 13px;
			transition: background 0.2s;
		}
		.tool-link:hover {
			background: #135e96;
		}
		.tool-link.secondary {
			background: #6c757d;
		}
		.tool-link.secondary:hover {
			background: #5a6268;
		}
		.footer {
			text-align: center;
			margin-top: 40px;
			padding-top: 30px;
			border-top: 2px solid #e5e7eb;
		}
		.footer a {
			color: #2271b1;
			text-decoration: none;
			font-weight: 600;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>🛠️ FP Privacy - Tools Dashboard</h1>
		<p class="subtitle">Script di sviluppo, testing e manutenzione per il plugin FP Privacy and Cookie Policy</p>
		
		<div class="info-bar">
			<div class="info-item">
				<span class="info-label">Versione:</span>
				<span class="info-value"><code><?php echo esc_html( $plugin_version ); ?></code></span>
			</div>
			<div class="info-item">
				<span class="info-label">Locale:</span>
				<span class="info-value"><code><?php echo esc_html( $current_locale ); ?></code></span>
			</div>
			<div class="info-item">
				<span class="info-label">Textdomain:</span>
				<span class="status-badge <?php echo $textdomain_loaded ? 'status-ok' : 'status-error'; ?>">
					<?php echo $textdomain_loaded ? '✓ Caricato' : '✗ Non caricato'; ?>
				</span>
			</div>
		</div>
		
		<h2>🔍 Diagnostica & Testing</h2>
		<div class="tools-grid">
			<div class="tool-card">
				<div class="tool-icon">🔍</div>
				<div class="tool-title">Diagnostica Completa</div>
				<div class="tool-desc">
					Verifica completa dello stato del plugin: core, database, traduzioni, file e integrazioni.
				</div>
				<a href="diagnostics.php" class="tool-link">Esegui diagnostica →</a>
			</div>
			
			<div class="tool-card">
				<div class="tool-icon">🧪</div>
				<div class="tool-title">Test Traduzioni</div>
				<div class="tool-desc">
					Test approfondito del sistema di traduzioni: textdomain, file .mo, banner e palette.
				</div>
				<a href="test-translations.php" class="tool-link">Esegui test →</a>
			</div>
		</div>
		
		<h2>🔧 Manutenzione Traduzioni</h2>
		<div class="tools-grid">
			<div class="tool-card">
				<div class="tool-icon">🔨</div>
				<div class="tool-title">Compila File .mo</div>
				<div class="tool-desc">
					Compila i file .po (testo) in .mo (binari). Usa dopo aver modificato le traduzioni.
				</div>
				<a href="compile-mo-files.php" class="tool-link secondary">Compila →</a>
			</div>
			
			<div class="tool-card">
				<div class="tool-icon">🔄</div>
				<div class="tool-title">Force Update Traduzioni</div>
				<div class="tool-desc">
					Forza l'aggiornamento delle traduzioni nel database e pulisce tutte le cache.
				</div>
				<a href="force-update-translations.php" class="tool-link secondary">Force Update →</a>
			</div>
		</div>
		
		<h2>📄 Generazione Contenuti</h2>
		<div class="tools-grid">
			<div class="tool-card">
				<div class="tool-icon">📝</div>
				<div class="tool-title">Genera Policy</div>
				<div class="tool-desc">
					Genera automaticamente le pagine Privacy Policy e Cookie Policy per tutte le lingue.
				</div>
				<a href="generate-policies.php" class="tool-link secondary">Genera →</a>
			</div>
		</div>
		
		<h2>📚 Documentazione</h2>
		<div class="tools-grid">
			<div class="tool-card">
				<div class="tool-icon">📖</div>
				<div class="tool-title">README Tools</div>
				<div class="tool-desc">
					Documentazione completa di tutti gli script disponibili con esempi d'uso.
				</div>
				<a href="README.md" class="tool-link secondary" target="_blank">Leggi README →</a>
			</div>
			
			<div class="tool-card">
				<div class="tool-icon">📋</div>
				<div class="tool-title">QA Checklist</div>
				<div class="tool-desc">
					Checklist per quality assurance e testing prima del rilascio.
				</div>
				<a href="qa-checklist.md" class="tool-link secondary" target="_blank">Vedi checklist →</a>
			</div>
		</div>
		
		<div class="footer">
			<p>
				<a href="<?php echo admin_url( 'admin.php?page=fp-privacy-settings' ); ?>">
					← Torna alle impostazioni FP Privacy
				</a>
				|
				<a href="<?php echo admin_url(); ?>">
					Dashboard WordPress
				</a>
			</p>
			<p style="color: #999; font-size: 12px; margin-top: 15px;">
				FP Privacy and Cookie Policy v<?php echo esc_html( $plugin_version ); ?> • 
				<a href="https://francescopasseri.com" style="color: #999;">Francesco Passeri</a>
			</p>
		</div>
	</div>
</body>
</html>

