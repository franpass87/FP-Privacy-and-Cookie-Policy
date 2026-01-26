<?php
/**
 * Consent domain service.
 *
 * @package FP\Privacy\Domain\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Consent;

/**
 * Domain service for consent business logic.
 * 
 * This service contains business rules and domain logic for consent management.
 */
class ConsentService {
	/**
	 * Consent repository.
	 *
	 * @var ConsentRepositoryInterface
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param ConsentRepositoryInterface $repository Consent repository.
	 */
	public function __construct( ConsentRepositoryInterface $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Validate consent data.
	 *
	 * @param array<string, mixed> $data Consent data.
	 * @return bool True if valid.
	 */
	public function validate( array $data ): bool {
		// Business rules for consent validation.
		if ( empty( $data['consent_id'] ) ) {
			return false;
		}

		if ( ! isset( $data['event'] ) || ! in_array( $data['event'], array( 'accept', 'reject', 'update' ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Process consent decision.
	 *
	 * @param array<string, mixed> $data Consent data.
	 * @return int|false Consent ID or false on failure.
	 */
	public function processConsent( array $data ) {
		if ( ! $this->validate( $data ) ) {
			return false;
		}

		return $this->repository->create( $data );
	}
}














