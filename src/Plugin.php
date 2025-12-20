<?php
/**
 * Main plugin bootstrap.
 *
 * @package FP\Privacy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy;

use FP\Privacy\Consent\Cleanup;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Utils\Options;

/**
 * Main plugin class.
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 *             The plugin now uses Kernel-based bootstrap with service providers.
 *             This class is kept only for backward compatibility as a fallback.
 *             New code should use Kernel::make() and service providers instead.
 */
class Plugin {
/**
 * Instance.
 *
 * @var Plugin
 */
private static $instance;

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
 * Get instance.
 *
 * @return Plugin
 */
public static function instance() {
if ( ! self::$instance ) {
self::$instance = new self();
}

return self::$instance;
}

	/**
	 * Boot plugin.
	 *
	 * @return void
	 */
	public function boot() {
		$this->options = Options::instance();

		// Note: ensure_pages_exist() is called only during:
		// - Plugin activation (via activate() method)
		// - Settings save (via Options::set())
		// - Manual regeneration (via PolicyEditor)
		// We don't call it on every boot to prevent duplicate page creation

		// Setup FP-Multilanguage compatibility hooks if plugin is active
		$multilanguage = new MultilanguageCompatibility( $this->options );
		$multilanguage->setup();

		$this->log_model     = new LogModel();
		$this->cleanup       = new Cleanup( $this->log_model, $this->options );
		$this->consent_state = new ConsentState( $this->options, $this->log_model );

		$bootstrapper = new PluginBootstrapper( $this->options, $this->log_model, $this->cleanup, $this->consent_state );
		$bootstrapper->bootstrap();

		$filters = new PluginFilters( $this->options );
		$filters->register();
	}

	/**
	 * Activate plugin.
	 *
	 * @param bool $network_wide Network wide activation.
	 *
	 * @return void
	 */
	public static function activate( $network_wide ) {
		$plugin = self::instance();
		$multisite = new MultisiteManager( $plugin->options );
		$multisite->activate( $network_wide );
	}

	/**
	 * Deactivate plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$plugin = self::instance();
		$multisite = new MultisiteManager( $plugin->options );
		$multisite->deactivate();
	}

	/**
	 * Provision a new site in multisite.
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return void
	 */
	public function provision_new_site( $blog_id ) {
		$multisite = new MultisiteManager( $this->options );
		$multisite->provision_new_site( $blog_id );
	}
}
