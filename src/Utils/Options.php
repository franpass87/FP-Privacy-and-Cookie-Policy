<?php
/**
 * Options handler.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Domain\ValueObjects\BannerLayout;
use FP\Privacy\Domain\ValueObjects\ColorPalette;
use FP\Privacy\Domain\ValueObjects\ConsentModeDefaults;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Services\Validation\OptionsValidator;
use FP\Privacy\Shared\Constants;
use FP\Privacy\Utils\DetectorAlertManager;
use FP\Privacy\Utils\Logger;
use WP_Post;
use function __;
use function did_action;
use function function_exists;

/**
 * Options utility class.
 */
class Options {
	const OPTION_KEY = Constants::OPTION_KEY;
	const PAGE_MANAGED_META_KEY = Constants::PAGE_MANAGED_META_KEY;

	/**
	 * Cached options.
	 *
	 * @var array<string, mixed>
	 */
	private $options = array();

	/**
	 * Blog identifier the options were loaded for.
	 *
	 * @var int
	 */
	private $blog_id = 0;

	/**
	 * Automatic translator utility.
	 *
	 * @var AutoTranslator
	 */
	private $auto_translator;

	/**
	 * Language normalizer.
	 *
	 * @var LanguageNormalizer
	 */
	private $language_normalizer;

	/**
	 * Script rules manager.
	 *
	 * @var ScriptRulesManager
	 */
	private $script_rules_manager;

	/**
	 * Page manager.
	 *
	 * @var PageManager
	 */
	private $page_manager;

	/**
	 * Banner texts manager.
	 *
	 * @var BannerTextsManager
	 */
	private $banner_texts_manager;

	/**
	 * Detector alert manager.
	 *
	 * @var DetectorAlertManager|null
	 */
	private $detector_alert_manager;

	/**
	 * Categories manager.
	 *
	 * @var CategoriesManager
	 */
	private $categories_manager;

	/**
	 * Options validator.
	 *
	 * @var OptionsValidator|null
	 */
	private $options_validator;

	/**
	 * Instance.
	 *
	 * @var Options
	 */
	private static $instance;

	/**
	 * Translate a string only once translations are available.
	 *
	 * @param string $text
	 * @return string
	 */
	public static function maybe_translate( string $text ): string {
		if ( function_exists( '__' ) ) {
			return __( $text, 'fp-privacy' );
		}

		return $text;
	}

	/**
	 * Get singleton instance.
	 *
	 * @deprecated Use dependency injection via service container instead.
	 *             This method is kept for backward compatibility only.
	 *             New code should request Options via constructor injection.
	 *             The container automatically handles multisite blog_id switching.
	 *
	 * @return Options
	 */
	public static function instance() {
		// Try to get from container first if available.
		if ( class_exists( '\\FP\\Privacy\\Core\\Kernel' ) ) {
			try {
				$kernel = \FP\Privacy\Core\Kernel::make();
				$container = $kernel->getContainer();
				if ( $container->has( self::class ) ) {
					// Container handles multisite automatically via Options constructor.
					return $container->get( self::class );
				}
			} catch ( \Exception $e ) {
				// Fall through to singleton pattern.
			}
		}

		// Fallback to singleton pattern for backward compatibility.
		// Note: This maintains multisite blog_id switching behavior.
		$current_blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;

		if ( ! self::$instance || self::$instance->blog_id !== $current_blog_id ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->blog_id                = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;
		$this->script_rules_manager   = new ScriptRulesManager();
		$this->options                = $this->load();
		$this->language_normalizer    = new LanguageNormalizer( $this->get_languages() );
		$this->auto_translator        = new AutoTranslator(
			isset( $this->options['auto_translations'] ) ? $this->options['auto_translations'] : array()
		);
		$this->page_manager           = new PageManager( $this->language_normalizer );
		
		// Initialize managers
		$this->banner_texts_manager   = new BannerTextsManager( $this, $this->language_normalizer );
		$this->detector_alert_manager = new DetectorAlertManager( $this );
		$this->categories_manager     = new CategoriesManager( $this, $this->auto_translator, $this->language_normalizer );
		
		// Initialize options validator with dependencies.
		$this->options_validator = new OptionsValidator(
			$this->script_rules_manager,
			$this->language_normalizer,
			$this->get_default_detector_notifications()
		);
	}

	/**
	 * Get raw options.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		return $this->options;
	}

	/**
	 * Get a specific option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		if ( isset( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		}

		return $default;
	}

	/**
	 * Verifica se il logging debug è abilitato.
	 *
	 * @return bool
	 */
	public function is_debug_enabled(): bool {
		return ! empty( $this->options['debug_logging'] );
	}

	/**
	 * Set options.
	 *
	 * @param array<string, mixed> $new_options New options.
	 *
	 * @return void
	 */
	public function set( array $new_options ): void {
		$defaults  = $this->get_default_options();
		$merged    = \wp_parse_args( $new_options, $this->options );
		$sanitized = $this->sanitize( $merged, $defaults );
		$this->options = $sanitized;

		// Update language normalizer with new languages
		$this->language_normalizer->set_languages( $this->get_languages() );

		// Update auto translator cache
		if ( isset( $sanitized['auto_translations'] ) ) {
			$this->auto_translator = new AutoTranslator( $sanitized['auto_translations'] );
		}

		\update_option( self::OPTION_KEY, $sanitized, false );

		$this->ensure_pages_exist();
	}

	/**
	 * Load options from the database.
	 *
	 * @return array<string, mixed>
	 */
	private function load() {
		$stored   = \get_option( self::OPTION_KEY );
		$defaults = $this->get_default_options();

		if ( ! is_array( $stored ) ) {
			return $defaults;
		}

		return $this->sanitize( \wp_parse_args( $stored, $defaults ), $defaults );
	}

	/**
	 * Get defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_options(): array {
		// IMPORTANTE: Italiano come lingua principale di default
		// WordPress può essere in qualsiasi lingua, ma per questo plugin
		// l'italiano deve essere la lingua primaria
		$default_locale = Constants::DEFAULT_LOCALE;
		
		// Use ColorPalette value object for default palette.
		$default_palette_vo = new ColorPalette();
		$default_palette = $default_palette_vo->to_array();

		// Default italiani hardcoded (lingua principale del plugin)
		$banner_default = array(
			'title'          => 'Rispettiamo la tua privacy',
			'message'        => 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
			'btn_accept'     => 'Accetta tutti',
			'btn_reject'     => 'Rifiuta tutti',
			'btn_prefs'      => 'Gestisci preferenze',
			'modal_title'    => 'Preferenze cookie',
			'modal_close'    => 'Chiudi preferenze',
			'modal_save'          => 'Salva preferenze',
			'revision_notice'     => 'Abbiamo aggiornato la nostra policy. Rivedi le tue preferenze.',
			'toggle_locked'       => 'Obbligatorio',
			'toggle_enabled'      => 'Abilitato',
			'debug_label'         => 'Debug cookie:',
			'link_policy'         => '',
			'link_privacy_policy' => 'Informativa sulla Privacy',
			'link_cookie_policy'  => 'Cookie Policy',
		);

		$category_defaults = array(
			'necessary'   => array(
				'label'       => array( 
					'default' => self::maybe_translate('Strictly necessary'),
					Constants::DEFAULT_LOCALE => self::maybe_translate('Strictly necessary'),
				),
				'description' => array( 
					'default' => self::maybe_translate('Essential cookies required for the website to function and cannot be disabled.'),
					Constants::DEFAULT_LOCALE => self::maybe_translate('Essential cookies required for the website to function and cannot be disabled.'),
				),
				'locked'      => true,
				'services'    => array(),
			),
			'preferences' => array(
				'label'       => array( 
					'default' => self::maybe_translate('Preferences'),
					Constants::DEFAULT_LOCALE => self::maybe_translate('Preferences'),
				),
				'description' => array( 
					'default' => self::maybe_translate('Store user preferences such as language or location.'),
					Constants::DEFAULT_LOCALE => self::maybe_translate('Store user preferences such as language or location.'),
				),
				'locked'      => false,
				'services'    => array(),
			),
			'statistics'  => array(
				'label'       => array( 
					'default' => self::maybe_translate('Statistics'),
					Constants::DEFAULT_LOCALE => self::maybe_translate('Statistics'),
				),
				'description' => array( 
					'default' => self::maybe_translate('Collect anonymous statistics to improve our services.'),
					Constants::DEFAULT_LOCALE => self::maybe_translate('Collect anonymous statistics to improve our services.'),
				),
				'locked'      => false,
				'services'    => array(),
			),
			'marketing'   => array(
				'label'       => array( 
					'default' => self::maybe_translate('Marketing'),
					Constants::DEFAULT_LOCALE => self::maybe_translate('Marketing'),
				),
				'description' => array( 
					'default' => self::maybe_translate('Enable personalized advertising and tracking.'),
					Constants::DEFAULT_LOCALE => self::maybe_translate('Enable personalized advertising and tracking.'),
				),
				'locked'      => false,
				'services'    => array(),
			),
		);

		$script_defaults = array(
			$default_locale => $this->build_script_language_defaults( $category_defaults ),
		);

		// Use BannerLayout value object for default layout.
		$default_banner_layout = new BannerLayout(
			'floating',
			'bottom',
			$default_palette_vo,
			true,
			false
		);
		
		// Use ConsentModeDefaults value object for default consent mode.
		$default_consent_mode = new ConsentModeDefaults();
		
		return array(
			'languages_active'      => array( $default_locale ),
			'banner_texts'          => array(
				$default_locale => $banner_default,
			),
			'banner_layout'         => $default_banner_layout->to_array(),
			'categories'            => $category_defaults,
			'consent_mode_defaults' => $default_consent_mode->to_array(),
			'retention_days'        => Constants::RETENTION_DAYS_DEFAULT,
			'consent_revision'      => Constants::CONSENT_REVISION_INITIAL,
			'gpc_enabled'           => false,
			'preview_mode'          => false,
			'debug_logging'         => false,
			'pages'                 => array(
				'privacy_policy_page_id' => array( $default_locale => 0 ),
				'cookie_policy_page_id'  => array( $default_locale => 0 ),
			),
			'org_name'              => '',
			'vat'                   => '',
			'address'               => '',
			'dpo_name'              => '',
			'dpo_email'             => '',
			'privacy_email'         => '',
			'snapshots'             => array(
				'services' => array(
					'detected'     => array(),
					'generated_at' => 0,
				),
				'policies' => array(
					'privacy' => array(),
					'cookie'  => array(),
				),
			),
			'scripts'               => $script_defaults,
			'detector_alert'        => $this->get_default_detector_alert(),
			'detector_notifications' => $this->get_default_detector_notifications(),
			'auto_update_services'  => false,
			'auto_update_policies'  => false,
			'auto_translations'     => array(),
			'ai_disclosure'              => $this->get_default_ai_disclosure(),
			'algorithmic_transparency'   => $this->get_default_algorithmic_transparency(),
			'enable_sub_categories'     => false, // EDPB 2025: Enable individual service toggles.
		);
	}

	/**
	 * Sanitize options array.
	 * Delegates to OptionsValidator for better separation of concerns.
	 *
	 * @param array<string, mixed> $value    Value to sanitize.
	 * @param array<string, mixed> $defaults Defaults.
	 *
	 * @return array<string, mixed>
	 */
	private function sanitize( array $value, array $defaults ): array {
		// Use OptionsValidator if available, otherwise fallback to inline logic.
		if ( $this->options_validator ) {
			return $this->options_validator->sanitize( $value, $defaults, $this->options );
		}
		
		// Fallback to original implementation for backward compatibility.
		return $this->sanitize_legacy( $value, $defaults );
	}

	/**
	 * Legacy sanitize implementation (kept for backward compatibility).
	 *
	 * @param array<string, mixed> $value    Value to sanitize.
	 * @param array<string, mixed> $defaults Defaults.
	 *
	 * @return array<string, mixed>
	 */
	private function sanitize_legacy( array $value, array $defaults ): array {
		$default_locale = ! empty( $defaults['languages_active'] ) ? $defaults['languages_active'][0] : 'en_US';
		$languages      = Validator::locale_list( $value['languages_active'] ?? $defaults['languages_active'], $default_locale );

		$banner_defaults_raw = isset( $defaults['banner_texts'][ $default_locale ] ) 
			? $defaults['banner_texts'][ $default_locale ] 
			: ( ! empty( $defaults['banner_texts'] ) ? reset( $defaults['banner_texts'] ) : array() );
		$banner_defaults = is_array( $banner_defaults_raw ) ? $banner_defaults_raw : array();
		$layout_raw      = isset( $value['banner_layout'] ) && \is_array( $value['banner_layout'] ) ? $value['banner_layout'] : array();
		$categories_raw  = isset( $value['categories'] ) && \is_array( $value['categories'] ) ? $value['categories'] : $defaults['categories'];
		$pages_raw       = isset( $value['pages'] ) && \is_array( $value['pages'] ) ? $value['pages'] : array();
		$scripts_raw     = isset( $value['scripts'] ) && \is_array( $value['scripts'] ) ? $value['scripts'] : array();
		$alert_raw       = isset( $value['detector_alert'] ) && \is_array( $value['detector_alert'] ) ? $value['detector_alert'] : array();
		$notifications_raw = isset( $value['detector_notifications'] ) && \is_array( $value['detector_notifications'] ) ? $value['detector_notifications'] : array();
		$existing_scripts  = isset( $this->options['scripts'] ) && \is_array( $this->options['scripts'] ) ? $this->options['scripts'] : array();

		$owner_fields = Validator::sanitize_owner_fields(
			array(
				'org_name'      => $value['org_name'] ?? $defaults['org_name'],
				'vat'           => $value['vat'] ?? $defaults['vat'],
				'address'       => $value['address'] ?? $defaults['address'],
				'dpo_name'      => $value['dpo_name'] ?? $defaults['dpo_name'],
				'dpo_email'     => $value['dpo_email'] ?? $defaults['dpo_email'],
				'privacy_email' => $value['privacy_email'] ?? $defaults['privacy_email'],
			)
		);

	// Use BannerLayout value object for validation and sanitization.
	$layout_data = array_merge(
		$defaults['banner_layout'],
		array(
			'type'                  => $layout_raw['type'] ?? $defaults['banner_layout']['type'],
			'position'              => $layout_raw['position'] ?? $defaults['banner_layout']['position'],
			'palette'               => isset( $layout_raw['palette'] ) && \is_array( $layout_raw['palette'] ) ? $layout_raw['palette'] : $defaults['banner_layout']['palette'],
			'sync_modal_and_button' => $layout_raw['sync_modal_and_button'] ?? $defaults['banner_layout']['sync_modal_and_button'],
			'enable_dark_mode'      => $layout_raw['enable_dark_mode'] ?? ( $defaults['banner_layout']['enable_dark_mode'] ?? false ),
		)
	);
	
	// Create BannerLayout value object which validates and sanitizes automatically.
	$banner_layout = BannerLayout::from_array( $layout_data );
	$layout = $banner_layout->to_array();

		$categories = $this->normalize_categories( $categories_raw, $defaults['categories'], $languages );

		// Create temporary normalizer for sanitization
		$temp_normalizer = new LanguageNormalizer( $languages );

		return array(
			'languages_active'      => $languages,
			'banner_texts'          => Validator::sanitize_banner_texts( isset( $value['banner_texts'] ) && \is_array( $value['banner_texts'] ) ? $value['banner_texts'] : array(), $languages, $banner_defaults ),
			'banner_layout'         => $layout,
			'categories'            => $categories,
			// Use ConsentModeDefaults value object for validation and sanitization.
			'consent_mode_defaults' => $this->sanitize_consent_mode_defaults( $value['consent_mode_defaults'] ?? array(), $defaults['consent_mode_defaults'] ),
			'retention_days'        => Validator::int( $value['retention_days'] ?? $defaults['retention_days'], $defaults['retention_days'], Constants::RETENTION_DAYS_MINIMUM ),
			'consent_revision'      => Validator::int( $value['consent_revision'] ?? $defaults['consent_revision'], $defaults['consent_revision'], Constants::CONSENT_REVISION_MINIMUM ),
			'gpc_enabled'           => Validator::bool( $value['gpc_enabled'] ?? $defaults['gpc_enabled'] ),
			'preview_mode'          => Validator::bool( $value['preview_mode'] ?? $defaults['preview_mode'] ),
			'debug_logging'         => Validator::bool( $value['debug_logging'] ?? $defaults['debug_logging'] ),
			'pages'                 => Validator::sanitize_pages( $pages_raw, $languages ),
			'org_name'              => $owner_fields['org_name'],
			'vat'                   => $owner_fields['vat'],
			'address'               => $owner_fields['address'],
			'dpo_name'              => $owner_fields['dpo_name'],
			'dpo_email'             => $owner_fields['dpo_email'],
			'privacy_email'         => $owner_fields['privacy_email'],
			'snapshots'             => OptionsSanitizer::sanitize_snapshots( isset( $value['snapshots'] ) && \is_array( $value['snapshots'] ) ? $value['snapshots'] : array(), $languages ),
			'scripts'               => $this->script_rules_manager->sanitize_rules( $scripts_raw, $languages, $categories, $existing_scripts, $temp_normalizer ),
			'detector_alert'        => OptionsSanitizer::sanitize_detector_alert( $alert_raw ),
			'detector_notifications' => OptionsSanitizer::sanitize_detector_notifications( $notifications_raw, $this->get_detector_notifications() ),
			'auto_update_services'  => Validator::bool( $value['auto_update_services'] ?? $defaults['auto_update_services'] ),
			'auto_update_policies'  => Validator::bool( $value['auto_update_policies'] ?? $defaults['auto_update_policies'] ),
			'auto_translations'     => Validator::sanitize_auto_translations( isset( $value['auto_translations'] ) && \is_array( $value['auto_translations'] ) ? $value['auto_translations'] : array(), $banner_defaults ),
			'ai_disclosure'         => $this->sanitize_ai_disclosure( isset( $value['ai_disclosure'] ) && \is_array( $value['ai_disclosure'] ) ? $value['ai_disclosure'] : array(), $defaults['ai_disclosure'] ),
			'algorithmic_transparency'   => $this->sanitize_algorithmic_transparency( isset( $value['algorithmic_transparency'] ) && \is_array( $value['algorithmic_transparency'] ) ? $value['algorithmic_transparency'] : array(), $defaults['algorithmic_transparency'] ),
			'enable_sub_categories'      => Validator::bool( $value['enable_sub_categories'] ?? $defaults['enable_sub_categories'] ),
		);
	}


	/**
	 * Get default detector notification settings.
	 * Delegates to DetectorAlertManager.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_detector_notifications(): array {
		return $this->get_detector_alert_manager()->get_default_detector_notifications();
	}

	/**
	 * Retrieve detector notification settings.
	 * Delegates to DetectorAlertManager.
	 *
	 * @return array<string, mixed>
	 */
	public function get_detector_notifications(): array {
		return $this->get_detector_alert_manager()->get_detector_notifications();
	}

	/**
	 * Persist detector notification settings.
	 * Delegates to DetectorAlertManager.
	 *
	 * @param array<string, mixed> $settings Settings to merge.
	 *
	 * @return void
	 */
	public function update_detector_notifications( array $settings ): void {
		$this->get_detector_alert_manager()->update_detector_notifications( $settings );
	}

	/**
	 * Persist preset script rules when new services are detected.
	 *
	 * @param array<int, array<string, mixed>> $services Services detected.
	 *
	 * @return void
	 */
	public function prime_script_rules_from_services( array $services ): void {
		$languages = $this->get_languages();

		if ( empty( $languages ) ) {
			return;
		}

		$scripts = isset( $this->options['scripts'] ) && \is_array( $this->options['scripts'] ) ? $this->options['scripts'] : array();

		$updated_scripts = $this->script_rules_manager->prime_from_services(
			$services,
			$languages,
			$scripts,
			array( $this, 'get_categories_for_language' ),
			$this->language_normalizer
		);

		if ( $updated_scripts ) {
			$this->set(
				array(
					'scripts' => $updated_scripts,
				)
			);
		}
	}

	/**
	 * Retrieve detected services from stored snapshots.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_snapshot_services() {
		if ( isset( $this->options['snapshots']['services']['detected'] ) && \is_array( $this->options['snapshots']['services']['detected'] ) ) {
			return $this->options['snapshots']['services']['detected'];
		}

		return array();
	}

	/**
	 * Get active languages.
	 *
	 * @return array<int, string>
	 */
	public function get_languages(): array {
		$configured = array();

		if ( isset( $this->options['languages_active'] ) ) {
			$configured = is_array( $this->options['languages_active'] )
				? $this->options['languages_active']
				: array( $this->options['languages_active'] );
		}

		$fallback = $configured[0] ?? ( function_exists( '\\get_locale' ) ? (string) \get_locale() : 'en_US' );

		return Validator::locale_list( $configured, $fallback );
	}

	/**
	 * Retrieve script rules for a given language.
	 *
	 * @param string $lang Language code.
	 *
	 * @return array<string, array<string, array<int, string>>>
	 */
	public function get_script_rules_for_language( string $lang ): array {
		$categories = $this->get_categories_for_language( $lang );
		$defaults   = $this->build_script_language_defaults( $categories );
		$stored     = isset( $this->options['scripts'] ) && \is_array( $this->options['scripts'] ) ? $this->options['scripts'] : array();

		return $this->script_rules_manager->get_rules_for_language(
			$lang,
			$categories,
			$stored,
			$defaults,
			$this->language_normalizer
		);
	}

	/**
	 * Get the detector alert payload.
	 * Delegates to DetectorAlertManager.
	 *
	 * @return array<string, mixed>
	 */
	public function get_detector_alert(): array {
		return $this->get_detector_alert_manager()->get_detector_alert();
	}

	/**
	 * Persist detector alert payload.
	 * Delegates to DetectorAlertManager.
	 *
	 * @param array<string, mixed> $payload Alert payload.
	 *
	 * @return void
	 */
	public function set_detector_alert( array $payload ): void {
		$this->get_detector_alert_manager()->set_detector_alert( $payload );
	}

	/**
	 * Reset detector alert to defaults.
	 * Delegates to DetectorAlertManager.
	 *
	 * @return void
	 */
	public function clear_detector_alert(): void {
		$this->get_detector_alert_manager()->clear_detector_alert();
	}

	/**
	 * Get detector alert manager instance.
	 * Lazy initialization to avoid circular dependencies.
	 *
	 * @return DetectorAlertManager
	 */
	private function get_detector_alert_manager() {
		if ( null === $this->detector_alert_manager ) {
			$this->detector_alert_manager = new DetectorAlertManager( $this );
		}
		return $this->detector_alert_manager;
	}

	/**
	 * Get default detector alert payload.
	 * Delegates to DetectorAlertManager.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_detector_alert(): array {
		return $this->get_detector_alert_manager()->get_default_detector_alert();
	}

	/**
	 * Get AI disclosure configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function get_ai_disclosure(): array {
		$defaults = $this->get_default_ai_disclosure();
		$stored = isset( $this->options['ai_disclosure'] ) && is_array( $this->options['ai_disclosure'] )
			? $this->options['ai_disclosure']
			: array();

		return wp_parse_args( $stored, $defaults );
	}

	/**
	 * Get default AI disclosure configuration.
	 *
	 * @return array<string, mixed>
	 */
	private function get_default_ai_disclosure(): array {
		return array(
			'enabled'            => false,
			'systems'            => array(),
			'automated_decisions' => false,
			'profiling'          => false,
			'texts'              => array(),
		);
	}

	/**
	 * Sanitize AI disclosure configuration.
	 *
	 * @param array<string, mixed> $value Value to sanitize.
	 * @param array<string, mixed> $defaults Defaults.
	 *
	 * @return array<string, mixed>
	 */
	private function sanitize_ai_disclosure( array $value, array $defaults ): array {
		$sanitized = array(
			'enabled'            => Validator::bool( $value['enabled'] ?? $defaults['enabled'] ),
			'systems'            => array(),
			'automated_decisions' => Validator::bool( $value['automated_decisions'] ?? $defaults['automated_decisions'] ),
			'profiling'          => Validator::bool( $value['profiling'] ?? $defaults['profiling'] ),
			'texts'              => array(),
		);

		// Sanitize AI systems.
		if ( isset( $value['systems'] ) && is_array( $value['systems'] ) ) {
			foreach ( $value['systems'] as $system ) {
				if ( ! is_array( $system ) || empty( $system['name'] ) ) {
					continue;
				}

				$sanitized['systems'][] = array(
					'name'      => sanitize_text_field( $system['name'] ),
					'purpose'   => isset( $system['purpose'] ) ? wp_kses_post( $system['purpose'] ) : '',
					'risk_level' => isset( $system['risk_level'] ) ? sanitize_text_field( $system['risk_level'] ) : '',
				);
			}
		}

		// Sanitize texts per language.
		if ( isset( $value['texts'] ) && is_array( $value['texts'] ) ) {
			$languages = $this->get_languages();
			foreach ( $languages as $lang ) {
				if ( isset( $value['texts'][ $lang ] ) && is_array( $value['texts'][ $lang ] ) ) {
					$sanitized['texts'][ $lang ] = array(
						'title'                  => isset( $value['texts'][ $lang ]['title'] ) ? sanitize_text_field( $value['texts'][ $lang ]['title'] ) : '',
						'description'            => isset( $value['texts'][ $lang ]['description'] ) ? wp_kses_post( $value['texts'][ $lang ]['description'] ) : '',
						'systems_title'          => isset( $value['texts'][ $lang ]['systems_title'] ) ? sanitize_text_field( $value['texts'][ $lang ]['systems_title'] ) : '',
						'automated_title'        => isset( $value['texts'][ $lang ]['automated_title'] ) ? sanitize_text_field( $value['texts'][ $lang ]['automated_title'] ) : '',
						'automated_description'  => isset( $value['texts'][ $lang ]['automated_description'] ) ? wp_kses_post( $value['texts'][ $lang ]['automated_description'] ) : '',
						'profiling_title'       => isset( $value['texts'][ $lang ]['profiling_title'] ) ? sanitize_text_field( $value['texts'][ $lang ]['profiling_title'] ) : '',
						'profiling_description' => isset( $value['texts'][ $lang ]['profiling_description'] ) ? wp_kses_post( $value['texts'][ $lang ]['profiling_description'] ) : '',
						'rights_title'          => isset( $value['texts'][ $lang ]['rights_title'] ) ? sanitize_text_field( $value['texts'][ $lang ]['rights_title'] ) : '',
						'rights_description'    => isset( $value['texts'][ $lang ]['rights_description'] ) ? wp_kses_post( $value['texts'][ $lang ]['rights_description'] ) : '',
						'contact_text'          => isset( $value['texts'][ $lang ]['contact_text'] ) ? wp_kses_post( $value['texts'][ $lang ]['contact_text'] ) : '',
					);
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Get algorithmic transparency configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function get_algorithmic_transparency(): array {
		$defaults = $this->get_default_algorithmic_transparency();
		$stored = isset( $this->options['algorithmic_transparency'] ) && is_array( $this->options['algorithmic_transparency'] )
			? $this->options['algorithmic_transparency']
			: array();

		return wp_parse_args( $stored, $defaults );
	}

	/**
	 * Get default algorithmic transparency configuration.
	 *
	 * @return array<string, mixed>
	 */
	private function get_default_algorithmic_transparency(): array {
		return array(
			'enabled'            => false,
			'system_description' => '',
			'system_logic'       => '',
			'system_impact'      => '',
		);
	}

	/**
	 * Sanitize algorithmic transparency configuration.
	 *
	 * @param array<string, mixed> $value Value to sanitize.
	 * @param array<string, mixed> $defaults Defaults.
	 *
	 * @return array<string, mixed>
	 */
	private function sanitize_algorithmic_transparency( array $value, array $defaults ): array {
		return array(
			'enabled'            => Validator::bool( $value['enabled'] ?? $defaults['enabled'] ),
			'system_description' => isset( $value['system_description'] ) ? wp_kses_post( $value['system_description'] ) : '',
			'system_logic'       => isset( $value['system_logic'] ) ? wp_kses_post( $value['system_logic'] ) : '',
			'system_impact'      => isset( $value['system_impact'] ) ? wp_kses_post( $value['system_impact'] ) : '',
		);
	}

	/**
	 * Build empty script rule set for a language based on categories metadata.
	 *
	 * @param array<string, mixed> $categories Categories metadata.
	 *
	 * @return array<string, array<string, array<int, string>>>
	 */
	private function build_script_language_defaults( $categories ) {
		return $this->script_rules_manager->build_language_defaults(
			$categories,
			$this->get_snapshot_services()
		);
	}

	/**
	 * Normalize locale against active languages.
	 *
	 * @param string $locale Raw locale.
	 *
	 * @return string
	 */
	public function normalize_language( string $locale ): string {
		return $this->language_normalizer->normalize( $locale );
	}

	/**
	 * Get banner text for a language.
	 *
	 * @param string $lang Locale.
	 *
	 * @return array<string, string>
	 */
	public function get_banner_text( string $lang ): array {
		return $this->banner_texts_manager->get_banner_text( $lang );
	}

	/**
	 * Force update banner texts for all active languages with proper translations.
	 * Delegates to BannerTextsManager.
	 *
	 * @return void
	 */
	public function force_update_banner_texts_translations(): void {
		$this->banner_texts_manager->force_update_banner_texts_translations();
	}

	/**
	 * Detect user language from browser or WordPress locale.
	 * Delegates to BannerTextsManager.
	 *
	 * @return string
	 */
	public function detect_user_language(): string {
		return $this->banner_texts_manager->detect_user_language();
	}

	/**
	 * Get categories for the requested language.
	 * Delegates to CategoriesManager.
	 *
	 * @param string $lang Locale.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_categories_for_language( string $lang ): array {
		return $this->categories_manager->get_categories_for_language( $lang );
	}

	/**
	 * Retrieve a policy page id for type and language.
	 *
	 * @param string $type  privacy_policy|cookie_policy.
	 * @param string $lang  Locale.
	 *
	 * @return int
	 */
	public function get_page_id( string $type, string $lang ): int {
		return $this->page_manager->get_page_id(
			$type,
			$lang,
			isset( $this->options['pages'] ) ? $this->options['pages'] : array()
		);
	}

	/**
	 * Get banner layout as value object.
	 *
	 * @return BannerLayout
	 */
	public function get_banner_layout(): BannerLayout {
		$layout_data = isset( $this->options['banner_layout'] ) && \is_array( $this->options['banner_layout'] )
			? $this->options['banner_layout']
			: $this->get_default_options()['banner_layout'];

		return BannerLayout::from_array( $layout_data );
	}

	/**
	 * Get color palette as value object.
	 *
	 * @return ColorPalette
	 */
	public function get_color_palette(): ColorPalette {
		$layout = $this->get_banner_layout();
		return $layout->get_palette();
	}

	/**
	 * Get consent mode defaults as value object.
	 *
	 * @return ConsentModeDefaults
	 */
	public function get_consent_mode_defaults(): ConsentModeDefaults {
		$consent_mode_data = isset( $this->options['consent_mode_defaults'] ) && \is_array( $this->options['consent_mode_defaults'] )
			? $this->options['consent_mode_defaults']
			: $this->get_default_options()['consent_mode_defaults'];

		return ConsentModeDefaults::from_array( $consent_mode_data );
	}

	/**
	 * Normalize categories by merging with defaults and preserving locked state.
	 *
	 * @param array<string, mixed> $categories_raw Raw categories data.
	 * @param array<string, mixed> $default_categories Default categories.
	 * @param array<int, string>   $languages Active languages.
	 *
	 * @return array<string, mixed> Normalized categories.
	 */
	private function normalize_categories( array $categories_raw, array $default_categories, array $languages ): array {
		$categories = Validator::sanitize_categories( $categories_raw, $languages );
		$defaults   = Validator::sanitize_categories( $default_categories, $languages );

		$raw_categories_by_slug = array();

		foreach ( $categories_raw as $raw_slug => $raw_category ) {
			$normalized_slug = \sanitize_key( $raw_slug );

			if ( '' === $normalized_slug ) {
				continue;
			}

			$raw_categories_by_slug[ $normalized_slug ] = \is_array( $raw_category ) ? $raw_category : array();
		}

		if ( empty( $defaults ) ) {
			return $categories;
		}

		$normalized = array();

		foreach ( $defaults as $slug => $default_category ) {
			if ( isset( $categories[ $slug ] ) ) {
				$merged = \array_replace_recursive( $default_category, $categories[ $slug ] );

				$raw = $raw_categories_by_slug[ $slug ] ?? array();
				if ( ! \array_key_exists( 'locked', $raw ) ) {
					$merged['locked'] = $default_category['locked'];
				}

				$normalized[ $slug ] = $merged;
			} else {
				$normalized[ $slug ] = $default_category;
			}
		}

		foreach ( $categories as $slug => $category ) {
			if ( ! isset( $normalized[ $slug ] ) ) {
				$normalized[ $slug ] = $category;
			}
		}

		return $normalized;
	}

	/**
	 * Sanitize consent mode defaults using value object.
	 *
	 * @param array<string, string> $value    Raw consent mode values.
	 * @param array<string, string> $defaults Default consent mode values.
	 *
	 * @return array<string, string>
	 */
	private function sanitize_consent_mode_defaults( array $value, array $defaults ): array {
		// Merge with defaults first.
		$consent_mode_data = array_merge( $defaults, $value );
		
		// Use ConsentModeDefaults value object for validation and sanitization.
		$consent_mode = ConsentModeDefaults::from_array( $consent_mode_data );
		
		// Return as array for backward compatibility.
		return $consent_mode->to_array();
	}

	/**
	 * Increment consent revision.
	 *
	 * @return void
	 */
	public function bump_revision(): void {
		$this->options['consent_revision'] = isset( $this->options['consent_revision'] ) 
			? (int) $this->options['consent_revision'] + 1 
			: Constants::CONSENT_REVISION_INITIAL;
		\update_option( self::OPTION_KEY, $this->options, false );
	}

	/**
	 * Ensure required pages exist.
	 *
	 * @return void
	 */
	public function ensure_pages_exist(): void {
		// Prevent recursive calls.
		static $running = false;
		if ( $running ) {
			return;
		}
		$running = true;

		try {
			$languages = $this->get_languages();
			if ( ! is_array( $languages ) || empty( $languages ) ) {
				$languages = array( function_exists( '\\get_locale' ) ? (string) \get_locale() : 'en_US' );
			}

			$pages = isset( $this->options['pages'] ) && \is_array( $this->options['pages'] ) ? $this->options['pages'] : array();

			$updated_pages = $this->page_manager->ensure_pages_exist( $pages, $languages );

			if ( $updated_pages && is_array( $updated_pages ) ) {
				$this->options['pages'] = $updated_pages;
				\update_option( self::OPTION_KEY, $this->options, false );
			}
		} catch ( \Throwable $e ) {
			Logger::error( 'Error ensuring pages exist', $e );
		} finally {
			$running = false;
		}
	}
}