<?php
/**
 * Validator interface.
 *
 * @package FP\Privacy\Services\Validation
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Validation;

/**
 * Validation result.
 */
class ValidationResult {
	/**
	 * Whether validation passed.
	 *
	 * @var bool
	 */
	public $valid;

	/**
	 * Validation errors.
	 *
	 * @var array<string, string>
	 */
	public $errors;

	/**
	 * Constructor.
	 *
	 * @param bool $valid Whether valid.
	 * @param array<string, string> $errors Errors.
	 */
	public function __construct( bool $valid = true, array $errors = array() ) {
		$this->valid  = $valid;
		$this->errors = $errors;
	}
}

/**
 * Validator interface.
 */
interface ValidatorInterface {
	/**
	 * Validate data against rules.
	 *
	 * @param array<string, mixed> $data Data to validate.
	 * @param array<string, array<string, mixed>> $rules Validation rules.
	 * @return ValidationResult Validation result.
	 */
	public function validate( array $data, array $rules ): ValidationResult;

	/**
	 * Validate a single field.
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @param array<string, mixed> $rules Validation rules.
	 * @return bool True if valid.
	 */
	public function validateField( string $field, $value, array $rules ): bool;
}










