<?php
/**
 * Diagnostic page renderer.
 *
 * @package FP\Privacy\Admin\Diagnostic
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Controllers\Diagnostic;

use FP\Privacy\Frontend\ConsentState as FrontendConsentState;
use FP\Privacy\Utils\Options;
use FP\Privacy\Consent\LogModel;

/**
 * Renders the diagnostic tools page.
 */
class DiagnosticPageRenderer {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Log model.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Constructor.
	 *
	 * @param Options  $options   Options handler.
	 * @param LogModel $log_model Log model.
	 */
	public function __construct( Options $options, LogModel $log_model ) {
		$this->options   = $options;
		$this->log_model = $log_model;
	}

	/**
	 * Render the diagnostic page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$consent_state  = new FrontendConsentState( $this->options, $this->log_model );
		$lang           = \determine_locale();
		$frontend_state = $consent_state->get_frontend_state( $lang );
		$all_options    = $this->options->all();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( \__( 'Strumenti Diagnostica FP Privacy', 'fp-privacy' ) ); ?></h1>

			<?php DiagnosticNoticesRenderer::render(); ?>

			<div class="fp-privacy-diagnostic-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
				
				<!-- Colonna 1: Stato Corrente -->
				<div>
					<?php DiagnosticStateRenderer::render_current_state( $frontend_state ); ?>
					<?php DiagnosticContentRenderer::render_consent_categories( $all_options, $lang ); ?>
					<?php DiagnosticContentRenderer::render_policy_pages( $all_options ); ?>
				</div>

				<!-- Colonna 2: Azioni -->
				<div>
					<?php DiagnosticContentRenderer::render_quick_actions(); ?>
					<?php DiagnosticStateRenderer::render_debug_info( $frontend_state ); ?>
					<?php DiagnosticContentRenderer::render_useful_links(); ?>
				</div>
			</div>

			<?php DiagnosticStylesRenderer::render(); ?>
		</div>
		<?php
	}
}
