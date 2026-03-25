<?php
/**
 * Analytics renderer.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;

/**
 * Handles rendering of analytics page.
 */
class AnalyticsRenderer {
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
	 * Render analytics page.
	 *
	 * @return void
	 */
	public function render() {
		$stats  = AnalyticsDataCalculator::calculate_stats( $this->log_model );
		$summary = $this->log_model->summary_last_30_days();
		$total   = $this->log_model->count();

		?>
		<div class="wrap fp-privacy-analytics-page fp-privacy-admin-page">
			<h1 class="screen-reader-text"><?php \esc_html_e( 'Analytics consensi', 'fp-privacy' ); ?></h1>
			<?php
			AdminHeader::render(
				'dashicons-chart-area',
				\__( 'Analytics', 'fp-privacy' ),
				\__( 'Trend dei consensi cookie e statistiche d’uso.', 'fp-privacy' )
			);
			AdminSubnav::maybe_render( 'fp-privacy-analytics' );
			?>

			<p class="description fp-privacy-analytics-intro"><?php \esc_html_e( 'Riepilogo numerico, grafici e ultimi eventi registrati nel log consensi.', 'fp-privacy' ); ?></p>

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
					<div class="stat-label"><?php \esc_html_e( 'Preferenze personalizzate', 'fp-privacy' ); ?></div>
				</div>

				<?php if ( isset( $stats['total_revocations'] ) && $stats['total_revocations'] > 0 ) : ?>
				<div class="fp-privacy-stat-card">
					<div class="stat-icon">🔄</div>
					<div class="stat-value"><?php echo \number_format_i18n( $stats['total_revocations'] ); ?></div>
					<div class="stat-label"><?php \esc_html_e( 'Revoche Consenso', 'fp-privacy' ); ?></div>
				</div>

				<div class="fp-privacy-stat-card">
					<div class="stat-icon">📉</div>
					<div class="stat-value"><?php echo \number_format_i18n( $stats['revocation_rate'], 1 ); ?>%</div>
					<div class="stat-label"><?php \esc_html_e( 'Tasso Revoca', 'fp-privacy' ); ?></div>
				</div>
				<?php endif; ?>
			</div>

			<!-- Charts Row 1 -->
			<div class="fp-privacy-charts-row">
				<div class="fp-privacy-chart-card">
					<h3><?php \esc_html_e( '📈 Trend Consensi (Ultimi 30 Giorni)', 'fp-privacy' ); ?></h3>
					<canvas id="fp-consent-trend-chart"></canvas>
				</div>

				<div class="fp-privacy-chart-card">
					<h3><?php \esc_html_e( '🥧 Ripartizione per tipo', 'fp-privacy' ); ?></h3>
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

			<div class="fp-privacy-card fp-privacy-card--analytics-detail">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Ultimi 100 eventi di consenso', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<p class="description"><?php \esc_html_e( 'Estratto dal registro: data, tipo evento, categorie attive e lingua.', 'fp-privacy' ); ?></p>
					<?php $this->render_recent_consents_table(); ?>
				</div>
			</div>
		</div>
		<?php
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
		<table class="wp-list-table widefat fixed striped fp-privacy-table">
			<thead>
				<tr>
					<th><?php \esc_html_e( 'Data', 'fp-privacy' ); ?></th>
					<th><?php \esc_html_e( 'Evento', 'fp-privacy' ); ?></th>
					<th><?php \esc_html_e( 'Categoria', 'fp-privacy' ); ?></th>
					<th><?php \esc_html_e( 'Lingua', 'fp-privacy' ); ?></th>
					<th><?php \esc_html_e( 'Revisione', 'fp-privacy' ); ?></th>
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
						} elseif ( 'consent_revoked' === $entry['event'] ) {
							$event_label = '🔄 Consenso Revocato';
						} elseif ( 'consent_withdrawn' === $entry['event'] ) {
							$event_label = '🔄 Consenso Ritirato';
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















