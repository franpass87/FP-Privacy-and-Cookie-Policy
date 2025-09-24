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

        /**
         * Singleton instance.
         *
         * @var FP_Privacy_Cookie_Policy|null
         */
        protected static $instance = null;

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
         * Sanitize settings.
         *
         * @param array $input Input values.
         *
         * @return array
         */
        public function sanitize_settings( $input ) {
            $defaults = $this->get_default_settings();
            $output   = wp_parse_args( $input, $defaults );

            $output['privacy_policy_content'] = isset( $input['privacy_policy_content'] ) ? wp_kses_post( $input['privacy_policy_content'] ) : $defaults['privacy_policy_content'];
            $output['cookie_policy_content']  = isset( $input['cookie_policy_content'] ) ? wp_kses_post( $input['cookie_policy_content'] ) : $defaults['cookie_policy_content'];

            $banner_fields = array(
                'banner_title'          => 'sanitize_text_field',
                'accept_all_label'      => 'sanitize_text_field',
                'reject_all_label'      => 'sanitize_text_field',
                'preferences_label'     => 'sanitize_text_field',
                'save_preferences_label'=> 'sanitize_text_field',
            );

            foreach ( $banner_fields as $field => $callback ) {
                $output['banner'][ $field ] = isset( $input['banner'][ $field ] ) ? call_user_func( $callback, $input['banner'][ $field ] ) : $defaults['banner'][ $field ];
            }

            $output['banner']['banner_message'] = isset( $input['banner']['banner_message'] ) ? wp_kses_post( $input['banner']['banner_message'] ) : $defaults['banner']['banner_message'];

            if ( isset( $input['banner']['show_reject'] ) ) {
                $output['banner']['show_reject'] = (bool) $input['banner']['show_reject'];
            } else {
                $output['banner']['show_reject'] = false;
            }

            if ( isset( $input['banner']['show_preferences'] ) ) {
                $output['banner']['show_preferences'] = (bool) $input['banner']['show_preferences'];
            } else {
                $output['banner']['show_preferences'] = false;
            }

            $categories = $defaults['categories'];

            if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
                foreach ( $categories as $key => $category ) {
                    if ( isset( $input['categories'][ $key ]['enabled'] ) ) {
                        $categories[ $key ]['enabled'] = (bool) $input['categories'][ $key ]['enabled'];
                    } else {
                        $categories[ $key ]['enabled'] = false;
                    }

                    if ( isset( $input['categories'][ $key ]['required'] ) ) {
                        $categories[ $key ]['required'] = (bool) $input['categories'][ $key ]['required'];
                    }

                    if ( isset( $input['categories'][ $key ]['description'] ) ) {
                        $categories[ $key ]['description'] = wp_kses_post( $input['categories'][ $key ]['description'] );
                    }

                    if ( isset( $input['categories'][ $key ]['services'] ) ) {
                        $categories[ $key ]['services'] = sanitize_textarea_field( $input['categories'][ $key ]['services'] );
                    }
                }
            }

            $output['categories'] = $categories;

            $google_defaults = $defaults['google_defaults'];

            if ( isset( $input['google_defaults'] ) && is_array( $input['google_defaults'] ) ) {
                foreach ( $google_defaults as $key => $value ) {
                    $google_defaults[ $key ] = isset( $input['google_defaults'][ $key ] ) ? sanitize_text_field( $input['google_defaults'][ $key ] ) : $value;
                }
            }

            $output['google_defaults'] = $google_defaults;

            return $output;
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
            $options = $this->get_settings();
            $banner  = $options['banner'];
            ?>
            <fieldset class="fp-banner-settings">
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
            <?php
        }

        /**
         * Render categories fields.
         */
        public function render_categories_fields() {
            $options    = $this->get_settings();
            $categories = $options['categories'];
            ?>
            <div class="fp-categories">
                <?php foreach ( $categories as $key => $category ) : ?>
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
            $options = $this->get_settings();

            wp_enqueue_style( 'fp-privacy-frontend', plugin_dir_url( __FILE__ ) . 'assets/css/banner.css', array(), self::VERSION );

            wp_register_script( 'fp-privacy-frontend', plugin_dir_url( __FILE__ ) . 'assets/js/fp-consent.js', array(), self::VERSION, true );

            $localize = array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( self::NONCE_ACTION ),
                'cookieName'     => self::CONSENT_COOKIE,
                'consentId'      => $this->get_consent_id(),
                'categories'     => $this->prepare_categories_for_frontend( $options['categories'] ),
                'banner'         => $options['banner'],
                'googleDefaults' => $options['google_defaults'],
                'texts'          => array(
                    'manageConsent' => __( 'Gestisci preferenze cookie', 'fp-privacy-cookie-policy' ),
                    'updatedAt'     => __( 'Ultimo aggiornamento', 'fp-privacy-cookie-policy' ),
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
         * Render consent banner markup.
         */
        public function render_consent_banner() {
            $options = $this->get_settings();

            $banner        = $options['banner'];
            $categories    = $options['categories'];
            $has_preferred = ! empty( array_filter( $categories, static function ( $category ) {
                return ! empty( $category['enabled'] ) && empty( $category['required'] );
            } ) );
            ?>
            <div class="fp-consent-banner" role="dialog" aria-live="polite" aria-modal="true" data-cookie-name="<?php echo esc_attr( self::CONSENT_COOKIE ); ?>">
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
            <div class="fp-consent-modal" role="dialog" aria-modal="true" aria-labelledby="fp-consent-modal-title" hidden>
                <div class="fp-consent-modal__overlay" data-consent-action="close"></div>
                <div class="fp-consent-modal__dialog" role="document">
                    <button class="fp-consent-modal__close" type="button" aria-label="<?php echo esc_attr__( 'Chiudi', 'fp-privacy-cookie-policy' ); ?>" data-consent-action="close">&times;</button>
                    <h3 id="fp-consent-modal-title"><?php echo esc_html__( 'Gestisci le preferenze', 'fp-privacy-cookie-policy' ); ?></h3>
                    <p class="fp-consent-modal__intro"><?php esc_html_e( 'Decidi quali categorie di cookie attivare. Puoi modificare la tua scelta in qualsiasi momento.', 'fp-privacy-cookie-policy' ); ?></p>
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
                                                <summary><?php esc_html_e( 'Servizi inclusi', 'fp-privacy-cookie-policy' ); ?></summary>
                                                <p><?php echo esc_html( $category['services'] ); ?></p>
                                            </details>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fp-consent-toggle">
                                        <?php if ( ! empty( $category['required'] ) ) : ?>
                                            <span class="fp-consent-required"><?php esc_html_e( 'Sempre attivo', 'fp-privacy-cookie-policy' ); ?></span>
                                        <?php else : ?>
                                            <label class="fp-switch">
                                                <input type="checkbox" value="1" data-category-toggle="<?php echo esc_attr( $key ); ?>" />
                                                <span class="fp-slider" aria-hidden="true"></span>
                                                <span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Attiva o disattiva i cookie %s', 'fp-privacy-cookie-policy' ), $category['label'] ) ); ?></span>
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
            $options = $this->get_settings();
            $banner  = $options['banner'];

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
