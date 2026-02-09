<?php
/**
 * Options validator - handles validation and sanitization of plugin options.
 *
 * @package FP\Privacy\Services\Validation
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Validation;

use FP\Privacy\Domain\ValueObjects\BannerLayout;
use FP\Privacy\Domain\ValueObjects\ConsentModeDefaults;
use FP\Privacy\Shared\Constants;
use FP\Privacy\Utils\Validator;
use FP\Privacy\Utils\OptionsSanitizer;

/**
 * Validator for plugin options.
 */
class OptionsValidator {
	/**
	 * Script rules manager.
	 *
	 * @var \FP\Privacy\Utils\ScriptRulesManager|null
	 */
	private $script_rules_manager;

	/**
	 * Language normalizer.
	 *
	 * @var \FP\Privacy\Utils\LanguageNormalizer|null
	 */
	private $language_normalizer;

	/**
	 * Default detector notifications.
	 *
	 * @var array<string, mixed>
	 */
	private $default_detector_notifications;

	/**
	 * Constructor.
	 *
	 * @param \FP\Privacy\Utils\ScriptRulesManager|null $script_rules_manager Script rules manager.
	 * @param \FP\Privacy\Utils\LanguageNormalizer|null  $language_normalizer Language normalizer.
	 * @param array<string, mixed>                       $default_detector_notifications Default detector notifications.
	 */
	public function __construct(
		?\FP\Privacy\Utils\ScriptRulesManager $script_rules_manager = null,
		?\FP\Privacy\Utils\LanguageNormalizer $language_normalizer = null,
		array $default_detector_notifications = array()
	) {
		$this->script_rules_manager          = $script_rules_manager;
		$this->language_normalizer            = $language_normalizer;
		$this->default_detector_notifications = $default_detector_notifications;
	}

	/**
	 * Sanitize options array.
	 *
	 * @param array<string, mixed> $value           Value to sanitize.
	 * @param array<string, mixed> $defaults        Defaults.
	 * @param array<string, mixed> $existing_options Existing options (for scripts).
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize( array $value, array $defaults, array $existing_options = array() ): array {
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
		$existing_scripts  = isset( $existing_options['scripts'] ) && \is_array( $existing_options['scripts'] ) ? $existing_options['scripts'] : array();

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
			)
		);
		
		// Create BannerLayout value object which validates and sanitizes automatically.
		$banner_layout = BannerLayout::from_array( $layout_data );
		$layout = $banner_layout->to_array();

		$default_categories = Validator::sanitize_categories( $defaults['categories'], $languages );
		$categories         = Validator::sanitize_categories( $categories_raw, $languages );

		$raw_categories_by_slug = array();

		foreach ( $categories_raw as $raw_slug => $raw_category ) {
			$normalized_slug = \sanitize_key( $raw_slug );

			if ( '' === $normalized_slug ) {
				continue;
			}

			$raw_categories_by_slug[ $normalized_slug ] = \is_array( $raw_category ) ? $raw_category : array();
		}

		if ( ! empty( $default_categories ) ) {
			$normalized = array();

			foreach ( $default_categories as $slug => $default_category ) {
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

			$categories = $normalized;
		}

		// Create temporary normalizer for sanitization if not provided.
		$temp_normalizer = $this->language_normalizer;
		if ( null === $temp_normalizer && class_exists( '\\FP\\Privacy\\Utils\\LanguageNormalizer' ) ) {
			$temp_normalizer = new \FP\Privacy\Utils\LanguageNormalizer( $languages );
		}

		// Script rules sanitization.
		$scripts = $scripts_raw;
		if ( $this->script_rules_manager && $temp_normalizer ) {
			$scripts = $this->script_rules_manager->sanitize_rules( $scripts_raw, $languages, $categories, $existing_scripts, $temp_normalizer );
		}

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
			'scripts'               => $scripts,
			'detector_alert'        => OptionsSanitizer::sanitize_detector_alert( $alert_raw ),
			'detector_notifications' => OptionsSanitizer::sanitize_detector_notifications( $notifications_raw, $this->default_detector_notifications ?: ( $defaults['detector_notifications'] ?? array() ) ),
			'auto_update_services'  => Validator::bool( $value['auto_update_services'] ?? $defaults['auto_update_services'] ),
			'auto_update_policies'  => Validator::bool( $value['auto_update_policies'] ?? $defaults['auto_update_policies'] ),
			'auto_translations'     => Validator::sanitize_auto_translations( isset( $value['auto_translations'] ) && \is_array( $value['auto_translations'] ) ? $value['auto_translations'] : array(), $banner_defaults ),
			'ai_disclosure'              => $this->sanitize_ai_disclosure( isset( $value['ai_disclosure'] ) && \is_array( $value['ai_disclosure'] ) ? $value['ai_disclosure'] : array(), $defaults['ai_disclosure'] ?? array() ),
			'algorithmic_transparency'   => $this->sanitize_algorithmic_transparency( isset( $value['algorithmic_transparency'] ) && \is_array( $value['algorithmic_transparency'] ) ? $value['algorithmic_transparency'] : array(), $defaults['algorithmic_transparency'] ?? array() ),
			'enable_sub_categories'      => Validator::bool( $value['enable_sub_categories'] ?? ( $defaults['enable_sub_categories'] ?? false ) ),
		);
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
	 * Sanitize AI disclosure configuration.
	 *
	 * @param array<string, mixed> $value    Value to sanitize.
	 * @param array<string, mixed> $defaults Defaults.
	 *
	 * @return array<string, mixed>
	 */
	private function sanitize_ai_disclosure( array $value, array $defaults ): array {
		$default_ai = \wp_parse_args(
			$defaults,
			array(
				'enabled'             => false,
				'systems'             => array(),
				'automated_decisions' => false,
				'profiling'           => false,
				'texts'               => array(),
			)
		);

		$sanitized = array(
			'enabled'             => Validator::bool( $value['enabled'] ?? $default_ai['enabled'] ),
			'systems'             => array(),
			'automated_decisions' => Validator::bool( $value['automated_decisions'] ?? $default_ai['automated_decisions'] ),
			'profiling'           => Validator::bool( $value['profiling'] ?? $default_ai['profiling'] ),
			'texts'               => array(),
		);

		// Sanitize AI systems.
		if ( isset( $value['systems'] ) && \is_array( $value['systems'] ) ) {
			foreach ( $value['systems'] as $system ) {
				if ( ! \is_array( $system ) || empty( $system['name'] ) ) {
					continue;
				}

				$sanitized['systems'][] = array(
					'name'       => \sanitize_text_field( $system['name'] ),
					'purpose'    => isset( $system['purpose'] ) ? \wp_kses_post( $system['purpose'] ) : '',
					'risk_level' => isset( $system['risk_level'] ) ? \sanitize_text_field( $system['risk_level'] ) : '',
				);
			}
		}

		// Sanitize texts per language using the normalizer.
		if ( isset( $value['texts'] ) && \is_array( $value['texts'] ) ) {
			foreach ( $value['texts'] as $lang => $lang_texts ) {
				if ( ! \is_array( $lang_texts ) ) {
					continue;
				}

				$sanitized['texts'][ $lang ] = array(
					'title'                  => isset( $lang_texts['title'] ) ? \sanitize_text_field( $lang_texts['title'] ) : '',
					'description'            => isset( $lang_texts['description'] ) ? \wp_kses_post( $lang_texts['description'] ) : '',
					'systems_title'          => isset( $lang_texts['systems_title'] ) ? \sanitize_text_field( $lang_texts['systems_title'] ) : '',
					'automated_title'        => isset( $lang_texts['automated_title'] ) ? \sanitize_text_field( $lang_texts['automated_title'] ) : '',
					'automated_description'  => isset( $lang_texts['automated_description'] ) ? \wp_kses_post( $lang_texts['automated_description'] ) : '',
					'profiling_title'        => isset( $lang_texts['profiling_title'] ) ? \sanitize_text_field( $lang_texts['profiling_title'] ) : '',
					'profiling_description'  => isset( $lang_texts['profiling_description'] ) ? \wp_kses_post( $lang_texts['profiling_description'] ) : '',
					'rights_title'           => isset( $lang_texts['rights_title'] ) ? \sanitize_text_field( $lang_texts['rights_title'] ) : '',
					'rights_description'     => isset( $lang_texts['rights_description'] ) ? \wp_kses_post( $lang_texts['rights_description'] ) : '',
					'contact_text'           => isset( $lang_texts['contact_text'] ) ? \wp_kses_post( $lang_texts['contact_text'] ) : '',
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize algorithmic transparency configuration.
	 *
	 * @param array<string, mixed> $value    Value to sanitize.
	 * @param array<string, mixed> $defaults Defaults.
	 *
	 * @return array<string, mixed>
	 */
	private function sanitize_algorithmic_transparency( array $value, array $defaults ): array {
		$default_at = \wp_parse_args(
			$defaults,
			array(
				'enabled'            => false,
				'system_description' => '',
				'system_logic'       => '',
				'system_impact'      => '',
			)
		);

		return array(
			'enabled'            => Validator::bool( $value['enabled'] ?? $default_at['enabled'] ),
			'system_description' => isset( $value['system_description'] ) ? \wp_kses_post( $value['system_description'] ) : '',
			'system_logic'       => isset( $value['system_logic'] ) ? \wp_kses_post( $value['system_logic'] ) : '',
			'system_impact'      => isset( $value['system_impact'] ) ? \wp_kses_post( $value['system_impact'] ) : '',
		);
	}
}

