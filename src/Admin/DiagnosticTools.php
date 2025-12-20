<?php
/**
 * Diagnostic and setup tools for FP Privacy.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Presentation\Admin\Controllers\Diagnostic\DiagnosticHandlers;
use FP\Privacy\Presentation\Admin\Controllers\Diagnostic\DiagnosticPageRenderer;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;

/**
 * Provides diagnostic and setup tools in admin.
 */
class DiagnosticTools {
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
	 * Page renderer.
	 *
	 * @var DiagnosticPageRenderer
	 */
	private $page_renderer;

	/**
	 * Action handlers.
	 *
	 * @var DiagnosticHandlers
	 */
	private $handlers;

	/**
	 * Constructor.
	 *
	 * @param Options  $options   Options handler.
	 * @param LogModel $log_model Log model.
	 */
	public function __construct( Options $options, LogModel $log_model ) {
		$this->options       = $options;
		$this->log_model     = $log_model;
		$this->page_renderer = new DiagnosticPageRenderer( $options );
		$this->handlers      = new DiagnosticHandlers( $options );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'admin_menu', array( $this, 'add_menu_page' ), 100 );
		\add_action( 'admin_post_fp_privacy_setup_defaults', array( $this->handlers, 'handle_setup_defaults' ) );
		\add_action( 'admin_post_fp_privacy_force_banner', array( $this->handlers, 'handle_force_banner' ) );
		\add_action( 'admin_post_fp_privacy_disable_preview', array( $this->handlers, 'handle_disable_preview' ) );
		\add_action( 'admin_post_fp_privacy_clear_consent', array( $this->handlers, 'handle_clear_consent' ) );
	}

	/**
	 * Add diagnostic tools submenu page.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		\add_submenu_page(
			'fp-privacy',
			\__( 'Strumenti Diagnostica', 'fp-privacy' ),
			\__( 'Diagnostica', 'fp-privacy' ),
			'manage_options',
			'fp-privacy-diagnostics',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render diagnostic tools page.
	 *
	 * @return void
	 */
	public function render_page() {
		$this->page_renderer->render();
	}
}
