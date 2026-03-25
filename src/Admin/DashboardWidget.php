<?php
/**
 * Dashboard widget.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;

/**
 * Adds dashboard widget summary.
 */
class DashboardWidget {
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
 * Hooks.
 *
 * @return void
 */
public function hooks() {
\add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
}

/**
 * Register widget.
 *
 * @return void
 */
public function register_widget() {
\wp_add_dashboard_widget( 'fp_privacy_dashboard', \__( 'Panoramica Privacy e Cookie', 'fp-privacy' ), array( $this, 'render_widget' ) );
}

/**
 * Render widget content.
 *
 * @return void
 */
public function render_widget() {
$summary       = $this->log_model->summary_last_30_days();
$total         = array_sum( $summary );
$event_labels  = new ConsentLogRenderer();
?>
<p><strong><?php \esc_html_e( 'Eventi totali negli ultimi 30 giorni:', 'fp-privacy' ); ?></strong> <?php echo (int) $total; ?></p>
<ul>
<?php foreach ( $summary as $event => $count ) : ?>
<li><?php echo \esc_html( $event_labels->event_label( (string) $event ) ); ?>: <?php echo (int) $count; ?></li>
<?php endforeach; ?>
</ul>
<p><a href="<?php echo \esc_url( admin_url( 'admin.php?page=fp-privacy-consent-log' ) ); ?>" class="button"><?php \esc_html_e( 'Apri registro consensi', 'fp-privacy' ); ?></a></p>
<?php
}
}
