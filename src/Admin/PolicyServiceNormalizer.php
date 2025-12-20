<?php
/**
 * Policy service normalizer.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use function is_array;
use function sanitize_key;
use function sanitize_text_field;

/**
 * Handles normalization of manually configured service entries.
 */
class PolicyServiceNormalizer {
	/**
	 * Normalize a manually configured service entry to match detector output.
	 *
	 * @param array<string, mixed> $entry    Service entry.
	 * @param string               $category Category slug.
	 *
	 * @return array<string, mixed>
	 */
	public static function normalize( $entry, $category ) {
		if ( ! is_array( $entry ) ) {
			return array();
		}

		$key = '';

		if ( isset( $entry['key'] ) && '' !== $entry['key'] ) {
			$key = \sanitize_key( $entry['key'] );
		}

		if ( '' === $key && isset( $entry['name'] ) ) {
			$key = \sanitize_key( $entry['name'] );
		}

		$cookies = self::normalize_cookies( $entry );
		$consent_signals = self::normalize_consent_signals( $entry );

		$name = isset( $entry['name'] ) ? (string) $entry['name'] : '';

		if ( '' === $key && '' === $name ) {
			return array();
		}

		$service = array(
			'slug'             => $key,
			'name'             => $name,
			'provider'         => isset( $entry['provider'] ) ? (string) $entry['provider'] : '',
			'purpose'          => isset( $entry['purpose'] ) ? (string) $entry['purpose'] : '',
			'policy_url'       => isset( $entry['policy_url'] ) ? (string) $entry['policy_url'] : '',
			'retention'        => isset( $entry['retention'] ) ? (string) $entry['retention'] : '',
			'legal_basis'      => isset( $entry['legal_basis'] ) ? (string) $entry['legal_basis'] : '',
			'data_collected'   => isset( $entry['data_collected'] ) ? (string) $entry['data_collected'] : '',
			'data_transfer'    => isset( $entry['data_transfer'] ) ? (string) $entry['data_transfer'] : '',
			'uses_consent_mode'=> $consent_signals,
			'cookies'          => $cookies,
			'category'         => $category,
			'detected'         => true,
		);

		return $service;
	}

	/**
	 * Normalize cookies array.
	 *
	 * @param array<string, mixed> $entry Service entry.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function normalize_cookies( $entry ) {
		$cookies = array();

		if ( isset( $entry['cookies'] ) && is_array( $entry['cookies'] ) ) {
			foreach ( $entry['cookies'] as $cookie ) {
				if ( ! is_array( $cookie ) ) {
					continue;
				}

				$cookies[] = array(
					'name'        => isset( $cookie['name'] ) ? (string) $cookie['name'] : '',
					'domain'      => isset( $cookie['domain'] ) ? (string) $cookie['domain'] : '',
					'duration'    => isset( $cookie['duration'] ) ? (string) $cookie['duration'] : '',
					'description' => isset( $cookie['description'] ) ? (string) $cookie['description'] : '',
				);
			}
		}

		return $cookies;
	}

	/**
	 * Normalize consent mode signals.
	 *
	 * @param array<string, mixed> $entry Service entry.
	 *
	 * @return array<int, string>
	 */
	private static function normalize_consent_signals( $entry ) {
		$consent_signals = array();

		if ( isset( $entry['uses_consent_mode'] ) && is_array( $entry['uses_consent_mode'] ) ) {
			$allowed_signals = array( 'analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization', 'functionality_storage', 'personalization_storage', 'security_storage' );

			foreach ( $entry['uses_consent_mode'] as $signal_key => $signal_value ) {
				$candidate = '';

				if ( is_string( $signal_key ) && '' !== $signal_key && ! is_numeric( $signal_key ) ) {
					$candidate = sanitize_text_field( $signal_key );

					if ( is_array( $signal_value ) ) {
						$enabled = false;

						foreach ( $signal_value as $flag ) {
							if ( \rest_sanitize_boolean( $flag ) ) {
								$enabled = true;
								break;
							}
						}
					} else {
						$enabled = (bool) \rest_sanitize_boolean( $signal_value );
					}

					if ( ! $enabled ) {
						continue;
					}
				} else {
					$candidate = sanitize_text_field( (string) $signal_value );
				}

				if ( '' === $candidate ) {
					continue;
				}

				if ( in_array( $candidate, $allowed_signals, true ) && ! in_array( $candidate, $consent_signals, true ) ) {
					$consent_signals[] = $candidate;
				}
			}
		}

		return $consent_signals;
	}
}















