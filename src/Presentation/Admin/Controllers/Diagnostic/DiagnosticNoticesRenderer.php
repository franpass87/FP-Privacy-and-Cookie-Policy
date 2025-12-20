<?php
/**
 * Diagnostic notices renderer.
 *
 * @package FP\Privacy\Admin\Diagnostic
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Controllers\Diagnostic;

/**
 * Handles rendering of diagnostic page notices.
 */
class DiagnosticNoticesRenderer {
	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	public static function render() {
		if ( isset( $_GET['fp_privacy_success'] ) ) {
			$message = '';
			switch ( $_GET['fp_privacy_success'] ) {
				case 'setup_defaults':
					$message = \__( 'Impostazioni di default configurate con successo!', 'fp-privacy' );
					break;
				case 'force_banner':
					$message = \__( 'Modalità preview attivata e cookie di consenso cancellato.', 'fp-privacy' );
					break;
				case 'disable_preview':
					$message = \__( 'Modalità preview disattivata.', 'fp-privacy' );
					break;
				case 'clear_consent':
					$message = \__( 'Consenso corrente cancellato.', 'fp-privacy' );
					break;
			}

			if ( $message ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
				<?php
			}
		}
	}
}















