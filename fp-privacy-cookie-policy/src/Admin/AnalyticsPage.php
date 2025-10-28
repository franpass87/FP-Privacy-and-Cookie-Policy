<?php
/**
 * Analytics Dashboard Page
 * QUICK WIN #3: Dashboard con grafici Chart.js
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;

/**
 * Analytics dashboard con metriche e grafici consent rate
 */
class AnalyticsPage {
	/**
	 * Log model.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param LogModel $log_model Log model.
	 * @param Options  $options   Options.
	 */
	public function __construct( LogModel $log_model, Options $options ) {
		$this->log_model = $log_model;
		$this->options   = $options;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'fp_privacy_admin_page_analytics', array( $this, 'render' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_chart_assets' ) );
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

		// Chart.js CDN
		\wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		\wp_enqueue_script(
			'fp-privacy-analytics',
			FP_PRIVACY_PLUGIN_URL . 'assets/js/analytics.js',
			array( 'jquery', 'chartjs' ),
			FP_PRIVACY_PLUGIN_VERSION,
			true
		);

		// Pass dati ai grafici
		\wp_localize_script(
			'fp-privacy-analytics',
			'fpPrivacyAnalytics',
			$this->get_analytics_data()
		);
	}

	/**
	 * Render analytics page
	 *
	 * @return void
	 */
	public function render() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'fp-privacy' ) );
		}

		$stats = $this->calculate_stats();
		$summary = $this->log_model->summary_last_30_days();
		$total = $this->log_model->count();

		?>
		<div class="wrap fp-privacy-analytics-page">
			<h1><?php \esc_html_e( '📊 Analytics Consensi', 'fp-privacy' ); ?></h1>
			<p class="description"><?php \esc_html_e( 'Analisi dettagliata dei consensi cookie e statistiche di utilizzo', 'fp-privacy' ); ?></p>

			<!-- Summary Cards -->
			<div class="fp-privacy-stats-grid">
				<div class="fp-privacy-stat-card">
					<div class="stat-icon">📊</div>
					<div class="stat-value"><?php echo \number_format_i18n( $total ); ?></div>
					<div class="stat-label"><?php \esc_html_e( 'Consensi Totali', 'fp-privacy' ); ?></div>
				</div>

				<div class="fp-privacy-stat-card">
					<div class="stat-icon">✅</div>
					<div class="stat-value"><?php echo isset( $summary['accept_all'] ) ? \number_format_i18n( $summary['accept_all'] ) : '0'; ?></div>
					<div class="stat-label"><?php \esc_html_e( 'Accetta Tutti', 'fp-privacy' ); ?></div>
				</div>

				<div class="fp-privacy-stat-card">
					<div class="stat-icon">❌</div>
					<div class="stat-value"><?php echo isset( $summary['reject_all'] ) ? \number_format_i18n( $summary['reject_all'] ) : '0'; ?></div>
					<div class="stat-label"><?php \esc_html_e( 'Rifiuta Tutti', 'fp-privacy' ); ?></div>
				</div>

				<div class="fp-privacy-stat-card">
					<div class="stat-icon">⚙️</div>
					<div class="stat-value"><?php echo isset( $summary['consent'] ) ? \number_format_i18n( $summary['consent'] ) : '0'; ?></div>
					<div class="stat-label"><?php \esc_html_e( 'Preferenze Custom', 'fp-privacy' ); ?></div>
				</div>
			</div>

			<!-- Charts Row 1 -->
			<div class="fp-privacy-charts-row">
				<div class="fp-privacy-chart-card">
					<h3><?php \esc_html_e( '📈 Trend Consensi (Ultimi 30 Giorni)', 'fp-privacy' ); ?></h3>
					<canvas id="fp-consent-trend-chart"></canvas>
				</div>

				<div class="fp-privacy-chart-card">
					<h3><?php \esc_html_e( '🥧 Breakdown per Tipo', 'fp-privacy' ); ?></h3>
					<canvas id="fp-consent-type-chart"></canvas>
				</div>
			</div>

			<!-- Charts Row 2 -->
			<div class="fp-privacy-charts-row">
				<div class="fp-privacy-chart-card">
					<h3><?php \esc_html_e( '📊 Consensi per Categoria', 'fp-privacy' ); ?></h3>
					<canvas id="fp-consent-categories-chart"></canvas>
				</div>

				<div class="fp-privacy-chart-card">
					<h3><?php \esc_html_e( '🌍 Lingue Utenti', 'fp-privacy' ); ?></h3>
					<canvas id="fp-consent-lang-chart"></canvas>
				</div>
			</div>

			<!-- Tabella Dettagli -->
			<div class="fp-privacy-details-table">
				<h3><?php \esc_html_e( '📋 Dettagli Ultimi 100 Consensi', 'fp-privacy' ); ?></h3>
				<?php $this->render_recent_consents_table(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Calculate statistics
	 *
	 * @return array
	 */
	private function calculate_stats() {
		global $wpdb;
		$table = $this->log_model->get_table();

		// Total consents
		$total = $this->log_model->count();

		// Last 30 days summary
		$summary = $this->log_model->summary_last_30_days();

		// Accept rate
		$accept_all = isset( $summary['accept_all'] ) ? (int) $summary['accept_all'] : 0;
		$reject_all = isset( $summary['reject_all'] ) ? (int) $summary['reject_all'] : 0;
		$custom = isset( $summary['consent'] ) ? (int) $summary['consent'] : 0;
		$total_actions = $accept_all + $reject_all + $custom;

		$accept_rate = $total_actions > 0 ? ( $accept_all / $total_actions ) * 100 : 0;

		return array(
			'total'       => $total,
			'accept_all'  => $accept_all,
			'reject_all'  => $reject_all,
			'custom'      => $custom,
			'accept_rate' => $accept_rate,
		);
	}

	/**
	 * Get analytics data for JavaScript
	 *
	 * @return array
	 */
	private function get_analytics_data() {
		global $wpdb;
		$table = $this->log_model->get_table();

		// Trend ultimi 30 giorni (per giorno)
		$trend_query = $wpdb->prepare(
			"SELECT DATE(created_at) as date, COUNT(*) as count, event
			FROM {$table}
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY DATE(created_at), event
			ORDER BY date ASC",
			array()
		);
		
		$trend_results = $wpdb->get_results( $trend_query, ARRAY_A );

		// Breakdown per tipo
		$type_summary = $this->log_model->summary_last_30_days();

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

	/**
	 * Render recent consents table
	 *
	 * @return void
	 */
	private function render_recent_consents_table() {
		$recent = $this->log_model->query(
			array(
				'per_page' => 100,
				'paged'    => 1,
			)
		);

		if ( empty( $recent ) ) {
			echo '<p>' . \esc_html__( 'Nessun consenso registrato.', 'fp-privacy' ) . '</p>';
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php \esc_html_e( 'Data', 'fp-privacy' ); ?></th>
					<th><?php \esc_html_e( 'Evento', 'fp-privacy' ); ?></th>
					<th><?php \esc_html_e( 'Categoria', 'fp-privacy' ); ?></th>
					<th><?php \esc_html_e( 'Lingua', 'fp-privacy' ); ?></th>
					<th><?php \esc_html_e( 'Rev', 'fp-privacy' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent as $entry ) : ?>
				<tr>
					<td><?php echo \esc_html( \wp_date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), \strtotime( $entry['created_at'] ) ) ); ?></td>
					<td>
						<?php
						$event_label = $entry['event'];
						$event_class = 'event-' . \esc_attr( $entry['event'] );
						if ( 'accept_all' === $entry['event'] ) {
							$event_label = '✅ Accetta Tutti';
						} elseif ( 'reject_all' === $entry['event'] ) {
							$event_label = '❌ Rifiuta Tutti';
						} elseif ( 'consent' === $entry['event'] ) {
							$event_label = '⚙️ Personalizzato';
						}
						?>
						<span class="fp-privacy-event-badge <?php echo \esc_attr( $event_class ); ?>">
							<?php echo \esc_html( $event_label ); ?>
						</span>
					</td>
					<td>
						<?php
						$states = \json_decode( $entry['states'], true );
						if ( \is_array( $states ) ) {
							$enabled = array_keys( array_filter( $states ) );
							echo \esc_html( \implode( ', ', $enabled ) );
						}
						?>
					</td>
					<td><?php echo \esc_html( $entry['lang'] ); ?></td>
					<td><?php echo \esc_html( $entry['rev'] ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

