<?php
/**
 * Update settings command handler.
 *
 * @package FP\Privacy\Application\Settings
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Settings;

use FP\Privacy\Infrastructure\Options\OptionsRepositoryInterface;
use FP\Privacy\Services\Logger\LoggerInterface;
use FP\Privacy\Services\Validation\ValidatorInterface;
use FP\Privacy\Services\Sanitization\SanitizerInterface;

/**
 * Handler for updating plugin settings.
 */
class UpdateSettingsHandler {
	/**
	 * Options repository.
	 *
	 * @var OptionsRepositoryInterface
	 */
	private $options;

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
	 * @param OptionsRepositoryInterface $options Options repository.
	 * @param ValidatorInterface         $validator Validator.
	 * @param SanitizerInterface         $sanitizer Sanitizer.
	 * @param LoggerInterface            $logger Logger.
	 */
	public function __construct(
		OptionsRepositoryInterface $options,
		ValidatorInterface $validator,
		SanitizerInterface $sanitizer,
		LoggerInterface $logger
	) {
		$this->options   = $options;
		$this->validator = $validator;
		$this->sanitizer = $sanitizer;
		$this->logger    = $logger;
	}

	/**
	 * Handle settings update.
	 *
	 * @param array<string, mixed> $settings Settings data.
	 * @return bool True on success.
	 */
	public function handle( array $settings ): bool {
		try {
			// Validate settings.
			if ( ! $this->validateSettings( $settings ) ) {
				$this->logger->warning( 'Settings validation failed', array( 'settings' => $settings ) );
				return false;
			}

			// Sanitize settings.
			$sanitized = $this->sanitizeSettings( $settings );

			// Save settings.
			foreach ( $sanitized as $key => $value ) {
				$this->options->set( $key, $value );
			}

			$this->logger->info( 'Settings updated', array( 'keys' => array_keys( $sanitized ) ) );

			return true;
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Failed to update settings',
				array(
					'error' => $e->getMessage(),
					'settings' => $settings,
				)
			);
			return false;
		}
	}

	/**
	 * Validate settings.
	 *
	 * @param array<string, mixed> $settings Settings data.
	 * @return bool True if valid.
	 */
	private function validateSettings( array $settings ): bool {
		// Basic validation - can be extended with specific rules.
		return is_array( $settings ) && ! empty( $settings );
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $settings Settings data.
	 * @return array<string, mixed> Sanitized settings.
	 */
	private function sanitizeSettings( array $settings ): array {
		$sanitized = array();
		foreach ( $settings as $key => $value ) {
			$sanitized[ $key ] = $this->sanitizer->sanitize( $key, $value );
		}
		return $sanitized;
	}
}














