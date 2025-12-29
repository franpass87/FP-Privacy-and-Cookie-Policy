<?php
/**
 * AI disclosure generator for AI Act compliance.
 *
 * @package FP\Privacy\Domain\Policy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Policy;

use FP\Privacy\Utils\Options;

/**
 * Generates AI disclosure sections for privacy policies.
 * Complies with AI Act Art. 13 and GDPR Art. 13.2(f).
 */
class AIDisclosureGenerator {
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
	 * Generate AI disclosure section HTML.
	 *
	 * @param string $lang Language code.
	 *
	 * @return string HTML content or empty string if AI not enabled.
	 */
	public function generate_ai_disclosure( $lang ): string {
		$ai_config = $this->options->get_ai_disclosure();

		// Check if AI disclosure is enabled.
		if ( empty( $ai_config['enabled'] ) || ! $ai_config['enabled'] ) {
			return '';
		}

		$lang = $this->options->normalize_language( $lang );

		// Get AI-specific texts for language.
		$ai_texts = isset( $ai_config['texts'][ $lang ] ) && is_array( $ai_config['texts'][ $lang ] )
			? $ai_config['texts'][ $lang ]
			: $this->get_default_ai_texts( $lang );

		$html = '<h2 id="fp-privacy-ai-disclosure">' . esc_html( $ai_texts['title'] ) . '</h2>';

		// Main description.
		if ( ! empty( $ai_texts['description'] ) ) {
			$html .= '<p>' . wp_kses_post( $ai_texts['description'] ) . '</p>';
		}

		// AI systems used.
		if ( ! empty( $ai_config['systems'] ) && is_array( $ai_config['systems'] ) ) {
			$html .= '<h3>' . esc_html( $ai_texts['systems_title'] ?? __( 'Sistemi AI utilizzati', 'fp-privacy' ) ) . '</h3>';
			$html .= '<ul>';

			foreach ( $ai_config['systems'] as $system ) {
				if ( ! is_array( $system ) || empty( $system['name'] ) ) {
					continue;
				}

				$name = esc_html( $system['name'] );
				$purpose = ! empty( $system['purpose'] ) ? esc_html( $system['purpose'] ) : '';
				$risk_level = ! empty( $system['risk_level'] ) ? esc_html( $system['risk_level'] ) : '';

				$html .= '<li>';
				$html .= '<strong>' . $name . '</strong>';

				if ( $purpose ) {
					$html .= ' — ' . $purpose;
				}

				if ( $risk_level ) {
					$html .= ' (' . sprintf(
						/* translators: %s: risk level */
						esc_html__( 'Livello di rischio: %s', 'fp-privacy' ),
						$risk_level
					) . ')';
				}

				$html .= '</li>';
			}

			$html .= '</ul>';
		}

		// Automated decision-making.
		if ( ! empty( $ai_config['automated_decisions'] ) && $ai_config['automated_decisions'] ) {
			$html .= '<h3>' . esc_html( $ai_texts['automated_title'] ?? __( 'Decisioni automatizzate', 'fp-privacy' ) ) . '</h3>';

			$automated_text = ! empty( $ai_texts['automated_description'] )
				? $ai_texts['automated_description']
				: $this->get_default_automated_text( $lang );

			$html .= '<p>' . wp_kses_post( $automated_text ) . '</p>';
		}

		// Profiling.
		if ( ! empty( $ai_config['profiling'] ) && $ai_config['profiling'] ) {
			$html .= '<h3>' . esc_html( $ai_texts['profiling_title'] ?? __( 'Profilazione', 'fp-privacy' ) ) . '</h3>';

			$profiling_text = ! empty( $ai_texts['profiling_description'] )
				? $ai_texts['profiling_description']
				: $this->get_default_profiling_text( $lang );

			$html .= '<p>' . wp_kses_post( $profiling_text ) . '</p>';
		}

		// User rights.
		$html .= '<h3>' . esc_html( $ai_texts['rights_title'] ?? __( 'I tuoi diritti', 'fp-privacy' ) ) . '</h3>';

		$rights_text = ! empty( $ai_texts['rights_description'] )
			? $ai_texts['rights_description']
			: $this->get_default_rights_text( $lang );

		$html .= '<p>' . wp_kses_post( $rights_text ) . '</p>';

		// Contact information.
		if ( ! empty( $ai_texts['contact_text'] ) ) {
			$dpo_email = $this->options->get( 'dpo_email', '' );
			$privacy_email = $this->options->get( 'privacy_email', '' );
			$contact_email = $dpo_email ? $dpo_email : $privacy_email;

			if ( $contact_email ) {
				$html .= '<p>' . sprintf(
					wp_kses_post( $ai_texts['contact_text'] ),
					'<a href="mailto:' . esc_attr( $contact_email ) . '">' . esc_html( $contact_email ) . '</a>'
				) . '</p>';
			}
		}

		return $html;
	}

	/**
	 * Get default AI texts for language.
	 *
	 * @param string $lang Language code.
	 *
	 * @return array<string, string>
	 */
	private function get_default_ai_texts( string $lang ): array {
		if ( 'it_IT' === $lang ) {
			return array(
				'title'                  => __( 'Trattamento dati per sistemi AI', 'fp-privacy' ),
				'description'            => __( 'Utilizziamo sistemi di intelligenza artificiale (AI) e machine learning (ML) per migliorare i nostri servizi, personalizzare l\'esperienza utente e ottimizzare le operazioni. Questa sezione descrive come utilizziamo queste tecnologie e i tuoi diritti in relazione al trattamento automatizzato dei dati.', 'fp-privacy' ),
				'systems_title'          => __( 'Sistemi AI utilizzati', 'fp-privacy' ),
				'automated_title'        => __( 'Decisioni automatizzate', 'fp-privacy' ),
				'profiling_title'       => __( 'Profilazione', 'fp-privacy' ),
				'rights_title'          => __( 'I tuoi diritti', 'fp-privacy' ),
				'automated_description'  => __( 'Alcuni dei nostri sistemi AI possono essere utilizzati per prendere decisioni automatizzate che influenzano la tua esperienza sul sito. Queste decisioni sono basate su algoritmi che analizzano i tuoi dati di utilizzo, preferenze e comportamento. Hai il diritto di non essere sottoposto a decisioni basate unicamente sul trattamento automatizzato, compresa la profilazione, che producono effetti giuridici che ti riguardano o che incidono in modo analogo significativamente su di te, salvo determinate eccezioni previste dalla legge.', 'fp-privacy' ),
				'profiling_description' => __( 'Utilizziamo tecniche di profilazione per analizzare le tue preferenze, interessi e comportamento al fine di personalizzare contenuti, raccomandazioni e pubblicità. La profilazione viene effettuata solo quando basata su una base giuridica valida (tipicamente il tuo consenso o i nostri interessi legittimi) e con adeguate garanzie per i tuoi diritti.', 'fp-privacy' ),
				'rights_description'    => __( 'Hai il diritto di: (1) ottenere informazioni sulla logica utilizzata nei processi decisionali automatizzati; (2) esprimere il tuo punto di vista e contestare le decisioni automatizzate; (3) richiedere l\'intervento umano nelle decisioni automatizzate; (4) opporti al trattamento basato su profilazione per scopi di marketing diretto; (5) revocare il consenso al trattamento basato su AI quando applicabile.', 'fp-privacy' ),
				'contact_text'          => __( 'Per esercitare i tuoi diritti o per maggiori informazioni sui sistemi AI utilizzati, contattaci all\'indirizzo %s.', 'fp-privacy' ),
			);
		}

		// Default English.
		return array(
			'title'                  => __( 'AI and Machine Learning Data Processing', 'fp-privacy' ),
			'description'            => __( 'We use artificial intelligence (AI) and machine learning (ML) systems to improve our services, personalize user experience, and optimize operations. This section describes how we use these technologies and your rights regarding automated data processing.', 'fp-privacy' ),
			'systems_title'          => __( 'AI Systems Used', 'fp-privacy' ),
			'automated_title'        => __( 'Automated Decision-Making', 'fp-privacy' ),
			'profiling_title'       => __( 'Profiling', 'fp-privacy' ),
			'rights_title'          => __( 'Your Rights', 'fp-privacy' ),
			'automated_description'  => __( 'Some of our AI systems may be used to make automated decisions that affect your experience on the site. These decisions are based on algorithms that analyze your usage data, preferences, and behavior. You have the right not to be subject to decisions based solely on automated processing, including profiling, which produce legal effects concerning you or similarly significantly affect you, except in certain cases provided by law.', 'fp-privacy' ),
			'profiling_description' => __( 'We use profiling techniques to analyze your preferences, interests, and behavior to personalize content, recommendations, and advertising. Profiling is only performed when based on a valid legal basis (typically your consent or our legitimate interests) and with adequate safeguards for your rights.', 'fp-privacy' ),
			'rights_description'    => __( 'You have the right to: (1) obtain information about the logic used in automated decision-making processes; (2) express your point of view and contest automated decisions; (3) request human intervention in automated decisions; (4) object to processing based on profiling for direct marketing purposes; (5) withdraw consent to AI-based processing when applicable.', 'fp-privacy' ),
			'contact_text'          => __( 'To exercise your rights or for more information about the AI systems used, contact us at %s.', 'fp-privacy' ),
		);
	}

	/**
	 * Get default automated decision-making text.
	 *
	 * @param string $lang Language code.
	 *
	 * @return string
	 */
	private function get_default_automated_text( string $lang ): string {
		if ( 'it_IT' === $lang ) {
			return __( 'Alcuni dei nostri sistemi AI possono essere utilizzati per prendere decisioni automatizzate che influenzano la tua esperienza sul sito. Queste decisioni sono basate su algoritmi che analizzano i tuoi dati di utilizzo, preferenze e comportamento. Hai il diritto di non essere sottoposto a decisioni basate unicamente sul trattamento automatizzato, compresa la profilazione, che producono effetti giuridici che ti riguardano o che incidono in modo analogo significativamente su di te, salvo determinate eccezioni previste dalla legge.', 'fp-privacy' );
		}

		return __( 'Some of our AI systems may be used to make automated decisions that affect your experience on the site. These decisions are based on algorithms that analyze your usage data, preferences, and behavior. You have the right not to be subject to decisions based solely on automated processing, including profiling, which produce legal effects concerning you or similarly significantly affect you, except in certain cases provided by law.', 'fp-privacy' );
	}

	/**
	 * Get default profiling text.
	 *
	 * @param string $lang Language code.
	 *
	 * @return string
	 */
	private function get_default_profiling_text( string $lang ): string {
		if ( 'it_IT' === $lang ) {
			return __( 'Utilizziamo tecniche di profilazione per analizzare le tue preferenze, interessi e comportamento al fine di personalizzare contenuti, raccomandazioni e pubblicità. La profilazione viene effettuata solo quando basata su una base giuridica valida (tipicamente il tuo consenso o i nostri interessi legittimi) e con adeguate garanzie per i tuoi diritti.', 'fp-privacy' );
		}

		return __( 'We use profiling techniques to analyze your preferences, interests, and behavior to personalize content, recommendations, and advertising. Profiling is only performed when based on a valid legal basis (typically your consent or our legitimate interests) and with adequate safeguards for your rights.', 'fp-privacy' );
	}

	/**
	 * Get default rights text.
	 *
	 * @param string $lang Language code.
	 *
	 * @return string
	 */
	private function get_default_rights_text( string $lang ): string {
		if ( 'it_IT' === $lang ) {
			return __( 'Hai il diritto di: (1) ottenere informazioni sulla logica utilizzata nei processi decisionali automatizzati; (2) esprimere il tuo punto di vista e contestare le decisioni automatizzate; (3) richiedere l\'intervento umano nelle decisioni automatizzate; (4) opporti al trattamento basato su profilazione per scopi di marketing diretto; (5) revocare il consenso al trattamento basato su AI quando applicabile.', 'fp-privacy' );
		}

		return __( 'You have the right to: (1) obtain information about the logic used in automated decision-making processes; (2) express your point of view and contest automated decisions; (3) request human intervention in automated decisions; (4) object to processing based on profiling for direct marketing purposes; (5) withdraw consent to AI-based processing when applicable.', 'fp-privacy' );
	}
}

