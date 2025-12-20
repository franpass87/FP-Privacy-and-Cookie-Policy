<?php
/**
 * Validator implementation.
 *
 * @package FP\Privacy\Services\Validation
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Validation;

/**
 * Validator implementation consolidating existing validators.
 */
class Validator implements ValidatorInterface {
	/**
	 * Validate data against rules.
	 *
	 * @param array<string, mixed> $data Data to validate.
	 * @param array<string, array<string, mixed>> $rules Validation rules.
	 * @return ValidationResult Validation result.
	 */
	public function validate( array $data, array $rules ): ValidationResult {
		$errors = array();

		foreach ( $rules as $field => $field_rules ) {
			$value = $data[ $field ] ?? null;

			if ( ! $this->validateField( $field, $value, $field_rules ) ) {
				$errors[ $field ] = $this->getErrorMessage( $field, $field_rules );
			}
		}

		return new ValidationResult( empty( $errors ), $errors );
	}

	/**
	 * Validate a single field.
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Field value.
	 * @param array<string, mixed> $rules Validation rules.
	 * @return bool True if valid.
	 */
	public function validateField( string $field, $value, array $rules ): bool {
		// Required check.
		if ( isset( $rules['required'] ) && $rules['required'] && ( null === $value || '' === $value ) ) {
			return false;
		}

		// Skip other validations if value is empty and not required.
		if ( empty( $value ) && ( ! isset( $rules['required'] ) || ! $rules['required'] ) ) {
			return true;
		}

		// Type checks.
		if ( isset( $rules['type'] ) ) {
			$type = $rules['type'];
			if ( 'string' === $type && ! is_string( $value ) ) {
				return false;
			}
			if ( 'int' === $type || 'integer' === $type ) {
				if ( ! is_numeric( $value ) ) {
					return false;
				}
			}
			if ( 'bool' === $type || 'boolean' === $type && ! is_bool( $value ) ) {
				return false;
			}
			if ( 'array' === $type && ! is_array( $value ) ) {
				return false;
			}
		}

		// Min/Max length for strings.
		if ( is_string( $value ) ) {
			if ( isset( $rules['min_length'] ) && strlen( $value ) < $rules['min_length'] ) {
				return false;
			}
			if ( isset( $rules['max_length'] ) && strlen( $value ) > $rules['max_length'] ) {
				return false;
			}
		}

		// Custom validation function.
		if ( isset( $rules['validate'] ) && is_callable( $rules['validate'] ) ) {
			return (bool) call_user_func( $rules['validate'], $value );
		}

		return true;
	}

	/**
	 * Get error message for field.
	 *
	 * @param string $field Field name.
	 * @param array<string, mixed> $rules Rules.
	 * @return string Error message.
	 */
	private function getErrorMessage( string $field, array $rules ): string {
		if ( isset( $rules['message'] ) ) {
			return $rules['message'];
		}

		return sprintf( 'Validation failed for field: %s', $field );
	}
}










