<?php
/**
 * Export consent handler.
 *
 * @package FP\Privacy\Application\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Consent;

use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Services\Logger\LoggerInterface;

/**
 * Handler for exporting consent data (GDPR compliance).
 */
class ExportConsentHandler {
	/**
	 * Consent repository.
	 *
	 * @var ConsentRepositoryInterface
	 */
	private $repository;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param ConsentRepositoryInterface $repository Consent repository.
	 * @param LoggerInterface            $logger Logger.
	 */
	public function __construct(
		ConsentRepositoryInterface $repository,
		LoggerInterface $logger
	) {
		$this->repository = $repository;
		$this->logger     = $logger;
	}

	/**
	 * Export consent data for a user email.
	 *
	 * @param string $email User email.
	 * @return array<string, mixed> Export data.
	 */
	public function handle( string $email ): array {
		try {
			// Find consent records by email (via consent_id stored in user meta).
			$consent_ids = $this->getConsentIdsForEmail( $email );
			
			if ( empty( $consent_ids ) ) {
				return array(
					'data' => array(),
					'done' => true,
				);
			}

			$export_data = array();
			foreach ( $consent_ids as $consent_id ) {
				$records = $this->repository->findMany( array( 'consent_id' => $consent_id ) );
				foreach ( $records as $record ) {
					$export_data[] = array(
						'name'  => 'Consent Decision',
						'value' => $this->formatConsentRecord( $record ),
					);
				}
			}

			$this->logger->info(
				'Consent data exported',
				array(
					'email'      => $email,
					'records'    => count( $export_data ),
					'consent_ids' => $consent_ids,
				)
			);

			return array(
				'data' => $export_data,
				'done' => true,
			);
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Failed to export consent data',
				array(
					'error' => $e->getMessage(),
					'email' => $email,
				)
			);

			return array(
				'data' => array(),
				'done' => true,
			);
		}
	}

	/**
	 * Get consent IDs for an email address.
	 *
	 * @param string $email Email address.
	 * @return array<string> Consent IDs.
	 */
	private function getConsentIdsForEmail( string $email ): array {
		// Use WordPress filter to get consent IDs (allows other plugins to extend).
		$ids = apply_filters( 'fp_privacy_consent_ids_for_email', array(), $email );

		// Also check user meta if user exists.
		if ( function_exists( 'get_user_by' ) && is_email( $email ) ) {
			$user = get_user_by( 'email', $email );
			if ( $user && isset( $user->ID ) ) {
				$stored = get_user_meta( (int) $user->ID, 'fp_consent_ids', true );
				if ( is_array( $stored ) ) {
					foreach ( $stored as $candidate ) {
						$candidate = substr( (string) $candidate, 0, 64 );
						if ( '' !== $candidate ) {
							$ids[] = $candidate;
						}
					}
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Format consent record for export.
	 *
	 * @param array<string, mixed> $record Consent record.
	 * @return string Formatted string.
	 */
	private function formatConsentRecord( array $record ): string {
		$event  = isset( $record['event'] ) ? $record['event'] : 'unknown';
		$states = isset( $record['states'] ) && is_array( $record['states'] ) ? $record['states'] : array();
		$date   = isset( $record['created_at'] ) ? $record['created_at'] : '';
		$lang   = isset( $record['lang'] ) ? $record['lang'] : '';

		$formatted = sprintf(
			'Event: %s | Date: %s | Language: %s | Categories: %s',
			$event,
			$date,
			$lang,
			implode( ', ', array_keys( $states ) )
		);

		return $formatted;
	}
}
