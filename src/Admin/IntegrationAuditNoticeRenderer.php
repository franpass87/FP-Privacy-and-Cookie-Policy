<?php
/**
 * Integration audit notice renderer.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Admin\Audit\ServiceFormatter;
use FP\Privacy\Utils\DetectorAlertManager;

use function _n;
use function count;
use function esc_html;
use function esc_html__;
use function esc_html_e;
use function esc_url;
use function number_format_i18n;

/**
 * Handles rendering of integration audit notices.
 */
class IntegrationAuditNoticeRenderer {
	/**
	 * Service formatter.
	 *
	 * @var ServiceFormatter
	 */
	private $formatter;

	/**
	 * Constructor.
	 *
	 * @param ServiceFormatter $formatter Service formatter.
	 */
	public function __construct( ServiceFormatter $formatter ) {
		$this->formatter = $formatter;
	}

	/**
	 * Render the integration change notice when required.
	 *
	 * @param DetectorAlertManager $alert_manager Alert manager.
	 *
	 * @return void
	 */
	public function render_notice( DetectorAlertManager $alert_manager ) {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$alert = $alert_manager->get_detector_alert();

		if ( empty( $alert['active'] ) ) {
			return;
		}

		$added_count   = isset( $alert['added'] ) && is_array( $alert['added'] ) ? count( $alert['added'] ) : 0;
		$removed_count = isset( $alert['removed'] ) && is_array( $alert['removed'] ) ? count( $alert['removed'] ) : 0;
		$summary_parts = array();

		if ( $added_count ) {
			$summary_parts[] = sprintf(
				_n( '%s new service detected', '%s new services detected', $added_count, 'fp-privacy' ),
				number_format_i18n( $added_count )
			);
		}

		if ( $removed_count ) {
			$summary_parts[] = sprintf(
				_n( '%s service missing', '%s services missing', $removed_count, 'fp-privacy' ),
				number_format_i18n( $removed_count )
			);
		}

		$detected_at = '';
		if ( ! empty( $alert['detected_at'] ) ) {
			$detected_at = \wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $alert['detected_at'] );
		}

		$editor_url = \admin_url( 'admin.php?page=fp-privacy-policy-editor' );
		$added_list = $this->formatter->format_services_list( isset( $alert['added'] ) ? $alert['added'] : array() );
		$removed_list = $this->formatter->format_services_list( isset( $alert['removed'] ) ? $alert['removed'] : array() );
		?>
		<div class="notice notice-warning fp-privacy-detector-alert">
			<p>
				<?php
				if ( $detected_at ) {
					printf(
						'<strong>%s</strong> %s ',
						esc_html__( 'Integration changes detected', 'fp-privacy' ),
						esc_html( sprintf( __( 'on %s', 'fp-privacy' ), $detected_at ) )
					);
				} else {
					printf( '<strong>%s</strong> ', esc_html__( 'Integration changes detected', 'fp-privacy' ) );
				}

				if ( ! empty( $summary_parts ) ) {
					echo esc_html( implode( ' · ', $summary_parts ) );
				}
				?>
			</p>
			<?php if ( $added_list ) : ?>
				<p><strong><?php esc_html_e( 'New:', 'fp-privacy' ); ?></strong> <?php echo esc_html( $added_list ); ?></p>
			<?php endif; ?>
			<?php if ( $removed_list ) : ?>
				<p><strong><?php esc_html_e( 'Removed:', 'fp-privacy' ); ?></strong> <?php echo esc_html( $removed_list ); ?></p>
			<?php endif; ?>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $editor_url ); ?>">
					<?php esc_html_e( 'Review in policy editor', 'fp-privacy' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( \admin_url( 'admin.php?page=fp-privacy-tools' ) ); ?>">
					<?php esc_html_e( 'Open tools', 'fp-privacy' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}















