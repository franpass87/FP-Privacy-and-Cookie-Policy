<?php
/**
 * Bootstrap PHPUnit senza WordPress completo (stub minime per funzioni globali usate in unit test).
 *
 * @package FP\Privacy\Tests
 */

declare(strict_types=1);

$plugin_root = dirname(__DIR__);
require $plugin_root . '/vendor/autoload.php';

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param mixed $value Value.
	 * @param mixed ...$args Extra args.
	 * @return mixed
	 */
	function apply_filters( $hook, $value, ...$args ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return $value;
	}
}

if ( ! function_exists( 'get_locale' ) ) {
	function get_locale(): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return 'en_US';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param mixed $str String.
	 * @return string
	 */
	function sanitize_text_field( $str ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return is_scalar( $str ) ? trim( (string) $str ) : '';
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * @param array<string, mixed>|object $args    User-defined args.
	 * @param array<string, mixed>        $defaults Defaults.
	 * @return array<string, mixed>
	 */
	function wp_parse_args( $args, $defaults = array() ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		return array_merge( $defaults, $args );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * @param mixed $data Data.
	 * @return string
	 */
	function wp_kses_post( $data ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return is_string( $data ) ? $data : '';
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * @param mixed $url URL.
	 * @return string
	 */
	function esc_url_raw( $url ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return is_string( $url ) ? trim( $url ) : '';
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	/**
	 * @param mixed $email Email.
	 * @return string
	 */
	function sanitize_email( $email ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$e = is_string( $email ) ? trim( $email ) : '';
		$v = filter_var( $e, FILTER_VALIDATE_EMAIL );
		return is_string( $v ) ? $v : '';
	}
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
	/**
	 * @param mixed $color Color.
	 * @return string
	 */
	function sanitize_hex_color( $color ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		if ( is_string( $color ) && preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
			return $color;
		}
		return '';
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * @param string $url       URL.
	 * @param int    $component PHP_URL_* or -1.
	 * @return mixed
	 */
	function wp_parse_url( $url, $component = -1 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'rest_sanitize_boolean' ) ) {
	/**
	 * @param mixed $value Value.
	 * @return bool
	 */
	function rest_sanitize_boolean( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false;
	}
}
