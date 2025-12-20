<?php
/**
 * Email notifier for integration changes.
 *
 * @package FP\Privacy\Admin\Audit
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Audit;

use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\DetectorAlertManager;

/**
 * Sends email notifications when integration changes are detected.
 */
class EmailNotifier {
	const EMAIL_COOLDOWN = DAY_IN_SECONDS;

	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Detector alert manager.
	 *
	 * @var DetectorAlertManager
	 */
	private $alert_manager;

	/**
	 * Service formatter.
	 *
	 * @var ServiceFormatter
	 */
	private $formatter;

	/**
	 * Constructor.
	 *
	 * @param Options            $options      Options handler.
	 * @param DetectorAlertManager $alert_manager Alert manager.
	 * @param ServiceFormatter   $formatter    Service formatter.
	 */
	public function __construct( Options $options, DetectorAlertManager $alert_manager, ServiceFormatter $formatter ) {
		$this->options       = $options;
		$this->alert_manager = $alert_manager;
		$this->formatter     = $formatter;
	}

	/**
	 * Send email notifications when alerts become active.
	 *
	 * @param array<string, mixed> $alert Alert payload.
	 *
	 * @return void
	 */
	public function maybe_send_email_alert( array $alert ) {
		$settings = $this->alert_manager->get_detector_notifications();

		if ( empty( $settings['email'] ) ) {
			return;
		}

		$now       = time();
		$last_sent = isset( $settings['last_sent'] ) ? (int) $settings['last_sent'] : 0;

		if ( $last_sent && ( $now - $last_sent ) < self::EMAIL_COOLDOWN ) {
			return;
		}

		$recipients = $this->get_recipients( $settings );

		if ( empty( $recipients ) ) {
			return;
		}

		$subject = $this->build_subject();
		$message = $this->build_message( $alert );

		if ( \wp_mail( $recipients, $subject, $message ) ) {
			$this->alert_manager->update_detector_notifications(
				array(
					'last_sent' => $now,
				)
			);
		}
	}

	/**
	 * Get email recipients.
	 *
	 * @param array<string, mixed> $settings Notification settings.
	 *
	 * @return array<int, string>
	 */
	private function get_recipients( array $settings ) {
		$recipients = array();

		if ( isset( $settings['recipients'] ) && is_array( $settings['recipients'] ) ) {
			foreach ( $settings['recipients'] as $recipient ) {
				$email = \sanitize_email( $recipient );

				if ( '' === $email || in_array( $email, $recipients, true ) ) {
					continue;
				}

				$recipients[] = $email;
			}
		}

		if ( empty( $recipients ) ) {
			$admin_email = \sanitize_email( \get_option( 'admin_email' ) );

			if ( '' !== $admin_email ) {
				$recipients[] = $admin_email;
			}
		}

		return $recipients;
	}

	/**
	 * Build email subject.
	 *
	 * @return string
	 */
	private function build_subject() {
		$site_name = trim( (string) \get_bloginfo( 'name' ) );

		if ( '' === $site_name ) {
			$site_name = \home_url();
		}

		return sprintf( \__( '[%s] Integration changes detected', 'fp-privacy' ), $site_name );
	}

	/**
	 * Build email message.
	 *
	 * @param array<string, mixed> $alert Alert payload.
	 *
	 * @return string
	 */
	private function build_message( array $alert ) {
		$site_name = trim( (string) \get_bloginfo( 'name' ) );

		if ( '' === $site_name ) {
			$site_name = \home_url();
		}

		$lines = array();
		$lines[] = sprintf( \__( 'Integration monitoring detected changes on %s.', 'fp-privacy' ), $site_name );

		if ( ! empty( $alert['detected_at'] ) ) {
			$lines[] = sprintf(
				\__( 'Detected at: %s', 'fp-privacy' ),
				\wp_date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), (int) $alert['detected_at'] )
			);
		}

		if ( ! empty( $alert['added'] ) ) {
			$added_block = $this->formatter->format_services_for_email( $alert['added'] );

			if ( $added_block ) {
				$lines[] = \__( 'New services:', 'fp-privacy' );
				$lines[] = $added_block;
			}
		}

		if ( ! empty( $alert['removed'] ) ) {
			$removed_block = $this->formatter->format_services_for_email( $alert['removed'] );

			if ( $removed_block ) {
				$lines[] = \__( 'Removed services:', 'fp-privacy' );
				$lines[] = $removed_block;
			}
		}

		$lines[] = sprintf( \__( 'Policy editor: %s', 'fp-privacy' ), \admin_url( 'admin.php?page=fp-privacy-policy-editor' ) );
		$lines[] = sprintf( \__( 'Tools dashboard: %s', 'fp-privacy' ), \admin_url( 'admin.php?page=fp-privacy-tools' ) );

		return implode( "\n\n", array_filter( $lines ) );
	}
}
















