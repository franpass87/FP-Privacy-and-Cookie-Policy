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
	 * Render a text/textarea field with standardized styling.
	 *
	 * @param string $name        Field name.
	 * @param string $label       Field label.
	 * @param string $value       Field value.
	 * @param string $type        Field type (text|textarea|email|url).
	 * @param string $data_field  Data field attribute.
	 * @param string $description Optional description/help text.
	 * @param bool   $required    Whether field is required.
	 *
	 * @return void
	 */
	protected function render_text_field( $name, $label, $value, $type = 'text', $data_field = '', $description = '', $required = false ) {
		$field_id = 'fp-field-' . \sanitize_html_class( $name );
		$description_id = ! empty( $description ) ? $field_id . '-description' : '';
		?>
		<div class="fp-form-field">
			<label for="<?php echo \esc_attr( $field_id ); ?>" class="fp-form-label<?php echo $required ? ' required' : ''; ?>">
				<?php echo \esc_html( $label ); ?>
			</label>
			<?php if ( 'textarea' === $type ) : ?>
				<textarea 
					id="<?php echo \esc_attr( $field_id ); ?>"
					name="<?php echo \esc_attr( $name ); ?>" 
					rows="4" 
					class="fp-form-textarea large-text" 
					data-field="<?php echo \esc_attr( $data_field ); ?>"
					aria-describedby="<?php echo $description_id ? \esc_attr( $description_id ) : ''; ?>"
					<?php echo $required ? 'required aria-required="true"' : ''; ?>
				><?php echo \esc_textarea( $value ); ?></textarea>
			<?php else : ?>
				<input 
					type="<?php echo \esc_attr( $type ); ?>"
					id="<?php echo \esc_attr( $field_id ); ?>"
					name="<?php echo \esc_attr( $name ); ?>" 
					value="<?php echo \esc_attr( $value ); ?>" 
					class="fp-form-input regular-text" 
					data-field="<?php echo \esc_attr( $data_field ); ?>"
					aria-describedby="<?php echo $description_id ? \esc_attr( $description_id ) : ''; ?>"
					<?php echo $required ? 'required aria-required="true"' : ''; ?>
				/>
			<?php endif; ?>
			<?php if ( ! empty( $description ) ) : ?>
				<span id="<?php echo \esc_attr( $description_id ); ?>" class="fp-form-description" role="text"><?php echo \esc_html( $description ); ?></span>
			<?php endif; ?>
			<span class="fp-form-error-message" id="<?php echo \esc_attr( $field_id ); ?>-error" role="alert" aria-live="polite"></span>
			<span class="fp-form-success-message" id="<?php echo \esc_attr( $field_id ); ?>-success" role="status" aria-live="polite"></span>
		</div>
		<?php
	}

	/**
	 * Render help icon with tooltip and optional modal documentation.
	 *
	 * @param string $tooltip_text  Short tooltip text.
	 * @param string $modal_title   Modal title (if modal content provided).
	 * @param string $modal_content Modal content HTML (optional).
	 * @param string $help_id       Unique ID for help element.
	 *
	 * @return void
	 */
	protected function render_help_icon( $tooltip_text, $modal_title = '', $modal_content = '', $help_id = '' ) {
		if ( empty( $help_id ) ) {
			$help_id = 'fp-help-' . \uniqid();
		}
		?>
		<span class="fp-help-icon-wrapper">
			<button 
				type="button"
				class="fp-help-icon" 
				data-help-id="<?php echo \esc_attr( $help_id ); ?>"
				aria-label="<?php echo \esc_attr( \sprintf( \__( 'Help: %s', 'fp-privacy' ), $tooltip_text ) ); ?>"
				aria-describedby="<?php echo \esc_attr( $help_id . '-tooltip' ); ?>"
			>
				<span class="dashicons dashicons-info"></span>
			</button>
			<span 
				id="<?php echo \esc_attr( $help_id . '-tooltip' ); ?>"
				class="fp-help-tooltip" 
				role="tooltip"
			>
				<?php echo \esc_html( $tooltip_text ); ?>
				<?php if ( ! empty( $modal_content ) ) : ?>
					<button 
						type="button"
						class="fp-help-learn-more" 
						data-modal-title="<?php echo \esc_attr( $modal_title ); ?>"
						data-modal-content="<?php echo \esc_attr( $modal_content ); ?>"
					>
						<?php \esc_html_e( 'Learn more', 'fp-privacy' ); ?> →
					</button>
				<?php endif; ?>
			</span>
		</span>
		<?php
	}
}
















