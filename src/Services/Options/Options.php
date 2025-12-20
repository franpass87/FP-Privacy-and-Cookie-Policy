<?php
/**
 * Options service implementation.
 *
 * @package FP\Privacy\Services\Options
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Options;

/**
 * Options service using WordPress options API.
 */
class Options implements OptionsInterface {
	/**
	 * Option key prefix.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Cached options.
	 *
	 * @var array<string, mixed>|null
	 */
	private $cached = null;

	/**
	 * Constructor.
	 *
	 * @param string $prefix Option key prefix.
	 */
	public function __construct( string $prefix = 'fp_privacy_' ) {
		$this->prefix = $prefix;
	}

	/**
	 * Get an option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Option value or default.
	 */
	public function get( string $key, $default = null ) {
		$full_key = $this->prefix . $key;
		$value    = get_option( $full_key, $default );
		return $value;
	}

	/**
	 * Set an option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Value to store.
	 * @return bool True on success.
	 */
	public function set( string $key, $value ): bool {
		$full_key = $this->prefix . $key;
		$result   = update_option( $full_key, $value );
		$this->cached = null; // Clear cache.
		return $result;
	}

	/**
	 * Delete an option.
	 *
	 * @param string $key Option key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool {
		$full_key = $this->prefix . $key;
		$result   = delete_option( $full_key );
		$this->cached = null; // Clear cache.
		return $result;
	}

	/**
	 * Get all options.
	 *
	 * @return array<string, mixed> All options.
	 */
	public function all(): array {
		if ( null !== $this->cached ) {
			return $this->cached;
		}

		// Get all options with our prefix.
		global $wpdb;
		$options = array();

		if ( isset( $wpdb ) ) {
			$prefix = $wpdb->esc_like( $this->prefix ) . '%';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
					$prefix
				),
				ARRAY_A
			);

			foreach ( $results as $row ) {
				$key = str_replace( $this->prefix, '', $row['option_name'] );
				$options[ $key ] = maybe_unserialize( $row['option_value'] );
			}
		}

		$this->cached = $options;
		return $options;
	}
}










