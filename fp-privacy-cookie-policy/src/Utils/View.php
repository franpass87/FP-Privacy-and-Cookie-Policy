<?php
/**
 * View renderer.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use function apply_filters;
use function ob_get_clean;
use function ob_start;
use function str_replace;
use function trailingslashit;
use function wp_normalize_path;

/**
 * Simple view rendering helper.
 */
class View {
	/**
	 * Render a PHP template and return the output.
	 *
	 * @param string               $template Template path relative to the plugin directory.
	 * @param array<string, mixed> $context  Data available inside the template.
	 *
	 * @return string
	 */
	public function render( string $template, array $context = array() ): string {
		$path = $this->resolve_template_path( $template );

		if ( '' === $path || ! file_exists( $path ) ) {
			return '';
		}

		$context = apply_filters( 'fp_privacy_view_context', $context, $template );

		ob_start();
		extract( $context, EXTR_SKIP );
		require $path;

		return (string) ob_get_clean();
	}

	/**
	 * Resolve template path ensuring it points inside the plugin directory.
	 *
	 * @param string $template Template identifier.
	 *
	 * @return string
	 */
	private function resolve_template_path( string $template ): string {
		$template = trim( $template );

		if ( '' === $template ) {
			return '';
		}

		$template = wp_normalize_path( $template );
		$template = str_replace( array( '../', '..\\' ), '', $template );

		if ( 0 !== strpos( $template, 'templates/' ) ) {
			$template = 'templates/' . ltrim( $template, '/' );
		}

		return trailingslashit( FP_PRIVACY_PLUGIN_PATH ) . $template;
	}
}
