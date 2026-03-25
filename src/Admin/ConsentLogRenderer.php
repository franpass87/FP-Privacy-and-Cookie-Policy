<?php
/**
 * Consent log renderer.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

/**
 * Handles rendering of consent log admin page.
 */
class ConsentLogRenderer {
	/**
	 * Events list.
	 *
	 * @var array<int, string>
	 */
	private $events = array( 'accept_all', 'reject_all', 'consent', 'reset', 'revision_bump' );

	/**
	 * Render admin page.
	 *
	 * @param array<string, mixed> $data  Data to render.
	 * @param array<string, mixed> $args  Request args.
	 * @param array<string, string> $urls URLs for export and pagination.
	 *
	 * @return void
	 */
	public function render_page( array $data, array $args, array $urls ) {
		$paged = $args['paged'];
		$pages = (int) ceil( max( 0, (int) $data['total'] ) / max( 1, (int) $args['per_page'] ) );

		?>
		<div class="wrap fp-privacy-consent-log fp-privacy-admin-page">
			<h1 class="screen-reader-text"><?php \esc_html_e( 'Registro consensi', 'fp-privacy' ); ?></h1>
			<?php
			AdminHeader::render(
				'dashicons-list-view',
				\__( 'Registro consensi', 'fp-privacy' ),
				\__( 'Consulta gli eventi di consenso ed esportali per tracciabilità e compliance.', 'fp-privacy' )
			);
			AdminSubnav::maybe_render( 'fp-privacy-consent-log' );
			?>

			<?php if ( ! empty( $data['error'] ) ) : ?>
				<div class="notice notice-error"><p><?php echo \esc_html__( 'Impossibile caricare il registro consensi: tabella assente o database temporaneamente non disponibile. Puoi comunque usare i filtri o riprovare.', 'fp-privacy' ); ?></p></div>
			<?php endif; ?>

			<div class="fp-privacy-card fp-privacy-card--filters">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-filter" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Filtri ed esportazione', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<p class="description"><?php \esc_html_e( 'Filtra per data, evento o testo; esporta i risultati in CSV.', 'fp-privacy' ); ?></p>
					<?php $this->render_filters( $args, $urls['export'] ); ?>
				</div>
			</div>

			<div class="fp-privacy-card fp-privacy-card--summary">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Panoramica ultimi 30 giorni', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
					<p class="description"><?php \esc_html_e( 'Conteggi rapidi per tipo di evento nel periodo indicato.', 'fp-privacy' ); ?></p>
					<ul class="fp-privacy-summary-list">
					<?php foreach ( $data['summary'] as $event => $count ) : ?>
						<li><strong><?php echo \esc_html( $this->event_label( $event ) ); ?>:</strong> <?php echo (int) $count; ?></li>
					<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<table class="widefat fp-privacy-log-table fp-privacy-table">
				<thead>
					<tr>
						<th><?php \esc_html_e( 'Data', 'fp-privacy' ); ?></th>
						<th><?php \esc_html_e( 'Evento', 'fp-privacy' ); ?></th>
						<th><?php \esc_html_e( 'ID consenso', 'fp-privacy' ); ?></th>
						<th><?php \esc_html_e( 'Lingua', 'fp-privacy' ); ?></th>
						<th><?php \esc_html_e( 'Revisione', 'fp-privacy' ); ?></th>
						<th><?php \esc_html_e( 'User agent', 'fp-privacy' ); ?></th>
						<th><?php \esc_html_e( 'Stati', 'fp-privacy' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $data['entries'] ) ) : ?>
						<tr><td colspan="7"><?php \esc_html_e( 'Nessun risultato.', 'fp-privacy' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $data['entries'] as $entry ) : ?>
							<tr>
								<td><?php echo \esc_html( \mysql2date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), $entry['created_at'] ) ); ?></td>
								<td><span class="fp-privacy-event fp-privacy-event-<?php echo \esc_attr( $entry['event'] ); ?>"><?php echo \esc_html( $this->event_label( $entry['event'] ) ); ?></span></td>
								<td><?php echo \esc_html( $entry['consent_id'] ); ?></td>
								<td><?php echo \esc_html( $entry['lang'] ); ?></td>
								<td><?php echo (int) $entry['rev']; ?></td>
								<td><code><?php echo \esc_html( $this->truncate_user_agent( $entry['ua'] ) ); ?></code></td>
								<td><details><summary><?php \esc_html_e( 'Mostra', 'fp-privacy' ); ?></summary><pre><?php echo \esc_html( \wp_json_encode( $entry['states'], JSON_PRETTY_PRINT ) ); ?></pre></details></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php echo \paginate_links(
							array(
								'current' => $paged,
								'total'   => $pages,
								'base'    => $urls['pagination_base'],
								'format'  => '',
							)
						); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render filters form.
	 *
	 * @param array<string, mixed> $args      Args.
	 * @param string                $export_url Export URL.
	 *
	 * @return void
	 */
	public function render_filters( array $args, $export_url ) {
		?>
		<form method="get" class="fp-privacy-filters">
			<input type="hidden" name="page" value="fp-privacy-consent-log" />
			<input type="search" name="s" value="<?php echo \esc_attr( $args['search'] ); ?>" placeholder="<?php \esc_attr_e( 'Cerca per ID, user agent o lingua', 'fp-privacy' ); ?>" />
			<select name="event">
				<option value=""><?php \esc_html_e( 'Tutti gli eventi', 'fp-privacy' ); ?></option>
				<?php foreach ( $this->events as $event ) : ?>
					<option value="<?php echo \esc_attr( $event ); ?>" <?php selected( $args['event'], $event ); ?>><?php echo \esc_html( $this->event_label( $event ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<label><?php \esc_html_e( 'Da', 'fp-privacy' ); ?> <input type="date" name="from" value="<?php echo \esc_attr( $args['from'] ); ?>" /></label>
			<label><?php \esc_html_e( 'A', 'fp-privacy' ); ?> <input type="date" name="to" value="<?php echo \esc_attr( $args['to'] ); ?>" /></label>
			<button type="submit" class="button"><?php \esc_html_e( 'Filtra', 'fp-privacy' ); ?></button>
			<a href="<?php echo \esc_url( $export_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Esporta CSV', 'fp-privacy' ); ?></a>
		</form>
		<?php
	}

	/**
	 * Build export and pagination URLs preserving filters.
	 *
	 * @param array<string, mixed> $args Args.
	 *
	 * @return array{export:string,pagination_base:string}
	 */
	public function build_urls( array $args ) {
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

		return array(
			'export'          => $export_url,
			'pagination_base' => $pagination_base,
		);
	}

	/**
	 * Truncate user agent strings safely when mbstring is unavailable.
	 *
	 * @param string $ua    User agent string.
	 * @param int    $width Maximum length.
	 *
	 * @return string
	 */
	public function truncate_user_agent( $ua, $width = 80 ) {
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

	/**
	 * Human label for event key.
	 *
	 * @param string $event Event key.
	 *
	 * @return string
	 */
	public function event_label( $event ) {
		$event = (string) $event;
		$labels = array(
			'accept_all'      => \__( 'Accetta tutto', 'fp-privacy' ),
			'reject_all'      => \__( 'Rifiuta tutto', 'fp-privacy' ),
			'consent'         => \__( 'Preferenze', 'fp-privacy' ),
			'reset'           => \__( 'Reset', 'fp-privacy' ),
			'revision_bump'   => \__( 'Nuova revisione consenso', 'fp-privacy' ),
			'consent_revoked' => \__( 'Consenso revocato', 'fp-privacy' ),
			'consent_withdrawn' => \__( 'Consenso ritirato', 'fp-privacy' ),
		);
		if ( isset( $labels[ $event ] ) ) {
			return $labels[ $event ];
		}
		$label = str_replace( '_', ' ', $event );
		if ( function_exists( 'mb_convert_case' ) ) {
			return mb_convert_case( $label, MB_CASE_TITLE, 'UTF-8' );
		}
		return ucfirst( $label );
	}
}















