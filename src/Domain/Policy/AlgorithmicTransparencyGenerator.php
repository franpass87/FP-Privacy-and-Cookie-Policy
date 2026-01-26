<?php
/**
 * Algorithmic Transparency Generator.
 *
 * @package FP\Privacy\Domain\Policy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Policy;

use FP\Privacy\Utils\Options;

/**
 * Generates algorithmic transparency disclosure content based on options.
 */
class AlgorithmicTransparencyGenerator {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Generate algorithmic transparency HTML.
	 *
	 * @param string $lang Language.
	 *
	 * @return string
	 */
	public function generate_algorithmic_transparency( $lang ) {
		$algorithmic_transparency_options = $this->options->get_algorithmic_transparency();

		if ( ! $algorithmic_transparency_options['enabled'] ) {
			return '';
		}

		$lang = $this->options->normalize_language( $lang );
		$is_italian = 'it_IT' === $lang;

		$html = '<h2 id="fp-privacy-algorithmic-transparency">';
		$html .= $is_italian
			? esc_html__( 'Trasparenza Algoritmica', 'fp-privacy' )
			: esc_html__( 'Algorithmic Transparency', 'fp-privacy' );
		$html .= '</h2>';

		// Main description.
		$description = $is_italian
			? __( 'In conformità con il Digital Omnibus e l\'articolo 22 del GDPR, forniamo informazioni trasparenti sulle decisioni automatizzate e sulla profilazione utilizzate nel trattamento dei dati personali.', 'fp-privacy' )
			: __( 'In compliance with the Digital Omnibus and GDPR Article 22, we provide transparent information about automated decisions and profiling used in personal data processing.', 'fp-privacy' );
		$html .= '<p>' . wp_kses_post( $description ) . '</p>';

		// System description.
		if ( ! empty( $algorithmic_transparency_options['system_description'] ) ) {
			$html .= '<h3>';
			$html .= $is_italian
				? esc_html__( 'Descrizione del Sistema Algoritmico', 'fp-privacy' )
				: esc_html__( 'Algorithmic System Description', 'fp-privacy' );
			$html .= '</h3>';
			$html .= '<p>' . wp_kses_post( $algorithmic_transparency_options['system_description'] ) . '</p>';
		}

		// System logic.
		if ( ! empty( $algorithmic_transparency_options['system_logic'] ) ) {
			$html .= '<h3>';
			$html .= $is_italian
				? esc_html__( 'Logica del Sistema', 'fp-privacy' )
				: esc_html__( 'System Logic', 'fp-privacy' );
			$html .= '</h3>';
			$html .= '<p>' . wp_kses_post( $algorithmic_transparency_options['system_logic'] ) . '</p>';
		}

		// System impact.
		if ( ! empty( $algorithmic_transparency_options['system_impact'] ) ) {
			$html .= '<h3>';
			$html .= $is_italian
				? esc_html__( 'Impatto del Sistema', 'fp-privacy' )
				: esc_html__( 'System Impact', 'fp-privacy' );
			$html .= '</h3>';
			$html .= '<p>' . wp_kses_post( $algorithmic_transparency_options['system_impact'] ) . '</p>';
		}

		// Human intervention notice.
		$intervention_text = $is_italian
			? __( 'Hai il diritto di richiedere l\'intervento umano nelle decisioni automatizzate e di esprimere il tuo punto di vista. Per esercitare questo diritto, contattaci utilizzando i dati di contatto forniti nella sezione "Titolare del Trattamento".', 'fp-privacy' )
			: __( 'You have the right to request human intervention in automated decisions and to express your point of view. To exercise this right, contact us using the contact details provided in the "Data Controller" section.', 'fp-privacy' );
		$html .= '<p><strong>' . wp_kses_post( $intervention_text ) . '</strong></p>';

		return $html;
	}
}



