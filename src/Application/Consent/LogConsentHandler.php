<?php
/**
 * Log consent command handler.
 *
 * @package FP\Privacy\Application\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Consent;

use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Domain\Consent\ConsentService;
use FP\Privacy\Services\Logger\LoggerInterface;

/**
 * Handler for logging consent decisions.
 */
class LogConsentHandler {
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
	 * Handle consent logging.
	 *
	 * @param array<string, mixed> $data Consent data.
	 * @return int|false Consent ID or false on failure.
	 */
	public function handle( array $data ) {
		try {
			// Use domain service to process consent (includes validation).
			$id = $this->service->processConsent( $data );
			if ( false !== $id ) {
				$this->logger->info(
					'Consent logged',
					array(
						'consent_id' => $id,
						'data'       => $data,
					)
				);
			}
			return $id;
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Failed to log consent',
				array(
					'error' => $e->getMessage(),
					'data'  => $data,
				)
			);
			return false;
		}
	}
}

