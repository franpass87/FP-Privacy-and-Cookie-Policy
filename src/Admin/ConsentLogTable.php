<?php
/**
 * Consent log admin page.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;

/**
 * Displays consent log entries.
 */
class ConsentLogTable {
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
	 * Data loader.
	 *
	 * @var ConsentLogDataLoader
	 */
	private $data_loader;

	/**
	 * Renderer.
	 *
	 * @var ConsentLogRenderer
	 */
	private $renderer;

	/**
	 * Exporter.
	 *
	 * @var ConsentLogExporter
	 */
	private $exporter;

	/**
	 * Constructor.
	 *
	 * @param LogModel $log_model Log model.
	 * @param Options  $options   Options.
	 */
	public function __construct( LogModel $log_model, Options $options ) {
		$this->log_model   = $log_model;
		$this->options     = $options;
		$this->data_loader = new ConsentLogDataLoader( $log_model, 50 );
		$this->renderer    = new ConsentLogRenderer();
		$this->exporter    = new ConsentLogExporter( $log_model );
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'fp_privacy_admin_page_consent_log', array( $this, 'render_page' ) );
		\add_action( 'admin_post_fp_privacy_export_csv', array( $this->exporter, 'handle_export' ) );
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		$args = $this->data_loader->get_request_args();
		$data = $this->data_loader->load_data_safe( $args );
		$urls = $this->renderer->build_urls( $args );

		$this->renderer->render_page( $data, $args, $urls );
	}
}
