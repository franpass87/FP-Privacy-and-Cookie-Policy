<?php
/**
 * Script per aggiornare i default italiani nel database
 * 
 * Uso: visita questo file dal browser una volta sola
 */

require_once __DIR__ . '/../../../wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Non hai i permessi per eseguire questo script.' );
}

// Default italiani
$italian_defaults = array(
	'title'               => 'Rispettiamo la tua privacy',
	'message'             => 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
	'btn_accept'          => 'Accetta tutti',
	'btn_reject'          => 'Rifiuta tutti',
	'btn_prefs'           => 'Gestisci preferenze',
	'modal_title'         => 'Preferenze privacy',
	'modal_close'         => 'Chiudi preferenze',
	'modal_save'          => 'Salva preferenze',
	'revision_notice'     => 'Abbiamo aggiornato la nostra policy. Rivedi le tue preferenze.',
	'toggle_locked'       => 'Sempre attivo',
	'toggle_enabled'      => 'Abilitato',
	'debug_label'         => 'Debug cookie:',
	'link_privacy_policy' => 'Informativa sulla Privacy',
	'link_cookie_policy'  => 'Cookie Policy',
);

// Ottieni le opzioni attuali
$options = get_option( 'fp_privacy_settings', array() );

// Verifica se esiste già la sezione banner_texts
if ( ! isset( $options['banner_texts'] ) ) {
	$options['banner_texts'] = array();
}

// Aggiorna o crea i testi per it_IT
$options['banner_texts']['it_IT'] = $italian_defaults;

// Salva nel database
$updated = update_option( 'fp_privacy_settings', $options );

?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Aggiornamento Testi Italiani - FP Privacy</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			max-width: 800px;
			margin: 50px auto;
			padding: 20px;
			background: #f0f0f1;
		}
		.box {
			background: white;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.13);
		}
		.success {
			background: #d4edda;
			color: #155724;
			padding: 15px;
			border-radius: 4px;
			border-left: 4px solid #28a745;
			margin-bottom: 20px;
		}
		.info {
			background: #d1ecf1;
			color: #0c5460;
			padding: 15px;
			border-radius: 4px;
			border-left: 4px solid #17a2b8;
			margin-bottom: 20px;
		}
		h1 {
			margin-top: 0;
			color: #23282d;
		}
		.button {
			display: inline-block;
			background: #2271b1;
			color: white;
			padding: 10px 20px;
			text-decoration: none;
			border-radius: 3px;
			margin-top: 15px;
		}
		.button:hover {
			background: #135e96;
		}
		code {
			background: #f6f7f7;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: Consolas, Monaco, monospace;
		}
		ul {
			line-height: 1.8;
		}
	</style>
</head>
<body>
	<div class="box">
		<h1>✅ Aggiornamento Testi Italiani</h1>
		
		<?php if ( $updated !== false ) : ?>
			<div class="success">
				<strong>✓ Completato!</strong> I testi italiani sono stati aggiornati nel database.
			</div>
		<?php else : ?>
			<div class="info">
				<strong>ℹ Info:</strong> I testi italiani erano già aggiornati (nessuna modifica necessaria).
			</div>
		<?php endif; ?>

		<h2>Testi aggiornati per it_IT:</h2>
		<ul>
			<?php foreach ( $italian_defaults as $key => $value ) : ?>
				<li><code><?php echo esc_html( $key ); ?></code>: <?php echo esc_html( $value ); ?></li>
			<?php endforeach; ?>
		</ul>

		<h2>Prossimi passi:</h2>
		<ol>
			<li>Vai su <strong>Impostazioni → FP Privacy</strong></li>
			<li>Seleziona la lingua <code>it_IT</code> nel campo "Language"</li>
			<li>Verifica che tutti i testi siano ora in italiano</li>
			<li>Premi CTRL+F5 per ricaricare la pagina (hard refresh)</li>
		</ol>

		<a href="<?php echo admin_url( 'options-general.php?page=fp-privacy' ); ?>" class="button">
			Vai alle Impostazioni FP Privacy
		</a>

		<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 13px;">
			<strong>Nota:</strong> Questo script può essere eseguito più volte senza problemi. 
			Puoi eliminarlo dopo aver verificato che tutto funzioni correttamente.
		</div>
	</div>
</body>
</html>

