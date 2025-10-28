<?php
/**
 * Script per forzare l'aggiornamento dei testi in italiano nel database
 * senza eliminare le altre impostazioni
 * 
 * UTILIZZO: 
 * 1. Copiare questo file nella root di WordPress
 * 2. Visitare: https://tuo-sito.local/force-update-italian-texts.php
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

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Aggiorna Testi Banner in Italiano</title>
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
		.button.success {
			background: #00a32a;
		}
		.button.success:hover {
			background: #008a20;
		}
		.code {
			background: #f6f7f7;
			padding: 15px;
			border-radius: 4px;
			font-family: 'Courier New', monospace;
			overflow-x: auto;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>🇮🇹 Aggiorna Testi Banner in Italiano</h1>

		<?php if ( isset( $_GET['action'] ) && $_GET['action'] === 'update' && check_admin_referer( 'update_italian_texts' ) ) : ?>
			<?php
			$option_key = 'fp_privacy_options';
			$options = get_option( $option_key, array() );
			
			// Testi italiani
			$italian_texts = array(
				'title'              => 'Rispettiamo la tua privacy',
				'message'            => 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
				'btn_accept'         => 'Accetta tutto',
				'btn_reject'         => 'Rifiuta tutto',
				'btn_prefs'          => 'Gestisci preferenze',
				'modal_title'        => 'Preferenze privacy',
				'modal_close'        => 'Chiudi preferenze',
				'modal_save'         => 'Salva preferenze',
				'revision_notice'    => 'Abbiamo aggiornato la nostra policy. Ti invitiamo a rivedere le tue preferenze.',
				'toggle_locked'      => 'Sempre attivo',
				'toggle_enabled'     => 'Abilitato',
				'debug_label'        => 'Cookie debug:',
				'link_privacy_policy' => 'Privacy Policy',
				'link_cookie_policy'  => 'Cookie Policy',
			);
			
			// Aggiorna i testi per it_IT
			if ( isset( $options['banner_texts']['it_IT'] ) ) {
				// Mantieni il link_policy esistente
				$existing_link = isset( $options['banner_texts']['it_IT']['link_policy'] ) 
					? $options['banner_texts']['it_IT']['link_policy'] 
					: '';
				
				$italian_texts['link_policy'] = $existing_link;
				$options['banner_texts']['it_IT'] = array_merge( $options['banner_texts']['it_IT'], $italian_texts );
			} else {
				$italian_texts['link_policy'] = '';
				$options['banner_texts']['it_IT'] = $italian_texts;
			}
			
			// Aggiorna categorie cookie in italiano
			if ( isset( $options['script_rules']['it_IT'] ) ) {
				$categories_it = array(
					'necessary' => array(
						'label' => 'Strettamente necessari',
						'description' => 'Cookie essenziali richiesti per il funzionamento del sito web e non possono essere disabilitati.',
					),
					'preferences' => array(
						'label' => 'Preferenze',
						'description' => 'Memorizzano le preferenze utente come lingua o posizione.',
					),
					'statistics' => array(
						'label' => 'Statistiche',
						'description' => 'Raccolgono statistiche anonime per migliorare i nostri servizi.',
					),
					'marketing' => array(
						'label' => 'Marketing',
						'description' => 'Abilitano la pubblicità personalizzata e il tracciamento.',
					),
				);
				
				foreach ( $categories_it as $cat_key => $cat_data ) {
					if ( isset( $options['script_rules']['it_IT'][ $cat_key ] ) ) {
						$options['script_rules']['it_IT'][ $cat_key ]['label'] = $cat_data['label'];
						$options['script_rules']['it_IT'][ $cat_key ]['description'] = $cat_data['description'];
					}
				}
			}
			
			// Salva le opzioni aggiornate
			update_option( $option_key, $options );
			?>
			
			<div class="alert alert-success">
				<strong>✅ Aggiornamento completato con successo!</strong><br><br>
				I testi del banner sono stati aggiornati in italiano.<br>
				Ricarica la pagina delle impostazioni per vedere le modifiche.
			</div>

			<div class="alert alert-info">
				<strong>📋 Testi aggiornati:</strong>
				<ul>
					<li>✅ Titolo banner</li>
					<li>✅ Messaggio</li>
					<li>✅ Pulsanti (Accetta/Rifiuta/Preferenze)</li>
					<li>✅ Etichette modale</li>
					<li>✅ Categorie cookie</li>
				</ul>
			</div>

			<p>
				<a href="<?php echo admin_url( 'admin.php?page=fp-privacy-settings' ); ?>" class="button success">
					⚙️ Vai alle Impostazioni (Ricarica per vedere i cambiamenti)
				</a>
				<a href="<?php echo admin_url(); ?>" class="button">
					🏠 Torna alla Dashboard
				</a>
			</p>

		<?php else : ?>
			
			<div class="alert alert-info">
				<strong>ℹ️ Informazioni</strong><br>
				Questo script aggiorna <strong>SOLO i testi del banner</strong> in italiano,
				mantenendo tutte le altre impostazioni (colori, layout, servizi rilevati, ecc.).
			</div>

			<h3>🇮🇹 Testi che verranno aggiornati:</h3>
			<div class="code">
				<strong>Titolo:</strong> Rispettiamo la tua privacy<br>
				<strong>Messaggio:</strong> Utilizziamo i cookie per migliorare la tua esperienza...<br>
				<strong>Pulsante Accetta:</strong> Accetta tutto<br>
				<strong>Pulsante Rifiuta:</strong> Rifiuta tutto<br>
				<strong>Pulsante Preferenze:</strong> Gestisci preferenze<br>
				<strong>Titolo Modale:</strong> Preferenze privacy<br>
				<strong>Chiudi Modale:</strong> Chiudi preferenze<br>
				<strong>Salva:</strong> Salva preferenze<br>
				<br>
				<strong>Categorie Cookie:</strong><br>
				• Strettamente necessari<br>
				• Preferenze<br>
				• Statistiche<br>
				• Marketing
			</div>

			<h3>⚠️ Cosa NON verrà modificato:</h3>
			<ul>
				<li>✅ Colori e palette</li>
				<li>✅ Layout e posizione</li>
				<li>✅ Servizi rilevati</li>
				<li>✅ Regole di blocco script</li>
				<li>✅ Log dei consensi</li>
				<li>✅ Link alla policy (mantenuto)</li>
			</ul>

			<form method="post" action="<?php echo esc_url( add_query_arg( 'action', 'update' ) ); ?>">
				<?php wp_nonce_field( 'update_italian_texts' ); ?>
				<button type="submit" class="button success">
					🔄 Aggiorna Testi in Italiano
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
			<strong>Nota:</strong> Dopo l'aggiornamento, vai nelle impostazioni del plugin e ricarica la pagina (F5) 
			per vedere i testi aggiornati nella preview.
		</p>
	</div>
</body>
</html>

