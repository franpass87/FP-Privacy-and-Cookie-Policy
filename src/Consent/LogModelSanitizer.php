<?php
/**
 * Consent log model data sanitizer.
 *
 * @package FP\Privacy\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Consent;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Handles data sanitization for consent log entries.
 */
class LogModelSanitizer {
	/**
	 * Normalize states data.
	 *
	 * @param mixed $states States data.
	 *
	 * @return array<string, mixed>
	 */
	public static function normalize_states( $states ) {
		return self::ensure_states( $states );
	}

	/**
	 * Ensure states is an array.
	 *
	 * @param mixed $states States data.
	 *
	 * @return array<string, mixed>
	 */
	public static function ensure_states( $states ) {
		if ( \is_array( $states ) ) {
			return $states;
		}

		if ( \is_string( $states ) ) {
			$decoded = \json_decode( $states, true );
			if ( \is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return array();
	}

	/**
	 * Sanitize datetime string.
	 *
	 * @param string $date Date string.
	 *
	 * @return string
	 */
	public static function sanitize_datetime( $date ) {
		try {
			$dt = new DateTimeImmutable( $date, new DateTimeZone( 'UTC' ) );
			return $dt->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return \gmdate( 'Y-m-d H:i:s' );
		}
	}

	/**
	 * Sanitize insert data.
	 *
	 * @param array<string, mixed> $data Raw data.
	 *
	 * @return array<string, mixed>
	 */
	public static function sanitize_insert_data( array $data ) {
		$defaults = array(
			'consent_id' => '',
			'event'      => 'consent',
			'states'     => '{}',
			'ip_hash'    => '',
			'ua'         => '',
			'lang'       => '',
			'rev'        => 1,
			'created_at' => \current_time( 'mysql', true ),
		);

		$data = \wp_parse_args( $data, $defaults );

		$consent_id = \substr( \sanitize_text_field( $data['consent_id'] ), 0, 64 );
		$encoded    = \wp_json_encode( self::ensure_states( $data['states'] ) );

		if ( false === $encoded ) {
			$encoded = '{}';
		}

		return array(
			'consent_id' => $consent_id,
			'event'      => in_array( $data['event'], array( 'accept_all', 'reject_all', 'consent', 'reset', 'revision_bump', 'consent_revoked', 'consent_withdrawn' ), true ) ? $data['event'] : 'consent',
			'states'     => $encoded,
			'ip_hash'    => substr( \sanitize_text_field( $data['ip_hash'] ), 0, 64 ),
			'ua'         => substr( \sanitize_text_field( $data['ua'] ), 0, 255 ),
			'lang'       => substr( \sanitize_text_field( $data['lang'] ), 0, 16 ),
			'rev'        => (int) $data['rev'],
			'created_at' => self::sanitize_datetime( $data['created_at'] ),
		);
	}
}















