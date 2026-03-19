<?php
/**
 * Intestazione pagina admin allineata al design system FP (gradiente brand, badge versione).
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Admin;

/**
 * Output HTML per il page header viola (solo backend).
 */
final class AdminHeader {
	/**
	 * @param string $dashicon_class Classe dashicons (es. dashicons-shield).
	 * @param string $title          Titolo visibile (già tradotto).
	 * @param string $subtitle       Sottotitolo opzionale (già tradotto).
	 *
	 * @return void
	 */
	public static function render( string $dashicon_class, string $title, string $subtitle = '' ): void {
		$ver = \defined( 'FP_PRIVACY_PLUGIN_VERSION' )
			? (string) \constant( 'FP_PRIVACY_PLUGIN_VERSION' )
			: '';
		?>
		<div class="fp-privacy-page-header">
			<div class="fp-privacy-page-header-content">
				<h2 class="fp-privacy-page-header-title">
					<span class="dashicons <?php echo \esc_attr( $dashicon_class ); ?>" aria-hidden="true"></span>
					<?php echo \esc_html( $title ); ?>
				</h2>
				<?php if ( '' !== $subtitle ) : ?>
					<p class="fp-privacy-page-header-desc"><?php echo \esc_html( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $ver ) : ?>
				<span class="fp-privacy-page-header-badge"><?php echo \esc_html( 'v' . $ver ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}
}
