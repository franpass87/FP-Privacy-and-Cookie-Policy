<?php
/**
 * Diagnostic state renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Controllers\Diagnostic
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Presentation\Admin\Controllers\Diagnostic;

use FP\Privacy\Frontend\ConsentState;

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
	public static function render_current_state( array $frontend_state ): void {
		?>
		<div class="fp-privacy-card fp-privacy-diagnostic-card">
			<div class="fp-privacy-card-header">
				<div class="fp-privacy-card-header-left">
					<span class="dashicons dashicons-chart-line" aria-hidden="true"></span>
					<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Stato corrente', 'fp-privacy' ); ?></h2>
				</div>
			</div>
			<div class="fp-privacy-card-body fp-privacy-card-body--flush-table">
			<p class="description"><?php \esc_html_e( 'Valutazione lato visitatore: se il banner deve mostrarsi, anteprima admin e dati del consenso.', 'fp-privacy' ); ?></p>
			<table class="widefat striped fp-privacy-table">
				<tbody>
					<tr>
						<td><strong><?php \esc_html_e( 'Banner visibile', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php if ( ! empty( $frontend_state['state']['should_display'] ) ) : ?>
								<span class="fp-privacy-diagnostic-flag fp-privacy-diagnostic-flag--yes"><?php \esc_html_e( 'Sì', 'fp-privacy' ); ?></span>
							<?php else : ?>
								<span class="fp-privacy-diagnostic-flag fp-privacy-diagnostic-flag--no"><?php \esc_html_e( 'No', 'fp-privacy' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'Modalità anteprima', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php echo ! empty( $frontend_state['state']['preview_mode'] ) ? \esc_html__( 'Attiva', 'fp-privacy' ) : \esc_html__( 'Disattiva', 'fp-privacy' ); ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'ID consenso', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php
							$consent_id = $frontend_state['state']['consent_id'] ?? '';
							if ( $consent_id ) {
								echo '<code class="is-monospace">' . \esc_html( \substr( (string) $consent_id, 0, 16 ) ) . '…</code>';
							} else {
								echo '<em>' . \esc_html__( 'Nessuno', 'fp-privacy' ) . '</em>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'Revisione', 'fp-privacy' ); ?></strong></td>
						<td><code class="is-monospace"><?php echo \esc_html( (string) ( $frontend_state['state']['revision'] ?? '1' ) ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'Lingua', 'fp-privacy' ); ?></strong></td>
						<td><code class="is-monospace"><?php echo \esc_html( (string) ( $frontend_state['state']['lang'] ?? 'N/A' ) ); ?></code></td>
					</tr>
				</tbody>
			</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render debug info section.
	 *
	 * @return void
	 */
	public static function render_debug_info(): void {
		$cookie_name = ConsentState::COOKIE_NAME;
		$cookie_raw  = isset( $_COOKIE[ $cookie_name ] ) ? \sanitize_text_field( \wp_unslash( (string) $_COOKIE[ $cookie_name ] ) ) : '';
		$version     = \defined( 'FP_PRIVACY_PLUGIN_VERSION' ) ? (string) \constant( 'FP_PRIVACY_PLUGIN_VERSION' ) : '';
		?>
		<div class="fp-privacy-card fp-privacy-diagnostic-card fp-privacy-diagnostic-card--spaced">
			<div class="fp-privacy-card-header">
				<div class="fp-privacy-card-header-left">
					<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
					<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Informazioni di debug', 'fp-privacy' ); ?></h2>
				</div>
			</div>
			<div class="fp-privacy-card-body fp-privacy-card-body--flush-table">
			<p class="description"><?php \esc_html_e( 'Tema attivo, hook wp_body_open, cookie consenso e versione plugin.', 'fp-privacy' ); ?></p>
			<table class="widefat striped fp-privacy-table">
				<tbody>
					<tr>
						<td><strong><?php \esc_html_e( 'Tema attivo', 'fp-privacy' ); ?></strong></td>
						<td><code class="is-monospace"><?php echo \esc_html( (string) \wp_get_theme()->get( 'Name' ) ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'Hook wp_body_open', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php if ( \function_exists( 'wp_body_open' ) ) : ?>
								<span class="fp-privacy-diagnostic-flag fp-privacy-diagnostic-flag--yes"><?php \esc_html_e( 'Supportato', 'fp-privacy' ); ?></span>
							<?php else : ?>
								<span class="fp-privacy-diagnostic-flag fp-privacy-diagnostic-flag--no"><?php \esc_html_e( 'Non supportato', 'fp-privacy' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'Cookie consenso', 'fp-privacy' ); ?></strong></td>
						<td>
							<?php
							if ( '' !== $cookie_raw ) {
								echo '<code class="is-monospace">' . \esc_html( $cookie_raw ) . '</code>';
							} else {
								echo '<em>' . \esc_html__( 'Nessuno', 'fp-privacy' ) . '</em>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td><strong><?php \esc_html_e( 'Versione plugin', 'fp-privacy' ); ?></strong></td>
						<td><code class="is-monospace"><?php echo \esc_html( $version ); ?></code></td>
					</tr>
				</tbody>
			</table>
			</div>
		</div>
		<?php
	}
}
