<?php
/**
 * Update policy command handler.
 *
 * @package FP\Privacy\Application\Policy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Policy;

use FP\Privacy\Domain\Policy\PolicyRepositoryInterface;
use FP\Privacy\Domain\Policy\PolicyService;
use FP\Privacy\Services\Logger\LoggerInterface;
use FP\Privacy\Services\Validation\ValidatorInterface;
use FP\Privacy\Services\Sanitization\SanitizerInterface;

/**
 * Handler for updating policy content.
 */
class UpdatePolicyHandler {
	/**
	 * Policy service (domain layer).
	 *
	 * @var PolicyService
	 */
	private $service;

	/**
	 * Policy repository.
	 *
	 * @var PolicyRepositoryInterface
	 */
	private $repository;

	/**
	 * Validator.
	 *
	 * @var ValidatorInterface
	 */
	private $validator;

	/**
	 * Sanitizer.
	 *
	 * @var SanitizerInterface
	 */
	private $sanitizer;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param PolicyService            $service Policy service.
	 * @param PolicyRepositoryInterface $repository Policy repository.
	 * @param ValidatorInterface       $validator Validator.
	 * @param SanitizerInterface       $sanitizer Sanitizer.
	 * @param LoggerInterface          $logger Logger.
	 */
	public function __construct(
		PolicyService $service,
		PolicyRepositoryInterface $repository,
		ValidatorInterface $validator,
		SanitizerInterface $sanitizer,
		LoggerInterface $logger
	) {
		$this->service    = $service;
		$this->repository = $repository;
		$this->validator  = $validator;
		$this->sanitizer  = $sanitizer;
		$this->logger     = $logger;
	}

	/**
	 * Handle policy update.
	 *
	 * @param string $type Policy type ('privacy' or 'cookie').
	 * @param string $content Policy content.
	 * @param string $lang Language code.
	 * @return bool True on success.
	 */
	public function handle( string $type, string $content, string $lang = 'en' ): bool {
		try {
			// Validate policy type.
			if ( ! in_array( $type, array( 'privacy', 'cookie' ), true ) ) {
				$this->logger->warning(
					'Invalid policy type',
					array(
						'type' => $type,
					)
				);
				return false;
			}

			// Sanitize content.
			$sanitized_content = $this->sanitizer->sanitize( 'policy_content', $content );

			// Validate content using domain service.
			if ( ! $this->service->validateContent( $sanitized_content ) ) {
				$this->logger->warning(
					'Policy content validation failed',
					array(
						'type'    => $type,
						'lang'    => $lang,
						'length'  => strlen( $sanitized_content ),
					)
				);
				return false;
			}

			// Save via domain service (which uses repository).
			$result = $this->service->saveContent( $type, $sanitized_content, $lang );

			if ( $result ) {
				$this->logger->info(
					'Policy updated',
					array(
						'type' => $type,
						'lang' => $lang,
					)
				);
			} else {
				$this->logger->error(
					'Failed to update policy',
					array(
						'type' => $type,
						'lang' => $lang,
					)
				);
			}

			return $result;
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Exception while updating policy',
				array(
					'error' => $e->getMessage(),
					'type'  => $type,
					'lang'  => $lang,
				)
			);
			return false;
		}
	}
}













