<?php
/**
 * Script blocker placeholder builder.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use function base64_encode;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function sprintf;

/**
 * Handles building placeholder markup for blocked content.
 */
class ScriptBlockerPlaceholder {
	/**
	 * Build placeholder markup for the provided HTML chunk.
	 *
	 * @param string $original Original HTML.
	 * @param string $type     Placeholder type (script|style|iframe).
	 * @param string $category Consent category.
	 * @param string $label    Category label (optional, will use category slug if not provided).
	 *
	 * @return string
	 */
	public static function build( $original, $type, $category, $label = '' ) {
		$encoded = base64_encode( $original );

		if ( false === $encoded ) {
			return $original;
		}

		$category_attr = esc_attr( $category );
		$encoded_attr  = esc_attr( $encoded );

		if ( 'script' === $type ) {
			return sprintf(
				'<script type="text/plain" data-fp-privacy-blocked="script" data-fp-privacy-category="%1$s" data-fp-privacy-replace="%2$s"></script>',
				$category_attr,
				$encoded_attr
			);
		}

		if ( 'style' === $type ) {
			return sprintf(
				'<span class="fp-privacy-style-placeholder" data-fp-privacy-blocked="style" data-fp-privacy-category="%1$s" data-fp-privacy-replace="%2$s"></span>',
				$category_attr,
				$encoded_attr
			);
		}

		$display_label = '' !== $label ? $label : $category;
		$message       = sprintf(
			__( 'Content blocked until %s consent is granted.', 'fp-privacy' ),
			$display_label
		);

		return sprintf(
			'<div class="fp-privacy-blocked" data-fp-privacy-blocked="iframe" data-fp-privacy-category="%1$s" data-fp-privacy-replace="%2$s"><p>%3$s</p><button type="button" class="button" data-fp-privacy-open="1">%4$s</button></div>',
			$category_attr,
			$encoded_attr,
			esc_html( $message ),
			esc_html__( 'Manage preferences', 'fp-privacy' )
		);
	}
}















