<?php
/**
 * Cookies tab renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Admin\AdminUi;
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
		<div class="fp-privacy-tab-content" id="fp-privacy-tab-content-cookies" role="tabpanel" aria-labelledby="fp-privacy-tab-button-cookies" data-tab-content="cookies">
			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Granularità consenso (EDPB 2025)', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Consenti toggle per singolo servizio nel modal oltre alle categorie (linee guida EDPB).', 'fp-privacy' ); ?></p>
			<?php $this->render_sub_categories_settings( $data['options'] ); ?>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Blocco script', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Definisci handle, pattern URL e iframe da mettere in pausa finché l’utente non concede il consenso alla categoria.', 'fp-privacy' ); ?></p>
			<?php $this->render_script_blocking_settings( $languages, $script_rules, $script_categories ); ?>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Servizi rilevati', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Elenco dal detector: stato rilevato, fornitore e cookie noti. Utile per allineare script blocking e policy.', 'fp-privacy' ); ?></p>
			<?php $this->render_detected_services( $detected ); ?>
			</div>

			<p class="description"><?php \esc_html_e( 'Dopo modifiche ai servizi, rigenera i documenti dall’editor policy.', 'fp-privacy' ); ?></p>

			<?php
			AdminUi::render_submit_button(
				\__( 'Salva scheda Cookie', 'fp-privacy' ),
				'primary',
				array(
					'name'     => 'submit-cookies',
					'id'       => 'submit-cookies',
					'dashicon' => 'dashicons-saved',
				),
				false
			);
			?>
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
		$script_help_content = '<p>' . \esc_html__( 'Il blocco script è centrale per la conformità GDPR: impedisce il caricamento di script di terze parti (es. Google Analytics, Meta Pixel) finché l’utente non presta consenso.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Come configurare:', 'fp-privacy' ) . '</strong></p>' .
			'<ul>' .
			'<li>' . \esc_html__( 'Script handles: handle WordPress (es. "google-analytics", "facebook-pixel")', 'fp-privacy' ) . '</li>' .
			'<li>' . \esc_html__( 'Style handles: handle degli stili WordPress da bloccare', 'fp-privacy' ) . '</li>' .
			'<li>' . \esc_html__( 'Patterns: sottostringhe URL negli src (es. "google-analytics.com", "facebook.net")', 'fp-privacy' ) . '</li>' .
			'<li>' . \esc_html__( 'Iframe: sottostringhe negli src degli iframe da sostituire con prompt consenso', 'fp-privacy' ) . '</li>' .
			'</ul>' .
			'<p>' . \esc_html__( 'Le integrazioni rilevate possono precompilare handle e pattern suggeriti.', 'fp-privacy' ) . '</p>';
		?>
		<div class="fp-privacy-script-blocking-intro">
			<p class="description">
				<?php \esc_html_e( 'Metti in pausa script, stili o embed finché il visitatore non concede la categoria di consenso indicata.', 'fp-privacy' ); ?>
				<?php
				$this->render_help_icon(
					\__( 'Il blocco script impedisce il caricamento di risorse di terze parti finché non c’è consenso.', 'fp-privacy' ),
					\__( 'Blocco script', 'fp-privacy' ),
					$script_help_content,
					'help-script-blocking'
				);
				?>
			</p>
			<p class="description"><?php \esc_html_e( 'Le integrazioni rilevate precompilano handle e pattern; modifica una categoria per sovrascrivere le regole automatiche.', 'fp-privacy' ); ?></p>
		</div>
		<?php foreach ( $languages as $script_lang ) :
			$script_lang      = $this->options->normalize_language( $script_lang );
			$rules            = isset( $script_rules[ $script_lang ] ) ? $script_rules[ $script_lang ] : array();
			$categories_meta  = isset( $script_categories[ $script_lang ] ) ? $script_categories[ $script_lang ] : array();
			?>
			<div class="fp-privacy-language-panel" data-lang="<?php echo \esc_attr( $script_lang ); ?>">
				<h3><?php echo \esc_html( \sprintf( \__( 'Lingua: %s', 'fp-privacy' ), $script_lang ) ); ?></h3>
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
			<legend><?php echo \esc_html( \sprintf( \__( 'Categoria %s', 'fp-privacy' ), $label ) ); ?></legend>
			<label>
				<span><?php \esc_html_e( 'Script handle da bloccare (uno per riga)', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][script_handles]" rows="3" class="large-text"><?php echo \esc_textarea( $handles ); ?></textarea>
			</label>
			<label>
				<span><?php \esc_html_e( 'Style handle da bloccare (uno per riga)', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][style_handles]" rows="2" class="large-text"><?php echo \esc_textarea( $style_handles ); ?></textarea>
			</label>
			<label>
				<span><?php \esc_html_e( 'Sottostringhe negli src degli script', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][patterns]" rows="3" class="large-text"><?php echo \esc_textarea( $patterns ); ?></textarea>
				<span class="description"><?php \esc_html_e( 'Ogni script il cui src contiene uno di questi valori resta in pausa finché non c’è consenso.', 'fp-privacy' ); ?></span>
			</label>
			<label>
				<span><?php \esc_html_e( 'Sottostringhe negli src degli iframe', 'fp-privacy' ); ?></span>
				<textarea name="scripts[<?php echo \esc_attr( $lang ); ?>][<?php echo \esc_attr( $slug ); ?>][iframes]" rows="3" class="large-text"><?php echo \esc_textarea( $iframes ); ?></textarea>
				<span class="description"><?php \esc_html_e( 'Gli iframe il cui src contiene uno di questi valori vengono sostituiti con un invito al consenso.', 'fp-privacy' ); ?></span>
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
		<table class="widefat fp-privacy-detected fp-privacy-table">
		<thead>
		<tr>
		<th><?php \esc_html_e( 'Servizio', 'fp-privacy' ); ?></th>
		<th><?php \esc_html_e( 'Categoria', 'fp-privacy' ); ?></th>
		<th><?php \esc_html_e( 'Rilevato', 'fp-privacy' ); ?></th>
		<th><?php \esc_html_e( 'Fornitore', 'fp-privacy' ); ?></th>
		<th><?php \esc_html_e( 'Cookie', 'fp-privacy' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $detected as $service ) : ?>
		<tr>
		<td><?php echo \esc_html( $service['name'] ); ?></td>
		<td><?php echo \esc_html( $service['category'] ); ?></td>
		<td><?php echo $service['detected'] ? '<span class="status-detected">' . \esc_html__( 'Sì', 'fp-privacy' ) . '</span>' : \esc_html__( 'No', 'fp-privacy' ); ?></td>
		<td><?php echo \esc_html( $service['provider'] ); ?></td>
		<td><?php echo \esc_html( implode( ', ', $service['cookies'] ) ); ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
		</table>
		<?php
	}

	/**
	 * Render sub-categories settings (EDPB 2025 granularity).
	 *
	 * @param array<string, mixed> $options Options.
	 *
	 * @return void
	 */
	private function render_sub_categories_settings( $options ) {
		$enable_sub_categories = isset( $options['enable_sub_categories'] ) ? (bool) $options['enable_sub_categories'] : false;

		$help_content = '<p>' . \esc_html__( 'La granularità avanzata del consenso (EDPB 2025) consente agli utenti di controllare individualmente ogni servizio rilevato, non solo le categorie principali.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Come funziona:', 'fp-privacy' ) . '</strong></p>' .
			'<ul>' .
			'<li>' . \esc_html__( 'Quando abilitato, ogni servizio rilevato (es: Google Analytics 4, Google Tag Manager, Facebook Pixel) avrà un toggle individuale nella modal preferenze.', 'fp-privacy' ) . '</li>' .
			'<li>' . \esc_html__( 'Gli utenti possono accettare o rifiutare servizi specifici all\'interno di una categoria.', 'fp-privacy' ) . '</li>' .
			'<li>' . \esc_html__( 'Questo migliora la conformità con le linee guida EDPB 2025 sulla granularità del consenso.', 'fp-privacy' ) . '</li>' .
			'</ul>';

		?>
		<div class="fp-privacy-sub-categories">
			<?php
			$this->render_help_icon(
				\__( 'Abilita la granularità avanzata del consenso per conformità EDPB 2025.', 'fp-privacy' ),
				\__( 'Granularità Consenso Avanzata', 'fp-privacy' ),
				$help_content,
				'help-sub-categories'
			);
			?>

			<label>
				<span><?php \esc_html_e( 'Abilita toggle individuali per servizi', 'fp-privacy' ); ?></span>
				<input type="checkbox" name="enable_sub_categories" value="1" <?php \checked( $enable_sub_categories, true ); ?> />
			</label>
			<p class="description">
				<?php \esc_html_e( 'Quando abilitato, gli utenti possono controllare individualmente ogni servizio rilevato (es: GA4, GTM, Facebook Pixel) invece di accettare/rifiutare solo le categorie principali. Questo migliora la conformità con le linee guida EDPB 2025.', 'fp-privacy' ); ?>
			</p>
		</div>
		<?php
	}
}
















