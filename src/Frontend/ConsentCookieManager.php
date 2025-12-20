<?php
/**
 * Consent cookie manager.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

/**
 * Handles cookie persistence for consent state.
 */
class ConsentCookieManager {
	const COOKIE_NAME = 'fp_consent_state_id';

	/**
	 * Get cookie payload.
	 *
	 * @return array{id: string, rev: int}
	 */
	public static function get_cookie_payload() {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return array(
				'id'  => '',
				'rev' => 0,
			);
		}

		$value = \sanitize_text_field( \wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );

		// Verify that the cookie value is not empty
		if ( empty( $value ) ) {
			return array(
				'id'  => '',
				'rev' => 0,
			);
		}

		$parts = explode( '|', $value );

		// Ensure we have at least the consent ID
		$consent_id = isset( $parts[0] ) ? trim( $parts[0] ) : '';
		$revision   = isset( $parts[1] ) ? (int) $parts[1] : 0;

		// Verify that the consent ID is valid (not empty and of reasonable length)
		if ( empty( $consent_id ) || strlen( $consent_id ) < 8 ) {
			return array(
				'id'  => '',
				'rev' => 0,
			);
		}

		return array(
			'id'  => $consent_id,
			'rev' => $revision,
		);
	}

	/**
	 * Set cookie.
	 *
	 * @param string $id   Consent ID.
	 * @param int    $rev  Revision.
	 * @param int    $time Expiration timestamp.
	 *
	 * @return void
	 */
	public static function set_cookie( $id, $rev, $time = 0 ) {
		if ( headers_sent() ) {
			return;
		}

		$days = (int) \apply_filters( 'fp_privacy_cookie_duration_days', \FP\Privacy\Shared\Constants::COOKIE_DURATION_DAYS_DEFAULT );
		$days = $days > 0 ? $days : \FP\Privacy\Shared\Constants::COOKIE_DURATION_DAYS_DEFAULT;

		if ( ! $time ) {
			$time = time() + ( $days * DAY_IN_SECONDS );
		}

		$secure        = \is_ssl();
		$value         = $id ? $id . '|' . (int) $rev : '';
		$cookie_path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$cookie_domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

		$options = array(
			'expires'  => $time,
			'path'     => $cookie_path,
			'domain'   => $cookie_domain,
			'secure'   => $secure,
			'httponly' => false,
			'samesite' => 'Lax',
		);

		if ( function_exists( '\apply_filters' ) ) {
			$filtered = \apply_filters( 'fp_privacy_cookie_options', $options, $value, $id, $rev );

			if ( is_array( $filtered ) ) {
				$options = array_merge( $options, $filtered );
			}
		}

		\setcookie( self::COOKIE_NAME, $value, $options );

		if ( defined( 'SITECOOKIEPATH' ) ) {
			$site_path = SITECOOKIEPATH ? SITECOOKIEPATH : '/';

			if ( $site_path !== $options['path'] ) {
				$site_options         = $options;
				$site_options['path'] = $site_path;
				\setcookie( self::COOKIE_NAME, $value, $site_options );
			}
		}
	}

	/**
	 * Generate unique consent identifier.
	 *
	 * @return string
	 */
	public static function generate_consent_id() {
		try {
			return bin2hex( random_bytes( 16 ) );
		} catch ( \Exception $e ) {
			return uniqid( 'fpconsent', true );
		}
	}

	/**
	 * Get hashed IP.
	 *
	 * @return string
	 */
	public static function get_ip_hash() {
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$salt = function_exists( '\fp_privacy_get_ip_salt' ) ? \fp_privacy_get_ip_salt() : 'fp-privacy-cookie-policy-salt';

		return hash( 'sha256', $ip . '|' . $salt );
	}

	/**
	 * Get user agent.
	 *
	 * @return string
	 */
	public static function get_user_agent() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}
}















