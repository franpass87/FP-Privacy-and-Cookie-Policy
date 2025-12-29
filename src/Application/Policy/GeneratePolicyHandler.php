<?php
/**
 * Generate policy command handler.
 *
 * @package FP\Privacy\Application\Policy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Policy;

use FP\Privacy\Domain\Policy\PolicyRepositoryInterface;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Services\Logger\LoggerInterface;
use FP\Privacy\Utils\View;

/**
 * Handler for generating policy documents.
 */
class GeneratePolicyHandler {
	/**
	 * Policy generator (legacy, will be replaced with PolicyService).
	 *
	 * @var \FP\Privacy\Admin\PolicyGenerator
	 */
	private $generator;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param \FP\Privacy\Admin\PolicyGenerator $generator Policy generator.
	 * @param LoggerInterface                    $logger Logger.
	 */
	public function __construct( \FP\Privacy\Admin\PolicyGenerator $generator, LoggerInterface $logger ) {
		$this->generator = $generator;
		$this->logger    = $logger;
	}

	/**
	 * Generate policy content.
	 *
	 * @param string $type Policy type ('privacy' or 'cookie').
	 * @param string $lang Language code.
	 * @return string Generated policy content.
	 */
	public function handle( string $type, string $lang = 'en' ): string {
		try {
			if ( 'privacy' === $type ) {
				return $this->generator->generate_privacy_policy( $lang );
			} elseif ( 'cookie' === $type ) {
				return $this->generator->generate_cookie_policy( $lang );
			}

			$this->logger->warning(
				'Unknown policy type',
				array(
					'type' => $type,
					'lang' => $lang,
				)
			);

			return '';
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Failed to generate policy',
				array(
					'type'  => $type,
					'lang'  => $lang,
					'error' => $e->getMessage(),
				)
			);

			return '';
		}
	}
}













