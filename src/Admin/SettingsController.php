<?php
/**
 * Settings controller.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Presentation\Admin\Controllers\PolicyLinksAutoPopulator;
use FP\Privacy\Presentation\Admin\Controllers\SettingsDataPreparer;
use FP\Privacy\Presentation\Admin\Controllers\SettingsExportImportHandler;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;

/**
 * Handles settings business logic and request processing.
 */
class SettingsController {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Detector registry.
	 *
	 * @var DetectorRegistry
	 */
	private $detector;

	/**
	 * Policy generator.
	 *
	 * @var PolicyGenerator
	 */
	private $generator;

	/**
	 * Settings renderer.
	 *
	 * @var SettingsRenderer
	 */
	private $renderer;

	/**
	 * Settings data preparer.
	 *
	 * @var SettingsDataPreparer
	 */
	private $data_preparer;

	/**
	 * Policy links auto-populator.
	 *
	 * @var PolicyLinksAutoPopulator
	 */
	private $policy_links_populator;

	/**
	 * Export/import handler.
	 *
	 * @var SettingsExportImportHandler
	 */
	private $export_import_handler;

	/**
	 * Save handler.
	 *
	 * @var SettingsSaveHandler
	 */
	private $save_handler;

	/**
	 * Constructor.
	 *
	 * @param Options          $options   Options handler.
	 * @param DetectorRegistry $detector  Detector.
	 * @param PolicyGenerator  $generator Generator.
	 */
	public function __construct( Options $options, DetectorRegistry $detector, PolicyGenerator $generator ) {
		$this->options                = $options;
		$this->detector               = $detector;
		$this->generator              = $generator;
		$this->renderer              = new SettingsRenderer( $options );
		// Use new Presentation layer classes, fallback to old for compatibility.
		if ( class_exists( '\\FP\\Privacy\\Presentation\\Admin\\Controllers\\PolicyLinksAutoPopulator' ) ) {
			$this->policy_links_populator = new \FP\Privacy\Presentation\Admin\Controllers\PolicyLinksAutoPopulator( $options );
			$this->data_preparer          = new \FP\Privacy\Presentation\Admin\Controllers\SettingsDataPreparer( $options, $detector, $this->policy_links_populator );
			$this->export_import_handler  = new \FP\Privacy\Presentation\Admin\Controllers\SettingsExportImportHandler( $options );
			$this->save_handler           = new \FP\Privacy\Presentation\Admin\Controllers\SettingsSaveHandler( $options, $this->policy_links_populator );
		} else {
			// Fallback to old location during migration.
			$this->policy_links_populator = new \FP\Privacy\Admin\Handler\PolicyLinksAutoPopulator( $options );
			$this->data_preparer          = new \FP\Privacy\Admin\Handler\SettingsDataPreparer( $options, $detector, $this->policy_links_populator );
			$this->export_import_handler  = new \FP\Privacy\Admin\Handler\SettingsExportImportHandler( $options );
			$this->save_handler           = new \FP\Privacy\Admin\Handler\SettingsSaveHandler( $options, $this->policy_links_populator );
		}
	}

	/**
	 * Prepare data for settings page.
	 *
	 * @return array<string, mixed>
	 */
	public function prepare_settings_data() {
		return $this->data_preparer->prepare();
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$data = $this->prepare_settings_data();
		$this->renderer->render_settings_page( $data );
	}

	/**
	 * Render tools page.
	 *
	 * @return void
	 */
	public function render_tools_page() {
		$this->renderer->render_tools_page();
	}

	/**
	 * Render guide page.
	 *
	 * @return void
	 */
	public function render_guide_page() {
		$this->renderer->render_guide_page();
	}

	/**
	 * Handle settings save.
	 *
	 * @return void
	 */
	public function handle_save() {
		$this->save_handler->save();
	}

	/**
	 * Handle revision bump.
	 *
	 * @return void
	 */
	public function handle_bump_revision() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_bump_revision' );

		$this->options->bump_revision();
		$this->options->set( $this->options->all() );

		\wp_safe_redirect( \add_query_arg( 'revision-bumped', '1', \wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle settings export.
	 *
	 * @return void
	 */
	public function handle_export_settings() {
		$this->export_import_handler->handle_export();
	}

	/**
	 * Handle settings import.
	 *
	 * @return void
	 */
	public function handle_import_settings() {
		$this->export_import_handler->handle_import();
	}
}