<?php
/**
 * Base renderer for settings tabs.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Utils\Options;

/**
 * Base class for settings tab renderers.
 */
abstract class SettingsRendererBase {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Render a text/textarea field.
	 *
	 * @param string $name       Field name.
	 * @param string $label      Field label.
	 * @param string $value      Field value.
	 * @param string $type       Field type (text|textarea).
	 * @param string $data_field Data field attribute.
	 *
	 * @return void
	 */
	protected function render_text_field( $name, $label, $value, $type = 'text', $data_field = '' ) {
		?>
		<label>
		<span><?php echo \esc_html( $label ); ?></span>
		<?php if ( 'textarea' === $type ) : ?>
			<textarea name="<?php echo \esc_attr( $name ); ?>" rows="4" class="large-text" data-field="<?php echo \esc_attr( $data_field ); ?>"><?php echo \esc_textarea( $value ); ?></textarea>
		<?php else : ?>
			<input type="text" name="<?php echo \esc_attr( $name ); ?>" value="<?php echo \esc_attr( $value ); ?>" class="regular-text" data-field="<?php echo \esc_attr( $data_field ); ?>" />
		<?php endif; ?>
		</label>
		<?php
	}
}
















