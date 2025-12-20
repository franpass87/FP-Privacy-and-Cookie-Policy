<?php
/**
 * Options sanitizer.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Utils\Validator;

/**
 * Handles sanitization of options data.
 */
class OptionsSanitizer {
	/**
	 * Sanitize stored snapshots.
	 *
	 * @param array<string, mixed> $snapshots Snapshots payload.
	 * @param array<int, string>   $languages Active languages.
	 *
	 * @return array<string, mixed>
	 */
	public static function sanitize_snapshots( array $snapshots, array $languages ) {
		$services = array(
			'detected'     => array(),
			'generated_at' => 0,
		);

		if ( isset( $snapshots['services'] ) && \is_array( $snapshots['services'] ) ) {
			$services['detected']     = isset( $snapshots['services']['detected'] ) && \is_array( $snapshots['services']['detected'] ) ? array_values( $snapshots['services']['detected'] ) : array();
			$services['generated_at'] = (int) ( $snapshots['services']['generated_at'] ?? 0 );
		}

		$policies = array(
			'privacy' => array(),
			'cookie'  => array(),
		);

		foreach ( array( 'privacy', 'cookie' ) as $type ) {
			$entries = array();
			if ( isset( $snapshots['policies'][ $type ] ) && \is_array( $snapshots['policies'][ $type ] ) ) {
				$entries = $snapshots['policies'][ $type ];
			}

			foreach ( $languages as $language ) {
				$language = Validator::locale( $language, $languages[0] );
				$content  = isset( $entries[ $language ]['content'] ) ? \wp_kses_post( $entries[ $language ]['content'] ) : '';
				$generated = isset( $entries[ $language ]['generated_at'] ) ? (int) $entries[ $language ]['generated_at'] : 0;

				$policies[ $type ][ $language ] = array(
					'content'      => $content,
					'generated_at' => $generated,
				);
			}
		}

		return array(
			'services' => $services,
			'policies' => $policies,
		);
	}

	/**
	 * Sanitize detector alert payload.
	 *
	 * @param array<string, mixed> $alert Raw alert payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function sanitize_detector_alert( array $alert ) {
		return array(
			'active'       => Validator::bool( $alert['active'] ?? false ),
			'detected_at'  => Validator::int( $alert['detected_at'] ?? 0, 0, 0 ),
			'last_checked' => Validator::int( $alert['last_checked'] ?? 0, 0, 0 ),
			'added'        => self::sanitize_service_summaries( $alert['added'] ?? array() ),
			'removed'      => self::sanitize_service_summaries( $alert['removed'] ?? array() ),
		);
	}

	/**
	 * Normalize service summaries stored alongside detector alerts.
	 *
	 * @param mixed $services Raw services list.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function sanitize_service_summaries( $services ) {
		if ( ! \is_array( $services ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $services as $service ) {
			if ( ! \is_array( $service ) ) {
				continue;
			}

			$normalized[] = array(
				'slug'     => \sanitize_key( $service['slug'] ?? '' ),
				'name'     => Validator::text( $service['name'] ?? '' ),
				'category' => \sanitize_key( $service['category'] ?? '' ),
				'provider' => Validator::text( $service['provider'] ?? '' ),
			);
		}

		return $normalized;
	}

	/**
	 * Sanitize detector notification settings.
	 *
	 * @param array<string, mixed> $settings Raw settings.
	 * @param array<string, mixed> $defaults Existing defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function sanitize_detector_notifications( array $settings, array $defaults ) {
		$email      = isset( $settings['email'] ) ? Validator::bool( $settings['email'] ) : $defaults['email'];
		$recipients = isset( $settings['recipients'] ) ? $settings['recipients'] : $defaults['recipients'];
		$last_sent  = isset( $settings['last_sent'] ) ? Validator::int( $settings['last_sent'], (int) $defaults['last_sent'], 0 ) : $defaults['last_sent'];

		return array(
			'email'      => $email,
			'recipients' => self::sanitize_email_list( $recipients ),
			'last_sent'  => $last_sent,
		);
	}

	/**
	 * Normalize list of email recipients.
	 *
	 * @param mixed $emails Raw emails.
	 *
	 * @return array<int, string>
	 */
	public static function sanitize_email_list( $emails ) {
		if ( \is_string( $emails ) ) {
			$emails = \preg_split( '/[\s,;]+/', $emails ) ?: array();
		}

		if ( ! \is_array( $emails ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $emails as $email ) {
			$clean = Validator::email( $email );

			if ( '' === $clean || in_array( $clean, $normalized, true ) ) {
				continue;
			}

			$normalized[] = $clean;
		}

		return $normalized;
	}
}















