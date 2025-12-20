<?php
/**
 * Privacy tab renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Utils\Options;

/**
 * Renders the Privacy tab content.
 */
class PrivacyTabRenderer extends SettingsRendererBase {
	/**
	 * Render privacy tab content.
	 *
	 * @param array<string, mixed> $data Tab data.
	 *
	 * @return void
	 */
	public function render( array $data ) {
		$options = $data['options'];
		?>
		<div class="fp-privacy-tab-content" data-tab-content="privacy">
			<h2><?php \esc_html_e( 'Consent Mode defaults', 'fp-privacy' ); ?></h2>
			<?php $this->render_consent_mode_settings(); ?>

			<h2><?php \esc_html_e( 'Global Privacy Control (GPC)', 'fp-privacy' ); ?></h2>
			<?php $this->render_gpc_settings( $options['gpc_enabled'] ); ?>

			<h2><?php \esc_html_e( 'Retention & Revision', 'fp-privacy' ); ?></h2>
			<?php $this->render_retention_settings( $options['retention_days'], $options['preview_mode'], $options['consent_revision'] ); ?>

			<h2><?php \esc_html_e( 'Controller & DPO', 'fp-privacy' ); ?></h2>
			<?php $this->render_organization_settings( $options ); ?>

			<?php \submit_button( \__( 'Salva impostazioni privacy', 'fp-privacy' ), 'primary', 'submit-privacy', false ); ?>
		</div>
		<?php
	}

	/**
	 * Render consent mode defaults.
	 *
	 * Uses ConsentModeDefaults value object for type safety.
	 *
	 * @return void
	 */
	private function render_consent_mode_settings() {
		// Get consent mode defaults as value object, then convert to array for rendering.
		$consent_mode = $this->options->get_consent_mode_defaults();
		$defaults = $consent_mode->to_array();
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
}
















