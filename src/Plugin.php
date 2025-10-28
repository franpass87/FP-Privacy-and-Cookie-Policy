<?php
/**
 * Main plugin bootstrap.
 *
 * @package FP\Privacy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy;

use FP\Privacy\Admin\DashboardWidget;
use FP\Privacy\Admin\Menu;
use FP\Privacy\Admin\PolicyEditor;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Admin\Settings;
use FP\Privacy\Admin\ConsentLogTable;
use FP\Privacy\Admin\IntegrationAudit;
use FP\Privacy\Admin\AnalyticsPage;
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
 * Main plugin class.
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

// Force update banner texts translations on boot to ensure they're always up to date
$this->options->force_update_banner_texts_translations();

// Setup FP-Multilanguage compatibility hooks if plugin is active
$this->setup_multilanguage_compatibility();

$this->log_model     = new LogModel();
$this->cleanup       = new Cleanup( $this->log_model, $this->options );
$this->consent_state = new ConsentState( $this->options, $this->log_model );

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

( new Controller( $this->consent_state, $this->options, $generator, $this->log_model ) )->hooks();
( new ExporterEraser( $this->log_model, $this->options ) )->hooks();
$this->cleanup->hooks();

        // Enable WordPress privacy tools integration by default.
        \add_filter( 'fp_privacy_enable_privacy_tools', '__return_true', 10, 2 );

        // Wire GPC enablement to saved option.
        $optionsRef = $this->options;
        \add_filter(
            'fp_privacy_enable_gpc',
            static function ( $enabled ) use ( $optionsRef ) {
                $value = $optionsRef ? (bool) $optionsRef->get( 'gpc_enabled', false ) : false;
                return (bool) $value;
            },
            10,
            1
        );

        // Map email -> consent_ids using user meta recorded at consent time.
        \add_filter(
            'fp_privacy_consent_ids_for_email',
            static function ( $ids, $email ) {
                if ( ! \is_array( $ids ) ) {
                    $ids = array();
                }

                if ( ! \is_email( $email ) || ! function_exists( '\get_user_by' ) ) {
                    return $ids;
                }

                $user = \get_user_by( 'email', $email );

                if ( ! $user || ! isset( $user->ID ) ) {
                    return $ids;
                }

                if ( function_exists( '\get_user_meta' ) ) {
                    $stored = \get_user_meta( (int) $user->ID, 'fp_consent_ids', true );

                    if ( \is_array( $stored ) ) {
                        foreach ( $stored as $candidate ) {
                            $candidate = \substr( (string) $candidate, 0, 64 );

                            if ( '' !== $candidate ) {
                                $ids[] = $candidate;
                            }
                        }
                    }
                }

                return array_values( array_unique( $ids ) );
            },
            10,
            2
        );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
\WP_CLI::add_command( 'fp-privacy', new Commands( $this->log_model, $this->options, $generator, $detector, $this->cleanup ) );
}
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

if ( \is_multisite() && $network_wide ) {
$sites = \get_sites( array( 'fields' => 'ids' ) );
foreach ( $sites as $site_id ) {
$plugin->switch_call( (int) $site_id, array( $plugin, 'setup_site' ) );
}
} else {
$plugin->setup_site();
}
}

/**
 * Deactivate plugin.
 *
 * @return void
 */
public static function deactivate() {
$plugin = self::instance();

if ( \is_multisite() ) {
$sites = \get_sites( array( 'fields' => 'ids' ) );
foreach ( $sites as $site_id ) {
$plugin->switch_call(
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
 * Perform site setup.
 *
 * @return void
 */
public function setup_site() {
$options = Options::instance();
$options->set( $options->all() );
$options->ensure_pages_exist();

// Force update banner texts translations for all active languages
$options->force_update_banner_texts_translations();

$log_model = new LogModel();
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
 * Execute callback within blog context.
 *
 * @param int      $blog_id Blog ID.
 * @param callable $callback Callback.
 *
 * @return void
 */
private function switch_call( $blog_id, $callback ) {
if ( ! \function_exists( 'switch_to_blog' ) ) {
\call_user_func( $callback );
return;
}

\switch_to_blog( $blog_id );
\call_user_func( $callback );
\restore_current_blog();
}

/**
 * Setup compatibility hooks for FP-Multilanguage plugin.
 * 
 * Ensures the two plugins work together without conflicts:
 * - Excludes privacy pages from automatic translation
 * - Syncs current language between plugins
 * - Translates policy URLs in banner links
 *
 * @return void
 */
private function setup_multilanguage_compatibility() {
	// Check if FP-Multilanguage is active
	$fpml_active = defined( 'FPML_VERSION' ) || class_exists( 'FP\MultiLanguage\Plugin' );
	
	if ( ! $fpml_active ) {
		return; // FP-Multilanguage not active, skip compatibility setup
	}
	
	// 1. EXCLUDE PRIVACY PAGES FROM AUTOMATIC TRANSLATION
	// FP-Privacy already manages multilang internally for privacy/cookie pages
	\add_filter( 'fpml_skip_post', array( $this, 'exclude_privacy_pages_from_translation' ), 10, 2 );
	
	// 2. SYNC LOCALE WITH FP-MULTILANGUAGE
	// Use FP-Multilanguage's current language for banner texts
	\add_filter( 'locale', array( $this, 'sync_locale_with_multilanguage' ), 5 );
	
	// 3. TRANSLATE POLICY URLs IN BANNER
	// Ensure links point to correct language version
	\add_filter( 'fp_privacy_policy_link_url', array( $this, 'translate_policy_url' ), 10, 3 );
}

/**
 * Exclude privacy/cookie pages from FP-Multilanguage automatic translation.
 *
 * @param bool $skip    Whether to skip translation.
 * @param int  $post_id Post ID.
 *
 * @return bool
 */
public function exclude_privacy_pages_from_translation( $skip, $post_id ) {
	if ( $skip ) {
		return $skip; // Already skipped for other reasons
	}
	
	$options = $this->options ? $this->options->all() : array();
	$pages   = isset( $options['pages'] ) && \is_array( $options['pages'] ) ? $options['pages'] : array();
	
	// Extract all privacy/cookie page IDs
	foreach ( $pages as $type => $languages ) {
		if ( ! \is_array( $languages ) ) {
			continue;
		}
		
		foreach ( $languages as $lang => $page_id ) {
			if ( (int) $page_id === (int) $post_id ) {
				return true; // This is a privacy page, exclude it
			}
		}
	}
	
	return $skip;
}

/**
 * Sync locale with FP-Multilanguage current language.
 *
 * @param string $locale Current locale.
 *
 * @return string
 */
public function sync_locale_with_multilanguage( $locale ) {
	// Get current language from FP-Multilanguage
	if ( \function_exists( 'fpml_get_current_language' ) ) {
		$current_lang = \fpml_get_current_language();
		
		if ( $current_lang && \is_string( $current_lang ) ) {
			return $current_lang;
		}
	}
	
	return $locale;
}

/**
 * Translate policy URL to use FP-Multilanguage URL structure.
 *
 * @param string $url  Original URL.
 * @param string $type Policy type (privacy|cookie).
 * @param string $lang Language code.
 *
 * @return string
 */
public function translate_policy_url( $url, $type, $lang ) {
	if ( ! $this->options ) {
		return $url;
	}
	
	$options = $this->options->all();
	$pages   = isset( $options['pages'] ) && \is_array( $options['pages'] ) ? $options['pages'] : array();
	
	// Determine correct key
	$key = ( $type === 'privacy' ) ? 'privacy_policy_page_id' : 'cookie_policy_page_id';
	
	// Get page ID for requested language
	$page_id = isset( $pages[ $key ][ $lang ] ) ? (int) $pages[ $key ][ $lang ] : 0;
	
	if ( ! $page_id ) {
		return $url;
	}
	
	// Use WordPress permalink (FP-Multilanguage will add language prefix automatically)
	$permalink = \get_permalink( $page_id );
	
	return $permalink ?: $url;
}
}
