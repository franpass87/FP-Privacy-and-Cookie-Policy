<?php
/**
 * Settings renderer.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Presentation\Admin\Views\BannerTabRenderer;
use FP\Privacy\Presentation\Admin\Views\CookiesTabRenderer;
use FP\Privacy\Presentation\Admin\Views\PrivacyTabRenderer;
use FP\Privacy\Presentation\Admin\Views\AdvancedTabRenderer;
use FP\Privacy\Utils\Options;

/**
 * Renders settings pages HTML.
 */
class SettingsRenderer {
	/**
	 * Tab renderers.
	 *
	 * @var array<string, object>
	 */
	private $tab_renderers;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		// Initialize tab renderers
		$this->tab_renderers = array(
			'banner'   => new BannerTabRenderer( $options ),
			'cookies'  => new CookiesTabRenderer( $options ),
			'privacy'  => new PrivacyTabRenderer( $options ),
			'advanced' => new AdvancedTabRenderer( $options ),
		);
	}

	/**
	 * Render settings page.
	 *
	 * @param array<string, mixed> $data Page data.
	 *
	 * @return void
	 */
	public function render_settings_page( array $data ) {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'fp-privacy' ) );
		}

		$options         = $data['options'];
		$languages       = $data['languages'];
		$primary_lang    = $data['primary_lang'];
		$detected        = $data['detected'];
		$snapshot_notice = $data['snapshot_notice'];
		$script_rules      = $data['script_rules'];
		$script_categories = $data['script_categories'];
		$notification_recipients = $data['notification_recipients'];
		?>
<div class="wrap fp-privacy-settings fp-privacy-admin-page">
<h1 class="screen-reader-text"><?php \esc_html_e( 'Impostazioni Privacy e Cookie', 'fp-privacy' ); ?></h1>
<?php
AdminHeader::render(
	'dashicons-shield',
	\__( 'Impostazioni Privacy e Cookie', 'fp-privacy' ),
	\__( 'Banner, cookie, link alle policy e opzioni avanzate.', 'fp-privacy' )
);
AdminSubnav::maybe_render( Menu::MENU_SLUG );
?>

<div class="fp-privacy-card fp-privacy-card--actions">
	<div class="fp-privacy-card-header">
		<div class="fp-privacy-card-header-left">
			<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
			<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Azioni rapide', 'fp-privacy' ); ?></h2>
		</div>
	</div>
	<div class="fp-privacy-card-body">
<div class="fp-privacy-quick-actions">
	<div class="fp-quick-actions-primary">
		<button type="submit" form="fp-privacy-settings-form-id" class="button button-primary">
			<span class="dashicons dashicons-saved"></span>
			<?php \esc_html_e( 'Salva tutto', 'fp-privacy' ); ?>
		</button>
		<button type="button" class="button button-secondary fp-action-reset" id="fp-reset-changes">
			<span class="dashicons dashicons-undo"></span>
			<?php \esc_html_e( 'Annulla modifiche non salvate', 'fp-privacy' ); ?>
		</button>
		<button type="button" class="button button-secondary fp-action-reset-default" id="fp-reset-default">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php \esc_html_e( 'Ripristina predefiniti', 'fp-privacy' ); ?>
		</button>
	</div>
	<div class="fp-quick-actions-secondary">
		<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy-tools' ) ); ?>" class="button button-secondary">
			<span class="dashicons dashicons-download"></span>
			<?php \esc_html_e( 'Esporta configurazione', 'fp-privacy' ); ?>
		</a>
		<button type="button" class="button button-secondary fp-action-preview" id="fp-scroll-to-preview">
			<span class="dashicons dashicons-visibility"></span>
			<?php \esc_html_e( 'Anteprima banner', 'fp-privacy' ); ?>
		</button>
	</div>
</div>
	</div>
</div>

<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Impostazioni salvate.', 'fp-privacy' ); ?></p></div>
<?php endif; ?>
<?php if ( isset( $_GET['fp_privacy_reset'] ) && '1' === $_GET['fp_privacy_reset'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Le impostazioni sono state ripristinate ai valori predefiniti.', 'fp-privacy' ); ?></p></div>
<?php endif; ?>

<?php if ( $snapshot_notice ) :
	$tools_link = \admin_url( 'admin.php?page=fp-privacy-tools' );
	$timestamp  = $snapshot_notice['timestamp'];
	$message    = $timestamp ? \sprintf( \__( 'Le policy generate il %s potrebbero essere obsolete. Rigenerale dalla scheda Strumenti.', 'fp-privacy' ), \wp_date( \get_option( 'date_format' ), $timestamp ) ) : \__( 'Le policy non sono ancora state generate. Esegui il generatore dalla scheda Strumenti.', 'fp-privacy' );
?>
<div class="notice notice-warning fp-privacy-stale-notice"><p><?php echo \esc_html( $message ); ?> <a href="<?php echo \esc_url( $tools_link ); ?>"><?php \esc_html_e( 'Apri Strumenti', 'fp-privacy' ); ?></a></p></div>
<?php endif; ?>

<div class="fp-privacy-settings-form-heading">
	<nav class="fp-privacy-breadcrumb" aria-label="<?php \esc_attr_e( 'Percorso modulo impostazioni', 'fp-privacy' ); ?>">
		<span><?php \esc_html_e( 'FP Privacy & Cookie', 'fp-privacy' ); ?></span>
		<span class="separator" aria-hidden="true">/</span>
		<span><?php \esc_html_e( 'Impostazioni', 'fp-privacy' ); ?></span>
		<span class="separator" aria-hidden="true">/</span>
		<span class="fp-privacy-breadcrumb-current"><?php \esc_html_e( 'Sezioni di configurazione', 'fp-privacy' ); ?></span>
	</nav>
	<p class="fp-privacy-settings-form-intro description"><?php \esc_html_e( 'Usa le schede sotto per modificare ogni area. Salva tutto con «Salva tutto» o con la barra in basso. Registro consensi, Analytics, editor policy, Strumenti e Guida rapida sono nel sottomenu FP Privacy.', 'fp-privacy' ); ?></p>
</div>

<nav class="fp-privacy-tabs-nav" role="tablist" aria-label="<?php \esc_attr_e( 'Schede configurazione impostazioni', 'fp-privacy' ); ?>">
	<button type="button" id="fp-privacy-tab-button-banner" class="fp-privacy-tab-button active" role="tab" aria-selected="true" aria-controls="fp-privacy-tab-content-banner" data-tab="banner" aria-label="<?php echo \esc_attr( \sprintf( \__( 'Scheda: %s', 'fp-privacy' ), \__( 'Banner e aspetto', 'fp-privacy' ) ) ); ?>">
		<span class="dashicons dashicons-admin-appearance" aria-hidden="true"></span>
		<span><?php \esc_html_e( 'Banner e aspetto', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-cookies" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-cookies" data-tab="cookies" aria-label="<?php echo \esc_attr( \sprintf( \__( 'Scheda: %s', 'fp-privacy' ), \__( 'Cookie e script', 'fp-privacy' ) ) ); ?>">
		<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
		<span><?php \esc_html_e( 'Cookie e script', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-privacy" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-privacy" data-tab="privacy" aria-label="<?php echo \esc_attr( \sprintf( \__( 'Scheda: %s', 'fp-privacy' ), \__( 'Privacy e consenso', 'fp-privacy' ) ) ); ?>">
		<span class="dashicons dashicons-shield" aria-hidden="true"></span>
		<span><?php \esc_html_e( 'Privacy e consenso', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-advanced" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-advanced" data-tab="advanced" aria-label="<?php echo \esc_attr( \sprintf( \__( 'Scheda: %s', 'fp-privacy' ), \__( 'Avanzate', 'fp-privacy' ) ) ); ?>">
		<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
		<span><?php \esc_html_e( 'Avanzate', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
</nav>
<form id="fp-privacy-settings-form-id" method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-settings-form">
<?php \wp_nonce_field( 'fp_privacy_save_settings', 'fp_privacy_nonce' ); ?>
		<input type="hidden" name="action" value="fp_privacy_save_settings" />

		<?php
		// Render banner tab
		$this->tab_renderers['banner']->render( $data );
		
		// Render privacy tab
		$this->tab_renderers['privacy']->render( $data );
		
		// Render advanced tab
		$this->tab_renderers['advanced']->render( $data );
		
		// Render cookies tab
		$this->tab_renderers['cookies']->render( $data );
		?>
</form>

<!-- Sticky Save Button -->
<div class="fp-privacy-sticky-save">
	<button type="submit" form="fp-privacy-settings-form-id" class="button button-primary">
		<span class="dashicons dashicons-saved" aria-hidden="true"></span>
		<?php \esc_html_e( 'Salva tutte le impostazioni', 'fp-privacy' ); ?>
	</button>
</div>


</div>
		<?php
	}

	/**
	 * Render tools page.
	 *
	 * @return void
	 */
	public function render_tools_page() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Accesso negato.', 'fp-privacy' ) );
		}

		?>
		<div class="wrap fp-privacy-tools fp-privacy-admin-page">
			<h1 class="screen-reader-text"><?php \esc_html_e( 'Strumenti', 'fp-privacy' ); ?></h1>
			<?php
			AdminHeader::render(
				'dashicons-admin-tools',
				\__( 'Strumenti', 'fp-privacy' ),
				\__( 'Esporta o importa la configurazione, rigenera le policy e reimposta la revisione consenso.', 'fp-privacy' )
			);
			AdminSubnav::maybe_render( 'fp-privacy-tools' );
			?>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-download" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Esporta', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-tools-export">
				<?php \wp_nonce_field( 'fp_privacy_export_settings', 'fp_privacy_export_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_export_settings" />
				<p class="description"><?php \esc_html_e( 'Scarica l’intera configurazione del plugin in formato JSON.', 'fp-privacy' ); ?></p>
				<?php
				AdminUi::render_submit_button(
					\__( 'Scarica JSON impostazioni', 'fp-privacy' ),
					'secondary',
					array( 'name' => 'submit', 'id' => 'submit', 'dashicon' => 'dashicons-download' )
				);
				?>
			</form>
				</div>
			</div>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-upload" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Importa', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="fp-privacy-tools-import">
				<?php \wp_nonce_field( 'fp_privacy_import_settings', 'fp_privacy_import_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_import_settings" />
				<p class="description"><?php \esc_html_e( 'Ripristina le impostazioni da un file JSON esportato in precedenza.', 'fp-privacy' ); ?></p>
				<p><input type="file" name="settings_file" accept="application/json" required /></p>
				<?php
				AdminUi::render_submit_button(
					\__( 'Importa impostazioni', 'fp-privacy' ),
					'primary',
					array( 'name' => 'submit', 'id' => 'submit', 'dashicon' => 'dashicons-upload' )
				);
				?>
			</form>
				</div>
			</div>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-update" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Policy e revisione consenso', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-tools-regenerate">
				<?php \wp_nonce_field( 'fp_privacy_regenerate_policy', 'fp_privacy_regenerate_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_regenerate_policy" />
				<p class="description"><?php \esc_html_e( 'Rigenera le pagine privacy e cookie policy dalla configurazione attuale del detector.', 'fp-privacy' ); ?></p>
				<?php
				AdminUi::render_submit_button(
					\__( 'Rigenera policy', 'fp-privacy' ),
					'secondary',
					array( 'name' => 'submit', 'id' => 'submit', 'dashicon' => 'dashicons-update' )
				);
				?>
			</form>
			<div class="fp-privacy-card-stack-gap">
			<p class="description"><?php \esc_html_e( 'Incrementa la revisione consenso per far rivedere il banner ai visitatori.', 'fp-privacy' ); ?></p>
			<p><a class="button button-secondary" href="<?php echo \esc_url( \wp_nonce_url( \admin_url( 'admin-post.php?action=fp_privacy_bump_revision' ), 'fp_privacy_bump_revision' ) ); ?>"><?php \esc_html_e( 'Reimposta consenso (bump revision)', 'fp-privacy' ); ?></a></p>
			</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render quick guide page.
	 *
	 * @return void
	 */
	public function render_guide_page() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Accesso negato.', 'fp-privacy' ) );
		}

		?>
		<div class="wrap fp-privacy-guide fp-privacy-admin-page">
			<h1 class="screen-reader-text"><?php \esc_html_e( 'Guida rapida', 'fp-privacy' ); ?></h1>
			<?php
			AdminHeader::render(
				'dashicons-book-alt',
				\__( 'Guida rapida', 'fp-privacy' ),
				\__( 'Shortcode, blocchi Gutenberg e hook per sviluppatori.', 'fp-privacy' )
			);
			AdminSubnav::maybe_render( 'fp-privacy-guide' );
			?>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Shortcode', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<ul class="fp-privacy-guide-list">
						<li><code>[fp_privacy_policy]</code></li>
						<li><code>[fp_cookie_policy]</code></li>
						<li><code>[fp_cookie_preferences]</code></li>
						<li><code>[fp_cookie_banner]</code></li>
					</ul>
				</div>
			</div>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-editor-kitchensink" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Blocchi', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<p><?php \esc_html_e( 'Usa i blocchi Privacy Policy, Cookie Policy, Preferenze cookie e Banner cookie nell’editor a blocchi. Ogni blocco replica l’output dello shortcode corrispondente.', 'fp-privacy' ); ?></p>
				</div>
			</div>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Hook', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<ul class="fp-privacy-guide-list">
						<li><code>fp_consent_update</code></li>
						<li><code>fp_privacy_settings_imported</code></li>
						<li><code>fp_privacy_policy_content</code></li>
						<li><code>fp_cookie_policy_content</code></li>
					</ul>
				</div>
			</div>

			<div class="fp-privacy-card fp-privacy-card--notice">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-warning" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Avviso legale', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<p><?php \esc_html_e( 'Il plugin offre strumenti tecnici per supportare la conformità privacy. Fatti validare i documenti generati dal tuo consulente legale.', 'fp-privacy' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}