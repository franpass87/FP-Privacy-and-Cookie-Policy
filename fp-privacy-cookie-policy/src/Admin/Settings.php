<?php
/**
 * Settings page.
 *
 * @package FP\Privacy\Admin
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;

/**
 * Renders and saves settings.
 */
class Settings {
/**
 * Options handler.
 *
 * @var Options
 */
private $options;

/**
 * Detector registry.
 *
 * @var DetectorRegistry
 */
private $detector;

/**
 * Policy generator.
 *
 * @var PolicyGenerator
 */
private $generator;

/**
 * Constructor.
 *
 * @param Options          $options   Options handler.
 * @param DetectorRegistry $detector  Detector.
 * @param PolicyGenerator  $generator Generator.
 */
public function __construct( Options $options, DetectorRegistry $detector, PolicyGenerator $generator ) {
$this->options   = $options;
$this->detector  = $detector;
$this->generator = $generator;
}

/**
 * Hooks.
 *
 * @return void
 */
public function hooks() {
	\add_action( 'fp_privacy_admin_page_settings', array( $this, 'render_page' ) );
	\add_action( 'fp_privacy_admin_page_tools', array( $this, 'render_tools_page' ) );
	\add_action( 'fp_privacy_admin_page_guide', array( $this, 'render_guide_page' ) );
	\add_action( 'admin_post_fp_privacy_save_settings', array( $this, 'handle_save' ) );
	\add_action( 'admin_post_fp_privacy_bump_revision', array( $this, 'handle_bump_revision' ) );
	\add_action( 'admin_post_fp_privacy_export_settings', array( $this, 'handle_export_settings' ) );
	\add_action( 'admin_post_fp_privacy_import_settings', array( $this, 'handle_import_settings' ) );
	\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
}

/**
 * Enqueue admin assets.
 *
 * @param string $hook Hook.
 *
 * @return void
 */
public function enqueue_assets( $hook ) {
if ( false === strpos( $hook, Menu::MENU_SLUG ) ) {
return;
}

\wp_enqueue_style( 'fp-privacy-admin', FP_PRIVACY_PLUGIN_URL . 'assets/css/admin.css', array(), FP_PRIVACY_PLUGIN_VERSION );
\wp_enqueue_style( 'fp-privacy-banner-preview', FP_PRIVACY_PLUGIN_URL . 'assets/css/banner.css', array(), FP_PRIVACY_PLUGIN_VERSION );
\wp_enqueue_script( 'fp-privacy-admin', FP_PRIVACY_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), FP_PRIVACY_PLUGIN_VERSION, true );
\wp_localize_script(
    'fp-privacy-admin',
    'fpPrivacyL10n',
    array(
        'lowContrast'      => \__( 'The contrast ratio between background and text is below 4.5:1. Please adjust your palette.', 'fp-privacy' ),
        'previewLanguage'  => \__( 'Preview language', 'fp-privacy' ),
        'previewEmpty'     => \__( 'Update the banner texts above to preview the banner.', 'fp-privacy' ),
    )
);
}

/**
 * Render settings page.
 *
 * @return void
 */
public function render_page() {
if ( ! \current_user_can( 'manage_options' ) ) {
\wp_die( \esc_html__( 'You do not have permission to access this page.', 'fp-privacy' ) );
}

$options         = $this->options->all();
$languages       = $options['languages_active'];
$detected        = $this->detector->detect_services();
$primary_lang    = $languages[0] ?? 'en_US';
$snapshot_notice = $this->get_snapshot_notice( $options['snapshots'] );
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
<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-settings-form">
<?php \wp_nonce_field( 'fp_privacy_save_settings', 'fp_privacy_nonce' ); ?>
<input type="hidden" name="action" value="fp_privacy_save_settings" />

<h2><?php \esc_html_e( 'Languages', 'fp-privacy' ); ?></h2>
<p class="description"><?php \esc_html_e( 'Provide active languages (comma separated locale codes).', 'fp-privacy' ); ?></p>
<input type="text" name="languages_active" class="regular-text" value="<?php echo \esc_attr( implode( ',', $languages ) ); ?>" />

<h2><?php \esc_html_e( 'Banner content', 'fp-privacy' ); ?></h2>
<?php foreach ( $languages as $lang ) :
$text = isset( $options['banner_texts'][ $lang ] ) ? $options['banner_texts'][ $lang ] : $this->options->get_default_options()['banner_texts'][ \get_locale() ];
?>
<div class="fp-privacy-language-panel" data-lang="<?php echo \esc_attr( $lang ); ?>">
<h3><?php echo \esc_html( \sprintf( \__( 'Language: %s', 'fp-privacy' ), $lang ) ); ?></h3>
<label>
<span><?php \esc_html_e( 'Title', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][title]" value="<?php echo \esc_attr( $text['title'] ); ?>" class="regular-text" data-field="title" />
</label>
<label>
<span><?php \esc_html_e( 'Message', 'fp-privacy' ); ?></span>
<textarea name="banner_texts[<?php echo \esc_attr( $lang ); ?>][message]" rows="4" class="large-text" data-field="message"><?php echo \esc_textarea( $text['message'] ); ?></textarea>
</label>
<label>
<span><?php \esc_html_e( 'Accept button label', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][btn_accept]" value="<?php echo \esc_attr( $text['btn_accept'] ); ?>" data-field="btn_accept" />
</label>
<label>
<span><?php \esc_html_e( 'Reject button label', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][btn_reject]" value="<?php echo \esc_attr( $text['btn_reject'] ); ?>" data-field="btn_reject" />
</label>
<label>
<span><?php \esc_html_e( 'Preferences button label', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][btn_prefs]" value="<?php echo \esc_attr( $text['btn_prefs'] ); ?>" data-field="btn_prefs" />
</label>
<label>
<span><?php \esc_html_e( 'Revision notice message', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][revision_notice]" value="<?php echo \esc_attr( $text['revision_notice'] ?? '' ); ?>" data-field="revision_notice" />
</label>
<label>
<span><?php \esc_html_e( 'Modal title', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][modal_title]" value="<?php echo \esc_attr( $text['modal_title'] ); ?>" data-field="modal_title" />
</label>
<label>
<span><?php \esc_html_e( 'Modal close label', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][modal_close]" value="<?php echo \esc_attr( $text['modal_close'] ); ?>" data-field="modal_close" />
</label>
<label>
<span><?php \esc_html_e( 'Modal save button', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][modal_save]" value="<?php echo \esc_attr( $text['modal_save'] ); ?>" data-field="modal_save" />
</label>
<label>
<span><?php \esc_html_e( 'Locked toggle label', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][toggle_locked]" value="<?php echo \esc_attr( $text['toggle_locked'] ); ?>" data-field="toggle_locked" />
</label>
<label>
<span><?php \esc_html_e( 'Enabled toggle label', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][toggle_enabled]" value="<?php echo \esc_attr( $text['toggle_enabled'] ); ?>" data-field="toggle_enabled" />
</label>
<label>
<span><?php \esc_html_e( 'Policy link URL', 'fp-privacy' ); ?></span>
<input type="url" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][link_policy]" value="<?php echo \esc_attr( $text['link_policy'] ); ?>" class="regular-text" data-field="link_policy" />
</label>
<label>
<span><?php \esc_html_e( 'Debug panel label', 'fp-privacy' ); ?></span>
<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][debug_label]" value="<?php echo \esc_attr( $text['debug_label'] ); ?>" data-field="debug_label" />
</label>
</div>
<?php endforeach; ?>

<div class="fp-privacy-preview">
<h2><?php \esc_html_e( 'Banner preview', 'fp-privacy' ); ?></h2>
<p class="description"><?php \esc_html_e( 'Adjust copy, colors, and layout to see a live preview of the cookie banner.', 'fp-privacy' ); ?></p>
<div class="fp-privacy-preview-controls">
<label for="fp-privacy-preview-language"><span><?php \esc_html_e( 'Preview language', 'fp-privacy' ); ?></span></label>
<select id="fp-privacy-preview-language">
<?php foreach ( $languages as $lang ) : ?>
<option value="<?php echo \esc_attr( $lang ); ?>" <?php selected( $lang, $primary_lang ); ?>><?php echo \esc_html( $lang ); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="fp-privacy-preview-frame">
<div id="fp-privacy-preview-banner" data-preview-lang="<?php echo \esc_attr( $primary_lang ); ?>"></div>
</div>
</div>

<h2><?php \esc_html_e( 'Layout', 'fp-privacy' ); ?></h2>
<div class="fp-privacy-layout">
<label>
<span><?php \esc_html_e( 'Display type', 'fp-privacy' ); ?></span>
<select name="banner_layout[type]">
<option value="floating" <?php\selected( $options['banner_layout']['type'], 'floating' ); ?>><?php \esc_html_e( 'Floating', 'fp-privacy' ); ?></option>
<option value="bar" <?php\selected( $options['banner_layout']['type'], 'bar' ); ?>><?php \esc_html_e( 'Bar', 'fp-privacy' ); ?></option>
</select>
</label>
<label>
<span><?php \esc_html_e( 'Position', 'fp-privacy' ); ?></span>
<select name="banner_layout[position]">
<option value="top" <?php\selected( $options['banner_layout']['position'], 'top' ); ?>><?php \esc_html_e( 'Top', 'fp-privacy' ); ?></option>
<option value="bottom" <?php\selected( $options['banner_layout']['position'], 'bottom' ); ?>><?php \esc_html_e( 'Bottom', 'fp-privacy' ); ?></option>
</select>
</label>
<label>
<input type="checkbox" name="banner_layout[sync_modal_and_button]" value="1" <?php\checked( $options['banner_layout']['sync_modal_and_button'], true ); ?> />
<?php \esc_html_e( 'Synchronize modal and button palette', 'fp-privacy' ); ?>
</label>
</div>

<h2><?php \esc_html_e( 'Palette', 'fp-privacy' ); ?></h2>
<div class="fp-privacy-palette">
<?php foreach ( $options['banner_layout']['palette'] as $key => $color ) : ?>
<label>
<span><?php echo \esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></span>
<input type="color" name="banner_layout[palette][<?php echo \esc_attr( $key ); ?>]" value="<?php echo \esc_attr( $color ); ?>" />
</label>
<?php endforeach; ?>
</div>

<h2><?php \esc_html_e( 'Consent Mode defaults', 'fp-privacy' ); ?></h2>
<div class="fp-privacy-consent-mode">
<?php foreach ( $options['consent_mode_defaults'] as $key => $value ) : ?>
<label>
<span><?php echo \esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></span>
<select name="consent_mode_defaults[<?php echo \esc_attr( $key ); ?>]">
<option value="granted" <?php\selected( $value, 'granted' ); ?>><?php \esc_html_e( 'Granted', 'fp-privacy' ); ?></option>
<option value="denied" <?php\selected( $value, 'denied' ); ?>><?php \esc_html_e( 'Denied', 'fp-privacy' ); ?></option>
</select>
</label>
<?php endforeach; ?>
</div>

<h2><?php \esc_html_e( 'Retention & Revision', 'fp-privacy' ); ?></h2>
<label>
<span><?php \esc_html_e( 'Retention days', 'fp-privacy' ); ?></span>
<input type="number" min="1" name="retention_days" value="<?php echo \esc_attr( $options['retention_days'] ); ?>" />
</label>
<label>
<input type="checkbox" name="preview_mode" value="1" <?php\checked( $options['preview_mode'], true ); ?> />
<?php \esc_html_e( 'Enable preview mode (admins only)', 'fp-privacy' ); ?>
</label>
<p><?php echo \esc_html( \sprintf( \__( 'Current consent revision: %d', 'fp-privacy' ), $options['consent_revision'] ) ); ?></p>
<p><a class="button button-secondary" href="<?php echo \esc_url( \wp_nonce_url( \admin_url( 'admin-post.php?action=fp_privacy_bump_revision' ), 'fp_privacy_bump_revision' ) ); ?>"><?php \esc_html_e( 'Force new consent (bump revision)', 'fp-privacy' ); ?></a></p>

<h2><?php \esc_html_e( 'Controller & DPO', 'fp-privacy' ); ?></h2>
<div class="fp-privacy-org">
<label><span><?php \esc_html_e( 'Organization name', 'fp-privacy' ); ?></span><input type="text" name="org_name" value="<?php echo \esc_attr( $options['org_name'] ); ?>" class="regular-text" /></label>
<label><span><?php \esc_html_e( 'VAT / Tax ID', 'fp-privacy' ); ?></span><input type="text" name="vat" value="<?php echo \esc_attr( $options['vat'] ); ?>" class="regular-text" /></label>
<label><span><?php \esc_html_e( 'Registered address', 'fp-privacy' ); ?></span><input type="text" name="address" value="<?php echo \esc_attr( $options['address'] ); ?>" class="regular-text" /></label>
<label><span><?php \esc_html_e( 'DPO name', 'fp-privacy' ); ?></span><input type="text" name="dpo_name" value="<?php echo \esc_attr( $options['dpo_name'] ); ?>" class="regular-text" /></label>
<label><span><?php \esc_html_e( 'DPO email', 'fp-privacy' ); ?></span><input type="email" name="dpo_email" value="<?php echo \esc_attr( $options['dpo_email'] ); ?>" class="regular-text" /></label>
<label><span><?php \esc_html_e( 'Privacy contact email', 'fp-privacy' ); ?></span><input type="email" name="privacy_email" value="<?php echo \esc_attr( $options['privacy_email'] ); ?>" class="regular-text" /></label>
</div>

<?php \submit_button( \__( 'Save settings', 'fp-privacy' ) ); ?>
</form>

<h2><?php \esc_html_e( 'Detected services', 'fp-privacy' ); ?></h2>
<table class="widefat fp-privacy-detected">
<thead>
<tr>
<th><?php \esc_html_e( 'Service', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'Category', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'Detected', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'Provider', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'Cookies', 'fp-privacy' ); ?></th>
</tr>
</thead>
<tbody>
<?php foreach ( $detected as $service ) : ?>
<tr>
<td><?php echo \esc_html( $service['name'] ); ?></td>
<td><?php echo \esc_html( $service['category'] ); ?></td>
<td><?php echo $service['detected'] ? '<span class="status-detected">' . \esc_html__( 'Yes', 'fp-privacy' ) . '</span>' : \esc_html__( 'No', 'fp-privacy' ); ?></td>
<td><?php echo \esc_html( $service['provider'] ); ?></td>
<td><?php echo \esc_html( implode( ', ', $service['cookies'] ) ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p class="description"><?php \esc_html_e( 'Use the policy editor to regenerate your documents after services change.', 'fp-privacy' ); ?></p>
</div>
<?php
}

/**
 * Determine whether stored snapshots are stale.
 *
 * @param array<string, mixed> $snapshots Snapshots payload.
 *
 * @return array{timestamp:int}|null
 */
private function get_snapshot_notice( $snapshots ) {
if ( ! \is_array( $snapshots ) ) {
return array( 'timestamp' => 0 );
}

$now        = time();
$threshold  = DAY_IN_SECONDS * 14;
$stale      = false;
$oldest     = PHP_INT_MAX;
$has_policy = false;

$services_generated = isset( $snapshots['services']['generated_at'] ) ? (int) $snapshots['services']['generated_at'] : 0;
if ( $services_generated <= 0 || ( $now - $services_generated ) > $threshold ) {
$stale = true;
}

if ( $services_generated > 0 ) {
$oldest = min( $oldest, $services_generated );
}

if ( isset( $snapshots['policies'] ) && \is_array( $snapshots['policies'] ) ) {
foreach ( $snapshots['policies'] as $entries ) {
if ( ! \is_array( $entries ) ) {
continue;
}

foreach ( $entries as $data ) {
$generated = isset( $data['generated_at'] ) ? (int) $data['generated_at'] : 0;
if ( $generated > 0 ) {
$has_policy = true;
$oldest     = min( $oldest, $generated );
if ( ( $now - $generated ) > $threshold ) {
$stale = true;
}
} else {
$stale = true;
}
}
}
} else {
$stale = true;
}

if ( ! $stale ) {
return null;
}

if ( PHP_INT_MAX === $oldest ) {
$oldest = $has_policy ? 0 : $services_generated;
}

return array(
'timestamp' => $oldest > 0 ? $oldest : 0,
);
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

/**
 * Handle settings export.
 *
 * @return void
 */
public function handle_export_settings() {
	if ( ! \current_user_can( 'manage_options' ) ) {
	\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
	}

	\check_admin_referer( 'fp_privacy_export_settings', 'fp_privacy_export_nonce' );

	$settings = $this->options->all();
	$filename = 'fp-privacy-settings-' . \gmdate( 'Ymd-His' ) . '.json';

	\nocache_headers();
	\header( 'Content-Type: application/json; charset=utf-8' );
	\header( 'Content-Disposition: attachment; filename="' . \sanitize_file_name( $filename ) . '"' );

	echo \wp_json_encode( $settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	exit;
}

/**
 * Handle settings import.
 *
 * @return void
 */
public function handle_import_settings() {
	if ( ! \current_user_can( 'manage_options' ) ) {
	\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
	}

	\check_admin_referer( 'fp_privacy_import_settings', 'fp_privacy_import_nonce' );

	$redirect = \wp_get_referer() ? \wp_get_referer() : \admin_url( 'admin.php?page=fp-privacy-tools' );

	if ( empty( $_FILES['settings_file']['tmp_name'] ) || ! \is_uploaded_file( $_FILES['settings_file']['tmp_name'] ) ) {
	\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'missing', $redirect ) );
	exit;
	}

	$content = \file_get_contents( $_FILES['settings_file']['tmp_name'] );
	if ( false === $content ) {
	\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'error', $redirect ) );
	exit;
	}

	$data = \json_decode( $content, true );
	if ( ! is_array( $data ) ) {
	\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'invalid', $redirect ) );
	exit;
	}

	$this->options->set( $data );
	\do_action( 'fp_privacy_settings_imported', $this->options->all() );

	\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'success', $redirect ) );
	exit;
}

/**
 * Handle settings save.
 *
 * @return void
 */
public function handle_save() {
if ( ! \current_user_can( 'manage_options' ) ) {
\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
}

\check_admin_referer( 'fp_privacy_save_settings', 'fp_privacy_nonce' );

$languages = isset( $_POST['languages_active'] ) ? array_filter( array_map( 'trim', explode( ',', \wp_unslash( $_POST['languages_active'] ) ) ) ) : array();
if ( empty( $languages ) ) {
$languages = array( \get_locale() );
}

$payload = array(
'languages_active'      => $languages,
'banner_texts'          => isset( $_POST['banner_texts'] ) ? \wp_unslash( $_POST['banner_texts'] ) : array(),
'banner_layout'         => isset( $_POST['banner_layout'] ) ? \wp_unslash( $_POST['banner_layout'] ) : array(),
'consent_mode_defaults' => isset( $_POST['consent_mode_defaults'] ) ? \wp_unslash( $_POST['consent_mode_defaults'] ) : array(),
'preview_mode'          => isset( $_POST['preview_mode'] ),
'org_name'              => isset( $_POST['org_name'] ) ? \wp_unslash( $_POST['org_name'] ) : '',
'vat'                   => isset( $_POST['vat'] ) ? \wp_unslash( $_POST['vat'] ) : '',
'address'               => isset( $_POST['address'] ) ? \wp_unslash( $_POST['address'] ) : '',
'dpo_name'              => isset( $_POST['dpo_name'] ) ? \wp_unslash( $_POST['dpo_name'] ) : '',
'dpo_email'             => isset( $_POST['dpo_email'] ) ? \wp_unslash( $_POST['dpo_email'] ) : '',
'privacy_email'         => isset( $_POST['privacy_email'] ) ? \wp_unslash( $_POST['privacy_email'] ) : '',
'categories'            => $this->options->get( 'categories' ),
'retention_days'        => isset( $_POST['retention_days'] ) ? (int) $_POST['retention_days'] : $this->options->get( 'retention_days' ),
);

$this->options->set( $payload );

\wp_safe_redirect( \add_query_arg( 'updated', 'true', \wp_get_referer() ) );
exit;
}

/**
 * Handle revision bump.
 *
 * @return void
 */
public function handle_bump_revision() {
if ( ! \current_user_can( 'manage_options' ) ) {
\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
}

\check_admin_referer( 'fp_privacy_bump_revision' );

$this->options->bump_revision();
$this->options->set( $this->options->all() );

\wp_safe_redirect( \add_query_arg( 'revision-bumped', '1', \wp_get_referer() ) );
exit;
}
}
