<?php
/**
 * Diagnostic state renderer.
 *
 * @package FP\Privacy\Admin\Diagnostic
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Diagnostic;

use FP\Privacy\Consent\ConsentState;

/**
 * Handles rendering of diagnostic state and debug information.
 */
class DiagnosticStateRenderer {
	/**
	 * Render current state section.
	 *
	 * @param array<string, mixed> $frontend_state Frontend state.
	 * @return void
	 */
	public static function render_current_state( array $frontend_state ) {
		?>
		<div class="card">
			<h2 class="title"><?php esc_html_e( 'Stato Corrente', 'fp-privacy' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Banner Visibile', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php if ( ! empty( $frontend_state['state']['should_display'] ) ) : ?>
								<span style="color: #46b450;">✓ <?php esc_html_e( 'SÌ', 'fp-privacy' ); ?></span>
							<?php else : ?>
								<span style="color: #dc3232;">✗ <?php esc_html_e( 'NO', 'fp-privacy' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Modalità Preview', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php echo ! empty( $frontend_state['state']['preview_mode'] ) ? esc_html__( 'ATTIVA', 'fp-privacy' ) : esc_html__( 'DISATTIVA', 'fp-privacy' ); ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Consent ID', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php
							$consent_id = $frontend_state['state']['consent_id'] ?? '';
							if ( $consent_id ) {
								echo '<code>' . esc_html( substr( $consent_id, 0, 16 ) ) . '...</code>';
							} else {
								echo '<em>' . esc_html__( 'Nessuno', 'fp-privacy' ) . '</em>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Revisione', 'fp-privacy' ); ?></strong></td>
						<td><code><?php echo esc_html( $frontend_state['state']['revision'] ?? '1' ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Lingua', 'fp-privacy' ); ?></strong></td>
						<td><code><?php echo esc_html( $frontend_state['state']['lang'] ?? 'N/A' ); ?></code></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render debug info section.
	 *
	 * @param array<string, mixed> $frontend_state Frontend state.
	 * @return void
	 */
	public static function render_debug_info( array $frontend_state ) {
		?>
		<div class="card" style="margin-top: 20px;">
			<h2 class="title"><?php esc_html_e( 'Informazioni di Debug', 'fp-privacy' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Tema Attivo', 'fp-privacy' ); ?></strong></td>
						<td><code><?php echo esc_html( \wp_get_theme()->get( 'Name' ) ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Hook wp_body_open', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php if ( \function_exists( 'wp_body_open' ) ) : ?>
								<span style="color: #46b450;">✓ <?php esc_html_e( 'Supportato', 'fp-privacy' ); ?></span>
							<?php else : ?>
								<span style="color: #dc3232;">✗ <?php esc_html_e( 'Non supportato', 'fp-privacy' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Cookie Consenso', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php
							$cookie_name = ConsentState::COOKIE_NAME;
							if ( isset( $_COOKIE[ $cookie_name ] ) ) {
								echo '<code>' . esc_html( $_COOKIE[ $cookie_name ] ) . '</code>';
							} else {
								echo '<em>' . esc_html__( 'Nessuno', 'fp-privacy' ) . '</em>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Versione Plugin', 'fp-privacy' ); ?></strong></td>
						<td><code><?php echo esc_html( FP_PRIVACY_PLUGIN_VERSION ); ?></code></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}















