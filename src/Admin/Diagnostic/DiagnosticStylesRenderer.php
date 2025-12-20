<?php
/**
 * Diagnostic styles renderer.
 *
 * @package FP\Privacy\Admin\Diagnostic
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Diagnostic;

/**
 * Handles rendering of diagnostic page styles.
 */
class DiagnosticStylesRenderer {
	/**
	 * Render page styles.
	 *
	 * @return void
	 */
	public static function render() {
		?>
		<style>
			.fp-privacy-diagnostic-grid .card {
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				padding: 20px;
			}
			.fp-privacy-diagnostic-grid .card .title {
				margin-top: 0;
				padding-bottom: 10px;
				border-bottom: 1px solid #ddd;
			}
		</style>
		<?php
	}
}















