<?php
/**
 * Markup admin riusabile (design system FP).
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Admin;

/**
 * Helper per output HTML coerente con `.fp-privacy-btn` (evita `submit_button()` core).
 */
final class AdminUi {
	/**
	 * Pulsante submit stile FP (gradiente / secondario).
	 *
	 * @param string               $text            Etichetta (già tradotta o da tradurre dal chiamante).
	 * @param string               $variant         `primary` o `secondary`.
	 * @param array<string, mixed> $attrs           Attributi: `name`, `id`, `class` (aggiuntivi), `value`, `form`.
	 * @param bool                 $wrap_paragraph  Se true avvolge in `<p>` come `submit_button()` default WP.
	 *
	 * @return void
	 */
	public static function render_submit_button( string $text, string $variant, array $attrs = array(), bool $wrap_paragraph = true ): void {
		$is_primary = ( 'primary' === $variant );
		$btn_class  = $is_primary ? 'fp-privacy-btn fp-privacy-btn-primary' : 'fp-privacy-btn fp-privacy-btn-secondary';

		$name = isset( $attrs['name'] ) ? (string) $attrs['name'] : 'submit';
		$id   = isset( $attrs['id'] ) ? (string) $attrs['id'] : $name;

		$extra_class = isset( $attrs['class'] ) ? (string) $attrs['class'] : '';
		if ( '' !== $extra_class ) {
			$btn_class .= ' ' . $extra_class;
		}

		$attr_parts = array(
			'type="submit"',
			'class="' . \esc_attr( $btn_class ) . '"',
			'name="' . \esc_attr( $name ) . '"',
			'id="' . \esc_attr( $id ) . '"',
		);

		if ( isset( $attrs['value'] ) ) {
			$attr_parts[] = 'value="' . \esc_attr( (string) $attrs['value'] ) . '"';
		}
		if ( isset( $attrs['form'] ) && '' !== (string) $attrs['form'] ) {
			$attr_parts[] = 'form="' . \esc_attr( (string) $attrs['form'] ) . '"';
		}

		$button_inner = \esc_html( $text );
		if ( isset( $attrs['dashicon'] ) && \is_string( $attrs['dashicon'] ) && '' !== $attrs['dashicon'] ) {
			$icon_class = \sanitize_html_class( $attrs['dashicon'] );
			$button_inner = '<span class="dashicons ' . \esc_attr( $icon_class ) . '" aria-hidden="true"></span> ' . $button_inner;
		}

		$html = '<button ' . \implode( ' ', $attr_parts ) . '>' . $button_inner . '</button>';

		if ( $wrap_paragraph ) {
			echo '<p>' . $html . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- button built with escaped parts.
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
