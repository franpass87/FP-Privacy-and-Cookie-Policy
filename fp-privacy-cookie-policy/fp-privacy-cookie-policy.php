<?php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Plugin URI:  https://francescopasseri.com/
 * Description: Gestisci privacy policy, cookie policy e consenso informato in modo conforme al GDPR e al Google Consent Mode v2.
 * Version:     1.14.0
 * Author:      Francesco Passeri
 * Author URI:  https://francescopasseri.com/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fp-privacy-cookie-policy
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'FP_PRIVACY_COOKIE_POLICY_MIN_PHP' ) ) {
    define( 'FP_PRIVACY_COOKIE_POLICY_MIN_PHP', '7.4' );
}

if ( ! defined( 'FP_PRIVACY_COOKIE_POLICY_MIN_WP' ) ) {
    define( 'FP_PRIVACY_COOKIE_POLICY_MIN_WP', '6.0' );
}

$fp_privacy_cookie_policy_requirement_error = '';

if ( version_compare( PHP_VERSION, FP_PRIVACY_COOKIE_POLICY_MIN_PHP, '<' ) ) {
    $fp_privacy_cookie_policy_requirement_error = sprintf(
        /* translators: %s is the minimum required PHP version. */
        __( 'FP Privacy and Cookie Policy richiede PHP %s o superiore. Il plugin è stato disattivato.', 'fp-privacy-cookie-policy' ),
        FP_PRIVACY_COOKIE_POLICY_MIN_PHP
    );
} elseif ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], FP_PRIVACY_COOKIE_POLICY_MIN_WP, '<' ) ) {
    $fp_privacy_cookie_policy_requirement_error = sprintf(
        /* translators: %s is the minimum required WordPress version. */
        __( 'FP Privacy and Cookie Policy richiede WordPress %s o superiore. Il plugin è stato disattivato.', 'fp-privacy-cookie-policy' ),
        FP_PRIVACY_COOKIE_POLICY_MIN_WP
    );
}

if ( $fp_privacy_cookie_policy_requirement_error ) {
    if ( ! function_exists( 'fp_privacy_cookie_policy_render_requirement_notice' ) ) {
        /**
         * Render environment requirement notices when the plugin cannot run.
         */
        function fp_privacy_cookie_policy_render_requirement_notice() {
            global $fp_privacy_cookie_policy_requirement_error;

            if ( empty( $fp_privacy_cookie_policy_requirement_error ) ) {
                return;
            }

            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html( $fp_privacy_cookie_policy_requirement_error )
            );
        }
    }

    add_action( 'admin_notices', 'fp_privacy_cookie_policy_render_requirement_notice' );
    add_action( 'network_admin_notices', 'fp_privacy_cookie_policy_render_requirement_notice' );

    add_action(
        'admin_init',
        static function () {
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }

            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            deactivate_plugins( plugin_basename( __FILE__ ) );
        }
    );

    register_activation_hook(
        __FILE__,
        static function () use ( $fp_privacy_cookie_policy_requirement_error ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            deactivate_plugins( plugin_basename( __FILE__ ) );

            wp_die(
                esc_html( $fp_privacy_cookie_policy_requirement_error ),
                esc_html__( 'FP Privacy and Cookie Policy', 'fp-privacy-cookie-policy' ),
                array( 'back_link' => true )
            );
        }
    );

    return;
}

if ( ! class_exists( 'FP_Privacy_Cookie_Policy' ) ) {

    class FP_Privacy_Cookie_Policy {

        const OPTION_KEY                 = 'fp_privacy_cookie_settings';
        const VERSION                    = '1.14.0';
        const PREVIEW_QUERY_KEY          = 'fp_cookie_preview';
        const VERSION_OPTION             = 'fp_privacy_cookie_version';
        const CONSENT_COOKIE             = 'fp_consent_state';
        const CONSENT_TABLE              = 'fp_consent_logs';
        const NONCE_ACTION               = 'fp_privacy_nonce';
        const DEFAULT_LANGUAGE           = 'it';
        const CLEANUP_HOOK               = 'fp_privacy_cleanup_logs';
        const GENERATION_HASH_OPTION     = 'fp_privacy_cookie_generation_hash';
        const GENERATION_SNAPSHOT_OPTION = 'fp_privacy_cookie_generation_snapshot';
        const GENERATION_TIME_OPTION     = 'fp_privacy_cookie_generation_time';
        const PRIVACY_PAGE_OPTION        = 'fp_privacy_cookie_privacy_page_id';
        const COOKIE_PAGE_OPTION         = 'fp_privacy_cookie_cookie_page_id';
        const POLICY_PAGE_META_KEY       = '_fp_privacy_generated';

        /**
         * Singleton instance.
         *
         * @var FP_Privacy_Cookie_Policy|null
         */
        protected static $instance = null;

        /**
         * Cache for localized settings.
         *
         * @var array
         */
        protected $localized_cache = array();

        /**
         * Cache plugin settings for the current request.
         *
         * @var array|null
         */
        protected $settings_cache = null;

        /**
         * Cache asset availability lookups for the current request.
         *
         * @var array<string, bool>
         */
        protected $asset_status = array();

        /**
         * Track missing assets that should surface an administrator notice.
         *
         * @var array<string, array<string, mixed>>
         */
        protected $missing_assets = array();

        /**
         * Track the hook currently used to render the banner so it can be swapped dynamically.
         *
         * @var string|null
         */
        protected $current_banner_hook = null;

        /**
         * Cached list of human readable language labels.
         *
         * @var array
         */
        protected $language_labels = array();

        /**
         * Cached categories metadata for the consent log display.
         *
         * @var array|null
         */
        protected $consent_log_categories = null;

        /**
         * Build an absolute URL for a plugin asset.
         *
         * @param string $relative_path Asset path relative to the plugin root.
         *
         * @return string
         */
        protected function get_asset_url( $relative_path ) {
            $relative_path = $this->normalize_asset_path( $relative_path );

            return plugins_url( $relative_path, __FILE__ );
        }

        /**
         * Retrieve an asset version string based on the file modification time.
         *
         * Falls back to the plugin version when the file cannot be read.
         *
         * @param string $relative_path Asset path relative to the plugin root.
         *
         * @return string
         */
        protected function get_asset_version( $relative_path ) {
            $relative_path = $this->normalize_asset_path( $relative_path );
            $file_path     = $this->get_asset_file_path( $relative_path );

            if ( is_readable( $file_path ) ) {
                $timestamp = filemtime( $file_path );

                if ( $timestamp ) {
                    return (string) $timestamp;
                }
            }

            return self::VERSION;
        }

        /**
         * Normalize relative asset paths.
         *
         * @param string $relative_path Raw relative path.
         *
         * @return string
         */
        protected function normalize_asset_path( $relative_path ) {
            return ltrim( (string) $relative_path, '/' );
        }

        /**
         * Resolve the absolute filesystem path for an asset.
         *
         * @param string $relative_path Normalized relative path.
         *
         * @return string
         */
        protected function get_asset_file_path( $relative_path ) {
            return plugin_dir_path( __FILE__ ) . $this->normalize_asset_path( $relative_path );
        }

        /**
         * Determine whether an asset is available on disk.
         *
         * @param string $relative_path Asset path relative to the plugin root.
         *
         * @return bool
         */
        protected function asset_exists( $relative_path ) {
            $relative_path = $this->normalize_asset_path( $relative_path );

            if ( isset( $this->asset_status[ $relative_path ] ) ) {
                return $this->asset_status[ $relative_path ];
            }

            $file_path = $this->get_asset_file_path( $relative_path );
            $exists    = is_readable( $file_path );

            $this->asset_status[ $relative_path ] = $exists;

            return $exists;
        }

        /**
         * Flag a missing asset so administrators can be notified.
         *
         * @param string $relative_path Asset path relative to the plugin root.
         * @param array  $context       Extra context for the notice.
         */
        protected function flag_missing_asset( $relative_path, array $context = array() ) {
            $relative_path = $this->normalize_asset_path( $relative_path );

            if ( $this->asset_exists( $relative_path ) ) {
                return;
            }

            if ( isset( $this->missing_assets[ $relative_path ] ) ) {
                $this->missing_assets[ $relative_path ] = array_merge( $this->missing_assets[ $relative_path ], $context );

                return;
            }

            $this->missing_assets[ $relative_path ] = $context;
        }

        /**
         * Validate critical plugin assets to surface actionable notices.
         */
        public function validate_required_assets() {
            $assets = array(
                'assets/js/fp-consent.js' => array(
                    'label'       => __( 'Script principale del banner cookie', 'fp-privacy-cookie-policy' ),
                    'scope'       => 'frontend',
                    'severity'    => 'error',
                    'description' => __( 'Rigenera gli asset JavaScript (ad esempio con npm run build) e carica nuovamente il file per ripristinare il funzionamento del banner.', 'fp-privacy-cookie-policy' ),
                ),
                'assets/css/banner.css'   => array(
                    'label'       => __( 'Stili del banner cookie', 'fp-privacy-cookie-policy' ),
                    'scope'       => 'frontend',
                    'severity'    => 'warning',
                    'description' => __( 'Il banner rimarrà funzionale ma privo di stile finché il foglio di stile non sarà disponibile.', 'fp-privacy-cookie-policy' ),
                ),
                'assets/css/admin.css'    => array(
                    'label'       => __( 'Stili dell’interfaccia di amministrazione', 'fp-privacy-cookie-policy' ),
                    'scope'       => 'admin',
                    'severity'    => 'warning',
                    'description' => __( 'Ricompila o ripristina il foglio di stile per visualizzare correttamente le pagine di configurazione del plugin.', 'fp-privacy-cookie-policy' ),
                ),
                'assets/js/admin.js'      => array(
                    'label'       => __( 'Script dell’interfaccia di amministrazione', 'fp-privacy-cookie-policy' ),
                    'scope'       => 'admin',
                    'severity'    => 'info',
                    'description' => __( 'Alcune funzioni avanzate della schermata impostazioni potrebbero non essere disponibili senza questo file.', 'fp-privacy-cookie-policy' ),
                ),
            );

            foreach ( $assets as $path => $context ) {
                $this->flag_missing_asset( $path, $context );
            }
        }

        /**
         * Render warnings for missing assets in the plugin admin screens.
         */
        public function render_asset_warnings() {
            if ( empty( $this->missing_assets ) ) {
                return;
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

            if ( ! $screen || 'toplevel_page_fp-privacy-cookie-policy' !== $screen->id ) {
                return;
            }

            $messages     = array();
            $highest_type = 'info';

            foreach ( $this->missing_assets as $path => $context ) {
                $label       = isset( $context['label'] ) ? $context['label'] : $path;
                $description = isset( $context['description'] ) ? $context['description'] : '';
                $severity    = isset( $context['severity'] ) ? $context['severity'] : 'warning';

                if ( 'error' === $severity ) {
                    $highest_type = 'error';
                } elseif ( 'warning' === $severity && 'error' !== $highest_type ) {
                    $highest_type = 'warning';
                }

                $message = sprintf(
                    /* translators: 1: asset label, 2: relative file path */
                    __( '%1$s (%2$s) non è disponibile.', 'fp-privacy-cookie-policy' ),
                    $label,
                    $path
                );

                if ( $description ) {
                    $message .= ' ' . $description;
                }

                $messages[] = esc_html( $message );
            }

            if ( empty( $messages ) ) {
                return;
            }

            $class = 'notice notice-info';

            if ( 'error' === $highest_type ) {
                $class = 'notice notice-error';
            } elseif ( 'warning' === $highest_type ) {
                $class = 'notice notice-warning';
            }

            echo '<div class="' . esc_attr( $class ) . '"><p><strong>' . esc_html__( 'Asset richiesti mancanti', 'fp-privacy-cookie-policy' ) . '</strong></p><ul>';

            foreach ( $messages as $message ) {
                echo '<li>' . $message . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            echo '</ul></div>';
        }

        /**
         * Get singleton instance.
         *
         * @return FP_Privacy_Cookie_Policy
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * FP_Privacy_Cookie_Policy constructor.
         */
        private function __construct() {
            add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ) );
            add_action( 'init', array( $this, 'load_textdomain' ) );
            add_action( 'init', array( $this, 'maybe_generate_legal_documents' ), 20 );
            add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );
            add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
            add_action( 'admin_notices', array( $this, 'render_asset_warnings' ) );
            add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widgets' ) );
            add_action( 'init', array( $this, 'setup_banner_render_hook' ), 9 );
            add_action( 'init', array( $this, 'validate_required_assets' ), 5 );
            add_action( 'init', array( $this, 'register_blocks' ) );
            add_action( 'init', array( $this, 'register_shortcodes' ) );
            add_action( 'wp_ajax_fp_save_consent', array( $this, 'ajax_save_consent' ) );
            add_action( 'wp_ajax_nopriv_fp_save_consent', array( $this, 'ajax_save_consent' ) );
            add_action( 'admin_post_fp_export_consent', array( $this, 'export_consent_logs' ) );
            add_action( 'admin_post_fp_export_settings', array( $this, 'handle_export_settings' ) );
            add_action( 'admin_post_fp_import_settings', array( $this, 'handle_import_settings' ) );
            add_action( 'admin_post_fp_recreate_consent_table', array( $this, 'handle_recreate_consent_table' ) );
            add_action( 'admin_post_fp_cleanup_consent_logs', array( $this, 'handle_cleanup_consent_logs' ) );
            add_action( 'admin_post_fp_reset_consent_revision', array( $this, 'handle_reset_consent_revision' ) );
            add_action( self::CLEANUP_HOOK, array( $this, 'cleanup_consent_logs' ) );
            add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_privacy_exporter' ) );
            add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_privacy_eraser' ) );
            add_action( 'admin_notices', array( $this, 'maybe_render_admin_notices' ) );
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );
            add_filter( 'site_status_tests', array( $this, 'register_site_health_tests' ) );

            add_action( 'add_option_' . self::OPTION_KEY, array( $this, 'flush_settings_cache' ), 10, 0 );
            add_action( 'update_option_' . self::OPTION_KEY, array( $this, 'flush_settings_cache' ), 10, 0 );
            add_action( 'delete_option_' . self::OPTION_KEY, array( $this, 'flush_settings_cache' ), 10, 0 );

            if ( is_multisite() ) {
                add_action( 'wp_initialize_site', array( $this, 'handle_new_site_initialization' ), 10, 2 );
                add_action( 'wpmu_new_blog', array( $this, 'handle_new_site_legacy' ), 10, 6 );
            }
        }

        /**
         * Maybe regenerate the privacy and cookie policies when the site changes.
         *
         * @param bool $force Whether to force regeneration even if no changes are detected.
         */
        public function maybe_generate_legal_documents( $force = false ) {
            if ( ! is_blog_installed() ) {
                return;
            }

            if ( ! apply_filters( 'fp_privacy_enable_auto_generation', true ) ) {
                return;
            }

            if ( ! $force && function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
                return;
            }

            $settings = $this->get_settings();

            if ( isset( $settings['auto_generate'] ) && ! $settings['auto_generate'] && ! $force ) {
                return;
            }

            $snapshot      = $this->get_site_snapshot( $settings );
            $snapshot_hash = md5( wp_json_encode( $snapshot ) );
            $previous_hash = get_option( self::GENERATION_HASH_OPTION, '' );

            if ( ! $force && $snapshot_hash === $previous_hash ) {
                return;
            }

            $previous_snapshot = get_option( self::GENERATION_SNAPSHOT_OPTION, array() );

            if ( ! is_array( $previous_snapshot ) ) {
                $previous_snapshot = array();
            }

            $changes   = $this->diff_snapshots( $previous_snapshot, $snapshot );
            $timestamp = current_time( 'timestamp' );

            $documents = $this->generate_legal_documents( $snapshot, $changes, $timestamp );

            if ( empty( $documents['privacy']['it'] ) || empty( $documents['cookie']['it'] ) ) {
                return;
            }

            $settings['privacy_policy_content'] = $documents['privacy']['it'];
            $settings['cookie_policy_content']  = $documents['cookie']['it'];
            $settings['auto_generate']          = true;

            if ( ! isset( $settings['translations'] ) || ! is_array( $settings['translations'] ) ) {
                $settings['translations'] = array();
            }

            if ( ! isset( $settings['translations']['en'] ) || ! is_array( $settings['translations']['en'] ) ) {
                $settings['translations']['en'] = array();
            }

            $settings['translations']['en']['privacy_policy_content'] = $documents['privacy']['en'];
            $settings['translations']['en']['cookie_policy_content']  = $documents['cookie']['en'];

            update_option( self::OPTION_KEY, $settings );
            update_option( self::GENERATION_HASH_OPTION, $snapshot_hash );
            update_option( self::GENERATION_SNAPSHOT_OPTION, $snapshot );
            update_option( self::GENERATION_TIME_OPTION, $timestamp );

            $this->flush_settings_cache();

            $this->ensure_policy_pages_exist();

            do_action( 'fp_privacy_documents_regenerated', $snapshot, $changes, $timestamp );
        }

        /**
         * Generate the privacy and cookie policy documents for supported languages.
         *
         * @param array $snapshot  Snapshot of the current site configuration.
         * @param array $changes   Differences detected since the previous snapshot.
         * @param int   $timestamp Generation timestamp.
         *
         * @return array{
         *     privacy: array<string,string>,
         *     cookie: array<string,string>
         * }
         */
        protected function generate_legal_documents( array $snapshot, array $changes, $timestamp ) {
            $privacy_it = $this->generate_privacy_policy_document( $snapshot, $changes, $timestamp, self::DEFAULT_LANGUAGE );
            $privacy_en = $this->generate_privacy_policy_document( $snapshot, $changes, $timestamp, 'en' );
            $cookie_it  = $this->generate_cookie_policy_document( $snapshot, $changes, $timestamp, self::DEFAULT_LANGUAGE );
            $cookie_en  = $this->generate_cookie_policy_document( $snapshot, $changes, $timestamp, 'en' );

            $privacy_it = apply_filters( 'fp_privacy_generated_privacy_policy', $privacy_it, $snapshot, $changes, $timestamp, self::DEFAULT_LANGUAGE );
            $privacy_en = apply_filters( 'fp_privacy_generated_privacy_policy', $privacy_en, $snapshot, $changes, $timestamp, 'en' );
            $cookie_it  = apply_filters( 'fp_privacy_generated_cookie_policy', $cookie_it, $snapshot, $changes, $timestamp, self::DEFAULT_LANGUAGE );
            $cookie_en  = apply_filters( 'fp_privacy_generated_cookie_policy', $cookie_en, $snapshot, $changes, $timestamp, 'en' );

            return array(
                'privacy' => array(
                    'it' => $this->sanitize_generated_html( $privacy_it ),
                    'en' => $this->sanitize_generated_html( $privacy_en ),
                ),
                'cookie'  => array(
                    'it' => $this->sanitize_generated_html( $cookie_it ),
                    'en' => $this->sanitize_generated_html( $cookie_en ),
                ),
            );
        }

        /**
         * Build the privacy policy document for a specific language.
         *
         * @param array  $snapshot  Site snapshot.
         * @param array  $changes   Detected changes.
         * @param int    $timestamp Generation timestamp.
         * @param string $language  Target language code.
         *
         * @return string
         */
        protected function generate_privacy_policy_document( array $snapshot, array $changes, $timestamp, $language ) {
            $strings        = $this->get_auto_policy_strings( $language );
            $shared         = isset( $strings['shared'] ) ? $strings['shared'] : array();
            $privacy        = isset( $strings['privacy'] ) ? $strings['privacy'] : array();
            $change_items   = $this->build_changes_list( $changes, $strings );
            $datetime_parts = $this->get_generation_datetime_parts( $timestamp );
            $site           = isset( $snapshot['site'] ) && is_array( $snapshot['site'] ) ? $snapshot['site'] : array();
            $theme          = isset( $snapshot['theme'] ) && is_array( $snapshot['theme'] ) ? $snapshot['theme'] : array();
            $content        = isset( $snapshot['content'] ) && is_array( $snapshot['content'] ) ? $snapshot['content'] : array();
            $plugins        = isset( $snapshot['plugins'] ) && is_array( $snapshot['plugins'] ) ? $snapshot['plugins'] : array();
            $consent        = isset( $snapshot['consent'] ) && is_array( $snapshot['consent'] ) ? $snapshot['consent'] : array();

            $html = '';

            if ( ! empty( $privacy['title'] ) ) {
                $html .= '<h2>' . esc_html( $privacy['title'] ) . '</h2>';
            }

            $generated_template = isset( $shared['generated_on'] ) ? $shared['generated_on'] : '%1$s %2$s';
            $generated_text     = sprintf( $generated_template, $datetime_parts['date'], $datetime_parts['time'] );
            $html              .= '<p><em>' . esc_html( $generated_text ) . '</em></p>';

            if ( ! empty( $privacy['data_intro'] ) ) {
                $html .= '<p>' . esc_html( $privacy['data_intro'] ) . '</p>';
            }

            $controller_items = array();
            $controller_labels = isset( $shared['controller_labels'] ) && is_array( $shared['controller_labels'] ) ? $shared['controller_labels'] : array();

            if ( ! empty( $site['name'] ) && ! empty( $controller_labels['name'] ) ) {
                $controller_items[] = '<li><strong>' . esc_html( $controller_labels['name'] ) . ':</strong> ' . esc_html( $site['name'] ) . '</li>';
            }

            if ( ! empty( $site['url'] ) && ! empty( $controller_labels['url'] ) ) {
                $controller_items[] = '<li><strong>' . esc_html( $controller_labels['url'] ) . ':</strong> ' . esc_html( $site['url'] ) . '</li>';
            }

            if ( ! empty( $site['admin_email'] ) && ! empty( $controller_labels['email'] ) ) {
                $controller_items[] = '<li><strong>' . esc_html( $controller_labels['email'] ) . ':</strong> ' . esc_html( $site['admin_email'] ) . '</li>';
            }

            if ( ! empty( $site['description'] ) && ! empty( $controller_labels['description'] ) ) {
                $controller_items[] = '<li><strong>' . esc_html( $controller_labels['description'] ) . ':</strong> ' . esc_html( $site['description'] ) . '</li>';
            }

            if ( ! empty( $site['language'] ) && ! empty( $controller_labels['language'] ) ) {
                $controller_items[] = '<li><strong>' . esc_html( $controller_labels['language'] ) . ':</strong> ' . esc_html( $site['language'] ) . '</li>';
            }

            if ( ! empty( $site['timezone'] ) && ! empty( $controller_labels['timezone'] ) ) {
                $controller_items[] = '<li><strong>' . esc_html( $controller_labels['timezone'] ) . ':</strong> ' . esc_html( $this->format_timezone_value( $site['timezone'] ) ) . '</li>';
            }

            if ( ! empty( $shared['controller_heading'] ) ) {
                $html .= '<h3>' . esc_html( $shared['controller_heading'] ) . '</h3>';
            }

            if ( ! empty( $shared['controller_intro'] ) ) {
                $html .= '<p>' . esc_html( $shared['controller_intro'] ) . '</p>';
            }

            if ( ! empty( $controller_items ) ) {
                $html .= '<ul>' . implode( '', $controller_items ) . '</ul>';
            }

            if ( ! empty( $change_items ) && ! empty( $strings['changes']['heading'] ) ) {
                $html .= '<h3>' . esc_html( $strings['changes']['heading'] ) . '</h3>';
                $changes_markup = array();

                foreach ( $change_items as $item ) {
                    $text            = vsprintf( $item['text'], $item['args'] );
                    $changes_markup[] = '<li>' . esc_html( $text ) . '</li>';
                }

                if ( $changes_markup ) {
                    $html .= '<ul>' . implode( '', $changes_markup ) . '</ul>';
                }
            }

            if ( ! empty( $shared['theme_heading'] ) ) {
                $html .= '<h3>' . esc_html( $shared['theme_heading'] ) . '</h3>';
            }

            $theme_items = array();

            if ( ! empty( $theme['name'] ) && ! empty( $shared['theme_active'] ) ) {
                $theme_items[] = '<li>' . sprintf( esc_html( $shared['theme_active'] ), esc_html( $theme['name'] ) ) . '</li>';
            }

            if ( ! empty( $theme['version'] ) && ! empty( $shared['theme_version'] ) ) {
                $theme_items[] = '<li>' . sprintf( esc_html( $shared['theme_version'] ), esc_html( $theme['version'] ) ) . '</li>';
            }

            if ( ! empty( $theme['parent'] ) && ! empty( $shared['theme_child_of'] ) ) {
                $theme_items[] = '<li>' . sprintf( esc_html( $shared['theme_child_of'] ), esc_html( $theme['parent'] ) ) . '</li>';
            }

            if ( $theme_items ) {
                $html .= '<ul>' . implode( '', $theme_items ) . '</ul>';
            }

            if ( ! empty( $shared['content_heading'] ) ) {
                $html .= '<h3>' . esc_html( $shared['content_heading'] ) . '</h3>';
            }

            if ( ! empty( $shared['content_intro'] ) ) {
                $html .= '<p>' . esc_html( $shared['content_intro'] ) . '</p>';
            }

            $content_items  = array();
            $content_counts = isset( $content['counts'] ) && is_array( $content['counts'] ) ? $content['counts'] : array();
            $content_labels = isset( $shared['content_labels'] ) && is_array( $shared['content_labels'] ) ? $shared['content_labels'] : array();

            foreach ( $content_labels as $key => $label ) {
                if ( isset( $content_counts[ $key ] ) ) {
                    $content_items[] = '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( number_format_i18n( (int) $content_counts[ $key ] ) ) . '</li>';
                }
            }

            if ( ! empty( $content['custom_post_types'] ) && ! empty( $shared['content_custom_post_types_label'] ) ) {
                $custom_types = implode( ', ', array_map( 'sanitize_text_field', array_values( $content['custom_post_types'] ) ) );
                if ( $custom_types ) {
                    $content_items[] = '<li><strong>' . esc_html( $shared['content_custom_post_types_label'] ) . ':</strong> ' . esc_html( $custom_types ) . '</li>';
                }
            }

            if ( $content_items ) {
                $html .= '<ul>' . implode( '', $content_items ) . '</ul>';
            }

            if ( ! empty( $privacy['plugin_heading'] ) ) {
                $html .= '<h3>' . esc_html( $privacy['plugin_heading'] ) . '</h3>';
            }

            if ( ! empty( $privacy['plugin_intro'] ) ) {
                $html .= '<p>' . esc_html( $privacy['plugin_intro'] ) . '</p>';
            }

            if ( ! empty( $plugins ) ) {
                $plugin_markup = array();

                foreach ( $plugins as $plugin ) {
                    $entry  = '<li><strong>' . esc_html( isset( $plugin['name'] ) ? $plugin['name'] : '' ) . '</strong>';

                    if ( ! empty( $plugin['version'] ) && ! empty( $shared['plugin_version_format'] ) ) {
                        $version_text = sprintf( $shared['plugin_version_format'], $plugin['version'] );
                        $entry       .= ' <em>' . esc_html( $version_text ) . '</em>';
                    }

                    if ( ! empty( $plugin['description'] ) ) {
                        $entry .= '<br />' . esc_html( $plugin['description'] );
                    }

                    if ( ! empty( $plugin['url'] ) && ! empty( $shared['plugins_link_label'] ) ) {
                        $entry .= '<br /><a href="' . esc_url( $plugin['url'] ) . '" target="_blank" rel="noopener noreferrer nofollow">' . esc_html( $shared['plugins_link_label'] ) . '</a>';
                    }

                    $tag_text = '';
                    if ( ! empty( $plugin['tags'] ) && ! empty( $strings['tags'] ) && is_array( $strings['tags'] ) ) {
                        $tag_text = $this->format_plugin_tags( $plugin['tags'], $strings['tags'] );
                    }

                    if ( $tag_text && ! empty( $shared['plugins_tags_format'] ) && ! empty( $shared['plugins_tags_label'] ) ) {
                        $tag_sentence = sprintf( $shared['plugins_tags_format'], $shared['plugins_tags_label'], $tag_text );
                        $entry       .= '<br /><em>' . esc_html( $tag_sentence ) . '</em>';
                    }

                    $entry          .= '</li>';
                    $plugin_markup[] = $entry;
                }

                if ( $plugin_markup ) {
                    $html .= '<ul>' . implode( '', $plugin_markup ) . '</ul>';
                }
            }

            if ( ! empty( $shared['plugins_review_hint'] ) ) {
                $html .= '<p><em>' . esc_html( $shared['plugins_review_hint'] ) . '</em></p>';
            }

            if ( ! empty( $privacy['rights'] ) ) {
                $html .= '<p><em>' . esc_html( $privacy['rights'] ) . '</em></p>';
            }

            if ( ! empty( $privacy['consent_heading'] ) ) {
                $html .= '<h3>' . esc_html( $privacy['consent_heading'] ) . '</h3>';
            }

            $retention_days = isset( $consent['retention_days'] ) ? (int) $consent['retention_days'] : 0;
            $retention_text = $retention_days > 0 ? number_format_i18n( $retention_days ) : ( isset( $shared['consent_unlimited'] ) ? $shared['consent_unlimited'] : '0' );

            if ( ! empty( $privacy['consent_description'] ) ) {
                $html .= '<p>' . sprintf( esc_html( $privacy['consent_description'] ), esc_html( self::CONSENT_COOKIE ), esc_html( $retention_text ) ) . '</p>';
            }

            return $html;
        }

        /**
         * Build the cookie policy document for a specific language.
         *
         * @param array  $snapshot  Site snapshot.
         * @param array  $changes   Detected changes.
         * @param int    $timestamp Generation timestamp.
         * @param string $language  Target language code.
         *
         * @return string
         */
        protected function generate_cookie_policy_document( array $snapshot, array $changes, $timestamp, $language ) {
            $strings        = $this->get_auto_policy_strings( $language );
            $shared         = isset( $strings['shared'] ) ? $strings['shared'] : array();
            $cookie         = isset( $strings['cookie'] ) ? $strings['cookie'] : array();
            $change_items   = $this->build_changes_list( $changes, $strings );
            $datetime_parts = $this->get_generation_datetime_parts( $timestamp );
            $categories     = isset( $snapshot['cookie_categories'] ) && is_array( $snapshot['cookie_categories'] ) ? $snapshot['cookie_categories'] : array();
            $plugins        = isset( $snapshot['plugins'] ) && is_array( $snapshot['plugins'] ) ? $snapshot['plugins'] : array();
            $consent        = isset( $snapshot['consent'] ) && is_array( $snapshot['consent'] ) ? $snapshot['consent'] : array();

            $relevant_tags  = array( 'analytics', 'marketing', 'cookie', 'form', 'social', 'ecommerce' );
            $cookie_plugins = array();

            foreach ( $plugins as $plugin_key => $plugin ) {
                $plugin_tags = isset( $plugin['tags'] ) && is_array( $plugin['tags'] ) ? $plugin['tags'] : array();

                if ( array_intersect( $plugin_tags, $relevant_tags ) ) {
                    $cookie_plugins[ $plugin_key ] = $plugin;
                }
            }

            if ( empty( $cookie_plugins ) ) {
                $cookie_plugins = $plugins;
            }

            $html = '';

            if ( ! empty( $cookie['title'] ) ) {
                $html .= '<h2>' . esc_html( $cookie['title'] ) . '</h2>';
            }

            $generated_template = isset( $shared['generated_on'] ) ? $shared['generated_on'] : '%1$s %2$s';
            $generated_text     = sprintf( $generated_template, $datetime_parts['date'], $datetime_parts['time'] );
            $html              .= '<p><em>' . esc_html( $generated_text ) . '</em></p>';

            if ( ! empty( $cookie['intro'] ) ) {
                $html .= '<p>' . esc_html( $cookie['intro'] ) . '</p>';
            }

            if ( ! empty( $change_items ) && ! empty( $strings['changes']['heading'] ) ) {
                $html .= '<h3>' . esc_html( $strings['changes']['heading'] ) . '</h3>';
                $changes_markup = array();

                foreach ( $change_items as $item ) {
                    $text            = vsprintf( $item['text'], $item['args'] );
                    $changes_markup[] = '<li>' . esc_html( $text ) . '</li>';
                }

                if ( $changes_markup ) {
                    $html .= '<ul>' . implode( '', $changes_markup ) . '</ul>';
                }
            }

            if ( ! empty( $cookie['categories_heading'] ) ) {
                $html .= '<h3>' . esc_html( $cookie['categories_heading'] ) . '</h3>';
            }

            if ( ! empty( $cookie['categories_intro'] ) ) {
                $html .= '<p>' . esc_html( $cookie['categories_intro'] ) . '</p>';
            }

            if ( ! empty( $categories ) ) {
                $category_markup = array();

                foreach ( $categories as $category ) {
                    $entry = '<li><strong>' . esc_html( isset( $category['label'] ) ? $category['label'] : '' ) . '</strong>';

                    if ( ! empty( $category['description'] ) ) {
                        $entry .= '<br />' . esc_html( $category['description'] );
                    }

                    if ( ! empty( $category['services'] ) && ! empty( $cookie['category_services_label'] ) ) {
                        $entry .= '<br /><em>' . esc_html( $cookie['category_services_label'] ) . ':</em> ' . wp_kses( nl2br( esc_html( $category['services'] ) ), array( 'br' => array() ) );
                    }

                    if ( ! empty( $category['required'] ) && ! empty( $cookie['category_required_label'] ) ) {
                        $entry .= '<br /><em>' . esc_html( $cookie['category_required_label'] ) . '</em>';
                    }

                    $entry             .= '</li>';
                    $category_markup[] = $entry;
                }

                if ( $category_markup ) {
                    $html .= '<ul>' . implode( '', $category_markup ) . '</ul>';
                }
            }

            if ( ! empty( $cookie['plugin_heading'] ) ) {
                $html .= '<h3>' . esc_html( $cookie['plugin_heading'] ) . '</h3>';
            }

            if ( ! empty( $cookie['plugin_intro'] ) ) {
                $html .= '<p>' . esc_html( $cookie['plugin_intro'] ) . '</p>';
            }

            if ( ! empty( $cookie_plugins ) ) {
                $plugin_markup = array();

                foreach ( $cookie_plugins as $plugin ) {
                    $entry = '<li><strong>' . esc_html( isset( $plugin['name'] ) ? $plugin['name'] : '' ) . '</strong>';

                    if ( ! empty( $plugin['description'] ) ) {
                        $entry .= '<br />' . esc_html( $plugin['description'] );
                    }

                    $tag_text = '';
                    if ( ! empty( $plugin['tags'] ) && ! empty( $strings['tags'] ) && is_array( $strings['tags'] ) ) {
                        $tag_text = $this->format_plugin_tags( $plugin['tags'], $strings['tags'] );
                    }

                    if ( $tag_text && ! empty( $shared['plugins_tags_format'] ) && ! empty( $shared['plugins_tags_label'] ) ) {
                        $tag_sentence = sprintf( $shared['plugins_tags_format'], $shared['plugins_tags_label'], $tag_text );
                        $entry       .= '<br /><em>' . esc_html( $tag_sentence ) . '</em>';
                    }

                    $entry          .= '</li>';
                    $plugin_markup[] = $entry;
                }

                if ( $plugin_markup ) {
                    $html .= '<ul>' . implode( '', $plugin_markup ) . '</ul>';
                }
            }

            if ( ! empty( $shared['plugins_review_hint'] ) ) {
                $html .= '<p><em>' . esc_html( $shared['plugins_review_hint'] ) . '</em></p>';
            }

            if ( ! empty( $cookie['consent_heading'] ) ) {
                $html .= '<h3>' . esc_html( $cookie['consent_heading'] ) . '</h3>';
            }

            $cookie_days      = isset( $consent['cookie_days'] ) ? (int) $consent['cookie_days'] : 0;
            $cookie_days_text = $cookie_days > 0 ? number_format_i18n( $cookie_days ) : ( isset( $shared['cookie_duration_unlimited'] ) ? $shared['cookie_duration_unlimited'] : '0' );

            if ( ! empty( $cookie['consent_description'] ) ) {
                $html .= '<p>' . sprintf( esc_html( $cookie['consent_description'] ), esc_html( self::CONSENT_COOKIE ), esc_html( $cookie_days_text ) ) . '</p>';
            }

            return $html;
        }

        /**
         * Build the list of textual changes to display in the generated documents.
         *
         * @param array $changes Detected changes.
         * @param array $strings Localised strings.
         *
         * @return array<int, array{text:string,args:array<int,string>}>
         */
        protected function build_changes_list( array $changes, array $strings ) {
            $items          = array();
            $change_strings = isset( $strings['changes'] ) && is_array( $strings['changes'] ) ? $strings['changes'] : array();

            if ( empty( $changes ) || empty( $change_strings ) ) {
                return $items;
            }

            if ( ! empty( $changes['plugins_added'] ) && ! empty( $change_strings['plugins_added'] ) ) {
                $names = $this->extract_plugin_names( $changes['plugins_added'] );
                if ( ! empty( $names ) ) {
                    $items[] = array(
                        'text' => $change_strings['plugins_added'],
                        'args' => array( implode( ', ', $names ) ),
                    );
                }
            }

            if ( ! empty( $changes['plugins_removed'] ) && ! empty( $change_strings['plugins_removed'] ) ) {
                $names = $this->extract_plugin_names( $changes['plugins_removed'] );
                if ( ! empty( $names ) ) {
                    $items[] = array(
                        'text' => $change_strings['plugins_removed'],
                        'args' => array( implode( ', ', $names ) ),
                    );
                }
            }

            if ( ! empty( $changes['theme'] ) && ! empty( $change_strings['theme'] ) ) {
                $previous_theme = isset( $changes['theme']['previous'] ) && is_array( $changes['theme']['previous'] ) ? $changes['theme']['previous'] : array();
                $current_theme  = isset( $changes['theme']['current'] ) && is_array( $changes['theme']['current'] ) ? $changes['theme']['current'] : array();

                $items[] = array(
                    'text' => $change_strings['theme'],
                    'args' => array(
                        $this->format_theme_name( $previous_theme ),
                        $this->format_theme_name( $current_theme ),
                    ),
                );
            }

            if ( ! empty( $changes['custom_post_types_added'] ) && ! empty( $change_strings['post_types_added'] ) ) {
                $items[] = array(
                    'text' => $change_strings['post_types_added'],
                    'args' => array( implode( ', ', array_map( 'sanitize_text_field', $changes['custom_post_types_added'] ) ) ),
                );
            }

            if ( ! empty( $changes['custom_post_types_removed'] ) && ! empty( $change_strings['post_types_removed'] ) ) {
                $items[] = array(
                    'text' => $change_strings['post_types_removed'],
                    'args' => array( implode( ', ', array_map( 'sanitize_text_field', $changes['custom_post_types_removed'] ) ) ),
                );
            }

            if ( ! empty( $changes['counts'] ) && ! empty( $change_strings['counts'] ) ) {
                $labels = isset( $strings['shared']['content_labels'] ) && is_array( $strings['shared']['content_labels'] ) ? $strings['shared']['content_labels'] : array();

                foreach ( $changes['counts'] as $key => $data ) {
                    $label = isset( $labels[ $key ] ) ? $labels[ $key ] : ucfirst( (string) $key );
                    $prev  = isset( $data['previous'] ) ? number_format_i18n( (int) $data['previous'] ) : '0';
                    $curr  = isset( $data['current'] ) ? number_format_i18n( (int) $data['current'] ) : '0';

                    $items[] = array(
                        'text' => $change_strings['counts'],
                        'args' => array( $label, $prev, $curr ),
                    );
                }
            }

            if ( ! empty( $changes['consent'] ) ) {
                if ( ! empty( $changes['consent']['cookie_days'] ) && ! empty( $change_strings['consent_cookie'] ) ) {
                    $items[] = array(
                        'text' => $change_strings['consent_cookie'],
                        'args' => array(
                            number_format_i18n( (int) $changes['consent']['cookie_days']['previous'] ),
                            number_format_i18n( (int) $changes['consent']['cookie_days']['current'] ),
                        ),
                    );
                }

                if ( ! empty( $changes['consent']['retention_days'] ) && ! empty( $change_strings['consent_retention'] ) ) {
                    $items[] = array(
                        'text' => $change_strings['consent_retention'],
                        'args' => array(
                            number_format_i18n( (int) $changes['consent']['retention_days']['previous'] ),
                            number_format_i18n( (int) $changes['consent']['retention_days']['current'] ),
                        ),
                    );
                }
            }

            return $items;
        }

        /**
         * Extract plugin names from the given entries.
         *
         * @param array $plugins Plugin entries.
         *
         * @return array
         */
        protected function extract_plugin_names( array $plugins ) {
            $names = array();

            foreach ( $plugins as $plugin ) {
                if ( isset( $plugin['name'] ) ) {
                    $names[] = sanitize_text_field( $plugin['name'] );
                }
            }

            return $names;
        }

        /**
         * Format a theme label with version information when available.
         *
         * @param array $theme Theme data.
         *
         * @return string
         */
        protected function format_theme_name( array $theme ) {
            $name    = isset( $theme['name'] ) ? sanitize_text_field( $theme['name'] ) : '';
            $version = isset( $theme['version'] ) ? sanitize_text_field( $theme['version'] ) : '';

            if ( $name && $version ) {
                return sprintf( '%1$s (%2$s)', $name, $version );
            }

            if ( $name ) {
                return $name;
            }

            if ( $version ) {
                return $version;
            }

            return '';
        }

        /**
         * Build a snapshot of the current site configuration.
         *
         * @param array|null $settings Optional plugin settings.
         *
         * @return array
         */
        protected function get_site_snapshot( ?array $settings = null ) {
            if ( null === $settings ) {
                $settings = $this->get_settings();
            }

            $site = array(
                'name'        => get_bloginfo( 'name' ),
                'description' => get_bloginfo( 'description' ),
                'url'         => home_url(),
                'admin_email' => get_bloginfo( 'admin_email' ),
                'language'    => get_bloginfo( 'language' ),
                'timezone'    => get_option( 'timezone_string', get_option( 'gmt_offset', 'UTC' ) ),
            );

            $theme      = wp_get_theme();
            $parent     = $theme->parent();
            $theme_data = array(
                'name'       => $theme->get( 'Name' ),
                'version'    => $theme->get( 'Version' ),
                'stylesheet' => $theme->get_stylesheet(),
                'template'   => $theme->get_template(),
                'parent'     => $parent ? $parent->get( 'Name' ) : '',
                'parent_ver' => $parent ? $parent->get( 'Version' ) : '',
            );

            $post_counts    = wp_count_posts( 'post' );
            $page_counts    = wp_count_posts( 'page' );
            $comment_counts = wp_count_comments();
            $user_totals    = count_users();
            $custom_post_map = array();
            $custom_posts    = get_post_types(
                array(
                    'public'   => true,
                    '_builtin' => false,
                ),
                'objects'
            );

            foreach ( $custom_posts as $post_type ) {
                if ( class_exists( 'WP_Post_Type' ) && $post_type instanceof WP_Post_Type ) {
                    $label = $post_type->labels && ! empty( $post_type->labels->name ) ? $post_type->labels->name : $post_type->name;
                    $custom_post_map[ $post_type->name ] = $label;
                }
            }

            ksort( $custom_post_map );

            $content = array(
                'counts' => array(
                    'posts'    => isset( $post_counts->publish ) ? (int) $post_counts->publish : 0,
                    'pages'    => isset( $page_counts->publish ) ? (int) $page_counts->publish : 0,
                    'comments' => isset( $comment_counts['approved'] ) ? (int) $comment_counts['approved'] : 0,
                    'users'    => isset( $user_totals['total_users'] ) ? (int) $user_totals['total_users'] : 0,
                ),
                'custom_post_types' => $custom_post_map,
            );

            $categories = array();

            if ( isset( $settings['categories'] ) && is_array( $settings['categories'] ) ) {
                foreach ( $settings['categories'] as $key => $category ) {
                    $categories[ $key ] = array(
                        'label'       => isset( $category['label'] ) ? sanitize_text_field( $category['label'] ) : '',
                        'description' => isset( $category['description'] ) ? wp_strip_all_tags( $category['description'] ) : '',
                        'services'    => isset( $category['services'] ) ? sanitize_textarea_field( $category['services'] ) : '',
                        'required'    => ! empty( $category['required'] ),
                        'enabled'     => ! empty( $category['enabled'] ),
                    );
                }

                ksort( $categories );
            }

            $consent = array(
                'cookie_days'    => isset( $settings['consent_cookie_days'] ) ? (int) $settings['consent_cookie_days'] : 0,
                'retention_days' => isset( $settings['retention_days'] ) ? (int) $settings['retention_days'] : 0,
            );

            $snapshot = array(
                'site'              => $site,
                'theme'             => $theme_data,
                'content'           => $content,
                'plugins'           => $this->gather_active_plugins(),
                'cookie_categories' => $categories,
                'consent'           => $consent,
            );

            return apply_filters( 'fp_privacy_site_snapshot', $snapshot, $settings );
        }

        /**
         * Determine the differences between two snapshots.
         *
         * @param array $previous Previous snapshot.
         * @param array $current  Current snapshot.
         *
         * @return array
         */
        protected function diff_snapshots( array $previous, array $current ) {
            $changes          = array();
            $previous_plugins = isset( $previous['plugins'] ) && is_array( $previous['plugins'] ) ? $previous['plugins'] : array();
            $current_plugins  = isset( $current['plugins'] ) && is_array( $current['plugins'] ) ? $current['plugins'] : array();

            $added_plugins = array_diff_key( $current_plugins, $previous_plugins );
            if ( ! empty( $added_plugins ) ) {
                $changes['plugins_added'] = array_values( $added_plugins );
            }

            $removed_plugins = array_diff_key( $previous_plugins, $current_plugins );
            if ( ! empty( $removed_plugins ) ) {
                $changes['plugins_removed'] = array_values( $removed_plugins );
            }

            $previous_theme = isset( $previous['theme'] ) && is_array( $previous['theme'] ) ? $previous['theme'] : array();
            $current_theme  = isset( $current['theme'] ) && is_array( $current['theme'] ) ? $current['theme'] : array();

            if ( $previous_theme !== $current_theme && ( ! empty( $previous_theme ) || ! empty( $current_theme ) ) ) {
                $changes['theme'] = array(
                    'previous' => $previous_theme,
                    'current'  => $current_theme,
                );
            }

            $previous_cpts = isset( $previous['content']['custom_post_types'] ) && is_array( $previous['content']['custom_post_types'] ) ? $previous['content']['custom_post_types'] : array();
            $current_cpts  = isset( $current['content']['custom_post_types'] ) && is_array( $current['content']['custom_post_types'] ) ? $current['content']['custom_post_types'] : array();

            $added_cpts = array_diff_key( $current_cpts, $previous_cpts );
            if ( ! empty( $added_cpts ) ) {
                $changes['custom_post_types_added'] = array_values( $added_cpts );
            }

            $removed_cpts = array_diff_key( $previous_cpts, $current_cpts );
            if ( ! empty( $removed_cpts ) ) {
                $changes['custom_post_types_removed'] = array_values( $removed_cpts );
            }

            $previous_counts = isset( $previous['content']['counts'] ) && is_array( $previous['content']['counts'] ) ? $previous['content']['counts'] : array();
            $current_counts  = isset( $current['content']['counts'] ) && is_array( $current['content']['counts'] ) ? $current['content']['counts'] : array();

            foreach ( $current_counts as $key => $value ) {
                $prev = isset( $previous_counts[ $key ] ) ? (int) $previous_counts[ $key ] : 0;
                $curr = (int) $value;

                if ( $prev !== $curr ) {
                    if ( ! isset( $changes['counts'] ) ) {
                        $changes['counts'] = array();
                    }

                    $changes['counts'][ $key ] = array(
                        'previous' => $prev,
                        'current'  => $curr,
                    );
                }
            }

            $previous_consent = isset( $previous['consent'] ) && is_array( $previous['consent'] ) ? $previous['consent'] : array();
            $current_consent  = isset( $current['consent'] ) && is_array( $current['consent'] ) ? $current['consent'] : array();

            foreach ( array( 'cookie_days', 'retention_days' ) as $key ) {
                $prev = isset( $previous_consent[ $key ] ) ? (int) $previous_consent[ $key ] : 0;
                $curr = isset( $current_consent[ $key ] ) ? (int) $current_consent[ $key ] : 0;

                if ( $prev !== $curr ) {
                    if ( ! isset( $changes['consent'] ) ) {
                        $changes['consent'] = array();
                    }

                    $changes['consent'][ $key ] = array(
                        'previous' => $prev,
                        'current'  => $curr,
                    );
                }
            }

            return $changes;
        }

        /**
         * Gather metadata about active plugins.
         *
         * @return array
         */
        protected function gather_active_plugins() {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $active_plugins = (array) get_option( 'active_plugins', array() );

            if ( is_multisite() ) {
                $network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
                $active_plugins  = array_merge( $active_plugins, array_keys( $network_plugins ) );
            }

            $active_plugins = array_unique( $active_plugins );
            sort( $active_plugins );

            $plugins = array();

            foreach ( $active_plugins as $plugin_file ) {
                $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

                if ( ! file_exists( $plugin_path ) ) {
                    continue;
                }

                $data = get_plugin_data( $plugin_path, false, false );

                $plugins[ $plugin_file ] = array(
                    'name'        => isset( $data['Name'] ) ? $data['Name'] : $plugin_file,
                    'version'     => isset( $data['Version'] ) ? $data['Version'] : '',
                    'description' => isset( $data['Description'] ) ? wp_strip_all_tags( $data['Description'] ) : '',
                    'url'         => isset( $data['PluginURI'] ) ? $data['PluginURI'] : '',
                    'tags'        => $this->classify_plugin_tags( $data ),
                );
            }

            return apply_filters( 'fp_privacy_detected_plugins', $plugins );
        }

        /**
         * Heuristically classify plugins to help contextualise their role in the legal documents.
         *
         * @param array $plugin_data Plugin metadata returned by get_plugin_data().
         *
         * @return array
         */
        protected function classify_plugin_tags( array $plugin_data ) {
            $text = strtolower( implode( ' ', array(
                isset( $plugin_data['Name'] ) ? $plugin_data['Name'] : '',
                isset( $plugin_data['Description'] ) ? $plugin_data['Description'] : '',
            ) ) );

            $map = array(
                'analytics'   => array( 'analytics', 'statistic', 'tracking', 'matomo', 'ga4', 'gtag', 'measure' ),
                'marketing'   => array( 'ads', 'advert', 'remarketing', 'pixel', 'conversion', 'campaign', 'adsense', 'facebook', 'meta', 'tiktok' ),
                'form'        => array( 'form', 'newsletter', 'mail', 'contact', 'gravity forms', 'mailchimp', 'survey' ),
                'security'    => array( 'security', 'captcha', 'antispam', 'firewall', 'protection' ),
                'ecommerce'   => array( 'commerce', 'woocommerce', 'shop', 'store', 'cart', 'checkout', 'payment' ),
                'social'      => array( 'social', 'share', 'instagram', 'facebook', 'twitter', 'linkedin', 'whatsapp' ),
                'performance' => array( 'cache', 'performance', 'speed', 'optimize', 'cdn' ),
                'cookie'      => array( 'cookie', 'consent', 'gdpr' ),
            );

            $detected = array();

            foreach ( $map as $tag => $keywords ) {
                foreach ( $keywords as $keyword ) {
                    if ( false !== strpos( $text, $keyword ) ) {
                        $detected[] = $tag;
                        break;
                    }
                }
            }

            $detected = array_values( array_unique( $detected ) );

            return apply_filters( 'fp_privacy_detected_plugin_tags', $detected, $plugin_data );
        }

        /**
         * Format plugin tag labels using the provided dictionary.
         *
         * @param array $tags   Detected tags.
         * @param array $labels Tag labels keyed by tag name.
         *
         * @return string
         */
        protected function format_plugin_tags( array $tags, array $labels ) {
            $resolved = array();

            foreach ( $tags as $tag ) {
                if ( isset( $labels[ $tag ] ) ) {
                    $resolved[] = $labels[ $tag ];
                } else {
                    $resolved[] = ucfirst( sanitize_text_field( $tag ) );
                }
            }

            $resolved = array_unique( array_filter( $resolved ) );

            return implode( ', ', $resolved );
        }

        /**
         * Retrieve the dictionary of auto-generated policy strings.
         *
         * @param string $language Target language code.
         *
         * @return array
         */
        protected function get_auto_policy_strings( $language ) {
            $language = $this->normalize_language_code( $language );

            $strings = array(
                'it' => array(
                    'shared'  => array(
                        'generated_on'                 => 'Documento aggiornato automaticamente il %1$s alle %2$s.',
                        'controller_heading'           => 'Titolare del trattamento',
                        'controller_intro'             => 'Il sistema ha ricavato automaticamente le seguenti informazioni dal sito WordPress.',
                        'controller_labels'            => array(
                            'name'        => 'Denominazione',
                            'url'         => 'Sito web',
                            'email'       => 'Email di contatto',
                            'description' => 'Descrizione',
                            'language'    => 'Lingua principale',
                            'timezone'    => 'Fuso orario',
                        ),
                        'theme_heading'                => 'Tema attivo e infrastruttura',
                        'theme_active'                 => 'Tema attivo: %1$s',
                        'theme_version'                => 'Versione: %1$s',
                        'theme_child_of'               => 'Tema child di %1$s',
                        'content_heading'              => 'Struttura dei contenuti',
                        'content_intro'                => 'Panoramica dei contenuti pubblicati che potrebbero comportare il trattamento di dati personali.',
                        'content_labels'               => array(
                            'pages'    => 'Pagine pubblicate',
                            'posts'    => 'Articoli pubblicati',
                            'comments' => 'Commenti approvati',
                            'users'    => 'Utenti registrati',
                        ),
                        'content_custom_post_types_label' => 'Tipologie di contenuto personalizzate',
                        'plugins_link_label'            => 'Documentazione ufficiale',
                        'plugins_tags_label'            => 'Ambiti interessati',
                        'plugins_tags_format'           => '%1$s: %2$s',
                        'plugin_version_format'         => 'Versione %s',
                        'plugins_review_hint'           => 'Verifica con il tuo consulente legale le finalità e le basi giuridiche di ciascun componente.',
                        'consent_unlimited'             => 'non impostato (nessuna cancellazione automatica)',
                        'cookie_duration_unlimited'     => 'fino alla cancellazione manuale',
                    ),
                    'changes' => array(
                        'heading'           => 'Ultime modifiche rilevate',
                        'plugins_added'     => 'Nuovi plugin attivati: %s.',
                        'plugins_removed'   => 'Plugin disattivati o rimossi: %s.',
                        'theme'             => 'Tema aggiornato da %1$s a %2$s.',
                        'counts'            => 'Aggiornamento %1$s: da %2$s a %3$s.',
                        'post_types_added'  => 'Nuove tipologie di contenuto: %s.',
                        'post_types_removed'=> 'Tipologie di contenuto rimosse: %s.',
                        'consent_cookie'    => 'Durata del cookie di consenso aggiornata da %1$s giorni a %2$s giorni.',
                        'consent_retention' => 'Periodo di conservazione del registro consensi aggiornato da %1$s giorni a %2$s giorni.',
                    ),
                    'privacy' => array(
                        'title'               => 'Informativa Privacy automatica',
                        'data_intro'          => 'Questa informativa viene generata automaticamente analizzando la configurazione del sito WordPress e i componenti attivi.',
                        'plugin_heading'      => 'Plugin e servizi che richiedono attenzione',
                        'plugin_intro'        => 'Sono stati rilevati i seguenti plugin che potrebbero comportare il trattamento di dati personali o il trasferimento di dati verso terze parti.',
                        'rights'              => 'Ricorda che gli interessati possono esercitare i diritti previsti dagli articoli 15-22 GDPR contattando il titolare tramite i recapiti indicati.',
                        'consent_heading'     => 'Registro dei consensi e tracciamento interno',
                        'consent_description' => 'Il plugin memorizza le preferenze nel cookie tecnico %1$s e conserva gli eventi nel database per %2$s giorni, con indirizzo IP anonimizzato e user agent.',
                    ),
                    'cookie'  => array(
                        'title'                    => 'Cookie Policy automatica',
                        'intro'                    => 'Il documento elenca le categorie di cookie utilizzate dal sito e i servizi collegati così come risultano dall\'ultima scansione automatica.',
                        'categories_heading'       => 'Categorie di cookie configurate',
                        'categories_intro'         => 'Ogni categoria può includere servizi di terze parti. Aggiorna le descrizioni se integri nuovi strumenti.',
                        'category_services_label'  => 'Servizi e cookie censiti',
                        'category_required_label'  => 'Categoria sempre attiva',
                        'plugin_heading'           => 'Plugin che potrebbero installare cookie',
                        'plugin_intro'             => 'Analizzando i plugin attivi sono state individuate le seguenti integrazioni potenzialmente rilevanti per la cookie policy.',
                        'consent_heading'          => 'Gestione del consenso',
                        'consent_description'      => 'Le preferenze vengono archiviate nel cookie tecnico %1$s per %2$s giorni. Puoi riaprire il banner in qualsiasi momento tramite il pulsante di gestione delle preferenze.',
                    ),
                    'tags'    => array(
                        'analytics'   => 'analisi e misurazione',
                        'marketing'   => 'marketing e advertising',
                        'form'        => 'moduli e raccolta contatti',
                        'security'    => 'sicurezza e protezione',
                        'ecommerce'   => 'e-commerce e pagamenti',
                        'social'      => 'integrazioni social',
                        'performance' => 'prestazioni e cache',
                        'cookie'      => 'gestione cookie e consenso',
                    ),
                ),
                'en' => array(
                    'shared'  => array(
                        'generated_on'                 => 'Document automatically updated on %1$s at %2$s.',
                        'controller_heading'           => 'Data controller',
                        'controller_intro'             => 'The system automatically retrieved the following WordPress configuration details.',
                        'controller_labels'            => array(
                            'name'        => 'Organisation',
                            'url'         => 'Website',
                            'email'       => 'Contact email',
                            'description' => 'Public description',
                            'language'    => 'Primary language',
                            'timezone'    => 'Timezone',
                        ),
                        'theme_heading'                => 'Theme and technical stack',
                        'theme_active'                 => 'Active theme: %1$s',
                        'theme_version'                => 'Version: %1$s',
                        'theme_child_of'               => 'Child theme of %1$s',
                        'content_heading'              => 'Content overview',
                        'content_intro'                => 'Overview of the published content that may involve personal data processing.',
                        'content_labels'               => array(
                            'pages'    => 'Published pages',
                            'posts'    => 'Published posts',
                            'comments' => 'Approved comments',
                            'users'    => 'Registered users',
                        ),
                        'content_custom_post_types_label' => 'Custom post types',
                        'plugins_link_label'            => 'Official documentation',
                        'plugins_tags_label'            => 'Impacted areas',
                        'plugins_tags_format'           => '%1$s: %2$s',
                        'plugin_version_format'         => 'Version %s',
                        'plugins_review_hint'           => 'Review each integration with your legal advisor to document purposes and lawful bases.',
                        'consent_unlimited'             => 'not configured (no automatic cleanup)',
                        'cookie_duration_unlimited'     => 'until manually cleared',
                    ),
                    'changes' => array(
                        'heading'           => 'Latest detected changes',
                        'plugins_added'     => 'New plugins activated: %s.',
                        'plugins_removed'   => 'Plugins deactivated or removed: %s.',
                        'theme'             => 'Theme updated from %1$s to %2$s.',
                        'counts'            => '%1$s updated from %2$s to %3$s.',
                        'post_types_added'  => 'New custom post types: %s.',
                        'post_types_removed'=> 'Removed custom post types: %s.',
                        'consent_cookie'    => 'Consent cookie duration changed from %1$s days to %2$s days.',
                        'consent_retention' => 'Consent log retention period changed from %1$s days to %2$s days.',
                    ),
                    'privacy' => array(
                        'title'               => 'Automated Privacy Notice',
                        'data_intro'          => 'This notice is generated automatically by analysing the active WordPress components.',
                        'plugin_heading'      => 'Detected plugins and third-party services',
                        'plugin_intro'        => 'The following plugins may process personal data or transfer information to third parties.',
                        'rights'              => 'Data subjects can exercise their GDPR rights (articles 15-22) by contacting the controller through the channels listed above.',
                        'consent_heading'     => 'Consent log and technical storage',
                        'consent_description' => 'The plugin stores preferences inside the technical cookie %1$s and keeps consent events in the database for %2$s days, including an anonymised IP address and the user agent.',
                    ),
                    'cookie'  => array(
                        'title'                    => 'Automated Cookie Policy',
                        'intro'                    => 'This document lists the configured cookie categories and related services based on the latest automatic scan.',
                        'categories_heading'       => 'Configured cookie categories',
                        'categories_intro'         => 'Each category may include third-party services. Keep descriptions up to date when onboarding new tools.',
                        'category_services_label'  => 'Services and cookies detected',
                        'category_required_label'  => 'Always active category',
                        'plugin_heading'           => 'Plugins that may set cookies',
                        'plugin_intro'             => 'The following active plugins may interact with cookies or tracking technologies.',
                        'consent_heading'          => 'Consent management',
                        'consent_description'      => 'Preferences are stored inside the technical cookie %1$s for %2$s days. Visitors can reopen the banner at any time through the manage preferences button.',
                    ),
                    'tags'    => array(
                        'analytics'   => 'analytics and measurement',
                        'marketing'   => 'marketing and advertising',
                        'form'        => 'forms and lead capture',
                        'security'    => 'security and protection',
                        'ecommerce'   => 'e-commerce and payments',
                        'social'      => 'social integrations',
                        'performance' => 'performance and caching',
                        'cookie'      => 'cookie and consent management',
                    ),
                ),
            );

            if ( isset( $strings[ $language ] ) ) {
                return $strings[ $language ];
            }

            return $strings[ self::DEFAULT_LANGUAGE ];
        }

        /**
         * Sanitize the generated HTML, keeping only a curated set of tags.
         *
         * @param string $html Raw HTML.
         *
         * @return string
         */
        protected function sanitize_generated_html( $html ) {
            $allowed_tags = array(
                'h2' => array(),
                'h3' => array(),
                'p'  => array(),
                'em' => array(),
                'strong' => array(),
                'ul' => array(),
                'li' => array(),
                'a'  => array(
                    'href'   => array(),
                    'target' => array(),
                    'rel'    => array(),
                ),
                'br' => array(),
            );

            return wp_kses( $html, $allowed_tags );
        }

        /**
         * Format the generation timestamp according to the site settings.
         *
         * @param int $timestamp Timestamp to format.
         *
         * @return array{date:string,time:string}
         */
        protected function get_generation_datetime_parts( $timestamp ) {
            $date_format = get_option( 'date_format', 'Y-m-d' );
            $time_format = get_option( 'time_format', 'H:i' );

            return array(
                'date' => wp_date( $date_format, $timestamp ),
                'time' => wp_date( $time_format, $timestamp ),
            );
        }

        /**
         * Format timezone values for display.
         *
         * @param string $timezone Timezone string or offset.
         *
         * @return string
         */
        protected function format_timezone_value( $timezone ) {
            if ( is_numeric( $timezone ) ) {
                $offset = (float) $timezone;
                $sign   = $offset >= 0 ? '+' : '';

                return 'UTC' . $sign . $offset;
            }

            return (string) $timezone;
        }

        /**
         * Ensure the dedicated privacy and cookie policy pages exist.
         */
        protected function ensure_policy_pages_exist() {
            if ( ! post_type_exists( 'page' ) ) {
                return;
            }

            $privacy_page_id = (int) get_option( self::PRIVACY_PAGE_OPTION, 0 );
            $cookie_page_id  = (int) get_option( self::COOKIE_PAGE_OPTION, 0 );

            $privacy_page_id = $this->ensure_single_policy_page( 'privacy', $privacy_page_id );
            $cookie_page_id  = $this->ensure_single_policy_page( 'cookie', $cookie_page_id );

            if ( $privacy_page_id ) {
                update_option( self::PRIVACY_PAGE_OPTION, $privacy_page_id );
            }

            if ( $cookie_page_id ) {
                update_option( self::COOKIE_PAGE_OPTION, $cookie_page_id );
            }
        }

        /**
         * Create or update a single policy page with the correct shortcode.
         *
         * @param string $type    Policy type (privacy|cookie).
         * @param int    $page_id Existing page identifier.
         *
         * @return int Page identifier.
         */
        protected function ensure_single_policy_page( $type, $page_id = 0 ) {
            $type      = 'cookie' === $type ? 'cookie' : 'privacy';
            $shortcode = 'cookie' === $type ? '[fp_cookie_policy]' : '[fp_privacy_policy]';

            if ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
                $content = get_post_field( 'post_content', $page_id );

                if ( false === strpos( (string) $content, $shortcode ) ) {
                    wp_update_post(
                        array(
                            'ID'           => $page_id,
                            'post_content' => $shortcode,
                        )
                    );
                }

                update_post_meta( $page_id, self::POLICY_PAGE_META_KEY, $type );

                return $page_id;
            }

            $existing = get_posts(
                array(
                    'post_type'      => 'page',
                    'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
                    'posts_per_page' => 1,
                    'meta_key'       => self::POLICY_PAGE_META_KEY,
                    'meta_value'     => $type,
                    'fields'         => 'ids',
                )
            );

            if ( $existing ) {
                $page_id = (int) $existing[0];

                if ( 'trash' !== get_post_status( $page_id ) ) {
                    $content = get_post_field( 'post_content', $page_id );

                    if ( false === strpos( (string) $content, $shortcode ) ) {
                        wp_update_post(
                            array(
                                'ID'           => $page_id,
                                'post_content' => $shortcode,
                            )
                        );
                    }

                    update_post_meta( $page_id, self::POLICY_PAGE_META_KEY, $type );

                    return $page_id;
                }
            }

            $strings = $this->get_auto_policy_strings( self::DEFAULT_LANGUAGE );
            $title   = 'cookie' === $type
                ? ( isset( $strings['cookie']['title'] ) ? $strings['cookie']['title'] : 'Cookie Policy' )
                : ( isset( $strings['privacy']['title'] ) ? $strings['privacy']['title'] : 'Privacy Policy' );

            $page_id = wp_insert_post(
                array(
                    'post_title'   => sanitize_text_field( $title ),
                    'post_content' => $shortcode,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ),
                true
            );

            if ( is_wp_error( $page_id ) ) {
                return 0;
            }

            update_post_meta( $page_id, self::POLICY_PAGE_META_KEY, $type );

            return (int) $page_id;
        }

        /**
         * Retrieve the plugin basename.
         *
         * @return string
         */
        protected static function get_plugin_basename() {
            return plugin_basename( __FILE__ );
        }

        /**
         * Determine whether the plugin is network-activated.
         *
         * @return bool
         */
        protected static function is_network_active() {
            if ( ! is_multisite() ) {
                return false;
            }

            $active = (array) get_site_option( 'active_sitewide_plugins', array() );

            return isset( $active[ self::get_plugin_basename() ] );
        }

        /**
         * Add quick links to the plugin entry inside the plugins list.
         *
         * @param string[] $links Existing action links.
         *
         * @return string[]
         */
        public function add_plugin_action_links( $links ) {
            $settings_link = sprintf(
                '<a href="%s">%s</a>',
                esc_url( admin_url( 'admin.php?page=fp-privacy-cookie-policy' ) ),
                esc_html__( 'Settings', 'fp-privacy-cookie-policy' )
            );

            array_unshift( $links, $settings_link );

            return $links;
        }

        /**
         * Load textdomain.
         */
        public function load_textdomain() {
            $domain        = 'fp-privacy-cookie-policy';
            $languages_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

            if ( load_plugin_textdomain( $domain, false, $languages_dir ) ) {
                return;
            }

            $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
            $po_file = plugin_dir_path( __FILE__ ) . 'languages/' . $domain . '-' . $locale . '.po';

            if ( ! is_readable( $po_file ) ) {
                return;
            }

            if ( ! class_exists( 'PO', false ) ) {
                require_once ABSPATH . WPINC . '/pomo/po.php';
            }

            $po = new PO();

            if ( ! $po->import_from_file( $po_file ) ) {
                return;
            }

            if ( isset( $GLOBALS['l10n'][ $domain ] ) ) {
                $po->merge_with( $GLOBALS['l10n'][ $domain ] );
            }

            $GLOBALS['l10n'][ $domain ] = $po;

            if ( isset( $GLOBALS['l10n_unloaded'][ $domain ] ) ) {
                unset( $GLOBALS['l10n_unloaded'][ $domain ] );
            }
        }

        /**
         * Register activation hook.
         */
        public static function activate( $network_wide = false ) {
            self::run_for_each_site( $network_wide, array( __CLASS__, 'activate_single_site' ) );
        }

        /**
         * Execute activation tasks for a single site.
         *
         * @param int|null $site_id Optional site identifier.
         */
        protected static function activate_single_site( $site_id = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            self::create_consent_table();
            self::schedule_cleanup_event();
            update_option( self::VERSION_OPTION, self::VERSION );

            $instance = self::instance();

            if ( $instance instanceof self ) {
                $instance->ensure_policy_pages_exist();
                $instance->maybe_generate_legal_documents( true );
            }
        }

        /**
         * Create consent logs table.
         */
        public static function create_consent_table() {
            global $wpdb;

            $table_name      = self::get_consent_table_name();
            $charset_collate = $wpdb->get_charset_collate();

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $sql = "CREATE TABLE {$table_name} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                consent_id VARCHAR(64) NOT NULL,
                user_id BIGINT UNSIGNED DEFAULT NULL,
                event_type VARCHAR(20) NOT NULL,
                consent_state LONGTEXT NOT NULL,
                ip_address VARCHAR(100) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY consent_id (consent_id)
            ) {$charset_collate};";

            dbDelta( $sql );
        }

        /**
         * Register deactivation hook.
         */
        public static function deactivate( $network_wide = false ) {
            self::run_for_each_site( $network_wide, array( __CLASS__, 'deactivate_single_site' ) );
        }

        /**
         * Execute deactivation tasks for a single site.
         *
         * @param int|null $site_id Optional site identifier.
         */
        protected static function deactivate_single_site( $site_id = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            wp_clear_scheduled_hook( self::CLEANUP_HOOK );
        }

        /**
         * Register uninstall hook.
         */
        public static function uninstall() {
            self::run_for_each_site( true, array( __CLASS__, 'uninstall_single_site' ) );
        }

        /**
         * Execute uninstall tasks for a single site.
         *
         * @param int|null $site_id Optional site identifier.
         */
        protected static function uninstall_single_site( $site_id = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            delete_option( self::OPTION_KEY );
            delete_option( self::VERSION_OPTION );
            wp_clear_scheduled_hook( self::CLEANUP_HOOK );

            global $wpdb;

            $table_name = self::get_consent_table_name();
            $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        }

        /**
         * Execute a callback for each site when running in multisite environments.
         *
         * @param bool     $network_wide Whether the current operation targets the entire network.
         * @param callable $callback     Callback executed for each site.
         */
        protected static function run_for_each_site( $network_wide, $callback ) {
            if ( ! is_multisite() || ! $network_wide ) {
                call_user_func( $callback, get_current_blog_id() );

                return;
            }

            $site_ids = get_sites(
                array(
                    'fields' => 'ids',
                    'number' => 0,
                )
            );

            if ( empty( $site_ids ) ) {
                return;
            }

            foreach ( $site_ids as $site_id ) {
                self::with_blog( (int) $site_id, $callback );
            }
        }

        /**
         * Execute a callback within the context of a given site.
         *
         * @param int      $site_id  Site identifier.
         * @param callable $callback Callback to execute.
         */
        protected static function with_blog( $site_id, $callback ) {
            $site_id = (int) $site_id;

            if ( ! is_multisite() || $site_id < 1 ) {
                call_user_func( $callback, $site_id );

                return;
            }

            $current_blog_id = get_current_blog_id();
            $switched        = false;

            if ( $site_id !== $current_blog_id ) {
                $switched = switch_to_blog( $site_id );
            }

            try {
                call_user_func( $callback, $site_id );
            } finally {
                if ( $switched ) {
                    restore_current_blog();
                }
            }
        }

        /**
         * Retrieve consent logs table name.
         *
         * @return string
         */
        protected static function get_consent_table_name() {
            global $wpdb;

            return $wpdb->prefix . self::CONSENT_TABLE;
        }

        /**
         * Ensure the scheduled cleanup event exists.
         */
        protected static function schedule_cleanup_event() {
            if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
                wp_schedule_event( time(), 'daily', self::CLEANUP_HOOK );
            }
        }

        /**
         * Run upgrade routines when the plugin version changes.
         */
        public function maybe_upgrade() {
            self::schedule_cleanup_event();

            $installed_version = get_option( self::VERSION_OPTION, '' );

            if ( empty( $installed_version ) ) {
                $installed_version = '0';
            }

            if ( version_compare( $installed_version, self::VERSION, '>=' ) ) {
                return;
            }

            self::create_consent_table();

            if ( version_compare( $installed_version, '1.8.0', '<' ) ) {
                $options = get_option( self::OPTION_KEY, array() );

                if ( ! isset( $options['active_languages'] ) || ! is_array( $options['active_languages'] ) ) {
                    $languages = array();

                    if ( isset( $options['translations'] ) && is_array( $options['translations'] ) ) {
                        foreach ( array_keys( $options['translations'] ) as $language ) {
                            $language = $this->normalize_language_code( $language );

                            if ( self::DEFAULT_LANGUAGE === $language ) {
                                continue;
                            }

                            $languages[] = $language;
                        }
                    }

                    if ( empty( $languages ) ) {
                        $languages[] = 'en';
                    }

                    $options['active_languages'] = array_values( array_unique( $languages ) );

                    update_option( self::OPTION_KEY, $options );
                    $this->flush_settings_cache();
                }
            }

            update_option( self::VERSION_OPTION, self::VERSION );
        }

        /**
         * Prepare a newly created site when the plugin is network activated.
         *
         * @param WP_Site $new_site New site object.
         * @param array   $args     Initialization arguments.
         */
        public function handle_new_site_initialization( $new_site, $args = array() ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter
            if ( ! $new_site instanceof WP_Site ) {
                return;
            }

            $this->maybe_bootstrap_new_site( (int) $new_site->blog_id );
        }

        /**
         * Prepare a newly created site (legacy hook compatibility).
         *
         * @param int    $blog_id Blog identifier.
         * @param int    $user_id User identifier.
         * @param string $domain  Site domain.
         * @param string $path    Site path.
         * @param int    $site_id Network site identifier.
         * @param array  $meta    Site meta arguments.
         */
        public function handle_new_site_legacy( $blog_id, $user_id = 0, $domain = '', $path = '', $site_id = 0, $meta = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
            unset( $user_id, $domain, $path, $site_id, $meta );

            $this->maybe_bootstrap_new_site( (int) $blog_id );
        }

        /**
         * Bootstrap plugin data for a new site when network activated.
         *
         * @param int $site_id Site identifier.
         */
        protected function maybe_bootstrap_new_site( $site_id ) {
            if ( $site_id < 1 || ! self::is_network_active() ) {
                return;
            }

            self::with_blog( $site_id, array( __CLASS__, 'activate_single_site' ) );
        }

        /**
         * Check if consent table exists.
         *
         * @return bool
         */
        protected function consent_table_exists() {
            global $wpdb;

            $table_name = self::get_consent_table_name();

            return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        }

        /**
         * Cleanup old consent logs based on retention settings.
         *
         * @param array|null $settings Optional settings override.
         *
         * @return int Number of deleted rows.
         */
        public function cleanup_consent_logs( ?array $settings = null ) {
            if ( ! $this->consent_table_exists() ) {
                return 0;
            }

            if ( null === $settings ) {
                $settings = $this->get_settings();
            }

            $retention_days = $this->get_effective_retention_days( $settings );

            if ( $retention_days < 1 ) {
                return 0;
            }

            $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );

            global $wpdb;

            $table_name = self::get_consent_table_name();

            $deleted = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE created_at < %s",
                    $cutoff
                )
            );

            if ( false === $deleted ) {
                return 0;
            }

            return (int) $deleted;
        }

        /**
         * Retrieve the effective retention period for the consent log in days.
         *
         * @param array $settings Plugin settings.
         *
         * @return int
         */
        protected function get_effective_retention_days( array $settings ) {
            $retention_days = isset( $settings['retention_days'] ) ? (int) $settings['retention_days'] : 0;
            $retention_days = (int) apply_filters( 'fp_privacy_consent_retention_days', $retention_days, $settings );

            if ( $retention_days < 0 ) {
                $retention_days = 0;
            }

            return $retention_days;
        }

        /**
         * Register data exporter with WordPress privacy tools.
         *
         * @param array $exporters Existing exporters.
         *
         * @return array
         */
        public function register_privacy_exporter( $exporters ) {
            $exporters['fp-privacy-cookie-policy'] = array(
                'exporter_friendly_name' => __( 'Registro consensi FP Privacy & Cookie Policy', 'fp-privacy-cookie-policy' ),
                'callback'               => array( $this, 'export_personal_data' ),
            );

            return $exporters;
        }

        /**
         * Register data eraser with WordPress privacy tools.
         *
         * @param array $erasers Existing erasers.
         *
         * @return array
         */
        public function register_privacy_eraser( $erasers ) {
            $erasers['fp-privacy-cookie-policy'] = array(
                'eraser_friendly_name' => __( 'Registro consensi FP Privacy & Cookie Policy', 'fp-privacy-cookie-policy' ),
                'callback'             => array( $this, 'erase_personal_data' ),
            );

            return $erasers;
        }

        /**
         * Export personal data for a given email address.
         *
         * @param string $email_address Email address.
         * @param int    $page          Page number.
         *
         * @return array
         */
        public function export_personal_data( $email_address, $page = 1 ) {
            if ( ! $this->consent_table_exists() ) {
                return array(
                    'data' => array(),
                    'done' => true,
                );
            }

            $user = get_user_by( 'email', $email_address );

            if ( ! $user ) {
                return array(
                    'data' => array(),
                    'done' => true,
                );
            }

            $page     = max( 1, (int) $page );
            $per_page = 50;
            $offset   = ( $page - 1 ) * $per_page;

            global $wpdb;

            $table_name = self::get_consent_table_name();

            $logs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $user->ID,
                    $per_page,
                    $offset
                )
            );

            if ( empty( $logs ) ) {
                return array(
                    'data' => array(),
                    'done' => true,
                );
            }

            $group_label  = __( 'Registro consensi FP Privacy & Cookie Policy', 'fp-privacy-cookie-policy' );
            $export_items = array();

            foreach ( $logs as $log ) {
                $item_data = array();

                $item_data[] = array(
                    'name'  => __( 'ID consenso', 'fp-privacy-cookie-policy' ),
                    'value' => $log->consent_id,
                );

                $item_data[] = array(
                    'name'  => __( 'Evento', 'fp-privacy-cookie-policy' ),
                    'value' => $log->event_type,
                );

                $formatted_date = mysql2date(
                    get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                    $log->created_at,
                    true
                );

                if ( empty( $formatted_date ) ) {
                    $formatted_date = $log->created_at;
                }

                $item_data[] = array(
                    'name'  => __( 'Registrato il', 'fp-privacy-cookie-policy' ),
                    'value' => $formatted_date,
                );

                $decoded_state = json_decode( $log->consent_state, true );
                if ( is_array( $decoded_state ) ) {
                    $state_value = wp_json_encode( $decoded_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
                } else {
                    $state_value = $log->consent_state;
                }

                $item_data[] = array(
                    'name'  => __( 'Stato del consenso', 'fp-privacy-cookie-policy' ),
                    'value' => $state_value,
                );

                $item_data[] = array(
                    'name'  => __( 'IP anonimizzato', 'fp-privacy-cookie-policy' ),
                    'value' => $log->ip_address,
                );

                $item_data[] = array(
                    'name'  => __( 'User agent', 'fp-privacy-cookie-policy' ),
                    'value' => $log->user_agent,
                );

                $export_items[] = array(
                    'group_id'    => 'fp-privacy-cookie-policy-consents',
                    'group_label' => $group_label,
                    'item_id'     => 'consent-' . (int) $log->id,
                    'data'        => $item_data,
                );
            }

            $done = count( $logs ) < $per_page;

            return array(
                'data' => $export_items,
                'done' => $done,
            );
        }

        /**
         * Erase personal data for a given email address.
         *
         * @param string $email_address Email address.
         * @param int    $page          Page number.
         *
         * @return array
         */
        public function erase_personal_data( $email_address, $page = 1 ) {
            if ( ! $this->consent_table_exists() ) {
                return array(
                    'items_removed'  => false,
                    'items_retained' => false,
                    'messages'       => array(),
                    'done'           => true,
                );
            }

            $user = get_user_by( 'email', $email_address );

            if ( ! $user ) {
                return array(
                    'items_removed'  => false,
                    'items_retained' => false,
                    'messages'       => array(),
                    'done'           => true,
                );
            }

            $page     = max( 1, (int) $page );
            $per_page = 50;
            $offset   = ( $page - 1 ) * $per_page;

            global $wpdb;

            $table_name = self::get_consent_table_name();

            $ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
                    $user->ID,
                    $per_page,
                    $offset
                )
            );

            if ( empty( $ids ) ) {
                return array(
                    'items_removed'  => false,
                    'items_retained' => false,
                    'messages'       => array(),
                    'done'           => true,
                );
            }

            $items_removed  = false;
            $items_retained = false;

            foreach ( $ids as $id ) {
                $deleted = $wpdb->delete( $table_name, array( 'id' => (int) $id ), array( '%d' ) );

                if ( $deleted ) {
                    $items_removed = true;
                } else {
                    $items_retained = true;
                }
            }

            $messages = array();

            if ( $items_retained ) {
                $messages[] = __( 'Alcune voci del registro non sono state rimosse e richiedono un intervento manuale.', 'fp-privacy-cookie-policy' );
            }

            $done = count( $ids ) < $per_page;

            return array(
                'items_removed'  => $items_removed,
                'items_retained' => $items_retained,
                'messages'       => $messages,
                'done'           => $done,
            );
        }

        /**
         * Register admin menu.
         */
        public function register_admin_menu() {
            add_menu_page(
                __( 'Privacy & Cookie', 'fp-privacy-cookie-policy' ),
                __( 'Privacy & Cookie', 'fp-privacy-cookie-policy' ),
                'manage_options',
                'fp-privacy-cookie-policy',
                array( $this, 'render_settings_page' ),
                'dashicons-shield-alt',
                82
            );
        }

        /**
         * Retrieve the configuration for the admin tabs.
         *
         * @return array[]
         */
        protected function get_admin_tabs() {
            $base_url = admin_url( 'admin.php' );

            $tabs = array(
                'settings' => array(
                    'label' => __( 'Impostazioni', 'fp-privacy-cookie-policy' ),
                    'url'   => add_query_arg(
                        array(
                            'page' => 'fp-privacy-cookie-policy',
                            'tab'  => 'settings',
                        ),
                        $base_url
                    ),
                ),
                'logs' => array(
                    'label' => __( 'Registro consensi', 'fp-privacy-cookie-policy' ),
                    'url'   => add_query_arg(
                        array(
                            'page' => 'fp-privacy-cookie-policy',
                            'tab'  => 'logs',
                        ),
                        $base_url
                    ),
                ),
                'help' => array(
                    'label' => __( 'Guida rapida', 'fp-privacy-cookie-policy' ),
                    'url'   => add_query_arg(
                        array(
                            'page' => 'fp-privacy-cookie-policy',
                            'tab'  => 'help',
                        ),
                        $base_url
                    ),
                ),
            );

            /**
             * Filter the list of admin tabs displayed in the plugin settings.
             *
             * @param array[] $tabs Tab configuration.
             */
            return apply_filters( 'fp_privacy_admin_tabs', $tabs );
        }

        /**
         * Render admin notices for consent table health.
         */
        public function maybe_render_admin_notices() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $screen            = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
            $is_plugin_screen  = $screen && isset( $screen->id ) && 'toplevel_page_fp-privacy-cookie-policy' === $screen->id;
            $status            = isset( $_GET['fp_consent_table_status'] ) ? sanitize_key( wp_unslash( $_GET['fp_consent_table_status'] ) ) : '';
            $allowed_statuses  = array( 'success', 'error' );
            $cleanup_status    = isset( $_GET['fp_cleanup_status'] ) ? sanitize_key( wp_unslash( $_GET['fp_cleanup_status'] ) ) : '';
            $cleanup_removed   = isset( $_GET['fp_cleanup_removed'] ) ? absint( $_GET['fp_cleanup_removed'] ) : 0;
            $cleanup_allowed   = array( 'success', 'empty', 'disabled', 'missing' );
            $settings_import   = isset( $_GET['fp_settings_import'] ) ? sanitize_key( wp_unslash( $_GET['fp_settings_import'] ) ) : '';
            $settings_export   = isset( $_GET['fp_settings_export'] ) ? sanitize_key( wp_unslash( $_GET['fp_settings_export'] ) ) : '';
            $revision_status   = isset( $_GET['fp_consent_revision'] ) ? sanitize_key( wp_unslash( $_GET['fp_consent_revision'] ) ) : '';

            if ( $status && in_array( $status, $allowed_statuses, true ) ) {
                if ( ! $is_plugin_screen ) {
                    return;
                }

                if ( 'success' === $status ) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'La tabella del registro consensi è stata ricreata correttamente.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                } elseif ( 'error' === $status ) {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Impossibile creare la tabella del registro consensi. Verifica i permessi del database.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                }
            }

            if ( $settings_export && 'error' === $settings_export ) {
                if ( ! $is_plugin_screen ) {
                    return;
                }

                echo '<div class="notice notice-error"><p>' . esc_html__( 'Impossibile generare il file di esportazione delle impostazioni. Riprova.', 'fp-privacy-cookie-policy' ) . '</p></div>';
            }

            if ( $settings_import ) {
                if ( ! $is_plugin_screen ) {
                    return;
                }

                if ( 'success' === $settings_import ) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Le impostazioni sono state importate correttamente.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                } elseif ( 'invalid' === $settings_import ) {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Il file selezionato non contiene impostazioni valide.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                } elseif ( 'missing' === $settings_import ) {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Seleziona un file JSON generato dal plugin prima di procedere con l\'import.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                } elseif ( 'error' === $settings_import ) {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Impossibile importare le impostazioni. Riprova.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                }
            }

            if ( $cleanup_status && in_array( $cleanup_status, $cleanup_allowed, true ) ) {
                if ( ! $is_plugin_screen ) {
                    return;
                }

                if ( 'success' === $cleanup_status ) {
                    $message = sprintf(
                        /* translators: %d is the number of removed consent log entries. */
                        __( 'Pulizia completata. %d registrazioni sono state rimosse.', 'fp-privacy-cookie-policy' ),
                        $cleanup_removed
                    );
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
                } elseif ( 'empty' === $cleanup_status ) {
                    echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Non ci sono registrazioni più vecchie del periodo di conservazione configurato.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                } elseif ( 'disabled' === $cleanup_status ) {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Imposta un periodo di conservazione per attivare la pulizia del registro consensi.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                } elseif ( 'missing' === $cleanup_status ) {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Impossibile pulire il registro perché la tabella dei consensi non è disponibile.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                }
            }

            if ( $revision_status && $is_plugin_screen ) {
                if ( 'reset' === $revision_status ) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Il consenso verrà richiesto nuovamente a tutti i visitatori.', 'fp-privacy-cookie-policy' ) . '</p></div>';
                }
            }

            if ( ! $is_plugin_screen ) {
                return;
            }

            if ( $this->consent_table_exists() ) {
                return;
            }

            $action_url  = admin_url( 'admin-post.php' );
            $redirect_to = admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=logs' );

            echo '<div class="notice notice-error">';
            echo '<p>' . esc_html__( 'La tabella del registro consensi è mancante o danneggiata. Ricreala per continuare a salvare i consensi.', 'fp-privacy-cookie-policy' ) . '</p>';
            echo '<form method="post" action="' . esc_url( $action_url ) . '">';
            wp_nonce_field( 'fp_recreate_consent_table', 'fp_recreate_consent_table_nonce' );
            echo '<input type="hidden" name="action" value="fp_recreate_consent_table" />';
            echo '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect_to ) . '" />';
            echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__( 'Ricrea tabella registro consensi', 'fp-privacy-cookie-policy' ) . '</button></p>';
            echo '</form>';
            echo '</div>';
        }

        /**
         * Register plugin settings.
         */
        public function register_settings() {
            register_setting(
                'fp_privacy_cookie_group',
                self::OPTION_KEY,
                array( $this, 'sanitize_settings' )
            );

            add_settings_section(
                'fp_privacy_general_section',
                __( 'Impostazioni generali', 'fp-privacy-cookie-policy' ),
                '__return_false',
                'fp_privacy_cookie_policy'
            );

            add_settings_field(
                'privacy_policy_content',
                __( 'Privacy Policy', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_privacy_editor' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_field(
                'cookie_policy_content',
                __( 'Cookie Policy', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_cookie_editor' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_field(
                'banner_settings',
                __( 'Banner Cookie', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_banner_settings' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_field(
                'render_hook',
                __( 'Posizionamento banner', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_banner_hook_field' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_field(
                'preview_mode',
                __( 'Modalità anteprima', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_preview_mode_field' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_field(
                'active_languages',
                __( 'Lingue aggiuntive', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_language_manager_field' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_field(
                'frontend_texts',
                __( 'Testi interfaccia', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_frontend_texts_field' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_field(
                'retention_days',
                __( 'Conservazione registro consensi', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_retention_field' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_field(
                'consent_cookie_days',
                __( 'Durata cookie di consenso', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_consent_cookie_field' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_general_section'
            );

            add_settings_section(
                'fp_privacy_categories_section',
                __( 'Categorie di cookie', 'fp-privacy-cookie-policy' ),
                '__return_false',
                'fp_privacy_cookie_policy'
            );

            add_settings_field(
                'cookie_categories',
                __( 'Dettagli categorie', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_categories_fields' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_categories_section'
            );

            add_settings_section(
                'fp_privacy_google_section',
                __( 'Google Consent Mode v2', 'fp-privacy-cookie-policy' ),
                '__return_false',
                'fp_privacy_cookie_policy'
            );

            add_settings_field(
                'google_consent_defaults',
                __( 'Stato di default', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_google_defaults' ),
                'fp_privacy_cookie_policy',
                'fp_privacy_google_section'
            );
        }

        /**
         * Sanitize settings.
         *
         * @param array $input Input values.
         *
         * @return array
         */
        public function sanitize_settings( $input ) {
            $defaults = $this->get_default_settings();
            $existing = get_option( self::OPTION_KEY, array() );
            $output   = $defaults;

            $output['privacy_policy_content'] = isset( $input['privacy_policy_content'] ) ? wp_kses_post( $input['privacy_policy_content'] ) : $defaults['privacy_policy_content'];
            $output['cookie_policy_content']  = isset( $input['cookie_policy_content'] ) ? wp_kses_post( $input['cookie_policy_content'] ) : $defaults['cookie_policy_content'];

            $banner_input        = isset( $input['banner'] ) && is_array( $input['banner'] ) ? $input['banner'] : array();
            $categories_input    = isset( $input['categories'] ) && is_array( $input['categories'] ) ? $input['categories'] : array();
            $google_input        = isset( $input['google_defaults'] ) && is_array( $input['google_defaults'] ) ? $input['google_defaults'] : array();
            $texts_input         = isset( $input['texts'] ) && is_array( $input['texts'] ) ? $input['texts'] : array();
            $translations_input  = isset( $input['translations'] ) && is_array( $input['translations'] ) ? $input['translations'] : array();
            $active_languages_in = isset( $input['active_languages'] ) ? $input['active_languages'] : array();
            $custom_languages_in = isset( $input['custom_languages'] ) ? $input['custom_languages'] : '';

            $output['banner']           = $this->sanitize_banner_settings( $banner_input, $defaults['banner'] );
            $output['categories']       = $this->sanitize_categories_settings( $categories_input, $defaults['categories'] );
            $output['google_defaults']  = $this->sanitize_google_defaults( $google_input, $defaults['google_defaults'] );
            $output['texts']            = $this->sanitize_frontend_texts( $texts_input, $defaults['texts'] );
            $output['active_languages'] = $this->sanitize_active_languages( $active_languages_in, $custom_languages_in );
            $output['translations']     = $this->sanitize_translations( $translations_input, $defaults['translations'], $defaults, $output['active_languages'] );
            $output['render_hook']     = $this->sanitize_render_hook(
                isset( $input['render_hook'] ) ? $input['render_hook'] : $defaults['render_hook'],
                $defaults['render_hook']
            );
            $output['preview_mode']    = ! empty( $input['preview_mode'] );
            $output['retention_days']  = $this->sanitize_retention_days(
                isset( $input['retention_days'] ) ? $input['retention_days'] : $defaults['retention_days'],
                $defaults['retention_days']
            );
            $output['consent_cookie_days'] = $this->sanitize_cookie_duration_days(
                isset( $input['consent_cookie_days'] ) ? $input['consent_cookie_days'] : $defaults['consent_cookie_days'],
                $defaults['consent_cookie_days']
            );

            $existing_revision = isset( $existing['consent_revision'] ) ? (int) $existing['consent_revision'] : $defaults['consent_revision'];
            if ( isset( $input['consent_revision'] ) ) {
                $existing_revision = max( 1, (int) $input['consent_revision'] );
            }

            $output['consent_revision'] = max( 1, $existing_revision );

            $existing_revision_date = isset( $existing['consent_revision_updated_at'] ) ? $existing['consent_revision_updated_at'] : $defaults['consent_revision_updated_at'];
            if ( isset( $input['consent_revision_updated_at'] ) ) {
                $existing_revision_date = $this->sanitize_mysql_datetime( $input['consent_revision_updated_at'] );
            }

            $output['consent_revision_updated_at'] = $this->sanitize_mysql_datetime( $existing_revision_date );

            $output['auto_generate'] = isset( $input['auto_generate'] ) ? (bool) $input['auto_generate'] : $defaults['auto_generate'];

            return $output;
        }

        /**
         * Sanitize banner settings.
         *
         * @param array $input    Raw input.
         * @param array $defaults Default banner settings.
         *
         * @return array
         */
        protected function sanitize_banner_settings( array $input, array $defaults ) {
            $sanitized = array_merge( $defaults, $this->sanitize_banner_texts( $input, $defaults ) );

            $sanitized['show_reject']      = ! empty( $input['show_reject'] );
            $sanitized['show_preferences'] = ! empty( $input['show_preferences'] );

            $default_layout = isset( $defaults['layout'] ) ? $defaults['layout'] : 'floating';
            $sanitized['layout'] = $this->sanitize_banner_layout(
                isset( $input['layout'] ) ? $input['layout'] : $default_layout,
                $default_layout
            );

            $sanitized['background_color'] = $this->sanitize_hex_color_default(
                isset( $input['background_color'] ) ? $input['background_color'] : '',
                isset( $defaults['background_color'] ) ? $defaults['background_color'] : '#ffffff'
            );

            $sanitized['text_color'] = $this->sanitize_hex_color_default(
                isset( $input['text_color'] ) ? $input['text_color'] : '',
                isset( $defaults['text_color'] ) ? $defaults['text_color'] : '#1f2933'
            );

            $sanitized['accent_color'] = $this->sanitize_hex_color_default(
                isset( $input['accent_color'] ) ? $input['accent_color'] : '',
                isset( $defaults['accent_color'] ) ? $defaults['accent_color'] : '#2563eb'
            );

            $sanitized['secondary_color'] = $this->sanitize_hex_color_default(
                isset( $input['secondary_color'] ) ? $input['secondary_color'] : '',
                isset( $defaults['secondary_color'] ) ? $defaults['secondary_color'] : '#eef2ff'
            );

            $sanitized['secondary_text_color'] = $this->sanitize_hex_color_default(
                isset( $input['secondary_text_color'] ) ? $input['secondary_text_color'] : '',
                isset( $defaults['secondary_text_color'] ) ? $defaults['secondary_text_color'] : '#1e3a8a'
            );

            $sanitized['border_color'] = $this->sanitize_hex_color_default(
                isset( $input['border_color'] ) ? $input['border_color'] : '',
                isset( $defaults['border_color'] ) ? $defaults['border_color'] : '#dbeafe'
            );

            return $sanitized;
        }

        /**
         * Sanitize a hex color value with fallback.
         *
         * @param string $value   Raw value.
         * @param string $default Default value.
         *
         * @return string
         */
        protected function sanitize_hex_color_default( $value, $default ) {
            if ( is_string( $value ) ) {
                $value = trim( $value );
            }

            $sanitized = sanitize_hex_color( $value );

            if ( $sanitized ) {
                return $sanitized;
            }

            $fallback = sanitize_hex_color( $default );

            return $fallback ? $fallback : '#000000';
        }

        /**
         * Sanitize banner layout.
         *
         * @param string $value   Raw layout value.
         * @param string $default Default layout.
         *
         * @return string
         */
        protected function sanitize_banner_layout( $value, $default ) {
            $value    = is_string( $value ) ? trim( strtolower( $value ) ) : '';
            $allowed  = $this->get_banner_layout_options();
            $selected = in_array( $value, $allowed, true ) ? $value : $default;

            if ( ! in_array( $selected, $allowed, true ) ) {
                $selected = 'floating';
            }

            return $selected;
        }

        /**
         * Retrieve the available banner layout options.
         *
         * @return array
         */
        protected function get_banner_layout_options() {
            return array( 'floating', 'floating_top', 'bar_bottom', 'bar_top' );
        }

        /**
         * Sanitize the banner render hook setting.
         *
         * @param string $value   Raw value.
         * @param string $default Default value.
         *
         * @return string
         */
        protected function sanitize_render_hook( $value, $default ) {
            $value    = sanitize_key( $value );
            $allowed  = array( 'footer', 'body_open', 'manual' );
            $selected = in_array( $value, $allowed, true ) ? $value : $default;

            return $selected;
        }

        /**
         * Sanitize consent log date filters coming from the admin UI.
         *
         * @param string $value Raw date value.
         *
         * @return string
         */
        protected function sanitize_log_filter_date( $value ) {
            if ( ! is_string( $value ) ) {
                return '';
            }

            $value = trim( $value );

            if ( '' === $value ) {
                return '';
            }

            try {
                $timezone = wp_timezone();
                $date     = DateTimeImmutable::createFromFormat( 'Y-m-d', $value, $timezone );

                if ( false === $date ) {
                    return '';
                }

                $errors = DateTimeImmutable::getLastErrors();

                if ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) {
                    return '';
                }

                return $date->format( 'Y-m-d' );
            } catch ( Exception $exception ) {
                return '';
            }
        }

        /**
         * Normalize consent log filters and build SQL fragments for queries.
         *
         * @param array $filters Raw filter arguments.
         *
         * @return array
         */
        protected function prepare_consent_log_filters( array $filters ) {
            global $wpdb;

            $search_raw = isset( $filters['search'] ) && ! is_array( $filters['search'] ) ? $filters['search'] : '';
            $event_raw  = isset( $filters['event'] ) && ! is_array( $filters['event'] ) ? $filters['event'] : '';
            $from_raw   = isset( $filters['from'] ) && ! is_array( $filters['from'] ) ? $filters['from'] : '';
            $to_raw     = isset( $filters['to'] ) && ! is_array( $filters['to'] ) ? $filters['to'] : '';

            $search = sanitize_text_field( (string) $search_raw );
            $event  = sanitize_key( (string) $event_raw );
            $from   = $this->sanitize_log_filter_date( (string) $from_raw );
            $to     = $this->sanitize_log_filter_date( (string) $to_raw );

            if ( '' !== $from && '' !== $to ) {
                try {
                    $timezone = wp_timezone();
                    $from_dt  = new DateTimeImmutable( $from, $timezone );
                    $to_dt    = new DateTimeImmutable( $to, $timezone );

                    if ( $from_dt > $to_dt ) {
                        $temp = $from;
                        $from = $to;
                        $to   = $temp;
                    }
                } catch ( Exception $exception ) {
                    $from = '';
                    $to   = '';
                }
            }

            $where_clauses = array();
            $params        = array();

            if ( '' !== $search ) {
                $like            = '%' . $wpdb->esc_like( $search ) . '%';
                $where_clauses[] = '(consent_id LIKE %s OR consent_state LIKE %s OR ip_address LIKE %s)';
                $params[]        = $like;
                $params[]        = $like;
                $params[]        = $like;
            }

            $allowed_events = $this->get_allowed_consent_events();

            if ( '' !== $event && in_array( $event, $allowed_events, true ) ) {
                $where_clauses[] = 'event_type = %s';
                $params[]        = $event;
            } else {
                $event = '';
            }

            if ( '' !== $from ) {
                $where_clauses[] = 'created_at >= %s';
                $params[]        = $from . ' 00:00:00';
            } else {
                $from = '';
            }

            if ( '' !== $to ) {
                $where_clauses[] = 'created_at <= %s';
                $params[]        = $to . ' 23:59:59';
            } else {
                $to = '';
            }

            return array(
                'search' => $search,
                'event'  => $event,
                'from'   => $from,
                'to'     => $to,
                'where'  => $where_clauses,
                'params' => $params,
            );
        }

        /**
         * Sanitize banner translation fields.
         *
         * @param array $input          Raw translation input.
         * @param array $defaults       Translation defaults for the banner.
         * @param array $base_defaults  Base banner defaults.
         *
         * @return array
         */
        protected function sanitize_banner_translation( array $input, array $defaults, array $base_defaults ) {
            $fallback = ! empty( $defaults ) ? $defaults : $this->extract_banner_text_defaults( $base_defaults );

            return $this->sanitize_banner_texts( $input, $fallback );
        }

        /**
         * Sanitize textual banner fields.
         *
         * @param array $input    Raw input.
         * @param array $defaults Default values.
         *
         * @return array
         */
        protected function sanitize_banner_texts( array $input, array $defaults ) {
            $fields    = array(
                'banner_title'          => 'sanitize_text_field',
                'accept_all_label'      => 'sanitize_text_field',
                'reject_all_label'      => 'sanitize_text_field',
                'preferences_label'     => 'sanitize_text_field',
                'save_preferences_label'=> 'sanitize_text_field',
            );
            $sanitized = array();

            foreach ( $fields as $field => $callback ) {
                if ( array_key_exists( $field, $input ) ) {
                    $sanitized[ $field ] = call_user_func( $callback, $input[ $field ] );
                } elseif ( array_key_exists( $field, $defaults ) ) {
                    $sanitized[ $field ] = $defaults[ $field ];
                }
            }

            if ( array_key_exists( 'banner_message', $input ) ) {
                $sanitized['banner_message'] = wp_kses_post( $input['banner_message'] );
            } elseif ( array_key_exists( 'banner_message', $defaults ) ) {
                $sanitized['banner_message'] = $defaults['banner_message'];
            }

            return $sanitized;
        }

        /**
         * Extract textual defaults from banner array.
         *
         * @param array $banner Banner defaults.
         *
         * @return array
         */
        protected function extract_banner_text_defaults( array $banner ) {
            $keys     = array( 'banner_title', 'banner_message', 'accept_all_label', 'reject_all_label', 'preferences_label', 'save_preferences_label' );
            $defaults = array();

            foreach ( $keys as $key ) {
                if ( array_key_exists( $key, $banner ) ) {
                    $defaults[ $key ] = $banner[ $key ];
                }
            }

            return $defaults;
        }

        /**
         * Retrieve the sanitized banner layout key.
         *
         * @param array $banner Banner settings.
         *
         * @return string
         */
        protected function get_banner_layout_key( array $banner ) {
            $layout = isset( $banner['layout'] ) ? $banner['layout'] : 'floating';

            return $this->sanitize_banner_layout( $layout, 'floating' );
        }

        /**
         * Retrieve the suffix used for layout-based class names.
         *
         * @param array $banner Banner settings.
         *
         * @return string
         */
        protected function get_banner_layout_suffix( array $banner ) {
            return str_replace( '_', '-', $this->get_banner_layout_key( $banner ) );
        }

        /**
         * Retrieve the class applied to the front-end banner for layout variations.
         *
         * @param array $banner Banner settings.
         *
         * @return string
         */
        protected function get_banner_layout_class( array $banner ) {
            return 'fp-consent-banner--' . $this->get_banner_layout_suffix( $banner );
        }

        /**
         * Retrieve the preview class applied for layout variations.
         *
         * @param array $banner Banner settings.
         *
         * @return string
         */
        protected function get_preview_layout_class( array $banner ) {
            return 'fp-preview-layout--' . $this->get_banner_layout_suffix( $banner );
        }

        /**
         * Build the CSS custom properties used to style the banner.
         *
         * @param array $banner Banner settings.
         *
         * @return array
         */
        protected function get_banner_theme_variables( array $banner ) {
            $background = isset( $banner['background_color'] )
                ? $this->sanitize_hex_color_default( $banner['background_color'], '#ffffff' )
                : '#ffffff';
            $text = isset( $banner['text_color'] )
                ? $this->sanitize_hex_color_default( $banner['text_color'], '#1f2933' )
                : '#1f2933';
            $accent = isset( $banner['accent_color'] )
                ? $this->sanitize_hex_color_default( $banner['accent_color'], '#2563eb' )
                : '#2563eb';
            $secondary = isset( $banner['secondary_color'] )
                ? $this->sanitize_hex_color_default( $banner['secondary_color'], '#eef2ff' )
                : '#eef2ff';
            $secondary_text = isset( $banner['secondary_text_color'] )
                ? $this->sanitize_hex_color_default( $banner['secondary_text_color'], '#1e3a8a' )
                : '#1e3a8a';
            $border = isset( $banner['border_color'] )
                ? $this->sanitize_hex_color_default( $banner['border_color'], '#dbeafe' )
                : '#dbeafe';

            $accent_active = $this->adjust_color_luminance( $accent, -0.15 );
            $accent_soft   = $this->build_rgba( $accent, 0.12 );
            $accent_softer = $this->build_rgba( $accent, 0.18 );

            $secondary_border = $this->mix_hex_colors( $secondary, '#000000', 0.12 );
            $secondary_hover  = $this->mix_hex_colors( $secondary, '#ffffff', 0.2 );
            $card_bg          = $this->mix_hex_colors( $background, '#ffffff', 0.92 );
            $card_border      = $this->mix_hex_colors( $background, '#000000', 0.08 );
            $muted_text       = $this->mix_hex_colors( $text, $background, 0.4 );
            $switch_off       = $this->mix_hex_colors( $accent, '#ffffff', 0.65 );
            $manage_bg        = $this->mix_hex_colors( $background, '#ffffff', 0.75 );
            $manage_border    = $this->mix_hex_colors( $background, '#000000', 0.12 );
            $manage_hover     = $this->mix_hex_colors( $manage_bg, '#ffffff', 0.12 );
            $manage_text      = $this->mix_hex_colors( $text, '#ffffff', 0.08 );

            return array(
                '--fp-banner-background'        => $background,
                '--fp-banner-text'              => $text,
                '--fp-banner-border'            => $border,
                '--fp-banner-muted-text'        => $muted_text,
                '--fp-banner-accent'            => $accent,
                '--fp-banner-accent-contrast'   => $this->calculate_contrast_color( $accent ),
                '--fp-banner-accent-active'     => $accent_active,
                '--fp-banner-accent-soft'       => $accent_soft,
                '--fp-banner-accent-softer'     => $accent_softer,
                '--fp-banner-secondary-bg'      => $secondary,
                '--fp-banner-secondary-text'    => $secondary_text,
                '--fp-banner-secondary-border'  => $secondary_border,
                '--fp-banner-secondary-hover'   => $secondary_hover,
                '--fp-banner-card-bg'           => $card_bg,
                '--fp-banner-card-border'       => $card_border,
                '--fp-banner-switch-off'        => $switch_off,
                '--fp-banner-manage-bg'         => $manage_bg,
                '--fp-banner-manage-border'     => $manage_border,
                '--fp-banner-manage-hover-bg'   => $manage_hover,
                '--fp-banner-manage-text'       => $manage_text,
            );
        }

        /**
         * Build a CSS declaration string from custom properties.
         *
         * @param array $variables Map of property => value.
         *
         * @return string
         */
        protected function build_css_custom_properties( array $variables ) {
            $declarations = array();

            foreach ( $variables as $property => $value ) {
                if ( ! is_string( $property ) || '' === $property || null === $value || '' === $value ) {
                    continue;
                }

                $property = trim( $property );
                $value    = is_string( $value ) ? trim( $value ) : $value;

                if ( '' === $property || '' === $value ) {
                    continue;
                }

                $declarations[] = $property . ': ' . $value;
            }

            if ( empty( $declarations ) ) {
                return '';
            }

            return implode( '; ', $declarations );
        }

        /**
         * Blend two hexadecimal colours by the provided amount.
         *
         * @param string $first   Base colour.
         * @param string $second  Secondary colour.
         * @param float  $amount  Amount between 0 and 1.
         *
         * @return string
         */
        protected function mix_hex_colors( $first, $second, $amount ) {
            $amount = max( 0, min( 1, (float) $amount ) );

            $first_rgb  = $this->hex_to_rgb( $first );
            $second_rgb = $this->hex_to_rgb( $second );

            if ( empty( $first_rgb ) || empty( $second_rgb ) ) {
                return $first;
            }

            $result = array(
                'r' => (int) round( $first_rgb['r'] * ( 1 - $amount ) + $second_rgb['r'] * $amount ),
                'g' => (int) round( $first_rgb['g'] * ( 1 - $amount ) + $second_rgb['g'] * $amount ),
                'b' => (int) round( $first_rgb['b'] * ( 1 - $amount ) + $second_rgb['b'] * $amount ),
            );

            return $this->rgb_to_hex( $result );
        }

        /**
         * Adjust the luminance of a hexadecimal colour.
         *
         * @param string $hex      Base colour.
         * @param float  $percent  Percentage between -1 and 1.
         *
         * @return string
         */
        protected function adjust_color_luminance( $hex, $percent ) {
            $percent = max( -1, min( 1, (float) $percent ) );

            if ( $percent === 0.0 ) {
                return $this->sanitize_hex_color_default( $hex, '#000000' );
            }

            if ( $percent > 0 ) {
                return $this->mix_hex_colors( $hex, '#ffffff', $percent );
            }

            return $this->mix_hex_colors( $hex, '#000000', abs( $percent ) );
        }

        /**
         * Convert a hexadecimal colour to an associative RGB array.
         *
         * @param string $hex Hexadecimal colour.
         *
         * @return array|null
         */
        protected function hex_to_rgb( $hex ) {
            $hex = is_string( $hex ) ? trim( $hex ) : '';
            $hex = ltrim( $hex, '#' );

            if ( 3 === strlen( $hex ) ) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }

            if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
                return null;
            }

            $int = hexdec( $hex );

            return array(
                'r' => ( $int >> 16 ) & 255,
                'g' => ( $int >> 8 ) & 255,
                'b' => $int & 255,
            );
        }

        /**
         * Convert RGB components to hexadecimal notation.
         *
         * @param array $rgb RGB array.
         *
         * @return string
         */
        protected function rgb_to_hex( $rgb ) {
            $r = isset( $rgb['r'] ) ? max( 0, min( 255, (int) $rgb['r'] ) ) : 0;
            $g = isset( $rgb['g'] ) ? max( 0, min( 255, (int) $rgb['g'] ) ) : 0;
            $b = isset( $rgb['b'] ) ? max( 0, min( 255, (int) $rgb['b'] ) ) : 0;

            return sprintf( '#%02x%02x%02x', $r, $g, $b );
        }

        /**
         * Convert a hexadecimal colour into an rgba() string with the given alpha.
         *
         * @param string $hex   Base colour.
         * @param float  $alpha Alpha channel value between 0 and 1.
         *
         * @return string
         */
        protected function build_rgba( $hex, $alpha ) {
            $alpha = max( 0, min( 1, (float) $alpha ) );
            $rgb   = $this->hex_to_rgb( $hex );

            if ( empty( $rgb ) ) {
                $rgb = $this->hex_to_rgb( '#000000' );
            }

            return sprintf( 'rgba(%d, %d, %d, %.2f)', $rgb['r'], $rgb['g'], $rgb['b'], $alpha );
        }

        /**
         * Determine an accessible contrast colour (black or white) for the provided base colour.
         *
         * @param string $hex Base colour.
         *
         * @return string
         */
        protected function calculate_contrast_color( $hex ) {
            $rgb = $this->hex_to_rgb( $hex );

            if ( empty( $rgb ) ) {
                return '#ffffff';
            }

            $luminance = ( 0.2126 * $rgb['r'] + 0.7152 * $rgb['g'] + 0.0722 * $rgb['b'] ) / 255;

            return ( $luminance > 0.55 ) ? '#000000' : '#ffffff';
        }

        /**
         * Sanitize categories settings.
         *
         * @param array $input    Raw input.
         * @param array $defaults Default categories.
         *
         * @return array
         */
        protected function sanitize_categories_settings( array $input, array $defaults ) {
            $categories = $defaults;

            foreach ( $categories as $key => $category ) {
                $item = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();

                $categories[ $key ]['enabled'] = ! empty( $item['enabled'] );

                if ( array_key_exists( 'required', $item ) ) {
                    $categories[ $key ]['required'] = (bool) $item['required'];
                }

                if ( array_key_exists( 'description', $item ) ) {
                    $categories[ $key ]['description'] = wp_kses_post( $item['description'] );
                }

                if ( array_key_exists( 'services', $item ) ) {
                    $categories[ $key ]['services'] = sanitize_textarea_field( $item['services'] );
                }
            }

            return $categories;
        }

        /**
         * Sanitize category translations.
         *
         * @param array $input         Raw input.
         * @param array $defaults      Translation defaults.
         * @param array $base_defaults Base categories defaults.
         *
         * @return array
         */
        protected function sanitize_category_translations( array $input, array $defaults, array $base_defaults ) {
            $sanitized = array();
            $keys      = array_unique( array_merge( array_keys( $base_defaults ), array_keys( $defaults ), array_keys( $input ) ) );

            foreach ( $keys as $key ) {
                $incoming = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();
                $default  = array();

                if ( isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) ) {
                    $default = $defaults[ $key ];
                } elseif ( isset( $base_defaults[ $key ] ) && is_array( $base_defaults[ $key ] ) ) {
                    $default = array(
                        'label'       => $base_defaults[ $key ]['label'],
                        'description' => $base_defaults[ $key ]['description'],
                        'services'    => $base_defaults[ $key ]['services'],
                    );
                }

                $sanitized[ $key ] = array();

                if ( array_key_exists( 'label', $incoming ) ) {
                    $sanitized[ $key ]['label'] = sanitize_text_field( $incoming['label'] );
                } elseif ( array_key_exists( 'label', $default ) ) {
                    $sanitized[ $key ]['label'] = sanitize_text_field( $default['label'] );
                }

                if ( array_key_exists( 'description', $incoming ) ) {
                    $sanitized[ $key ]['description'] = wp_kses_post( $incoming['description'] );
                } elseif ( array_key_exists( 'description', $default ) ) {
                    $sanitized[ $key ]['description'] = wp_kses_post( $default['description'] );
                }

                if ( array_key_exists( 'services', $incoming ) ) {
                    $sanitized[ $key ]['services'] = sanitize_textarea_field( $incoming['services'] );
                } elseif ( array_key_exists( 'services', $default ) ) {
                    $sanitized[ $key ]['services'] = sanitize_textarea_field( $default['services'] );
                }
            }

            return $sanitized;
        }

        /**
         * Sanitize frontend static texts.
         *
         * @param array $input    Raw input values.
         * @param array $defaults Default texts.
         *
         * @return array
         */
        protected function sanitize_frontend_texts( array $input, array $defaults ) {
            $sanitized = $defaults;

            foreach ( $defaults as $key => $default ) {
                if ( ! array_key_exists( $key, $input ) ) {
                    continue;
                }

                $sanitized[ $key ] = $this->sanitize_single_frontend_text( $key, $input[ $key ], $default );
            }

            if ( isset( $sanitized['toggle_aria'] ) && false === strpos( $sanitized['toggle_aria'], '%s' ) ) {
                $sanitized['toggle_aria'] = $defaults['toggle_aria'];
            }

            return $sanitized;
        }

        /**
         * Sanitize a single frontend text value.
         *
         * @param string $key     Text key.
         * @param mixed  $value   Incoming value.
         * @param string $default Default value.
         *
         * @return string
         */
        protected function sanitize_single_frontend_text( $key, $value, $default ) {
            if ( is_array( $value ) ) {
                $value = ''; // Unexpected structure, fallback to default.
            }

            if ( 'modal_intro' === $key ) {
                $sanitized = sanitize_textarea_field( (string) $value );
            } else {
                $sanitized = sanitize_text_field( (string) $value );
            }

            if ( '' === $sanitized ) {
                return $default;
            }

            if ( 'toggle_aria' === $key && false === strpos( $sanitized, '%s' ) ) {
                return $default;
            }

            return $sanitized;
        }

        /**
         * Sanitize Google default consent values.
         *
         * @param array $input    Raw input.
         * @param array $defaults Default values.
         *
         * @return array
         */
        protected function sanitize_google_defaults( array $input, array $defaults ) {
            $sanitized      = $defaults;
            $allowed_values = array( 'granted', 'denied' );

            foreach ( $defaults as $key => $value ) {
                if ( array_key_exists( $key, $input ) ) {
                    $incoming = sanitize_text_field( $input[ $key ] );

                    if ( ! in_array( $incoming, $allowed_values, true ) ) {
                        $incoming = $value;
                    }

                    $sanitized[ $key ] = $incoming;
                }
            }

            return $sanitized;
        }

        /**
         * Sanitize the list of active languages submitted via the settings form.
         *
         * @param mixed $selected Selected languages from the multi select.
         * @param mixed $custom   Custom languages provided manually.
         *
         * @return array
         */
        protected function sanitize_active_languages( $selected, $custom ) {
            $languages = array();

            if ( is_array( $selected ) ) {
                foreach ( $selected as $language ) {
                    $languages[] = $this->normalize_language_code( $language );
                }
            }

            if ( is_string( $custom ) && $custom ) {
                $parts = array_map( 'trim', explode( ',', $custom ) );

                foreach ( $parts as $language ) {
                    if ( '' === $language ) {
                        continue;
                    }

                    $languages[] = $this->normalize_language_code( $language );
                }
            }

            $languages = array_filter(
                $languages,
                array( $this, 'filter_non_default_language' )
            );

            $languages = array_values( array_unique( $languages ) );
            sort( $languages );

            return $languages;
        }

        /**
         * Sanitize translations array.
         *
         * @param array $translations   Raw translation input.
         * @param array $defaults       Translation defaults.
         * @param array $base_defaults  Base defaults.
         * @param array $active_languages Languages enabled in the settings.
         *
         * @return array
         */
        protected function sanitize_translations( array $translations, array $defaults, array $base_defaults, array $active_languages = array() ) {
            $sanitized = array();
            $allowed   = array();

            foreach ( $active_languages as $language ) {
                $language = $this->normalize_language_code( $language );

                if ( self::DEFAULT_LANGUAGE === $language ) {
                    continue;
                }

                $allowed[] = $language;
            }

            if ( empty( $allowed ) ) {
                $allowed = array_keys( $defaults );
                $allowed = array_merge( $allowed, array_keys( $translations ) );
            }

            $candidates = array_unique( $allowed );

            foreach ( $candidates as $candidate ) {
                $language = $this->normalize_language_code( $candidate );

                if ( self::DEFAULT_LANGUAGE === $language ) {
                    continue;
                }

                $incoming = array();
                foreach ( $translations as $key => $value ) {
                    if ( $this->normalize_language_code( $key ) === $language && is_array( $value ) ) {
                        $incoming = $value;
                        break;
                    }
                }

                $default_translation = array();
                foreach ( $defaults as $key => $value ) {
                    if ( $this->normalize_language_code( $key ) === $language && is_array( $value ) ) {
                        $default_translation = $value;
                        break;
                    }
                }

                $translation = array();

                if ( array_key_exists( 'privacy_policy_content', $incoming ) ) {
                    $translation['privacy_policy_content'] = wp_kses_post( $incoming['privacy_policy_content'] );
                } elseif ( array_key_exists( 'privacy_policy_content', $default_translation ) ) {
                    $translation['privacy_policy_content'] = $default_translation['privacy_policy_content'];
                } else {
                    $translation['privacy_policy_content'] = $base_defaults['privacy_policy_content'];
                }

                if ( array_key_exists( 'cookie_policy_content', $incoming ) ) {
                    $translation['cookie_policy_content'] = wp_kses_post( $incoming['cookie_policy_content'] );
                } elseif ( array_key_exists( 'cookie_policy_content', $default_translation ) ) {
                    $translation['cookie_policy_content'] = $default_translation['cookie_policy_content'];
                } else {
                    $translation['cookie_policy_content'] = $base_defaults['cookie_policy_content'];
                }

                $translation['banner']     = $this->sanitize_banner_translation(
                    isset( $incoming['banner'] ) && is_array( $incoming['banner'] ) ? $incoming['banner'] : array(),
                    isset( $default_translation['banner'] ) && is_array( $default_translation['banner'] ) ? $default_translation['banner'] : array(),
                    $base_defaults['banner']
                );
                $translation['categories'] = $this->sanitize_category_translations(
                    isset( $incoming['categories'] ) && is_array( $incoming['categories'] ) ? $incoming['categories'] : array(),
                    isset( $default_translation['categories'] ) && is_array( $default_translation['categories'] ) ? $default_translation['categories'] : array(),
                    $base_defaults['categories']
                );
                $translation['texts']      = $this->sanitize_frontend_texts(
                    isset( $incoming['texts'] ) && is_array( $incoming['texts'] ) ? $incoming['texts'] : array(),
                    isset( $default_translation['texts'] ) && is_array( $default_translation['texts'] )
                        ? $default_translation['texts']
                        : $this->get_frontend_texts_defaults( $language )
                );

                $sanitized[ $language ] = $translation;
            }

            return $sanitized;
        }

        /**
         * Sanitize retention days value.
         *
         * @param mixed $value   Incoming value.
         * @param int   $default Default retention.
         *
         * @return int
         */
        protected function sanitize_retention_days( $value, $default ) {
            if ( '' === $value ) {
                return (int) $default;
            }

            if ( is_string( $value ) ) {
                $value = trim( $value );
            }

            if ( ! is_numeric( $value ) ) {
                return (int) $default;
            }

            $value = (int) $value;

            if ( $value < 0 ) {
                return (int) $default;
            }

            if ( 0 === $value ) {
                return 0;
            }

            $minimum = (int) apply_filters( 'fp_privacy_consent_retention_min_days', 30 );

            if ( $minimum < 1 ) {
                $minimum = 1;
            }

            return max( $minimum, $value );
        }

        /**
         * Sanitize consent cookie duration value.
         *
         * @param mixed $value   Incoming value.
         * @param int   $default Default duration.
         *
         * @return int
         */
        protected function sanitize_cookie_duration_days( $value, $default ) {
            if ( '' === $value ) {
                return (int) $default;
            }

            if ( is_string( $value ) ) {
                $value = trim( $value );
            }

            if ( ! is_numeric( $value ) ) {
                return (int) $default;
            }

            $value = (int) $value;

            if ( $value < 0 ) {
                return (int) $default;
            }

            if ( 0 === $value ) {
                return 0;
            }

            $minimum = (int) apply_filters( 'fp_privacy_consent_cookie_min_days', 30 );

            if ( $minimum < 1 ) {
                $minimum = 1;
            }

            return max( $minimum, $value );
        }

        /**
         * Sanitize MySQL datetime strings.
         *
         * @param mixed $value Raw value.
         *
         * @return string
         */
        protected function sanitize_mysql_datetime( $value ) {
            if ( ! is_string( $value ) ) {
                return '';
            }

            $value = trim( $value );

            if ( '' === $value ) {
                return '';
            }

            if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ) {
                return $value;
            }

            return '';
        }

        /**
         * Render privacy policy editor.
         */
        public function render_privacy_editor() {
            $options = $this->get_settings();
            $translations = isset( $options['translations'] ) ? $options['translations'] : array();
            $languages    = $this->get_translation_languages( $options );

            if ( ! empty( $options['auto_generate'] ) ) {
                $last_generated = (int) get_option( self::GENERATION_TIME_OPTION, 0 );
                $message        = __( 'Questo contenuto viene generato automaticamente sulla base della configurazione del sito. Le modifiche manuali potrebbero essere sovrascritte al prossimo aggiornamento.', 'fp-privacy-cookie-policy' );

                if ( $last_generated ) {
                    $message .= ' ' . sprintf(
                        __( 'Ultima generazione: %s.', 'fp-privacy-cookie-policy' ),
                        wp_date( get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' ), $last_generated )
                    );
                }

                echo '<p class="description">' . esc_html( $message ) . '</p>';
            }

            echo '<h4>' . esc_html( $this->get_language_label( self::DEFAULT_LANGUAGE ) ) . '</h4>';
            wp_editor(
                $options['privacy_policy_content'],
                'fp_privacy_policy_content',
                array(
                    'textarea_name' => self::OPTION_KEY . '[privacy_policy_content]',
                    'textarea_rows' => 10,
                )
            );

            foreach ( $languages as $language ) {
                $translation = $this->get_translation_for_language( $translations, $language );
                $content     = isset( $translation['privacy_policy_content'] ) ? $translation['privacy_policy_content'] : '';
                $editor_id   = 'fp_privacy_policy_content_' . sanitize_key( $language );
                $field_name  = self::OPTION_KEY . '[translations][' . $language . '][privacy_policy_content]';

                echo '<h4>' . esc_html( $this->get_language_label( $language ) ) . '</h4>';
                wp_editor(
                    $content,
                    $editor_id,
                    array(
                        'textarea_name' => $field_name,
                        'textarea_rows' => 10,
                    )
                );
            }
        }

        /**
         * Render cookie policy editor.
         */
        public function render_cookie_editor() {
            $options = $this->get_settings();
            $translations = isset( $options['translations'] ) ? $options['translations'] : array();
            $languages    = $this->get_translation_languages( $options );

            if ( ! empty( $options['auto_generate'] ) ) {
                $last_generated = (int) get_option( self::GENERATION_TIME_OPTION, 0 );
                $message        = __( 'Questo contenuto viene generato automaticamente sulla base della configurazione del sito. Le modifiche manuali potrebbero essere sovrascritte al prossimo aggiornamento.', 'fp-privacy-cookie-policy' );

                if ( $last_generated ) {
                    $message .= ' ' . sprintf(
                        __( 'Ultima generazione: %s.', 'fp-privacy-cookie-policy' ),
                        wp_date( get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' ), $last_generated )
                    );
                }

                echo '<p class="description">' . esc_html( $message ) . '</p>';
            }

            echo '<h4>' . esc_html( $this->get_language_label( self::DEFAULT_LANGUAGE ) ) . '</h4>';
            wp_editor(
                $options['cookie_policy_content'],
                'fp_cookie_policy_content',
                array(
                    'textarea_name' => self::OPTION_KEY . '[cookie_policy_content]',
                    'textarea_rows' => 10,
                )
            );

            foreach ( $languages as $language ) {
                $translation = $this->get_translation_for_language( $translations, $language );
                $content     = isset( $translation['cookie_policy_content'] ) ? $translation['cookie_policy_content'] : '';
                $editor_id   = 'fp_cookie_policy_content_' . sanitize_key( $language );
                $field_name  = self::OPTION_KEY . '[translations][' . $language . '][cookie_policy_content]';

                echo '<h4>' . esc_html( $this->get_language_label( $language ) ) . '</h4>';
                wp_editor(
                    $content,
                    $editor_id,
                    array(
                        'textarea_name' => $field_name,
                        'textarea_rows' => 10,
                    )
                );
            }
        }

        /**
         * Render language manager field.
         */
        public function render_language_manager_field() {
            $options        = $this->get_settings();
            $selected       = isset( $options['active_languages'] ) && is_array( $options['active_languages'] )
                ? array_map( array( $this, 'normalize_language_code' ), $options['active_languages'] )
                : array();
            $translations   = isset( $options['translations'] ) && is_array( $options['translations'] )
                ? array_map( array( $this, 'normalize_language_code' ), array_keys( $options['translations'] ) )
                : array();
            $choices        = $this->get_language_choices( $options, $selected );
            $selected       = array_values( array_unique( array_filter( $selected, array( $this, 'filter_non_default_language' ) ) ) );
            $size           = min( 10, max( 4, count( $choices ) ) );
            ?>
            <div class="fp-language-manager">
                <p>
                    <label for="fp_active_languages">
                        <?php esc_html_e( 'Seleziona le lingue in cui vuoi gestire testi e traduzioni del banner.', 'fp-privacy-cookie-policy' ); ?>
                    </label>
                </p>
                <select id="fp_active_languages" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[active_languages][]" multiple size="<?php echo esc_attr( $size ); ?>" class="fp-language-select">
                    <?php foreach ( $choices as $code => $label ) : ?>
                        <?php $is_selected = in_array( $code, $selected, true ) || in_array( $code, $translations, true ); ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $is_selected ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="fp-language-manager__custom">
                    <label for="fp_custom_languages">
                        <?php esc_html_e( 'Aggiungi manualmente altri codici lingua (ISO 639-1) separati da virgola.', 'fp-privacy-cookie-policy' ); ?>
                    </label><br />
                    <input type="text" id="fp_custom_languages" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[custom_languages]" class="regular-text" placeholder="es, de, pt" />
                </p>
                <p class="description">
                    <?php esc_html_e( 'La lingua principale (Italiano) è sempre disponibile. Le lingue selezionate mostreranno i campi dedicati nelle sezioni di testo.', 'fp-privacy-cookie-policy' ); ?>
                </p>
            </div>
            <?php
        }

        /**
         * Helper callback used with array_filter to remove default language.
         *
         * @param string $language Language code.
         *
         * @return bool
         */
        protected function filter_non_default_language( $language ) {
            $language = $this->normalize_language_code( $language );

            return $language && self::DEFAULT_LANGUAGE !== $language;
        }

        /**
         * Render banner settings.
         */
        public function render_banner_settings() {
            $options       = $this->get_settings();
            $banner        = $options['banner'];
            $translations  = isset( $options['translations'] ) ? $options['translations'] : array();
            $languages     = $this->get_translation_languages( $options );
            $field_labels  = array(
                'banner_title'           => esc_html__( 'Titolo', 'fp-privacy-cookie-policy' ),
                'banner_message'         => esc_html__( 'Messaggio', 'fp-privacy-cookie-policy' ),
                'accept_all_label'       => esc_html__( 'Etichetta "Accetta tutti"', 'fp-privacy-cookie-policy' ),
                'reject_all_label'       => esc_html__( 'Etichetta "Rifiuta"', 'fp-privacy-cookie-policy' ),
                'preferences_label'      => esc_html__( 'Etichetta "Preferenze"', 'fp-privacy-cookie-policy' ),
                'save_preferences_label' => esc_html__( 'Etichetta "Salva"', 'fp-privacy-cookie-policy' ),
            );
            $textarea_fields = array( 'banner_message' );
            ?>
            <fieldset class="fp-banner-settings" data-fp-banner-language="<?php echo esc_attr( self::DEFAULT_LANGUAGE ); ?>">
                <h4>
                    <?php
                    printf(
                        esc_html__( 'Testo banner (%s)', 'fp-privacy-cookie-policy' ),
                        esc_html( $this->get_language_label( self::DEFAULT_LANGUAGE ) )
                    );
                    ?>
                </h4>
                <?php foreach ( $field_labels as $key => $label ) : ?>
                    <?php $field_id = 'fp_banner_' . $key; ?>
                    <p>
                        <label for="<?php echo esc_attr( $field_id ); ?>">
                            <strong><?php echo esc_html( $label ); ?></strong>
                        </label><br />
                        <?php if ( in_array( $key, $textarea_fields, true ) ) : ?>
                            <textarea class="large-text" rows="4" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][<?php echo esc_attr( $key ); ?>]" data-fp-preview-target="<?php echo esc_attr( $key ); ?>" data-fp-preview-language="<?php echo esc_attr( self::DEFAULT_LANGUAGE ); ?>"><?php echo esc_textarea( isset( $banner[ $key ] ) ? $banner[ $key ] : '' ); ?></textarea>
                        <?php else : ?>
                            <input type="text" class="regular-text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $banner[ $key ] ) ? $banner[ $key ] : '' ); ?>" data-fp-preview-target="<?php echo esc_attr( $key ); ?>" data-fp-preview-language="<?php echo esc_attr( self::DEFAULT_LANGUAGE ); ?>" />
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
                <p>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][show_reject]" value="1" <?php checked( $banner['show_reject'] ); ?> data-fp-preview-toggle="reject" />
                        <?php echo esc_html__( 'Mostra il pulsante "Rifiuta" nel banner principale', 'fp-privacy-cookie-policy' ); ?>
                    </label>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][show_preferences]" value="1" <?php checked( $banner['show_preferences'] ); ?> data-fp-preview-toggle="preferences" />
                        <?php echo esc_html__( 'Mostra il pulsante per aprire le preferenze direttamente nel banner', 'fp-privacy-cookie-policy' ); ?>
                    </label>
                </p>
                <?php
                $layout_value   = $this->sanitize_banner_layout( isset( $banner['layout'] ) ? $banner['layout'] : 'floating', 'floating' );
                $layout_options = array(
                    'floating'     => esc_html__( 'Riquadro fluttuante (in basso)', 'fp-privacy-cookie-policy' ),
                    'floating_top' => esc_html__( 'Riquadro fluttuante (in alto)', 'fp-privacy-cookie-policy' ),
                    'bar_bottom'   => esc_html__( 'Barra a tutta larghezza (in basso)', 'fp-privacy-cookie-policy' ),
                    'bar_top'      => esc_html__( 'Barra a tutta larghezza (in alto)', 'fp-privacy-cookie-policy' ),
                );
                ?>
                <p>
                    <label for="fp_banner_layout"><strong><?php esc_html_e( 'Layout del banner', 'fp-privacy-cookie-policy' ); ?></strong></label><br />
                    <select id="fp_banner_layout" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][layout]" data-fp-preview-layout-control>
                        <?php foreach ( $layout_options as $layout_key => $layout_label ) : ?>
                            <option value="<?php echo esc_attr( $layout_key ); ?>" <?php selected( $layout_key, $layout_value ); ?>><?php echo esc_html( $layout_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <?php
                $color_fields = array(
                    'background_color'     => array(
                        'label'    => esc_html__( 'Colore di sfondo', 'fp-privacy-cookie-policy' ),
                        'default'  => '#ffffff',
                        'property' => '--fp-banner-background',
                    ),
                    'text_color'           => array(
                        'label'    => esc_html__( 'Colore del testo', 'fp-privacy-cookie-policy' ),
                        'default'  => '#1f2933',
                        'property' => '--fp-banner-text',
                    ),
                    'accent_color'         => array(
                        'label'    => esc_html__( 'Colore principale (CTA)', 'fp-privacy-cookie-policy' ),
                        'default'  => '#2563eb',
                        'property' => '--fp-banner-accent',
                    ),
                    'secondary_color'      => array(
                        'label'    => esc_html__( 'Colore pulsante secondario', 'fp-privacy-cookie-policy' ),
                        'default'  => '#eef2ff',
                        'property' => '--fp-banner-secondary-bg',
                    ),
                    'secondary_text_color' => array(
                        'label'    => esc_html__( 'Testo pulsante secondario', 'fp-privacy-cookie-policy' ),
                        'default'  => '#1e3a8a',
                        'property' => '--fp-banner-secondary-text',
                    ),
                    'border_color'         => array(
                        'label'    => esc_html__( 'Colore bordo', 'fp-privacy-cookie-policy' ),
                        'default'  => '#dbeafe',
                        'property' => '--fp-banner-border',
                    ),
                );
                ?>
                <div class="fp-banner-design">
                    <h4><?php esc_html_e( 'Aspetto del banner', 'fp-privacy-cookie-policy' ); ?></h4>
                    <div class="fp-banner-design__grid">
                        <?php foreach ( $color_fields as $field_key => $field ) :
                            $field_id    = 'fp_banner_' . $field_key;
                            $color_value = isset( $banner[ $field_key ] ) ? sanitize_hex_color( $banner[ $field_key ] ) : '';
                            if ( ! $color_value ) {
                                $color_value = $field['default'];
                            }
                            ?>
                            <div class="fp-color-picker">
                                <label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
                                <div class="fp-color-picker__inputs">
                                    <input type="text" class="regular-text fp-color-picker__value" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][<?php echo esc_attr( $field_key ); ?>]" value="<?php echo esc_attr( $color_value ); ?>" data-fp-color-control data-fp-color-key="<?php echo esc_attr( $field_key ); ?>" data-fp-color-prop="<?php echo esc_attr( $field['property'] ); ?>" />
                                    <input type="color" class="fp-color-picker__preview" id="<?php echo esc_attr( $field_id ); ?>_preview" value="<?php echo esc_attr( $color_value ); ?>" data-fp-color-picker data-fp-color-key="<?php echo esc_attr( $field_key ); ?>" data-fp-color-prop="<?php echo esc_attr( $field['property'] ); ?>" data-fp-color-target="<?php echo esc_attr( $field_id ); ?>" />
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description"><?php esc_html_e( 'Inserisci colori in formato esadecimale (es. #1F2933) o utilizza i selettori per aggiornare l’anteprima in tempo reale.', 'fp-privacy-cookie-policy' ); ?></p>
                </div>
            </fieldset>

            <?php
            $preview_languages = array_merge( array( self::DEFAULT_LANGUAGE ), $languages );
            $preview_languages = array_values( array_unique( array_filter( $preview_languages ) ) );
            ?>
            <?php if ( ! empty( $preview_languages ) ) : ?>
                <div class="fp-banner-preview__controls">
                    <label for="fp_preview_language"><strong><?php esc_html_e( 'Anteprima lingua', 'fp-privacy-cookie-policy' ); ?></strong></label>
                    <select id="fp_preview_language" class="fp-preview-language-select" data-fp-preview-language-selector>
                        <?php foreach ( $preview_languages as $language_code ) : ?>
                            <option value="<?php echo esc_attr( $language_code ); ?>" <?php selected( $language_code, self::DEFAULT_LANGUAGE ); ?>>
                                <?php echo esc_html( $this->get_language_label( $language_code ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="fp-banner-preview" id="fp-banner-preview" aria-live="polite">
                <?php echo $this->get_banner_preview_markup( $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <?php foreach ( $languages as $language ) :
                $translation = $this->get_translation_for_language( $translations, $language );
                $values      = array_merge( $this->extract_banner_text_defaults( $banner ), isset( $translation['banner'] ) ? $translation['banner'] : array() );
                $lang_label  = $this->get_language_label( $language );
                ?>
                <fieldset class="fp-banner-settings" data-fp-banner-language="<?php echo esc_attr( $language ); ?>">
                    <h4>
                        <?php
                        printf(
                            esc_html__( 'Testo banner (%s)', 'fp-privacy-cookie-policy' ),
                            esc_html( $lang_label )
                        );
                        ?>
                    </h4>
                    <?php foreach ( $field_labels as $key => $label ) : ?>
                        <?php $field_id = 'fp_banner_' . $key . '_' . sanitize_key( $language ); ?>
                        <p>
                            <label for="<?php echo esc_attr( $field_id ); ?>">
                                <strong><?php echo esc_html( $label ); ?></strong>
                            </label><br />
                                <?php if ( in_array( $key, $textarea_fields, true ) ) : ?>
                                    <textarea class="large-text" rows="4" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][<?php echo esc_attr( $language ); ?>][banner][<?php echo esc_attr( $key ); ?>]" data-fp-preview-target="<?php echo esc_attr( $key ); ?>" data-fp-preview-language="<?php echo esc_attr( $language ); ?>"><?php echo esc_textarea( isset( $values[ $key ] ) ? $values[ $key ] : '' ); ?></textarea>
                                <?php else : ?>
                                    <input type="text" class="regular-text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][<?php echo esc_attr( $language ); ?>][banner][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $values[ $key ] ) ? $values[ $key ] : '' ); ?>" data-fp-preview-target="<?php echo esc_attr( $key ); ?>" data-fp-preview-language="<?php echo esc_attr( $language ); ?>" />
                            <?php endif; ?>
                        </p>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
            <?php
        }

        /**
         * Build markup for the live banner preview in the admin area.
         *
         * @param array $options Current plugin options.
         *
         * @return string
         */
        protected function get_banner_preview_markup( array $options ) {
            $banner     = isset( $options['banner'] ) ? $options['banner'] : array();
            $categories = isset( $options['categories'] ) ? $options['categories'] : array();
            $texts      = $this->get_frontend_texts( self::DEFAULT_LANGUAGE, $options );
            $date       = current_time( 'timestamp' );
            $theme_vars = $this->get_banner_theme_variables( $banner );
            $style_attr = $this->build_css_custom_properties( $theme_vars );
            $layout_key = $this->get_banner_layout_suffix( $banner );
            $layout_class = $this->get_preview_layout_class( $banner );

            $visible_categories = array();
            foreach ( $categories as $key => $category ) {
                if ( empty( $category['enabled'] ) ) {
                    continue;
                }

                $visible_categories[ $key ] = $category;

                if ( count( $visible_categories ) >= 3 ) {
                    break;
                }
            }

            ob_start();
            ?>
            <div class="fp-banner-preview__wrapper <?php echo esc_attr( $layout_class ); ?>" data-fp-preview-root data-default-language="<?php echo esc_attr( self::DEFAULT_LANGUAGE ); ?>" data-fp-preview-layout="<?php echo esc_attr( $layout_key ); ?>"<?php if ( $style_attr ) : ?> style="<?php echo esc_attr( $style_attr ); ?>"<?php endif; ?>>
                <div class="fp-banner-preview__panel" data-fp-preview-section="banner">
                    <div class="fp-banner-preview__content">
                        <h4 data-fp-preview="banner_title"><?php echo esc_html( isset( $banner['banner_title'] ) ? $banner['banner_title'] : '' ); ?></h4>
                        <div class="fp-banner-preview__message" data-fp-preview="banner_message"><?php echo wpautop( wp_kses_post( isset( $banner['banner_message'] ) ? $banner['banner_message'] : '' ) ); ?></div>
                    </div>
                    <div class="fp-banner-preview__actions">
                        <button type="button" class="button button-primary" data-fp-preview="accept_all_label"><?php echo esc_html( isset( $banner['accept_all_label'] ) ? $banner['accept_all_label'] : '' ); ?></button>
                        <button type="button" class="button<?php echo empty( $banner['show_reject'] ) ? ' is-hidden' : ''; ?>" data-fp-preview="reject_all_label" data-fp-toggle-target="reject"><?php echo esc_html( isset( $banner['reject_all_label'] ) ? $banner['reject_all_label'] : '' ); ?></button>
                        <button type="button" class="button button-secondary<?php echo empty( $banner['show_preferences'] ) ? ' is-hidden' : ''; ?>" data-fp-preview="preferences_label" data-fp-toggle-target="preferences"><?php echo esc_html( isset( $banner['preferences_label'] ) ? $banner['preferences_label'] : '' ); ?></button>
                    </div>
                </div>

                <div class="fp-banner-preview__modal" data-fp-preview-section="modal">
                    <div class="fp-banner-preview__modal-header">
                        <h4 data-fp-preview="modal_title"><?php echo esc_html( isset( $texts['modal_title'] ) ? $texts['modal_title'] : '' ); ?></h4>
                        <button type="button" class="button-link" data-fp-preview="modal_close"><?php echo esc_html( isset( $texts['modal_close'] ) ? $texts['modal_close'] : '' ); ?></button>
                    </div>
                    <p data-fp-preview="modal_intro"><?php echo esc_html( isset( $texts['modal_intro'] ) ? $texts['modal_intro'] : '' ); ?></p>

                    <?php if ( ! empty( $visible_categories ) ) : ?>
                        <ul class="fp-banner-preview__categories">
                            <?php foreach ( $visible_categories as $key => $category ) :
                                $category_label = isset( $category['label'] ) ? $category['label'] : '';
                                $services       = isset( $category['services'] ) ? $category['services'] : '';
                                $is_required    = ! empty( $category['required'] );
                                ?>
                                <li data-fp-preview-category="<?php echo esc_attr( $key ); ?>">
                                    <h5 data-fp-preview-category-field="label"><?php echo esc_html( $category_label ); ?></h5>
                                    <p data-fp-preview-category-field="description"><?php echo esc_html( wp_strip_all_tags( isset( $category['description'] ) ? $category['description'] : '' ) ); ?></p>
                                    <p class="fp-banner-preview__services<?php echo $services ? '' : ' is-hidden'; ?>" data-fp-preview-category-field="services">
                                        <strong data-fp-preview="services_included"><?php echo esc_html( isset( $texts['services_included'] ) ? $texts['services_included'] : '' ); ?></strong>:
                                        <span class="fp-banner-preview__services-text"><?php echo esc_html( $services ); ?></span>
                                    </p>
                                    <div class="fp-banner-preview__category-meta">
                                        <span class="fp-banner-preview__badge<?php echo $is_required ? '' : ' is-hidden'; ?>" data-fp-preview="always_active" data-fp-category-element="required"><?php echo esc_html( isset( $texts['always_active'] ) ? $texts['always_active'] : '' ); ?></span>
                                        <span class="fp-banner-preview__toggle-label<?php echo $is_required ? ' is-hidden' : ''; ?>" data-fp-preview="toggle_aria" data-fp-category-label="<?php echo esc_attr( $category_label ); ?>" data-fp-category-element="toggle"><?php echo esc_html( isset( $texts['toggle_aria'] ) ? sprintf( $texts['toggle_aria'], $category_label ) : '' ); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="fp-banner-preview__empty"><?php esc_html_e( 'Aggiungi almeno una categoria per vedere l\'anteprima delle preferenze.', 'fp-privacy-cookie-policy' ); ?></p>
                    <?php endif; ?>

                    <div class="fp-banner-preview__footer">
                        <button type="button" class="button button-primary" data-fp-preview="save_preferences_label"><?php echo esc_html( isset( $banner['save_preferences_label'] ) ? $banner['save_preferences_label'] : '' ); ?></button>
                        <span class="fp-banner-preview__updated"><strong data-fp-preview="updated_at"><?php echo esc_html( isset( $texts['updated_at'] ) ? $texts['updated_at'] : '' ); ?></strong>: <?php echo esc_html( wp_date( get_option( 'date_format', 'Y-m-d' ), $date ) ); ?></span>
                    </div>
                </div>

                <div class="fp-banner-preview__manage">
                    <button type="button" class="button button-link" data-fp-preview="manage_consent"><?php echo esc_html( isset( $texts['manage_consent'] ) ? $texts['manage_consent'] : '' ); ?></button>
                </div>
            </div>
            <?php

            return ob_get_clean();
        }

        /**
         * Render banner position field.
         */
        public function render_banner_hook_field() {
            $options  = $this->get_settings();
            $selected = isset( $options['render_hook'] ) ? $options['render_hook'] : 'footer';
            ?>
            <p>
                <label for="fp_render_hook"><strong><?php esc_html_e( 'Seleziona il punto di aggancio del banner', 'fp-privacy-cookie-policy' ); ?></strong></label><br />
                <select id="fp_render_hook" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[render_hook]">
                    <option value="footer" <?php selected( 'footer', $selected ); ?>><?php esc_html_e( 'Footer del tema (default)', 'fp-privacy-cookie-policy' ); ?></option>
                    <option value="body_open" <?php selected( 'body_open', $selected ); ?>><?php esc_html_e( 'All\'apertura del tag &lt;body&gt;', 'fp-privacy-cookie-policy' ); ?></option>
                    <option value="manual" <?php selected( 'manual', $selected ); ?>><?php esc_html_e( 'Inserimento manuale tramite blocco/shortcode', 'fp-privacy-cookie-policy' ); ?></option>
                </select>
            </p>
            <p class="description">
                <?php esc_html_e( 'Scegli "manuale" se vuoi gestire il banner in modo personalizzato con lo shortcode [fp_cookie_banner] o con il blocco Gutenberg dedicato.', 'fp-privacy-cookie-policy' ); ?>
            </p>
            <?php
        }

        /**
         * Render preview mode field.
         */
        public function render_preview_mode_field() {
            $options      = $this->get_settings();
            $preview_mode = ! empty( $options['preview_mode'] );
            $preview_url  = add_query_arg( self::PREVIEW_QUERY_KEY, '1', home_url( '/' ) );
            ?>
            <p>
                <label for="fp_preview_mode">
                    <input type="checkbox" id="fp_preview_mode" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[preview_mode]" value="1" <?php checked( $preview_mode ); ?> />
                    <?php esc_html_e( 'Abilita la modalità anteprima per gli amministratori', 'fp-privacy-cookie-policy' ); ?>
                </label>
            </p>
            <p class="description"><?php esc_html_e( 'Mostra sempre il banner agli amministratori senza salvare le scelte per verificare layout, testi e script.', 'fp-privacy-cookie-policy' ); ?></p>
            <p class="description">
                <?php
                printf(
                    /* translators: %s is the preview query string parameter. */
                    esc_html__( 'Puoi attivare l\'anteprima anche visitando il sito con %s.', 'fp-privacy-cookie-policy' ),
                    '<code>?' . esc_html( self::PREVIEW_QUERY_KEY ) . '=1</code>'
                );
                ?>
            </p>
            <p class="description">
                <a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Apri il sito in modalità anteprima', 'fp-privacy-cookie-policy' ); ?></a>
            </p>
            <?php
        }

        /**
         * Render frontend interface texts controls.
         */
        public function render_frontend_texts_field() {
            $options            = $this->get_settings();
            $base_texts         = isset( $options['texts'] ) && is_array( $options['texts'] ) ? $options['texts'] : array();
            $text_defaults      = $this->get_frontend_texts_defaults( self::DEFAULT_LANGUAGE );
            $texts              = array_merge( $text_defaults, $base_texts );
            $translations       = isset( $options['translations'] ) ? $options['translations'] : array();
            $languages          = $this->get_translation_languages( $options );
            $field_labels       = array(
                'modal_title'       => esc_html__( 'Titolo finestra', 'fp-privacy-cookie-policy' ),
                'modal_intro'       => esc_html__( 'Messaggio introduttivo', 'fp-privacy-cookie-policy' ),
                'modal_close'       => esc_html__( 'Etichetta pulsante chiusura', 'fp-privacy-cookie-policy' ),
                'services_included' => esc_html__( 'Etichetta "Servizi inclusi"', 'fp-privacy-cookie-policy' ),
                'always_active'     => esc_html__( 'Etichetta "Sempre attivo"', 'fp-privacy-cookie-policy' ),
                'toggle_aria'       => esc_html__( 'Descrizione accessibilità toggle', 'fp-privacy-cookie-policy' ),
                'manage_consent'    => esc_html__( 'Etichetta pulsante gestione', 'fp-privacy-cookie-policy' ),
                'updated_at'        => esc_html__( 'Etichetta stato aggiornamento', 'fp-privacy-cookie-policy' ),
            );
            $textarea_fields    = array( 'modal_intro' );
            $placeholder_notice = esc_html__( 'Ricorda di mantenere il segnaposto %s per il nome categoria.', 'fp-privacy-cookie-policy' );
            ?>
            <fieldset class="fp-banner-settings fp-interface-texts">
                <h4>
                    <?php
                    printf(
                        esc_html__( 'Testi interfaccia (%s)', 'fp-privacy-cookie-policy' ),
                        esc_html( $this->get_language_label( self::DEFAULT_LANGUAGE ) )
                    );
                    ?>
                </h4>
                <div class="fp-texts-grid">
                    <?php foreach ( $field_labels as $key => $label ) : ?>
                        <?php $field_id = 'fp_text_' . $key; ?>
                        <p>
                            <label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label><br />
                            <?php if ( in_array( $key, $textarea_fields, true ) ) : ?>
                                <textarea class="large-text" rows="3" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[texts][<?php echo esc_attr( $key ); ?>]" data-fp-preview-target="<?php echo esc_attr( $key ); ?>" data-fp-preview-language="<?php echo esc_attr( self::DEFAULT_LANGUAGE ); ?>"><?php echo esc_textarea( isset( $texts[ $key ] ) ? $texts[ $key ] : '' ); ?></textarea>
                            <?php else : ?>
                                <input type="text" class="regular-text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[texts][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $texts[ $key ] ) ? $texts[ $key ] : '' ); ?>" data-fp-preview-target="<?php echo esc_attr( $key ); ?>" data-fp-preview-language="<?php echo esc_attr( self::DEFAULT_LANGUAGE ); ?>" />
                            <?php endif; ?>
                            <?php if ( 'toggle_aria' === $key ) : ?>
                                <span class="description">
                                    <?php printf( esc_html( $placeholder_notice ), '<code>%s</code>' ); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <?php foreach ( $languages as $language ) :
                $translation       = $this->get_translation_for_language( $translations, $language );
                $language_defaults = $this->get_frontend_texts_defaults( $language );
                $language_texts    = isset( $translation['texts'] ) && is_array( $translation['texts'] )
                    ? array_merge( $language_defaults, $translation['texts'] )
                    : $language_defaults;
                $field_suffix      = sanitize_key( $language );
                ?>
                <fieldset class="fp-banner-settings fp-interface-texts" data-fp-text-language="<?php echo esc_attr( $language ); ?>">
                    <h4>
                        <?php
                        printf(
                            esc_html__( 'Testi interfaccia (%s)', 'fp-privacy-cookie-policy' ),
                            esc_html( $this->get_language_label( $language ) )
                        );
                        ?>
                    </h4>
                    <div class="fp-texts-grid">
                        <?php foreach ( $field_labels as $key => $label ) : ?>
                            <?php $field_id = 'fp_text_' . $key . '_' . $field_suffix; ?>
                            <p>
                                <label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label><br />
                                <?php if ( in_array( $key, $textarea_fields, true ) ) : ?>
                                    <textarea class="large-text" rows="3" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][<?php echo esc_attr( $language ); ?>][texts][<?php echo esc_attr( $key ); ?>]" data-fp-preview-target="<?php echo esc_attr( $key ); ?>" data-fp-preview-language="<?php echo esc_attr( $language ); ?>"><?php echo esc_textarea( isset( $language_texts[ $key ] ) ? $language_texts[ $key ] : '' ); ?></textarea>
                                <?php else : ?>
                                    <input type="text" class="regular-text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][<?php echo esc_attr( $language ); ?>][texts][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $language_texts[ $key ] ) ? $language_texts[ $key ] : '' ); ?>" data-fp-preview-target="<?php echo esc_attr( $key ); ?>" data-fp-preview-language="<?php echo esc_attr( $language ); ?>" />
                                <?php endif; ?>
                                <?php if ( 'toggle_aria' === $key ) : ?>
                                    <span class="description">
                                        <?php printf( esc_html( $placeholder_notice ), '<code>%s</code>' ); ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>
            <?php
        }

        /**
         * Render retention field.
         */
        public function render_retention_field() {
            $options        = $this->get_settings();
            $retention_days = isset( $options['retention_days'] ) ? (int) $options['retention_days'] : 0;
            ?>
            <p>
                <label for="fp_retention_days">
                    <input type="number" class="small-text" id="fp_retention_days" min="0" step="1" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[retention_days]" value="<?php echo esc_attr( $retention_days ); ?>" />
                    <?php echo esc_html__( 'Giorni di conservazione del registro', 'fp-privacy-cookie-policy' ); ?>
                </label>
            </p>
            <p class="description">
                <?php echo esc_html__( 'Imposta 0 per disattivare la pulizia automatica. Valori inferiori a 30 giorni vengono automaticamente aumentati.', 'fp-privacy-cookie-policy' ); ?>
            </p>
            <?php
        }

        /**
         * Render consent cookie duration field.
         */
        public function render_consent_cookie_field() {
            $options            = $this->get_settings();
            $consent_cookie_days = isset( $options['consent_cookie_days'] ) ? (int) $options['consent_cookie_days'] : 0;
            ?>
            <p>
                <label for="fp_consent_cookie_days">
                    <input type="number" class="small-text" id="fp_consent_cookie_days" min="0" step="1" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[consent_cookie_days]" value="<?php echo esc_attr( $consent_cookie_days ); ?>" />
                    <?php echo esc_html__( 'Giorni di validità del consenso', 'fp-privacy-cookie-policy' ); ?>
                </label>
            </p>
            <p class="description">
                <?php echo esc_html__( 'Imposta 0 per chiedere il consenso a ogni sessione. Valori inferiori a 30 giorni vengono automaticamente aumentati, ma puoi modificare la soglia con il filtro fp_privacy_consent_cookie_min_days.', 'fp-privacy-cookie-policy' ); ?>
            </p>
            <?php
        }

        /**
         * Render categories fields.
         */
        public function render_categories_fields() {
            $options    = $this->get_settings();
            $categories = $options['categories'];
            $translations = isset( $options['translations'] ) ? $options['translations'] : array();
            $languages    = $this->get_translation_languages( $options );
            ?>
            <div class="fp-categories">
                <?php foreach ( $categories as $key => $category ) : ?>
                    <fieldset class="fp-category" id="fp_category_<?php echo esc_attr( $key ); ?>">
                        <legend><strong><?php echo esc_html( $category['label'] ); ?></strong></legend>
                        <p>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $category['enabled'] ); ?> <?php disabled( $category['required'] ); ?> data-fp-preview-category="<?php echo esc_attr( $key ); ?>" data-fp-preview-category-toggle="enabled" />
                                <?php echo esc_html__( 'Mostra categoria nelle preferenze', 'fp-privacy-cookie-policy' ); ?>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][required]" value="1" <?php checked( $category['required'] ); ?> <?php disabled( $category['required'] ); ?> data-fp-preview-category="<?php echo esc_attr( $key ); ?>" data-fp-preview-category-toggle="required" />
                                <?php echo esc_html__( 'Necessario (non disattivabile)', 'fp-privacy-cookie-policy' ); ?>
                            </label>
                        </p>
                        <p>
                            <label for="fp_category_<?php echo esc_attr( $key ); ?>_description"><?php echo esc_html__( 'Descrizione', 'fp-privacy-cookie-policy' ); ?></label><br />
                            <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key ); ?>_description" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][description]" data-fp-preview-category="<?php echo esc_attr( $key ); ?>" data-fp-preview-field="description" data-fp-preview-language="<?php echo esc_attr( self::DEFAULT_LANGUAGE ); ?>"><?php echo esc_textarea( $category['description'] ); ?></textarea>
                        </p>
                        <p>
                            <label for="fp_category_<?php echo esc_attr( $key ); ?>_services"><?php echo esc_html__( 'Servizi e cookie inclusi', 'fp-privacy-cookie-policy' ); ?></label><br />
                            <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key ); ?>_services" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][services]" data-fp-preview-category="<?php echo esc_attr( $key ); ?>" data-fp-preview-field="services" data-fp-preview-language="<?php echo esc_attr( self::DEFAULT_LANGUAGE ); ?>"><?php echo esc_textarea( $category['services'] ); ?></textarea>
                            <span class="description"><?php echo esc_html__( 'Indica ad esempio strumenti di analytics, pixel e durata dei cookie per agevolare la documentazione.', 'fp-privacy-cookie-policy' ); ?></span>
                        </p>
                        <?php foreach ( $languages as $language ) :
                            $translation       = $this->get_translation_for_language( $translations, $language );
                            $translated_values = isset( $translation['categories'][ $key ] ) && is_array( $translation['categories'][ $key ] )
                                ? $translation['categories'][ $key ]
                                : array();
                            $defaults = array(
                                'label'       => isset( $category['label'] ) ? $category['label'] : '',
                                'description' => isset( $category['description'] ) ? $category['description'] : '',
                                'services'    => isset( $category['services'] ) ? $category['services'] : '',
                            );
                            $translated_values = array_merge( $defaults, $translated_values );
                            $suffix            = sanitize_key( $language );
                            ?>
                            <details class="fp-category-translation">
                                <summary>
                                    <?php
                                    printf(
                                        esc_html__( 'Traduzione categoria (%s)', 'fp-privacy-cookie-policy' ),
                                        esc_html( $this->get_language_label( $language ) )
                                    );
                                    ?>
                                </summary>
                                <p>
                                    <label for="fp_category_<?php echo esc_attr( $key ); ?>_label_<?php echo esc_attr( $suffix ); ?>"><?php echo esc_html__( 'Nome categoria', 'fp-privacy-cookie-policy' ); ?></label><br />
                                    <input type="text" class="regular-text" id="fp_category_<?php echo esc_attr( $key ); ?>_label_<?php echo esc_attr( $suffix ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][<?php echo esc_attr( $language ); ?>][categories][<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( isset( $translated_values['label'] ) ? $translated_values['label'] : '' ); ?>" data-fp-preview-category="<?php echo esc_attr( $key ); ?>" data-fp-preview-field="label" data-fp-preview-language="<?php echo esc_attr( $language ); ?>" />
                                </p>
                                <p>
                                    <label for="fp_category_<?php echo esc_attr( $key ); ?>_description_<?php echo esc_attr( $suffix ); ?>"><?php echo esc_html__( 'Descrizione', 'fp-privacy-cookie-policy' ); ?></label><br />
                                    <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key ); ?>_description_<?php echo esc_attr( $suffix ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][<?php echo esc_attr( $language ); ?>][categories][<?php echo esc_attr( $key ); ?>][description]" data-fp-preview-category="<?php echo esc_attr( $key ); ?>" data-fp-preview-field="description" data-fp-preview-language="<?php echo esc_attr( $language ); ?>"><?php echo esc_textarea( isset( $translated_values['description'] ) ? $translated_values['description'] : '' ); ?></textarea>
                                </p>
                                <p>
                                    <label for="fp_category_<?php echo esc_attr( $key ); ?>_services_<?php echo esc_attr( $suffix ); ?>"><?php echo esc_html__( 'Servizi e cookie inclusi', 'fp-privacy-cookie-policy' ); ?></label><br />
                                    <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key ); ?>_services_<?php echo esc_attr( $suffix ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][<?php echo esc_attr( $language ); ?>][categories][<?php echo esc_attr( $key ); ?>][services]" data-fp-preview-category="<?php echo esc_attr( $key ); ?>" data-fp-preview-field="services" data-fp-preview-language="<?php echo esc_attr( $language ); ?>"><?php echo esc_textarea( isset( $translated_values['services'] ) ? $translated_values['services'] : '' ); ?></textarea>
                                </p>
                            </details>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endforeach; ?>
            </div>
            <?php
        }

        /**
         * Render Google consent defaults.
         */
        public function render_google_defaults() {
            $options         = $this->get_settings();
            $google_defaults = $options['google_defaults'];
            ?>
            <table class="widefat striped" id="fp_google_defaults">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Segnale', 'fp-privacy-cookie-policy' ); ?></th>
                        <th><?php echo esc_html__( 'Valore di default', 'fp-privacy-cookie-policy' ); ?></th>
                        <th><?php echo esc_html__( 'Descrizione', 'fp-privacy-cookie-policy' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $google_defaults as $signal => $value ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $signal ); ?></code></td>
                            <td>
                                <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[google_defaults][<?php echo esc_attr( $signal ); ?>]">
                                    <option value="granted" <?php selected( 'granted', $value ); ?>><?php echo esc_html__( 'Granted', 'fp-privacy-cookie-policy' ); ?></option>
                                    <option value="denied" <?php selected( 'denied', $value ); ?>><?php echo esc_html__( 'Denied', 'fp-privacy-cookie-policy' ); ?></option>
                                </select>
                            </td>
                            <td><?php echo esc_html( $this->describe_google_signal( $signal ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        /**
         * Describe Google signal.
         *
         * @param string $signal Signal.
         *
         * @return string
         */
        protected function describe_google_signal( $signal ) {
            $descriptions = array(
                'analytics_storage'   => __( 'Abilita la memorizzazione dei cookie per Google Analytics e servizi di misurazione.', 'fp-privacy-cookie-policy' ),
                'ad_storage'          => __( 'Abilita i cookie pubblicitari e il retargeting (ad esempio Google Ads).', 'fp-privacy-cookie-policy' ),
                'ad_user_data'        => __( 'Permette di inviare dati utente a Google per finalità pubblicitarie.', 'fp-privacy-cookie-policy' ),
                'ad_personalization'  => __( 'Consente la personalizzazione degli annunci in base al comportamento.', 'fp-privacy-cookie-policy' ),
                'functionality_storage' => __( 'Cookie per ricordare preferenze, lingua e altre funzionalità.', 'fp-privacy-cookie-policy' ),
                'security_storage'    => __( 'Cookie destinati alla prevenzione delle frodi e alla sicurezza.', 'fp-privacy-cookie-policy' ),
            );

            return isset( $descriptions[ $signal ] ) ? $descriptions[ $signal ] : '';
        }

        /**
         * Flush cached settings and localized data.
         */
        public function flush_settings_cache() {
            $this->settings_cache         = null;
            $this->localized_cache        = array();
            $this->language_labels        = array();
            $this->consent_log_categories = null;
        }

        /**
         * Get plugin settings.
         *
         * @return array
         */
        public function get_settings() {
            if ( null !== $this->settings_cache ) {
                return $this->settings_cache;
            }

            $defaults = $this->get_default_settings();
            $options  = get_option( self::OPTION_KEY, array() );

            $this->settings_cache = wp_parse_args( $options, $defaults );

            return $this->settings_cache;
        }

        /**
         * Plugin default settings.
         *
         * @return array
         */
        public function get_default_settings() {
            return array(
                'privacy_policy_content' => __( '<h2>Informativa Privacy</h2><p>Inserisci qui il testo della tua informativa privacy conforme al GDPR, includendo i diritti dell\'interessato, i dati di contatto del titolare e le finalità del trattamento.</p>', 'fp-privacy-cookie-policy' ),
                'cookie_policy_content'  => __( '<h2>Informativa Cookie</h2><p>Descrivi i cookie utilizzati dal sito, le finalità e la base giuridica del trattamento. Ricorda di aggiornare periodicamente questo elenco.</p>', 'fp-privacy-cookie-policy' ),
                'auto_generate'          => true,
                'render_hook'            => 'footer',
                'preview_mode'           => false,
                'banner'                 => array(
                    'banner_title'          => __( 'Rispettiamo la tua privacy', 'fp-privacy-cookie-policy' ),
                    'banner_message'        => __( 'Utilizziamo cookie tecnici e, previo consenso, cookie di profilazione e di terze parti per migliorare l\'esperienza di navigazione. Puoi gestire le tue preferenze in qualsiasi momento.', 'fp-privacy-cookie-policy' ),
                    'accept_all_label'      => __( 'Accetta tutto', 'fp-privacy-cookie-policy' ),
                    'reject_all_label'      => __( 'Rifiuta', 'fp-privacy-cookie-policy' ),
                    'preferences_label'     => __( 'Preferenze', 'fp-privacy-cookie-policy' ),
                    'save_preferences_label'=> __( 'Salva preferenze', 'fp-privacy-cookie-policy' ),
                    'show_reject'           => true,
                    'show_preferences'      => true,
                    'layout'                => 'floating',
                    'background_color'      => '#ffffff',
                    'text_color'            => '#1f2933',
                    'accent_color'          => '#2563eb',
                    'secondary_color'       => '#eef2ff',
                    'secondary_text_color'  => '#1e3a8a',
                    'border_color'          => '#dbeafe',
                ),
                'categories'             => array(
                    'necessary'     => array(
                        'label'       => __( 'Necessari', 'fp-privacy-cookie-policy' ),
                        'description' => __( 'Cookie indispensabili per il funzionamento del sito e la fornitura del servizio.', 'fp-privacy-cookie-policy' ),
                        'services'    => __( "WordPress (sessione), cookie di autenticazione, salvataggio preferenze.", 'fp-privacy-cookie-policy' ),
                        'required'    => true,
                        'enabled'     => true,
                    ),
                    'preferences'   => array(
                        'label'       => __( 'Preferenze', 'fp-privacy-cookie-policy' ),
                        'description' => __( 'Consentono al sito di ricordare le scelte effettuate dall\'utente, come la lingua o la regione.', 'fp-privacy-cookie-policy' ),
                        'services'    => '',
                        'required'    => false,
                        'enabled'     => true,
                    ),
                    'statistics'    => array(
                        'label'       => __( 'Statistiche', 'fp-privacy-cookie-policy' ),
                        'description' => __( 'Aiutano a capire come i visitatori interagiscono con il sito raccogliendo e trasmettendo informazioni in forma anonima.', 'fp-privacy-cookie-policy' ),
                        'services'    => __( 'Google Analytics 4 (2 anni), Matomo (13 mesi).', 'fp-privacy-cookie-policy' ),
                        'required'    => false,
                        'enabled'     => true,
                    ),
                    'marketing'     => array(
                        'label'       => __( 'Marketing', 'fp-privacy-cookie-policy' ),
                        'description' => __( 'Vengono utilizzati per tracciare i visitatori e proporre annunci personalizzati.', 'fp-privacy-cookie-policy' ),
                        'services'    => __( 'Google Ads, Meta Pixel, TikTok Pixel.', 'fp-privacy-cookie-policy' ),
                        'required'    => false,
                        'enabled'     => true,
                    ),
                ),
                'texts'                  => $this->get_frontend_texts_defaults( self::DEFAULT_LANGUAGE ),
                'active_languages'      => array( 'en' ),
                'translations'          => array(
                    'en' => array(
                        'privacy_policy_content' => '<h2>Privacy Notice</h2><p>Provide the text of your GDPR-compliant privacy notice here, including data subject rights, controller contact details and the purposes of processing.</p>',
                        'cookie_policy_content'  => '<h2>Cookie Policy</h2><p>Describe the cookies used by the site, their purposes and the legal basis for processing. Remember to keep this list up to date.</p>',
                        'banner'                 => array(
                            'banner_title'           => 'We respect your privacy',
                            'banner_message'         => 'We use technical cookies and, subject to consent, profiling and third-party cookies to improve your browsing experience. You can manage your preferences at any time.',
                            'accept_all_label'       => 'Accept all',
                            'reject_all_label'       => 'Reject',
                            'preferences_label'      => 'Preferences',
                            'save_preferences_label' => 'Save preferences',
                        ),
                        'categories'             => array(
                            'necessary'   => array(
                                'label'       => 'Necessary',
                                'description' => 'Cookies that are essential for the website to function and to deliver the requested service.',
                                'services'    => 'WordPress (session), authentication cookies, preference storage.',
                            ),
                            'preferences' => array(
                                'label'       => 'Preferences',
                                'description' => 'Allow the site to remember the choices made by the user, such as language or region.',
                                'services'    => '',
                            ),
                            'statistics'  => array(
                                'label'       => 'Statistics',
                                'description' => 'Help us understand how visitors interact with the site by collecting and transmitting information anonymously.',
                                'services'    => 'Google Analytics 4 (2 years), Matomo (13 months).',
                            ),
                            'marketing'   => array(
                                'label'       => 'Marketing',
                                'description' => 'Used to track visitors and deliver personalised advertisements.',
                                'services'    => 'Google Ads, Meta Pixel, TikTok Pixel.',
                            ),
                        ),
                        'texts'                 => $this->get_frontend_texts_defaults( 'en' ),
                    ),
                ),
                'retention_days'        => 365,
                'consent_cookie_days'   => 180,
                'consent_revision'      => 1,
                'consent_revision_updated_at' => '',
                'google_defaults'        => array(
                    'analytics_storage'    => 'denied',
                    'ad_storage'           => 'denied',
                    'ad_user_data'         => 'denied',
                    'ad_personalization'   => 'denied',
                    'functionality_storage'=> 'granted',
                    'security_storage'     => 'granted',
                ),
            );
        }

        /**
         * Enqueue admin assets.
         */
        public function enqueue_admin_assets( $hook ) {
            $is_plugin_screen = 'toplevel_page_fp-privacy-cookie-policy' === $hook;
            $is_dashboard     = 'index.php' === $hook;

            if ( ! $is_plugin_screen && ! $is_dashboard ) {
                return;
            }

            $admin_style = 'assets/css/admin.css';

            if ( $this->asset_exists( $admin_style ) ) {
                wp_enqueue_style(
                    'fp-privacy-admin',
                    $this->get_asset_url( $admin_style ),
                    array(),
                    $this->get_asset_version( $admin_style )
                );
            } else {
                $this->flag_missing_asset( $admin_style, array(
                    'label'    => __( 'Stili dell’interfaccia di amministrazione', 'fp-privacy-cookie-policy' ),
                    'scope'    => 'admin',
                    'severity' => 'warning',
                ) );
            }

            if ( ! $is_plugin_screen ) {
                return;
            }

            $admin_script = 'assets/js/admin.js';
            if ( $this->asset_exists( $admin_script ) ) {
                wp_enqueue_script(
                    'fp-privacy-admin',
                    $this->get_asset_url( $admin_script ),
                    array(),
                    $this->get_asset_version( $admin_script ),
                    true
                );
            } else {
                $this->flag_missing_asset( $admin_script, array(
                    'label'    => __( 'Script dell’interfaccia di amministrazione', 'fp-privacy-cookie-policy' ),
                    'scope'    => 'admin',
                    'severity' => 'info',
                ) );
            }

            if ( wp_script_is( 'fp-privacy-admin', 'registered' ) || wp_script_is( 'fp-privacy-admin', 'enqueued' ) ) {
                wp_localize_script(
                    'fp-privacy-admin',
                    'fpPrivacyAdmin',
                    array(
                        'wizardSteps' => $this->get_onboarding_steps(),
                    )
                );
            }
        }

        /**
         * Enqueue frontend assets.
         */
        public function enqueue_frontend_assets() {
            $options        = $this->get_settings();
            $localized      = $this->get_localized_settings();
            $text_values    = isset( $localized['texts'] ) && is_array( $localized['texts'] )
                ? $localized['texts']
                : $this->get_frontend_texts( $localized['language'], $options );
            $cookie_options = $this->get_frontend_cookie_options( $options );
            $preview_mode   = $this->is_preview_mode_active();

            $frontend_style  = 'assets/css/banner.css';
            $frontend_script = 'assets/js/fp-consent.js';

            if ( $this->asset_exists( $frontend_style ) ) {
                wp_enqueue_style(
                    'fp-privacy-frontend',
                    $this->get_asset_url( $frontend_style ),
                    array(),
                    $this->get_asset_version( $frontend_style )
                );
            } else {
                $this->flag_missing_asset( $frontend_style, array(
                    'label'    => __( 'Stili del banner cookie', 'fp-privacy-cookie-policy' ),
                    'scope'    => 'frontend',
                    'severity' => 'warning',
                ) );
            }

            if ( ! $this->asset_exists( $frontend_script ) ) {
                $this->flag_missing_asset( $frontend_script, array(
                    'label'       => __( 'Script principale del banner cookie', 'fp-privacy-cookie-policy' ),
                    'scope'       => 'frontend',
                    'severity'    => 'error',
                    'description' => __( 'Il consenso non può essere raccolto senza il file JavaScript compilato. Ricompila gli asset e riprova.', 'fp-privacy-cookie-policy' ),
                ) );

                return;
            }

            wp_register_script(
                'fp-privacy-frontend',
                $this->get_asset_url( $frontend_script ),
                array(),
                $this->get_asset_version( $frontend_script ),
                true
            );

            $localize = array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( self::NONCE_ACTION ),
                'cookieName'     => self::CONSENT_COOKIE,
                'consentId'      => $this->get_consent_id(),
                'consentRevision' => isset( $options['consent_revision'] ) ? max( 1, (int) $options['consent_revision'] ) : 1,
                'consentRevisionUpdatedAt' => isset( $options['consent_revision_updated_at'] ) ? $options['consent_revision_updated_at'] : '',
                'categories'     => $this->prepare_categories_for_frontend( $localized['categories'] ),
                'banner'         => $localized['banner'],
                'googleDefaults' => $options['google_defaults'],
                'language'       => $localized['language'],
                'cookieTtlDays'  => isset( $options['consent_cookie_days'] ) ? (int) $options['consent_cookie_days'] : 0,
                'cookieOptions'  => $cookie_options,
                'previewMode'    => $preview_mode,
                'texts'          => array(
                    'manageConsent' => $text_values['manage_consent'],
                    'updatedAt'     => $text_values['updated_at'],
                ),
            );

            wp_localize_script( 'fp-privacy-frontend', 'fpPrivacySettings', $localize );
            wp_enqueue_script( 'fp-privacy-frontend' );
        }

        /**
         * Register the hook used to render the consent banner on the front-end.
         */
        public function setup_banner_render_hook() {
            $hook = $this->get_banner_render_hook();

            if ( $this->current_banner_hook && $this->current_banner_hook !== $hook ) {
                remove_action( $this->current_banner_hook, array( $this, 'render_consent_banner' ) );
                $this->current_banner_hook = null;
            }

            if ( 'manual' === $hook || empty( $hook ) ) {
                $this->current_banner_hook = 'manual';

                return;
            }

            if ( $hook !== $this->current_banner_hook ) {
                add_action( $hook, array( $this, 'render_consent_banner' ) );
                $this->current_banner_hook = $hook;
            }
        }

        /**
         * Prepare categories for frontend.
         *
         * @param array $categories Categories.
         *
         * @return array
         */
        protected function prepare_categories_for_frontend( $categories ) {
            $prepared = array();

            foreach ( $categories as $key => $category ) {
                $prepared[ $key ] = array(
                    'label'       => $category['label'],
                    'description' => wp_kses_post( $category['description'] ),
                    'services'    => $category['services'],
                    'required'    => (bool) $category['required'],
                    'enabled'     => (bool) $category['enabled'],
                );
            }

            return $prepared;
        }

        /**
         * Retrieve the WordPress hook that should output the consent banner.
         *
         * @return string
         */
        protected function get_banner_render_hook() {
            $settings = $this->get_settings();
            $mode     = isset( $settings['render_hook'] ) ? sanitize_key( $settings['render_hook'] ) : 'footer';

            switch ( $mode ) {
                case 'body_open':
                    return 'wp_body_open';
                case 'manual':
                    return 'manual';
                default:
                    return 'wp_footer';
            }
        }

        /**
         * Determine if preview mode should be enabled for the current request.
         *
         * @return bool
         */
        protected function is_preview_mode_active() {
            if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
                return false;
            }

            $settings    = $this->get_settings();
            $always_on   = ! empty( $settings['preview_mode'] );
            $query_param = false;

            if ( isset( $_GET[ self::PREVIEW_QUERY_KEY ] ) ) {
                $raw_value = wp_unslash( $_GET[ self::PREVIEW_QUERY_KEY ] );
                $value     = strtolower( sanitize_text_field( $raw_value ) );
                $truthy    = array( '1', 'true', 'yes', 'on' );

                $query_param = in_array( $value, $truthy, true ) || '' === $value;
            }

            $enabled = $always_on || $query_param;

            /**
             * Filter whether preview mode is active for the current request.
             *
             * @param bool $enabled     Whether preview mode should be forced.
             * @param bool $always_on   Whether preview mode is enabled in the settings.
             * @param bool $query_param Whether it has been requested via query parameter.
             */
            return (bool) apply_filters( 'fp_privacy_preview_mode', $enabled, $always_on, $query_param );
        }

        /**
         * Retrieve the plugin settings localized for the current language.
         *
         * @param string|null $language Preferred language code.
         *
         * @return array
         */
        protected function get_localized_settings( $language = null ) {
            $settings            = $this->get_settings();
            $available_languages = $this->get_available_languages( $settings );
            $default_language    = self::DEFAULT_LANGUAGE;

            if ( null === $language ) {
                $language = $this->determine_user_language( $available_languages, $default_language );
            }

            if ( ! in_array( $language, $available_languages, true ) ) {
                $language = $default_language;
            }

            if ( isset( $this->localized_cache[ $language ] ) ) {
                return $this->localized_cache[ $language ];
            }

            $localized = array(
                'language'               => $language,
                'privacy_policy_content' => $settings['privacy_policy_content'],
                'cookie_policy_content'  => $settings['cookie_policy_content'],
                'banner'                 => $settings['banner'],
                'categories'             => $settings['categories'],
            );

            if ( $language !== $default_language ) {
                $translation = $this->get_translation_for_language(
                    isset( $settings['translations'] ) && is_array( $settings['translations'] ) ? $settings['translations'] : array(),
                    $language
                );

                if ( ! empty( $translation ) ) {
                    if ( isset( $translation['privacy_policy_content'] ) ) {
                        $localized['privacy_policy_content'] = $translation['privacy_policy_content'];
                    }

                    if ( isset( $translation['cookie_policy_content'] ) ) {
                        $localized['cookie_policy_content'] = $translation['cookie_policy_content'];
                    }

                    if ( ! empty( $translation['banner'] ) && is_array( $translation['banner'] ) ) {
                        $localized['banner'] = array_merge( $localized['banner'], $translation['banner'] );
                    }

                    if ( ! empty( $translation['categories'] ) && is_array( $translation['categories'] ) ) {
                        foreach ( $localized['categories'] as $key => $category ) {
                            if ( isset( $translation['categories'][ $key ] ) && is_array( $translation['categories'][ $key ] ) ) {
                                $localized['categories'][ $key ] = array_merge( $category, $translation['categories'][ $key ] );
                            }
                        }
                    }
                }
            }

            $localized['texts'] = $this->get_frontend_texts( $language, $settings );

            $this->localized_cache[ $language ] = $localized;

            return $localized;
        }

        /**
         * Retrieve available languages from settings.
         *
         * @param array $settings Plugin settings.
         *
         * @return array
         */
        protected function get_available_languages( array $settings ) {
            $languages = array( self::DEFAULT_LANGUAGE );
            $detected  = array();

            if ( function_exists( 'get_available_languages' ) ) {
                $detected = array_merge( $detected, (array) get_available_languages() );
            }

            if ( function_exists( 'get_locale' ) ) {
                $site_locale = get_locale();
                if ( $site_locale ) {
                    $detected[] = $site_locale;
                }
            }

            $site_language = get_bloginfo( 'language' );
            if ( $site_language ) {
                $detected[] = $site_language;
            }

            if ( ! empty( $settings['active_languages'] ) && is_array( $settings['active_languages'] ) ) {
                $detected = array_merge( $detected, $settings['active_languages'] );
            }

            if ( ! empty( $settings['translations'] ) && is_array( $settings['translations'] ) ) {
                $detected = array_merge( $detected, array_keys( $settings['translations'] ) );
            }

            foreach ( $detected as $locale ) {
                $code = $this->normalize_language_code( $locale );
                if ( ! in_array( $code, $languages, true ) ) {
                    $languages[] = $code;
                }
            }

            $languages = array_values( array_unique( $languages ) );

            $default_index = array_search( self::DEFAULT_LANGUAGE, $languages, true );
            if ( false !== $default_index ) {
                unset( $languages[ $default_index ] );
                array_unshift( $languages, self::DEFAULT_LANGUAGE );
            }

            /**
             * Filter the list of available languages supported by the plugin.
             *
             * @param array $languages Language codes.
             * @param array $settings  Plugin settings.
             */
            return (array) apply_filters( 'fp_privacy_available_languages', $languages, $settings );
        }

        /**
         * Retrieve the list of languages that require translation fields.
         *
         * @param array|null $settings Optional settings array to reuse.
         *
         * @return array
         */
        protected function get_translation_languages( $settings = null ) {
            if ( null === $settings ) {
                $settings = $this->get_settings();
            }

            $languages = array();

            if ( isset( $settings['active_languages'] ) && is_array( $settings['active_languages'] ) ) {
                foreach ( $settings['active_languages'] as $language ) {
                    $language = $this->normalize_language_code( $language );

                    if ( self::DEFAULT_LANGUAGE === $language ) {
                        continue;
                    }

                    $languages[] = $language;
                }
            }

            if ( empty( $languages ) ) {
                $languages = array_filter(
                    $this->get_available_languages( $settings ),
                    array( $this, 'filter_non_default_language' )
                );
            }

            if ( isset( $settings['translations'] ) && is_array( $settings['translations'] ) ) {
                foreach ( array_keys( $settings['translations'] ) as $language ) {
                    $language = $this->normalize_language_code( $language );

                    if ( self::DEFAULT_LANGUAGE === $language ) {
                        continue;
                    }

                    if ( ! in_array( $language, $languages, true ) ) {
                        $languages[] = $language;
                    }
                }
            }

            $languages = array_values( $languages );
            sort( $languages );

            return $languages;
        }

        /**
         * Retrieve language choices for the selector.
         *
         * @param array|null $settings Optional settings array.
         * @param array      $selected Languages already selected.
         *
         * @return array
         */
        protected function get_language_choices( $settings = null, array $selected = array() ) {
            if ( null === $settings ) {
                $settings = $this->get_settings();
            }

            $candidates = array(
                'en',
                'es',
                'fr',
                'de',
                'pt',
                'nl',
                'pl',
                'cs',
                'da',
                'sv',
            );

            if ( function_exists( 'get_available_languages' ) ) {
                $candidates = array_merge( $candidates, (array) get_available_languages() );
            }

            if ( isset( $settings['active_languages'] ) && is_array( $settings['active_languages'] ) ) {
                $candidates = array_merge( $candidates, $settings['active_languages'] );
            }

            if ( isset( $settings['translations'] ) && is_array( $settings['translations'] ) ) {
                $candidates = array_merge( $candidates, array_keys( $settings['translations'] ) );
            }

            if ( ! empty( $selected ) ) {
                $candidates = array_merge( $candidates, $selected );
            }

            $choices = array();

            foreach ( $candidates as $candidate ) {
                $language = $this->normalize_language_code( $candidate );

                if ( self::DEFAULT_LANGUAGE === $language ) {
                    continue;
                }

                if ( isset( $choices[ $language ] ) ) {
                    continue;
                }

                $choices[ $language ] = $this->get_language_label( $language );
            }

            asort( $choices, SORT_NATURAL | SORT_FLAG_CASE );

            return $choices;
        }

        /**
         * Retrieve a human readable label for a language code.
         *
         * @param string $language Language code.
         *
         * @return string
         */
        protected function get_language_label( $language ) {
            $language = $this->normalize_language_code( $language );

            if ( empty( $this->language_labels ) ) {
                $this->language_labels = array(
                    self::DEFAULT_LANGUAGE => __( 'Italiano', 'fp-privacy-cookie-policy' ),
                    'en'                   => __( 'Inglese', 'fp-privacy-cookie-policy' ),
                );

                if ( function_exists( 'wp_get_available_translations' ) ) {
                    $available = wp_get_available_translations();
                    if ( is_array( $available ) ) {
                        foreach ( $available as $locale => $data ) {
                            if ( empty( $data['native_name'] ) ) {
                                continue;
                            }

                            $code = $this->normalize_language_code( $locale );
                            if ( ! isset( $this->language_labels[ $code ] ) ) {
                                $this->language_labels[ $code ] = $data['native_name'];
                            }
                        }
                    }
                }
            }

            if ( ! isset( $this->language_labels[ $language ] ) ) {
                $this->language_labels[ $language ] = strtoupper( $language );
            }

            return $this->language_labels[ $language ];
        }

        /**
         * Determine user preferred language based on browser and WordPress locale.
         *
         * @param array  $available_languages Available languages.
         * @param string $default_language    Default language code.
         *
         * @return string
         */
        protected function determine_user_language( array $available_languages = array(), $default_language = self::DEFAULT_LANGUAGE ) {
            if ( empty( $available_languages ) ) {
                $available_languages = array( $default_language );
            }

            if ( ! in_array( $default_language, $available_languages, true ) ) {
                $available_languages[] = $default_language;
            }

            if ( ! empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
                $accepted = explode( ',', wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) );

                foreach ( $accepted as $locale ) {
                    $locale = trim( $locale );
                    if ( empty( $locale ) ) {
                        continue;
                    }

                    $parts = explode( ';', $locale );
                    $code  = $this->normalize_language_code( $parts[0] );

                    if ( in_array( $code, $available_languages, true ) ) {
                        return $code;
                    }
                }
            }

            $wp_locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

            if ( $wp_locale ) {
                $code = $this->normalize_language_code( $wp_locale );
                if ( in_array( $code, $available_languages, true ) ) {
                    return $code;
                }
            }

            return $default_language;
        }

        /**
         * Normalize language code to ISO 639-1 (two letters).
         *
         * @param string $language Language identifier.
         *
         * @return string
         */
        protected function normalize_language_code( $language ) {
            $language = strtolower( str_replace( '_', '-', (string) $language ) );
            $parts    = explode( '-', $language );

            return isset( $parts[0] ) && $parts[0] ? $parts[0] : self::DEFAULT_LANGUAGE;
        }

        /**
         * Retrieve translation data for a specific language.
         *
         * @param array  $translations Translations array.
         * @param string $language     Language code.
         *
         * @return array
         */
        protected function get_translation_for_language( array $translations, $language ) {
            if ( isset( $translations[ $language ] ) && is_array( $translations[ $language ] ) ) {
                return $translations[ $language ];
            }

            foreach ( $translations as $key => $translation ) {
                if ( strpos( $this->normalize_language_code( $key ), $language ) === 0 && is_array( $translation ) ) {
                    return $translation;
                }
            }

            return array();
        }

        /**
         * Retrieve the frontend text definitions for supported languages.
         *
         * @return array
         */
        protected function get_frontend_text_definitions() {
            return array(
                'modal_close'       => array(
                    self::DEFAULT_LANGUAGE => __( 'Chiudi', 'fp-privacy-cookie-policy' ),
                    'en'                   => 'Close',
                ),
                'modal_title'       => array(
                    self::DEFAULT_LANGUAGE => __( 'Gestisci le preferenze', 'fp-privacy-cookie-policy' ),
                    'en'                   => 'Manage preferences',
                ),
                'modal_intro'       => array(
                    self::DEFAULT_LANGUAGE => __( 'Decidi quali categorie di cookie attivare. Puoi modificare la tua scelta in qualsiasi momento.', 'fp-privacy-cookie-policy' ),
                    'en'                   => 'Choose which categories of cookies to enable. You can change your preferences at any time.',
                ),
                'services_included' => array(
                    self::DEFAULT_LANGUAGE => __( 'Servizi inclusi', 'fp-privacy-cookie-policy' ),
                    'en'                   => 'Included services',
                ),
                'always_active'     => array(
                    self::DEFAULT_LANGUAGE => __( 'Sempre attivo', 'fp-privacy-cookie-policy' ),
                    'en'                   => 'Always active',
                ),
                'toggle_aria'       => array(
                    self::DEFAULT_LANGUAGE => __( 'Attiva o disattiva i cookie %s', 'fp-privacy-cookie-policy' ),
                    'en'                   => 'Enable or disable %s cookies',
                ),
                'manage_consent'    => array(
                    self::DEFAULT_LANGUAGE => __( 'Gestisci preferenze cookie', 'fp-privacy-cookie-policy' ),
                    'en'                   => 'Manage cookie preferences',
                ),
                'updated_at'        => array(
                    self::DEFAULT_LANGUAGE => __( 'Ultimo aggiornamento', 'fp-privacy-cookie-policy' ),
                    'en'                   => 'Last updated',
                ),
            );
        }

        /**
         * Retrieve default frontend texts for a specific language.
         *
         * @param string $language Language code.
         *
         * @return array
         */
        protected function get_frontend_texts_defaults( $language ) {
            $language     = $this->normalize_language_code( $language );
            $definitions  = $this->get_frontend_text_definitions();
            $defaults     = array();

            foreach ( $definitions as $key => $values ) {
                if ( isset( $values[ $language ] ) ) {
                    $defaults[ $key ] = $values[ $language ];
                } elseif ( isset( $values[ self::DEFAULT_LANGUAGE ] ) ) {
                    $defaults[ $key ] = $values[ self::DEFAULT_LANGUAGE ];
                } else {
                    $defaults[ $key ] = '';
                }
            }

            if ( isset( $defaults['toggle_aria'] ) && false === strpos( $defaults['toggle_aria'], '%s' ) ) {
                $defaults['toggle_aria'] = $definitions['toggle_aria'][ self::DEFAULT_LANGUAGE ];
            }

            return $defaults;
        }

        /**
         * Frontend static texts by language, including settings overrides.
         *
         * @param string     $language Language code.
         * @param array|null $settings Optional settings to reuse.
         *
         * @return array
         */
        protected function get_frontend_texts( $language, $settings = null ) {
            if ( null === $settings ) {
                $settings = $this->get_settings();
            }

            if ( ! is_array( $settings ) ) {
                $settings = $this->get_default_settings();
            }

            $language          = $this->normalize_language_code( $language );
            $base_defaults     = $this->get_frontend_texts_defaults( self::DEFAULT_LANGUAGE );
            $language_defaults = $this->get_frontend_texts_defaults( $language );
            $base_texts        = isset( $settings['texts'] ) && is_array( $settings['texts'] ) ? $settings['texts'] : array();
            $texts             = array_merge( $base_defaults, $base_texts );

            if ( $language !== self::DEFAULT_LANGUAGE ) {
                $translation = $this->get_translation_for_language(
                    isset( $settings['translations'] ) && is_array( $settings['translations'] ) ? $settings['translations'] : array(),
                    $language
                );

                if ( isset( $translation['texts'] ) && is_array( $translation['texts'] ) ) {
                    $texts = array_merge( $texts, $translation['texts'] );
                } else {
                    $texts = $language_defaults;
                }
            }

            foreach ( $language_defaults as $key => $value ) {
                if ( ! isset( $texts[ $key ] ) || '' === $texts[ $key ] ) {
                    $texts[ $key ] = $value;
                }
            }

            if ( isset( $texts['toggle_aria'] ) && false === strpos( $texts['toggle_aria'], '%s' ) ) {
                $texts['toggle_aria'] = $language_defaults['toggle_aria'];
            }

            /**
             * Filter the frontend interface texts for the consent banner.
             *
             * @param array  $texts    Prepared texts.
             * @param string $language Normalized language code.
             * @param array  $settings Plugin settings.
             */
            return (array) apply_filters( 'fp_privacy_frontend_texts', $texts, $language, $settings );
        }

        /**
         * Render consent banner markup.
         */
        public function render_consent_banner() {
            $markup = $this->get_consent_banner_markup();

            if ( $markup ) {
                echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }

        /**
         * Generate the consent banner markup for the given language.
         *
         * @param string|null $language Optional language code.
         *
         * @return string
         */
        public function get_consent_banner_markup( $language = null ) {
            $localized     = $this->get_localized_settings( $language );
            $banner        = $localized['banner'];
            $categories    = $localized['categories'];
            $texts         = isset( $localized['texts'] ) && is_array( $localized['texts'] )
                ? $localized['texts']
                : $this->get_frontend_texts( $localized['language'] );
            $preview_mode  = $this->is_preview_mode_active();
            $has_preferred = ! empty( array_filter( $categories, static function ( $category ) {
                return ! empty( $category['enabled'] ) && empty( $category['required'] );
            } ) );
            $banner_title_id   = 'fp-consent-banner-title';
            $banner_message_id = 'fp-consent-banner-message';
            $modal_intro_id    = 'fp-consent-modal-description';
            $theme_vars        = $this->get_banner_theme_variables( $banner );
            $style_attr        = $this->build_css_custom_properties( $theme_vars );
            $layout_class      = $this->get_banner_layout_class( $banner );
            $layout_key        = $this->get_banner_layout_suffix( $banner );

            ob_start();
            ?>
            <div class="fp-consent-wrapper" data-consent-layout="<?php echo esc_attr( $layout_key ); ?>" data-preview-mode="<?php echo esc_attr( $preview_mode ? '1' : '0' ); ?>"<?php if ( $style_attr ) : ?> style="<?php echo esc_attr( $style_attr ); ?>"<?php endif; ?>>
                <?php if ( $preview_mode ) : ?>
                    <p class="fp-consent-preview-notice"><?php esc_html_e( 'Modalità anteprima attiva: le scelte non vengono salvate.', 'fp-privacy-cookie-policy' ); ?></p>
                <?php endif; ?>
                <div class="fp-consent-banner <?php echo esc_attr( $layout_class ); ?>" role="dialog" aria-live="polite" aria-modal="true" aria-labelledby="<?php echo esc_attr( $banner_title_id ); ?>" aria-describedby="<?php echo esc_attr( $banner_message_id ); ?>" data-cookie-name="<?php echo esc_attr( self::CONSENT_COOKIE ); ?>" data-language="<?php echo esc_attr( $localized['language'] ); ?>">
                    <div class="fp-consent-container">
                        <div class="fp-consent-content">
                            <h3 id="<?php echo esc_attr( $banner_title_id ); ?>" class="fp-consent-title"><?php echo esc_html( $banner['banner_title'] ); ?></h3>
                            <div id="<?php echo esc_attr( $banner_message_id ); ?>" class="fp-consent-text"><?php echo wpautop( wp_kses_post( $banner['banner_message'] ) ); ?></div>
                        </div>
                        <div class="fp-consent-actions">
                            <button class="fp-btn fp-btn-primary" data-consent-action="accept-all"><?php echo esc_html( $banner['accept_all_label'] ); ?></button>
                            <?php if ( $banner['show_reject'] ) : ?>
                                <button class="fp-btn fp-btn-secondary" data-consent-action="reject-all"><?php echo esc_html( $banner['reject_all_label'] ); ?></button>
                            <?php endif; ?>
                            <?php if ( $banner['show_preferences'] && $has_preferred ) : ?>
                                <button class="fp-btn fp-btn-tertiary" data-consent-action="open-preferences"><?php echo esc_html( $banner['preferences_label'] ); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div id="fp-consent-modal" class="fp-consent-modal" role="dialog" aria-modal="true" aria-labelledby="fp-consent-modal-title" aria-describedby="<?php echo esc_attr( $modal_intro_id ); ?>" data-language="<?php echo esc_attr( $localized['language'] ); ?>" aria-hidden="true" tabindex="-1" hidden>
                <div class="fp-consent-modal__overlay" data-consent-action="close"></div>
                <div class="fp-consent-modal__dialog" role="document">
                    <button class="fp-consent-modal__close" type="button" aria-label="<?php echo esc_attr( $texts['modal_close'] ); ?>" data-consent-action="close">&times;</button>
                    <h3 id="fp-consent-modal-title"><?php echo esc_html( $texts['modal_title'] ); ?></h3>
                    <p id="<?php echo esc_attr( $modal_intro_id ); ?>" class="fp-consent-modal__intro"><?php echo esc_html( $texts['modal_intro'] ); ?></p>
                    <div class="fp-consent-categories">
                        <?php foreach ( $categories as $key => $category ) : ?>
                            <?php if ( empty( $category['enabled'] ) && empty( $category['required'] ) ) : ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <div class="fp-consent-category" data-category-key="<?php echo esc_attr( $key ); ?>">
                                <div class="fp-consent-category__header">
                                    <div>
                                        <h4><?php echo esc_html( $category['label'] ); ?></h4>
                                        <p><?php echo esc_html( wp_strip_all_tags( $category['description'] ) ); ?></p>
                                        <?php if ( ! empty( $category['services'] ) ) : ?>
                                            <details>
                                                <summary><?php echo esc_html( $texts['services_included'] ); ?></summary>
                                                <p><?php echo esc_html( $category['services'] ); ?></p>
                                            </details>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fp-consent-toggle">
                                        <?php if ( ! empty( $category['required'] ) ) : ?>
                                            <span class="fp-consent-required"><?php echo esc_html( $texts['always_active'] ); ?></span>
                                        <?php else : ?>
                                            <label class="fp-switch">
                                                <input type="checkbox" value="1" data-category-toggle="<?php echo esc_attr( $key ); ?>" />
                                                <span class="fp-slider" aria-hidden="true"></span>
                                                <span class="screen-reader-text"><?php echo esc_html( sprintf( $texts['toggle_aria'], $category['label'] ) ); ?></span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="fp-consent-modal__actions">
                        <button class="fp-btn fp-btn-primary" data-consent-action="save-preferences"><?php echo esc_html( $banner['save_preferences_label'] ); ?></button>
                        <?php if ( $banner['show_reject'] ) : ?>
                            <button class="fp-btn fp-btn-secondary" data-consent-action="reject-all"><?php echo esc_html( $banner['reject_all_label'] ); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
                </div>
                <button class="fp-btn fp-btn-preferences fp-consent-manage" type="button" data-consent-manage data-consent-action="open-preferences" aria-haspopup="dialog" aria-controls="fp-consent-modal" aria-hidden="true" aria-expanded="false" aria-label="<?php echo esc_attr( $texts['manage_consent'] ); ?>" hidden>
                    <span class="fp-consent-manage__icon" aria-hidden="true">⚙</span>
                    <span class="fp-consent-manage__label"><?php echo esc_html( $texts['manage_consent'] ); ?></span>
                </button>
            </div>
            <?php

            return ob_get_clean();
        }

        /**
         * Generate the markup for the manage preferences button.
         *
         * @param string|null $language Optional language code.
         *
         * @return string
         */
        protected function get_manage_preferences_button_markup( $language = null ) {
            $localized = $this->get_localized_settings( $language );
            $banner    = $localized['banner'];

            return sprintf(
                '<button class="fp-btn fp-btn-preferences" type="button" data-consent-action="open-preferences">%s</button>',
                esc_html( $banner['preferences_label'] )
            );
        }

        /**
         * Determine whether the provided content differs from its default.
         *
         * @param string $value   Current value.
         * @param string $default Default value.
         *
         * @return bool
         */
        protected function has_custom_content( $value, $default ) {
            $value   = trim( wp_strip_all_tags( (string) $value ) );
            $default = trim( wp_strip_all_tags( (string) $default ) );

            if ( '' === $value ) {
                return false;
            }

            return $value !== $default;
        }

        /**
         * Build the link to the settings page with an optional anchor.
         *
         * @param string $anchor Anchor to append.
         *
         * @return string
         */
        protected function get_settings_link( $anchor = '' ) {
            $url = add_query_arg( 'page', 'fp-privacy-cookie-policy', admin_url( 'admin.php' ) );

            if ( $anchor ) {
                $url .= $anchor;
            }

            return $url;
        }

        /**
         * Build onboarding steps status for the admin checklist.
         *
         * @return array
         */
        protected function get_onboarding_steps() {
            $options  = $this->get_settings();
            $defaults = $this->get_default_settings();

            $privacy_custom = $this->has_custom_content(
                isset( $options['privacy_policy_content'] ) ? $options['privacy_policy_content'] : '',
                isset( $defaults['privacy_policy_content'] ) ? $defaults['privacy_policy_content'] : ''
            );

            $cookie_custom = $this->has_custom_content(
                isset( $options['cookie_policy_content'] ) ? $options['cookie_policy_content'] : '',
                isset( $defaults['cookie_policy_content'] ) ? $defaults['cookie_policy_content'] : ''
            );

            $categories_custom = false;
            $categories        = isset( $options['categories'] ) && is_array( $options['categories'] ) ? $options['categories'] : array();
            foreach ( $categories as $key => $category ) {
                $default_category = isset( $defaults['categories'][ $key ] ) && is_array( $defaults['categories'][ $key ] )
                    ? $defaults['categories'][ $key ]
                    : array( 'description' => '', 'services' => '', 'required' => false, 'enabled' => true );

                $description = isset( $category['description'] ) ? $category['description'] : '';
                $services    = isset( $category['services'] ) ? $category['services'] : '';

                $required        = ! empty( $category['required'] );
                $default_required = ! empty( $default_category['required'] );

                if ( $required !== $default_required ) {
                    $categories_custom = true;
                    break;
                }

                if ( $this->has_custom_content( $description, isset( $default_category['description'] ) ? $default_category['description'] : '' ) ) {
                    $categories_custom = true;
                    break;
                }

                if ( $this->has_custom_content( $services, isset( $default_category['services'] ) ? $default_category['services'] : '' ) ) {
                    $categories_custom = true;
                    break;
                }
            }

            $google_defaults   = isset( $options['google_defaults'] ) ? $options['google_defaults'] : array();
            $google_customized = ! empty( array_diff_assoc( $google_defaults, $defaults['google_defaults'] ) );
            $render_hook       = isset( $options['render_hook'] ) ? sanitize_key( $options['render_hook'] ) : 'footer';

            return array(
                array(
                    'key'         => 'privacy',
                    'title'       => __( 'Personalizza la privacy policy', 'fp-privacy-cookie-policy' ),
                    'description' => __( 'Rivedi il testo proposto e adattalo insieme al tuo consulente legale.', 'fp-privacy-cookie-policy' ),
                    'link'        => $this->get_settings_link( '#fp_privacy_policy_content' ),
                    'link_label'  => __( 'Modifica privacy policy', 'fp-privacy-cookie-policy' ),
                    'complete'    => $privacy_custom,
                    'auto'        => true,
                ),
                array(
                    'key'         => 'cookie_policy',
                    'title'       => __( 'Aggiorna la cookie policy', 'fp-privacy-cookie-policy' ),
                    'description' => __( 'Completa le informazioni su durata, base giuridica e responsabili del trattamento.', 'fp-privacy-cookie-policy' ),
                    'link'        => $this->get_settings_link( '#fp_cookie_policy_content' ),
                    'link_label'  => __( 'Modifica cookie policy', 'fp-privacy-cookie-policy' ),
                    'complete'    => $cookie_custom,
                    'auto'        => true,
                ),
                array(
                    'key'         => 'categories',
                    'title'       => __( 'Configura categorie e servizi', 'fp-privacy-cookie-policy' ),
                    'description' => __( 'Definisci descrizioni e servizi per ogni categoria, includendo eventuali strumenti di terze parti.', 'fp-privacy-cookie-policy' ),
                    'link'        => $this->get_settings_link( '#fp_category_necessary' ),
                    'link_label'  => __( 'Apri categorie cookie', 'fp-privacy-cookie-policy' ),
                    'complete'    => $categories_custom,
                    'auto'        => true,
                ),
                array(
                    'key'         => 'consent',
                    'title'       => __( 'Imposta i segnali del Google Consent Mode', 'fp-privacy-cookie-policy' ),
                    'description' => __( 'Controlla i valori predefiniti dei segnali e assicurati che riflettano le tue necessità di tracciamento.', 'fp-privacy-cookie-policy' ),
                    'link'        => $this->get_settings_link( '#fp_google_defaults' ),
                    'link_label'  => __( 'Modifica Consent Mode', 'fp-privacy-cookie-policy' ),
                    'complete'    => $google_customized,
                    'auto'        => true,
                ),
                array(
                    'key'         => 'integration',
                    'title'       => __( 'Integra il banner nel sito', 'fp-privacy-cookie-policy' ),
                    'description' => __( 'Scegli il punto di aggancio più adatto o inserisci blocchi/shortcode dove necessario e verifica i tag di tracciamento.', 'fp-privacy-cookie-policy' ),
                    'link'        => $this->get_settings_link( '#fp_render_hook' ),
                    'link_label'  => __( 'Configura posizionamento banner', 'fp-privacy-cookie-policy' ),
                    'complete'    => 'footer' !== $render_hook,
                    'auto'        => false,
                ),
            );
        }

        /**
         * Register shortcodes.
         */
        public function register_shortcodes() {
            add_shortcode( 'fp_privacy_policy', array( $this, 'shortcode_privacy_policy' ) );
            add_shortcode( 'fp_cookie_policy', array( $this, 'shortcode_cookie_policy' ) );
            add_shortcode( 'fp_cookie_preferences', array( $this, 'shortcode_cookie_preferences' ) );
            add_shortcode( 'fp_cookie_banner', array( $this, 'shortcode_cookie_banner' ) );
        }

        /**
         * Register Gutenberg blocks for the plugin.
         */
        public function register_blocks() {
            if ( ! function_exists( 'register_block_type' ) ) {
                return;
            }

            $handle          = 'fp-privacy-blocks';
            $blocks_script   = 'assets/js/blocks.js';
            $has_editor_code = $this->asset_exists( $blocks_script );

            if ( ! $has_editor_code ) {
                $this->flag_missing_asset( $blocks_script, array(
                    'label'       => __( 'Script dei blocchi per l’editor', 'fp-privacy-cookie-policy' ),
                    'scope'       => 'editor',
                    'severity'    => 'info',
                    'description' => __( 'I blocchi Gutenberg resteranno disponibili solo in front-end finché non ricompili gli asset dell’editor.', 'fp-privacy-cookie-policy' ),
                ) );
            }

            if ( $has_editor_code ) {
                wp_register_script(
                    $handle,
                    $this->get_asset_url( $blocks_script ),
                    array( 'wp-blocks', 'wp-element', 'wp-i18n' ),
                    $this->get_asset_version( $blocks_script ),
                    true
                );

                if ( function_exists( 'wp_set_script_translations' ) ) {
                    wp_set_script_translations( $handle, 'fp-privacy-cookie-policy', plugin_dir_path( __FILE__ ) . 'languages' );
                }
            }

            $blocks = array(
                'fp/privacy-policy'     => array( $this, 'render_privacy_policy_block' ),
                'fp/cookie-policy'      => array( $this, 'render_cookie_policy_block' ),
                'fp/cookie-preferences' => array( $this, 'render_cookie_preferences_block' ),
                'fp/cookie-banner'      => array( $this, 'render_cookie_banner_block' ),
            );

            foreach ( $blocks as $name => $callback ) {
                $args = array(
                    'render_callback' => $callback,
                );

                if ( $has_editor_code ) {
                    $args['editor_script'] = $handle;
                }

                register_block_type( $name, $args );
            }
        }

        /**
         * Privacy policy shortcode callback.
         *
         * @return string
         */
        public function shortcode_privacy_policy() {
            $localized = $this->get_localized_settings();

            return '<div class="fp-privacy-policy">' . wp_kses_post( $localized['privacy_policy_content'] ) . '</div>';
        }

        /**
         * Cookie policy shortcode callback.
         *
         * @return string
         */
        public function shortcode_cookie_policy() {
            $localized = $this->get_localized_settings();

            return '<div class="fp-cookie-policy">' . wp_kses_post( $localized['cookie_policy_content'] ) . '</div>';
        }

        /**
         * Cookie banner shortcode callback.
         *
         * @return string
         */
        public function shortcode_cookie_banner() {
            return $this->get_consent_banner_markup();
        }

        /**
         * Cookie preferences shortcode callback.
         *
         * @return string
         */
        public function shortcode_cookie_preferences() {
            return $this->get_manage_preferences_button_markup();
        }

        /**
         * Render callback for the privacy policy block.
         */
        public function render_privacy_policy_block( $attributes = array(), $content = '' ) {
            return $this->shortcode_privacy_policy();
        }

        /**
         * Render callback for the cookie policy block.
         */
        public function render_cookie_policy_block( $attributes = array(), $content = '' ) {
            return $this->shortcode_cookie_policy();
        }

        /**
         * Render callback for the cookie preferences block.
         */
        public function render_cookie_preferences_block( $attributes = array(), $content = '' ) {
            return $this->shortcode_cookie_preferences();
        }

        /**
         * Render callback for the cookie banner block.
         */
        public function render_cookie_banner_block( $attributes = array(), $content = '' ) {
            return $this->shortcode_cookie_banner();
        }

        /**
         * Render settings page.
         */
        public function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $tabs       = $this->get_admin_tabs();
            $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';

            if ( ! isset( $tabs[ $active_tab ] ) ) {
                $active_tab = 'settings';
            }
            ?>
            <div class="wrap fp-privacy-admin">
                <h1><?php esc_html_e( 'Privacy e Cookie Policy', 'fp-privacy-cookie-policy' ); ?></h1>
                <h2 class="nav-tab-wrapper">
                    <?php foreach ( $tabs as $tab_key => $tab ) :
                        $classes = array( 'nav-tab' );

                        if ( $tab_key === $active_tab ) {
                            $classes[] = 'nav-tab-active';
                        }

                        $classes = array_map( 'sanitize_html_class', $classes );
                        $class   = implode( ' ', array_filter( $classes ) );
                        $label   = isset( $tab['label'] ) ? $tab['label'] : $tab_key;
                        $url     = isset( $tab['url'] ) ? $tab['url'] : add_query_arg(
                            array(
                                'page' => 'fp-privacy-cookie-policy',
                                'tab'  => $tab_key,
                            ),
                            admin_url( 'admin.php' )
                        );
                        ?>
                        <a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></a>
                    <?php endforeach; ?>
                </h2>

                <?php if ( 'logs' === $active_tab ) : ?>
                    <?php $this->render_logs_tab(); ?>
                <?php elseif ( 'help' === $active_tab ) : ?>
                    <?php $this->render_help_tab(); ?>
                <?php else : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'fp_privacy_cookie_group' );
                        do_settings_sections( 'fp_privacy_cookie_policy' );
                        submit_button();
                        ?>
                    </form>
                <?php endif; ?>
            </div>
            <?php
        }

        /**
         * Register the dashboard widgets exposed by the plugin.
         */
        public function register_dashboard_widgets() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            /**
             * Filter whether the dashboard widget should be registered.
             *
             * @since 1.13.1
             *
             * @param bool $enabled Whether the widget should be available.
             */
            $enabled = apply_filters( 'fp_privacy_enable_dashboard_widget', true );

            if ( ! $enabled ) {
                return;
            }

            wp_add_dashboard_widget(
                'fp_privacy_consent_overview',
                __( 'Registro consensi', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_dashboard_widget' )
            );
        }

        /**
         * Render the consent overview widget in the WordPress dashboard.
         */
        public function render_dashboard_widget() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            echo '<div class="fp-dashboard-consent">';

            if ( ! $this->consent_table_exists() ) {
                $settings_url = $this->get_settings_link( '&tab=logs' );

                echo '<p>' . esc_html__( 'La tabella del registro consensi è mancante o danneggiata. Ricreala per continuare a salvare i consensi.', 'fp-privacy-cookie-policy' ) . '</p>';
                echo '<p class="fp-dashboard-consent__actions">';
                echo '<a class="button button-primary" href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Apri impostazioni', 'fp-privacy-cookie-policy' ) . '</a>';
                echo '</p>';
                echo '</div>';

                return;
            }

            $summary        = $this->get_consent_log_summary();
            $summary_labels = $this->get_consent_event_labels();
            $summary_labels['other'] = __( 'Altri eventi', 'fp-privacy-cookie-policy' );
            $total          = isset( $summary['total'] ) ? (int) $summary['total'] : 0;
            $last_event     = isset( $summary['last_event'] ) ? $this->format_consent_datetime( $summary['last_event'] ) : '';
            $recent_days    = isset( $summary['recent']['days'] ) ? (int) $summary['recent']['days'] : 30;
            $recent_total   = isset( $summary['recent']['total'] ) ? (int) $summary['recent']['total'] : 0;
            $logs_url       = add_query_arg(
                array(
                    'page' => 'fp-privacy-cookie-policy',
                    'tab'  => 'logs',
                ),
                admin_url( 'admin.php' )
            );

            echo '<ul class="fp-dashboard-consent__stats">';
            echo '<li class="fp-dashboard-consent__stat">';
            echo '<span class="fp-dashboard-consent__label">' . esc_html__( 'Totale eventi registrati', 'fp-privacy-cookie-policy' ) . '</span>';
            echo '<span class="fp-dashboard-consent__metric">' . esc_html( number_format_i18n( $total ) ) . '</span>';
            echo '<span class="fp-dashboard-consent__description">';
            if ( $last_event ) {
                printf(
                    esc_html__( 'Ultimo evento registrato: %s', 'fp-privacy-cookie-policy' ),
                    esc_html( $last_event )
                );
            } else {
                esc_html_e( 'Nessun evento registrato finora.', 'fp-privacy-cookie-policy' );
            }
            echo '</span>';
            echo '</li>';

            echo '<li class="fp-dashboard-consent__stat">';
            $recent_label = sprintf( esc_html__( 'Ultimi %d giorni', 'fp-privacy-cookie-policy' ), max( 1, $recent_days ) );
            echo '<span class="fp-dashboard-consent__label">' . esc_html( $recent_label ) . '</span>';
            echo '<span class="fp-dashboard-consent__metric">' . esc_html( number_format_i18n( $recent_total ) ) . '</span>';
            if ( 0 === $recent_total ) {
                echo '<span class="fp-dashboard-consent__description">';
                printf(
                    esc_html__( 'Nessun dato registrato negli ultimi %d giorni.', 'fp-privacy-cookie-policy' ),
                    max( 1, $recent_days )
                );
                echo '</span>';
            }
            echo '</li>';
            echo '</ul>';

            if ( $total > 0 ) {
                echo '<h4 class="fp-dashboard-consent__subheading">' . esc_html__( 'Distribuzione totale', 'fp-privacy-cookie-policy' ) . '</h4>';
                echo '<ul class="fp-dashboard-consent__distribution">';

                foreach ( $summary_labels as $event_key => $label ) {
                    $count = isset( $summary['events'][ $event_key ] ) ? (int) $summary['events'][ $event_key ] : 0;

                    if ( 'other' === $event_key && 0 === $count ) {
                        continue;
                    }

                    if ( 0 === $count ) {
                        continue;
                    }

                    $percentage = $this->calculate_percentage( $count, $total );

                    echo '<li class="fp-dashboard-consent__distribution-row">';
                    echo '<div class="fp-dashboard-consent__distribution-header">';
                    echo '<span class="fp-dashboard-consent__distribution-label">' . esc_html( $label ) . '</span>';
                    echo '<span class="fp-dashboard-consent__distribution-value">' . esc_html( number_format_i18n( $count ) );
                    echo '<span class="fp-dashboard-consent__distribution-percentage">' . esc_html( number_format_i18n( $percentage, 1 ) ) . '%</span>';
                    echo '</span>';
                    echo '</div>';
                    echo '<div class="fp-dashboard-consent__distribution-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( round( $percentage ) ) . '">';
                    echo '<span class="fp-dashboard-consent__distribution-fill" style="width: ' . esc_attr( min( 100, max( 0, $percentage ) ) ) . '%"></span>';
                    echo '</div>';
                    echo '</li>';
                }

                echo '</ul>';
            } else {
                echo '<p class="fp-dashboard-consent__empty">' . esc_html__( 'Nessun consenso registrato al momento.', 'fp-privacy-cookie-policy' ) . '</p>';
            }

            echo '<p class="fp-dashboard-consent__actions">';
            echo '<a class="button button-primary" href="' . esc_url( $logs_url ) . '">' . esc_html__( 'Apri registro consensi', 'fp-privacy-cookie-policy' ) . '</a>';
            echo '<a class="button" href="' . esc_url( $this->get_settings_link() ) . '">' . esc_html__( 'Apri impostazioni', 'fp-privacy-cookie-policy' ) . '</a>';
            echo '</p>';

            echo '</div>';
        }

        /**
         * Render logs tab.
         */
        protected function render_logs_tab() {
            global $wpdb;

            if ( ! $this->consent_table_exists() ) {
                echo '<p>' . esc_html__( 'La tabella del registro consensi non è disponibile. Riattiva il plugin per ricrearla.', 'fp-privacy-cookie-policy' ) . '</p>';

                return;
            }

            $table_name = self::get_consent_table_name();
            $per_page = 50;
            $paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
            $offset   = ( $paged - 1 ) * $per_page;

            $filters = $this->prepare_consent_log_filters(
                array(
                    'search' => isset( $_GET['s'] ) ? wp_unslash( $_GET['s'] ) : '',
                    'event'  => isset( $_GET['event'] ) ? wp_unslash( $_GET['event'] ) : '',
                    'from'   => isset( $_GET['from'] ) ? wp_unslash( $_GET['from'] ) : '',
                    'to'     => isset( $_GET['to'] ) ? wp_unslash( $_GET['to'] ) : '',
                )
            );

            $search        = $filters['search'];
            $event         = $filters['event'];
            $from_raw      = $filters['from'];
            $to_raw        = $filters['to'];
            $where_clauses = $filters['where'];
            $params        = $filters['params'];

            $allowed_events = $this->get_allowed_consent_events();
            $event_labels   = $this->get_consent_event_labels();

            $where_sql = '';

            if ( ! empty( $where_clauses ) ) {
                $where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
            }

            $logs_sql   = "SELECT * FROM {$table_name}{$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
            $logs_query = $wpdb->prepare( $logs_sql, array_merge( $params, array( $per_page, $offset ) ) );
            $logs       = $wpdb->get_results( $logs_query );

            $count_sql   = "SELECT COUNT(*) FROM {$table_name}{$where_sql}";
            $count_query = ! empty( $params ) ? $wpdb->prepare( $count_sql, $params ) : $count_sql;
            $total       = (int) $wpdb->get_var( $count_query );
            $pages       = (int) ceil( $total / $per_page );

            $logs_url = add_query_arg(
                array(
                    'page' => 'fp-privacy-cookie-policy',
                    'tab'  => 'logs',
                ),
                admin_url( 'admin.php' )
            );

            $filter_args = array();

            if ( $search ) {
                $filter_args['s'] = $search;
            }

            if ( $event ) {
                $filter_args['event'] = $event;
            }

            if ( $from_raw ) {
                $filter_args['from'] = $from_raw;
            }

            if ( $to_raw ) {
                $filter_args['to'] = $to_raw;
            }

            if ( ! empty( $filter_args ) ) {
                $logs_url = add_query_arg( $filter_args, $logs_url );
            }

            $summary         = $this->get_consent_log_summary();
            $summary_labels  = $event_labels;
            $summary_labels['other'] = __( 'Altri eventi', 'fp-privacy-cookie-policy' );
            $last_event_date = isset( $summary['last_event'] ) ? $this->format_consent_datetime( $summary['last_event'] ) : '';

            $recent_days  = isset( $summary['recent']['days'] ) ? (int) $summary['recent']['days'] : 30;
            $recent_total = isset( $summary['recent']['total'] ) ? (int) $summary['recent']['total'] : 0;

            ?>
            <section class="fp-consent-log-summary" aria-labelledby="fp-consent-summary-heading">
                <h3 id="fp-consent-summary-heading"><?php esc_html_e( 'Riepilogo registro consensi', 'fp-privacy-cookie-policy' ); ?></h3>
                <div class="fp-consent-log-summary__grid">
                    <div class="fp-consent-log-summary__card">
                        <span class="fp-consent-log-summary__heading"><?php esc_html_e( 'Totale eventi registrati', 'fp-privacy-cookie-policy' ); ?></span>
                        <span class="fp-consent-log-summary__metric"><?php echo esc_html( number_format_i18n( isset( $summary['total'] ) ? (int) $summary['total'] : 0 ) ); ?></span>
                        <p class="fp-consent-log-summary__description">
                            <?php
                            if ( $last_event_date ) {
                                printf(
                                    esc_html__( 'Ultimo evento registrato: %s', 'fp-privacy-cookie-policy' ),
                                    esc_html( $last_event_date )
                                );
                            } else {
                                esc_html_e( 'Nessun evento registrato finora.', 'fp-privacy-cookie-policy' );
                            }
                            ?>
                        </p>
                    </div>
                    <div class="fp-consent-log-summary__card">
                        <span class="fp-consent-log-summary__heading"><?php esc_html_e( 'Distribuzione totale', 'fp-privacy-cookie-policy' ); ?></span>
                        <ul class="fp-consent-log-summary__list">
                            <?php
                            $total_events = isset( $summary['total'] ) ? (int) $summary['total'] : 0;

                            foreach ( $summary_labels as $event_key => $label ) {
                                $count = isset( $summary['events'][ $event_key ] ) ? (int) $summary['events'][ $event_key ] : 0;

                                if ( 'other' === $event_key && 0 === $count ) {
                                    continue;
                                }
                                ?>
                                <li class="fp-consent-log-summary__row">
                                    <span class="fp-consent-log-summary__label"><?php echo esc_html( $label ); ?></span>
                                    <span class="fp-consent-log-summary__value">
                                        <?php echo esc_html( number_format_i18n( $count ) ); ?>
                                        <?php if ( $total_events > 0 ) : ?>
                                            <span class="fp-consent-log-summary__muted"><?php echo esc_html( number_format_i18n( $this->calculate_percentage( $count, $total_events ), 1 ) ); ?>%</span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="fp-consent-log-summary__card">
                        <span class="fp-consent-log-summary__heading"><?php printf( esc_html__( 'Ultimi %d giorni', 'fp-privacy-cookie-policy' ), max( 1, $recent_days ) ); ?></span>
                        <?php if ( $recent_total > 0 ) : ?>
                            <ul class="fp-consent-log-summary__list">
                                <li class="fp-consent-log-summary__row fp-consent-log-summary__row--total">
                                    <span class="fp-consent-log-summary__label"><?php esc_html_e( 'Totale', 'fp-privacy-cookie-policy' ); ?></span>
                                    <span class="fp-consent-log-summary__value"><?php echo esc_html( number_format_i18n( $recent_total ) ); ?></span>
                                </li>
                                <?php
                                foreach ( $summary_labels as $event_key => $label ) {
                                    $count = isset( $summary['recent']['events'][ $event_key ] ) ? (int) $summary['recent']['events'][ $event_key ] : 0;

                                    if ( 'other' === $event_key && 0 === $count ) {
                                        continue;
                                    }

                                    if ( 0 === $count ) {
                                        continue;
                                    }
                                    ?>
                                    <li class="fp-consent-log-summary__row">
                                        <span class="fp-consent-log-summary__label"><?php echo esc_html( $label ); ?></span>
                                        <span class="fp-consent-log-summary__value">
                                            <?php echo esc_html( number_format_i18n( $count ) ); ?>
                                            <span class="fp-consent-log-summary__muted"><?php echo esc_html( number_format_i18n( $this->calculate_percentage( $count, $recent_total ), 1 ) ); ?>%</span>
                                        </span>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        <?php else : ?>
                            <p class="fp-consent-log-summary__description">
                                <?php printf( esc_html__( 'Nessun dato registrato negli ultimi %d giorni.', 'fp-privacy-cookie-policy' ), max( 1, $recent_days ) ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="fp-consent-log-filters">
                <input type="hidden" name="page" value="fp-privacy-cookie-policy" />
                <input type="hidden" name="tab" value="logs" />
                <p class="search-box">
                    <label class="screen-reader-text" for="fp-consent-search"><?php esc_html_e( 'Cerca consensi', 'fp-privacy-cookie-policy' ); ?></label>
                    <input type="search" id="fp-consent-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Cerca per ID, IP o stato', 'fp-privacy-cookie-policy' ); ?>" />
                </p>
                <p class="fp-consent-log-filters__field">
                    <label for="fp-consent-from"><?php esc_html_e( 'Dal giorno', 'fp-privacy-cookie-policy' ); ?></label>
                    <input type="date" id="fp-consent-from" name="from" value="<?php echo esc_attr( $from_raw ); ?>" />
                </p>
                <p class="fp-consent-log-filters__field">
                    <label for="fp-consent-to"><?php esc_html_e( 'Al giorno', 'fp-privacy-cookie-policy' ); ?></label>
                    <input type="date" id="fp-consent-to" name="to" value="<?php echo esc_attr( $to_raw ); ?>" />
                </p>
                <p>
                    <label for="fp-consent-event-filter" class="screen-reader-text"><?php esc_html_e( 'Filtra per evento', 'fp-privacy-cookie-policy' ); ?></label>
                    <select id="fp-consent-event-filter" name="event">
                        <option value=""><?php esc_html_e( 'Tutti gli eventi', 'fp-privacy-cookie-policy' ); ?></option>
                        <?php foreach ( $event_labels as $event_key => $label ) : ?>
                            <option value="<?php echo esc_attr( $event_key ); ?>" <?php selected( $event_key, $event ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Filtra', 'fp-privacy-cookie-policy' ); ?></button>
                    <?php if ( $search || $event || $from_raw || $to_raw ) : ?>
                        <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'fp-privacy-cookie-policy', 'tab' => 'logs' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Reimposta', 'fp-privacy-cookie-policy' ); ?></a>
                    <?php endif; ?>
                </p>
            </form>
            <div class="fp-consent-log-actions">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'fp_export_consent', 'fp_export_consent_nonce' ); ?>
                    <input type="hidden" name="action" value="fp_export_consent" />
                    <input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>" />
                    <input type="hidden" name="event" value="<?php echo esc_attr( $event ); ?>" />
                    <input type="hidden" name="from" value="<?php echo esc_attr( $from_raw ); ?>" />
                    <input type="hidden" name="to" value="<?php echo esc_attr( $to_raw ); ?>" />
                    <button type="submit" class="button button-secondary">
                        <?php esc_html_e( 'Esporta CSV', 'fp-privacy-cookie-policy' ); ?>
                    </button>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'fp_cleanup_consent_logs', 'fp_cleanup_consent_logs_nonce' ); ?>
                    <input type="hidden" name="action" value="fp_cleanup_consent_logs" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $logs_url ); ?>" />
                    <button type="submit" class="button">
                        <?php esc_html_e( 'Pulisci registro', 'fp-privacy-cookie-policy' ); ?>
                    </button>
                </form>
            </div>
            <table class="widefat striped fp-consent-log-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Data', 'fp-privacy-cookie-policy' ); ?></th>
                        <th><?php esc_html_e( 'ID consenso', 'fp-privacy-cookie-policy' ); ?></th>
                        <th><?php esc_html_e( 'Utente', 'fp-privacy-cookie-policy' ); ?></th>
                        <th><?php esc_html_e( 'Evento', 'fp-privacy-cookie-policy' ); ?></th>
                        <th><?php esc_html_e( 'Stato', 'fp-privacy-cookie-policy' ); ?></th>
                        <th><?php esc_html_e( 'IP', 'fp-privacy-cookie-policy' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $logs ) : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
                                <td><code><?php echo esc_html( $log->consent_id ); ?></code></td>
                                <td>
                                    <?php
                                    $user_label = __( 'Anonimo', 'fp-privacy-cookie-policy' );

                                    if ( $log->user_id ) {
                                        $user = get_userdata( (int) $log->user_id );
                                        if ( $user ) {
                                            $user_label = $user->user_login;
                                        }
                                    }

                                    echo esc_html( $user_label );
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $event_key   = sanitize_key( $log->event_type );
                                    $event_label = isset( $event_labels[ $event_key ] ) ? $event_labels[ $event_key ] : $log->event_type;
                                    echo esc_html( $event_label );
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $decoded_state = json_decode( $log->consent_state, true );
                                    $state_items   = is_array( $decoded_state ) ? $this->format_consent_state_for_display( $decoded_state ) : array();

                                    if ( ! empty( $state_items ) ) :
                                        ?>
                                        <ul class="fp-consent-log-state">
                                            <?php foreach ( $state_items as $state_key => $state_item ) : // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable ?>
                                                <?php
                                                $item_classes = array( 'fp-consent-log-state__item' );

                                                if ( ! empty( $state_item['required'] ) ) {
                                                    $item_classes[] = 'is-required';
                                                }

                                                $item_classes[] = ! empty( $state_item['value'] ) ? 'is-granted' : 'is-denied';
                                                ?>
                                                <li class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>">
                                                    <span class="fp-consent-log-state__label"><?php echo esc_html( $state_item['label'] ); ?></span>
                                                    <span class="fp-consent-log-state__status">
                                                        <?php
                                                        if ( ! empty( $state_item['required'] ) ) {
                                                            esc_html_e( 'Sempre attivo', 'fp-privacy-cookie-policy' );
                                                        } elseif ( ! empty( $state_item['value'] ) ) {
                                                            esc_html_e( 'Consentito', 'fp-privacy-cookie-policy' );
                                                        } else {
                                                            esc_html_e( 'Negato', 'fp-privacy-cookie-policy' );
                                                        }
                                                        ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <details class="fp-consent-log-state__raw">
                                            <summary><?php esc_html_e( 'Mostra JSON', 'fp-privacy-cookie-policy' ); ?></summary>
                                            <code><?php echo esc_html( $log->consent_state ); ?></code>
                                        </details>
                                        <?php
                                    else :
                                        ?>
                                        <code><?php echo esc_html( $log->consent_state ); ?></code>
                                        <?php
                                    endif;
                                    ?>
                                </td>
                                <td><?php echo esc_html( $log->ip_address ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6">
                                <?php
                                if ( $search || $event ) {
                                    esc_html_e( 'Nessun consenso corrisponde ai criteri di ricerca.', 'fp-privacy-cookie-policy' );
                                } else {
                                    esc_html_e( 'Nessun consenso registrato al momento.', 'fp-privacy-cookie-policy' );
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ( $pages > 1 ) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo wp_kses_post(
                            paginate_links(
                                array(
                                    'base'      => $logs_url . '%_%',
                                    'format'    => '&paged=%#%',
                                    'prev_text' => __( '&laquo;', 'fp-privacy-cookie-policy' ),
                                    'next_text' => __( '&raquo;', 'fp-privacy-cookie-policy' ),
                                    'total'     => $pages,
                                    'current'   => $paged,
                                    'add_args'  => array(),
                                )
                            )
                        );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php
        }

        /**
         * Retrieve an aggregate summary of the consent log for quick insights.
         *
         * @param int $days Number of days to include in the recent window.
         *
         * @return array
         */
        protected function get_consent_log_summary( $days = 30 ) {
            $recent_days = max( 1, (int) $days );

            $summary = array(
                'total'      => 0,
                'last_event' => '',
                'events'     => array(),
                'recent'     => array(
                    'days'   => $recent_days,
                    'total'  => 0,
                    'events' => array(),
                ),
            );

            if ( ! $this->consent_table_exists() ) {
                return $summary;
            }

            global $wpdb;

            $table_name = self::get_consent_table_name();
            $allowed    = array_values( array_unique( array_map( 'sanitize_key', $this->get_allowed_consent_events() ) ) );

            $template = array();

            foreach ( $allowed as $event ) {
                if ( '' === $event ) {
                    continue;
                }

                $template[ $event ] = 0;
            }

            $template['other'] = 0;

            $summary['events']             = $template;
            $summary['recent']['events']   = $template;
            $summary['total']              = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
            $summary['last_event']         = (string) $wpdb->get_var( "SELECT created_at FROM {$table_name} ORDER BY created_at DESC LIMIT 1" );

            $counts = $wpdb->get_results( "SELECT event_type, COUNT(*) AS total FROM {$table_name} GROUP BY event_type" );

            if ( $counts ) {
                foreach ( $counts as $row ) {
                    $event = sanitize_key( $row->event_type );
                    $count = (int) $row->total;

                    if ( isset( $summary['events'][ $event ] ) ) {
                        $summary['events'][ $event ] = $count;
                    } else {
                        $summary['events']['other'] += $count;
                    }
                }
            }

            $threshold = $this->get_recent_summary_threshold( $recent_days );

            if ( $threshold ) {
                $recent_counts = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT event_type, COUNT(*) AS total FROM {$table_name} WHERE created_at >= %s GROUP BY event_type",
                        $threshold
                    )
                );

                if ( $recent_counts ) {
                    $recent_total = 0;

                    foreach ( $recent_counts as $row ) {
                        $event = sanitize_key( $row->event_type );
                        $count = (int) $row->total;

                        $recent_total += $count;

                        if ( isset( $summary['recent']['events'][ $event ] ) ) {
                            $summary['recent']['events'][ $event ] = $count;
                        } else {
                            $summary['recent']['events']['other'] += $count;
                        }
                    }

                    $summary['recent']['total'] = $recent_total;
                }
            }

            if ( ! isset( $summary['recent']['total'] ) ) {
                $summary['recent']['total'] = array_sum( $summary['recent']['events'] );
            }

            return $summary;
        }

        /**
         * Format a consent log datetime for display using the site preferences.
         *
         * @param string $datetime Datetime string stored in the database.
         *
         * @return string
         */
        protected function format_consent_datetime( $datetime ) {
            if ( empty( $datetime ) ) {
                return '';
            }

            try {
                $timezone = wp_timezone();
                $object   = date_create_from_format( 'Y-m-d H:i:s', $datetime, $timezone );

                if ( $object instanceof DateTimeInterface ) {
                    $format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' );

                    return wp_date( $format, $object->getTimestamp(), $timezone );
                }
            } catch ( Exception $exception ) {
                return '';
            }

            return '';
        }

        /**
         * Prepare consent state metadata for display within the admin log.
         *
         * @param array $state Normalized consent state payload.
         *
         * @return array[]
         */
        protected function format_consent_state_for_display( array $state ) {
            $categories = $this->get_consent_log_categories();

            if ( empty( $categories ) ) {
                return array();
            }

            $display = array();

            foreach ( $categories as $key => $category ) {
                $value = isset( $state[ $key ] ) ? (bool) $state[ $key ] : false;

                if ( ! empty( $category['required'] ) ) {
                    $value = true;
                }

                $display[ $key ] = array(
                    'label'    => $category['label'],
                    'required' => ! empty( $category['required'] ),
                    'value'    => $value,
                );
            }

            foreach ( $state as $key => $value ) {
                $key = sanitize_key( $key );

                if ( 0 === strpos( $key, '__' ) ) {
                    continue;
                }

                if ( isset( $display[ $key ] ) ) {
                    continue;
                }

                $display[ $key ] = array(
                    'label'    => $key,
                    'required' => false,
                    'value'    => (bool) $value,
                );
            }

            /**
             * Filter the consent state display metadata before rendering it in the admin log.
             *
             * @param array $display   Prepared display metadata.
             * @param array $state     Original normalized consent state.
             * @param array $categories Category metadata sourced from the plugin settings.
             */
            return (array) apply_filters( 'fp_privacy_consent_state_display', $display, $state, $categories );
        }

        /**
         * Retrieve consent categories metadata for the admin log display.
         *
         * @return array
         */
        protected function get_consent_log_categories() {
            if ( null !== $this->consent_log_categories ) {
                return $this->consent_log_categories;
            }

            $settings   = $this->get_settings();
            $categories = isset( $settings['categories'] ) && is_array( $settings['categories'] ) ? $settings['categories'] : array();
            $prepared   = array();

            foreach ( $categories as $key => $category ) {
                $key = sanitize_key( $key );

                if ( '' === $key ) {
                    continue;
                }

                $label = isset( $category['label'] ) && '' !== $category['label'] ? $category['label'] : $key;

                $prepared[ $key ] = array(
                    'label'    => $label,
                    'required' => ! empty( $category['required'] ),
                );
            }

            $this->consent_log_categories = $prepared;

            return $this->consent_log_categories;
        }

        /**
         * Build the lower bound datetime for the recent consent summary window.
         *
         * @param int $days Days to subtract from the current time.
         *
         * @return string
         */
        protected function get_recent_summary_threshold( $days ) {
            $days = max( 1, (int) $days );

            try {
                $timezone = wp_timezone();
                $now      = new DateTimeImmutable( 'now', $timezone );
                $point    = $now->modify( sprintf( '-%d days', $days ) );

                return $point->format( 'Y-m-d H:i:s' );
            } catch ( Exception $exception ) {
                return '';
            }
        }

        /**
         * Calculate a percentage with a single decimal precision.
         *
         * @param int|float $value Current value.
         * @param int|float $total Total reference.
         *
         * @return float
         */
        protected function calculate_percentage( $value, $total ) {
            $total = (float) $total;

            if ( $total <= 0 ) {
                return 0;
            }

            $value = (float) $value;

            return round( ( $value / $total ) * 100, 1 );
        }

        /**
         * Retrieve human readable labels for consent events.
         *
         * @return array
         */
        protected function get_consent_event_labels() {
            return array(
                'accept_all'       => __( 'Accetta tutto', 'fp-privacy-cookie-policy' ),
                'reject_all'       => __( 'Rifiuta tutto', 'fp-privacy-cookie-policy' ),
                'save_preferences' => __( 'Salva preferenze', 'fp-privacy-cookie-policy' ),
                'save'             => __( 'Salva', 'fp-privacy-cookie-policy' ),
            );
        }

        /**
         * Render help tab.
         */
        protected function render_help_tab() {
            $steps          = $this->get_onboarding_steps();
            $total_steps    = count( $steps );
            $completed_steps = array_reduce(
                $steps,
                static function ( $carry, $step ) {
                    return $carry + ( ! empty( $step['complete'] ) ? 1 : 0 );
                },
                0
            );
            $progress = $total_steps > 0 ? round( ( $completed_steps / $total_steps ) * 100 ) : 0;
            $settings = $this->get_settings();
            $revision = isset( $settings['consent_revision'] ) ? (int) $settings['consent_revision'] : 1;

            if ( $revision < 1 ) {
                $revision = 1;
            }

            $revision_updated_at = isset( $settings['consent_revision_updated_at'] ) ? $settings['consent_revision_updated_at'] : '';
            $revision_formatted   = $revision_updated_at ? $this->format_consent_datetime( $revision_updated_at ) : '';
            $revision_label       = $revision_formatted ? $revision_formatted : __( 'Mai', 'fp-privacy-cookie-policy' );
            $reset_redirect       = admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=help' );
            ?>
            <div class="fp-help-tab">
                <section class="fp-onboarding" data-fp-onboarding>
                    <div class="fp-onboarding__header">
                        <h2><?php esc_html_e( 'Checklist di conformità', 'fp-privacy-cookie-policy' ); ?></h2>
                        <p><?php esc_html_e( 'Completa i passaggi fondamentali per rendere operativo il banner e mantenere la documentazione aggiornata.', 'fp-privacy-cookie-policy' ); ?></p>
                        <div class="fp-onboarding__progress" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $total_steps ); ?>" aria-valuenow="<?php echo esc_attr( $completed_steps ); ?>">
                            <div class="fp-onboarding__progress-bar" style="width: <?php echo esc_attr( $progress ); ?>%"></div>
                            <span class="fp-onboarding__progress-label">
                                <strong data-fp-progress-count><?php echo esc_html( $completed_steps ); ?></strong>
                                /
                                <?php echo esc_html( $total_steps ); ?>
                            </span>
                        </div>
                        <button type="button" class="button button-secondary" data-fp-start-wizard><?php esc_html_e( 'Avvia checklist guidata', 'fp-privacy-cookie-policy' ); ?></button>
                    </div>
                    <ol class="fp-onboarding__steps" data-fp-steps>
                        <?php foreach ( $steps as $step ) :
                            $is_complete = ! empty( $step['complete'] );
                            $is_auto     = ! empty( $step['auto'] );
                            ?>
                            <li class="fp-onboarding__step<?php echo $is_complete ? ' is-complete' : ''; ?>" data-fp-step="<?php echo esc_attr( $step['key'] ); ?>" data-fp-step-auto="<?php echo $is_auto ? '1' : '0'; ?>" data-fp-step-complete="<?php echo $is_complete ? '1' : '0'; ?>">
                                <label>
                                    <input type="checkbox" <?php checked( $is_complete ); ?> <?php disabled( $is_auto ); ?> data-fp-step-checkbox />
                                    <span class="fp-onboarding__step-title"><?php echo esc_html( $step['title'] ); ?></span>
                                </label>
                                <p class="fp-onboarding__step-description"><?php echo esc_html( $step['description'] ); ?></p>
                                <?php if ( ! empty( $step['link'] ) ) : ?>
                                    <a class="button button-link fp-onboarding__step-link" href="<?php echo esc_url( $step['link'] ); ?>">
                                        <?php echo esc_html( isset( $step['link_label'] ) ? $step['link_label'] : __( 'Apri impostazioni', 'fp-privacy-cookie-policy' ) ); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ( $is_auto ) : ?>
                                    <span class="fp-onboarding__badge"><?php esc_html_e( 'Automatico', 'fp-privacy-cookie-policy' ); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>

                <section class="fp-help-section" id="fp-help-shortcodes">
                    <h3><?php esc_html_e( 'Shortcode e blocchi disponibili', 'fp-privacy-cookie-policy' ); ?></h3>
                    <p><?php esc_html_e( 'Puoi inserire i contenuti generati utilizzando shortcode o i blocchi Gutenberg inclusi nel plugin.', 'fp-privacy-cookie-policy' ); ?></p>
                    <ul>
                        <li><code>[fp_privacy_policy]</code> &mdash; <?php esc_html_e( 'Mostra il testo della privacy policy configurato.', 'fp-privacy-cookie-policy' ); ?></li>
                        <li><code>[fp_cookie_policy]</code> &mdash; <?php esc_html_e( 'Mostra il testo della cookie policy.', 'fp-privacy-cookie-policy' ); ?></li>
                        <li><code>[fp_cookie_preferences]</code> &mdash; <?php esc_html_e( 'Inserisce un pulsante per riaprire le preferenze di consenso.', 'fp-privacy-cookie-policy' ); ?></li>
                        <li><code>[fp_cookie_banner]</code> &mdash; <?php esc_html_e( 'Visualizza manualmente il banner nelle pagine desiderate.', 'fp-privacy-cookie-policy' ); ?></li>
                    </ul>
                    <p><?php esc_html_e( 'Nel blocco editor troverai le varianti “FP Privacy Policy”, “FP Cookie Policy”, “FP Gestisci preferenze cookie” e “FP Banner cookie” per integrare i contenuti senza usare shortcode.', 'fp-privacy-cookie-policy' ); ?></p>
                </section>

                <section class="fp-help-section" id="fp-help-backup">
                    <h3><?php esc_html_e( 'Esporta o importa la configurazione', 'fp-privacy-cookie-policy' ); ?></h3>
                    <p><?php esc_html_e( 'Salva un backup delle impostazioni o ripristina rapidamente la stessa configurazione su un altro sito.', 'fp-privacy-cookie-policy' ); ?></p>
                    <div class="fp-settings-backup">
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'fp_export_settings', 'fp_export_settings_nonce' ); ?>
                            <input type="hidden" name="action" value="fp_export_settings" />
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Esporta impostazioni', 'fp-privacy-cookie-policy' ); ?></button>
                        </form>
                        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'fp_import_settings', 'fp_import_settings_nonce' ); ?>
                            <input type="hidden" name="action" value="fp_import_settings" />
                            <label class="screen-reader-text" for="fp_settings_file"><?php esc_html_e( 'File impostazioni', 'fp-privacy-cookie-policy' ); ?></label>
                            <input type="file" id="fp_settings_file" name="fp_settings_file" accept="application/json" />
                            <button type="submit" class="button"><?php esc_html_e( 'Importa impostazioni', 'fp-privacy-cookie-policy' ); ?></button>
                        </form>
                    </div>
                    <p class="description"><?php esc_html_e( 'L\'importazione sovrascrive le impostazioni attuali. Utilizza solo file JSON generati da FP Privacy and Cookie Policy.', 'fp-privacy-cookie-policy' ); ?></p>
                </section>

                <section class="fp-help-section" id="fp-help-consent-reset">
                    <h3><?php esc_html_e( 'Richiedi nuovamente il consenso', 'fp-privacy-cookie-policy' ); ?></h3>
                    <p><?php esc_html_e( 'Aumenta la revisione del consenso per mostrare di nuovo il banner dopo modifiche rilevanti alle informative o alle impostazioni di tracking.', 'fp-privacy-cookie-policy' ); ?></p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fp-consent-reset">
                        <?php wp_nonce_field( 'fp_reset_consent_revision', 'fp_reset_consent_revision_nonce' ); ?>
                        <input type="hidden" name="action" value="fp_reset_consent_revision" />
                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $reset_redirect ); ?>" />
                        <button type="submit" class="button button-secondary"><?php esc_html_e( 'Richiedi nuovo consenso', 'fp-privacy-cookie-policy' ); ?></button>
                    </form>
                    <p class="fp-consent-reset__meta">
                        <?php
                        $revision_message = sprintf(
                            /* translators: 1: consent revision number, 2: formatted datetime or "Mai". */
                            __( 'Revisione corrente: %1$d. Ultimo reset: %2$s.', 'fp-privacy-cookie-policy' ),
                            (int) $revision,
                            $revision_label
                        );

                        echo esc_html( $revision_message );
                        ?>
                    </p>
                </section>

                <section class="fp-help-section" id="fp-help-preview">
                    <h3><?php esc_html_e( 'Testa il banner senza salvare consensi', 'fp-privacy-cookie-policy' ); ?></h3>
                    <p><?php esc_html_e( 'Utilizza la modalità anteprima per mostrare il banner solo agli amministratori e verificare layout, traduzioni e integrazioni JavaScript senza registrare eventi nel log.', 'fp-privacy-cookie-policy' ); ?></p>
                    <ul>
                        <li><?php esc_html_e( 'Attiva la modalità anteprima dalla tab Impostazioni per forzare sempre il banner agli amministratori.', 'fp-privacy-cookie-policy' ); ?></li>
                        <li>
                            <?php
                            printf(
                                /* translators: %s is the preview query string parameter. */
                                esc_html__( 'In alternativa aggiungi %s all\'URL del sito per abilitare l\'anteprima temporaneamente.', 'fp-privacy-cookie-policy' ),
                                '<code>?' . esc_html( self::PREVIEW_QUERY_KEY ) . '=1</code>'
                            );
                            ?>
                        </li>
                        <li><?php esc_html_e( 'In modalità anteprima le scelte non vengono salvate in cookie né inviate al registro, ma vengono comunque emessi gli eventi per testare GTM o script personalizzati.', 'fp-privacy-cookie-policy' ); ?></li>
                    </ul>
                </section>

                <section class="fp-help-section" id="fp-help-consent-mode">
                    <h3><?php esc_html_e( 'Come collegare Google Consent Mode v2', 'fp-privacy-cookie-policy' ); ?></h3>
                    <p><?php esc_html_e( 'Il plugin aggiorna automaticamente i segnali del Consent Mode (analytics_storage, ad_storage, ad_personalization, ad_user_data, functionality_storage, security_storage). Inserisci il tag gtag o Google Tag Manager dopo il banner e ascolta l\'evento fp_consent_update se utilizzi script personalizzati.', 'fp-privacy-cookie-policy' ); ?></p>
                    <p>
                        <code>
                            &lt;script&gt;
                            window.dataLayer = window.dataLayer || [];
                            function gtag(){dataLayer.push(arguments);}
                            gtag('js', new Date());
                            gtag('config', 'G-XXXXXXX');
                            &lt;/script&gt;
                        </code>
                    </p>
                    <p><?php esc_html_e( 'Ricorda di programmare il richiamo del consenso ogni 12 mesi o quando cambiano le finalità del trattamento.', 'fp-privacy-cookie-policy' ); ?></p>
                </section>

                <section class="fp-help-section">
                    <h3><?php esc_html_e( 'Suggerimenti operativi', 'fp-privacy-cookie-policy' ); ?></h3>
                    <ul>
                        <li><?php esc_html_e( 'Sfrutta il selettore "Posizionamento banner" per scegliere tra footer, apertura del body o inserimento manuale con blocchi/shortcode.', 'fp-privacy-cookie-policy' ); ?></li>
                        <li><?php esc_html_e( 'Dopo aver raccolto il consenso testa il comportamento dei tag di marketing assicurandoti che rispettino le preferenze scelte.', 'fp-privacy-cookie-policy' ); ?></li>
                        <li><?php esc_html_e( 'Programma la pulizia periodica del registro consensi in base alla durata impostata nelle impostazioni.', 'fp-privacy-cookie-policy' ); ?></li>
                    </ul>
                </section>
            </div>
            <?php
        }
        /**
         * Handle consent saving via AJAX.
         */
        public function ajax_save_consent() {
            check_ajax_referer( self::NONCE_ACTION, 'nonce' );

            $consent_id = isset( $_POST['consentId'] ) ? $this->sanitize_consent_identifier( wp_unslash( $_POST['consentId'] ) ) : '';
            $event      = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : 'save_preferences';
            $consent    = isset( $_POST['consent'] ) ? wp_unslash( $_POST['consent'] ) : array();

            if ( ! is_array( $consent ) ) {
                wp_send_json_error( array( 'message' => __( 'Dati non validi.', 'fp-privacy-cookie-policy' ) ), 400 );
            }

            $cookie_consent_id = isset( $_COOKIE[ self::CONSENT_COOKIE . '_id' ] )
                ? $this->sanitize_consent_identifier( wp_unslash( $_COOKIE[ self::CONSENT_COOKIE . '_id' ] ) )
                : '';

            if ( $cookie_consent_id ) {
                $consent_id = $cookie_consent_id;
            } elseif ( empty( $consent_id ) ) {
                $consent_id = $this->sanitize_consent_identifier( $this->get_consent_id() );
            }

            if ( empty( $consent_id ) ) {
                wp_send_json_error( array( 'message' => __( 'Dati non validi.', 'fp-privacy-cookie-policy' ) ), 400 );
            }

            $allowed_events = $this->get_allowed_consent_events();

            if ( ! in_array( $event, $allowed_events, true ) ) {
                $event = 'save_preferences';
            }

            $sanitized = array();

            foreach ( $consent as $key => $value ) {
                $sanitized[ sanitize_key( $key ) ] = rest_sanitize_boolean( $value );
            }

            $normalized = $this->normalize_consent_state( $sanitized );

            if ( empty( $normalized ) ) {
                wp_send_json_error( array( 'message' => __( 'Impossibile registrare il consenso. Riprova.', 'fp-privacy-cookie-policy' ) ), 400 );
            }

            if ( ! $this->log_consent( $consent_id, $event, $normalized ) ) {
                wp_send_json_error( array( 'message' => __( 'Impossibile registrare il consenso. Riprova.', 'fp-privacy-cookie-policy' ) ), 500 );
            }

            wp_send_json_success(
                array(
                    'message'   => __( 'Consenso aggiornato.', 'fp-privacy-cookie-policy' ),
                    'consentId' => $consent_id,
                )
            );
        }

        /**
         * Retrieve the allowed consent event identifiers.
         *
         * @return array
         */
        protected function get_allowed_consent_events() {
            $events = array( 'accept_all', 'reject_all', 'save_preferences', 'save' );

            /**
             * Filter the list of allowed consent events accepted via AJAX.
             *
             * @param array $events Allowed events.
             */
            return (array) apply_filters( 'fp_privacy_allowed_consent_events', $events );
        }

        /**
         * Normalize the consent state before logging it.
         *
         * @param array $consent Raw consent payload.
         *
         * @return array
         */
        protected function normalize_consent_state( array $consent ) {
            $settings   = $this->get_settings();
            $categories = isset( $settings['categories'] ) && is_array( $settings['categories'] ) ? $settings['categories'] : array();

            if ( empty( $categories ) ) {
                return array();
            }

            $normalized = array();

            foreach ( $categories as $key => $category ) {
                $key        = sanitize_key( $key );
                $is_required = ! empty( $category['required'] );

                if ( $is_required ) {
                    $normalized[ $key ] = true;
                    continue;
                }

                if ( array_key_exists( $key, $consent ) ) {
                    $normalized[ $key ] = (bool) $consent[ $key ];
                } else {
                    $normalized[ $key ] = false;
                }
            }

            $revision = isset( $settings['consent_revision'] ) ? (int) $settings['consent_revision'] : 1;

            if ( $revision < 1 ) {
                $revision = 1;
            }

            $normalized['__revision'] = $revision;

            if ( ! empty( $settings['consent_revision_updated_at'] ) ) {
                $normalized['__revision_updated_at'] = $settings['consent_revision_updated_at'];
            }

            /**
             * Filter the normalized consent state before it is persisted.
             *
             * @param array $normalized Normalized consent data.
             * @param array $categories Plugin categories configuration.
             */
            return apply_filters( 'fp_privacy_normalized_consent_state', $normalized, $categories );
        }

        /**
         * Register custom Site Health tests.
         *
         * @param array $tests Existing Site Health tests.
         *
         * @return array
         */
        public function register_site_health_tests( $tests ) {
            if ( ! is_array( $tests ) ) {
                $tests = array();
            }

            if ( ! isset( $tests['direct'] ) || ! is_array( $tests['direct'] ) ) {
                $tests['direct'] = array();
            }

            $tests['direct']['fp_privacy_consent_table'] = array(
                'label' => __( 'FP Privacy & Cookie Policy consent log table', 'fp-privacy-cookie-policy' ),
                'test'  => array( $this, 'site_health_test_consent_table' ),
            );

            $tests['direct']['fp_privacy_cleanup_schedule'] = array(
                'label' => __( 'FP Privacy & Cookie Policy consent cleanup schedule', 'fp-privacy-cookie-policy' ),
                'test'  => array( $this, 'site_health_test_cleanup_schedule' ),
            );

            return $tests;
        }

        /**
         * Site Health test that verifies the consent log table exists.
         *
         * @return array
         */
        public function site_health_test_consent_table() {
            $badge = array(
                'label' => __( 'FP Privacy & Cookie Policy', 'fp-privacy-cookie-policy' ),
                'color' => 'blue',
            );

            if ( $this->consent_table_exists() ) {
                return array(
                    'status'      => 'good',
                    'label'       => __( 'Consent log table is operational', 'fp-privacy-cookie-policy' ),
                    'description' => sprintf(
                        '<p>%s</p>',
                        esc_html__( 'The consent log table is available and ready to store new consent events.', 'fp-privacy-cookie-policy' )
                    ),
                    'badge'       => $badge,
                    'test'        => 'fp_privacy_consent_table',
                );
            }

            $actions = sprintf(
                '<p><a href="%1$s" class="button button-primary">%2$s</a></p>',
                esc_url( admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=logs' ) ),
                esc_html__( 'Open consent tools', 'fp-privacy-cookie-policy' )
            );

            return array(
                'status'      => 'critical',
                'label'       => __( 'Consent log table is missing', 'fp-privacy-cookie-policy' ),
                'description' => sprintf(
                    '<p>%s</p>',
                    esc_html__( 'The consent log table could not be found. Consents will not be tracked until the table is recreated.', 'fp-privacy-cookie-policy' )
                ),
                'actions'     => $actions,
                'badge'       => $badge,
                'test'        => 'fp_privacy_consent_table',
            );
        }

        /**
         * Site Health test that checks whether the cleanup cron event is scheduled.
         *
         * @return array
         */
        public function site_health_test_cleanup_schedule() {
            $badge = array(
                'label' => __( 'FP Privacy & Cookie Policy', 'fp-privacy-cookie-policy' ),
                'color' => 'blue',
            );

            $timestamp = wp_next_scheduled( self::CLEANUP_HOOK );

            if ( ! $timestamp ) {
                self::schedule_cleanup_event();
                $timestamp = wp_next_scheduled( self::CLEANUP_HOOK );
            }

            if ( $timestamp ) {
                $diff = human_time_diff( time(), $timestamp );

                return array(
                    'status'      => 'good',
                    'label'       => __( 'Consent log cleanup is scheduled', 'fp-privacy-cookie-policy' ),
                    'description' => sprintf(
                        '<p>%s</p>',
                        sprintf(
                            /* translators: %s is a human readable time interval. */
                            esc_html__( 'The consent log cleanup event is scheduled to run in %s.', 'fp-privacy-cookie-policy' ),
                            esc_html( $diff )
                        )
                    ),
                    'badge'       => $badge,
                    'test'        => 'fp_privacy_cleanup_schedule',
                );
            }

            $actions = sprintf(
                '<p><a href="%1$s" class="button">%2$s</a></p>',
                esc_url( admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=logs' ) ),
                esc_html__( 'Review retention settings', 'fp-privacy-cookie-policy' )
            );

            return array(
                'status'      => 'recommended',
                'label'       => __( 'Consent log cleanup is not scheduled', 'fp-privacy-cookie-policy' ),
                'description' => sprintf(
                    '<p>%s</p>',
                    esc_html__( 'The consent log cleanup task is not scheduled. Old consent records may accumulate over time.', 'fp-privacy-cookie-policy' )
                ),
                'actions'     => $actions,
                'badge'       => $badge,
                'test'        => 'fp_privacy_cleanup_schedule',
            );
        }

        /**
         * Log consent event.
         *
         * @param string $consent_id Consent ID.
         * @param string $event      Event type.
         * @param array  $consent    Consent data.
         *
         * @return bool
         */
        protected function log_consent( $consent_id, $event, $consent ) {
            if ( ! $this->consent_table_exists() ) {
                self::create_consent_table();
            }

            global $wpdb;

            $table_name = self::get_consent_table_name();

            $consent_id = $this->sanitize_consent_identifier( $consent_id );

            if ( '' === $consent_id ) {
                return false;
            }

            $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
            $ip_address = wp_privacy_anonymize_ip( $ip_address );
            $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
            $user_id    = get_current_user_id();

            $data = array(
                'consent_id'    => $consent_id,
                'event_type'    => $event,
                'consent_state' => wp_json_encode( $consent ),
                'ip_address'    => $ip_address,
                'user_agent'    => $user_agent,
                'created_at'    => current_time( 'mysql' ),
            );
            $format = array( '%s', '%s', '%s', '%s', '%s', '%s' );

            if ( $user_id ) {
                $data['user_id'] = (int) $user_id;
                $format[]        = '%d';
            }

            $result = $wpdb->insert( $table_name, $data, $format );

            return false !== $result;
        }

        /**
         * Export consent logs as CSV.
         */
        public function export_consent_logs() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Non autorizzato.', 'fp-privacy-cookie-policy' ) );
            }

            check_admin_referer( 'fp_export_consent', 'fp_export_consent_nonce' );

            global $wpdb;

            if ( ! $this->consent_table_exists() ) {
                wp_die( esc_html__( 'La tabella del registro consensi non è disponibile.', 'fp-privacy-cookie-policy' ) );
            }

            if ( function_exists( 'wp_raise_memory_limit' ) ) {
                wp_raise_memory_limit( 'admin' );
            }

            ignore_user_abort( true );

            if ( function_exists( 'set_time_limit' ) ) {
                @set_time_limit( 0 );
            }

            $table_name = self::get_consent_table_name();
            $batch_size = $this->get_export_batch_size();

            $filters = $this->prepare_consent_log_filters(
                array(
                    'search' => isset( $_POST['s'] ) ? wp_unslash( $_POST['s'] ) : '',
                    'event'  => isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : '',
                    'from'   => isset( $_POST['from'] ) ? wp_unslash( $_POST['from'] ) : '',
                    'to'     => isset( $_POST['to'] ) ? wp_unslash( $_POST['to'] ) : '',
                )
            );

            $where_sql = '';

            if ( ! empty( $filters['where'] ) ) {
                $where_sql = ' WHERE ' . implode( ' AND ', $filters['where'] );
            }

            nocache_headers();
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=fp-consent-logs-' . gmdate( 'Ymd-His' ) . '.csv' );

            $output = fopen( 'php://output', 'w' );

            if ( false === $output ) {
                wp_die( esc_html__( 'Impossibile aprire lo stream di esportazione.', 'fp-privacy-cookie-policy' ) );
            }

            fputcsv(
                $output,
                array( 'created_at', 'consent_id', 'user_id', 'event_type', 'consent_state', 'ip_address', 'user_agent' )
            );

            $offset = 0;

            do {
                $query_args = array_merge( $filters['params'], array( $batch_size, $offset ) );
                $query_sql  = "SELECT * FROM {$table_name}{$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";

                $logs = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_args ) );

                if ( empty( $logs ) ) {
                    break;
                }

                foreach ( $logs as $log ) {
                    fputcsv(
                        $output,
                        array(
                            $log->created_at,
                            $log->consent_id,
                            $log->user_id,
                            $log->event_type,
                            $log->consent_state,
                            $log->ip_address,
                            $log->user_agent,
                        )
                    );
                }

                fflush( $output );
                flush();

                $offset += $batch_size;
            } while ( count( $logs ) === $batch_size );

            fclose( $output );

            exit;
        }

        /**
         * Build the payload exported when downloading plugin settings.
         *
         * @return array
         */
        public function get_settings_export_payload() {
            return array(
                'plugin'      => 'fp-privacy-cookie-policy',
                'version'     => self::VERSION,
                'exported_at' => gmdate( 'c' ),
                'site'        => get_bloginfo( 'name' ),
                'settings'    => $this->get_settings(),
            );
        }

        /**
         * Export the plugin settings as a JSON file.
         */
        public function handle_export_settings() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Non autorizzato.', 'fp-privacy-cookie-policy' ) );
            }

            check_admin_referer( 'fp_export_settings', 'fp_export_settings_nonce' );

            $payload = $this->get_settings_export_payload();

            $json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

            if ( false === $json ) {
                $redirect = add_query_arg(
                    'fp_settings_export',
                    'error',
                    admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=help' )
                );

                wp_safe_redirect( $redirect );
                exit;
            }

            $filename = sprintf( 'fp-privacy-settings-%s.json', gmdate( 'Ymd-His' ) );

            nocache_headers();

            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            header( 'Content-Length: ' . strlen( $json ) );

            echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        /**
         * Import plugin settings from a JSON file.
         */
        public function handle_import_settings() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Non autorizzato.', 'fp-privacy-cookie-policy' ) );
            }

            check_admin_referer( 'fp_import_settings', 'fp_import_settings_nonce' );

            $redirect = admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=help' );

            if ( empty( $_FILES['fp_settings_file'] ) || ! is_array( $_FILES['fp_settings_file'] ) ) {
                $redirect = add_query_arg( 'fp_settings_import', 'missing', $redirect );
                wp_safe_redirect( $redirect );
                exit;
            }

            $file = $_FILES['fp_settings_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
                $redirect = add_query_arg( 'fp_settings_import', 'error', $redirect );
                wp_safe_redirect( $redirect );
                exit;
            }

            if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
                $redirect = add_query_arg( 'fp_settings_import', 'missing', $redirect );
                wp_safe_redirect( $redirect );
                exit;
            }

            $raw = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

            if ( false === $raw || '' === $raw ) {
                $redirect = add_query_arg( 'fp_settings_import', 'error', $redirect );
                wp_safe_redirect( $redirect );
                exit;
            }

            $data = json_decode( $raw, true );

            $result = $this->import_settings_from_payload( $data );

            if ( is_wp_error( $result ) ) {
                $error_code = $result->get_error_code();
                $status     = 'error';

                if ( 'fp_privacy_invalid_settings' === $error_code ) {
                    $status = 'invalid';
                }

                $redirect = add_query_arg( 'fp_settings_import', $status, $redirect );
                wp_safe_redirect( $redirect );
                exit;
            }

            $redirect = add_query_arg( 'fp_settings_import', 'success', $redirect );

            wp_safe_redirect( $redirect );
            exit;
        }

        /**
         * Import plugin settings from a payload array.
         *
         * @param array $data Raw data decoded from the settings JSON.
         *
         * @return true|WP_Error
         */
        public function import_settings_from_payload( $data ) {
            if ( ! is_array( $data ) ) {
                return new WP_Error( 'fp_privacy_invalid_settings', __( 'Il file selezionato non contiene impostazioni valide.', 'fp-privacy-cookie-policy' ) );
            }

            $settings_data = isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : $data;

            if ( ! is_array( $settings_data ) ) {
                return new WP_Error( 'fp_privacy_invalid_settings', __( 'Il file selezionato non contiene impostazioni valide.', 'fp-privacy-cookie-policy' ) );
            }

            $sanitized = $this->sanitize_settings( $settings_data );

            update_option( self::OPTION_KEY, $sanitized );
            update_option( self::VERSION_OPTION, self::VERSION );

            $this->flush_settings_cache();

            /**
             * Fires after plugin settings have been imported programmatically.
             *
             * @param array $sanitized Sanitized settings saved in the database.
             */
            do_action( 'fp_privacy_settings_imported', $sanitized );

            return true;
        }

        /**
         * Retrieve the batch size used during CSV export.
         *
         * The value can be customised via the {@see 'fp_privacy_csv_export_batch_size'} filter.
         *
         * @return int
         */
        protected function get_export_batch_size() {
            $batch_size = (int) apply_filters( 'fp_privacy_csv_export_batch_size', 500 );

            if ( $batch_size < 1 ) {
                $batch_size = 500;
            }

            return $batch_size;
        }

        /**
         * Handle consent table recreation requests.
         */
        public function handle_recreate_consent_table() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Non autorizzato.', 'fp-privacy-cookie-policy' ) );
            }

            check_admin_referer( 'fp_recreate_consent_table', 'fp_recreate_consent_table_nonce' );

            self::create_consent_table();
            self::schedule_cleanup_event();
            update_option( self::VERSION_OPTION, self::VERSION );

            $status = $this->consent_table_exists() ? 'success' : 'error';

            $redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
            $fallback = admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=logs' );
            $redirect = $redirect ? wp_validate_redirect( $redirect, $fallback ) : $fallback;
            $redirect = add_query_arg( 'fp_consent_table_status', $status, $redirect );

            wp_safe_redirect( $redirect );
            exit;
        }

        /**
         * Handle manual consent log cleanup requests.
         */
        public function handle_cleanup_consent_logs() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Non autorizzato.', 'fp-privacy-cookie-policy' ) );
            }

            check_admin_referer( 'fp_cleanup_consent_logs', 'fp_cleanup_consent_logs_nonce' );

            $redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
            $fallback = admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=logs' );
            $redirect = $redirect ? wp_validate_redirect( $redirect, $fallback ) : $fallback;

            if ( ! $this->consent_table_exists() ) {
                $redirect = add_query_arg( 'fp_cleanup_status', 'missing', $redirect );
                wp_safe_redirect( $redirect );
                exit;
            }

            $settings       = $this->get_settings();
            $retention_days = $this->get_effective_retention_days( $settings );

            if ( $retention_days < 1 ) {
                $redirect = add_query_arg( 'fp_cleanup_status', 'disabled', $redirect );
                wp_safe_redirect( $redirect );
                exit;
            }

            $removed = $this->cleanup_consent_logs( $settings );

            if ( $removed > 0 ) {
                $redirect = add_query_arg(
                    array(
                        'fp_cleanup_status'  => 'success',
                        'fp_cleanup_removed' => (int) $removed,
                    ),
                    $redirect
                );
            } else {
                $redirect = add_query_arg( 'fp_cleanup_status', 'empty', $redirect );
            }

            wp_safe_redirect( $redirect );
            exit;
        }

        /**
         * Handle consent revision reset requests.
         */
        public function handle_reset_consent_revision() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Non autorizzato.', 'fp-privacy-cookie-policy' ) );
            }

            check_admin_referer( 'fp_reset_consent_revision', 'fp_reset_consent_revision_nonce' );

            $redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
            $fallback = admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=help' );
            $redirect = $redirect ? wp_validate_redirect( $redirect, $fallback ) : $fallback;

            $settings = $this->get_settings();
            $revision = isset( $settings['consent_revision'] ) ? (int) $settings['consent_revision'] : 1;
            $revision++;

            if ( $revision < 1 ) {
                $revision = 1;
            }

            $settings['consent_revision']            = $revision;
            $settings['consent_revision_updated_at'] = current_time( 'mysql' );

            update_option( self::OPTION_KEY, $settings );
            update_option( self::VERSION_OPTION, self::VERSION );

            $this->flush_settings_cache();

            $redirect = add_query_arg( 'fp_consent_revision', 'reset', $redirect );

            wp_safe_redirect( $redirect );
            exit;
        }

        /**
         * Generate or retrieve consent ID.
         *
         * @return string
         */
        protected function get_consent_id() {
            if ( isset( $_COOKIE[ self::CONSENT_COOKIE . '_id' ] ) ) {
                $existing_id = $this->sanitize_consent_identifier( wp_unslash( $_COOKIE[ self::CONSENT_COOKIE . '_id' ] ) );

                if ( $existing_id ) {
                    return $existing_id;
                }
            }

            $consent_id = $this->sanitize_consent_identifier( wp_generate_uuid4() );

            if ( '' === $consent_id ) {
                return '';
            }
            $settings   = $this->get_settings();
            $lifetime   = $this->get_consent_cookie_lifetime( $settings );
            $options    = $this->get_consent_cookie_options( $lifetime, $settings );

            if ( ! headers_sent() ) {
                setcookie( self::CONSENT_COOKIE . '_id', $consent_id, $options );
            }

            $_COOKIE[ self::CONSENT_COOKIE . '_id' ] = $consent_id;

            return $consent_id;
        }

        /**
         * Sanitize the consent identifier to avoid storing unexpected characters.
         *
         * @param string $identifier Raw identifier.
         *
         * @return string
         */
        protected function sanitize_consent_identifier( $identifier ) {
            $identifier = sanitize_text_field( (string) $identifier );
            $identifier = preg_replace( '/[^a-z0-9\-]/i', '', $identifier );

            if ( ! is_string( $identifier ) ) {
                $identifier = '';
            }

            return substr( $identifier, 0, 64 );
        }

        /**
         * Retrieve the consent cookie lifetime in seconds.
         *
         * @param array $settings Plugin settings.
         *
         * @return int
         */
        protected function get_consent_cookie_lifetime( array $settings ) {
            $days = isset( $settings['consent_cookie_days'] ) ? (int) $settings['consent_cookie_days'] : 0;

            if ( $days <= 0 ) {
                return 0;
            }

            $lifetime = (int) $days * DAY_IN_SECONDS;

            /**
             * Filter the lifetime (in seconds) of the consent identifier cookie.
             *
             * @param int   $lifetime Lifetime in seconds.
             * @param int   $days     Lifetime expressed in days.
             * @param array $settings Plugin settings.
             */
            $lifetime = (int) apply_filters( 'fp_privacy_consent_cookie_max_age', $lifetime, $days, $settings );

            if ( $lifetime < 0 ) {
                $lifetime = 0;
            }

            return $lifetime;
        }

        /**
         * Build the options array used to set the consent identifier cookie.
         *
         * @param int   $lifetime Lifetime in seconds.
         * @param array $settings Plugin settings.
         *
         * @return array
         */
        protected function get_consent_cookie_options( $lifetime, array $settings ) {
            $expires      = 0;
            $current_time = time();

            if ( $lifetime > 0 ) {
                $expires = $current_time + $lifetime;

                if ( $expires <= $current_time ) {
                    $expires = 0;
                }
            }

            $options = array(
                'expires'  => $expires,
                'path'     => defined( 'COOKIEPATH' ) ? COOKIEPATH : '/',
                'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            );

            /**
             * Filter the options used when setting the consent identifier cookie.
             *
             * @param array $options  Cookie options.
             * @param int   $lifetime Lifetime in seconds.
             * @param array $settings Plugin settings.
             */
            $options = apply_filters( 'fp_privacy_consent_cookie_options', $options, $lifetime, $settings );

            if ( ! isset( $options['expires'] ) || ! is_int( $options['expires'] ) || $options['expires'] < 0 ) {
                $options['expires'] = 0;
            }

            return $options;
        }

        /**
         * Retrieve consent cookie options suitable for frontend usage.
         *
         * @param array $settings Plugin settings.
         *
         * @return array
         */
        protected function get_frontend_cookie_options( array $settings ) {
            $lifetime = $this->get_consent_cookie_lifetime( $settings );
            $options  = $this->get_consent_cookie_options( $lifetime, $settings );

            return array(
                'path'     => isset( $options['path'] ) ? (string) $options['path'] : '/',
                'domain'   => isset( $options['domain'] ) ? (string) $options['domain'] : '',
                'sameSite' => isset( $options['samesite'] ) ? (string) $options['samesite'] : 'Lax',
                'secure'   => ! empty( $options['secure'] ),
            );
        }

        /**
         * Add the privacy policy content suggestion in the WordPress privacy guide.
         */
        public function add_privacy_policy_content() {
            if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
                return;
            }

            $content  = '<p>' . esc_html__( 'Questo plugin memorizza e registra le preferenze di consenso dei visitatori per aiutarvi a dimostrare la conformità al GDPR.', 'fp-privacy-cookie-policy' ) . '</p>';
            $content .= '<ul>';
            $content .= '<li>' . sprintf(
                /* translators: %s is the cookie name. */
                esc_html__( 'Salviamo le scelte nel cookie tecnico %s per ricordare le preferenze dell\'utente.', 'fp-privacy-cookie-policy' ),
                '<code>' . esc_html( self::CONSENT_COOKIE ) . '</code>'
            ) . '</li>';
            $content .= '<li>' . sprintf(
                /* translators: %s is the cookie name. */
                esc_html__( 'Associamo a ogni browser un identificativo anonimo conservato nel cookie %s per collegare gli eventi registrati.', 'fp-privacy-cookie-policy' ),
                '<code>' . esc_html( self::CONSENT_COOKIE ) . '_id</code>'
            ) . '</li>';
            $content .= '<li>' . esc_html__( 'Registriamo nel database la data e l\'ora, l\'azione scelta, l\'indirizzo IP anonimizzato e il browser utilizzato.', 'fp-privacy-cookie-policy' ) . '</li>';
            $content .= '</ul>';
            $content .= '<p>' . esc_html__( 'I dati vengono conservati per il periodo configurato nelle impostazioni del plugin e sono disponibili per esportazioni o cancellazioni tramite gli strumenti privacy di WordPress.', 'fp-privacy-cookie-policy' ) . '</p>';

            wp_add_privacy_policy_content(
                __( 'FP Privacy and Cookie Policy', 'fp-privacy-cookie-policy' ),
                wp_kses_post( $content )
            );
        }
    }

    register_activation_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'uninstall' ) );

    FP_Privacy_Cookie_Policy::instance();

    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        require_once __DIR__ . '/includes/class-fp-privacy-cli.php';
        \WP_CLI::add_command( 'fp-privacy', new FP_Privacy_CLI( FP_Privacy_Cookie_Policy::instance() ) );
    }
}
