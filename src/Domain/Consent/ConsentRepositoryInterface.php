<?php
/**
 * Consent repository interface.
 *
 * @package FP\Privacy\Domain\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Consent;

/**
 * Interface for consent repository implementations.
 */
interface ConsentRepositoryInterface {
	/**
	 * Store a consent record.
	 *
	 * @param array<string, mixed> $data Consent data.
	 * @return int|false Consent ID or false on failure.
	 */
	public function create( array $data );

	/**
	 * Get a consent record by ID.
	 *
	 * @param int $id Consent ID.
	 * @return array<string, mixed>|null Consent data or null if not found.
	 */
	public function find( int $id ): ?array;

	/**
	 * Get consent records matching criteria.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, array<string, mixed>> Consent records.
	 */
	public function findMany( array $args = array() ): array;

	/**
	 * Update a consent record.
	 *
	 * @param int $id Consent ID.
	 * @param array<string, mixed> $data Data to update.
	 * @return bool True on success.
	 */
	public function update( int $id, array $data ): bool;

	/**
	 * Delete a consent record.
	 *
	 * @param int $id Consent ID.
	 * @return bool True on success.
	 */
	public function delete( int $id ): bool;

	/**
	 * Delete consent records matching criteria.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return int Number of records deleted.
	 */
	public function deleteMany( array $args = array() ): int;

	/**
	 * Count consent records matching criteria.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return int Count.
	 */
	public function count( array $args = array() ): int;
}














