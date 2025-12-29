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
		<div class="fp-privacy-tab-content" id="fp-privacy-tab-content-privacy" role="tabpanel" aria-labelledby="fp-privacy-tab-button-privacy" data-tab-content="privacy">
			<h2><?php \esc_html_e( 'Consent Mode defaults', 'fp-privacy' ); ?></h2>
			<?php $this->render_consent_mode_settings(); ?>

			<h2><?php \esc_html_e( 'Global Privacy Control (GPC)', 'fp-privacy' ); ?></h2>
			<?php $this->render_gpc_settings( $options['gpc_enabled'] ); ?>

			<h2><?php \esc_html_e( 'Retention & Revision', 'fp-privacy' ); ?></h2>
			<?php $this->render_retention_settings( $options['retention_days'], $options['preview_mode'], $options['consent_revision'] ); ?>

			<h2><?php \esc_html_e( 'Controller & DPO', 'fp-privacy' ); ?></h2>
			<?php $this->render_organization_settings( $options ); ?>

			<h2><?php \esc_html_e( 'Algorithmic Transparency (Digital Omnibus)', 'fp-privacy' ); ?></h2>
			<?php $this->render_algorithmic_transparency_settings( $options ); ?>

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
		<span>
			<?php echo \esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?>
			<?php
			if ( 'analytics_storage' === $key || 'ad_storage' === $key ) {
				$consent_help = '<p>' . \esc_html__( 'Google Consent Mode v2 allows you to set default consent states for different types of data collection before the user makes an explicit choice.', 'fp-privacy' ) . '</p>' .
					'<p>' . \esc_html__( 'Setting defaults to "denied" means scripts will not run until explicit consent is given. This is recommended for GDPR compliance.', 'fp-privacy' ) . '</p>';
				$this->render_help_icon(
					\__( 'Set the default consent state for this category when the user has not yet made a choice.', 'fp-privacy' ),
					\__( 'Consent Mode v2 Defaults', 'fp-privacy' ),
					$consent_help,
					'help-consent-mode-' . $key
				);
			}
			?>
		</span>
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
		<span>
			<?php \esc_html_e( 'Honor Global Privacy Control (deny non-necessary storage when GPC=1)', 'fp-privacy' ); ?>
			<?php
			$this->render_help_icon(
				\__( 'Global Privacy Control (GPC) is a browser signal that indicates a user\'s privacy preference.', 'fp-privacy' ),
				\__( 'Global Privacy Control (GPC)', 'fp-privacy' ),
				'<p>' . \esc_html__( 'Global Privacy Control (GPC) is a proposed standard that allows users to communicate their privacy preferences to websites. When enabled, this plugin will respect GPC signals from the browser.', 'fp-privacy' ) . '</p>' .
				'<p><strong>' . \esc_html__( 'How it works:', 'fp-privacy' ) . '</strong></p>' .
				'<ul>' .
				'<li>' . \esc_html__( 'When a user\'s browser sends a GPC signal (Sec-GPC: 1 header or navigator.globalPrivacyControl = true), the plugin will automatically deny non-necessary storage.', 'fp-privacy' ) . '</li>' .
				'<li>' . \esc_html__( 'Google Consent Mode default signals for analytics and advertising will be set to denied.', 'fp-privacy' ) . '</li>' .
				'<li>' . \esc_html__( 'This option is not mandatory in the EU but represents a best practice for privacy-respecting websites.', 'fp-privacy' ) . '</li>' .
				'</ul>',
				'help-gpc'
			);
			?>
		</span>
		<input type="checkbox" name="gpc_enabled" value="1" <?php \checked( $gpc_enabled, true ); ?> />
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
		$retention_help_content = '<p>' . \esc_html__( 'Retention settings control how long consent records are kept in the database.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Retention Days:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'How many days to keep consent logs. GDPR recommends keeping records for the duration of data processing plus a reasonable period for legal purposes.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Preview Mode:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'When enabled, consent decisions are logged but not enforced. Useful for testing.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Consent Revision:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Increment this number to invalidate all existing consents and require users to consent again. Useful when privacy policy changes significantly.', 'fp-privacy' ) . '</p>';
		?>
		<div>
			<h3>
				<?php \esc_html_e( 'Retention & Revision', 'fp-privacy' ); ?>
				<?php
				$this->render_help_icon(
					\__( 'Configure how long consent records are kept and manage consent revisions.', 'fp-privacy' ),
					\__( 'Retention & Revision Settings', 'fp-privacy' ),
					$retention_help_content,
					'help-retention'
				);
				?>
			</h3>
		</div>
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
	 * Render algorithmic transparency settings.
	 *
	 * @param array<string, mixed> $options Options.
	 *
	 * @return void
	 */
	private function render_algorithmic_transparency_settings( $options ) {
		$transparency = isset( $options['algorithmic_transparency'] ) && is_array( $options['algorithmic_transparency'] )
			? $options['algorithmic_transparency']
			: array(
				'enabled'            => false,
				'system_description' => '',
				'system_logic'       => '',
				'system_impact'      => '',
			);

		$enabled            = (bool) ( $transparency['enabled'] ?? false );
		$system_description = \esc_attr( $transparency['system_description'] ?? '' );
		$system_logic       = \esc_attr( $transparency['system_logic'] ?? '' );
		$system_impact      = \esc_attr( $transparency['system_impact'] ?? '' );

		$help_content = '<p>' . \esc_html__( 'The Digital Omnibus Directive (EU 2019/2161) and related regulations require transparency regarding algorithmic decision-making and profiling.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Enable Algorithmic Transparency:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Toggle to include a section in your privacy policy detailing the use of algorithmic systems.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'System Description:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Provide a clear and concise description of the algorithmic system(s) in use.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'System Logic:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Explain the main logic or criteria used by the algorithm to make decisions or recommendations.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'System Impact:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Describe the potential impact of the algorithmic system on users.', 'fp-privacy' ) . '</p>';

		?>
		<div class="fp-privacy-algorithmic-transparency">
			<label>
				<span>
					<?php \esc_html_e( 'Enable Algorithmic Transparency Disclosure', 'fp-privacy' ); ?>
					<?php
					$this->render_help_icon(
						\__( 'Configure disclosure settings for algorithmic transparency as required by the Digital Omnibus Directive.', 'fp-privacy' ),
						\__( 'Algorithmic Transparency Settings', 'fp-privacy' ),
						$help_content,
						'help-algorithmic-transparency'
					);
					?>
				</span>
				<input type="checkbox" name="algorithmic_transparency[enabled]" value="1" <?php \checked( $enabled, true ); ?> />
			</label>
			<p class="description"><?php \esc_html_e( 'Include a section in your privacy policy detailing the use of algorithmic systems and their impact.', 'fp-privacy' ); ?></p>

			<div class="fp-privacy-algorithmic-transparency-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
				<label>
					<span><?php \esc_html_e( 'Description of Algorithmic System', 'fp-privacy' ); ?></span>
					<textarea name="algorithmic_transparency[system_description]" class="large-text" rows="4"><?php echo \esc_textarea( $system_description ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Provide a clear and concise description of the algorithmic system(s) in use.', 'fp-privacy' ); ?></p>
				</label>
				<label>
					<span><?php \esc_html_e( 'Logic of Algorithmic System', 'fp-privacy' ); ?></span>
					<textarea name="algorithmic_transparency[system_logic]" class="large-text" rows="4"><?php echo \esc_textarea( $system_logic ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Explain the main logic or criteria used by the algorithm to make decisions or recommendations.', 'fp-privacy' ); ?></p>
				</label>
				<label>
					<span><?php \esc_html_e( 'Impact of Algorithmic System', 'fp-privacy' ); ?></span>
					<textarea name="algorithmic_transparency[system_impact]" class="large-text" rows="4"><?php echo \esc_textarea( $system_impact ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Describe the potential impact of the algorithmic system on users.', 'fp-privacy' ); ?></p>
				</label>
			</div>
		</div>
		<script>
		(function() {
			var checkbox = document.querySelector('input[name="algorithmic_transparency[enabled]"]');
			var fields = document.querySelector('.fp-privacy-algorithmic-transparency-fields');
			if (checkbox && fields) {
				checkbox.addEventListener('change', function() {
					fields.style.display = this.checked ? '' : 'none';
				});
			}
		})();
		</script>
		<?php
	}
}
















