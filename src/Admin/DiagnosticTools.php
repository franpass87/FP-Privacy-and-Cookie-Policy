<?php
/**
 * Diagnostic and setup tools for FP Privacy.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Utils\Options;

/**
 * Provides diagnostic and setup tools in admin.
 */
class DiagnosticTools {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Log model.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Constructor.
	 *
	 * @param Options  $options   Options handler.
	 * @param LogModel $log_model Log model.
	 */
	public function __construct( Options $options, LogModel $log_model ) {
		$this->options   = $options;
		$this->log_model = $log_model;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'admin_menu', array( $this, 'add_menu_page' ), 100 );
		\add_action( 'admin_post_fp_privacy_setup_defaults', array( $this, 'handle_setup_defaults' ) );
		\add_action( 'admin_post_fp_privacy_force_banner', array( $this, 'handle_force_banner' ) );
		\add_action( 'admin_post_fp_privacy_disable_preview', array( $this, 'handle_disable_preview' ) );
		\add_action( 'admin_post_fp_privacy_clear_consent', array( $this, 'handle_clear_consent' ) );
	}

	/**
	 * Add diagnostic tools submenu page.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		\add_submenu_page(
			'fp-privacy',
			\__( 'Strumenti Diagnostica', 'fp-privacy' ),
			\__( 'Diagnostica', 'fp-privacy' ),
			'manage_options',
			'fp-privacy-diagnostics',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render diagnostic tools page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$consent_state  = new ConsentState( $this->options, $this->log_model );
		$lang           = \determine_locale();
		$frontend_state = $consent_state->get_frontend_state( $lang );
		$all_options    = $this->options->all();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( \__( 'Strumenti Diagnostica FP Privacy', 'fp-privacy' ) ); ?></h1>

			<?php $this->render_notices(); ?>

			<div class="fp-privacy-diagnostic-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
				
				<!-- Colonna 1: Stato Corrente -->
				<div>
					<div class="card">
						<h2 class="title"><?php esc_html_e( 'Stato Corrente', 'fp-privacy' ); ?></h2>
						<table class="widefat striped">
							<tbody>
								<tr>
									<td><strong><?php esc_html_e( 'Banner Visibile', 'fp-privacy' ); ?></strong></td>
									<td>
										<?php if ( ! empty( $frontend_state['state']['should_display'] ) ) : ?>
											<span style="color: #46b450;">✓ <?php esc_html_e( 'SÌ', 'fp-privacy' ); ?></span>
										<?php else : ?>
											<span style="color: #dc3232;">✗ <?php esc_html_e( 'NO', 'fp-privacy' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Modalità Preview', 'fp-privacy' ); ?></strong></td>
									<td>
										<?php echo ! empty( $frontend_state['state']['preview_mode'] ) ? esc_html__( 'ATTIVA', 'fp-privacy' ) : esc_html__( 'DISATTIVA', 'fp-privacy' ); ?>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Consent ID', 'fp-privacy' ); ?></strong></td>
									<td>
										<?php
										$consent_id = $frontend_state['state']['consent_id'] ?? '';
										if ( $consent_id ) {
											echo '<code>' . esc_html( substr( $consent_id, 0, 16 ) ) . '...</code>';
										} else {
											echo '<em>' . esc_html__( 'Nessuno', 'fp-privacy' ) . '</em>';
										}
										?>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Revisione', 'fp-privacy' ); ?></strong></td>
									<td><code><?php echo esc_html( $frontend_state['state']['revision'] ?? '1' ); ?></code></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Lingua', 'fp-privacy' ); ?></strong></td>
									<td><code><?php echo esc_html( $frontend_state['state']['lang'] ?? 'N/A' ); ?></code></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="card" style="margin-top: 20px;">
						<h2 class="title"><?php esc_html_e( 'Categorie Consenso', 'fp-privacy' ); ?></h2>
						<?php
						$categories = $all_options['categories'] ?? array();
						if ( ! empty( $categories ) ) :
							?>
							<ul style="list-style: none; padding-left: 0;">
								<?php foreach ( $categories as $cat_id => $cat_data ) : ?>
									<?php
									$label  = $cat_data['label'][ $lang ] ?? $cat_data['label']['en_US'] ?? $cat_id;
									$locked = ! empty( $cat_data['locked'] );
									?>
									<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
										<strong><?php echo esc_html( $label ); ?></strong>
										<?php if ( $locked ) : ?>
											<span style="color: #999;">🔒 <?php esc_html_e( '(Sempre attivo)', 'fp-privacy' ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p style="color: #dc3232;">
								⚠️ <?php esc_html_e( 'Nessuna categoria configurata. Usa il pulsante "Configura Default" qui sotto.', 'fp-privacy' ); ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="card" style="margin-top: 20px;">
						<h2 class="title"><?php esc_html_e( 'Pagine Policy', 'fp-privacy' ); ?></h2>
						<?php
						$pages = $all_options['pages'] ?? array();
						foreach ( array( 'privacy_policy_page_id', 'cookie_policy_page_id' ) as $key ) :
							$type = str_replace( '_page_id', '', $key );
							?>
							<h4><?php echo esc_html( ucfirst( str_replace( '_', ' ', $type ) ) ); ?></h4>
							<?php
							if ( ! empty( $pages[ $key ] ) && is_array( $pages[ $key ] ) ) :
								foreach ( $pages[ $key ] as $lang_code => $page_id ) :
									$page = \get_post( $page_id );
									if ( $page ) :
										?>
										<div style="padding: 5px 0;">
											<strong><?php echo esc_html( $lang_code ); ?>:</strong>
											<a href="<?php echo esc_url( \get_permalink( $page_id ) ); ?>" target="_blank">
												<?php echo esc_html( $page->post_title ); ?>
											</a>
											<code>(ID: <?php echo esc_html( $page_id ); ?>)</code>
										</div>
									<?php else : ?>
										<div style="padding: 5px 0; color: #dc3232;">
											<strong><?php echo esc_html( $lang_code ); ?>:</strong>
											<?php esc_html_e( 'Pagina non trovata', 'fp-privacy' ); ?>
											<code>(ID: <?php echo esc_html( $page_id ); ?>)</code>
										</div>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php else : ?>
								<p style="color: #999;"><em><?php esc_html_e( 'Nessuna pagina configurata', 'fp-privacy' ); ?></em></p>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Colonna 2: Azioni -->
				<div>
					<div class="card">
						<h2 class="title"><?php esc_html_e( 'Azioni Rapide', 'fp-privacy' ); ?></h2>
						
						<div style="margin-bottom: 20px;">
							<h3><?php esc_html_e( '1. Configura Impostazioni di Default', 'fp-privacy' ); ?></h3>
							<p><?php esc_html_e( 'Configura categorie, testi del banner e layout predefiniti.', 'fp-privacy' ); ?></p>
							<form method="post" action="<?php echo esc_url( \admin_url( 'admin-post.php' ) ); ?>">
								<?php \wp_nonce_field( 'fp_privacy_setup_defaults' ); ?>
								<input type="hidden" name="action" value="fp_privacy_setup_defaults">
								<button type="submit" class="button button-primary button-large">
									⚙️ <?php esc_html_e( 'Configura Default', 'fp-privacy' ); ?>
								</button>
							</form>
						</div>

						<hr>

						<div style="margin-bottom: 20px;">
							<h3><?php esc_html_e( '2. Forza Visualizzazione Banner', 'fp-privacy' ); ?></h3>
							<p><?php esc_html_e( 'Attiva la modalità preview e cancella il cookie di consenso per forzare la visualizzazione del banner.', 'fp-privacy' ); ?></p>
							<form method="post" action="<?php echo esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-right: 10px;">
								<?php \wp_nonce_field( 'fp_privacy_force_banner' ); ?>
								<input type="hidden" name="action" value="fp_privacy_force_banner">
								<button type="submit" class="button button-secondary button-large">
									👁️ <?php esc_html_e( 'Attiva Preview', 'fp-privacy' ); ?>
								</button>
							</form>
							<form method="post" action="<?php echo esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block;">
								<?php \wp_nonce_field( 'fp_privacy_disable_preview' ); ?>
								<input type="hidden" name="action" value="fp_privacy_disable_preview">
								<button type="submit" class="button button-secondary button-large">
									❌ <?php esc_html_e( 'Disattiva Preview', 'fp-privacy' ); ?>
								</button>
							</form>
						</div>

						<hr>

						<div style="margin-bottom: 20px;">
							<h3><?php esc_html_e( '3. Cancella Consenso Corrente', 'fp-privacy' ); ?></h3>
							<p><?php esc_html_e( 'Cancella il tuo consenso corrente per testare il banner (solo per il tuo account).', 'fp-privacy' ); ?></p>
							<form method="post" action="<?php echo esc_url( \admin_url( 'admin-post.php' ) ); ?>">
								<?php \wp_nonce_field( 'fp_privacy_clear_consent' ); ?>
								<input type="hidden" name="action" value="fp_privacy_clear_consent">
								<button type="submit" class="button button-secondary button-large">
									🗑️ <?php esc_html_e( 'Cancella Consenso', 'fp-privacy' ); ?>
								</button>
							</form>
						</div>
					</div>

					<div class="card" style="margin-top: 20px;">
						<h2 class="title"><?php esc_html_e( 'Informazioni di Debug', 'fp-privacy' ); ?></h2>
						<table class="widefat striped">
							<tbody>
								<tr>
									<td><strong><?php esc_html_e( 'Tema Attivo', 'fp-privacy' ); ?></strong></td>
									<td><code><?php echo esc_html( \wp_get_theme()->get( 'Name' ) ); ?></code></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Hook wp_body_open', 'fp-privacy' ); ?></strong></td>
									<td>
										<?php if ( \function_exists( 'wp_body_open' ) ) : ?>
											<span style="color: #46b450;">✓ <?php esc_html_e( 'Supportato', 'fp-privacy' ); ?></span>
										<?php else : ?>
											<span style="color: #dc3232;">✗ <?php esc_html_e( 'Non supportato', 'fp-privacy' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Cookie Consenso', 'fp-privacy' ); ?></strong></td>
									<td>
										<?php
										$cookie_name = ConsentState::COOKIE_NAME;
										if ( isset( $_COOKIE[ $cookie_name ] ) ) {
											echo '<code>' . esc_html( $_COOKIE[ $cookie_name ] ) . '</code>';
										} else {
											echo '<em>' . esc_html__( 'Nessuno', 'fp-privacy' ) . '</em>';
										}
										?>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Versione Plugin', 'fp-privacy' ); ?></strong></td>
									<td><code><?php echo esc_html( FP_PRIVACY_PLUGIN_VERSION ); ?></code></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="card" style="margin-top: 20px;">
						<h2 class="title"><?php esc_html_e( 'Link Utili', 'fp-privacy' ); ?></h2>
						<ul>
							<li><a href="<?php echo esc_url( \admin_url( 'admin.php?page=fp-privacy-settings' ) ); ?>">⚙️ <?php esc_html_e( 'Impostazioni', 'fp-privacy' ); ?></a></li>
							<li><a href="<?php echo esc_url( \home_url( '/' ) ); ?>" target="_blank">🏠 <?php esc_html_e( 'Visualizza Sito', 'fp-privacy' ); ?></a></li>
							<li><a href="<?php echo esc_url( \admin_url( 'admin.php?page=fp-privacy-consent-log' ) ); ?>">📊 <?php esc_html_e( 'Log Consensi', 'fp-privacy' ); ?></a></li>
						</ul>
					</div>
				</div>
			</div>

			<style>
				.fp-privacy-diagnostic-grid .card {
					background: #fff;
					border: 1px solid #ccd0d4;
					box-shadow: 0 1px 1px rgba(0,0,0,.04);
					padding: 20px;
				}
				.fp-privacy-diagnostic-grid .card .title {
					margin-top: 0;
					padding-bottom: 10px;
					border-bottom: 1px solid #ddd;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	private function render_notices() {
		if ( isset( $_GET['fp_privacy_success'] ) ) {
			$message = '';
			switch ( $_GET['fp_privacy_success'] ) {
				case 'setup_defaults':
					$message = \__( 'Impostazioni di default configurate con successo!', 'fp-privacy' );
					break;
				case 'force_banner':
					$message = \__( 'Modalità preview attivata e cookie cancellato!', 'fp-privacy' );
					break;
				case 'disable_preview':
					$message = \__( 'Modalità preview disattivata! Il banner ora funziona normalmente.', 'fp-privacy' );
					break;
				case 'clear_consent':
					$message = \__( 'Consenso cancellato con successo!', 'fp-privacy' );
					break;
			}
			if ( $message ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
		}
	}

	/**
	 * Handle setup defaults action.
	 *
	 * @return void
	 */
	public function handle_setup_defaults() {
		\check_admin_referer( 'fp_privacy_setup_defaults' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \__( 'Permessi insufficienti.', 'fp-privacy' ) );
		}

		$all = $this->options->all();

		// Setup categories
		if ( empty( $all['categories'] ) ) {
			$all['categories'] = array(
				'necessary'   => array(
					'label'       => array(
						'it_IT' => 'Necessari',
						'en_US' => 'Necessary',
					),
					'description' => array(
						'it_IT' => 'Cookie necessari per il funzionamento base del sito. Sempre attivi.',
						'en_US' => 'Cookies necessary for the basic functioning of the site. Always active.',
					),
					'locked'      => true,
				),
				'preferences' => array(
					'label'       => array(
						'it_IT' => 'Preferenze',
						'en_US' => 'Preferences',
					),
					'description' => array(
						'it_IT' => 'Cookie che memorizzano le tue preferenze sul sito.',
						'en_US' => 'Cookies that store your preferences on the site.',
					),
					'locked'      => false,
				),
				'statistics'  => array(
					'label'       => array(
						'it_IT' => 'Statistiche',
						'en_US' => 'Statistics',
					),
					'description' => array(
						'it_IT' => 'Cookie che aiutano a capire come i visitatori interagiscono con il sito.',
						'en_US' => 'Cookies that help understand how visitors interact with the site.',
					),
					'locked'      => false,
				),
				'marketing'   => array(
					'label'       => array(
						'it_IT' => 'Marketing',
						'en_US' => 'Marketing',
					),
					'description' => array(
						'it_IT' => 'Cookie utilizzati per tracciare i visitatori e mostrare annunci personalizzati.',
						'en_US' => 'Cookies used to track visitors and display personalized ads.',
					),
					'locked'      => false,
				),
			);
		}

		// Setup banner texts
		if ( empty( $all['banner_texts'] ) ) {
			$all['banner_texts'] = array(
				'it_IT' => array(
					'title'            => 'Rispettiamo la tua privacy',
					'message'          => 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
					'btn_accept'       => 'Accetta tutti',
					'btn_reject'       => 'Rifiuta tutti',
					'btn_prefs'        => 'Gestisci preferenze',
					'modal_title'      => 'Preferenze privacy',
					'modal_close'      => 'Chiudi preferenze',
					'modal_save'       => 'Salva preferenze',
					'revision_notice'  => 'Abbiamo aggiornato la nostra policy. Rivedi le tue preferenze.',
					'toggle_locked'    => 'Sempre attivo',
					'toggle_enabled'   => 'Abilitato',
					'link_privacy_policy' => 'Informativa sulla Privacy',
					'link_cookie_policy'  => 'Cookie Policy',
				),
				'en_US' => array(
					'title'            => 'We respect your privacy',
					'message'          => 'We use cookies to improve your experience. You can accept all cookies or manage your preferences.',
					'btn_accept'       => 'Accept all',
					'btn_reject'       => 'Reject all',
					'btn_prefs'        => 'Manage preferences',
					'modal_title'      => 'Privacy preferences',
					'modal_close'      => 'Close preferences',
					'modal_save'       => 'Save preferences',
					'revision_notice'  => 'We have updated our policy. Please review your preferences.',
					'toggle_locked'    => 'Always active',
					'toggle_enabled'   => 'Enabled',
					'link_privacy_policy' => 'Privacy Policy',
					'link_cookie_policy'  => 'Cookie Policy',
				),
			);
		}

		// Setup banner layout
		if ( empty( $all['banner_layout'] ) ) {
			$all['banner_layout'] = array(
				'type'                   => 'floating',
				'position'               => 'bottom',
				'sync_modal_and_button'  => false,
				'enable_dark_mode'       => false,
				'palette'                => array(
					'surface_bg'          => '#F9FAFB',
					'surface_text'        => '#1F2937',
					'button_primary_bg'   => '#2563EB',
					'button_primary_tx'   => '#FFFFFF',
					'button_secondary_bg' => '#FFFFFF',
					'button_secondary_tx' => '#1F2937',
					'link'                => '#1D4ED8',
					'border'              => '#D1D5DB',
					'focus'               => '#2563EB',
				),
			);
		}

		$this->options->set( $all );

		\wp_safe_redirect( \add_query_arg( 'fp_privacy_success', 'setup_defaults', \admin_url( 'admin.php?page=fp-privacy-diagnostics' ) ) );
		exit;
	}

	/**
	 * Handle force banner action.
	 *
	 * @return void
	 */
	public function handle_force_banner() {
		\check_admin_referer( 'fp_privacy_force_banner' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \__( 'Permessi insufficienti.', 'fp-privacy' ) );
		}

		$all                   = $this->options->all();
		$all['preview_mode']   = true;
		$this->options->set( $all );

		$cookie_name = ConsentState::COOKIE_NAME;
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			\setcookie( $cookie_name, '', time() - 3600, '/', '', false, true );
			unset( $_COOKIE[ $cookie_name ] );
		}

		\wp_safe_redirect( \add_query_arg( 'fp_privacy_success', 'force_banner', \admin_url( 'admin.php?page=fp-privacy-diagnostics' ) ) );
		exit;
	}

	/**
	 * Handle disable preview action.
	 *
	 * @return void
	 */
	public function handle_disable_preview() {
		\check_admin_referer( 'fp_privacy_disable_preview' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \__( 'Permessi insufficienti.', 'fp-privacy' ) );
		}

		$all                   = $this->options->all();
		$all['preview_mode']   = false;
		$this->options->set( $all );

		\wp_safe_redirect( \add_query_arg( 'fp_privacy_success', 'disable_preview', \admin_url( 'admin.php?page=fp-privacy-diagnostics' ) ) );
		exit;
	}

	/**
	 * Handle clear consent action.
	 *
	 * @return void
	 */
	public function handle_clear_consent() {
		\check_admin_referer( 'fp_privacy_clear_consent' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \__( 'Permessi insufficienti.', 'fp-privacy' ) );
		}

		$cookie_name = ConsentState::COOKIE_NAME;
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			\setcookie( $cookie_name, '', time() - 3600, '/', '', false, true );
			unset( $_COOKIE[ $cookie_name ] );
		}

		\wp_safe_redirect( \add_query_arg( 'fp_privacy_success', 'clear_consent', \admin_url( 'admin.php?page=fp-privacy-diagnostics' ) ) );
		exit;
	}
}

