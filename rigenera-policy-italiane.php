<?php
/**
 * Script per rigenerare automaticamente Cookie Policy e Privacy Policy in italiano
 * 
 * Uso: visita questo file dal browser
 */

require_once __DIR__ . '/../../../wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Non hai i permessi per eseguire questo script.' );
}

// Verifica che il plugin sia attivo
if ( ! class_exists( 'FP\Privacy\Plugin' ) ) {
	wp_die( 'Il plugin FP Privacy non è attivo.' );
}

// Ottieni le istanze necessarie
global $fp_privacy_plugin;

if ( ! $fp_privacy_plugin ) {
	wp_die( 'Impossibile inizializzare il plugin FP Privacy.' );
}

$options   = $fp_privacy_plugin->get_options();
$generator = $fp_privacy_plugin->get_policy_generator();
$page_mgr  = $fp_privacy_plugin->get_page_manager();

// Lingua italiana
$lang = 'it_IT';

$results = array(
	'cookie_policy'  => false,
	'privacy_policy' => false,
	'errors'         => array(),
);

// Genera Cookie Policy
try {
	$cookie_content = $generator->generate_cookie_policy( $lang );
	
	if ( ! empty( $cookie_content ) ) {
		// Trova o crea la pagina Cookie Policy
		$cookie_page_id = $page_mgr->get_or_create_page( 'cookie', $lang );
		
		if ( $cookie_page_id ) {
			// Aggiorna il contenuto della pagina
			$updated = wp_update_post( array(
				'ID'           => $cookie_page_id,
				'post_content' => $cookie_content,
			), true );
			
			if ( ! is_wp_error( $updated ) ) {
				$results['cookie_policy'] = $cookie_page_id;
			} else {
				$results['errors'][] = 'Errore aggiornamento Cookie Policy: ' . $updated->get_error_message();
			}
		} else {
			$results['errors'][] = 'Impossibile creare/trovare la pagina Cookie Policy.';
		}
	} else {
		$results['errors'][] = 'Contenuto Cookie Policy vuoto.';
	}
} catch ( Exception $e ) {
	$results['errors'][] = 'Eccezione Cookie Policy: ' . $e->getMessage();
}

// Genera Privacy Policy
try {
	$privacy_content = $generator->generate_privacy_policy( $lang );
	
	if ( ! empty( $privacy_content ) ) {
		// Trova o crea la pagina Privacy Policy
		$privacy_page_id = $page_mgr->get_or_create_page( 'privacy', $lang );
		
		if ( $privacy_page_id ) {
			// Aggiorna il contenuto della pagina
			$updated = wp_update_post( array(
				'ID'           => $privacy_page_id,
				'post_content' => $privacy_content,
			), true );
			
			if ( ! is_wp_error( $updated ) ) {
				$results['privacy_policy'] = $privacy_page_id;
			} else {
				$results['errors'][] = 'Errore aggiornamento Privacy Policy: ' . $updated->get_error_message();
			}
		} else {
			$results['errors'][] = 'Impossibile creare/trovare la pagina Privacy Policy.';
		}
	} else {
		$results['errors'][] = 'Contenuto Privacy Policy vuoto.';
	}
} catch ( Exception $e ) {
	$results['errors'][] = 'Eccezione Privacy Policy: ' . $e->getMessage();
}

// Salva timestamp generazione
$snapshots = $options->get( 'snapshots', array() );
if ( ! isset( $snapshots['policies'] ) ) {
	$snapshots['policies'] = array();
}

$timestamp = time();

if ( $results['cookie_policy'] ) {
	if ( ! isset( $snapshots['policies']['cookie'] ) ) {
		$snapshots['policies']['cookie'] = array();
	}
	$snapshots['policies']['cookie'][$lang] = array(
		'generated_at' => $timestamp,
		'page_id'      => $results['cookie_policy'],
	);
}

if ( $results['privacy_policy'] ) {
	if ( ! isset( $snapshots['policies']['privacy'] ) ) {
		$snapshots['policies']['privacy'] = array();
	}
	$snapshots['policies']['privacy'][$lang] = array(
		'generated_at' => $timestamp,
		'page_id'      => $results['privacy_policy'],
	);
}

$options->set( 'snapshots', $snapshots );
$options->save();

?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Rigenerazione Policy - FP Privacy</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			max-width: 900px;
			margin: 50px auto;
			padding: 20px;
			background: #f0f0f1;
		}
		.box {
			background: white;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.13);
			margin-bottom: 20px;
		}
		.success {
			background: #d4edda;
			color: #155724;
			padding: 15px;
			border-radius: 4px;
			border-left: 4px solid #28a745;
			margin-bottom: 20px;
		}
		.error {
			background: #f8d7da;
			color: #721c24;
			padding: 15px;
			border-radius: 4px;
			border-left: 4px solid #dc3545;
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
		h2 {
			color: #1d2327;
			border-bottom: 1px solid #dcdcde;
			padding-bottom: 10px;
		}
		.button {
			display: inline-block;
			background: #2271b1;
			color: white;
			padding: 10px 20px;
			text-decoration: none;
			border-radius: 3px;
			margin-right: 10px;
			margin-top: 10px;
		}
		.button:hover {
			background: #135e96;
		}
		.button-secondary {
			background: #f0f0f1;
			color: #2c3338;
			border: 1px solid #8c8f94;
		}
		.button-secondary:hover {
			background: #fff;
		}
		ul {
			line-height: 1.8;
		}
		code {
			background: #f6f7f7;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: Consolas, Monaco, monospace;
		}
		.page-link {
			display: block;
			padding: 10px;
			background: #f6f7f7;
			border-left: 3px solid #2271b1;
			margin: 10px 0;
			border-radius: 3px;
		}
		.page-link a {
			text-decoration: none;
			font-weight: 600;
		}
	</style>
</head>
<body>
	<div class="box">
		<h1>✅ Rigenerazione Policy in Italiano</h1>
		
		<?php if ( ! empty( $results['errors'] ) ) : ?>
			<div class="error">
				<strong>⚠ Errori durante la generazione:</strong>
				<ul>
					<?php foreach ( $results['errors'] as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( $results['cookie_policy'] ) : ?>
			<div class="success">
				<strong>✓ Cookie Policy rigenerata con successo!</strong>
			</div>
			<div class="page-link">
				Pagina creata: <a href="<?php echo get_edit_post_link( $results['cookie_policy'] ); ?>">Modifica Cookie Policy</a> | 
				<a href="<?php echo get_permalink( $results['cookie_policy'] ); ?>" target="_blank">Visualizza sul sito</a>
			</div>
		<?php endif; ?>

		<?php if ( $results['privacy_policy'] ) : ?>
			<div class="success">
				<strong>✓ Privacy Policy rigenerata con successo!</strong>
			</div>
			<div class="page-link">
				Pagina creata: <a href="<?php echo get_edit_post_link( $results['privacy_policy'] ); ?>">Modifica Privacy Policy</a> | 
				<a href="<?php echo get_permalink( $results['privacy_policy'] ); ?>" target="_blank">Visualizza sul sito</a>
			</div>
		<?php endif; ?>

		<h2>Dettagli Generazione</h2>
		<ul>
			<li><strong>Lingua:</strong> <?php echo esc_html( $lang ); ?></li>
			<li><strong>Data/Ora:</strong> <?php echo wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ); ?></li>
			<li><strong>Timestamp:</strong> <?php echo esc_html( $timestamp ); ?></li>
		</ul>

		<h2>Prossimi Passi</h2>
		<div class="info">
			Le policy sono state generate in italiano utilizzando i template aggiornati. I contenuti includono tutti gli elementi richiesti dal GDPR e dalla Direttiva ePrivacy.
		</div>

		<ol>
			<li>Verifica il contenuto delle pagine generate</li>
			<li>Personalizza i testi se necessario</li>
			<li>Configura i dati del titolare del trattamento in <strong>Impostazioni → FP Privacy → Tab Privacy</strong></li>
			<li>Aggiungi i link alle policy nel footer o menu del sito</li>
			<li>Configura le categorie cookie e i servizi utilizzati</li>
		</ol>

		<h2>Link Rapidi</h2>
		<a href="<?php echo admin_url( 'options-general.php?page=fp-privacy' ); ?>" class="button">
			Vai alle Impostazioni
		</a>
		<a href="<?php echo admin_url( 'edit.php?post_type=page' ); ?>" class="button button-secondary">
			Tutte le Pagine
		</a>
		<?php if ( $results['cookie_policy'] ) : ?>
			<a href="<?php echo get_permalink( $results['cookie_policy'] ); ?>" class="button button-secondary" target="_blank">
				Vedi Cookie Policy
			</a>
		<?php endif; ?>
		<?php if ( $results['privacy_policy'] ) : ?>
			<a href="<?php echo get_permalink( $results['privacy_policy'] ); ?>" class="button button-secondary" target="_blank">
				Vedi Privacy Policy
			</a>
		<?php endif; ?>

		<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 13px;">
			<strong>Nota:</strong> Questo script può essere eseguito più volte. Le pagine esistenti verranno aggiornate con i nuovi contenuti.
			Puoi eliminare questo file dopo aver verificato che tutto funzioni correttamente.
		</div>
	</div>
</body>
</html>

