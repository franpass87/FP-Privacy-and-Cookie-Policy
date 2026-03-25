<?php
/**
 * Banner tab renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Admin\AdminUi;
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
		<div class="fp-privacy-tab-content active" id="fp-privacy-tab-content-banner" role="tabpanel" aria-labelledby="fp-privacy-tab-button-banner" data-tab-content="banner">
			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Lingue', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Definisci quali lingue sono attive per banner, policy e testi: i codici locale (es. it_IT, en_US) determinano le varianti salvate nelle opzioni.', 'fp-privacy' ); ?></p>
			<div class="fp-privacy-fields-grid">
				<div class="fp-privacy-field fp-privacy-field--full">
					<label for="fp-privacy-languages-active"><?php \esc_html_e( 'Lingue attive', 'fp-privacy' ); ?></label>
					<input type="text" id="fp-privacy-languages-active" name="languages_active" class="regular-text" value="<?php echo \esc_attr( \implode( ',', $languages ) ); ?>" />
					<span class="fp-privacy-hint"><?php \esc_html_e( 'Codici locale separati da virgola (es. it_IT, en_US).', 'fp-privacy' ); ?></span>
				</div>
			</div>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Contenuto banner', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Testi del banner e del modal preferenze (titoli, pulsanti, link alle policy) per ogni lingua attiva.', 'fp-privacy' ); ?></p>
			<?php foreach ( $languages as $lang ) :
				$lang = $this->options->normalize_language( $lang );
				
				// Ottieni i default specifici per questa lingua
				$lang_specific_defaults = $this->options->get_banner_text( $lang );
				$text = isset( $options['banner_texts'][ $lang ] ) && \is_array( $options['banner_texts'][ $lang ] ) ? $options['banner_texts'][ $lang ] : array();
				$text = \wp_parse_args( $text, $lang_specific_defaults );
			?>
				<div class="fp-privacy-language-panel" data-lang="<?php echo \esc_attr( $lang ); ?>">
					<h3><?php echo \esc_html( \sprintf( \__( 'Lingua: %s', 'fp-privacy' ), $lang ) ); ?></h3>
					<?php $this->render_banner_text_fields( $lang, $text ); ?>
				</div>
			<?php endforeach; ?>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Anteprima banner', 'fp-privacy' ); ?></h2>
			<div class="fp-privacy-preview">
				<p class="description"><?php \esc_html_e( 'Modifica testi, colori e layout per vedere un’anteprima del banner cookie senza uscire dall’admin.', 'fp-privacy' ); ?></p>
				<?php $this->render_preview_controls( $languages, $primary_lang ); ?>
			</div>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Layout', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Tipo di banner (floating o barra), posizione verticale e sincronizzazione colori tra banner e modal.', 'fp-privacy' ); ?></p>
			<?php $this->render_layout_settings( $options['banner_layout'] ); ?>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Link nel footer', 'fp-privacy' ); ?></h2>
			<label>
				<input type="checkbox" name="footer_policy_links_enabled" value="1" <?php \checked( isset( $options['footer_policy_links_enabled'] ) ? (bool) $options['footer_policy_links_enabled'] : true ); ?> />
				<?php \esc_html_e( 'Mostra in fondo a ogni pagina i link Privacy Policy | Cookie Policy', 'fp-privacy' ); ?>
			</label>
			<p class="description"><?php \esc_html_e( 'Aggiunge un blocco con link alle policy prima della chiusura del body. Serve almeno una pagina policy: configura in FP Privacy → Privacy oppure in Impostazioni WordPress → Privacy.', 'fp-privacy' ); ?></p>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Palette colori', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Colori del banner e dei pulsanti (hex). Allineati al design system FP del plugin.', 'fp-privacy' ); ?></p>
			<?php $this->render_palette_settings( $options['banner_layout']['palette'] ); ?>
			</div>

			<?php
			AdminUi::render_submit_button(
				\__( 'Salva scheda Banner (usa Salva tutto in alto per l’intera configurazione)', 'fp-privacy' ),
				'primary',
				array(
					'name'     => 'submit-banner',
					'id'       => 'submit-banner',
					'dashicon' => 'dashicons-saved',
				),
				false
			);
			?>
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
			'title'          => \__( 'Titolo', 'fp-privacy' ),
			'message'        => \__( 'Messaggio', 'fp-privacy' ),
			'btn_accept'     => \__( 'Etichetta pulsante Accetta', 'fp-privacy' ),
			'btn_reject'     => \__( 'Etichetta pulsante Rifiuta', 'fp-privacy' ),
			'btn_prefs'      => \__( 'Etichetta pulsante Preferenze', 'fp-privacy' ),
			'revision_notice'=> \__( 'Messaggio avviso revisione policy', 'fp-privacy' ),
			'modal_title'    => \__( 'Titolo modal preferenze', 'fp-privacy' ),
			'modal_close'    => \__( 'Etichetta chiusura modal', 'fp-privacy' ),
			'modal_save'     => \__( 'Pulsante salva nel modal', 'fp-privacy' ),
			'toggle_locked'  => \__( 'Etichetta toggle bloccato (obbligatorio)', 'fp-privacy' ),
			'toggle_enabled' => \__( 'Etichetta toggle abilitato', 'fp-privacy' ),
			'debug_label'    => \__( 'Etichetta pannello debug', 'fp-privacy' ),
		);

		foreach ( $fields as $key => $label ) {
			$field_type = 'message' === $key ? 'textarea' : 'text';
			$this->render_text_field( "banner_texts[{$lang}][{$key}]", $label, $text[ $key ] ?? '', $field_type, $key );
		}

		// Tab Info — testo azienda
		$about_label = \__( 'Scheda Info — testo informativo / chi siamo', 'fp-privacy' );
		$this->render_text_field( "banner_texts[{$lang}][about_content]", $about_label, $text['about_content'] ?? '', 'textarea', 'about_content' );

		// Policy link texts
		?>
		<label>
		<span><?php \esc_html_e( 'Testo link Privacy Policy', 'fp-privacy' ); ?></span>
		<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][link_privacy_policy]" value="<?php echo \esc_attr( $text['link_privacy_policy'] ?? '' ); ?>" class="regular-text" data-field="link_privacy_policy" />
		</label>
		<label>
		<span><?php \esc_html_e( 'Testo link Cookie Policy', 'fp-privacy' ); ?></span>
		<input type="text" name="banner_texts[<?php echo \esc_attr( $lang ); ?>][link_cookie_policy]" value="<?php echo \esc_attr( $text['link_cookie_policy'] ?? '' ); ?>" class="regular-text" data-field="link_cookie_policy" />
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
			<div class="fp-preview-controls-left">
				<label for="fp-privacy-preview-language">
					<span><?php \esc_html_e( 'Lingua anteprima', 'fp-privacy' ); ?></span>
					<select id="fp-privacy-preview-language">
					<?php foreach ( $languages as $lang ) : ?>
					<option value="<?php echo \esc_attr( $lang ); ?>" <?php selected( $lang, $primary_lang ); ?>><?php echo \esc_html( $lang ); ?></option>
					<?php endforeach; ?>
					</select>
				</label>
				
				<div class="fp-preview-mode-toggle">
					<button type="button" class="fp-preview-mode-btn active" data-mode="desktop" aria-label="<?php \esc_attr_e( 'Vista desktop', 'fp-privacy' ); ?>">
						<span class="dashicons dashicons-desktop"></span>
						<?php \esc_html_e( 'Desktop', 'fp-privacy' ); ?>
					</button>
					<button type="button" class="fp-preview-mode-btn" data-mode="mobile" aria-label="<?php \esc_attr_e( 'Vista mobile', 'fp-privacy' ); ?>">
						<span class="dashicons dashicons-smartphone"></span>
						<?php \esc_html_e( 'Mobile', 'fp-privacy' ); ?>
					</button>
				</div>
			</div>
			
			<div class="fp-preview-controls-right">
				<button type="button" class="button button-secondary fp-preview-fullscreen-btn" id="fp-preview-fullscreen">
					<span class="dashicons dashicons-fullscreen-alt"></span>
					<?php \esc_html_e( 'Anteprima a schermo intero', 'fp-privacy' ); ?>
				</button>
				<button type="button" class="button button-secondary fp-preview-reset-btn" id="fp-preview-reset">
					<span class="dashicons dashicons-update"></span>
					<?php \esc_html_e( 'Reimposta anteprima', 'fp-privacy' ); ?>
				</button>
			</div>
		</div>
		<div class="fp-privacy-preview-frame" id="fp-privacy-preview-frame">
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
		<span><?php \esc_html_e( 'Tipo di visualizzazione', 'fp-privacy' ); ?></span>
		<select name="banner_layout[type]">
		<option value="floating" <?php \selected( $layout['type'], 'floating' ); ?>><?php \esc_html_e( 'Floating', 'fp-privacy' ); ?></option>
		<option value="bar" <?php \selected( $layout['type'], 'bar' ); ?>><?php \esc_html_e( 'Barra', 'fp-privacy' ); ?></option>
		</select>
		</label>
		<label>
		<span><?php \esc_html_e( 'Posizione', 'fp-privacy' ); ?></span>
		<select name="banner_layout[position]">
		<option value="top" <?php \selected( $layout['position'], 'top' ); ?>><?php \esc_html_e( 'Alto', 'fp-privacy' ); ?></option>
		<option value="bottom" <?php \selected( $layout['position'], 'bottom' ); ?>><?php \esc_html_e( 'Basso', 'fp-privacy' ); ?></option>
		</select>
		</label>
		<label>
		<input type="checkbox" name="banner_layout[sync_modal_and_button]" value="1" <?php \checked( $layout['sync_modal_and_button'], true ); ?> />
		<?php \esc_html_e( 'Sincronizza palette tra modal e banner', 'fp-privacy' ); ?>
		</label>
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
			'surface_bg'          => \__( 'Sfondo banner', 'fp-privacy' ),
			'surface_text'        => \__( 'Testo banner', 'fp-privacy' ),
			'button_primary_bg'   => \__( 'Sfondo pulsante primario', 'fp-privacy' ),
			'button_primary_tx'   => \__( 'Testo pulsante primario', 'fp-privacy' ),
			'button_secondary_bg' => \__( 'Sfondo pulsanti secondari', 'fp-privacy' ),
			'button_secondary_tx' => \__( 'Testo pulsanti secondari', 'fp-privacy' ),
			'link'                => \__( 'Colore link', 'fp-privacy' ),
			'border'              => \__( 'Bordo', 'fp-privacy' ),
			'focus'               => \__( 'Colore focus (accessibilità)', 'fp-privacy' ),
		);
		?>
		<div class="fp-privacy-palette">
		<?php foreach ( $palette as $key => $color ) : ?>
		<div class="fp-privacy-palette-item">
		<label>
			<strong class="fp-palette-label-text"><?php echo \esc_html( isset( $labels[ $key ] ) ? $labels[ $key ] : ucwords( str_replace( '_', ' ', $key ) ) ); ?></strong>
			<div class="fp-privacy-color-input-wrapper">
				<div class="fp-privacy-color-preview" style="background-color: <?php echo \esc_attr( $color ?: '#000000' ); ?>"></div>
				<input type="text" 
				       name="banner_layout[palette][<?php echo \esc_attr( $key ); ?>]" 
				       value="<?php echo \esc_attr( $color ); ?>" 
				       class="fp-privacy-hex-input" 
				       placeholder="#000000"
				       pattern="^#[0-9A-Fa-f]{6}$"
				       maxlength="7"
				       data-label="<?php echo \esc_attr( isset( $labels[ $key ] ) ? $labels[ $key ] : ucwords( str_replace( '_', ' ', $key ) ) ); ?>" />
			</div>
		</label>
		</div>
		<?php endforeach; ?>
		</div>
		<?php
	}
}
















