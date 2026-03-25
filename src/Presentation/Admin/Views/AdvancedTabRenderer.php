<?php
/**
 * Advanced tab renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Admin\AdminUi;
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
		<div class="fp-privacy-tab-content" id="fp-privacy-tab-content-advanced" role="tabpanel" aria-labelledby="fp-privacy-tab-button-advanced" data-tab-content="advanced">
			<?php
			$this->render_settings_section_open( 'dashicons-bell', \__( 'Avvisi integrazioni', 'fp-privacy' ) );
			?>
			<p class="description"><?php \esc_html_e( 'Notifiche email e aggiornamento automatico degli snapshot quando il detector rileva servizi nuovi o rimossi; opzionale rigenerazione policy.', 'fp-privacy' ); ?></p>
			<?php $this->render_detector_notifications( $notifications, $notification_recipients ); ?>
			<?php $this->render_settings_section_close(); ?>

			<?php
			AdminUi::render_submit_button(
				\__( 'Salva scheda Avanzate', 'fp-privacy' ),
				'primary',
				array(
					'name'     => 'submit-advanced',
					'id'       => 'submit-advanced',
					'dashicon' => 'dashicons-saved',
				),
				false
			);
			?>
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
		<p class="description"><?php \esc_html_e( 'Configura rilevamento automatico e aggiornamento degli snapshot per i servizi di terze parti.', 'fp-privacy' ); ?></p>
		
		<label>
			<input type="checkbox" name="auto_update_services" value="1" <?php \checked( $auto_update_services, true ); ?> />
			<?php \esc_html_e( 'Aggiungi automaticamente al sistema i servizi appena rilevati', 'fp-privacy' ); ?>
		</label>
		<p class="fp-privacy-checkbox-help description">
			<?php \esc_html_e( 'Se attivo, i nuovi servizi rilevati dalla scansione giornaliera vengono aggiunti agli snapshot.', 'fp-privacy' ); ?>
		</p>

		<label>
			<input type="checkbox" name="auto_update_policies" value="1" <?php \checked( $auto_update_policies, true ); ?> />
			<?php \esc_html_e( 'Rigenera automaticamente le policy privacy e cookie', 'fp-privacy' ); ?>
		</label>
		<p class="fp-privacy-checkbox-help description">
			<?php \esc_html_e( 'Se attivo, le policy vengono rigenerate quando cambiano i servizi rilevati. Richiede l’opzione «Aggiungi automaticamente i servizi rilevati».', 'fp-privacy' ); ?>
		</p>

		<div class="fp-privacy-advanced-section-gap" aria-hidden="true"></div>

		<label>
			<input type="checkbox" name="detector_notifications[email]" value="1" <?php \checked( ! empty( $notifications['email'] ) ); ?> />
			<?php \esc_html_e( 'Invia un’email quando compaiono servizi nuovi o ne scompaiono di esistenti.', 'fp-privacy' ); ?>
		</label>
		<label>
			<span><?php \esc_html_e( 'Destinatari notifiche', 'fp-privacy' ); ?></span>
			<input type="text" name="detector_notifications[recipients]" value="<?php echo \esc_attr( $recipients ); ?>" class="regular-text" />
			<span class="description"><?php \esc_html_e( 'Indirizzi email separati da virgola. Lascia vuoto per usare l’email dell’amministratore del sito.', 'fp-privacy' ); ?></span>
		</label>
		<?php
	}
}
















