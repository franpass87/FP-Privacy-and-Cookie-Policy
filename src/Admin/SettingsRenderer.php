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
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

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
		$this->options = $options;
		
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
			\wp_die( \esc_html__( 'You do not have permission to access this page.', 'fp-privacy' ) );
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
<div class="wrap fp-privacy-settings">
<h1><?php \esc_html_e( 'Privacy & Cookie Settings', 'fp-privacy' ); ?></h1>

<!-- Quick Actions Bar -->
<div class="fp-privacy-quick-actions">
	<div class="fp-quick-actions-primary">
		<button type="submit" form="fp-privacy-settings-form-id" class="button button-primary">
			<span class="dashicons dashicons-saved"></span>
			<?php \esc_html_e( 'Salva tutto', 'fp-privacy' ); ?>
		</button>
		<button type="button" class="button button-secondary fp-action-reset" id="fp-reset-changes">
			<span class="dashicons dashicons-undo"></span>
			<?php \esc_html_e( 'Ripristina modifiche', 'fp-privacy' ); ?>
		</button>
		<button type="button" class="button button-secondary fp-action-reset-default" id="fp-reset-default">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php \esc_html_e( 'Reset a default', 'fp-privacy' ); ?>
		</button>
	</div>
	<div class="fp-quick-actions-secondary">
		<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy-tools' ) ); ?>" class="button button-secondary">
			<span class="dashicons dashicons-download"></span>
			<?php \esc_html_e( 'Esporta configurazione', 'fp-privacy' ); ?>
		</a>
		<button type="button" class="button button-secondary fp-action-preview" id="fp-scroll-to-preview">
			<span class="dashicons dashicons-visibility"></span>
			<?php \esc_html_e( 'Preview Banner', 'fp-privacy' ); ?>
		</button>
	</div>
	<div class="fp-quick-actions-links">
		<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy-policy-editor' ) ); ?>" class="fp-quick-link">
			<span class="dashicons dashicons-edit"></span>
			<?php \esc_html_e( 'Policy Editor', 'fp-privacy' ); ?>
		</a>
		<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy-analytics' ) ); ?>" class="fp-quick-link">
			<span class="dashicons dashicons-chart-bar"></span>
			<?php \esc_html_e( 'Analytics', 'fp-privacy' ); ?>
		</a>
		<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy-consent-log' ) ); ?>" class="fp-quick-link">
			<span class="dashicons dashicons-list-view"></span>
			<?php \esc_html_e( 'Consent Log', 'fp-privacy' ); ?>
		</a>
		<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy-guide' ) ); ?>" class="fp-quick-link">
			<span class="dashicons dashicons-book"></span>
			<?php \esc_html_e( 'Guide', 'fp-privacy' ); ?>
		</a>
	</div>
</div>

<?php if ( $snapshot_notice ) :
	$tools_link = \admin_url( 'admin.php?page=fp-privacy-tools' );
	$timestamp  = $snapshot_notice['timestamp'];
	$message    = $timestamp ? \sprintf( \__( 'Policies generated on %s may be outdated. Regenerate them from the Tools tab.', 'fp-privacy' ), \wp_date( \get_option( 'date_format' ), $timestamp ) ) : \__( 'Policies have not been generated yet. Run the policy generator from the Tools tab.', 'fp-privacy' );
?>
<div class="notice notice-warning fp-privacy-stale-notice"><p><?php echo \esc_html( $message ); ?> <a href="<?php echo \esc_url( $tools_link ); ?>"><?php \esc_html_e( 'Open Tools', 'fp-privacy' ); ?></a></p></div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav class="fp-privacy-breadcrumb" aria-label="<?php \esc_attr_e( 'Breadcrumb', 'fp-privacy' ); ?>">
	<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy' ) ); ?>"><?php \esc_html_e( 'Privacy & Cookie', 'fp-privacy' ); ?></a>
	<span class="separator" aria-hidden="true">›</span>
	<span><?php \esc_html_e( 'Settings', 'fp-privacy' ); ?></span>
</nav>

<!-- Tabs Navigation -->
<nav class="fp-privacy-tabs-nav" role="tablist" aria-label="<?php \esc_attr_e( 'Settings tabs', 'fp-privacy' ); ?>">
	<button type="button" id="fp-privacy-tab-button-banner" class="fp-privacy-tab-button active" role="tab" aria-selected="true" aria-controls="fp-privacy-tab-content-banner" data-tab="banner" aria-label="<?php \esc_attr_e( 'Tab: Banner e Aspetto', 'fp-privacy' ); ?>">
		<span class="dashicons dashicons-admin-appearance"></span>
		<span><?php \esc_html_e( 'Banner e Aspetto', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-cookies" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-cookies" data-tab="cookies" aria-label="<?php \esc_attr_e( 'Tab: Cookie e Script', 'fp-privacy' ); ?>">
		<span class="dashicons dashicons-admin-generic"></span>
		<span><?php \esc_html_e( 'Cookie e Script', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-privacy" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-privacy" data-tab="privacy" aria-label="<?php \esc_attr_e( 'Tab: Privacy e Consenso', 'fp-privacy' ); ?>">
		<span class="dashicons dashicons-shield"></span>
		<span><?php \esc_html_e( 'Privacy e Consenso', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-advanced" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-advanced" data-tab="advanced" aria-label="<?php \esc_attr_e( 'Tab: Avanzate', 'fp-privacy' ); ?>">
		<span class="dashicons dashicons-admin-tools"></span>
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
	<button type="submit" form="fp-privacy-settings-form-id" class="button-primary">
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
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		?>
		<div class="wrap fp-privacy-tools">
			<h1><?php \esc_html_e( 'Tools', 'fp-privacy' ); ?></h1>
			<p><?php \esc_html_e( 'Export or import configuration, regenerate policies and reset revision.', 'fp-privacy' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-tools-export">
				<?php \wp_nonce_field( 'fp_privacy_export_settings', 'fp_privacy_export_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_export_settings" />
				<?php \submit_button( \__( 'Download settings JSON', 'fp-privacy' ), 'secondary' ); ?>
			</form>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="fp-privacy-tools-import">
				<?php \wp_nonce_field( 'fp_privacy_import_settings', 'fp_privacy_import_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_import_settings" />
				<input type="file" name="settings_file" accept="application/json" required />
				<?php \submit_button( \__( 'Import settings', 'fp-privacy' ) ); ?>
			</form>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-tools-regenerate">
				<?php \wp_nonce_field( 'fp_privacy_regenerate_policy', 'fp_privacy_regenerate_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_regenerate_policy" />
				<?php \submit_button( \__( 'Regenerate policies', 'fp-privacy' ), 'secondary' ); ?>
			</form>
			<p><a class="button" href="<?php echo \esc_url( \wp_nonce_url( \admin_url( 'admin-post.php?action=fp_privacy_bump_revision' ), 'fp_privacy_bump_revision' ) ); ?>"><?php \esc_html_e( 'Reset consent (bump revision)', 'fp-privacy' ); ?></a></p>
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
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		?>
		<div class="wrap fp-privacy-guide">
			<h1><?php \esc_html_e( 'Quick guide', 'fp-privacy' ); ?></h1>
			<h2><?php \esc_html_e( 'Shortcodes', 'fp-privacy' ); ?></h2>
			<ul>
				<li><code>[fp_privacy_policy]</code></li>
				<li><code>[fp_cookie_policy]</code></li>
				<li><code>[fp_cookie_preferences]</code></li>
				<li><code>[fp_cookie_banner]</code></li>
			</ul>
			<h2><?php \esc_html_e( 'Blocks', 'fp-privacy' ); ?></h2>
			<p><?php \esc_html_e( 'Use the Privacy Policy, Cookie Policy, Cookie Preferences and Cookie Banner blocks inside the block editor. Each block mirrors the shortcode output.', 'fp-privacy' ); ?></p>
			<h2><?php \esc_html_e( 'Hooks', 'fp-privacy' ); ?></h2>
			<ul>
				<li><code>fp_consent_update</code></li>
				<li><code>fp_privacy_settings_imported</code></li>
				<li><code>fp_privacy_policy_content</code></li>
				<li><code>fp_cookie_policy_content</code></li>
			</ul>
			<h2><?php \esc_html_e( 'Legal notice', 'fp-privacy' ); ?></h2>
			<p><?php \esc_html_e( 'This plugin provides technical tooling to help comply with privacy regulations. Consult your legal advisor to validate the generated documents.', 'fp-privacy' ); ?></p>
		</div>
		<?php
	}
}