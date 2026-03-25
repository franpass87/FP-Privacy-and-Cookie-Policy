<?php
/**
 * Privacy tab renderer.
 *
 * @package FP\Privacy\Presentation\Admin\Views
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Views;

use FP\Privacy\Admin\AdminUi;
use FP\Privacy\Utils\Options;

/**
 * Renders the Privacy tab content.
 */
class PrivacyTabRenderer extends SettingsRendererBase {
	/**
	 * Render privacy tab content.
	 *
	 * @param array<string, mixed> $data Tab data.
	 *
	 * @return void
	 */
	public function render( array $data ) {
		$options = $data['options'];
		?>
		<div class="fp-privacy-tab-content" id="fp-privacy-tab-content-privacy" role="tabpanel" aria-labelledby="fp-privacy-tab-button-privacy" data-tab-content="privacy">
			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Consent Mode — valori predefiniti', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Stato iniziale dei segnali Google Consent Mode v2 prima della scelta dell’utente (consigliato denied per analytics/ad finché non c’è consenso).', 'fp-privacy' ); ?></p>
			<?php $this->render_consent_mode_settings(); ?>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Global Privacy Control (GPC)', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Rispetta il segnale GPC del browser (Sec-GPC / navigator.globalPrivacyControl) come opt-out per lo storage non necessario.', 'fp-privacy' ); ?></p>
			<?php $this->render_gpc_settings( $options['gpc_enabled'] ); ?>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Conservazione e revisione', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Durata log consenso, modalità anteprima per admin e numero di revisione per forzare nuovo consenso.', 'fp-privacy' ); ?></p>
			<?php $this->render_retention_settings( $options['retention_days'], $options['preview_mode'], $options['consent_revision'] ); ?>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Titolare e DPO', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Dati del titolare del trattamento e del DPO usati nei testi policy e nelle informative.', 'fp-privacy' ); ?></p>
			<?php $this->render_organization_settings( $options ); ?>
			</div>

			<div class="fp-privacy-settings-section">
			<h2 class="fp-privacy-settings-section-title"><?php \esc_html_e( 'Trasparenza algoritmica (Digital Omnibus)', 'fp-privacy' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Sezione opzionale nella privacy policy su sistemi decisionali automatizzati (Direttiva Omnibus UE).', 'fp-privacy' ); ?></p>
			<?php $this->render_algorithmic_transparency_settings( $options ); ?>
			</div>

			<?php
			AdminUi::render_submit_button(
				\__( 'Salva scheda Privacy', 'fp-privacy' ),
				'primary',
				array(
					'name'     => 'submit-privacy',
					'id'       => 'submit-privacy',
					'dashicon' => 'dashicons-saved',
				),
				false
			);
			?>
		</div>
		<?php
	}

	/**
	 * Render consent mode defaults.
	 *
	 * Uses ConsentModeDefaults value object for type safety.
	 *
	 * @return void
	 */
	private function render_consent_mode_settings() {
		// Get consent mode defaults as value object, then convert to array for rendering.
		$consent_mode = $this->options->get_consent_mode_defaults();
		$defaults = $consent_mode->to_array();
		?>
		<div class="fp-privacy-consent-mode">
		<?php foreach ( $defaults as $key => $value ) : ?>
		<label>
		<span>
			<?php echo \esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?>
			<?php
			if ( 'analytics_storage' === $key || 'ad_storage' === $key ) {
				$consent_help = '<p>' . \esc_html__( 'Google Consent Mode v2 consente di impostare lo stato predefinito del consenso per tipi di raccolta dati prima che l’utente scelga.', 'fp-privacy' ) . '</p>' .
					'<p>' . \esc_html__( 'Valori denied impediscono l’esecuzione degli script finché non c’è consenso esplicito: approccio consigliato per GDPR.', 'fp-privacy' ) . '</p>';
				$this->render_help_icon(
					\__( 'Stato predefinito per questa categoria prima della scelta dell’utente.', 'fp-privacy' ),
					\__( 'Consent Mode v2 — default', 'fp-privacy' ),
					$consent_help,
					'help-consent-mode-' . $key
				);
			}
			?>
		</span>
		<select name="consent_mode_defaults[<?php echo \esc_attr( $key ); ?>]">
		<option value="granted" <?php \selected( $value, 'granted' ); ?>><?php \esc_html_e( 'Granted', 'fp-privacy' ); ?></option>
		<option value="denied" <?php \selected( $value, 'denied' ); ?>><?php \esc_html_e( 'Denied', 'fp-privacy' ); ?></option>
		</select>
		</label>
		<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render GPC settings.
	 *
	 * @param bool $gpc_enabled Whether GPC is enabled.
	 *
	 * @return void
	 */
	private function render_gpc_settings( $gpc_enabled ) {
		?>
		<label>
		<span>
			<?php \esc_html_e( 'Rispetta Global Privacy Control (nega storage non necessario se GPC=1)', 'fp-privacy' ); ?>
			<?php
			$this->render_help_icon(
				\__( 'GPC è un segnale del browser che comunica la preferenza privacy dell’utente.', 'fp-privacy' ),
				\__( 'Global Privacy Control (GPC)', 'fp-privacy' ),
				'<p>' . \esc_html__( 'Global Privacy Control (GPC) è uno standard proposto per comunicare le preferenze privacy ai siti. Se attivo, il plugin rispetta il segnale inviato dal browser.', 'fp-privacy' ) . '</p>' .
				'<p><strong>' . \esc_html__( 'Come funziona:', 'fp-privacy' ) . '</strong></p>' .
				'<ul>' .
				'<li>' . \esc_html__( 'Con segnale GPC (header Sec-GPC: 1 o navigator.globalPrivacyControl = true) viene negato lo storage non necessario.', 'fp-privacy' ) . '</li>' .
				'<li>' . \esc_html__( 'I default Google Consent Mode per analytics e advertising vanno su denied.', 'fp-privacy' ) . '</li>' .
				'<li>' . \esc_html__( 'Non obbligatorio in UE ma best practice per siti attenti alla privacy.', 'fp-privacy' ) . '</li>' .
				'</ul>',
				'help-gpc'
			);
			?>
		</span>
		<input type="checkbox" name="gpc_enabled" value="1" <?php \checked( $gpc_enabled, true ); ?> />
		</label>
		<p class="description">
		<?php \esc_html_e( 'Se attivo, un segnale GPC (header Sec-GPC: 1 o navigator.globalPrivacyControl) vale come opt-out per lo storage non necessario; i default Consent Mode per analytics e advertising sono denied. Non obbligatorio in UE ma consigliabile come buona pratica.', 'fp-privacy' ); ?>
		</p>
		<?php
	}

	/**
	 * Render retention and revision settings.
	 *
	 * @param int  $retention_days   Retention days.
	 * @param bool $preview_mode     Preview mode enabled.
	 * @param int  $consent_revision Current revision.
	 *
	 * @return void
	 */
	private function render_retention_settings( $retention_days, $preview_mode, $consent_revision ) {
		$retention_help_content = '<p>' . \esc_html__( 'La conservazione determina per quanti giorni restano i log di consenso nel database.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Giorni di conservazione:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Periodo di mantenimento dei log; in linea di massima coerente con finalità del trattamento e obblighi di prova.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Modalità anteprima:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Le scelte vengono registrate ma non applicate in produzione: utile per test.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Revisione consenso:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Incrementando il numero si invalidano i consensi esistenti e il banner torna obbligatorio (es. dopo cambi rilevanti alla policy).', 'fp-privacy' ) . '</p>';
		?>
		<div>
			<h3>
				<?php \esc_html_e( 'Conservazione e revisione', 'fp-privacy' ); ?>
				<?php
				$this->render_help_icon(
					\__( 'Durata log consenso e gestione revisione per rinnovare il consenso.', 'fp-privacy' ),
					\__( 'Conservazione e revisione', 'fp-privacy' ),
					$retention_help_content,
					'help-retention'
				);
				?>
			</h3>
		</div>
		<label>
		<span><?php \esc_html_e( 'Giorni di conservazione', 'fp-privacy' ); ?></span>
		<input type="number" min="1" name="retention_days" value="<?php echo \esc_attr( (string) $retention_days ); ?>" />
		</label>
		<label>
		<input type="checkbox" name="preview_mode" value="1" <?php \checked( $preview_mode, true ); ?> />
		<?php \esc_html_e( 'Abilita modalità anteprima (solo admin)', 'fp-privacy' ); ?>
		</label>
		<p><?php echo \esc_html( \sprintf( \__( 'Revisione consenso attuale: %d', 'fp-privacy' ), $consent_revision ) ); ?></p>
		<p><a class="button button-secondary" href="<?php echo \esc_url( \wp_nonce_url( \admin_url( 'admin-post.php?action=fp_privacy_bump_revision' ), 'fp_privacy_bump_revision' ) ); ?>"><?php \esc_html_e( 'Forza nuovo consenso (bump revision)', 'fp-privacy' ); ?></a></p>
		<?php
	}

	/**
	 * Render organization settings.
	 *
	 * @param array<string, mixed> $options Options.
	 *
	 * @return void
	 */
	private function render_organization_settings( $options ) {
		?>
		<div class="fp-privacy-org">
		<label><span><?php \esc_html_e( 'Ragione sociale / organizzazione', 'fp-privacy' ); ?></span><input type="text" name="org_name" value="<?php echo \esc_attr( $options['org_name'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'P. IVA / Codice fiscale', 'fp-privacy' ); ?></span><input type="text" name="vat" value="<?php echo \esc_attr( $options['vat'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'Sede legale / indirizzo', 'fp-privacy' ); ?></span><input type="text" name="address" value="<?php echo \esc_attr( $options['address'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'Nome DPO', 'fp-privacy' ); ?></span><input type="text" name="dpo_name" value="<?php echo \esc_attr( $options['dpo_name'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'Email DPO', 'fp-privacy' ); ?></span><input type="email" name="dpo_email" value="<?php echo \esc_attr( $options['dpo_email'] ); ?>" class="regular-text" /></label>
		<label><span><?php \esc_html_e( 'Email contatti privacy', 'fp-privacy' ); ?></span><input type="email" name="privacy_email" value="<?php echo \esc_attr( $options['privacy_email'] ); ?>" class="regular-text" /></label>
		</div>
		<?php
	}

	/**
	 * Render algorithmic transparency settings.
	 *
	 * @param array<string, mixed> $options Options.
	 *
	 * @return void
	 */
	private function render_algorithmic_transparency_settings( $options ) {
		$transparency = isset( $options['algorithmic_transparency'] ) && is_array( $options['algorithmic_transparency'] )
			? $options['algorithmic_transparency']
			: array(
				'enabled'            => false,
				'system_description' => '',
				'system_logic'       => '',
				'system_impact'      => '',
			);

		$enabled            = (bool) ( $transparency['enabled'] ?? false );
		$system_description = \esc_attr( $transparency['system_description'] ?? '' );
		$system_logic       = \esc_attr( $transparency['system_logic'] ?? '' );
		$system_impact      = \esc_attr( $transparency['system_impact'] ?? '' );

		$help_content = '<p>' . \esc_html__( 'La Direttiva Omnibus digitale (UE 2019/2161) e norme collegate richiedono trasparenza su decisioni algoritmiche e profilazione.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Abilita trasparenza algoritmica:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Aggiunge una sezione nella privacy policy sui sistemi algoritmici usati.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Descrizione del sistema:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Testo chiaro e sintetico su quali sistemi sono in uso.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Logica del sistema:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Criteri o regole principali con cui l’algoritmo produce decisioni o suggerimenti.', 'fp-privacy' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Impatto sulle persone:', 'fp-privacy' ) . '</strong> ' . \esc_html__( 'Effetti possibili del sistema sugli utenti.', 'fp-privacy' ) . '</p>';

		?>
		<div class="fp-privacy-algorithmic-transparency">
			<label>
				<span>
					<?php \esc_html_e( 'Abilita sezione trasparenza algoritmica', 'fp-privacy' ); ?>
					<?php
					$this->render_help_icon(
						\__( 'Testi per la sezione obbligatoria/fortemente consigliata su sistemi decisionali automatizzati (Omnibus digitale).', 'fp-privacy' ),
						\__( 'Trasparenza algoritmica', 'fp-privacy' ),
						$help_content,
						'help-algorithmic-transparency'
					);
					?>
				</span>
				<input type="checkbox" name="algorithmic_transparency[enabled]" value="1" <?php \checked( $enabled, true ); ?> />
			</label>
			<p class="description"><?php \esc_html_e( 'Inserisce nella privacy policy una sezione su sistemi algoritmici e relativi effetti.', 'fp-privacy' ); ?></p>

			<div class="fp-privacy-algorithmic-transparency-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
				<label>
					<span><?php \esc_html_e( 'Descrizione del sistema algoritmico', 'fp-privacy' ); ?></span>
					<textarea name="algorithmic_transparency[system_description]" class="large-text" rows="4"><?php echo \esc_textarea( $system_description ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Descrizione chiara e sintetica dei sistemi in uso.', 'fp-privacy' ); ?></p>
				</label>
				<label>
					<span><?php \esc_html_e( 'Logica del sistema algoritmico', 'fp-privacy' ); ?></span>
					<textarea name="algorithmic_transparency[system_logic]" class="large-text" rows="4"><?php echo \esc_textarea( $system_logic ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Criteri o regole principali con cui l’algoritmo produce decisioni o suggerimenti.', 'fp-privacy' ); ?></p>
				</label>
				<label>
					<span><?php \esc_html_e( 'Impatto del sistema sulle persone', 'fp-privacy' ); ?></span>
					<textarea name="algorithmic_transparency[system_impact]" class="large-text" rows="4"><?php echo \esc_textarea( $system_impact ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Effetti possibili del sistema sugli utenti.', 'fp-privacy' ); ?></p>
				</label>
			</div>
		</div>
		<script>
		(function() {
			var checkbox = document.querySelector('input[name="algorithmic_transparency[enabled]"]');
			var fields = document.querySelector('.fp-privacy-algorithmic-transparency-fields');
			if (checkbox && fields) {
				checkbox.addEventListener('change', function() {
					fields.style.display = this.checked ? '' : 'none';
				});
			}
		})();
		</script>
		<?php
	}
}
















