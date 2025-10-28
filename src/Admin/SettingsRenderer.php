<?php
/**
 * Settings renderer.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

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
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
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
		$default_texts_raw = $data['default_texts_raw'];
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

<div class="fp-privacy-tab-content active" data-tab-content="banner">

<h2><?php \esc_html_e( 'Languages', 'fp-privacy' ); ?></h2>
		<p class="description"><?php \esc_html_e( 'Provide active languages (comma separated locale codes).', 'fp-privacy' ); ?></p>
		<input type="text" name="languages_active" class="regular-text" value="<?php echo \esc_attr( implode( ',', $languages ) ); ?>" />

		<h2><?php \esc_html_e( 'Banner content', 'fp-privacy' ); ?></h2>
		<?php foreach ( $languages as $lang ) :
			$lang = $this->options->normalize_language( $lang );
			$text = isset( $options['banner_texts'][ $lang ] ) && \is_array( $options['banner_texts'][ $lang ] ) ? $options['banner_texts'][ $lang ] : array();
			$text = \wp_parse_args( $text, $default_texts_raw );
?>
<div class="fp-privacy-language-panel" data-lang="<?php echo \esc_attr( $lang ); ?>">
<h3><?php echo \esc_html( \sprintf( \__( 'Language: %s', 'fp-privacy' ), $lang ) ); ?></h3>
<?php $this->render_banner_text_fields( $lang, $text ); ?>
</div>
<?php endforeach; ?>

<div class="fp-privacy-preview">
<h2><?php \esc_html_e( 'Banner preview', 'fp-privacy' ); ?></h2>
<p class="description"><?php \esc_html_e( 'Adjust copy, colors, and layout to see a live preview of the cookie banner.', 'fp-privacy' ); ?></p>
<?php $this->render_preview_controls( $languages, $primary_lang ); ?>
</div>

<h2><?php \esc_html_e( 'Layout', 'fp-privacy' ); ?></h2>
<?php $this->render_layout_settings( $options['banner_layout'] ); ?>

<h2><?php \esc_html_e( 'Palette', 'fp-privacy' ); ?></h2>
<?php $this->render_palette_settings( $options['banner_layout']['palette'] ); ?>



<?php \submit_button( \__( 'Salva impostazioni banner', 'fp-privacy' ), 'primary', 'submit-banner', false ); ?>
</div>

<div class="fp-privacy-tab-content" data-tab-content="privacy">

<h2><?php \esc_html_e( 'Consent Mode defaults', 'fp-privacy' ); ?></h2>
<?php $this->render_consent_mode_settings( $options['consent_mode_defaults'] ); ?>

<h2><?php \esc_html_e( 'Global Privacy Control (GPC)', 'fp-privacy' ); ?></h2>
<?php $this->render_gpc_settings( $options['gpc_enabled'] ); ?>

<h2><?php \esc_html_e( 'Retention & Revision', 'fp-privacy' ); ?></h2>
<?php $this->render_retention_settings( $options['retention_days'], $options['preview_mode'], $options['consent_revision'] ); ?>

<h2><?php \esc_html_e( 'Controller & DPO', 'fp-privacy' ); ?></h2>
<?php $this->render_organization_settings( $options ); ?>



<?php \submit_button( \__( 'Salva impostazioni privacy', 'fp-privacy' ), 'primary', 'submit-privacy', false ); ?>
</div>

<div class="fp-privacy-tab-content" data-tab-content="advanced">

<h2><?php \esc_html_e( 'Integration alerts', 'fp-privacy' ); ?></h2>
<?php $this->render_detector_notifications( $data['notifications'], $notification_recipients ); ?>



<?php \submit_button( \__( 'Salva impostazioni avanzate', 'fp-privacy' ), 'primary', 'submit-advanced', false ); ?>
</div>

<div class="fp-privacy-tab-content" data-tab-content="cookies">

<h2><?php \esc_html_e( 'Script blocking', 'fp-privacy' ); ?></h2>
<?php $this->render_script_blocking_settings( $languages, $script_rules, $script_categories ); ?>



<h2><?php \esc_html_e( 'Detected services', 'fp-privacy' ); ?></h2>
<?php $this->render_detected_services( $detected ); ?>

<p class="description"><?php \esc_html_e( 'Use the policy editor to regenerate your documents after services change.', 'fp-privacy' ); ?></p>

<?php \submit_button( \__( 'Salva impostazioni cookie', 'fp-privacy' ), 'primary', 'submit-cookies', false ); ?>
</div>
</form>


</div>
		<?php
	}

	/**
	 * Render banner text input fields.
	 *
	 * @param string               $lang Language code.
	 * @param array<string, string> $text Text values.
	 *
	 * @return void
	 */
	private function render_banner_text_fields( $lang, $text ) {
		$fields = array(
			'title'          => \__( 'Title', 'fp-privacy' ),
			'message'        => \__( 'Message', 'fp-privacy' ),
			'btn_accept'     => \__( 'Accept button label', 'fp-privacy' ),
			'btn_reject'     => \__( 'Reject button label', 'fp-privacy' ),
			'btn_prefs'      => \__( 'Preferences button label', 'fp-privacy' ),
			'revision_notice'=> \__( 'Revision notice message', 'fp-privacy' ),
			'modal_title'    => \__( 'Modal title', 'fp-privacy' ),
			'modal_close'    => \__( 'Modal close label', 'fp-privacy' ),
			'modal_save'     => \__( 'Modal save button', 'fp-privacy' ),
			'toggle_locked'  => \__( 'Locked toggle label', 'fp-privacy' ),
			'toggle_enabled' => \__( 'Enabled toggle label', 'fp-privacy' ),
			'debug_label'    => \__( 'Debug panel label', 'fp-privacy' ),
		);

		foreach ( $fields as $key => $label ) {
			$field_type = 'message' === $key ? 'textarea' : 'text';
			$this->render_text_field( "banner_texts[{$lang}][{$key}]", $label, $text[ $key ] ?? '', $field_type, $key );
		}

		// Policy link
		?>
		<label>
		<span><?php \esc_html_e( 'Policy link URL', 'fp-privacy' ); ?></span>
		<input type="url" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][link_policy]" value="<?php echo \esc_attr( $text['link_policy'] ?? '' ); ?>" class="regular-text" data-field="link_policy" />
		</label>
		<?php
	}

	/**
	 * Render a text/textarea field.
	 *
	 * @param string $name       Field name.
	 * @param string $label      Field label.
	 * @param string $value      Field value.
	 * @param string $type       Field type (text|textarea).
	 * @param string $data_field Data field attribute.
	 *
	 * @return void
	 */
	private function render_text_field( $name, $label, $value, $type = 'text', $data_field = '' ) {
		?>
		<label>
		<span><?php echo \esc_html( $label ); ?></span>
		<?php if ( 'textarea' === $type ) : ?>
			<textarea name="<?php echo \esc_attr( $name ); ?>" rows="4" class="large-text" data-field="<?php echo \esc_attr( $data_field ); ?>"><?php echo \esc_textarea( $value ); ?></textarea>
		<?php else : ?>
			<input type="text" name="<?php echo \esc_attr( $name ); ?>" value="<?php echo \esc_attr( $value ); ?>" class="regular-text" data-field="<?php echo \esc_attr( $data_field ); ?>" />
		<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render preview controls.
	 *
	 * @param array<int, string> $languages    Active languages.
	 * @param string             $primary_lang Primary language.
	 *
	 * @return void
	 */
	private function render_preview_controls( $languages, $primary_lang ) {
		?>
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
		<?php
	}

	/**
	 * Render layout settings.
	 *
	 * @param array<string, mixed> $layout Layout configuration.
	 *
	 * @return void
	 */
	private function render_layout_settings( $layout ) {
		?>
		<div class="fp-privacy-layout">
		<label>
		<span><?php \esc_html_e( 'Display type', 'fp-privacy' ); ?></span>
		<select name="banner_layout[type]">
		<option value="floating" <?php \selected( $layout['type'], 'floating' ); ?>><?php \esc_html_e( 'Floating', 'fp-privacy' ); ?></option>
		<option value="bar" <?php \selected( $layout['type'], 'bar' ); ?>><?php \esc_html_e( 'Bar', 'fp-privacy' ); ?></option>
		</select>
		</label>
		<label>
		<span><?php \esc_html_e( 'Position', 'fp-privacy' ); ?></span>
		<select name="banner_layout[position]">
		<option value="top" <?php \selected( $layout['position'], 'top' ); ?>><?php \esc_html_e( 'Top', 'fp-privacy' ); ?></option>
		<option value="bottom" <?php \selected( $layout['position'], 'bottom' ); ?>><?php \esc_html_e( 'Bottom', 'fp-privacy' ); ?></option>
		</select>
		</label>
		<label>
		<input type="checkbox" name="banner_layout[sync_modal_and_button]" value="1" <?php \checked( $layout['sync_modal_and_button'], true ); ?> />
		<?php \esc_html_e( 'Synchronize modal and button palette', 'fp-privacy' ); ?>
		</label>
		<label>
		<input type="checkbox" name="banner_layout[enable_dark_mode]" value="1" <?php \checked( ! empty( $layout['enable_dark_mode'] ), true ); ?> />
		<?php \esc_html_e( 'Enable dark mode (automatically adjusts colors for dark backgrounds)', 'fp-privacy' ); ?>
		</label>
		<p class="description" style="margin-left: 25px;">
		<?php \esc_html_e( '⚠️ Activate this only if your site uses a dark theme and you want the banner to match. The palette will be automatically adjusted.', 'fp-privacy' ); ?>
		</p>
		</div>
		<?php
	}

	/**
	 * Render palette color settings.
	 *
	 * @param array<string, string> $palette Color palette.
	 *
	 * @return void
	 */
	private function render_palette_settings( $palette ) {
		// Etichette descrittive in italiano per i colori della palette
		$labels = array(
			'surface_bg'          => \__( 'Sfondo banner', 'fp-privacy' ),
			'surface_text'        => \__( 'Testo banner', 'fp-privacy' ),
			'button_primary_bg'   => \__( 'Sfondo pulsante principale', 'fp-privacy' ),
			'button_primary_tx'   => \__( 'Testo pulsante principale', 'fp-privacy' ),
			'button_secondary_bg' => \__( 'Sfondo pulsanti secondari', 'fp-privacy' ),
			'button_secondary_tx' => \__( 'Testo pulsanti secondari', 'fp-privacy' ),
			'link'                => \__( 'Colore link', 'fp-privacy' ),
			'border'              => \__( 'Bordo', 'fp-privacy' ),
			'focus'               => \__( 'Colore focus', 'fp-privacy' ),
		);
		?>
		<div class="fp-privacy-palette">
		<?php foreach ( $palette as $key => $color ) : ?>
		<label>
		<span><?php echo \esc_html( isset( $labels[ $key ] ) ? $labels[ $key ] : ucwords( str_replace( '_', ' ', $key ) ) ); ?></span>
		<input type="text" 
		       name="banner_layout[palette][<?php echo \esc_attr( $key ); ?>]" 
		       value="<?php echo \esc_attr( $color ); ?>" 
		       class="fp-privacy-color-picker" 
		       data-default-color="<?php echo \esc_attr( $color ); ?>" />
		</label>
		<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render consent mode defaults.
	 *
	 * @param array<string, string> $defaults Consent mode defaults.
	 *
	 * @return void
	 */
	private function render_consent_mode_settings( $defaults ) {
		?>
		<div class="fp-privacy-consent-mode">
		<?php foreach ( $defaults as $key => $value ) : ?>
		<label>
		<span><?php echo \esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></span>
		<select name="consent_mode_defaults[<?php echo \esc_attr( $key ); ?>]">
		<option value="granted" <?php \selected( $value, 'granted' ); ?>><?php \esc_html_e( 'Granted', 'fp-privacy' ); ?></option>
		<option value="denied" <?php \selected( $value, 'denied' ); ?>><?php \esc_html_e( 'Denied', 'fp-privacy' ); ?></option>
		</select>
		</label>
		<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render GPC settings.
	 *
	 * @param bool $gpc_enabled Whether GPC is enabled.
	 *
	 * @return void
	 */
	private function render_gpc_settings( $gpc_enabled ) {
		?>
		<label>
		<input type="checkbox" name="gpc_enabled" value="1" <?php \checked( $gpc_enabled, true ); ?> />
		<?php \esc_html_e( 'Honor Global Privacy Control (deny non-necessary storage when GPC=1)', 'fp-privacy' ); ?>
		</label>
		<p class="description">
		<?php \esc_html_e( 'When enabled, the plugin will treat a Global Privacy Control signal (browser header Sec-GPC: 1 or navigator.globalPrivacyControl) as an opt-out for non-necessary storage. Google Consent Mode default signals for analytics and advertising will be set to denied. This option is not mandatory in the EU but can be adopted as a best practice.', 'fp-privacy' ); ?>
		</p>
		<?php
	}

	/**
	 * Render retention and revision settings.
	 *
	 * @param int  $retention_days   Retention days.
	 * @param bool $preview_mode     Preview mode enabled.
	 * @param int  $consent_revision Current revision.
	 *
	 * @return void
	 */
	private function render_retention_settings( $retention_days, $preview_mode, $consent_revision ) {
		?>
		<label>
		<span><?php \esc_html_e( 'Retention days', 'fp-privacy' ); ?></span>
		<input type="number" min="1" name="retention_days" value="<?php echo \esc_attr( $retention_days ); ?>" />
		</label>
		<label>
		<input type="checkbox" name="preview_mode" value="1" <?php \checked( $preview_mode, true ); ?> />
		<?php \esc_html_e( 'Enable preview mode (admins only)', 'fp-privacy' ); ?>
		</label>
		<p><?php echo \esc_html( \sprintf( \__( 'Current consent revision: %d', 'fp-privacy' ), $consent_revision ) ); ?></p>
		<p><a class="button button-secondary" href="<?php echo \esc_url( \wp_nonce_url( \admin_url( 'admin-post.php?action=fp_privacy_bump_revision' ), 'fp_privacy_bump_revision' ) ); ?>"><?php \esc_html_e( 'Force new consent (bump revision)', 'fp-privacy' ); ?></a></p>
		<?php
	}

	/**
	 * Render organization settings.
	 *
	 * @param array<string, mixed> $options Options.
	 *
	 * @return void
	 */
	private function render_organization_settings( $options ) {
		?>
		<div class="fp-privacy-org">
		<label><span><?php \esc_html_e( 'Organization name', 'fp-privacy' ); ?></span><input type="text" name="org_name" value="<?php echo \esc_attr( $options['org_name'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'VAT / Tax ID', 'fp-privacy' ); ?></span><input type="text" name="vat" value="<?php echo \esc_attr( $options['vat'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'Registered address', 'fp-privacy' ); ?></span><input type="text" name="address" value="<?php echo \esc_attr( $options['address'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'DPO name', 'fp-privacy' ); ?></span><input type="text" name="dpo_name" value="<?php echo \esc_attr( $options['dpo_name'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'DPO email', 'fp-privacy' ); ?></span><input type="email" name="dpo_email" value="<?php echo \esc_attr( $options['dpo_email'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'Privacy contact email', 'fp-privacy' ); ?></span><input type="email" name="privacy_email" value="<?php echo \esc_attr( $options['privacy_email'] ); ?>" class="regular-text" /></label>
		</div>
		<?php
	}

	/**
	 * Render detector notification settings.
	 *
	 * @param array<string, mixed> $notifications Notification settings.
	 * @param string               $recipients    Recipients string.
	 *
	 * @return void
	 */
	private function render_detector_notifications( $notifications, $recipients ) {
		$auto_update_services = $this->options->get( 'auto_update_services', false );
		$auto_update_policies = $this->options->get( 'auto_update_policies', false );
		?>
		<p class="description"><?php \esc_html_e( 'Configure automatic detection and updates for third-party services.', 'fp-privacy' ); ?></p>
		
		<label>
			<input type="checkbox" name="auto_update_services" value="1" <?php \checked( $auto_update_services, true ); ?> />
			<?php \esc_html_e( 'Automatically add newly detected services to the system', 'fp-privacy' ); ?>
		</label>
		<p class="description" style="margin-left: 1.5em; margin-top: 0.5em;">
			<?php \esc_html_e( 'When enabled, new services detected by the daily scan will be automatically added to your snapshots.', 'fp-privacy' ); ?>
		</p>

		<label>
			<input type="checkbox" name="auto_update_policies" value="1" <?php \checked( $auto_update_policies, true ); ?> />
			<?php \esc_html_e( 'Automatically regenerate privacy and cookie policies', 'fp-privacy' ); ?>
		</label>
		<p class="description" style="margin-left: 1.5em; margin-top: 0.5em;">
			<?php \esc_html_e( 'When enabled, policies will be automatically regenerated when new services are detected. Requires "Automatically add newly detected services" to be enabled.', 'fp-privacy' ); ?>
		</p>

		<hr style="margin: 1.5em 0;">

		<label>
			<input type="checkbox" name="detector_notifications[email]" value="1" <?php \checked( ! empty( $notifications['email'] ) ); ?> />
			<?php \esc_html_e( 'Send an email when new services are detected or existing ones disappear.', 'fp-privacy' ); ?>
		</label>
		<label>
			<span><?php \esc_html_e( 'Notification recipients', 'fp-privacy' ); ?></span>
			<input type="text" name="detector_notifications[recipients]" value="<?php echo \esc_attr( $recipients ); ?>" class="regular-text" />
			<span class="description"><?php \esc_html_e( 'Comma separated email addresses. Leave blank to use the site administrator email.', 'fp-privacy' ); ?></span>
		</label>
		<?php
	}

	/**
	 * Render script blocking settings.
	 *
	 * @param array<int, string>   $languages         Active languages.
	 * @param array<string, mixed> $script_rules      Script rules by language.
	 * @param array<string, mixed> $script_categories Categories by language.
	 *
	 * @return void
	 */
	private function render_script_blocking_settings( $languages, $script_rules, $script_categories ) {
		?>
		<p class="description"><?php \esc_html_e( 'Pause specific scripts, styles, or embeds until the visitor grants the corresponding consent category.', 'fp-privacy' ); ?></p>
		<p class="description"><?php \esc_html_e( 'Detected integrations prefill suggested handles and patterns; edit a category to override the automatic rules.', 'fp-privacy' ); ?></p>
		<?php foreach ( $languages as $script_lang ) :
			$script_lang      = $this->options->normalize_language( $script_lang );
			$rules            = isset( $script_rules[ $script_lang ] ) ? $script_rules[ $script_lang ] : array();
			$categories_meta  = isset( $script_categories[ $script_lang ] ) ? $script_categories[ $script_lang ] : array();
			?>
			<div class="fp-privacy-language-panel" data-lang="<?php echo \esc_attr( $script_lang ); ?>">
				<h3><?php echo \esc_html( \sprintf( \__( 'Language: %s', 'fp-privacy' ), $script_lang ) ); ?></h3>
				<?php foreach ( $categories_meta as $slug => $meta ) :
					$category_rules = isset( $rules[ $slug ] ) && \is_array( $rules[ $slug ] ) ? $rules[ $slug ] : array();
					$this->render_category_blocking_rules( $script_lang, $slug, $meta['label'], $category_rules );
				endforeach; ?>
			</div>
		<?php endforeach;
	}

	/**
	 * Render blocking rules for a specific category.
	 *
	 * @param string               $lang           Language code.
	 * @param string               $slug           Category slug.
	 * @param string               $label          Category label.
	 * @param array<string, mixed> $category_rules Category rules.
	 *
	 * @return void
	 */
	private function render_category_blocking_rules( $lang, $slug, $label, $category_rules ) {
		$handles        = isset( $category_rules['script_handles'] ) && \is_array( $category_rules['script_handles'] ) ? implode( "\n", $category_rules['script_handles'] ) : '';
		$style_handles  = isset( $category_rules['style_handles'] ) && \is_array( $category_rules['style_handles'] ) ? implode( "\n", $category_rules['style_handles'] ) : '';
		$patterns       = isset( $category_rules['patterns'] ) && \is_array( $category_rules['patterns'] ) ? implode( "\n", $category_rules['patterns'] ) : '';
		$iframes        = isset( $category_rules['iframes'] ) && \is_array( $category_rules['iframes'] ) ? implode( "\n", $category_rules['iframes'] ) : '';
		?>
		<fieldset class="fp-privacy-script-category">
			<legend><?php echo \esc_html( \sprintf( \__( '%s category', 'fp-privacy' ), $label ) ); ?></legend>
			<label>
				<span><?php \esc_html_e( 'Script handles to block (one per line)', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][script_handles]" rows="3" class="large-text"><?php echo \esc_textarea( $handles ); ?></textarea>
			</label>
			<label>
				<span><?php \esc_html_e( 'Style handles to block (one per line)', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][style_handles]" rows="2" class="large-text"><?php echo \esc_textarea( $style_handles ); ?></textarea>
			</label>
			<label>
				<span><?php \esc_html_e( 'Script source substrings', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][patterns]" rows="3" class="large-text"><?php echo \esc_textarea( $patterns ); ?></textarea>
				<span class="description"><?php \esc_html_e( 'Any script tag whose src contains one of these values will be paused until consent is granted.', 'fp-privacy' ); ?></span>
			</label>
			<label>
				<span><?php \esc_html_e( 'Iframe source substrings', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][iframes]" rows="3" class="large-text"><?php echo \esc_textarea( $iframes ); ?></textarea>
				<span class="description"><?php \esc_html_e( 'Iframes whose src contains one of these values will be replaced with a consent prompt.', 'fp-privacy' ); ?></span>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render detected services table.
	 *
	 * @param array<int, array<string, mixed>> $detected Detected services.
	 *
	 * @return void
	 */
	private function render_detected_services( $detected ) {
		?>
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