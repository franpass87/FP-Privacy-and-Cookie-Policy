<?php
/**
 * Analytics Dashboard Page
 * QUICK WIN #3: Dashboard con grafici Chart.js
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;

/**
 * Analytics dashboard con metriche e grafici consent rate
 */
class AnalyticsPage {
	/**
	 * Log model.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Asset manager.
	 *
	 * @var AnalyticsAssetManager
	 */
	private $asset_manager;

	/**
	 * Renderer.
	 *
	 * @var AnalyticsRenderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param LogModel $log_model Log model.
	 * @param Options  $options   Options.
	 */
	public function __construct( LogModel $log_model, Options $options ) {
		$this->log_model     = $log_model;
		$this->options       = $options;
		$this->asset_manager = new AnalyticsAssetManager( $log_model );
		$this->renderer      = new AnalyticsRenderer( $log_model );
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'fp_privacy_admin_page_analytics', array( $this, 'render' ) );
		\add_action( 'admin_enqueue_scripts', array( $this->asset_manager, 'enqueue_chart_assets' ) );
	}

	/**
	 * Render analytics page
	 *
	 * @return void
	 */
	public function render() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'fp-privacy' ) );
		}

		$this->renderer->render();
	}
}
