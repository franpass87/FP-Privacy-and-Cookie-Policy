<?php
/**
 * Scans options and content for tracking/analytics URL or code patterns.
 * Used as fallback when script-based detection fails (e.g. in admin context).
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

/**
 * Detects presence of tracking tools by scanning WordPress options and post content.
 */
class TrackingPatternScanner {

	/**
	 * Option keys often used by themes/plugins for tracking scripts or IDs.
	 *
	 * @var array<int, string>
	 */
	private static $option_keys_to_scan = array(
		'ga_dash_tracking',
		'ga_measurement_id',
		'google_analytics',
		'gtm_container_id',
		'gtm_id',
		'facebook_pixel_id',
		'fb_pixel_id',
		'hotjar_site_id',
		'hj_site_id',
		'clarity_project_id',
		'ms_clarity',
		'recaptcha_site_key',
		'grecaptcha',
	);

	/**
	 * Max number of posts to scan for pattern matches.
	 *
	 * @var int
	 */
	private static $max_posts_to_scan = 30;

	/**
	 * Runtime cache: result of site_contains_any() per pattern set hash.
	 *
	 * @var array<string, bool>
	 */
	private static $scan_cache = array();

	/**
	 * Check whether the site contains any of the given patterns (in options or post content).
	 *
	 * @param array<int, string> $patterns List of substrings to search for (e.g. 'googletagmanager.com', "gtag('config'").
	 * @return bool True if any pattern is found.
	 */
	public static function site_contains_any( array $patterns ) {
		$patterns = array_filter( array_map( 'strval', $patterns ) );
		if ( empty( $patterns ) ) {
			return false;
		}

		$cache_key = md5( implode( "\n", $patterns ) );
		if ( isset( self::$scan_cache[ $cache_key ] ) ) {
			return self::$scan_cache[ $cache_key ];
		}

		$found = self::scan_options_for_patterns( $patterns ) || self::scan_content_for_patterns( $patterns );
		self::$scan_cache[ $cache_key ] = $found;

		return $found;
	}

	/**
	 * Scan common WordPress options (and theme_mods) for tracking-related strings.
	 *
	 * @param array<int, string> $patterns Substrings to search for.
	 * @return bool True if any pattern is found in any scanned option value.
	 */
	public static function scan_options_for_patterns( array $patterns ) {
		if ( ! function_exists( '\get_option' ) ) {
			return false;
		}

		$options_to_check = self::get_options_to_scan();

		foreach ( $options_to_check as $option_name => $value ) {
			if ( $value === null || $value === '' || $value === false ) {
				continue;
			}
			if ( is_array( $value ) && empty( $value ) ) {
				continue;
			}
			$haystack = is_scalar( $value ) ? (string) $value : ( function_exists( 'wp_json_encode' ) ? wp_json_encode( $value ) : \json_encode( $value, \JSON_UNESCAPED_UNICODE ) );
			if ( ! is_string( $haystack ) || $haystack === '' ) {
				continue;
			}
			foreach ( $patterns as $needle ) {
				if ( '' !== $needle && false !== stripos( $haystack, $needle ) ) {
					return true;
				}
			}
		}

		// Theme mods (active theme and parent).
		$theme_slugs = array();
		if ( function_exists( '\get_stylesheet' ) ) {
			$theme_slugs[] = \get_stylesheet();
		}
		if ( function_exists( '\get_template' ) ) {
			$template = \get_template();
			if ( ! in_array( $template, $theme_slugs, true ) ) {
				$theme_slugs[] = $template;
			}
		}
		foreach ( $theme_slugs as $slug ) {
			$mods = \get_option( 'theme_mods_' . $slug );
			if ( ! is_array( $mods ) || empty( $mods ) ) {
				continue;
			}
			$haystack = function_exists( 'wp_json_encode' ) ? wp_json_encode( $mods ) : \json_encode( $mods, \JSON_UNESCAPED_UNICODE );
			if ( ! is_string( $haystack ) ) {
				continue;
			}
			foreach ( $patterns as $needle ) {
				if ( '' !== $needle && false !== stripos( $haystack, $needle ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get options to scan (known keys + filtered).
	 *
	 * @return array<string, mixed>
	 */
	private static function get_options_to_scan() {
		$list = array();

		foreach ( self::$option_keys_to_scan as $key ) {
			if ( strpos( $key, 'theme_mods_' ) === 0 ) {
				continue;
			}
			$val = \get_option( $key, null );
			if ( $val !== null ) {
				$list[ $key ] = $val;
			}
		}

		// Options that often contain script snippets or IDs (common plugin/theme keys).
		$extra_keys = array(
			'blogname',
			'blogdescription',
			'header_scripts',
			'footer_scripts',
			'custom_code_header',
			'custom_code_footer',
			'customizer_scripts',
			'tracking_code',
			'analytics_code',
			'gtm_code',
			'ns_footer_scripts',
			'salient_google_analytics',
			'nectar_google_analytics',
			'woocommerce_google_analytics_id',
			'widget_text', // text widgets often contain tracking snippets
			'monsterinsights_site_profile',
			'exactmetrics_site_profile',
			'nectar_theme_options',
			'salient_theme_options',
		);

		foreach ( $extra_keys as $key ) {
			if ( strpos( $key, '*' ) !== false ) {
				continue;
			}
			$val = \get_option( $key, null );
			if ( $val !== null && ! isset( $list[ $key ] ) ) {
				$list[ $key ] = $val;
			}
		}

		if ( function_exists( '\apply_filters' ) ) {
			$list = (array) \apply_filters( 'fp_privacy_tracking_scanner_option_keys', $list );
		}

		return $list;
	}

	/**
	 * Scan post content (and meta that might hold scripts) for patterns.
	 *
	 * @param array<int, string> $patterns Substrings to search for.
	 * @return bool True if any pattern is found.
	 */
	public static function scan_content_for_patterns( array $patterns ) {
		if ( ! class_exists( '\WP_Query' ) ) {
			return false;
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => self::$max_posts_to_scan,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		if ( empty( $query->posts ) || ! is_array( $query->posts ) ) {
			return false;
		}

		foreach ( $query->posts as $post_id ) {
			$content = '';
			if ( function_exists( '\get_post_field' ) ) {
				$content = (string) \get_post_field( 'post_content', $post_id );
			}
			// Some themes store header/footer scripts in post meta.
			if ( function_exists( '\get_post_meta' ) ) {
				$custom = \get_post_meta( $post_id, '_custom_header_footer', true );
				if ( is_string( $custom ) ) {
					$content .= "\n" . $custom;
				}
			}
			$content = trim( $content );
			if ( '' === $content ) {
				continue;
			}
			foreach ( $patterns as $needle ) {
				if ( '' !== $needle && false !== stripos( $content, $needle ) ) {
					return true;
				}
			}
		}

		// Widget blocks and options (sidebars often have HTML/script).
		// Scan widget options: sidebars_widgets has structure; widget content is in widget_text (already in get_options_to_scan). Skip sidebars_widgets for patterns (it only has widget IDs). Optionally scan other widget_* options that might hold HTML.
		$widget_option_prefixes = array( 'widget_custom_html', 'widget_block', 'widget_html' );
		if ( function_exists( '\get_option' ) ) {
			foreach ( $widget_option_prefixes as $prefix ) {
				$val = \get_option( $prefix, null );
				if ( $val === null || ! is_array( $val ) ) {
					continue;
				}
				$haystack = function_exists( 'wp_json_encode' ) ? wp_json_encode( $val ) : \json_encode( $val, \JSON_UNESCAPED_UNICODE );
				if ( $haystack === false ) {
					continue;
				}
				foreach ( $patterns as $needle ) {
					if ( '' !== $needle && false !== stripos( $haystack, $needle ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Clear runtime cache (e.g. after options change).
	 *
	 * @return void
	 */
	public static function clear_cache() {
		self::$scan_cache = array();
	}
}
