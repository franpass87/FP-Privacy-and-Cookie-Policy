<?php
/**
 * Revoke consent command handler.
 *
 * @package FP\Privacy\Application\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Consent;

use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Domain\Consent\ConsentService;
use FP\Privacy\Services\Logger\LoggerInterface;
use FP\Privacy\Frontend\ConsentCookieManager;

/**
 * Handler for revoking consent.
 */
class RevokeConsentHandler {
	/**
	 * Consent service (domain layer).
	 *
	 * @var ConsentService
	 */
	private $service;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param ConsentService   $service Consent service.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct( ConsentService $service, LoggerInterface $logger ) {
		$this->service = $service;
		$this->logger  = $logger;
	}

	/**
	 * Handle consent revocation.
	 *
	 * @param string $consent_id Consent identifier (optional).
	 * @param string $lang Language code (optional).
	 *
	 * @return array<string, mixed> Result with success status and consent_id.
	 */
	public function handle( ?string $consent_id = null, ?string $lang = '' ): array {
		try {
			// Get consent ID from cookie if not provided.
			if ( empty( $consent_id ) ) {
				$cookie = ConsentCookieManager::get_cookie_payload();
				$consent_id = $cookie['id'] ?? '';
			}

			if ( empty( $consent_id ) ) {
				return array(
					'success'    => false,
					'error'      => 'no_consent_id',
					'message'    => __( 'No consent identifier found.', 'fp-privacy' ),
					'consent_id' => '',
				);
			}

			// Log revocation event.
			$revocation_data = array(
				'event'      => 'consent_revoked',
				'states'     => array(
					'analytics'   => false,
					'marketing'   => false,
					'functional'  => false,
					'necessary'   => true, // Necessary cookies cannot be revoked.
				),
				'lang'       => $lang ?: 'en_US',
				'consent_id' => $consent_id,
			);

			// Use domain service to process revocation.
			$id = $this->service->processConsent( $revocation_data );

			if ( false !== $id ) {
				$this->logger->info(
					'Consent revoked',
					array(
						'consent_id' => $consent_id,
						'log_id'     => $id,
						'lang'       => $lang,
					)
				);

				return array(
					'success'    => true,
					'consent_id' => $consent_id,
					'log_id'     => $id,
					'message'    => __( 'Consent revoked successfully.', 'fp-privacy' ),
				);
			}

			return array(
				'success'    => false,
				'error'      => 'logging_failed',
				'message'    => __( 'Failed to log revocation.', 'fp-privacy' ),
				'consent_id' => $consent_id,
			);
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Failed to revoke consent',
				array(
					'error'      => $e->getMessage(),
					'consent_id' => $consent_id ?? '',
					'lang'       => $lang,
				)
			);

			return array(
				'success'    => false,
				'error'      => 'exception',
				'message'    => $e->getMessage(),
				'consent_id' => $consent_id ?? '',
			);
		}
	}
}



