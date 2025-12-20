<?php
/**
 * Multisite manager.
 *
 * @package FP\Privacy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;

/**
 * Handles multisite operations.
 */
class MultisiteManager {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Log model (optional, will be created if not provided).
	 *
	 * @var LogModel|null
	 */
	private $log_model;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 * @param LogModel|null $log_model Log model (optional).
	 */
	public function __construct( Options $options, ?LogModel $log_model = null ) {
		$this->options = $options;
		$this->log_model = $log_model;
	}

	/**
	 * Execute callback within blog context.
	 *
	 * @param int      $blog_id Blog ID.
	 * @param callable $callback Callback.
	 *
	 * @return void
	 */
	public function switch_call( $blog_id, $callback ) {
		if ( ! \function_exists( 'switch_to_blog' ) ) {
			\call_user_func( $callback );
			return;
		}

		\switch_to_blog( $blog_id );
		\call_user_func( $callback );
		\restore_current_blog();
	}

	/**
	 * Perform site setup.
	 *
	 * @return void
	 */
	public function setup_site() {
		// Use instance options if not provided in constructor (backward compatibility).
		// Try to get from container first, then fallback to singleton.
		if ( ! $this->options ) {
			if ( class_exists( '\\FP\\Privacy\\Core\\Kernel' ) ) {
				try {
					$kernel = \FP\Privacy\Core\Kernel::make();
					$container = $kernel->getContainer();
					if ( $container->has( Options::class ) ) {
						$this->options = $container->get( Options::class );
					} else {
						$this->options = Options::instance();
					}
				} catch ( \Exception $e ) {
					$this->options = Options::instance();
				}
			} else {
				$this->options = Options::instance();
			}
		}
		$options = $this->options;
		$options->set( $options->all() );
		$options->ensure_pages_exist();

		// Force update banner texts translations for all active languages
		if ( method_exists( $options, 'force_update_banner_texts_translations' ) ) {
			$options->force_update_banner_texts_translations();
		}

		// Use provided log model or create new one (backward compatibility).
		$log_model = $this->log_model ?: new LogModel();
		$log_model->maybe_create_table();

		if ( function_exists( '\fp_privacy_get_ip_salt' ) ) {
			\fp_privacy_get_ip_salt();
		}

		if ( ! \wp_next_scheduled( 'fp_privacy_cleanup' ) ) {
			\wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'fp_privacy_cleanup' );
		}

		if ( ! \wp_next_scheduled( 'fp_privacy_detector_audit' ) ) {
			\wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'fp_privacy_detector_audit' );
		}
	}

	/**
	 * Provision a new site in multisite.
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return void
	 */
	public function provision_new_site( $blog_id ) {
		$this->switch_call( $blog_id, array( $this, 'setup_site' ) );
	}

	/**
	 * Activate plugin for network or single site.
	 *
	 * @param bool $network_wide Network wide activation.
	 *
	 * @return void
	 */
	public function activate( $network_wide ) {
		if ( \is_multisite() && $network_wide ) {
			$sites = \get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				$this->switch_call( (int) $site_id, array( $this, 'setup_site' ) );
			}
		} else {
			$this->setup_site();
		}
	}

	/**
	 * Deactivate plugin for network or single site.
	 *
	 * @return void
	 */
	public function deactivate() {
		if ( \is_multisite() ) {
			$sites = \get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				$this->switch_call(
					(int) $site_id,
					static function () {
						\wp_clear_scheduled_hook( 'fp_privacy_cleanup' );
						\wp_clear_scheduled_hook( 'fp_privacy_detector_audit' );
					}
				);
			}
		} else {
			\wp_clear_scheduled_hook( 'fp_privacy_cleanup' );
			\wp_clear_scheduled_hook( 'fp_privacy_detector_audit' );
		}
	}
}


