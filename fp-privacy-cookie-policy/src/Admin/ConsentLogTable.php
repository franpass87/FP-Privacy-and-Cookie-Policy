<?php
/**
 * Consent log admin page.
 *
 * @package FP\Privacy\Admin
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;

/**
 * Displays consent log entries.
 */
class ConsentLogTable {
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
\add_action( 'fp_privacy_admin_page_consent_log', array( $this, 'render_page' ) );
	\add_action( 'admin_post_fp_privacy_export_csv', array( $this, 'handle_export_csv' ) );
}

/**
 * Render admin page.
 *
 * @return void
 */
public function render_page() {
if ( ! \current_user_can( 'manage_options' ) ) {
\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
}

$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$per_page = 50;
$args     = array(
'paged'    => $paged,
'per_page' => $per_page,
'event'    => isset( $_GET['event'] ) ? \sanitize_text_field( \wp_unslash( $_GET['event'] ) ) : '',
'search'   => isset( $_GET['s'] ) ? \sanitize_text_field( \wp_unslash( $_GET['s'] ) ) : '',
'from'     => isset( $_GET['from'] ) ? \sanitize_text_field( \wp_unslash( $_GET['from'] ) ) : '',
'to'       => isset( $_GET['to'] ) ? \sanitize_text_field( \wp_unslash( $_GET['to'] ) ) : '',
);

$entries = $this->log_model->query( $args );
$total   = $this->log_model->count( $args );
$summary = $this->log_model->summary_last_30_days();
$pages   = (int) ceil( $total / $per_page );

$pagination_args = array(
	'page' => 'fp-privacy-consent-log',
);

$export_args = array(
	'action'   => 'fp_privacy_export_csv',
	'_wpnonce' => \wp_create_nonce( 'fp_privacy_export_csv' ),
);

foreach ( array( 'event' => 'event', 'search' => 's', 'from' => 'from', 'to' => 'to' ) as $key => $query_key ) {
	if ( ! empty( $args[ $key ] ) ) {
		$pagination_args[ $query_key ] = $args[ $key ];
		$export_args[ $query_key ]      = $args[ $key ];
	}
}

$pagination_base = \esc_url_raw( \add_query_arg( array_merge( $pagination_args, array( 'paged' => '%#%' ) ), \admin_url( 'admin.php' ) ) );
$export_url      = \add_query_arg( $export_args, \admin_url( 'admin-post.php' ) );

?>
<div class="wrap fp-privacy-consent-log">
<h1><?php \esc_html_e( 'Consent log', 'fp-privacy' ); ?></h1>
<p><?php \esc_html_e( 'Review consent events and export them for compliance.', 'fp-privacy' ); ?></p>

<form method="get" class="fp-privacy-filters">
<input type="hidden" name="page" value="fp-privacy-consent-log" />
<input type="search" name="s" value="<?php echo \esc_attr( $args['search'] ); ?>" placeholder="<?php \esc_attr_e( 'Search by ID, user agent or language', 'fp-privacy' ); ?>" />
<select name="event">
<option value=""><?php \esc_html_e( 'All events', 'fp-privacy' ); ?></option>
<?php foreach ( array( 'accept_all', 'reject_all', 'consent', 'reset', 'revision_bump' ) as $event ) : ?>
<option value="<?php echo \esc_attr( $event ); ?>" <?php\selected( $args['event'], $event ); ?>><?php echo \esc_html( ucfirst( str_replace( '_', ' ', $event ) ) ); ?></option>
<?php endforeach; ?>
</select>
<label><?php \esc_html_e( 'From', 'fp-privacy' ); ?> <input type="date" name="from" value="<?php echo \esc_attr( $args['from'] ); ?>" /></label>
<label><?php \esc_html_e( 'To', 'fp-privacy' ); ?> <input type="date" name="to" value="<?php echo \esc_attr( $args['to'] ); ?>" /></label>
<button type="submit" class="button"><?php \esc_html_e( 'Filter', 'fp-privacy' ); ?></button>
<a href="<?php echo \esc_url( $export_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Export CSV', 'fp-privacy' ); ?></a>
</form>

<div class="fp-privacy-summary">
<h2><?php \esc_html_e( 'Last 30 days overview', 'fp-privacy' ); ?></h2>
<ul>
<?php foreach ( $summary as $event => $count ) : ?>
<li><strong><?php echo \esc_html( ucfirst( str_replace( '_', ' ', $event ) ) ); ?>:</strong> <?php echo (int) $count; ?></li>
<?php endforeach; ?>
</ul>
</div>

<table class="widefat fp-privacy-log-table">
<thead>
<tr>
<th><?php \esc_html_e( 'Date', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'Event', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'Consent ID', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'Language', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'Revision', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'User agent', 'fp-privacy' ); ?></th>
<th><?php \esc_html_e( 'States', 'fp-privacy' ); ?></th>
</tr>
</thead>
<tbody>
<?php if ( empty( $entries ) ) : ?>
<tr><td colspan="7"><?php \esc_html_e( 'No entries found.', 'fp-privacy' ); ?></td></tr>
<?php else : ?>
<?php foreach ( $entries as $entry ) : ?>
<tr>
<td><?php echo \esc_html( \mysql2date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), $entry['created_at'] ) ); ?></td>
<td><span class="fp-privacy-event fp-privacy-event-<?php echo \esc_attr( $entry['event'] ); ?>"><?php echo \esc_html( ucfirst( str_replace( '_', ' ', $entry['event'] ) ) ); ?></span></td>
<td><?php echo \esc_html( $entry['consent_id'] ); ?></td>
<td><?php echo \esc_html( $entry['lang'] ); ?></td>
<td><?php echo (int) $entry['rev']; ?></td>
<td><code><?php echo \esc_html( $this->truncate_user_agent( $entry['ua'] ) ); ?></code></td>
<td><details><summary><?php \esc_html_e( 'View', 'fp-privacy' ); ?></summary><pre><?php echo \esc_html( \wp_json_encode( $entry['states'], JSON_PRETTY_PRINT ) ); ?></pre></details></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

<?php if ( $pages > 1 ) : ?>
<div class="tablenav">
<div class="tablenav-pages">
<?php echo \paginate_links( array(
'current' => $paged,
'total'   => $pages,
'base'    => $pagination_base,
'format'  => '',
) ); ?>
</div>
</div>
<?php endif; ?>
</div>
<?php
}

/**
 * Handle CSV export.
 *
 * @return void
 */
public function handle_export_csv() {
	if ( ! \current_user_can( 'manage_options' ) ) {
	\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
	}

	\check_admin_referer( 'fp_privacy_export_csv' );

	$args = array(
		'event' => isset( $_GET['event'] ) ? \sanitize_text_field( \wp_unslash( $_GET['event'] ) ) : '',
		'search' => isset( $_GET['s'] ) ? \sanitize_text_field( \wp_unslash( $_GET['s'] ) ) : '',
		'from'   => isset( $_GET['from'] ) ? \sanitize_text_field( \wp_unslash( $_GET['from'] ) ) : '',
		'to'     => isset( $_GET['to'] ) ? \sanitize_text_field( \wp_unslash( $_GET['to'] ) ) : '',
	);

	$batch = (int) \apply_filters( 'fp_privacy_csv_export_batch_size', 1000 );
	if ( $batch < 1 ) {
		$batch = 1000;
	}

	$filename = 'fp-consent-log-' . \gmdate( 'Ymd-His' ) . '.csv';
	$handle   = \fopen( 'php://output', 'w' );

	if ( false === $handle ) {
	\wp_die( \esc_html__( 'Unable to open export stream.', 'fp-privacy' ) );
	}

	\nocache_headers();
	\header( 'Content-Type: text/csv; charset=utf-8' );
	\header( 'Content-Disposition: attachment; filename="' . \sanitize_file_name( $filename ) . '"' );

	\fputcsv( $handle, array( 'consent_id', 'event', 'lang', 'rev', 'created_at', 'ip_hash', 'user_agent', 'states' ) );

	$page = 1;

	while ( true ) {
		$entries = $this->log_model->query( array_merge( $args, array( 'paged' => $page, 'per_page' => $batch ) ) );

		if ( empty( $entries ) ) {
			break;
		}

		foreach ( $entries as $entry ) {
			\fputcsv(
				$handle,
				array(
					$entry['consent_id'],
					$entry['event'],
					$entry['lang'],
					(int) $entry['rev'],
					$entry['created_at'],
					$entry['ip_hash'],
					$entry['ua'],
					$this->encode_states_for_csv( $entry['states'] ),
				)
			);
		}

		$page++;
		if ( count( $entries ) < $batch ) {
			break;
		}
	}

	\fclose( $handle );
	exit;
}

    /**
     * Encode states payload safely for CSV output.
     *
     * @param mixed $states States payload.
     *
     * @return string
     */
    private function encode_states_for_csv( $states ) {
        $encoded = \wp_json_encode( $states );

        if ( false === $encoded ) {
            return '{}';
        }

        return $encoded;
    }

    /**
     * Truncate user agent strings safely when mbstring is unavailable.
     *
     * @param string $ua    User agent string.
     * @param int    $width Maximum length.
     *
     * @return string
     */
    private function truncate_user_agent( $ua, $width = 80 ) {
        $ua       = (string) $ua;
        $ellipsis = '…';

        if ( function_exists( 'mb_strimwidth' ) ) {
            return mb_strimwidth( $ua, 0, $width, $ellipsis, 'UTF-8' );
        }

        if ( strlen( $ua ) <= $width ) {
            return $ua;
        }

        $cut = max( 0, $width - strlen( $ellipsis ) );

        return substr( $ua, 0, $cut ) . $ellipsis;
    }
}
