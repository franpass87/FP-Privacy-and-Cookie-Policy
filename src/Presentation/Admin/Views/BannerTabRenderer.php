<?php
/**
 * Banner tab renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Utils\Options;

/**
 * Renders the Banner tab content.
 */
class BannerTabRenderer extends SettingsRendererBase {
	/**
	 * Render banner tab content.
	 *
	 * @param array<string, mixed> $data Tab data.
	 *
	 * @return void
	 */
	public function render( array $data ) {
		$options      = $data['options'];
		$languages    = $data['languages'];
		$primary_lang = $data['primary_lang'];
		?>
		<div class="fp-privacy-tab-content active" data-tab-content="banner">
			<h2><?php \esc_html_e( 'Languages', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Provide active languages (comma separated locale codes).', 'fp-privacy' ); ?></p>
			<input type="text" name="languages_active" class="regular-text" value="<?php echo \esc_attr( implode( ',', $languages ) ); ?>" />

			<h2><?php \esc_html_e( 'Banner content', 'fp-privacy' ); ?></h2>
			<?php foreach ( $languages as $lang ) :
				$lang = $this->options->normalize_language( $lang );
				
				// Ottieni i default specifici per questa lingua
				$lang_specific_defaults = $this->options->get_banner_text( $lang );
				$text = isset( $options['banner_texts'][ $lang ] ) && \is_array( $options['banner_texts'][ $lang ] ) ? $options['banner_texts'][ $lang ] : array();
				$text = \wp_parse_args( $text, $lang_specific_defaults );
			?>
				<div class="fp-privacy-language-panel" data-lang="<?php echo \esc_attr( $lang ); ?>">
					<h3><?php echo \esc_html( \sprintf( \__( 'Language: %s', 'fp-privacy' ), $lang ) ); ?></h3>
					<?php $this->render_banner_text_fields( $lang, $text ); ?>
				</div>
			<?php endforeach; ?>

			<div class="fp-privacy-preview">
				<h2><?php \esc_html_e( 'Banner preview', 'fp-privacy' ); ?></h2>
				<p class="description"><?php \esc_html_e( 'Adjust copy, colors, and layout to see a live preview of the cookie banner.', 'fp-privacy' ); ?></p>
				<?php $this->render_preview_controls( $languages, $primary_lang ); ?>
			</div>

			<h2><?php \esc_html_e( 'Layout', 'fp-privacy' ); ?></h2>
			<?php $this->render_layout_settings( $options['banner_layout'] ); ?>

			<h2><?php \esc_html_e( 'Palette', 'fp-privacy' ); ?></h2>
			<?php $this->render_palette_settings( $options['banner_layout']['palette'] ); ?>

			<?php \submit_button( \__( 'Salva impostazioni banner', 'fp-privacy' ), 'primary', 'submit-banner', false ); ?>
		</div>
		<?php
	}

	/**
	 * Render banner text input fields.
	 *
	 * @param string               $lang Language code.
	 * @param array<string, string> $text Text values.
	 *
	 * @return void
	 */
	private function render_banner_text_fields( $lang, $text ) {
		$fields = array(
			'title'          => \__( 'Title', 'fp-privacy' ),
			'message'        => \__( 'Message', 'fp-privacy' ),
			'btn_accept'     => \__( 'Accept button label', 'fp-privacy' ),
			'btn_reject'     => \__( 'Reject button label', 'fp-privacy' ),
			'btn_prefs'      => \__( 'Preferences button label', 'fp-privacy' ),
			'revision_notice'=> \__( 'Revision notice message', 'fp-privacy' ),
			'modal_title'    => \__( 'Modal title', 'fp-privacy' ),
			'modal_close'    => \__( 'Modal close label', 'fp-privacy' ),
			'modal_save'     => \__( 'Modal save button', 'fp-privacy' ),
			'toggle_locked'  => \__( 'Locked toggle label', 'fp-privacy' ),
			'toggle_enabled' => \__( 'Enabled toggle label', 'fp-privacy' ),
			'debug_label'    => \__( 'Debug panel label', 'fp-privacy' ),
		);

		foreach ( $fields as $key => $label ) {
			$field_type = 'message' === $key ? 'textarea' : 'text';
			$this->render_text_field( "banner_texts[{$lang}][{$key}]", $label, $text[ $key ] ?? '', $field_type, $key );
		}

		// Policy link
		?>
		<label>
		<span><?php \esc_html_e( 'Policy link URL', 'fp-privacy' ); ?></span>
		<input type="url" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][link_policy]" value="<?php echo \esc_attr( $text['link_policy'] ?? '' ); ?>" class="regular-text" data-field="link_policy" />
		</label>
		<?php
	}

	/**
	 * Render preview controls.
	 *
	 * @param array<int, string> $languages    Active languages.
	 * @param string             $primary_lang Primary language.
	 *
	 * @return void
	 */
	private function render_preview_controls( $languages, $primary_lang ) {
		?>
		<div class="fp-privacy-preview-controls">
		<label for="fp-privacy-preview-language"><span><?php \esc_html_e( 'Preview language', 'fp-privacy' ); ?></span></label>
		<select id="fp-privacy-preview-language">
		<?php foreach ( $languages as $lang ) : ?>
		<option value="<?php echo \esc_attr( $lang ); ?>" <?php selected( $lang, $primary_lang ); ?>><?php echo \esc_html( $lang ); ?></option>
		<?php endforeach; ?>
		</select>
		</div>
		<div class="fp-privacy-preview-frame">
		<div id="fp-privacy-preview-banner" data-preview-lang="<?php echo \esc_attr( $primary_lang ); ?>"></div>
		</div>
		<?php
	}

	/**
	 * Render layout settings.
	 *
	 * @param array<string, mixed> $layout Layout configuration.
	 *
	 * @return void
	 */
	private function render_layout_settings( $layout ) {
		?>
		<div class="fp-privacy-layout">
		<label>
		<span><?php \esc_html_e( 'Display type', 'fp-privacy' ); ?></span>
		<select name="banner_layout[type]">
		<option value="floating" <?php \selected( $layout['type'], 'floating' ); ?>><?php \esc_html_e( 'Floating', 'fp-privacy' ); ?></option>
		<option value="bar" <?php \selected( $layout['type'], 'bar' ); ?>><?php \esc_html_e( 'Bar', 'fp-privacy' ); ?></option>
		</select>
		</label>
		<label>
		<span><?php \esc_html_e( 'Position', 'fp-privacy' ); ?></span>
		<select name="banner_layout[position]">
		<option value="top" <?php \selected( $layout['position'], 'top' ); ?>><?php \esc_html_e( 'Top', 'fp-privacy' ); ?></option>
		<option value="bottom" <?php \selected( $layout['position'], 'bottom' ); ?>><?php \esc_html_e( 'Bottom', 'fp-privacy' ); ?></option>
		</select>
		</label>
		<label>
		<input type="checkbox" name="banner_layout[sync_modal_and_button]" value="1" <?php \checked( $layout['sync_modal_and_button'], true ); ?> />
		<?php \esc_html_e( 'Synchronize modal and button palette', 'fp-privacy' ); ?>
		</label>
		<label>
		<input type="checkbox" name="banner_layout[enable_dark_mode]" value="1" <?php \checked( ! empty( $layout['enable_dark_mode'] ), true ); ?> />
		<?php \esc_html_e( 'Enable dark mode (automatically adjusts colors for dark backgrounds)', 'fp-privacy' ); ?>
		</label>
		<p class="description" style="margin-left: 25px;">
		<?php \esc_html_e( '⚠️ Activate this only if your site uses a dark theme and you want the banner to match. The palette will be automatically adjusted.', 'fp-privacy' ); ?>
		</p>
		</div>
		<?php
	}

	/**
	 * Render palette color settings.
	 *
	 * @param array<string, string> $palette Color palette.
	 *
	 * @return void
	 */
	private function render_palette_settings( $palette ) {
		// Descriptive labels for palette colors
		$labels = array(
			'surface_bg'          => \__( 'Banner background', 'fp-privacy' ),
			'surface_text'        => \__( 'Banner text', 'fp-privacy' ),
			'button_primary_bg'   => \__( 'Primary button background', 'fp-privacy' ),
			'button_primary_tx'   => \__( 'Primary button text', 'fp-privacy' ),
			'button_secondary_bg' => \__( 'Secondary buttons background', 'fp-privacy' ),
			'button_secondary_tx' => \__( 'Secondary buttons text', 'fp-privacy' ),
			'link'                => \__( 'Link color', 'fp-privacy' ),
			'border'              => \__( 'Border', 'fp-privacy' ),
			'focus'               => \__( 'Focus color', 'fp-privacy' ),
		);
		?>
		<div class="fp-privacy-palette">
		<?php foreach ( $palette as $key => $color ) : ?>
		<div class="fp-privacy-palette-item">
		<label>
			<strong class="fp-palette-label-text"><?php echo \esc_html( isset( $labels[ $key ] ) ? $labels[ $key ] : ucwords( str_replace( '_', ' ', $key ) ) ); ?></strong>
			<input type="text" 
			       name="banner_layout[palette][<?php echo \esc_attr( $key ); ?>]" 
			       value="<?php echo \esc_attr( $color ); ?>" 
			       class="fp-privacy-hex-input" 
			       placeholder="#000000"
			       pattern="^#[0-9A-Fa-f]{6}$"
			       maxlength="7"
			       data-label="<?php echo \esc_attr( isset( $labels[ $key ] ) ? $labels[ $key ] : ucwords( str_replace( '_', ' ', $key ) ) ); ?>" />
		</label>
		</div>
		<?php endforeach; ?>
		</div>
		<?php
	}
}
















