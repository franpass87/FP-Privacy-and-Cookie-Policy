<?php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Plugin URI:  https://example.com/
 * Description: Gestisci privacy policy, cookie policy e consenso informato in modo conforme al GDPR e al Google Consent Mode v2.
 * Version:     1.2.0
 * Author:      FP Digital Assistant
 * Author URI:  https://example.com/
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

if ( ! class_exists( 'FP_Privacy_Cookie_Policy' ) ) {

    class FP_Privacy_Cookie_Policy {

        const OPTION_KEY        = 'fp_privacy_cookie_settings';
        const VERSION           = '1.2.0';
        const VERSION_OPTION    = 'fp_privacy_cookie_version';
        const CONSENT_COOKIE    = 'fp_consent_state';
        const CONSENT_TABLE     = 'fp_consent_logs';
        const NONCE_ACTION      = 'fp_privacy_nonce';
        const DEFAULT_LANGUAGE  = 'it';
        const CLEANUP_HOOK      = 'fp_privacy_cleanup_logs';

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
            add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
            add_action( 'wp_footer', array( $this, 'render_consent_banner' ) );
            add_action( 'init', array( $this, 'register_shortcodes' ) );
            add_action( 'wp_ajax_fp_save_consent', array( $this, 'ajax_save_consent' ) );
            add_action( 'wp_ajax_nopriv_fp_save_consent', array( $this, 'ajax_save_consent' ) );
            add_action( 'admin_post_fp_export_consent', array( $this, 'export_consent_logs' ) );
            add_action( 'admin_post_fp_recreate_consent_table', array( $this, 'handle_recreate_consent_table' ) );
            add_action( self::CLEANUP_HOOK, array( $this, 'cleanup_consent_logs' ) );
            add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_privacy_exporter' ) );
            add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_privacy_eraser' ) );
            add_action( 'admin_notices', array( $this, 'maybe_render_admin_notices' ) );
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
        public static function activate() {
            self::create_consent_table();
            self::schedule_cleanup_event();
            update_option( self::VERSION_OPTION, self::VERSION );
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
        public static function deactivate() {
            wp_clear_scheduled_hook( self::CLEANUP_HOOK );
        }

        /**
         * Register uninstall hook.
         */
        public static function uninstall() {
            delete_option( self::OPTION_KEY );
            delete_option( self::VERSION_OPTION );
            wp_clear_scheduled_hook( self::CLEANUP_HOOK );

            global $wpdb;

            $table_name = self::get_consent_table_name();
            $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

            update_option( self::VERSION_OPTION, self::VERSION );
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
         */
        public function cleanup_consent_logs() {
            if ( ! $this->consent_table_exists() ) {
                return;
            }

            $settings        = $this->get_settings();
            $retention_days  = isset( $settings['retention_days'] ) ? (int) $settings['retention_days'] : 0;
            $retention_days  = (int) apply_filters( 'fp_privacy_consent_retention_days', $retention_days, $settings );

            if ( $retention_days < 1 ) {
                return;
            }

            $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );

            global $wpdb;

            $table_name = self::get_consent_table_name();

            $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE created_at < %s",
                    $cutoff
                )
            );
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
                'retention_days',
                __( 'Conservazione registro consensi', 'fp-privacy-cookie-policy' ),
                array( $this, 'render_retention_field' ),
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
            $output   = $defaults;

            $output['privacy_policy_content'] = isset( $input['privacy_policy_content'] ) ? wp_kses_post( $input['privacy_policy_content'] ) : $defaults['privacy_policy_content'];
            $output['cookie_policy_content']  = isset( $input['cookie_policy_content'] ) ? wp_kses_post( $input['cookie_policy_content'] ) : $defaults['cookie_policy_content'];

            $banner_input       = isset( $input['banner'] ) && is_array( $input['banner'] ) ? $input['banner'] : array();
            $categories_input   = isset( $input['categories'] ) && is_array( $input['categories'] ) ? $input['categories'] : array();
            $google_input       = isset( $input['google_defaults'] ) && is_array( $input['google_defaults'] ) ? $input['google_defaults'] : array();
            $translations_input = isset( $input['translations'] ) && is_array( $input['translations'] ) ? $input['translations'] : array();

            $output['banner']          = $this->sanitize_banner_settings( $banner_input, $defaults['banner'] );
            $output['categories']      = $this->sanitize_categories_settings( $categories_input, $defaults['categories'] );
            $output['google_defaults'] = $this->sanitize_google_defaults( $google_input, $defaults['google_defaults'] );
            $output['translations']    = $this->sanitize_translations( $translations_input, $defaults['translations'], $defaults );
            $output['retention_days']  = $this->sanitize_retention_days(
                isset( $input['retention_days'] ) ? $input['retention_days'] : $defaults['retention_days'],
                $defaults['retention_days']
            );

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

            return $sanitized;
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
         * Sanitize translations array.
         *
         * @param array $translations   Raw translation input.
         * @param array $defaults       Translation defaults.
         * @param array $base_defaults  Base defaults.
         *
         * @return array
         */
        protected function sanitize_translations( array $translations, array $defaults, array $base_defaults ) {
            $sanitized = array();
            $languages = array_unique( array_merge( array_keys( $defaults ), array_keys( $translations ) ) );

            foreach ( $languages as $language ) {
                $default_translation = isset( $defaults[ $language ] ) && is_array( $defaults[ $language ] ) ? $defaults[ $language ] : array();
                $incoming            = isset( $translations[ $language ] ) && is_array( $translations[ $language ] ) ? $translations[ $language ] : array();

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
         * Render privacy policy editor.
         */
        public function render_privacy_editor() {
            $options = $this->get_settings();
            $translations = isset( $options['translations'] ) ? $options['translations'] : array();
            $english      = isset( $translations['en'] ) ? $translations['en'] : array();
            $english_text = isset( $english['privacy_policy_content'] ) ? $english['privacy_policy_content'] : '';

            echo '<h4>' . esc_html__( 'Italiano', 'fp-privacy-cookie-policy' ) . '</h4>';
            wp_editor(
                $options['privacy_policy_content'],
                'fp_privacy_policy_content',
                array(
                    'textarea_name' => self::OPTION_KEY . '[privacy_policy_content]',
                    'textarea_rows' => 10,
                )
            );

            echo '<h4>' . esc_html__( 'Inglese', 'fp-privacy-cookie-policy' ) . '</h4>';
            wp_editor(
                $english_text,
                'fp_privacy_policy_content_en',
                array(
                    'textarea_name' => self::OPTION_KEY . '[translations][en][privacy_policy_content]',
                    'textarea_rows' => 10,
                )
            );
        }

        /**
         * Render cookie policy editor.
         */
        public function render_cookie_editor() {
            $options = $this->get_settings();
            $translations = isset( $options['translations'] ) ? $options['translations'] : array();
            $english      = isset( $translations['en'] ) ? $translations['en'] : array();
            $english_text = isset( $english['cookie_policy_content'] ) ? $english['cookie_policy_content'] : '';

            echo '<h4>' . esc_html__( 'Italiano', 'fp-privacy-cookie-policy' ) . '</h4>';
            wp_editor(
                $options['cookie_policy_content'],
                'fp_cookie_policy_content',
                array(
                    'textarea_name' => self::OPTION_KEY . '[cookie_policy_content]',
                    'textarea_rows' => 10,
                )
            );

            echo '<h4>' . esc_html__( 'Inglese', 'fp-privacy-cookie-policy' ) . '</h4>';
            wp_editor(
                $english_text,
                'fp_cookie_policy_content_en',
                array(
                    'textarea_name' => self::OPTION_KEY . '[translations][en][cookie_policy_content]',
                    'textarea_rows' => 10,
                )
            );
        }

        /**
         * Render banner settings.
         */
        public function render_banner_settings() {
            $options = $this->get_settings();
            $banner  = $options['banner'];
            $translations   = isset( $options['translations'] ) ? $options['translations'] : array();
            $english_banner = isset( $translations['en']['banner'] ) ? $translations['en']['banner'] : array();
            ?>
            <fieldset class="fp-banner-settings">
                <h4><?php echo esc_html__( 'Testo banner (Italiano)', 'fp-privacy-cookie-policy' ); ?></h4>
                <p>
                    <label for="fp_banner_title"><strong><?php echo esc_html__( 'Titolo', 'fp-privacy-cookie-policy' ); ?></strong></label><br />
                    <input type="text" class="regular-text" id="fp_banner_title" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][banner_title]" value="<?php echo esc_attr( $banner['banner_title'] ); ?>" />
                </p>
                <p>
                    <label for="fp_banner_message"><strong><?php echo esc_html__( 'Messaggio', 'fp-privacy-cookie-policy' ); ?></strong></label><br />
                    <textarea class="large-text" rows="4" id="fp_banner_message" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][banner_message]"><?php echo esc_textarea( $banner['banner_message'] ); ?></textarea>
                </p>
                <div class="fp-banner-grid">
                    <p>
                        <label for="fp_accept_all_label"><?php echo esc_html__( 'Etichetta "Accetta tutti"', 'fp-privacy-cookie-policy' ); ?></label><br />
                        <input type="text" class="regular-text" id="fp_accept_all_label" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][accept_all_label]" value="<?php echo esc_attr( $banner['accept_all_label'] ); ?>" />
                    </p>
                    <p>
                        <label for="fp_reject_all_label"><?php echo esc_html__( 'Etichetta "Rifiuta"', 'fp-privacy-cookie-policy' ); ?></label><br />
                        <input type="text" class="regular-text" id="fp_reject_all_label" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][reject_all_label]" value="<?php echo esc_attr( $banner['reject_all_label'] ); ?>" />
                    </p>
                </div>
                <div class="fp-banner-grid">
                    <p>
                        <label for="fp_preferences_label"><?php echo esc_html__( 'Etichetta "Preferenze"', 'fp-privacy-cookie-policy' ); ?></label><br />
                        <input type="text" class="regular-text" id="fp_preferences_label" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][preferences_label]" value="<?php echo esc_attr( $banner['preferences_label'] ); ?>" />
                    </p>
                    <p>
                        <label for="fp_save_preferences_label"><?php echo esc_html__( 'Etichetta "Salva"', 'fp-privacy-cookie-policy' ); ?></label><br />
                        <input type="text" class="regular-text" id="fp_save_preferences_label" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][save_preferences_label]" value="<?php echo esc_attr( $banner['save_preferences_label'] ); ?>" />
                    </p>
                </div>
                <p>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][show_reject]" value="1" <?php checked( $banner['show_reject'] ); ?> />
                        <?php echo esc_html__( 'Mostra il pulsante "Rifiuta" nel banner principale', 'fp-privacy-cookie-policy' ); ?>
                    </label>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][show_preferences]" value="1" <?php checked( $banner['show_preferences'] ); ?> />
                        <?php echo esc_html__( 'Mostra il pulsante per aprire le preferenze direttamente nel banner', 'fp-privacy-cookie-policy' ); ?>
                    </label>
                </p>
            </fieldset>
            <fieldset class="fp-banner-settings">
                <h4><?php echo esc_html__( 'Testo banner (Inglese)', 'fp-privacy-cookie-policy' ); ?></h4>
                <p>
                    <label for="fp_banner_title_en"><strong><?php echo esc_html__( 'Titolo', 'fp-privacy-cookie-policy' ); ?></strong></label><br />
                    <input type="text" class="regular-text" id="fp_banner_title_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][banner][banner_title]" value="<?php echo esc_attr( isset( $english_banner['banner_title'] ) ? $english_banner['banner_title'] : '' ); ?>" />
                </p>
                <p>
                    <label for="fp_banner_message_en"><strong><?php echo esc_html__( 'Messaggio', 'fp-privacy-cookie-policy' ); ?></strong></label><br />
                    <textarea class="large-text" rows="4" id="fp_banner_message_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][banner][banner_message]"><?php echo esc_textarea( isset( $english_banner['banner_message'] ) ? $english_banner['banner_message'] : '' ); ?></textarea>
                </p>
                <div class="fp-banner-grid">
                    <p>
                        <label for="fp_accept_all_label_en"><?php echo esc_html__( 'Etichetta "Accetta tutti"', 'fp-privacy-cookie-policy' ); ?></label><br />
                        <input type="text" class="regular-text" id="fp_accept_all_label_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][banner][accept_all_label]" value="<?php echo esc_attr( isset( $english_banner['accept_all_label'] ) ? $english_banner['accept_all_label'] : '' ); ?>" />
                    </p>
                    <p>
                        <label for="fp_reject_all_label_en"><?php echo esc_html__( 'Etichetta "Rifiuta"', 'fp-privacy-cookie-policy' ); ?></label><br />
                        <input type="text" class="regular-text" id="fp_reject_all_label_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][banner][reject_all_label]" value="<?php echo esc_attr( isset( $english_banner['reject_all_label'] ) ? $english_banner['reject_all_label'] : '' ); ?>" />
                    </p>
                </div>
                <div class="fp-banner-grid">
                    <p>
                        <label for="fp_preferences_label_en"><?php echo esc_html__( 'Etichetta "Preferenze"', 'fp-privacy-cookie-policy' ); ?></label><br />
                        <input type="text" class="regular-text" id="fp_preferences_label_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][banner][preferences_label]" value="<?php echo esc_attr( isset( $english_banner['preferences_label'] ) ? $english_banner['preferences_label'] : '' ); ?>" />
                    </p>
                    <p>
                        <label for="fp_save_preferences_label_en"><?php echo esc_html__( 'Etichetta "Salva"', 'fp-privacy-cookie-policy' ); ?></label><br />
                        <input type="text" class="regular-text" id="fp_save_preferences_label_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][banner][save_preferences_label]" value="<?php echo esc_attr( isset( $english_banner['save_preferences_label'] ) ? $english_banner['save_preferences_label'] : '' ); ?>" />
                    </p>
                </div>
            </fieldset>
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
         * Render categories fields.
         */
        public function render_categories_fields() {
            $options    = $this->get_settings();
            $categories = $options['categories'];
            $translations = isset( $options['translations'] ) ? $options['translations'] : array();
            $english_categories = isset( $translations['en']['categories'] ) ? $translations['en']['categories'] : array();
            ?>
            <div class="fp-categories">
                <?php foreach ( $categories as $key => $category ) : ?>
                    <?php $english = isset( $english_categories[ $key ] ) ? $english_categories[ $key ] : array(); ?>
                    <fieldset class="fp-category" id="fp_category_<?php echo esc_attr( $key ); ?>">
                        <legend><strong><?php echo esc_html( $category['label'] ); ?></strong></legend>
                        <p>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $category['enabled'] ); ?> <?php disabled( $category['required'] ); ?> />
                                <?php echo esc_html__( 'Mostra categoria nelle preferenze', 'fp-privacy-cookie-policy' ); ?>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][required]" value="1" <?php checked( $category['required'] ); ?> <?php disabled( $category['required'] ); ?> />
                                <?php echo esc_html__( 'Necessario (non disattivabile)', 'fp-privacy-cookie-policy' ); ?>
                            </label>
                        </p>
                        <p>
                            <label for="fp_category_<?php echo esc_attr( $key ); ?>_description"><?php echo esc_html__( 'Descrizione', 'fp-privacy-cookie-policy' ); ?></label><br />
                            <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key ); ?>_description" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][description]"><?php echo esc_textarea( $category['description'] ); ?></textarea>
                        </p>
                        <p>
                            <label for="fp_category_<?php echo esc_attr( $key ); ?>_services"><?php echo esc_html__( 'Servizi e cookie inclusi', 'fp-privacy-cookie-policy' ); ?></label><br />
                            <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key ); ?>_services" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][services]"><?php echo esc_textarea( $category['services'] ); ?></textarea>
                            <span class="description"><?php echo esc_html__( 'Indica ad esempio strumenti di analytics, pixel e durata dei cookie per agevolare la documentazione.', 'fp-privacy-cookie-policy' ); ?></span>
                        </p>
                        <hr />
                        <p>
                            <label for="fp_category_<?php echo esc_attr( $key ); ?>_label_en"><?php echo esc_html__( 'Nome categoria (inglese)', 'fp-privacy-cookie-policy' ); ?></label><br />
                            <input type="text" class="regular-text" id="fp_category_<?php echo esc_attr( $key ); ?>_label_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][categories][<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( isset( $english['label'] ) ? $english['label'] : '' ); ?>" />
                        </p>
                        <p>
                            <label for="fp_category_<?php echo esc_attr( $key ); ?>_description_en"><?php echo esc_html__( 'Descrizione (inglese)', 'fp-privacy-cookie-policy' ); ?></label><br />
                            <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key ); ?>_description_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][categories][<?php echo esc_attr( $key ); ?>][description]"><?php echo esc_textarea( isset( $english['description'] ) ? $english['description'] : '' ); ?></textarea>
                        </p>
                        <p>
                            <label for="fp_category_<?php echo esc_attr( $key ); ?>_services_en"><?php echo esc_html__( 'Servizi e cookie inclusi (inglese)', 'fp-privacy-cookie-policy' ); ?></label><br />
                            <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key ); ?>_services_en" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[translations][en][categories][<?php echo esc_attr( $key ); ?>][services]"><?php echo esc_textarea( isset( $english['services'] ) ? $english['services'] : '' ); ?></textarea>
                        </p>
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
            <table class="widefat striped">
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
         * Get plugin settings.
         *
         * @return array
         */
        public function get_settings() {
            $defaults = $this->get_default_settings();
            $options  = get_option( self::OPTION_KEY, array() );

            return wp_parse_args( $options, $defaults );
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
                'banner'                 => array(
                    'banner_title'          => __( 'Rispettiamo la tua privacy', 'fp-privacy-cookie-policy' ),
                    'banner_message'        => __( 'Utilizziamo cookie tecnici e, previo consenso, cookie di profilazione e di terze parti per migliorare l\'esperienza di navigazione. Puoi gestire le tue preferenze in qualsiasi momento.', 'fp-privacy-cookie-policy' ),
                    'accept_all_label'      => __( 'Accetta tutto', 'fp-privacy-cookie-policy' ),
                    'reject_all_label'      => __( 'Rifiuta', 'fp-privacy-cookie-policy' ),
                    'preferences_label'     => __( 'Preferenze', 'fp-privacy-cookie-policy' ),
                    'save_preferences_label'=> __( 'Salva preferenze', 'fp-privacy-cookie-policy' ),
                    'show_reject'           => true,
                    'show_preferences'      => true,
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
                    ),
                ),
                'retention_days'        => 365,
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
            if ( 'toplevel_page_fp-privacy-cookie-policy' !== $hook ) {
                return;
            }

            wp_enqueue_style( 'fp-privacy-admin', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css', array(), self::VERSION );
        }

        /**
         * Enqueue frontend assets.
         */
        public function enqueue_frontend_assets() {
            $options    = $this->get_settings();
            $localized  = $this->get_localized_settings();
            $text_values = $this->get_frontend_texts( $localized['language'] );

            wp_enqueue_style( 'fp-privacy-frontend', plugin_dir_url( __FILE__ ) . 'assets/css/banner.css', array(), self::VERSION );

            wp_register_script( 'fp-privacy-frontend', plugin_dir_url( __FILE__ ) . 'assets/js/fp-consent.js', array(), self::VERSION, true );

            $localize = array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( self::NONCE_ACTION ),
                'cookieName'     => self::CONSENT_COOKIE,
                'consentId'      => $this->get_consent_id(),
                'categories'     => $this->prepare_categories_for_frontend( $localized['categories'] ),
                'banner'         => $localized['banner'],
                'googleDefaults' => $options['google_defaults'],
                'language'       => $localized['language'],
                'texts'          => array(
                    'manageConsent' => $text_values['manage_consent'],
                    'updatedAt'     => $text_values['updated_at'],
                ),
            );

            wp_localize_script( 'fp-privacy-frontend', 'fpPrivacySettings', $localize );
            wp_enqueue_script( 'fp-privacy-frontend' );
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

            if ( ! empty( $settings['translations'] ) && is_array( $settings['translations'] ) ) {
                foreach ( array_keys( $settings['translations'] ) as $language ) {
                    $code = $this->normalize_language_code( $language );
                    if ( ! in_array( $code, $languages, true ) ) {
                        $languages[] = $code;
                    }
                }
            }

            return $languages;
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
         * Frontend static texts by language.
         *
         * @param string $language Language code.
         *
         * @return array
         */
        protected function get_frontend_texts( $language ) {
            $language = $this->normalize_language_code( $language );
            $texts    = array(
                'modal_close'       => array(
                    'it' => 'Chiudi',
                    'en' => 'Close',
                ),
                'modal_title'       => array(
                    'it' => 'Gestisci le preferenze',
                    'en' => 'Manage preferences',
                ),
                'modal_intro'       => array(
                    'it' => 'Decidi quali categorie di cookie attivare. Puoi modificare la tua scelta in qualsiasi momento.',
                    'en' => 'Choose which categories of cookies to enable. You can change your preferences at any time.',
                ),
                'services_included' => array(
                    'it' => 'Servizi inclusi',
                    'en' => 'Included services',
                ),
                'always_active'     => array(
                    'it' => 'Sempre attivo',
                    'en' => 'Always active',
                ),
                'toggle_aria'       => array(
                    'it' => 'Attiva o disattiva i cookie %s',
                    'en' => 'Enable or disable %s cookies',
                ),
                'manage_consent'    => array(
                    'it' => 'Gestisci preferenze cookie',
                    'en' => 'Manage cookie preferences',
                ),
                'updated_at'        => array(
                    'it' => 'Ultimo aggiornamento',
                    'en' => 'Last updated',
                ),
            );

            $result = array();

            foreach ( $texts as $key => $values ) {
                $result[ $key ] = isset( $values[ $language ] ) ? $values[ $language ] : $values[ self::DEFAULT_LANGUAGE ];
            }

            return $result;
        }

        /**
         * Render consent banner markup.
         */
        public function render_consent_banner() {
            $localized     = $this->get_localized_settings();
            $banner        = $localized['banner'];
            $categories    = $localized['categories'];
            $texts         = $this->get_frontend_texts( $localized['language'] );
            $has_preferred = ! empty( array_filter( $categories, static function ( $category ) {
                return ! empty( $category['enabled'] ) && empty( $category['required'] );
            } ) );
            ?>
            <div class="fp-consent-banner" role="dialog" aria-live="polite" aria-modal="true" data-cookie-name="<?php echo esc_attr( self::CONSENT_COOKIE ); ?>" data-language="<?php echo esc_attr( $localized['language'] ); ?>">
                <div class="fp-consent-container">
                    <div class="fp-consent-content">
                        <h3 class="fp-consent-title"><?php echo esc_html( $banner['banner_title'] ); ?></h3>
                        <div class="fp-consent-text"><?php echo wpautop( wp_kses_post( $banner['banner_message'] ) ); ?></div>
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
            <div class="fp-consent-modal" role="dialog" aria-modal="true" aria-labelledby="fp-consent-modal-title" data-language="<?php echo esc_attr( $localized['language'] ); ?>" hidden>
                <div class="fp-consent-modal__overlay" data-consent-action="close"></div>
                <div class="fp-consent-modal__dialog" role="document">
                    <button class="fp-consent-modal__close" type="button" aria-label="<?php echo esc_attr( $texts['modal_close'] ); ?>" data-consent-action="close">&times;</button>
                    <h3 id="fp-consent-modal-title"><?php echo esc_html( $texts['modal_title'] ); ?></h3>
                    <p class="fp-consent-modal__intro"><?php echo esc_html( $texts['modal_intro'] ); ?></p>
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
            <?php
        }

        /**
         * Register shortcodes.
         */
        public function register_shortcodes() {
            add_shortcode( 'fp_privacy_policy', array( $this, 'shortcode_privacy_policy' ) );
            add_shortcode( 'fp_cookie_policy', array( $this, 'shortcode_cookie_policy' ) );
            add_shortcode( 'fp_cookie_preferences', array( $this, 'shortcode_cookie_preferences' ) );
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
         * Cookie preferences shortcode callback.
         *
         * @return string
         */
        public function shortcode_cookie_preferences() {
            $localized = $this->get_localized_settings();
            $banner     = $localized['banner'];

            return '<button class="fp-btn fp-btn-preferences" data-consent-action="open-preferences">' . esc_html( $banner['preferences_label'] ) . '</button>';
        }

        /**
         * Render settings page.
         */
        public function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
            ?>
            <div class="wrap fp-privacy-admin">
                <h1><?php esc_html_e( 'Privacy e Cookie Policy', 'fp-privacy-cookie-policy' ); ?></h1>
                <h2 class="nav-tab-wrapper">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Impostazioni', 'fp-privacy-cookie-policy' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=logs' ) ); ?>" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Registro consensi', 'fp-privacy-cookie-policy' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=fp-privacy-cookie-policy&tab=help' ) ); ?>" class="nav-tab <?php echo 'help' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Guida rapida', 'fp-privacy-cookie-policy' ); ?></a>
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
         * Render logs tab.
         */
        protected function render_logs_tab() {
            global $wpdb;

            if ( ! $this->consent_table_exists() ) {
                echo '<p>' . esc_html__( 'La tabella del registro consensi non è disponibile. Riattiva il plugin per ricrearla.', 'fp-privacy-cookie-policy' ) . '</p>';

                return;
            }

            $table_name = self::get_consent_table_name();
            $per_page   = 50;
            $paged      = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
            $offset     = ( $paged - 1 ) * $per_page;

            $logs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                )
            );

            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
            $pages = (int) ceil( $total / $per_page );
            ?>
            <div class="fp-consent-log-actions">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'fp_export_consent', 'fp_export_consent_nonce' ); ?>
                    <input type="hidden" name="action" value="fp_export_consent" />
                    <button type="submit" class="button button-secondary">
                        <?php esc_html_e( 'Esporta CSV', 'fp-privacy-cookie-policy' ); ?>
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
                                <td><?php echo esc_html( $log->event_type ); ?></td>
                                <td><code><?php echo esc_html( $log->consent_state ); ?></code></td>
                                <td><?php echo esc_html( $log->ip_address ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e( 'Nessun consenso registrato al momento.', 'fp-privacy-cookie-policy' ); ?></td>
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
                                    'base'      => add_query_arg( array( 'paged' => '%#%', 'tab' => 'logs' ) ),
                                    'format'    => '',
                                    'prev_text' => __( '&laquo;', 'fp-privacy-cookie-policy' ),
                                    'next_text' => __( '&raquo;', 'fp-privacy-cookie-policy' ),
                                    'total'     => $pages,
                                    'current'   => $paged,
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
         * Render help tab.
         */
        protected function render_help_tab() {
            $options = $this->get_settings();
            ?>
            <div class="fp-help-tab">
                <h2><?php esc_html_e( 'Checklist di conformità', 'fp-privacy-cookie-policy' ); ?></h2>
                <ol>
                    <li><?php esc_html_e( 'Aggiorna i testi di privacy e cookie policy con il supporto del tuo consulente legale.', 'fp-privacy-cookie-policy' ); ?></li>
                    <li><?php esc_html_e( 'Configura le categorie e collega i servizi realmente utilizzati sul sito.', 'fp-privacy-cookie-policy' ); ?></li>
                    <li><?php esc_html_e( 'Imposta i valori predefiniti del Google Consent Mode in base al principio di minimizzazione.', 'fp-privacy-cookie-policy' ); ?></li>
                    <li><?php esc_html_e( 'Inserisci nei template il codice di Google tag manager/gtag.js condizionato dagli eventi di consenso.', 'fp-privacy-cookie-policy' ); ?></li>
                    <li><?php esc_html_e( 'Definisci il periodo di conservazione del registro e utilizza gli strumenti di esportazione/cancellazione dati integrati in WordPress.', 'fp-privacy-cookie-policy' ); ?></li>
                </ol>
                <h3><?php esc_html_e( 'Shortcode disponibili', 'fp-privacy-cookie-policy' ); ?></h3>
                <ul>
                    <li><code>[fp_privacy_policy]</code> &mdash; <?php esc_html_e( 'Mostra il testo della privacy policy configurato.', 'fp-privacy-cookie-policy' ); ?></li>
                    <li><code>[fp_cookie_policy]</code> &mdash; <?php esc_html_e( 'Mostra il testo della cookie policy.', 'fp-privacy-cookie-policy' ); ?></li>
                    <li><code>[fp_cookie_preferences]</code> &mdash; <?php esc_html_e( 'Inserisce un pulsante per riaprire le preferenze di consenso.', 'fp-privacy-cookie-policy' ); ?></li>
                </ul>
                <h3><?php esc_html_e( 'Come collegare Google Consent Mode v2', 'fp-privacy-cookie-policy' ); ?></h3>
                <p><?php esc_html_e( 'Questo plugin aggiorna automaticamente i segnali del Consent Mode (analytics_storage, ad_storage, ad_personalization, ad_user_data, functionality_storage, security_storage). Assicurati che il tag gtag o Google Tag Manager sia caricato dopo il banner e che ascolti l\'evento fp_consent_update se utilizzi configurazioni personalizzate.', 'fp-privacy-cookie-policy' ); ?></p>
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
                <p><?php esc_html_e( 'Ricorda di richiedere nuovamente il consenso ogni 12 mesi o quando cambiano le finalità del trattamento.', 'fp-privacy-cookie-policy' ); ?></p>
                <p>
                    <?php
                    printf(
                        /* translators: %s is the shortcode */
                        esc_html__( 'Ultimo aggiornamento contenuti: %s', 'fp-privacy-cookie-policy' ),
                        '<strong>' . esc_html( date_i18n( get_option( 'date_format' ) ) ) . '</strong>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }

        /**
         * Handle consent saving via AJAX.
         */
        public function ajax_save_consent() {
            check_ajax_referer( self::NONCE_ACTION, 'nonce' );

            $consent_id = isset( $_POST['consentId'] ) ? sanitize_text_field( wp_unslash( $_POST['consentId'] ) ) : '';
            $event      = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : 'save_preferences';
            $consent    = isset( $_POST['consent'] ) ? wp_unslash( $_POST['consent'] ) : array();

            $consent_id = substr( $consent_id, 0, 64 );

            if ( empty( $consent_id ) || ! is_array( $consent ) ) {
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

            wp_send_json_success( array( 'message' => __( 'Consenso aggiornato.', 'fp-privacy-cookie-policy' ) ) );
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

            /**
             * Filter the normalized consent state before it is persisted.
             *
             * @param array $normalized Normalized consent data.
             * @param array $categories Plugin categories configuration.
             */
            return apply_filters( 'fp_privacy_normalized_consent_state', $normalized, $categories );
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

            $table_name = self::get_consent_table_name();

            $logs = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created_at DESC" );

            nocache_headers();
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=fp-consent-logs-' . gmdate( 'Ymd-His' ) . '.csv' );

            $output = fopen( 'php://output', 'w' );

            fputcsv( $output, array( 'created_at', 'consent_id', 'user_id', 'event_type', 'consent_state', 'ip_address', 'user_agent' ) );

            foreach ( $logs as $log ) {
                fputcsv( $output, array( $log->created_at, $log->consent_id, $log->user_id, $log->event_type, $log->consent_state, $log->ip_address, $log->user_agent ) );
            }

            fclose( $output );

            exit;
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
         * Generate or retrieve consent ID.
         *
         * @return string
         */
        protected function get_consent_id() {
            if ( isset( $_COOKIE[ self::CONSENT_COOKIE . '_id' ] ) ) {
                return sanitize_text_field( wp_unslash( $_COOKIE[ self::CONSENT_COOKIE . '_id' ] ) );
            }

            $consent_id = wp_generate_uuid4();

            $path   = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
            $domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

            setcookie( self::CONSENT_COOKIE . '_id', $consent_id, time() + YEAR_IN_SECONDS, $path, $domain, is_ssl(), true );

            $_COOKIE[ self::CONSENT_COOKIE . '_id' ] = $consent_id;

            return $consent_id;
        }
    }

    register_activation_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'uninstall' ) );

    FP_Privacy_Cookie_Policy::instance();
}
