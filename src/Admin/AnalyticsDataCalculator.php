<?php
/**
 * Analytics data calculator.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;

/**
 * Handles calculation of analytics statistics.
 */
class AnalyticsDataCalculator {
	/**
	 * Calculate statistics
	 *
	 * @param LogModel $log_model Log model.
	 *
	 * @return array<string, mixed>
	 */
	public static function calculate_stats( LogModel $log_model ) {
		// Total consents
		$total = $log_model->count();

		// Last 30 days summary
		$summary = $log_model->summary_last_30_days();

		// Accept rate
		$accept_all = isset( $summary['accept_all'] ) ? (int) $summary['accept_all'] : 0;
		$reject_all = isset( $summary['reject_all'] ) ? (int) $summary['reject_all'] : 0;
		$custom     = isset( $summary['consent'] ) ? (int) $summary['consent'] : 0;
		$revoked    = isset( $summary['consent_revoked'] ) ? (int) $summary['consent_revoked'] : 0;
		$withdrawn  = isset( $summary['consent_withdrawn'] ) ? (int) $summary['consent_withdrawn'] : 0;
		$total_actions = $accept_all + $reject_all + $custom;
		$total_revocations = $revoked + $withdrawn;

		$accept_rate = $total_actions > 0 ? ( $accept_all / $total_actions ) * 100 : 0;
		$revocation_rate = $total_actions > 0 ? ( $total_revocations / $total_actions ) * 100 : 0;

		return array(
			'total'           => $total,
			'accept_all'      => $accept_all,
			'reject_all'      => $reject_all,
			'custom'          => $custom,
			'revoked'         => $revoked,
			'withdrawn'       => $withdrawn,
			'total_revocations' => $total_revocations,
			'accept_rate'     => $accept_rate,
			'revocation_rate' => $revocation_rate,
		);
	}

	/**
	 * Get analytics data for JavaScript
	 *
	 * @param LogModel $log_model Log model.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_analytics_data_for_js( LogModel $log_model ) {
		global $wpdb;
		$table = $log_model->get_table();

		// Trend ultimi 30 giorni (per giorno)
		$trend_query = "
			SELECT DATE(created_at) as date, COUNT(*) as count, event
			FROM {$table}
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY DATE(created_at), event
			ORDER BY date ASC
		";

		$trend_results = $wpdb->get_results( $trend_query, ARRAY_A );

		// Breakdown per tipo
		$type_summary = $log_model->summary_last_30_days();

		// Consensi per categoria (analizzando states)
		$categories_query = $wpdb->prepare(
			"SELECT states FROM {$table} 
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			LIMIT %d",
			1000
		);

		$states_results = $wpdb->get_results( $categories_query, ARRAY_A );

		$categories_stats = array(
			'necessary'   => 0,
			'analytics'   => 0,
			'statistics'  => 0,
			'marketing'   => 0,
			'preferences' => 0,
		);

		foreach ( $states_results as $row ) {
			$states = \json_decode( $row['states'], true );
			if ( \is_array( $states ) ) {
				foreach ( $states as $cat => $value ) {
					if ( true === $value && isset( $categories_stats[ $cat ] ) ) {
						$categories_stats[ $cat ]++;
					}
				}
			}
		}

		// Lingue
		$lang_query = $wpdb->prepare(
			"SELECT lang, COUNT(*) as count
			FROM {$table}
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY lang
			ORDER BY count DESC
			LIMIT %d",
			10
		);

		$lang_results = $wpdb->get_results( $lang_query, ARRAY_A );

		return array(
			'trend'      => $trend_results,
			'types'      => $type_summary,
			'categories' => $categories_stats,
			'languages'  => $lang_results,
		);
	}
}















