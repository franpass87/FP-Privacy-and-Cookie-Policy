<?php
/**
 * Get consent query handler.
 *
 * @package FP\Privacy\Application\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Consent;

use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;

/**
 * Query handler for retrieving consent records.
 */
class GetConsentQuery {
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
	 * Get consent by ID.
	 *
	 * @param int $id Consent ID.
	 * @return array<string, mixed>|null Consent data or null.
	 */
	public function byId( int $id ): ?array {
		return $this->repository->find( $id );
	}

	/**
	 * Get consent records.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, array<string, mixed>> Consent records.
	 */
	public function many( array $args = array() ): array {
		return $this->repository->findMany( $args );
	}

	/**
	 * Count consent records.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return int Count.
	 */
	public function count( array $args = array() ): int {
		return $this->repository->count( $args );
	}
}













