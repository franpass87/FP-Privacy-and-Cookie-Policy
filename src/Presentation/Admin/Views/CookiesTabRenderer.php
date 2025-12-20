<?php
/**
 * Cookies tab renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Utils\Options;

/**
 * Renders the Cookies tab content.
 */
class CookiesTabRenderer extends SettingsRendererBase {
	/**
	 * Render cookies tab content.
	 *
	 * @param array<string, mixed> $data Tab data.
	 *
	 * @return void
	 */
	public function render( array $data ) {
		$languages         = $data['languages'];
		$script_rules      = $data['script_rules'];
		$script_categories = $data['script_categories'];
		$detected          = $data['detected'];
		?>
		<div class="fp-privacy-tab-content" data-tab-content="cookies">
			<h2><?php \esc_html_e( 'Script blocking', 'fp-privacy' ); ?></h2>
			<?php $this->render_script_blocking_settings( $languages, $script_rules, $script_categories ); ?>

			<h2><?php \esc_html_e( 'Detected services', 'fp-privacy' ); ?></h2>
			<?php $this->render_detected_services( $detected ); ?>

			<p class="description"><?php \esc_html_e( 'Use the policy editor to regenerate your documents after services change.', 'fp-privacy' ); ?></p>

			<?php \submit_button( \__( 'Salva impostazioni cookie', 'fp-privacy' ), 'primary', 'submit-cookies', false ); ?>
		</div>
		<?php
	}

	/**
	 * Render script blocking settings.
	 *
	 * @param array<int, string>   $languages         Active languages.
	 * @param array<string, mixed> $script_rules      Script rules by language.
	 * @param array<string, mixed> $script_categories Categories by language.
	 *
	 * @return void
	 */
	private function render_script_blocking_settings( $languages, $script_rules, $script_categories ) {
		?>
		<p class="description"><?php \esc_html_e( 'Pause specific scripts, styles, or embeds until the visitor grants the corresponding consent category.', 'fp-privacy' ); ?></p>
		<p class="description"><?php \esc_html_e( 'Detected integrations prefill suggested handles and patterns; edit a category to override the automatic rules.', 'fp-privacy' ); ?></p>
		<?php foreach ( $languages as $script_lang ) :
			$script_lang      = $this->options->normalize_language( $script_lang );
			$rules            = isset( $script_rules[ $script_lang ] ) ? $script_rules[ $script_lang ] : array();
			$categories_meta  = isset( $script_categories[ $script_lang ] ) ? $script_categories[ $script_lang ] : array();
			?>
			<div class="fp-privacy-language-panel" data-lang="<?php echo \esc_attr( $script_lang ); ?>">
				<h3><?php echo \esc_html( \sprintf( \__( 'Language: %s', 'fp-privacy' ), $script_lang ) ); ?></h3>
				<?php foreach ( $categories_meta as $slug => $meta ) :
					$category_rules = isset( $rules[ $slug ] ) && \is_array( $rules[ $slug ] ) ? $rules[ $slug ] : array();
					$this->render_category_blocking_rules( $script_lang, $slug, $meta['label'], $category_rules );
				endforeach; ?>
			</div>
		<?php endforeach;
	}

	/**
	 * Render blocking rules for a specific category.
	 *
	 * @param string               $lang           Language code.
	 * @param string               $slug           Category slug.
	 * @param string               $label          Category label.
	 * @param array<string, mixed> $category_rules Category rules.
	 *
	 * @return void
	 */
	private function render_category_blocking_rules( $lang, $slug, $label, $category_rules ) {
		$handles        = isset( $category_rules['script_handles'] ) && \is_array( $category_rules['script_handles'] ) ? implode( "\n", $category_rules['script_handles'] ) : '';
		$style_handles  = isset( $category_rules['style_handles'] ) && \is_array( $category_rules['style_handles'] ) ? implode( "\n", $category_rules['style_handles'] ) : '';
		$patterns       = isset( $category_rules['patterns'] ) && \is_array( $category_rules['patterns'] ) ? implode( "\n", $category_rules['patterns'] ) : '';
		$iframes        = isset( $category_rules['iframes'] ) && \is_array( $category_rules['iframes'] ) ? implode( "\n", $category_rules['iframes'] ) : '';
		?>
		<fieldset class="fp-privacy-script-category">
			<legend><?php echo \esc_html( \sprintf( \__( '%s category', 'fp-privacy' ), $label ) ); ?></legend>
			<label>
				<span><?php \esc_html_e( 'Script handles to block (one per line)', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][script_handles]" rows="3" class="large-text"><?php echo \esc_textarea( $handles ); ?></textarea>
			</label>
			<label>
				<span><?php \esc_html_e( 'Style handles to block (one per line)', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][style_handles]" rows="2" class="large-text"><?php echo \esc_textarea( $style_handles ); ?></textarea>
			</label>
			<label>
				<span><?php \esc_html_e( 'Script source substrings', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][patterns]" rows="3" class="large-text"><?php echo \esc_textarea( $patterns ); ?></textarea>
				<span class="description"><?php \esc_html_e( 'Any script tag whose src contains one of these values will be paused until consent is granted.', 'fp-privacy' ); ?></span>
			</label>
			<label>
				<span><?php \esc_html_e( 'Iframe source substrings', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][iframes]" rows="3" class="large-text"><?php echo \esc_textarea( $iframes ); ?></textarea>
				<span class="description"><?php \esc_html_e( 'Iframes whose src contains one of these values will be replaced with a consent prompt.', 'fp-privacy' ); ?></span>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render detected services table.
	 *
	 * @param array<int, array<string, mixed>> $detected Detected services.
	 *
	 * @return void
	 */
	private function render_detected_services( $detected ) {
		?>
		<table class="widefat fp-privacy-detected">
		<thead>
		<tr>
		<th><?php \esc_html_e( 'Service', 'fp-privacy' ); ?></th>
		<th><?php \esc_html_e( 'Category', 'fp-privacy' ); ?></th>
		<th><?php \esc_html_e( 'Detected', 'fp-privacy' ); ?></th>
		<th><?php \esc_html_e( 'Provider', 'fp-privacy' ); ?></th>
		<th><?php \esc_html_e( 'Cookies', 'fp-privacy' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $detected as $service ) : ?>
		<tr>
		<td><?php echo \esc_html( $service['name'] ); ?></td>
		<td><?php echo \esc_html( $service['category'] ); ?></td>
		<td><?php echo $service['detected'] ? '<span class="status-detected">' . \esc_html__( 'Yes', 'fp-privacy' ) . '</span>' : \esc_html__( 'No', 'fp-privacy' ); ?></td>
		<td><?php echo \esc_html( $service['provider'] ); ?></td>
		<td><?php echo \esc_html( implode( ', ', $service['cookies'] ) ); ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
		</table>
		<?php
	}
}
















