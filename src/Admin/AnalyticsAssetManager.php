<?php
/**
 * Analytics asset manager.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;

/**
 * Handles enqueuing of analytics page assets.
 */
class AnalyticsAssetManager {
	/**
	 * Log model.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Constructor.
	 *
	 * @param LogModel $log_model Log model.
	 */
	public function __construct( LogModel $log_model ) {
		$this->log_model = $log_model;
	}

	/**
	 * Enqueue Chart.js
	 *
	 * @param string $hook Hook.
	 *
	 * @return void
	 */
	public function enqueue_chart_assets( $hook ) {
		if ( false === strpos( $hook, 'fp-privacy' ) || false === strpos( $hook, 'analytics' ) ) {
			return;
		}

		$chart_src    = $this->get_chartjs_source();
		$chart_handle = 'fp-privacy-chartjs';
		$deps         = array( 'jquery' );

		if ( $chart_src ) {
			\wp_register_script(
				$chart_handle,
				$chart_src,
				array(),
				FP_PRIVACY_PLUGIN_VERSION,
				true
			);

			\wp_enqueue_script( $chart_handle );
			$deps[] = $chart_handle;
		} else {
			// Add notice only once per request
			if ( ! \has_action( 'admin_notices', array( $this, 'render_chart_missing_notice' ) ) ) {
				\add_action( 'admin_notices', array( $this, 'render_chart_missing_notice' ) );
			}
		}

		\wp_enqueue_script(
			'fp-privacy-analytics',
			FP_PRIVACY_PLUGIN_URL . 'assets/js/analytics.js',
			$deps,
			FP_PRIVACY_PLUGIN_VERSION,
			true
		);

		$analytics_data = AnalyticsDataCalculator::get_analytics_data_for_js( $this->log_model );
		\wp_localize_script( 'fp-privacy-analytics', 'fpPrivacyAnalytics', $analytics_data );
	}

	/**
	 * Determine Chart.js source URL, preferring a locally hosted copy.
	 *
	 * @return string
	 */
	private function get_chartjs_source() {
		$filtered = \apply_filters( 'fp_privacy_chartjs_src', '' );

		if ( \is_string( $filtered ) && '' !== trim( $filtered ) ) {
			return $filtered;
		}

		$local_path = FP_PRIVACY_PLUGIN_PATH . 'assets/js/chart.umd.min.js';

		if ( file_exists( $local_path ) ) {
			return FP_PRIVACY_PLUGIN_URL . 'assets/js/chart.umd.min.js';
		}

		return '';
	}

	/**
	 * Render admin notice when Chart.js is not available.
	 *
	 * @return void
	 */
	public function render_chart_missing_notice() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if we're on the analytics page
		$screen = \get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'fp-privacy' ) || false === strpos( $screen->id, 'analytics' ) ) {
			return;
		}

		// Check if Chart.js is actually missing
		if ( $this->get_chartjs_source() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo \wp_kses_post(
			\__( 'FP Privacy: Chart.js non è stato caricato. Aggiungi una copia locale in <code>assets/js/chart.umd.min.js</code> oppure fornisci un URL consentito tramite il filtro <code>fp_privacy_chartjs_src</code>.', 'fp-privacy' )
		);
		echo '</p></div>';
	}
}

