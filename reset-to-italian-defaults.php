<?php
/**
 * Script per resettare il plugin FP Privacy ai valori default in italiano
 * 
 * UTILIZZO: 
 * 1. Copiare questo file nella root di WordPress
 * 2. Visitare: https://tuo-sito.local/reset-to-italian-defaults.php
 * 3. Cliccare "Reset alle impostazioni italiane"
 */

// Carica WordPress
require_once __DIR__ . '/wp-load.php';

// Verifica che siamo in ambiente di sviluppo
if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
	die( 'Questo script funziona solo in modalità debug (WP_DEBUG = true)' );
}

// Verifica utente admin
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Accesso negato. Solo gli amministratori possono eseguire questo script.' );
}

$option_key = 'fp_privacy_options';

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Reset Plugin FP Privacy - Impostazioni Italiane</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
			max-width: 900px;
			margin: 40px auto;
			padding: 20px;
			background: #f0f0f1;
		}
		.container {
			background: white;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		h1 {
			color: #1d2327;
			border-bottom: 3px solid #2271b1;
			padding-bottom: 10px;
		}
		.alert {
			padding: 15px;
			margin: 20px 0;
			border-radius: 4px;
		}
		.alert-warning {
			background: #fcf9e8;
			border-left: 4px solid #dba617;
			color: #646970;
		}
		.alert-success {
			background: #edfaef;
			border-left: 4px solid #00a32a;
			color: #1e8c3e;
		}
		.alert-info {
			background: #f0f6fc;
			border-left: 4px solid #2271b1;
			color: #1d2327;
		}
		.button {
			display: inline-block;
			padding: 10px 20px;
			margin: 5px;
			background: #2271b1;
			color: white;
			text-decoration: none;
			border-radius: 4px;
			border: none;
			cursor: pointer;
			font-size: 14px;
		}
		.button:hover {
			background: #135e96;
		}
		.button.danger {
			background: #d63638;
		}
		.button.danger:hover {
			background: #b32d2e;
		}
		.current-settings {
			background: #f6f7f7;
			padding: 15px;
			border-radius: 4px;
			margin: 20px 0;
		}
		.setting-item {
			margin: 10px 0;
			padding: 8px;
			background: white;
			border-left: 3px solid #2271b1;
		}
		.setting-label {
			font-weight: 600;
			color: #1d2327;
		}
		.setting-value {
			color: #646970;
			margin-top: 3px;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>🇮🇹 Reset Plugin FP Privacy - Impostazioni Italiane</h1>

		<?php if ( isset( $_GET['action'] ) && $_GET['action'] === 'reset' && check_admin_referer( 'reset_fp_privacy' ) ) : ?>
			<?php
			// Elimina le opzioni esistenti
			delete_option( $option_key );
			
			// Forza il plugin a ricaricare i default
			do_action( 'fp_privacy_reset_options' );
			
			// Se il plugin è attivo, forza la rigenerazione
			if ( class_exists( '\FP\Privacy\Plugin' ) ) {
				$plugin = \FP\Privacy\Plugin::instance();
				// Il plugin ricreerà automaticamente le opzioni con i nuovi default italiani
			}
			?>
			
			<div class="alert alert-success">
				<strong>✅ Reset completato con successo!</strong><br><br>
				Le impostazioni del plugin sono state resettate ai nuovi valori default in italiano.<br>
				Tutte le etichette, messaggi e titoli sono ora in italiano.
			</div>

			<div class="alert alert-info">
				<strong>📋 Cosa è stato fatto:</strong>
				<ul>
					<li>✅ Opzioni del plugin eliminate</li>
					<li>✅ Nuovi default in italiano caricati</li>
					<li>✅ Testi del banner tradotti</li>
					<li>✅ Categorie cookie tradotte</li>
				</ul>
			</div>

			<p>
				<a href="<?php echo admin_url( 'admin.php?page=fp-privacy-settings' ); ?>" class="button">
					⚙️ Vai alle Impostazioni Plugin
				</a>
				<a href="<?php echo admin_url(); ?>" class="button">
					🏠 Torna alla Dashboard
				</a>
			</p>

		<?php else : ?>
			
			<div class="alert alert-info">
				<strong>ℹ️ Informazioni</strong><br>
				Questo script resetta il plugin FP Privacy ai <strong>nuovi valori default in italiano</strong>.<br>
				Tutti i testi, etichette e messaggi saranno impostati in italiano.
			</div>

			<?php
			$current_options = get_option( $option_key );
			if ( $current_options && isset( $current_options['banner_texts']['it_IT'] ) ) {
				$current_banner = $current_options['banner_texts']['it_IT'];
				?>
				<div class="current-settings">
					<h3>📝 Impostazioni Attuali (Banner IT):</h3>
					
					<div class="setting-item">
						<div class="setting-label">Titolo Banner:</div>
						<div class="setting-value"><?php echo esc_html( $current_banner['title'] ?? 'N/A' ); ?></div>
					</div>
					
					<div class="setting-item">
						<div class="setting-label">Pulsante Accetta:</div>
						<div class="setting-value"><?php echo esc_html( $current_banner['btn_accept'] ?? 'N/A' ); ?></div>
					</div>
					
					<div class="setting-item">
						<div class="setting-label">Pulsante Rifiuta:</div>
						<div class="setting-value"><?php echo esc_html( $current_banner['btn_reject'] ?? 'N/A' ); ?></div>
					</div>
					
					<div class="setting-item">
						<div class="setting-label">Pulsante Preferenze:</div>
						<div class="setting-value"><?php echo esc_html( $current_banner['btn_prefs'] ?? 'N/A' ); ?></div>
					</div>
					
					<div class="setting-item">
						<div class="setting-label">Titolo Modale:</div>
						<div class="setting-value"><?php echo esc_html( $current_banner['modal_title'] ?? 'N/A' ); ?></div>
					</div>
				</div>
			<?php } ?>

			<div class="alert alert-warning">
				<strong>⚠️ Attenzione!</strong><br>
				Questa operazione:<br>
				<ul>
					<li>❌ Eliminerà tutte le impostazioni personalizzate correnti</li>
					<li>✅ Caricherà i nuovi default in italiano</li>
					<li>ℹ️ Le pagine Privacy/Cookie Policy esistenti NON saranno eliminate</li>
					<li>ℹ️ I log dei consensi NON saranno eliminati</li>
				</ul>
			</div>

			<h3>🇮🇹 Nuovi Valori Default (Italiano):</h3>
			<div class="current-settings">
				<div class="setting-item">
					<div class="setting-label">Titolo Banner:</div>
					<div class="setting-value">Rispettiamo la tua privacy</div>
				</div>
				<div class="setting-item">
					<div class="setting-label">Messaggio:</div>
					<div class="setting-value">Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.</div>
				</div>
				<div class="setting-item">
					<div class="setting-label">Pulsante Accetta:</div>
					<div class="setting-value">Accetta tutto</div>
				</div>
				<div class="setting-item">
					<div class="setting-label">Pulsante Rifiuta:</div>
					<div class="setting-value">Rifiuta tutto</div>
				</div>
				<div class="setting-item">
					<div class="setting-label">Pulsante Preferenze:</div>
					<div class="setting-value">Gestisci preferenze</div>
				</div>
				<div class="setting-item">
					<div class="setting-label">Titolo Modale:</div>
					<div class="setting-value">Preferenze privacy</div>
				</div>
				<div class="setting-item">
					<div class="setting-label">Categorie Cookie:</div>
					<div class="setting-value">
						<strong>Strettamente necessari</strong> • Preferenze • Statistiche • Marketing
					</div>
				</div>
			</div>

			<form method="post" action="<?php echo esc_url( add_query_arg( 'action', 'reset' ) ); ?>" onsubmit="return confirm('Sei sicuro di voler resettare TUTTE le impostazioni del plugin?\n\nQuesta operazione eliminerà le tue personalizzazioni attuali.');">
				<?php wp_nonce_field( 'reset_fp_privacy' ); ?>
				<button type="submit" class="button danger">
					🔄 Reset alle Impostazioni Italiane
				</button>
			</form>
			
			<p>
				<a href="<?php echo admin_url(); ?>" class="button">
					⬅️ Torna alla Dashboard
				</a>
			</p>

		<?php endif; ?>

		<hr style="margin: 30px 0;">
		<p style="color: #646970; font-size: 13px;">
			<strong>Nota:</strong> Dopo il reset, visita le impostazioni del plugin per verificare che tutti i testi siano in italiano. 
			Se necessario, puoi sempre personalizzarli manualmente.
		</p>
	</div>
</body>
</html>

