<?php
/**
 * Policy domain service.
 *
 * @package FP\Privacy\Domain\Policy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Policy;

/**
 * Domain service for policy business logic.
 * 
 * This service contains business rules and domain logic for policy management.
 */
class PolicyService {
	/**
	 * Policy repository.
	 *
	 * @var PolicyRepositoryInterface|object
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param PolicyRepositoryInterface $repository Policy repository.
	 */
	public function __construct( PolicyRepositoryInterface $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Validate policy content.
	 *
	 * @param string $content Policy content.
	 * @return bool True if valid.
	 */
	public function validateContent( string $content ): bool {
		// Business rules for policy validation.
		if ( empty( trim( $content ) ) ) {
			return false;
		}

		// Minimum length check.
		if ( strlen( $content ) < 100 ) {
			return false;
		}

		return true;
	}

	/**
	 * Generate policy content.
	 *
	 * @param string $type Policy type ('privacy' or 'cookie').
	 * @param string $lang Language code.
	 * @return string Generated policy content.
	 */
	public function generateContent( string $type, string $lang = 'en' ): string {
		// This will delegate to PolicyGenerator for now.
		// In future, this will contain pure domain logic.
		return '';
	}

	/**
	 * Save policy content.
	 *
	 * @param string $type Policy type.
	 * @param string $content Policy content.
	 * @param string $lang Language code.
	 * @return bool True on success.
	 */
	public function saveContent( string $type, string $content, string $lang = 'en' ): bool {
		if ( ! $this->validateContent( $content ) ) {
			return false;
		}

		return $this->repository->saveContent( $type, $content, $lang );
	}
}

