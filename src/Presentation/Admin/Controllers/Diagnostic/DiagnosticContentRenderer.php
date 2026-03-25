<?php
/**
 * Diagnostic content renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Controllers\Diagnostic
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Presentation\Admin\Controllers\Diagnostic;

/**
 * Handles rendering of diagnostic page content sections.
 */
class DiagnosticContentRenderer {
	/**
	 * Render consent categories section.
	 *
	 * @param array<string, mixed> $all_options All options.
	 * @param string               $lang        Current language.
	 * @return void
	 */
	public static function render_consent_categories( array $all_options, string $lang ): void {
		?>
		<div class="fp-privacy-card fp-privacy-diagnostic-card fp-privacy-diagnostic-card--spaced">
			<div class="fp-privacy-card-header">
				<div class="fp-privacy-card-header-left">
					<span class="dashicons dashicons-category" aria-hidden="true"></span>
					<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Consent categories', 'fp-privacy' ); ?></h2>
				</div>
			</div>
			<div class="fp-privacy-card-body">
			<?php
			$categories = $all_options['categories'] ?? array();
			if ( ! empty( $categories ) ) :
				?>
				<ul class="fp-privacy-diagnostic-list">
					<?php foreach ( $categories as $cat_id => $cat_data ) : ?>
						<?php
						$label  = $cat_data['label'][ $lang ] ?? $cat_data['label']['en_US'] ?? $cat_id;
						$locked = ! empty( $cat_data['locked'] );
						?>
						<li class="fp-privacy-diagnostic-list__item">
							<strong><?php echo \esc_html( $label ); ?></strong>
							<?php if ( $locked ) : ?>
								<span class="fp-privacy-diagnostic-muted"><?php \esc_html_e( '(Always active)', 'fp-privacy' ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="fp-privacy-diagnostic-warning">
					<?php \esc_html_e( 'No categories configured. Use “Configure defaults” in Quick actions below.', 'fp-privacy' ); ?>
				</p>
			<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render policy pages section.
	 *
	 * @param array<string, mixed> $all_options All options.
	 * @return void
	 */
	public static function render_policy_pages( array $all_options ): void {
		?>
		<div class="fp-privacy-card fp-privacy-diagnostic-card fp-privacy-diagnostic-card--spaced">
			<div class="fp-privacy-card-header">
				<div class="fp-privacy-card-header-left">
					<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
					<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Policy pages', 'fp-privacy' ); ?></h2>
				</div>
			</div>
			<div class="fp-privacy-card-body">
			<?php
			$pages = $all_options['pages'] ?? array();
			foreach ( array( 'privacy_policy_page_id', 'cookie_policy_page_id' ) as $key ) :
				$type = \str_replace( '_page_id', '', $key );
				?>
				<h3 class="fp-privacy-diagnostic-subheading"><?php echo \esc_html( \ucfirst( \str_replace( '_', ' ', $type ) ) ); ?></h3>
				<?php
				if ( ! empty( $pages[ $key ] ) && \is_array( $pages[ $key ] ) ) :
					foreach ( $pages[ $key ] as $lang_code => $page_id ) :
						$page = \get_post( $page_id );
						if ( $page ) :
							?>
							<div class="fp-privacy-diagnostic-line">
								<strong><?php echo \esc_html( $lang_code ); ?>:</strong>
								<a href="<?php echo \esc_url( \get_permalink( $page_id ) ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo \esc_html( $page->post_title ); ?>
								</a>
								<code class="is-monospace">(ID: <?php echo \esc_html( (string) $page_id ); ?>)</code>
							</div>
						<?php else : ?>
							<div class="fp-privacy-diagnostic-line fp-privacy-diagnostic-line--error">
								<strong><?php echo \esc_html( $lang_code ); ?>:</strong>
								<?php \esc_html_e( 'Page not found', 'fp-privacy' ); ?>
								<code class="is-monospace">(ID: <?php echo \esc_html( (string) $page_id ); ?>)</code>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="fp-privacy-diagnostic-muted"><em><?php \esc_html_e( 'No page configured', 'fp-privacy' ); ?></em></p>
				<?php endif; ?>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render quick actions section.
	 *
	 * @return void
	 */
	public static function render_quick_actions(): void {
		?>
		<div class="fp-privacy-card fp-privacy-diagnostic-card">
			<div class="fp-privacy-card-header">
				<div class="fp-privacy-card-header-left">
					<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
					<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Quick actions', 'fp-privacy' ); ?></h2>
				</div>
			</div>
			<div class="fp-privacy-card-body">
			<div class="fp-privacy-diagnostic-action-block">
				<h3 class="fp-privacy-diagnostic-action-title"><?php \esc_html_e( '1. Configure default settings', 'fp-privacy' ); ?></h3>
				<p><?php \esc_html_e( 'Set categories, banner texts and default layout.', 'fp-privacy' ); ?></p>
				<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
					<?php \wp_nonce_field( 'fp_privacy_setup_defaults' ); ?>
					<input type="hidden" name="action" value="fp_privacy_setup_defaults">
					<button type="submit" class="fp-privacy-btn fp-privacy-btn-primary fp-privacy-btn--large">
						<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
						<?php \esc_html_e( 'Configure defaults', 'fp-privacy' ); ?>
					</button>
				</form>
			</div>

			<div class="fp-privacy-diagnostic-action-block fp-privacy-card-stack-gap">
				<h3 class="fp-privacy-diagnostic-action-title"><?php \esc_html_e( '2. Force banner preview', 'fp-privacy' ); ?></h3>
				<p><?php \esc_html_e( 'Enable preview mode and clear the consent cookie to show the banner again.', 'fp-privacy' ); ?></p>
				<div class="fp-privacy-diagnostic-inline-forms">
					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<?php \wp_nonce_field( 'fp_privacy_force_banner' ); ?>
						<input type="hidden" name="action" value="fp_privacy_force_banner">
						<button type="submit" class="fp-privacy-btn fp-privacy-btn-secondary fp-privacy-btn--large">
							<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
							<?php \esc_html_e( 'Enable preview', 'fp-privacy' ); ?>
						</button>
					</form>
					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<?php \wp_nonce_field( 'fp_privacy_disable_preview' ); ?>
						<input type="hidden" name="action" value="fp_privacy_disable_preview">
						<button type="submit" class="fp-privacy-btn fp-privacy-btn-secondary fp-privacy-btn--large">
							<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
							<?php \esc_html_e( 'Disable preview', 'fp-privacy' ); ?>
						</button>
					</form>
				</div>
			</div>

			<div class="fp-privacy-diagnostic-action-block fp-privacy-card-stack-gap">
				<h3 class="fp-privacy-diagnostic-action-title"><?php \esc_html_e( '3. Clear current consent', 'fp-privacy' ); ?></h3>
				<p><?php \esc_html_e( 'Clear your own consent cookie to test the banner (this browser only).', 'fp-privacy' ); ?></p>
				<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
					<?php \wp_nonce_field( 'fp_privacy_clear_consent' ); ?>
					<input type="hidden" name="action" value="fp_privacy_clear_consent">
					<button type="submit" class="fp-privacy-btn fp-privacy-btn-secondary fp-privacy-btn--large">
						<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						<?php \esc_html_e( 'Clear consent', 'fp-privacy' ); ?>
					</button>
				</form>
			</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render useful links section.
	 *
	 * @return void
	 */
	public static function render_useful_links(): void {
		?>
		<div class="fp-privacy-card fp-privacy-diagnostic-card fp-privacy-diagnostic-card--spaced">
			<div class="fp-privacy-card-header">
				<div class="fp-privacy-card-header-left">
					<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
					<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Useful links', 'fp-privacy' ); ?></h2>
				</div>
			</div>
			<div class="fp-privacy-card-body">
			<ul class="fp-privacy-diagnostic-links">
				<li>
					<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy' ) ); ?>">
						<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
						<?php \esc_html_e( 'Settings', 'fp-privacy' ); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo \esc_url( \home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="dashicons dashicons-admin-home" aria-hidden="true"></span>
						<?php \esc_html_e( 'View site', 'fp-privacy' ); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=fp-privacy-consent-log' ) ); ?>">
						<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
						<?php \esc_html_e( 'Consent log', 'fp-privacy' ); ?>
					</a>
				</li>
			</ul>
			</div>
		</div>
		<?php
	}
}
