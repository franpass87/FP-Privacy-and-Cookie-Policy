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
<?php if ( $snapshot_notice ) :
	$tools_link = \admin_url( 'admin.php?page=fp-privacy-tools' );
	$timestamp  = $snapshot_notice['timestamp'];
	$message    = $timestamp ? \sprintf( \__( 'Policies generated on %s may be outdated. Regenerate them from the Tools tab.', 'fp-privacy' ), \wp_date( \get_option( 'date_format' ), $timestamp ) ) : \__( 'Policies have not been generated yet. Run the policy generator from the Tools tab.', 'fp-privacy' );
?>
<div class="notice notice-warning fp-privacy-stale-notice"><p><?php echo \esc_html( $message ); ?> <a href="<?php echo \esc_url( $tools_link ); ?>"><?php \esc_html_e( 'Open Tools', 'fp-privacy' ); ?></a></p></div>
<?php endif; ?>

<!-- Tabs Navigation -->
<nav class="fp-privacy-tabs-nav">
	<button type="button" class="fp-privacy-tab-button active" data-tab="banner">
		<span class="dashicons dashicons-admin-appearance"></span>
		<span><?php \esc_html_e( 'Banner e Aspetto', 'fp-privacy' ); ?></span>
	</button>
	<button type="button" class="fp-privacy-tab-button" data-tab="cookies">
		<span class="dashicons dashicons-admin-generic"></span>
		<span><?php \esc_html_e( 'Cookie e Script', 'fp-privacy' ); ?></span>
	</button>
	<button type="button" class="fp-privacy-tab-button" data-tab="privacy">
		<span class="dashicons dashicons-shield"></span>
		<span><?php \esc_html_e( 'Privacy e Consenso', 'fp-privacy' ); ?></span>
	</button>
	<button type="button" class="fp-privacy-tab-button" data-tab="advanced">
		<span class="dashicons dashicons-admin-tools"></span>
		<span><?php \esc_html_e( 'Avanzate', 'fp-privacy' ); ?></span>
	</button>
</nav>
<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-settings-form">
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