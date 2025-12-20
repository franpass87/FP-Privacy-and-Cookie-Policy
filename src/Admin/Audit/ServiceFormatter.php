<?php
/**
 * Service formatter for audit output.
 *
 * @package FP\Privacy\Admin\Audit
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Audit;

/**
 * Formats services for display in admin notices and emails.
 */
class ServiceFormatter {
	/**
	 * Format services list for the admin notice.
	 *
	 * @param array<int, array<string, string>> $services Services summary.
	 *
	 * @return string
	 */
	public function format_services_list( array $services ) {
		if ( empty( $services ) ) {
			return '';
		}

		$entries = array();
		$slice   = array_slice( $services, 0, 3 );

		foreach ( $slice as $service ) {
			$label = $service['name'] ?: $service['slug'];

			if ( $service['provider'] ) {
				$label .= ' — ' . $service['provider'];
			}

			$entries[] = $label;
		}

		if ( count( $services ) > 3 ) {
			$entries[] = sprintf(
				\__( 'and %s more', 'fp-privacy' ),
				\number_format_i18n( count( $services ) - 3 )
			);
		}

		return implode( ', ', $entries );
	}

	/**
	 * Format services summary for email output.
	 *
	 * @param array<int, array<string, string>> $services Services summary.
	 *
	 * @return string
	 */
	public function format_services_for_email( array $services ) {
		if ( empty( $services ) ) {
			return '';
		}

		$lines = array();

		foreach ( $services as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}

			$label = $service['name'] ?: $service['slug'];

			if ( ! empty( $service['provider'] ) ) {
				$label .= ' — ' . $service['provider'];
			}

			if ( ! empty( $service['category'] ) ) {
				$label .= ' [' . $service['category'] . ']';
			}

			$lines[] = '- ' . $label;
		}

		return implode( "\n", $lines );
	}
}
















