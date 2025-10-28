<?php
/**
 * Script per eliminare le pagine duplicate create dal plugin FP Privacy
 * 
 * UTILIZZO: 
 * 1. Copiare questo file nella root di WordPress
 * 2. Visitare: https://tuo-sito.local/cleanup-duplicate-pages.php
 * 3. Seguire le istruzioni a schermo
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

// Meta key usato dal plugin per identificare le pagine gestite
$meta_key = '_fp_privacy_managed_signature';

// Cerca tutte le pagine con questo meta key
$args = array(
	'post_type'      => 'page',
	'posts_per_page' => -1,
	'post_status'    => array( 'publish', 'draft', 'trash', 'pending' ),
	'meta_key'       => $meta_key,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

$pages = get_posts( $args );

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Pulizia Pagine Privacy Policy Duplicate</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
			max-width: 1200px;
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
		.page-list {
			margin: 20px 0;
		}
		.page-item {
			padding: 15px;
			margin: 10px 0;
			background: #f6f7f7;
			border-left: 4px solid #2271b1;
			border-radius: 4px;
		}
		.page-item.trash {
			border-left-color: #d63638;
			opacity: 0.7;
		}
		.page-item.draft {
			border-left-color: #dba617;
		}
		.page-title {
			font-size: 16px;
			font-weight: 600;
			color: #1d2327;
		}
		.page-meta {
			font-size: 13px;
			color: #646970;
			margin-top: 5px;
		}
		.status-badge {
			display: inline-block;
			padding: 2px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
		}
		.status-publish {
			background: #00a32a;
			color: white;
		}
		.status-draft {
			background: #dba617;
			color: white;
		}
		.status-trash {
			background: #d63638;
			color: white;
		}
		.actions {
			margin-top: 30px;
			padding: 20px;
			background: #f0f6fc;
			border-radius: 4px;
			border: 1px solid #c3e1ff;
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
		.stats {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 15px;
			margin: 20px 0;
		}
		.stat-box {
			padding: 20px;
			background: #f6f7f7;
			border-radius: 4px;
			text-align: center;
		}
		.stat-number {
			font-size: 32px;
			font-weight: 700;
			color: #2271b1;
		}
		.stat-label {
			font-size: 13px;
			color: #646970;
			margin-top: 5px;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>🧹 Pulizia Pagine Privacy Policy Duplicate</h1>

		<?php if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && check_admin_referer( 'delete_privacy_pages' ) ) : ?>
			<?php
			$deleted_count = 0;
			$error_count = 0;

			foreach ( $pages as $page ) {
				// Elimina permanentemente (anche dal cestino)
				$result = wp_delete_post( $page->ID, true );
				
				if ( $result ) {
					$deleted_count++;
				} else {
					$error_count++;
				}
			}
			?>
			
			<div class="alert alert-success">
				<strong>✅ Operazione completata!</strong><br>
				• <strong><?php echo $deleted_count; ?></strong> pagine eliminate con successo<br>
				<?php if ( $error_count > 0 ) : ?>
					• <strong><?php echo $error_count; ?></strong> errori durante l'eliminazione
				<?php endif; ?>
			</div>

			<p><a href="<?php echo admin_url( 'edit.php?post_type=page' ); ?>" class="button">Visualizza Pagine Rimanenti</a></p>

		<?php else : ?>
			
			<div class="stats">
				<div class="stat-box">
					<div class="stat-number"><?php echo count( $pages ); ?></div>
					<div class="stat-label">Pagine Totali</div>
				</div>
				<div class="stat-box">
					<div class="stat-number">
						<?php echo count( array_filter( $pages, function( $p ) { return $p->post_status === 'publish'; } ) ); ?>
					</div>
					<div class="stat-label">Pubblicate</div>
				</div>
				<div class="stat-box">
					<div class="stat-number">
						<?php echo count( array_filter( $pages, function( $p ) { return $p->post_status === 'draft'; } ) ); ?>
					</div>
					<div class="stat-label">Bozze</div>
				</div>
				<div class="stat-box">
					<div class="stat-number">
						<?php echo count( array_filter( $pages, function( $p ) { return $p->post_status === 'trash'; } ) ); ?>
					</div>
					<div class="stat-label">Nel Cestino</div>
				</div>
			</div>

			<?php if ( empty( $pages ) ) : ?>
				<div class="alert alert-success">
					<strong>✅ Tutto pulito!</strong><br>
					Non ci sono pagine duplicate gestite dal plugin FP Privacy.
				</div>
			<?php else : ?>
				
				<div class="alert alert-warning">
					<strong>⚠️ Attenzione!</strong><br>
					Trovate <strong><?php echo count( $pages ); ?> pagine</strong> create automaticamente dal plugin FP Privacy.<br>
					Queste pagine hanno il meta key <code>_fp_privacy_managed_signature</code> e possono essere eliminate in sicurezza.
				</div>

				<div class="page-list">
					<h2>Pagine da Eliminare:</h2>
					<?php foreach ( $pages as $page ) : ?>
						<div class="page-item <?php echo $page->post_status; ?>">
							<div class="page-title">
								<?php echo esc_html( $page->post_title ); ?>
								<span class="status-badge status-<?php echo $page->post_status; ?>">
									<?php echo $page->post_status; ?>
								</span>
							</div>
							<div class="page-meta">
								ID: <?php echo $page->ID; ?> | 
								Creata: <?php echo get_the_date( 'd/m/Y H:i', $page ); ?> |
								Slug: <code><?php echo $page->post_name; ?></code>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="actions">
					<h3>Azioni Disponibili:</h3>
					<p>Scegli cosa fare con queste pagine:</p>
					
					<form method="post" action="<?php echo esc_url( add_query_arg( 'action', 'delete' ) ); ?>" onsubmit="return confirm('Sei sicuro di voler eliminare TUTTE le <?php echo count( $pages ); ?> pagine?\n\nQuesta operazione è IRREVERSIBILE!');">
						<?php wp_nonce_field( 'delete_privacy_pages' ); ?>
						<button type="submit" class="button danger">
							🗑️ Elimina TUTTE le <?php echo count( $pages ); ?> Pagine (PERMANENTE)
						</button>
					</form>
					
					<a href="<?php echo admin_url( 'edit.php?post_type=page' ); ?>" class="button">
						📄 Visualizza in WordPress Admin
					</a>
					
					<a href="<?php echo admin_url(); ?>" class="button">
						⬅️ Torna alla Dashboard
					</a>
				</div>

			<?php endif; ?>

		<?php endif; ?>

		<hr style="margin: 30px 0;">
		<p style="color: #646970; font-size: 13px;">
			<strong>Nota:</strong> Questo script elimina solo le pagine gestite automaticamente dal plugin FP Privacy (identificate dal meta key <code>_fp_privacy_managed_signature</code>). 
			Le altre pagine del sito non verranno toccate.
		</p>
	</div>
</body>
</html>

