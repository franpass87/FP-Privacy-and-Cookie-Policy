<?php
/**
 * Script blocker rules manager.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;
use function is_array;
use function sanitize_key;
use function stripos;
use function trim;

/**
 * Handles preparation and matching of blocking rules.
 */
class ScriptBlockerRules {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Consent state handler.
	 *
	 * @var ConsentState
	 */
	private $state;

	/**
	 * Whether the rules have been prepared.
	 *
	 * @var bool
	 */
	private $prepared = false;

	/**
	 * Current language.
	 *
	 * @var string
	 */
	private $language = '';

	/**
	 * Blocking rules.
	 *
	 * @var array<string, mixed>
	 */
	private $rules = array();

	/**
	 * Allowed categories map.
	 *
	 * @var array<string, bool>
	 */
	private $allowed = array();

	/**
	 * Category labels for placeholders.
	 *
	 * @var array<string, string>
	 */
	private $category_labels = array();

	/**
	 * Constructor.
	 *
	 * @param Options      $options Options handler.
	 * @param ConsentState $state   Consent state.
	 */
	public function __construct( Options $options, ConsentState $state ) {
		$this->options = $options;
		$this->state   = $state;
	}

	/**
	 * Hydrate rules and state.
	 *
	 * @return void
	 */
	public function prepare() {
		if ( $this->prepared ) {
			return;
		}

		$locale = '';

		if ( \function_exists( '\\determine_locale' ) ) {
			$locale = \determine_locale();
		}

		if ( '' === $locale && \function_exists( '\\get_locale' ) ) {
			$locale = \get_locale();
		}

		$locale        = $this->options->normalize_language( $locale ?: 'en_US' );
		$rules_by_cat  = $this->options->get_script_rules_for_language( $locale );
		$categories    = $this->options->get_categories_for_language( $locale );
		$frontend      = $this->state->get_frontend_state( $locale );
		$consent_state = array();
		$preview       = false;

		if ( isset( $frontend['state'] ) && is_array( $frontend['state'] ) ) {
			$preview = ! empty( $frontend['state']['preview_mode'] );

			if ( isset( $frontend['state']['categories'] ) && is_array( $frontend['state']['categories'] ) ) {
				$consent_state = $frontend['state']['categories'];
			}
		}

		$script_handles  = array();
		$style_handles   = array();
		$script_patterns = array();
		$iframe_patterns = array();

		foreach ( $rules_by_cat as $category => $rule_set ) {
			if ( empty( $rule_set ) || ! is_array( $rule_set ) ) {
				continue;
			}

			if ( ! empty( $rule_set['script_handles'] ) && is_array( $rule_set['script_handles'] ) ) {
				foreach ( $rule_set['script_handles'] as $handle ) {
					$script_handles[ $handle ] = $category;
				}
			}

			if ( ! empty( $rule_set['style_handles'] ) && is_array( $rule_set['style_handles'] ) ) {
				foreach ( $rule_set['style_handles'] as $handle ) {
					$style_handles[ $handle ] = $category;
				}
			}

			if ( ! empty( $rule_set['patterns'] ) && is_array( $rule_set['patterns'] ) ) {
				foreach ( $rule_set['patterns'] as $pattern ) {
					$pattern = trim( $pattern );

					if ( '' === $pattern ) {
						continue;
					}

					$script_patterns[] = array(
						'pattern'  => $pattern,
						'category' => $category,
					);
				}
			}

			if ( ! empty( $rule_set['iframes'] ) && is_array( $rule_set['iframes'] ) ) {
				foreach ( $rule_set['iframes'] as $pattern ) {
					$pattern = trim( $pattern );

					if ( '' === $pattern ) {
						continue;
					}

					$iframe_patterns[] = array(
						'pattern'  => $pattern,
						'category' => $category,
					);
				}
			}
		}

		$this->language        = $locale;
		$this->rules           = array(
			'script_handles'  => $script_handles,
			'style_handles'   => $style_handles,
			'script_patterns' => $script_patterns,
			'iframe_patterns' => $iframe_patterns,
		);
		$this->category_labels = array();
		$this->allowed         = array();

		foreach ( $categories as $slug => $meta ) {
			$this->category_labels[ $slug ] = isset( $meta['label'] ) ? (string) $meta['label'] : $slug;

			if ( $preview ) {
				$this->allowed[ $slug ] = true;
				continue;
			}

			if ( ! empty( $meta['locked'] ) ) {
				$this->allowed[ $slug ] = true;
				continue;
			}

			$this->allowed[ $slug ] = isset( $consent_state[ $slug ] ) ? (bool) $consent_state[ $slug ] : false;
		}

		$this->prepared = true;
	}

	/**
	 * Get blocking rules.
	 *
	 * @return array<string, mixed>
	 */
	public function get_rules() {
		$this->prepare();
		return $this->rules;
	}

	/**
	 * Get category label.
	 *
	 * @param string $category Category slug.
	 *
	 * @return string
	 */
	public function get_category_label( $category ) {
		$this->prepare();
		return isset( $this->category_labels[ $category ] ) ? $this->category_labels[ $category ] : $category;
	}

	/**
	 * Check if a category should be blocked.
	 *
	 * @param string $category Category slug.
	 *
	 * @return bool
	 */
	public function should_block_category( $category ) {
		$this->prepare();
		if ( isset( $this->allowed[ $category ] ) ) {
			return ! $this->allowed[ $category ];
		}

		return true;
	}

	/**
	 * Attempt to match a value against configured patterns.
	 *
	 * @param string $value    Candidate string.
	 * @param array  $patterns Pattern definitions.
	 *
	 * @return string Category slug or empty string.
	 */
	public function match_pattern_category( $value, $patterns ) {
		if ( empty( $patterns ) || '' === $value ) {
			return '';
		}

		foreach ( $patterns as $entry ) {
			if ( empty( $entry['pattern'] ) || ! isset( $entry['category'] ) ) {
				continue;
			}

			if ( false !== stripos( $value, $entry['pattern'] ) ) {
				return (string) $entry['category'];
			}
		}

		return '';
	}

	/**
	 * Get category for a script handle.
	 *
	 * @param string $handle Script handle.
	 *
	 * @return string Category slug or empty string.
	 */
	public function get_script_handle_category( $handle ) {
		$this->prepare();
		$normalized_handle = sanitize_key( $handle );

		if ( '' !== $normalized_handle && isset( $this->rules['script_handles'][ $normalized_handle ] ) ) {
			return $this->rules['script_handles'][ $normalized_handle ];
		}

		return '';
	}

	/**
	 * Get category for a style handle.
	 *
	 * @param string $handle Style handle.
	 *
	 * @return string Category slug or empty string.
	 */
	public function get_style_handle_category( $handle ) {
		$this->prepare();
		$normalized_handle = sanitize_key( $handle );

		if ( '' !== $normalized_handle && isset( $this->rules['style_handles'][ $normalized_handle ] ) ) {
			return $this->rules['style_handles'][ $normalized_handle ];
		}

		return '';
	}

	/**
	 * Get category for a script URL pattern.
	 *
	 * @param string $src Script source URL.
	 *
	 * @return string Category slug or empty string.
	 */
	public function get_script_pattern_category( $src ) {
		$this->prepare();
		return $this->match_pattern_category( $src, $this->rules['script_patterns'] );
	}

	/**
	 * Get category for an iframe pattern.
	 *
	 * @param string $content Content to check.
	 *
	 * @return string Category slug or empty string.
	 */
	public function get_iframe_pattern_category( $content ) {
		$this->prepare();
		return $this->match_pattern_category( $content, $this->rules['iframe_patterns'] );
	}
}















