<?php
/**
 * Policy generator.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Domain\Policy\AIDisclosureGenerator;
use FP\Privacy\Domain\Policy\AlgorithmicTransparencyGenerator;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\View;

/**
 * Generates policy contents based on detected services and options.
 */
class PolicyGenerator {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Detector registry.
	 *
	 * @var DetectorRegistry
	 */
	private $detector;

	/**
	 * Service grouper.
	 *
	 * @var PolicyServiceGrouper
	 */
	private $service_grouper;

	/**
	 * View renderer.
	 *
	 * @var View
	 */
	private $view;

	/**
	 * Constructor.
	 *
	 * @param Options          $options  Options.
	 * @param DetectorRegistry $detector Detector.
	 * @param View             $view     View renderer.
	 */
	public function __construct( Options $options, DetectorRegistry $detector, View $view ) {
		$this->options        = $options;
		$this->detector       = $detector;
		$this->service_grouper = new PolicyServiceGrouper( $options, $detector );
		$this->view            = $view;
	}

/**
 * Generate privacy policy HTML.
 *
 * @param string $lang Language.
 *
 * @return string
 */
	public function generate_privacy_policy( $lang ) {
		try {
			// Ensure textdomain is loaded for the correct language
			$this->load_textdomain_for_language( $lang );

			$groups = $this->service_grouper->get_grouped_services( false, $lang );
			if ( ! is_array( $groups ) ) {
				$groups = array();
			}

			$categories_meta = $this->options->get_categories_for_language( $lang );
			if ( ! is_array( $categories_meta ) ) {
				$categories_meta = array();
			}

			$options = $this->options->all();
			if ( ! is_array( $options ) ) {
				$options = array();
			}

			// Generate AI disclosure section if enabled.
			$ai_disclosure_generator = new AIDisclosureGenerator( $this->options );
			$ai_disclosure_html = $ai_disclosure_generator->generate_ai_disclosure( $lang );

			// Generate algorithmic transparency section if enabled.
			$algorithmic_transparency_generator = new AlgorithmicTransparencyGenerator( $this->options );
			$algorithmic_transparency_html = $algorithmic_transparency_generator->generate_algorithmic_transparency( $lang );

			return $this->view->render(
				'privacy-policy.php',
				array(
					'lang'            => $lang,
					'options'        => $options,
					'groups'          => $groups,
					'generated_at'    => $this->get_policy_generated_at( 'privacy', $lang ),
					'categories_meta' => $categories_meta,
					'ai_disclosure'   => $ai_disclosure_html,
					'algorithmic_transparency' => $algorithmic_transparency_html,
				)
			);
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating privacy policy for %s: %s', $lang, $e->getMessage() ) );
			}
			return '';
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating privacy policy for %s: %s', $lang, $e->getMessage() ) );
			}
			return '';
		}
	}

	/**
	 * Generate cookie policy HTML.
	 *
	 * @param string $lang Language.
	 *
	 * @return string
	 */
	public function generate_cookie_policy( $lang ) {
		try {
			// Ensure textdomain is loaded for the correct language
			$this->load_textdomain_for_language( $lang );

			$groups = $this->service_grouper->get_grouped_services( false, $lang );
			if ( ! is_array( $groups ) ) {
				$groups = array();
			}

			$categories_meta = $this->options->get_categories_for_language( $lang );
			if ( ! is_array( $categories_meta ) ) {
				$categories_meta = array();
			}

			$options = $this->options->all();
			if ( ! is_array( $options ) ) {
				$options = array();
			}

			// Generate AI/ML cookie section if AI disclosure is enabled.
			$ai_cookie_html = '';
			$ai_config = isset( $options['ai_disclosure'] ) && is_array( $options['ai_disclosure'] ) ? $options['ai_disclosure'] : array();
			if ( ! empty( $ai_config['enabled'] ) ) {
				$ai_cookie_html = $this->generate_ai_cookie_section( $lang, $options );
			}

			return $this->view->render(
				'cookie-policy.php',
				array(
					'lang'            => $lang,
					'options'        => $options,
					'groups'          => $groups,
					'generated_at'    => $this->get_policy_generated_at( 'cookie', $lang ),
					'categories_meta' => $categories_meta,
					'ai_cookie_section' => $ai_cookie_html,
				)
			);
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating cookie policy for %s: %s', $lang, $e->getMessage() ) );
			}
			return '';
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating cookie policy for %s: %s', $lang, $e->getMessage() ) );
			}
			return '';
		}
	}

	/**
	 * Get grouped services.
	 *
	 * @param bool   $force Force cache refresh.
	 * @param string $lang  Language override.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function group_services( $force = false, $lang = '' ) {
		return $this->service_grouper->get_grouped_services( $force, $lang );
	}

	/**
	 * Export snapshot of services.
	 *
	 * @param bool $force Force a fresh detection.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function snapshot( $force = false ) {
		$services = $this->detector->detect_services( (bool) $force );

		return array_values(
			array_filter(
				$services,
				static function ( $service ) {
					return ! empty( $service['detected'] );
				}
			)
		);
	}

    /**
     * Retrieve the stored generation timestamp for a policy.
     *
     * @param string $type Policy type (privacy|cookie).
     * @param string $lang Language code.
     *
     * @return int
     */
    private function get_policy_generated_at( $type, $lang ) {
        $lang      = $this->options->normalize_language( $lang );
        $snapshots = $this->options->get( 'snapshots', array() );

        if ( ! is_array( $snapshots ) || empty( $snapshots['policies'][ $type ][ $lang ] ) ) {
            return 0;
        }

        $value = $snapshots['policies'][ $type ][ $lang ];

        return isset( $value['generated_at'] ) ? (int) $value['generated_at'] : 0;
    }

	/**
	 * Generate AI/ML cookie section for cookie policy.
	 *
	 * @param string $lang Language code.
	 * @param array<string, mixed> $options Options.
	 *
	 * @return string HTML content.
	 */
	private function generate_ai_cookie_section( string $lang, array $options ): string {
		$lang = $this->options->normalize_language( $lang );
		$is_italian = 'it_IT' === $lang;

		$html = '<h2 id="fp-cookie-ai-technologies">';
		$html .= $is_italian
			? esc_html__( 'Cookie e tecnologie AI', 'fp-privacy' )
			: esc_html__( 'AI and Machine Learning Cookies', 'fp-privacy' );
		$html .= '</h2>';

		$description = $is_italian
			? __( 'Alcuni dei nostri sistemi di intelligenza artificiale e machine learning utilizzano cookie e tecnologie simili per funzionare. Questi cookie vengono utilizzati per: (1) raccogliere dati di utilizzo necessari per addestrare e migliorare gli algoritmi; (2) personalizzare l\'esperienza utente basandosi su pattern di comportamento; (3) ottimizzare le prestazioni dei sistemi AI. Questi cookie sono trattati in conformità con l\'AI Act e il GDPR, e vengono utilizzati solo previo consenso esplicito quando richiesto dalla legge.', 'fp-privacy' )
			: __( 'Some of our artificial intelligence and machine learning systems use cookies and similar technologies to function. These cookies are used to: (1) collect usage data necessary to train and improve algorithms; (2) personalize user experience based on behavior patterns; (3) optimize AI system performance. These cookies are processed in compliance with the AI Act and GDPR, and are only used with explicit consent when required by law.', 'fp-privacy' );

		$html .= '<p>' . wp_kses_post( $description ) . '</p>';

		$rights_text = $is_italian
			? __( 'Hai il diritto di rifiutare i cookie utilizzati per sistemi AI, salvo quelli strettamente necessari per il funzionamento del servizio. La revoca del consenso ai cookie AI non influisce sulla tua capacità di utilizzare i servizi principali del sito.', 'fp-privacy' )
			: __( 'You have the right to refuse cookies used for AI systems, except those strictly necessary for service operation. Revoking consent to AI cookies does not affect your ability to use the site\'s main services.', 'fp-privacy' );

		$html .= '<p>' . wp_kses_post( $rights_text ) . '</p>';

		return $html;
	}

	/**
	 * Load textdomain for specific language using absolute path (junction-safe).
	 *
	 * @param string $lang Language code.
	 *
	 * @return void
	 */
	private function load_textdomain_for_language( $lang ) {
		$locale = $this->options->normalize_language( $lang );
		$mofile = FP_PRIVACY_PLUGIN_PATH . 'languages/fp-privacy-' . $locale . '.mo';

		if ( file_exists( $mofile ) ) {
			\load_textdomain( 'fp-privacy', $mofile );
		} else {
			// Fallback: try to load using standard WordPress method
			\load_plugin_textdomain(
				'fp-privacy',
				false,
				dirname( plugin_basename( FP_PRIVACY_PLUGIN_FILE ) ) . '/languages'
			);
		}
	}
}
