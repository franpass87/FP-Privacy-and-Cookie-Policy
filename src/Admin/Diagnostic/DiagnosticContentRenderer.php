<?php
/**
 * Diagnostic content renderer.
 *
 * @package FP\Privacy\Admin\Diagnostic
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Diagnostic;

/**
 * Handles rendering of diagnostic page content sections.
 */
class DiagnosticContentRenderer {
	/**
	 * Render consent categories section.
	 *
	 * @param array<string, mixed> $all_options All options.
	 * @param string                $lang        Current language.
	 * @return void
	 */
	public static function render_consent_categories( array $all_options, string $lang ) {
		?>
		<div class="card" style="margin-top: 20px;">
			<h2 class="title"><?php esc_html_e( 'Categorie Consenso', 'fp-privacy' ); ?></h2>
			<?php
			$categories = $all_options['categories'] ?? array();
			if ( ! empty( $categories ) ) :
				?>
				<ul style="list-style: none; padding-left: 0;">
					<?php foreach ( $categories as $cat_id => $cat_data ) : ?>
						<?php
						$label  = $cat_data['label'][ $lang ] ?? $cat_data['label']['en_US'] ?? $cat_id;
						$locked = ! empty( $cat_data['locked'] );
						?>
						<li style="padding: 8px 0; border-bottom: 1px solid #ddd;">
							<strong><?php echo esc_html( $label ); ?></strong>
							<?php if ( $locked ) : ?>
								<span style="color: #999;">🔒 <?php esc_html_e( '(Sempre attivo)', 'fp-privacy' ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p style="color: #dc3232;">
					⚠️ <?php esc_html_e( 'Nessuna categoria configurata. Usa il pulsante "Configura Default" qui sotto.', 'fp-privacy' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render policy pages section.
	 *
	 * @param array<string, mixed> $all_options All options.
	 * @return void
	 */
	public static function render_policy_pages( array $all_options ) {
		?>
		<div class="card" style="margin-top: 20px;">
			<h2 class="title"><?php esc_html_e( 'Pagine Policy', 'fp-privacy' ); ?></h2>
			<?php
			$pages = $all_options['pages'] ?? array();
			foreach ( array( 'privacy_policy_page_id', 'cookie_policy_page_id' ) as $key ) :
				$type = str_replace( '_page_id', '', $key );
				?>
				<h4><?php echo esc_html( ucfirst( str_replace( '_', ' ', $type ) ) ); ?></h4>
				<?php
				if ( ! empty( $pages[ $key ] ) && is_array( $pages[ $key ] ) ) :
					foreach ( $pages[ $key ] as $lang_code => $page_id ) :
						$page = \get_post( $page_id );
						if ( $page ) :
							?>
							<div style="padding: 5px 0;">
								<strong><?php echo esc_html( $lang_code ); ?>:</strong>
								<a href="<?php echo esc_url( \get_permalink( $page_id ) ); ?>" target="_blank">
									<?php echo esc_html( $page->post_title ); ?>
								</a>
								<code>(ID: <?php echo esc_html( $page_id ); ?>)</code>
							</div>
						<?php else : ?>
							<div style="padding: 5px 0; color: #dc3232;">
								<strong><?php echo esc_html( $lang_code ); ?>:</strong>
								<?php esc_html_e( 'Pagina non trovata', 'fp-privacy' ); ?>
								<code>(ID: <?php echo esc_html( $page_id ); ?>)</code>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php else : ?>
					<p style="color: #999;"><em><?php esc_html_e( 'Nessuna pagina configurata', 'fp-privacy' ); ?></em></p>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render quick actions section.
	 *
	 * @return void
	 */
	public static function render_quick_actions() {
		?>
		<div class="card">
			<h2 class="title"><?php esc_html_e( 'Azioni Rapide', 'fp-privacy' ); ?></h2>
			
			<div style="margin-bottom: 20px;">
				<h3><?php esc_html_e( '1. Configura Impostazioni di Default', 'fp-privacy' ); ?></h3>
				<p><?php esc_html_e( 'Configura categorie, testi del banner e layout predefiniti.', 'fp-privacy' ); ?></p>
				<form method="post" action="<?php echo esc_url( \admin_url( 'admin-post.php' ) ); ?>">
					<?php \wp_nonce_field( 'fp_privacy_setup_defaults' ); ?>
					<input type="hidden" name="action" value="fp_privacy_setup_defaults">
					<button type="submit" class="button button-primary button-large">
						⚙️ <?php esc_html_e( 'Configura Default', 'fp-privacy' ); ?>
					</button>
				</form>
			</div>

			<hr>

			<div style="margin-bottom: 20px;">
				<h3><?php esc_html_e( '2. Forza Visualizzazione Banner', 'fp-privacy' ); ?></h3>
				<p><?php esc_html_e( 'Attiva la modalità preview e cancella il cookie di consenso per forzare la visualizzazione del banner.', 'fp-privacy' ); ?></p>
				<form method="post" action="<?php echo esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-right: 10px;">
					<?php \wp_nonce_field( 'fp_privacy_force_banner' ); ?>
					<input type="hidden" name="action" value="fp_privacy_force_banner">
					<button type="submit" class="button button-secondary button-large">
						👁️ <?php esc_html_e( 'Attiva Preview', 'fp-privacy' ); ?>
					</button>
				</form>
				<form method="post" action="<?php echo esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block;">
					<?php \wp_nonce_field( 'fp_privacy_disable_preview' ); ?>
					<input type="hidden" name="action" value="fp_privacy_disable_preview">
					<button type="submit" class="button button-secondary button-large">
						❌ <?php esc_html_e( 'Disattiva Preview', 'fp-privacy' ); ?>
					</button>
				</form>
			</div>

			<hr>

			<div style="margin-bottom: 20px;">
				<h3><?php esc_html_e( '3. Cancella Consenso Corrente', 'fp-privacy' ); ?></h3>
				<p><?php esc_html_e( 'Cancella il tuo consenso corrente per testare il banner (solo per il tuo account).', 'fp-privacy' ); ?></p>
				<form method="post" action="<?php echo esc_url( \admin_url( 'admin-post.php' ) ); ?>">
					<?php \wp_nonce_field( 'fp_privacy_clear_consent' ); ?>
					<input type="hidden" name="action" value="fp_privacy_clear_consent">
					<button type="submit" class="button button-secondary button-large">
						🗑️ <?php esc_html_e( 'Cancella Consenso', 'fp-privacy' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render useful links section.
	 *
	 * @return void
	 */
	public static function render_useful_links() {
		?>
		<div class="card" style="margin-top: 20px;">
			<h2 class="title"><?php esc_html_e( 'Link Utili', 'fp-privacy' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( \admin_url( 'admin.php?page=fp-privacy-settings' ) ); ?>">⚙️ <?php esc_html_e( 'Impostazioni', 'fp-privacy' ); ?></a></li>
				<li><a href="<?php echo esc_url( \home_url( '/' ) ); ?>" target="_blank">🏠 <?php esc_html_e( 'Visualizza Sito', 'fp-privacy' ); ?></a></li>
				<li><a href="<?php echo esc_url( \admin_url( 'admin.php?page=fp-privacy-consent-log' ) ); ?>">📊 <?php esc_html_e( 'Log Consensi', 'fp-privacy' ); ?></a></li>
			</ul>
		</div>
		<?php
	}
}















