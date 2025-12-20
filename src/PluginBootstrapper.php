<?php
/**
 * Plugin bootstrapper.
 *
 * @package FP\Privacy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy;

use FP\Privacy\Admin\AnalyticsPage;
use FP\Privacy\Admin\ConsentLogTable;
use FP\Privacy\Admin\DashboardWidget;
use FP\Privacy\Admin\DiagnosticTools;
use FP\Privacy\Admin\IntegrationAudit;
use FP\Privacy\Admin\Menu;
use FP\Privacy\Admin\PolicyEditor;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Admin\Settings;
use FP\Privacy\CLI\Commands;
use FP\Privacy\Consent\Cleanup;
use FP\Privacy\Consent\ExporterEraser;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Frontend\Banner;
use FP\Privacy\Frontend\Blocks;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Frontend\ScriptBlocker;
use FP\Privacy\Frontend\Shortcodes;
use FP\Privacy\Integrations\ConsentMode;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\REST\Controller;
use FP\Privacy\Utils\I18n;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\View;

/**
 * Handles plugin component initialization.
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 *             The functionality has been migrated to service providers.
 *             This class is kept only for backward compatibility with the old Plugin class.
 *             New code should use the Kernel and service providers instead.
 */
class PluginBootstrapper {
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
	 * Cleanup handler.
	 *
	 * @var Cleanup
	 */
	private $cleanup;

	/**
	 * Consent state manager.
	 *
	 * @var ConsentState
	 */
	private $consent_state;

	/**
	 * Constructor.
	 *
	 * @param Options     $options     Options handler.
	 * @param LogModel    $log_model   Log model.
	 * @param Cleanup     $cleanup     Cleanup handler.
	 * @param ConsentState $consent_state Consent state manager.
	 */
	public function __construct( Options $options, LogModel $log_model, Cleanup $cleanup, ConsentState $consent_state ) {
		$this->options      = $options;
		$this->log_model    = $log_model;
		$this->cleanup      = $cleanup;
		$this->consent_state = $consent_state;
	}

	/**
	 * Bootstrap all plugin components.
	 *
	 * @return void
	 */
	public function bootstrap() {
		// Force update banner texts translations on boot to ensure they're always up to date
		$this->options->force_update_banner_texts_translations();

		$view      = new View();
		$i18n      = new I18n();
		$detector  = new DetectorRegistry();
		$generator = new PolicyGenerator( $this->options, $detector, $view );

		// Load textdomain immediately - CRITICAL FIX
		// Previously this was registered on 'plugins_loaded' hook which was already running
		$i18n->load_textdomain();
		$i18n->hooks();

		( new ConsentMode( $this->options ) )->hooks();

		$shortcodes = new Shortcodes( $this->options, $view, $generator );
		$shortcodes->set_state( $this->consent_state );
		$shortcodes->hooks();
		( new Blocks( $this->options ) )->hooks();
		( new Banner( $this->options, $this->consent_state ) )->hooks();
		( new ScriptBlocker( $this->options, $this->consent_state ) )->hooks();

		( new Menu() )->hooks();
		( new Settings( $this->options, $detector, $generator ) )->hooks();
		( new PolicyEditor( $this->options, $generator ) )->hooks();
		( new IntegrationAudit( $this->options, $generator ) )->hooks();
		( new ConsentLogTable( $this->log_model, $this->options ) )->hooks();
		( new DashboardWidget( $this->log_model ) )->hooks();

		// QUICK WIN #3: Analytics Dashboard
		( new AnalyticsPage( $this->log_model, $this->options ) )->hooks();

		// Diagnostic Tools
		( new DiagnosticTools( $this->options, $this->log_model ) )->hooks();

		( new Controller( $this->consent_state, $this->options, $generator, $this->log_model ) )->hooks();
		( new ExporterEraser( $this->log_model, $this->options ) )->hooks();
		$this->cleanup->hooks();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'fp-privacy', new Commands( $this->log_model, $this->options, $generator, $detector, $this->cleanup ) );
		}
	}
}







