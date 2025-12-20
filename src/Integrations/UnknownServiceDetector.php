<?php
/**
 * Unknown service detector.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

/**
 * Handles detection of external scripts, styles, and iframes.
 */
class UnknownServiceDetector {
	/**
	 * Detect external scripts loaded on the page.
	 *
	 * @return array<int, string> Array of domains.
	 */
	public static function detect_external_scripts() {
		$domains = array();

		if ( ! function_exists( '\wp_scripts' ) ) {
			return $domains;
		}

		$wp_scripts = \wp_scripts();

		if ( empty( $wp_scripts->registered ) || ! is_array( $wp_scripts->registered ) ) {
			return $domains;
		}

		foreach ( $wp_scripts->registered as $handle => $script ) {
			if ( empty( $script->src ) ) {
				continue;
			}

			$src = (string) $script->src;

			// Skip local scripts
			if ( UnknownServiceUtils::is_local_url( $src ) ) {
				continue;
			}

			$domain = UnknownServiceUtils::extract_domain( $src );

			if ( $domain && ! in_array( $domain, $domains, true ) ) {
				$domains[] = $domain;
			}
		}

		return $domains;
	}

	/**
	 * Detect external styles loaded on the page.
	 *
	 * @return array<int, string> Array of domains.
	 */
	public static function detect_external_styles() {
		$domains = array();

		if ( ! function_exists( '\wp_styles' ) ) {
			return $domains;
		}

		$wp_styles = \wp_styles();

		if ( empty( $wp_styles->registered ) || ! is_array( $wp_styles->registered ) ) {
			return $domains;
		}

		foreach ( $wp_styles->registered as $handle => $style ) {
			if ( empty( $style->src ) ) {
				continue;
			}

			$src = (string) $style->src;

			if ( UnknownServiceUtils::is_local_url( $src ) ) {
				continue;
			}

			$domain = UnknownServiceUtils::extract_domain( $src );

			if ( $domain && ! in_array( $domain, $domains, true ) ) {
				$domains[] = $domain;
			}
		}

		return $domains;
	}

	/**
	 * Scan HTML output for inline scripts and external resources.
	 *
	 * @return array<int, string> Array of domains.
	 */
	public static function scan_html_output() {
		$domains = array();

		// Get output buffer content if available
		$content = self::get_page_content();

		if ( empty( $content ) ) {
			return $domains;
		}

		// Find all script src attributes
		\preg_match_all( '/<script[^>]+src=["\']([^"\']+)["\']/', $content, $script_matches );

		if ( ! empty( $script_matches[1] ) ) {
			foreach ( $script_matches[1] as $src ) {
				if ( UnknownServiceUtils::is_local_url( $src ) ) {
					continue;
				}

				$domain = UnknownServiceUtils::extract_domain( $src );
				if ( $domain && ! in_array( $domain, $domains, true ) ) {
					$domains[] = $domain;
				}
			}
		}

		// Find all link href attributes (for stylesheets, preconnect, etc)
		\preg_match_all( '/<link[^>]+href=["\']([^"\']+)["\']/', $content, $link_matches );

		if ( ! empty( $link_matches[1] ) ) {
			foreach ( $link_matches[1] as $href ) {
				if ( UnknownServiceUtils::is_local_url( $href ) ) {
					continue;
				}

				$domain = UnknownServiceUtils::extract_domain( $href );
				if ( $domain && ! in_array( $domain, $domains, true ) ) {
					$domains[] = $domain;
				}
			}
		}

		return $domains;
	}

	/**
	 * Detect third-party iframes.
	 *
	 * @return array<int, string> Array of domains.
	 */
	public static function detect_iframes() {
		$domains = array();

		$content = self::get_page_content();

		if ( empty( $content ) ) {
			return $domains;
		}

		// Find all iframe src attributes
		\preg_match_all( '/<iframe[^>]+src=["\']([^"\']+)["\']/', $content, $iframe_matches );

		if ( ! empty( $iframe_matches[1] ) ) {
			foreach ( $iframe_matches[1] as $src ) {
				if ( UnknownServiceUtils::is_local_url( $src ) ) {
					continue;
				}

				$domain = UnknownServiceUtils::extract_domain( $src );
				if ( $domain && ! in_array( $domain, $domains, true ) ) {
					$domains[] = $domain;
				}
			}
		}

		return $domains;
	}

	/**
	 * Get page content for scanning.
	 *
	 * @return string
	 */
	private static function get_page_content() {
		// Try to get from recent posts
		if ( ! function_exists( '\get_posts' ) ) {
			return '';
		}

		$posts = \get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		$content = '';

		foreach ( $posts as $post ) {
			if ( ! empty( $post->post_content ) ) {
				$content .= $post->post_content . "\n\n";
			}
		}

		return $content;
	}
}















