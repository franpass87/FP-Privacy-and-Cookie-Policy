<?php
/**
 * Plugin filters.
 *
 * @package FP\Privacy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy;

use FP\Privacy\Utils\Options;

/**
 * Handles plugin filter registration.
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 *             The functionality has been migrated to CoreServiceProvider::registerPluginFilters().
 *             This class is kept only for backward compatibility with the old Plugin class.
 *             New code should use the Kernel and service providers instead.
 */
class PluginFilters {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register all plugin filters.
	 *
	 * @return void
	 */
	public function register() {
		// Enable WordPress privacy tools integration by default.
		\add_filter( 'fp_privacy_enable_privacy_tools', '__return_true', 10, 2 );

		// Wire GPC enablement to saved option.
		$options_ref = $this->options;
		\add_filter(
			'fp_privacy_enable_gpc',
			static function ( $enabled ) use ( $options_ref ) {
				$value = $options_ref ? (bool) $options_ref->get( 'gpc_enabled', false ) : false;
				return (bool) $value;
			},
			10,
			1
		);

		// Map email -> consent_ids using user meta recorded at consent time.
		\add_filter(
			'fp_privacy_consent_ids_for_email',
			static function ( $ids, $email ) {
				if ( ! \is_array( $ids ) ) {
					$ids = array();
				}

				if ( ! \is_email( $email ) || ! function_exists( '\get_user_by' ) ) {
					return $ids;
				}

				$user = \get_user_by( 'email', $email );

				if ( ! $user || ! isset( $user->ID ) ) {
					return $ids;
				}

				if ( function_exists( '\get_user_meta' ) ) {
					$stored = \get_user_meta( (int) $user->ID, 'fp_consent_ids', true );

					if ( \is_array( $stored ) ) {
						foreach ( $stored as $candidate ) {
							$candidate = \substr( (string) $candidate, 0, 64 );

							if ( '' !== $candidate ) {
								$ids[] = $candidate;
							}
						}
					}
				}

				return array_values( array_unique( $ids ) );
			},
			10,
			2
		);
	}
}







