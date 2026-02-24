<?php
/**
 * Cleanup consent handler.
 *
 * @package FP\Privacy\Application\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Consent;

use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Services\Logger\LoggerInterface;
use FP\Privacy\Services\Options\OptionsInterface;

/**
 * Handler for cleaning up old consent records.
 */
class CleanupConsentHandler {
	/**
	 * Consent repository.
	 *
	 * @var ConsentRepositoryInterface
	 */
	private $repository;

	/**
	 * Options handler.
	 *
	 * @var OptionsInterface
	 */
	private $options;

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
	 * @param OptionsInterface           $options Options handler.
	 * @param LoggerInterface            $logger Logger.
	 */
	public function __construct(
		ConsentRepositoryInterface $repository,
		OptionsInterface $options,
		LoggerInterface $logger
	) {
		$this->repository = $repository;
		$this->options    = $options;
		$this->logger     = $logger;
	}

	/**
	 * Cleanup old consent records.
	 *
	 * @param int|null $days Retention days (uses option if null).
	 * @return int Number of records deleted.
	 */
	public function handle( ?int $days = null ): int {
		if ( null === $days ) {
			$days = (int) $this->options->get( 'consent_retention_days', \FP\Privacy\Shared\Constants::RETENTION_DAYS_CLEANUP_DEFAULT );
		}

		if ( $days < 1 ) {
			$this->logger->warning( 'Consent cleanup aborted: retention days must be >= 1', array( 'days' => $days ) );
			return 0;
		}

		try {
			$deleted = $this->repository->deleteMany( array( 'days' => $days ) );

			$this->logger->info(
				'Consent cleanup completed',
				array(
					'days'    => $days,
					'deleted' => $deleted,
				)
			);

			return $deleted;
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Consent cleanup failed',
				array(
					'error' => $e->getMessage(),
					'days'  => $days,
				)
			);

			return 0;
		}
	}
}







