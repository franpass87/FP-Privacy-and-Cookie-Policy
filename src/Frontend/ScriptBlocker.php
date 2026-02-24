<?php
/**
 * Blocks scripts and embeds until consent is granted.
 *
 * @package FP\Privacy\Frontend
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

use function is_admin;
use function preg_match;
use function preg_replace_callback;

/**
 * Transforms matching assets into inert placeholders.
 */
class ScriptBlocker {
	/**
	 * Rules manager.
	 *
	 * @var ScriptBlockerRules
	 */
	private $rules_manager;

	/**
	 * Constructor.
	 *
	 * @param Options      $options Options handler.
	 * @param ConsentState $state   Consent state.
	 */
	public function __construct( Options $options, ConsentState $state ) {
		$this->rules_manager = new ScriptBlockerRules( $options, $state );
	}

	/**
	 * Register hooks when not in the admin area.
	 *
	 * @return void
	 */
	public function hooks() {
		if ( is_admin() ) {
			return;
		}

		\add_filter( 'script_loader_tag', array( $this, 'filter_script_tag' ), 999, 3 );
		\add_filter( 'style_loader_tag', array( $this, 'filter_style_tag' ), 999, 4 );
		\add_filter( 'the_content', array( $this, 'filter_content' ), 9 );
		\add_filter( 'widget_text_content', array( $this, 'filter_content' ), 9 );
		\add_filter( 'widget_block_content', array( $this, 'filter_content' ), 9 );
	}


	/**
	 * Replace script tags when necessary.
	 *
	 * @param string $tag    Original tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Source URL.
	 *
	 * @return string
	 */
	public function filter_script_tag( $tag, $handle, $src ) {
		$rules = $this->rules_manager->get_rules();

		if ( empty( $rules['script_handles'] ) && empty( $rules['script_patterns'] ) ) {
			return $tag;
		}

		$category = $this->rules_manager->get_script_handle_category( $handle );

		if ( '' === $category && '' !== $src ) {
			$category = $this->rules_manager->get_script_pattern_category( $src );
		}

		if ( '' === $category || ! $this->rules_manager->should_block_category( $category ) ) {
			return $tag;
		}

		$label = $this->rules_manager->get_category_label( $category );
		return ScriptBlockerPlaceholder::build( $tag, 'script', $category, $label );
	}

	/**
	 * Replace style tags when necessary.
	 *
	 * @param string $tag    Original tag HTML.
	 * @param string $handle Style handle.
	 * @param string $href   Stylesheet href.
	 * @param string $media  Media attribute.
	 *
	 * @return string
	 */
	public function filter_style_tag( $tag, $handle, $href, $media ) {
		$rules = $this->rules_manager->get_rules();

		if ( empty( $rules['style_handles'] ) ) {
			return $tag;
		}

		$category = $this->rules_manager->get_style_handle_category( $handle );

		if ( '' === $category || ! $this->rules_manager->should_block_category( $category ) ) {
			return $tag;
		}

		$label = $this->rules_manager->get_category_label( $category );
		return ScriptBlockerPlaceholder::build( $tag, 'style', $category, $label );
	}

	/**
	 * Filter post content to block inline scripts and iframes.
	 *
	 * @param string $content HTML content.
	 *
	 * @return string
	 */
	public function filter_content( $content ) {
		$rules = $this->rules_manager->get_rules();

		$result = $content;

		if ( ! empty( $rules['script_patterns'] ) ) {
			$result = preg_replace_callback(
				'/<script\b[^>]*>.*?<\/script>/is',
				function ( $matches ) {
					$tag      = $matches[0];
					$category = '';

					if ( preg_match( '/src\s*=\s*(?:"([^"]+)"|\'([^\']+)\')/i', $tag, $src_match ) ) {
						$src_value = isset( $src_match[1] ) && '' !== $src_match[1] ? $src_match[1] : ( isset( $src_match[2] ) ? $src_match[2] : '' );
						$category  = $this->rules_manager->get_script_pattern_category( $src_value );
					}

					if ( '' === $category || ! $this->rules_manager->should_block_category( $category ) ) {
						return $tag;
					}

					$label = $this->rules_manager->get_category_label( $category );
					return ScriptBlockerPlaceholder::build( $tag, 'script', $category, $label );
				},
				$result
			);
		}

		if ( ! empty( $rules['iframe_patterns'] ) ) {
			$result = preg_replace_callback(
				'/<iframe\b[^>]*>.*?<\/iframe>/is',
				function ( $matches ) {
					$tag      = $matches[0];
					$category = '';

					if ( preg_match( '/src\s*=\s*(?:"([^"]+)"|\'([^\']+)\')/i', $tag, $src_match ) ) {
						$src_value = isset( $src_match[1] ) && '' !== $src_match[1] ? $src_match[1] : ( isset( $src_match[2] ) ? $src_match[2] : '' );
						$category  = $this->rules_manager->get_iframe_pattern_category( $src_value );
					}

					if ( '' === $category || ! $this->rules_manager->should_block_category( $category ) ) {
						return $tag;
					}

					$label = $this->rules_manager->get_category_label( $category );
					return ScriptBlockerPlaceholder::build( $tag, 'iframe', $category, $label );
				},
				$result
			);
		}

		return $result;
	}
}
