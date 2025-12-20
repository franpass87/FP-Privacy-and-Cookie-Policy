<?php
/**
 * Advanced tab renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Utils\Options;

/**
 * Renders the Advanced tab content.
 */
class AdvancedTabRenderer extends SettingsRendererBase {
	/**
	 * Render advanced tab content.
	 *
	 * @param array<string, mixed> $data Tab data.
	 *
	 * @return void
	 */
	public function render( array $data ) {
		$notifications         = $data['notifications'];
		$notification_recipients = $data['notification_recipients'];
		?>
		<div class="fp-privacy-tab-content" data-tab-content="advanced">
			<h2><?php \esc_html_e( 'Integration alerts', 'fp-privacy' ); ?></h2>
			<?php $this->render_detector_notifications( $notifications, $notification_recipients ); ?>

			<?php \submit_button( \__( 'Salva impostazioni avanzate', 'fp-privacy' ), 'primary', 'submit-advanced', false ); ?>
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
}
















