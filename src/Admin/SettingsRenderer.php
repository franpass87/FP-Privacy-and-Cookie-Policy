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
<div class="wrap fp-privacy-settings fp-privacy-admin-page">
<h1 class="screen-reader-text"><?php \esc_html_e( 'Privacy & Cookie Settings', 'fp-privacy' ); ?></h1>
<?php
AdminHeader::render(
	'dashicons-shield',
	\__( 'Privacy & Cookie Settings', 'fp-privacy' ),
	\__( 'Banner, cookie, policy links and advanced options.', 'fp-privacy' )
);
AdminSubnav::maybe_render( Menu::MENU_SLUG );
?>

<div class="fp-privacy-card fp-privacy-card--actions">
	<div class="fp-privacy-card-header">
		<div class="fp-privacy-card-header-left">
			<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
			<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Quick actions', 'fp-privacy' ); ?></h2>
		</div>
	</div>
	<div class="fp-privacy-card-body">
<div class="fp-privacy-quick-actions">
	<div class="fp-quick-actions-primary">
		<button type="submit" form="fp-privacy-settings-form-id" class="button button-primary">
			<span class="dashicons dashicons-saved"></span>
			<?php \esc_html_e( 'Save all', 'fp-privacy' ); ?>
		</button>
		<button type="button" class="button button-secondary fp-action-reset" id="fp-reset-changes">
			<span class="dashicons dashicons-undo"></span>
			<?php \esc_html_e( 'Discard unsaved changes', 'fp-privacy' ); ?>
		</button>
		<button type="button" class="button button-secondary fp-action-reset-default" id="fp-reset-default">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php \esc_html_e( 'Reset to defaults', 'fp-privacy' ); ?>
		</button>
	</div>
	<div class="fp-quick-actions-secondary">
		<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy-tools' ) ); ?>" class="button button-secondary">
			<span class="dashicons dashicons-download"></span>
			<?php \esc_html_e( 'Export configuration', 'fp-privacy' ); ?>
		</a>
		<button type="button" class="button button-secondary fp-action-preview" id="fp-scroll-to-preview">
			<span class="dashicons dashicons-visibility"></span>
			<?php \esc_html_e( 'Preview banner', 'fp-privacy' ); ?>
		</button>
	</div>
</div>
	</div>
</div>

<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Settings saved.', 'fp-privacy' ); ?></p></div>
<?php endif; ?>
<?php if ( isset( $_GET['fp_privacy_reset'] ) && '1' === $_GET['fp_privacy_reset'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Settings were reset to their default values.', 'fp-privacy' ); ?></p></div>
<?php endif; ?>

<?php if ( $snapshot_notice ) :
	$tools_link = \admin_url( 'admin.php?page=fp-privacy-tools' );
	$timestamp  = $snapshot_notice['timestamp'];
	$message    = $timestamp ? \sprintf( \__( 'Policies generated on %s may be outdated. Regenerate them from the Tools tab.', 'fp-privacy' ), \wp_date( \get_option( 'date_format' ), $timestamp ) ) : \__( 'Policies have not been generated yet. Run the policy generator from the Tools tab.', 'fp-privacy' );
?>
<div class="notice notice-warning fp-privacy-stale-notice"><p><?php echo \esc_html( $message ); ?> <a href="<?php echo \esc_url( $tools_link ); ?>"><?php \esc_html_e( 'Open Tools', 'fp-privacy' ); ?></a></p></div>
<?php endif; ?>

<div class="fp-privacy-settings-form-heading">
	<nav class="fp-privacy-breadcrumb" aria-label="<?php \esc_attr_e( 'Settings form location', 'fp-privacy' ); ?>">
		<span><?php \esc_html_e( 'FP Privacy & Cookie', 'fp-privacy' ); ?></span>
		<span class="separator" aria-hidden="true">/</span>
		<span><?php \esc_html_e( 'Settings', 'fp-privacy' ); ?></span>
		<span class="separator" aria-hidden="true">/</span>
		<span class="fp-privacy-breadcrumb-current"><?php \esc_html_e( 'Configuration sections', 'fp-privacy' ); ?></span>
	</nav>
	<p class="fp-privacy-settings-form-intro description"><?php \esc_html_e( 'Use the tabs below to edit each area. Save everything at once with Save all or the bar at the bottom. Other sections (log, analytics, policy editor, tools, guide) are in the FP Privacy submenu.', 'fp-privacy' ); ?></p>
</div>

<nav class="fp-privacy-tabs-nav" role="tablist" aria-label="<?php \esc_attr_e( 'Settings configuration tabs', 'fp-privacy' ); ?>">
	<button type="button" id="fp-privacy-tab-button-banner" class="fp-privacy-tab-button active" role="tab" aria-selected="true" aria-controls="fp-privacy-tab-content-banner" data-tab="banner" aria-label="<?php echo \esc_attr( \sprintf( \__( 'Tab: %s', 'fp-privacy' ), \__( 'Banner & appearance', 'fp-privacy' ) ) ); ?>">
		<span class="dashicons dashicons-admin-appearance" aria-hidden="true"></span>
		<span><?php \esc_html_e( 'Banner & appearance', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-cookies" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-cookies" data-tab="cookies" aria-label="<?php echo \esc_attr( \sprintf( \__( 'Tab: %s', 'fp-privacy' ), \__( 'Cookies & scripts', 'fp-privacy' ) ) ); ?>">
		<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
		<span><?php \esc_html_e( 'Cookies & scripts', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-privacy" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-privacy" data-tab="privacy" aria-label="<?php echo \esc_attr( \sprintf( \__( 'Tab: %s', 'fp-privacy' ), \__( 'Privacy & consent', 'fp-privacy' ) ) ); ?>">
		<span class="dashicons dashicons-shield" aria-hidden="true"></span>
		<span><?php \esc_html_e( 'Privacy & consent', 'fp-privacy' ); ?></span>
		<span class="fp-tab-badge" aria-hidden="true"></span>
	</button>
	<button type="button" id="fp-privacy-tab-button-advanced" class="fp-privacy-tab-button" role="tab" aria-selected="false" aria-controls="fp-privacy-tab-content-advanced" data-tab="advanced" aria-label="<?php echo \esc_attr( \sprintf( \__( 'Tab: %s', 'fp-privacy' ), \__( 'Advanced', 'fp-privacy' ) ) ); ?>">
		<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
		<span><?php \esc_html_e( 'Advanced', 'fp-privacy' ); ?></span>
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
		<?php \esc_html_e( 'Save all settings', 'fp-privacy' ); ?>
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
		<div class="wrap fp-privacy-tools fp-privacy-admin-page">
			<h1 class="screen-reader-text"><?php \esc_html_e( 'Tools', 'fp-privacy' ); ?></h1>
			<?php
			AdminHeader::render(
				'dashicons-admin-tools',
				\__( 'Tools', 'fp-privacy' ),
				\__( 'Export or import configuration, regenerate policies and reset revision.', 'fp-privacy' )
			);
			AdminSubnav::maybe_render( 'fp-privacy-tools' );
			?>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-download" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Export', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-tools-export">
				<?php \wp_nonce_field( 'fp_privacy_export_settings', 'fp_privacy_export_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_export_settings" />
				<p class="description"><?php \esc_html_e( 'Download the full plugin configuration as JSON.', 'fp-privacy' ); ?></p>
				<?php
				AdminUi::render_submit_button(
					\__( 'Download settings JSON', 'fp-privacy' ),
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
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Import', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="fp-privacy-tools-import">
				<?php \wp_nonce_field( 'fp_privacy_import_settings', 'fp_privacy_import_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_import_settings" />
				<p class="description"><?php \esc_html_e( 'Restore settings from a previously exported JSON file.', 'fp-privacy' ); ?></p>
				<p><input type="file" name="settings_file" accept="application/json" required /></p>
				<?php
				AdminUi::render_submit_button(
					\__( 'Import settings', 'fp-privacy' ),
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
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Policies & consent revision', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-tools-regenerate">
				<?php \wp_nonce_field( 'fp_privacy_regenerate_policy', 'fp_privacy_regenerate_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_regenerate_policy" />
				<p class="description"><?php \esc_html_e( 'Regenerate privacy and cookie policy pages from the current detector configuration.', 'fp-privacy' ); ?></p>
				<?php
				AdminUi::render_submit_button(
					\__( 'Regenerate policies', 'fp-privacy' ),
					'secondary',
					array( 'name' => 'submit', 'id' => 'submit', 'dashicon' => 'dashicons-update' )
				);
				?>
			</form>
			<div class="fp-privacy-card-stack-gap">
			<p class="description"><?php \esc_html_e( 'Bump the consent revision to force visitors to see the banner again.', 'fp-privacy' ); ?></p>
			<p><a class="button button-secondary" href="<?php echo \esc_url( \wp_nonce_url( \admin_url( 'admin-post.php?action=fp_privacy_bump_revision' ), 'fp_privacy_bump_revision' ) ); ?>"><?php \esc_html_e( 'Reset consent (bump revision)', 'fp-privacy' ); ?></a></p>
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
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		?>
		<div class="wrap fp-privacy-guide fp-privacy-admin-page">
			<h1 class="screen-reader-text"><?php \esc_html_e( 'Quick guide', 'fp-privacy' ); ?></h1>
			<?php
			AdminHeader::render(
				'dashicons-book-alt',
				\__( 'Quick guide', 'fp-privacy' ),
				\__( 'Shortcodes, blocks and developer hooks.', 'fp-privacy' )
			);
			AdminSubnav::maybe_render( 'fp-privacy-guide' );
			?>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Shortcodes', 'fp-privacy' ); ?></h2>
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
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Blocks', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<p><?php \esc_html_e( 'Use the Privacy Policy, Cookie Policy, Cookie Preferences and Cookie Banner blocks inside the block editor. Each block mirrors the shortcode output.', 'fp-privacy' ); ?></p>
				</div>
			</div>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Hooks', 'fp-privacy' ); ?></h2>
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
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Legal notice', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<p><?php \esc_html_e( 'This plugin provides technical tooling to help comply with privacy regulations. Consult your legal advisor to validate the generated documents.', 'fp-privacy' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}