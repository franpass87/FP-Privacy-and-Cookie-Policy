<?php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Plugin URI:  https://example.com/
 * Description: Gestisci privacy policy, cookie policy e consenso informato in modo conforme al GDPR e al Google Consent Mode v2.
 * Version:     1.0.0
 * Author:      FP Digital Assistant
 * Author URI:  https://example.com/
 * License:     GPL2
 * Text Domain: fp-privacy-cookie-policy
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'FP_Privacy_Cookie_Policy' ) ) {

    class FP_Privacy_Cookie_Policy {

        const OPTION_KEY        = 'fp_privacy_cookie_settings';
        const VERSION           = '1.0.0';
        const CONSENT_COOKIE    = 'fp_consent_state';
        const CONSENT_TABLE     = 'fp_consent_logs';
        const NONCE_ACTION      = 'fp_privacy_nonce';
        const DEFAULT_LANGUAGE  = 'it';

        /**
         * Singleton instance.
         *
         * @var FP_Privacy_Cookie_Policy|null
         */
        protected static $instance = null;

        /**
         * Cached fallback translations for the text domain.
         *
         * @var array|null
         */
        protected $fallback_translations = null;

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
            add_filter( 'gettext_fp-privacy-cookie-policy', array( $this, 'provide_textdomain_fallback' ), 10, 3 );
        }

        /**
         * Load textdomain.
         */
        public function load_textdomain() {
            load_plugin_textdomain( 'fp-privacy-cookie-policy', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }

        /**
         * Register activation hook.
         */
        public static function activate() {
            self::create_consent_table();
        }

        /**
         * Create consent logs table.
         */
        public static function create_consent_table() {
            global $wpdb;

            $table_name      = $wpdb->prefix . self::CONSENT_TABLE;
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
            // Nothing to do for now.
        }

        /**
         * Register uninstall hook.
         */
        public static function uninstall() {
            delete_option( self::OPTION_KEY );

            global $wpdb;

            $table_name = $wpdb->prefix . self::CONSENT_TABLE;
            $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
         * Supported language codes.
         *
         * @return array
         */
        protected function get_supported_languages() {
            return array( 'it', 'en' );
        }

        /**
         * Language labels for admin UI.
         *
         * @return array
         */
        protected function get_language_labels() {
            return array(
                'it' => __( 'Italiano', 'fp-privacy-cookie-policy' ),
                'en' => __( 'Inglese', 'fp-privacy-cookie-policy' ),
            );
        }

        /**
         * Retrieve fallback translations for the plugin text domain.
         *
         * @return array
         */
        protected function get_textdomain_fallback_strings() {
            if ( null !== $this->fallback_translations ) {
                return $this->fallback_translations;
            }

            $file          = plugin_dir_path( __FILE__ ) . 'languages/fp-privacy-cookie-policy-en_US.php';
            $translations  = array();
            $loaded_values = array();

            if ( file_exists( $file ) ) {
                $loaded_values = include $file;
            }

            if ( is_array( $loaded_values ) ) {
                foreach ( $loaded_values as $original => $translated ) {
                    $translations[ (string) $original ] = (string) $translated;
                }
            }

            $this->fallback_translations = $translations;

            return $this->fallback_translations;
        }

        /**
         * Provide English translations when binary language files are unavailable.
         *
         * @param string $translation Existing translation.
         * @param string $text        Original text.
         * @param string $domain      Text domain.
         *
         * @return string
         */
        public function provide_textdomain_fallback( $translation, $text, $domain ) {
            if ( 'fp-privacy-cookie-policy' !== $domain ) {
                return $translation;
            }

            if ( '' !== $translation && $translation !== $text ) {
                return $translation;
            }

            $language = $this->get_language_from_locale( $this->get_current_locale() );

            if ( 'en' !== $language ) {
                return $translation;
            }

            $map = $this->get_textdomain_fallback_strings();

            if ( isset( $map[ $text ] ) ) {
                return $map[ $text ];
            }

            return $translation;
        }

        /**
         * Retrieve current locale.
         *
         * @return string
         */
        protected function get_current_locale() {
            if ( function_exists( 'determine_locale' ) ) {
                return determine_locale();
            }

            return get_locale();
        }

        /**
         * Map locale to supported language.
         *
         * @param string $locale Locale code.
         *
         * @return string
         */
        protected function get_language_from_locale( $locale ) {
            $locale = strtolower( (string) $locale );

            if ( strlen( $locale ) > 2 ) {
                $locale = substr( $locale, 0, 2 );
            }

            if ( in_array( $locale, $this->get_supported_languages(), true ) ) {
                return $locale;
            }

            return self::DEFAULT_LANGUAGE;
        }

        /**
         * Ensure a value is represented as translation array.
         *
         * @param mixed $value     Value to normalize.
         * @param array $languages Supported languages.
         * @param mixed $defaults  Default values.
         *
         * @return array
         */
        protected function ensure_translation_array( $value, $languages = null, $defaults = array() ) {
            if ( null === $languages ) {
                $languages = $this->get_supported_languages();
            }

            if ( ! is_array( $defaults ) ) {
                $defaults = array();
            }

            $output = array();

            foreach ( $languages as $language ) {
                if ( is_array( $value ) && array_key_exists( $language, $value ) ) {
                    $output[ $language ] = (string) $value[ $language ];
                    continue;
                }

                if ( self::DEFAULT_LANGUAGE === $language && null !== $value && ! is_array( $value ) ) {
                    $output[ $language ] = (string) $value;
                    continue;
                }

                if ( isset( $defaults[ $language ] ) ) {
                    $output[ $language ] = (string) $defaults[ $language ];
                    continue;
                }

                $output[ $language ] = '';
            }

            return $output;
        }

        /**
         * Retrieve localized value.
         *
         * @param mixed       $value    Value or translations.
         * @param string|null $language Preferred language.
         *
         * @return string
         */
        protected function get_localized_value( $value, $language = null ) {
            if ( null === $language ) {
                $language = $this->get_language_from_locale( $this->get_current_locale() );
            }

            if ( is_array( $value ) ) {
                if ( isset( $value[ $language ] ) && '' !== $value[ $language ] ) {
                    return $value[ $language ];
                }

                if ( isset( $value[ self::DEFAULT_LANGUAGE ] ) && '' !== $value[ self::DEFAULT_LANGUAGE ] ) {
                    return $value[ self::DEFAULT_LANGUAGE ];
                }

                foreach ( $value as $translation ) {
                    if ( '' !== $translation ) {
                        return $translation;
                    }
                }

                return '';
            }

            return (string) $value;
        }

        /**
         * Sanitize translated field values.
         *
         * @param mixed  $value    Submitted value.
         * @param array  $defaults Default translations.
         * @param string $callback Sanitization callback.
         *
         * @return array
         */
        protected function sanitize_translated_field( $value, $defaults, $callback ) {
            $languages = $this->get_supported_languages();
            $sanitized = array();

            foreach ( $languages as $language ) {
                if ( is_array( $value ) && array_key_exists( $language, $value ) ) {
                    $raw = $value[ $language ];
                } elseif ( ! is_array( $value ) && null !== $value && self::DEFAULT_LANGUAGE === $language ) {
                    $raw = $value;
                } elseif ( isset( $defaults[ $language ] ) ) {
                    $raw = $defaults[ $language ];
                } else {
                    $raw = '';
                }

                if ( 'sanitize_text_field' === $callback ) {
                    $sanitized[ $language ] = sanitize_text_field( $raw );
                } elseif ( 'sanitize_textarea_field' === $callback ) {
                    $sanitized[ $language ] = sanitize_textarea_field( $raw );
                } elseif ( 'wp_kses_post' === $callback ) {
                    $sanitized[ $language ] = wp_kses_post( $raw );
                } elseif ( is_callable( $callback ) ) {
                    $sanitized[ $language ] = call_user_func( $callback, $raw );
                } else {
                    $sanitized[ $language ] = sanitize_text_field( $raw );
                }
            }

            return $sanitized;
        }

        /**
         * Normalize plugin settings.
         *
         * @param array      $settings Settings to normalize.
         * @param array|null $defaults Default settings.
         *
         * @return array
         */
        protected function normalize_settings( $settings, $defaults = null ) {
            if ( null === $defaults ) {
                $defaults = $this->get_default_settings();
            }

            if ( isset( $defaults['banner'] ) ) {
                $settings['banner'] = $this->normalize_banner_settings(
                    isset( $settings['banner'] ) ? $settings['banner'] : array(),
                    $defaults['banner']
                );
            }

            if ( isset( $defaults['categories'] ) ) {
                $settings['categories'] = $this->normalize_categories_settings(
                    isset( $settings['categories'] ) ? $settings['categories'] : array(),
                    $defaults['categories']
                );
            }

            return $settings;
        }

        /**
         * Normalize banner settings.
         *
         * @param mixed $banner   Banner settings.
         * @param array $defaults Default banner values.
         *
         * @return array
         */
        protected function normalize_banner_settings( $banner, $defaults ) {
            $languages = $this->get_supported_languages();

            if ( ! is_array( $banner ) ) {
                $banner = array();
            }

            $text_fields = array(
                'banner_title',
                'banner_message',
                'accept_all_label',
                'reject_all_label',
                'preferences_label',
                'save_preferences_label',
            );

            foreach ( $text_fields as $field ) {
                $default_value    = isset( $defaults[ $field ] ) ? $defaults[ $field ] : array();
                $banner[ $field ] = $this->ensure_translation_array(
                    isset( $banner[ $field ] ) ? $banner[ $field ] : $default_value,
                    $languages,
                    $default_value
                );
            }

            $banner['show_reject']      = ! empty( $banner['show_reject'] );
            $banner['show_preferences'] = ! empty( $banner['show_preferences'] );

            return $banner;
        }

        /**
         * Normalize categories settings.
         *
         * @param mixed $categories Categories settings.
         * @param array $defaults   Default categories.
         *
         * @return array
         */
        protected function normalize_categories_settings( $categories, $defaults ) {
            $languages  = $this->get_supported_languages();
            $normalized = array();

            foreach ( $defaults as $key => $default_category ) {
                $current = ( isset( $categories[ $key ] ) && is_array( $categories[ $key ] ) ) ? $categories[ $key ] : array();

                $normalized[ $key ] = array(
                    'label'       => $this->ensure_translation_array(
                        isset( $current['label'] ) ? $current['label'] : $default_category['label'],
                        $languages,
                        $default_category['label']
                    ),
                    'description' => $this->ensure_translation_array(
                        isset( $current['description'] ) ? $current['description'] : $default_category['description'],
                        $languages,
                        $default_category['description']
                    ),
                    'services'    => $this->ensure_translation_array(
                        isset( $current['services'] ) ? $current['services'] : $default_category['services'],
                        $languages,
                        $default_category['services']
                    ),
                    'required'    => ! empty( $default_category['required'] ),
                    'enabled'     => ! empty( $default_category['enabled'] ),
                );

                if ( array_key_exists( 'required', $current ) && empty( $default_category['required'] ) ) {
                    $normalized[ $key ]['required'] = (bool) $current['required'];
                }

                if ( array_key_exists( 'enabled', $current ) ) {
                    $normalized[ $key ]['enabled'] = (bool) $current['enabled'];
                }
            }

            return $normalized;
        }

        /**
         * Prepare banner strings for a language.
         *
         * @param array  $banner   Banner settings.
         * @param string $language Language code.
         *
         * @return array
         */
        protected function prepare_banner_for_language( $banner, $language ) {
            return array(
                'banner_title'           => $this->get_localized_value( isset( $banner['banner_title'] ) ? $banner['banner_title'] : '', $language ),
                'banner_message'         => $this->get_localized_value( isset( $banner['banner_message'] ) ? $banner['banner_message'] : '', $language ),
                'accept_all_label'       => $this->get_localized_value( isset( $banner['accept_all_label'] ) ? $banner['accept_all_label'] : '', $language ),
                'reject_all_label'       => $this->get_localized_value( isset( $banner['reject_all_label'] ) ? $banner['reject_all_label'] : '', $language ),
                'preferences_label'      => $this->get_localized_value( isset( $banner['preferences_label'] ) ? $banner['preferences_label'] : '', $language ),
                'save_preferences_label' => $this->get_localized_value( isset( $banner['save_preferences_label'] ) ? $banner['save_preferences_label'] : '', $language ),
            );
        }

        /**
         * Determine fallback language.
         *
         * @return string
         */
        protected function get_fallback_language() {
            return $this->get_language_from_locale( $this->get_current_locale() );
        }

        /**
         * Static translation map for the frontend.
         *
         * @return array
         */
        protected function get_static_translation_map() {
            return array(
                'modal_title'      => array(
                    'it' => 'Gestisci le preferenze',
                    'en' => 'Manage preferences',
                ),
                'modal_intro'      => array(
                    'it' => 'Decidi quali categorie di cookie attivare. Puoi modificare la tua scelta in qualsiasi momento.',
                    'en' => 'Decide which cookie categories to activate. You can update your choice at any time.',
                ),
                'close'            => array(
                    'it' => 'Chiudi',
                    'en' => 'Close',
                ),
                'services_included' => array(
                    'it' => 'Servizi inclusi',
                    'en' => 'Included services',
                ),
                'always_active'    => array(
                    'it' => 'Sempre attivo',
                    'en' => 'Always active',
                ),
                'toggle_label'     => array(
                    'it' => 'Attiva o disattiva i cookie %s',
                    'en' => 'Enable or disable %s cookies',
                ),
                'manage_consent'   => array(
                    'it' => 'Gestisci preferenze cookie',
                    'en' => 'Manage cookie preferences',
                ),
                'updated_at'       => array(
                    'it' => 'Ultimo aggiornamento',
                    'en' => 'Last update',
                ),
            );
        }

        /**
         * Retrieve a static translation.
         *
         * @param string      $key      Translation key.
         * @param string|null $language Language code.
         *
         * @return string
         */
        protected function get_static_translation( $key, $language = null ) {
            $map = $this->get_static_translation_map();

            if ( null === $language ) {
                $language = $this->get_fallback_language();
            }

            if ( isset( $map[ $key ][ $language ] ) ) {
                return $map[ $key ][ $language ];
            }

            if ( isset( $map[ $key ][ self::DEFAULT_LANGUAGE ] ) ) {
                return $map[ $key ][ self::DEFAULT_LANGUAGE ];
            }

            return isset( $map[ $key ] ) ? reset( $map[ $key ] ) : '';
        }

        /**
         * Build translation payload for the frontend.
         *
         * @param array $options Plugin options.
         *
         * @return array
         */
        protected function get_frontend_translations( $options ) {
            $languages    = $this->get_supported_languages();
            $translations = array();

            foreach ( $languages as $language ) {
                $banner_labels = $this->prepare_banner_for_language( $options['banner'], $language );

                $translations[ $language ] = array(
                    'banner'     => array(
                        'title'       => $banner_labels['banner_title'],
                        'message'     => wpautop( wp_kses_post( $banner_labels['banner_message'] ) ),
                        'acceptAll'   => $banner_labels['accept_all_label'],
                        'rejectAll'   => $banner_labels['reject_all_label'],
                        'preferences' => $banner_labels['preferences_label'],
                        'save'        => $banner_labels['save_preferences_label'],
                    ),
                    'modal'      => array(
                        'title'        => $this->get_static_translation( 'modal_title', $language ),
                        'intro'        => $this->get_static_translation( 'modal_intro', $language ),
                        'close'        => $this->get_static_translation( 'close', $language ),
                        'services'     => $this->get_static_translation( 'services_included', $language ),
                        'alwaysActive' => $this->get_static_translation( 'always_active', $language ),
                        'toggle'       => $this->get_static_translation( 'toggle_label', $language ),
                    ),
                    'texts'      => array(
                        'manageConsent' => $this->get_static_translation( 'manage_consent', $language ),
                        'updatedAt'     => $this->get_static_translation( 'updated_at', $language ),
                    ),
                    'categories' => array(),
                );

                foreach ( $options['categories'] as $key => $category ) {
                    $translations[ $language ]['categories'][ $key ] = array(
                        'label'       => $this->get_localized_value( $category['label'], $language ),
                        'description' => $this->get_localized_value( $category['description'], $language ),
                        'services'    => $this->get_localized_value( $category['services'], $language ),
                    );
                }
            }

            return array(
                'available' => $languages,
                'fallback'  => $this->get_fallback_language(),
                'strings'   => $translations,
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

            $banner_defaults  = $defaults['banner'];
            $submitted_banner = ( isset( $input['banner'] ) && is_array( $input['banner'] ) ) ? $input['banner'] : array();

            $output['banner']['banner_title']           = $this->sanitize_translated_field( isset( $submitted_banner['banner_title'] ) ? $submitted_banner['banner_title'] : null, $banner_defaults['banner_title'], 'sanitize_text_field' );
            $output['banner']['banner_message']         = $this->sanitize_translated_field( isset( $submitted_banner['banner_message'] ) ? $submitted_banner['banner_message'] : null, $banner_defaults['banner_message'], 'wp_kses_post' );
            $output['banner']['accept_all_label']       = $this->sanitize_translated_field( isset( $submitted_banner['accept_all_label'] ) ? $submitted_banner['accept_all_label'] : null, $banner_defaults['accept_all_label'], 'sanitize_text_field' );
            $output['banner']['reject_all_label']       = $this->sanitize_translated_field( isset( $submitted_banner['reject_all_label'] ) ? $submitted_banner['reject_all_label'] : null, $banner_defaults['reject_all_label'], 'sanitize_text_field' );
            $output['banner']['preferences_label']      = $this->sanitize_translated_field( isset( $submitted_banner['preferences_label'] ) ? $submitted_banner['preferences_label'] : null, $banner_defaults['preferences_label'], 'sanitize_text_field' );
            $output['banner']['save_preferences_label'] = $this->sanitize_translated_field( isset( $submitted_banner['save_preferences_label'] ) ? $submitted_banner['save_preferences_label'] : null, $banner_defaults['save_preferences_label'], 'sanitize_text_field' );
            $output['banner']['show_reject']            = ! empty( $submitted_banner['show_reject'] );
            $output['banner']['show_preferences']       = ! empty( $submitted_banner['show_preferences'] );

            $category_defaults = $defaults['categories'];
            $sanitized_categories = array();

            foreach ( $category_defaults as $key => $category_default ) {
                $submitted_category = ( isset( $input['categories'][ $key ] ) && is_array( $input['categories'][ $key ] ) ) ? $input['categories'][ $key ] : array();

                $sanitized_categories[ $key ] = array(
                    'label'       => $this->sanitize_translated_field( isset( $submitted_category['label'] ) ? $submitted_category['label'] : null, $category_default['label'], 'sanitize_text_field' ),
                    'description' => $this->sanitize_translated_field( isset( $submitted_category['description'] ) ? $submitted_category['description'] : null, $category_default['description'], 'wp_kses_post' ),
                    'services'    => $this->sanitize_translated_field( isset( $submitted_category['services'] ) ? $submitted_category['services'] : null, $category_default['services'], 'sanitize_textarea_field' ),
                    'required'    => ! empty( $category_default['required'] ),
                    'enabled'     => ! empty( $category_default['enabled'] ),
                );

                if ( empty( $category_default['required'] ) ) {
                    $sanitized_categories[ $key ]['required'] = ! empty( $submitted_category['required'] );
                }

                $sanitized_categories[ $key ]['enabled'] = ! empty( $submitted_category['enabled'] );
            }

            $output['categories'] = $sanitized_categories;

            $google_defaults = $defaults['google_defaults'];

            if ( isset( $input['google_defaults'] ) && is_array( $input['google_defaults'] ) ) {
                foreach ( $google_defaults as $key => $value ) {
                    $google_defaults[ $key ] = isset( $input['google_defaults'][ $key ] ) ? sanitize_text_field( $input['google_defaults'][ $key ] ) : $value;
                }
            }

            $output['google_defaults'] = $google_defaults;

            return $this->normalize_settings( $output, $defaults );
        }

        /**
         * Render privacy policy editor.
         */
        public function render_privacy_editor() {
            $options = $this->get_settings();

            wp_editor(
                $options['privacy_policy_content'],
                'fp_privacy_policy_content',
                array(
                    'textarea_name' => self::OPTION_KEY . '[privacy_policy_content]',
                    'textarea_rows' => 10,
                )
            );
        }

        /**
         * Render cookie policy editor.
         */
        public function render_cookie_editor() {
            $options = $this->get_settings();

            wp_editor(
                $options['cookie_policy_content'],
                'fp_cookie_policy_content',
                array(
                    'textarea_name' => self::OPTION_KEY . '[cookie_policy_content]',
                    'textarea_rows' => 10,
                )
            );
        }

        /**
         * Render banner settings.
         */
        public function render_banner_settings() {
            $options   = $this->get_settings();
            $banner    = $options['banner'];
            $languages = $this->get_language_labels();
            ?>
            <fieldset class="fp-banner-settings">
                <?php foreach ( $languages as $code => $language_label ) : ?>
                    <p>
                        <label for="fp_banner_title_<?php echo esc_attr( $code ); ?>"><strong><?php printf( esc_html__( 'Titolo (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></strong></label><br />
                        <input type="text" class="regular-text" id="fp_banner_title_<?php echo esc_attr( $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][banner_title][<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( $banner['banner_title'][ $code ] ); ?>" />
                    </p>
                <?php endforeach; ?>
                <?php foreach ( $languages as $code => $language_label ) : ?>
                    <p>
                        <label for="fp_banner_message_<?php echo esc_attr( $code ); ?>"><strong><?php printf( esc_html__( 'Messaggio (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></strong></label><br />
                        <textarea class="large-text" rows="4" id="fp_banner_message_<?php echo esc_attr( $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][banner_message][<?php echo esc_attr( $code ); ?>]"><?php echo esc_textarea( $banner['banner_message'][ $code ] ); ?></textarea>
                    </p>
                <?php endforeach; ?>
                <?php foreach ( $languages as $code => $language_label ) : ?>
                    <div class="fp-banner-grid">
                        <p>
                            <label for="fp_accept_all_label_<?php echo esc_attr( $code ); ?>"><?php printf( esc_html__( 'Etichetta "Accetta tutto" (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></label><br />
                            <input type="text" class="regular-text" id="fp_accept_all_label_<?php echo esc_attr( $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][accept_all_label][<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( $banner['accept_all_label'][ $code ] ); ?>" />
                        </p>
                        <p>
                            <label for="fp_reject_all_label_<?php echo esc_attr( $code ); ?>"><?php printf( esc_html__( 'Etichetta "Rifiuta" (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></label><br />
                            <input type="text" class="regular-text" id="fp_reject_all_label_<?php echo esc_attr( $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][reject_all_label][<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( $banner['reject_all_label'][ $code ] ); ?>" />
                        </p>
                    </div>
                    <div class="fp-banner-grid">
                        <p>
                            <label for="fp_preferences_label_<?php echo esc_attr( $code ); ?>"><?php printf( esc_html__( 'Etichetta "Preferenze" (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></label><br />
                            <input type="text" class="regular-text" id="fp_preferences_label_<?php echo esc_attr( $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][preferences_label][<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( $banner['preferences_label'][ $code ] ); ?>" />
                        </p>
                        <p>
                            <label for="fp_save_preferences_label_<?php echo esc_attr( $code ); ?>"><?php printf( esc_html__( 'Etichetta "Salva" (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></label><br />
                            <input type="text" class="regular-text" id="fp_save_preferences_label_<?php echo esc_attr( $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner][save_preferences_label][<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( $banner['save_preferences_label'][ $code ] ); ?>" />
                        </p>
                    </div>
                <?php endforeach; ?>
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
            <?php
        }


        /**
         * Render categories fields.
         */
        public function render_categories_fields() {
            $options    = $this->get_settings();
            $categories = $options['categories'];
            $languages  = $this->get_language_labels();
            ?>
            <div class="fp-categories">
                <?php foreach ( $categories as $key => $category ) : ?>
                    <?php $category_label = $this->get_localized_value( $category['label'] ); ?>
                    <fieldset class="fp-category" id="fp_category_<?php echo esc_attr( $key ); ?>">
                        <legend><strong><?php echo esc_html( $category_label ); ?></strong></legend>
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
                        <?php foreach ( $languages as $code => $language_label ) : ?>
                            <p>
                                <label for="fp_category_<?php echo esc_attr( $key . '_label_' . $code ); ?>"><?php printf( esc_html__( 'Nome categoria (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></label><br />
                                <input type="text" class="regular-text" id="fp_category_<?php echo esc_attr( $key . '_label_' . $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][label][<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( $category['label'][ $code ] ); ?>" />
                            </p>
                            <p>
                                <label for="fp_category_<?php echo esc_attr( $key . '_description_' . $code ); ?>"><?php printf( esc_html__( 'Descrizione (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></label><br />
                                <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key . '_description_' . $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][description][<?php echo esc_attr( $code ); ?>]"><?php echo esc_textarea( $category['description'][ $code ] ); ?></textarea>
                            </p>
                            <p>
                                <label for="fp_category_<?php echo esc_attr( $key . '_services_' . $code ); ?>"><?php printf( esc_html__( 'Servizi e cookie inclusi (%s)', 'fp-privacy-cookie-policy' ), esc_html( $language_label ) ); ?></label><br />
                                <textarea class="large-text" rows="3" id="fp_category_<?php echo esc_attr( $key . '_services_' . $code ); ?>" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[categories][<?php echo esc_attr( $key ); ?>][services][<?php echo esc_attr( $code ); ?>]"><?php echo esc_textarea( $category['services'][ $code ] ); ?></textarea>
                                <?php if ( self::DEFAULT_LANGUAGE === $code ) : ?>
                                    <span class="description"><?php echo esc_html__( 'Indica ad esempio strumenti di analytics, pixel e durata dei cookie per agevolare la documentazione.', 'fp-privacy-cookie-policy' ); ?></span>
                                <?php endif; ?>
                            </p>
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

            $settings = wp_parse_args( $options, $defaults );

            return $this->normalize_settings( $settings, $defaults );
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
                    'banner_title'           => array(
                        'it' => 'Rispettiamo la tua privacy',
                        'en' => 'We respect your privacy',
                    ),
                    'banner_message'         => array(
                        'it' => 'Utilizziamo cookie tecnici e, previo consenso, cookie di profilazione e di terze parti per migliorare l\'esperienza di navigazione. Puoi gestire le tue preferenze in qualsiasi momento.',
                        'en' => 'We use technical cookies and, with your consent, profiling and third-party cookies to improve your browsing experience. You can manage your preferences at any time.',
                    ),
                    'accept_all_label'       => array(
                        'it' => 'Accetta tutto',
                        'en' => 'Accept all',
                    ),
                    'reject_all_label'       => array(
                        'it' => 'Rifiuta',
                        'en' => 'Reject',
                    ),
                    'preferences_label'      => array(
                        'it' => 'Preferenze',
                        'en' => 'Preferences',
                    ),
                    'save_preferences_label' => array(
                        'it' => 'Salva preferenze',
                        'en' => 'Save preferences',
                    ),
                    'show_reject'           => true,
                    'show_preferences'      => true,
                ),
                'categories'             => array(
                    'necessary'     => array(
                        'label'       => array(
                            'it' => 'Necessari',
                            'en' => 'Necessary',
                        ),
                        'description' => array(
                            'it' => 'Cookie indispensabili per il funzionamento del sito e la fornitura del servizio.',
                            'en' => 'Cookies essential for the site to function and to deliver the requested service.',
                        ),
                        'services'    => array(
                            'it' => 'WordPress (sessione), cookie di autenticazione, salvataggio preferenze.',
                            'en' => 'WordPress (session), authentication cookies, preference storage.',
                        ),
                        'required'    => true,
                        'enabled'     => true,
                    ),
                    'preferences'   => array(
                        'label'       => array(
                            'it' => 'Preferenze',
                            'en' => 'Preferences',
                        ),
                        'description' => array(
                            'it' => 'Consentono al sito di ricordare le scelte effettuate dall\'utente, come la lingua o la regione.',
                            'en' => 'Allow the site to remember user choices such as language or region.',
                        ),
                        'services'    => array(
                            'it' => '',
                            'en' => '',
                        ),
                        'required'    => false,
                        'enabled'     => true,
                    ),
                    'statistics'    => array(
                        'label'       => array(
                            'it' => 'Statistiche',
                            'en' => 'Statistics',
                        ),
                        'description' => array(
                            'it' => 'Aiutano a capire come i visitatori interagiscono con il sito raccogliendo e trasmettendo informazioni in forma anonima.',
                            'en' => 'Help understand how visitors interact with the site by collecting and transmitting information anonymously.',
                        ),
                        'services'    => array(
                            'it' => 'Google Analytics 4 (2 anni), Matomo (13 mesi).',
                            'en' => 'Google Analytics 4 (2 years), Matomo (13 months).',
                        ),
                        'required'    => false,
                        'enabled'     => true,
                    ),
                    'marketing'     => array(
                        'label'       => array(
                            'it' => 'Marketing',
                            'en' => 'Marketing',
                        ),
                        'description' => array(
                            'it' => 'Vengono utilizzati per tracciare i visitatori e proporre annunci personalizzati.',
                            'en' => 'Used to track visitors and deliver personalised advertising.',
                        ),
                        'services'    => array(
                            'it' => 'Google Ads, Meta Pixel, TikTok Pixel.',
                            'en' => 'Google Ads, Meta Pixel, TikTok Pixel.',
                        ),
                        'required'    => false,
                        'enabled'     => true,
                    ),
                ),
                'google_defaults'        => array(
                    'analytics_storage'     => 'denied',
                    'ad_storage'            => 'denied',
                    'ad_user_data'          => 'denied',
                    'ad_personalization'    => 'denied',
                    'functionality_storage' => 'granted',
                    'security_storage'      => 'granted',
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
            $options  = $this->get_settings();
            $language = $this->get_fallback_language();
            $banner   = $this->prepare_banner_for_language( $options['banner'], $language );

            wp_enqueue_style( 'fp-privacy-frontend', plugin_dir_url( __FILE__ ) . 'assets/css/banner.css', array(), self::VERSION );

            wp_register_script( 'fp-privacy-frontend', plugin_dir_url( __FILE__ ) . 'assets/js/fp-consent.js', array(), self::VERSION, true );

            $localize = array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( self::NONCE_ACTION ),
                'cookieName'     => self::CONSENT_COOKIE,
                'consentId'      => $this->get_consent_id(),
                'categories'     => $this->prepare_categories_for_frontend( $options['categories'], $language ),
                'banner'         => array_merge(
                    $banner,
                    array(
                        'show_reject'      => ! empty( $options['banner']['show_reject'] ),
                        'show_preferences' => ! empty( $options['banner']['show_preferences'] ),
                    )
                ),
                'googleDefaults' => $options['google_defaults'],
                'translations'   => $this->get_frontend_translations( $options ),
                'language'       => array(
                    'fallback'  => $language,
                    'available' => $this->get_supported_languages(),
                ),
                'texts'          => array(
                    'manageConsent' => $this->get_static_translation( 'manage_consent', $language ),
                    'updatedAt'     => $this->get_static_translation( 'updated_at', $language ),
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
        protected function prepare_categories_for_frontend( $categories, $language ) {
            $prepared = array();

            foreach ( $categories as $key => $category ) {
                $prepared[ $key ] = array(
                    'label'       => $this->get_localized_value( $category['label'], $language ),
                    'description' => wp_kses_post( $this->get_localized_value( $category['description'], $language ) ),
                    'services'    => $this->get_localized_value( $category['services'], $language ),
                    'required'    => (bool) $category['required'],
                    'enabled'     => (bool) $category['enabled'],
                );
            }

            return $prepared;
        }

        /**
         * Render consent banner markup.
         */
        public function render_consent_banner() {
            $options = $this->get_settings();

            $language       = $this->get_fallback_language();
            $banner         = $this->prepare_banner_for_language( $options['banner'], $language );
            $categories     = $this->prepare_categories_for_frontend( $options['categories'], $language );
            $has_preferred  = ! empty( array_filter( $categories, static function ( $category ) {
                return ! empty( $category['enabled'] ) && empty( $category['required'] );
            } ) );
            $close_label    = $this->get_static_translation( 'close', $language );
            $modal_title    = $this->get_static_translation( 'modal_title', $language );
            $modal_intro    = $this->get_static_translation( 'modal_intro', $language );
            $services_label = $this->get_static_translation( 'services_included', $language );
            $always_active  = $this->get_static_translation( 'always_active', $language );
            $toggle_label   = $this->get_static_translation( 'toggle_label', $language );
            ?>
            <div class="fp-consent-banner" role="dialog" aria-live="polite" aria-modal="true" data-cookie-name="<?php echo esc_attr( self::CONSENT_COOKIE ); ?>">
                <div class="fp-consent-container">
                    <div class="fp-consent-content">
                        <h3 class="fp-consent-title"><?php echo esc_html( $banner['banner_title'] ); ?></h3>
                        <div class="fp-consent-text"><?php echo wpautop( wp_kses_post( $banner['banner_message'] ) ); ?></div>
                    </div>
                    <div class="fp-consent-actions">
                        <button class="fp-btn fp-btn-primary" data-consent-action="accept-all"><?php echo esc_html( $banner['accept_all_label'] ); ?></button>
                        <?php if ( ! empty( $banner['show_reject'] ) ) : ?>
                            <button class="fp-btn fp-btn-secondary" data-consent-action="reject-all"><?php echo esc_html( $banner['reject_all_label'] ); ?></button>
                        <?php endif; ?>
                        <?php if ( ! empty( $banner['show_preferences'] ) && $has_preferred ) : ?>
                            <button class="fp-btn fp-btn-tertiary" data-consent-action="open-preferences"><?php echo esc_html( $banner['preferences_label'] ); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="fp-consent-modal" role="dialog" aria-modal="true" aria-labelledby="fp-consent-modal-title" hidden>
                <div class="fp-consent-modal__overlay" data-consent-action="close"></div>
                <div class="fp-consent-modal__dialog" role="document">
                    <button class="fp-consent-modal__close" type="button" aria-label="<?php echo esc_attr( $close_label ); ?>" data-consent-action="close">&times;</button>
                    <h3 id="fp-consent-modal-title"><?php echo esc_html( $modal_title ); ?></h3>
                    <p class="fp-consent-modal__intro"><?php echo esc_html( $modal_intro ); ?></p>
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
                                                <summary><?php echo esc_html( $services_label ); ?></summary>
                                                <p><?php echo esc_html( $category['services'] ); ?></p>
                                            </details>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fp-consent-toggle">
                                        <?php if ( ! empty( $category['required'] ) ) : ?>
                                            <span class="fp-consent-required"><?php echo esc_html( $always_active ); ?></span>
                                        <?php else : ?>
                                            <label class="fp-switch">
                                                <input type="checkbox" value="1" data-category-toggle="<?php echo esc_attr( $key ); ?>" />
                                                <span class="fp-slider" aria-hidden="true"></span>
                                                <span class="screen-reader-text"><?php echo esc_html( sprintf( $toggle_label, $category['label'] ) ); ?></span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="fp-consent-modal__actions">
                        <button class="fp-btn fp-btn-primary" data-consent-action="save-preferences"><?php echo esc_html( $banner['save_preferences_label'] ); ?></button>
                        <?php if ( ! empty( $banner['show_reject'] ) ) : ?>
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
            $options = $this->get_settings();

            return '<div class="fp-privacy-policy">' . wp_kses_post( $options['privacy_policy_content'] ) . '</div>';
        }

        /**
         * Cookie policy shortcode callback.
         *
         * @return string
         */
        public function shortcode_cookie_policy() {
            $options = $this->get_settings();

            return '<div class="fp-cookie-policy">' . wp_kses_post( $options['cookie_policy_content'] ) . '</div>';
        }

        /**
         * Cookie preferences shortcode callback.
         *
         * @return string
         */
        public function shortcode_cookie_preferences() {
            $options  = $this->get_settings();
            $language = $this->get_fallback_language();
            $banner   = $this->prepare_banner_for_language( $options['banner'], $language );

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

            $table_name = $wpdb->prefix . self::CONSENT_TABLE;
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
                    <li><?php esc_html_e( 'Conserva il registro dei consensi per almeno 12 mesi o secondo le policy del cliente.', 'fp-privacy-cookie-policy' ); ?></li>
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
            $event      = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : 'save';
            $consent    = isset( $_POST['consent'] ) ? wp_unslash( $_POST['consent'] ) : array();

            if ( empty( $consent_id ) || empty( $consent ) || ! is_array( $consent ) ) {
                wp_send_json_error( array( 'message' => __( 'Dati non validi.', 'fp-privacy-cookie-policy' ) ), 400 );
            }

            $sanitized = array();

            foreach ( $consent as $key => $value ) {
                $sanitized[ sanitize_key( $key ) ] = rest_sanitize_boolean( $value );
            }

            $this->log_consent( $consent_id, $event, $sanitized );

            wp_send_json_success( array( 'message' => __( 'Consenso aggiornato.', 'fp-privacy-cookie-policy' ) ) );
        }

        /**
         * Log consent event.
         *
         * @param string $consent_id Consent ID.
         * @param string $event      Event type.
         * @param array  $consent    Consent data.
         */
        protected function log_consent( $consent_id, $event, $consent ) {
            global $wpdb;

            $table_name = $wpdb->prefix . self::CONSENT_TABLE;

            $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
            $ip_address = wp_privacy_anonymize_ip( $ip_address );
            $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
            $user_id    = get_current_user_id();

            $wpdb->insert(
                $table_name,
                array(
                    'consent_id'    => $consent_id,
                    'user_id'       => $user_id ? $user_id : null,
                    'event_type'    => $event,
                    'consent_state' => wp_json_encode( $consent ),
                    'ip_address'    => $ip_address,
                    'user_agent'    => $user_agent,
                    'created_at'    => current_time( 'mysql' ),
                ),
                array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
            );
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

            $table_name = $wpdb->prefix . self::CONSENT_TABLE;

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

            return $consent_id;
        }
    }

    register_activation_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'FP_Privacy_Cookie_Policy', 'uninstall' ) );

    FP_Privacy_Cookie_Policy::instance();
}
