<?php
/**
 * Algorithmic transparency value object.
 * Complies with Digital Omnibus and GDPR Art. 22.
 *
 * @package FP\Privacy\Domain\ValueObjects
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\ValueObjects;

/**
 * Value object for algorithmic transparency configuration.
 */
class AlgorithmicTransparency {
	/**
	 * Whether automated decision-making is used.
	 *
	 * @var bool
	 */
	private $automated_decisions_enabled;

	/**
	 * Description of automated decision-making logic.
	 *
	 * @var string
	 */
	private $decision_logic_description;

	/**
	 * Whether profiling is used.
	 *
	 * @var bool
	 */
	private $profiling_enabled;

	/**
	 * Description of profiling logic.
	 *
	 * @var string
	 */
	private $profiling_description;

	/**
	 * Whether human intervention is available.
	 *
	 * @var bool
	 */
	private $human_intervention_available;

	/**
	 * Link to detailed algorithm information (optional).
	 *
	 * @var string
	 */
	private $algorithm_details_url;

	/**
	 * Constructor.
	 *
	 * @param bool   $automated_decisions_enabled Whether automated decisions are enabled.
	 * @param string $decision_logic_description  Description of decision logic.
	 * @param bool   $profiling_enabled            Whether profiling is enabled.
	 * @param string $profiling_description        Description of profiling.
	 * @param bool   $human_intervention_available Whether human intervention is available.
	 * @param string $algorithm_details_url        URL to detailed algorithm information.
	 */
	public function __construct(
		bool $automated_decisions_enabled = false,
		string $decision_logic_description = '',
		bool $profiling_enabled = false,
		string $profiling_description = '',
		bool $human_intervention_available = true,
		string $algorithm_details_url = ''
	) {
		$this->automated_decisions_enabled = $automated_decisions_enabled;
		$this->decision_logic_description  = \sanitize_textarea_field( $decision_logic_description );
		$this->profiling_enabled           = $profiling_enabled;
		$this->profiling_description       = \sanitize_textarea_field( $profiling_description );
		$this->human_intervention_available = $human_intervention_available;
		$this->algorithm_details_url       = \esc_url_raw( $algorithm_details_url );
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Data array.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			isset( $data['automated_decisions_enabled'] ) ? (bool) $data['automated_decisions_enabled'] : false,
			isset( $data['decision_logic_description'] ) ? (string) $data['decision_logic_description'] : '',
			isset( $data['profiling_enabled'] ) ? (bool) $data['profiling_enabled'] : false,
			isset( $data['profiling_description'] ) ? (string) $data['profiling_description'] : '',
			isset( $data['human_intervention_available'] ) ? (bool) $data['human_intervention_available'] : true,
			isset( $data['algorithm_details_url'] ) ? (string) $data['algorithm_details_url'] : ''
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'automated_decisions_enabled' => $this->automated_decisions_enabled,
			'decision_logic_description'  => $this->decision_logic_description,
			'profiling_enabled'           => $this->profiling_enabled,
			'profiling_description'       => $this->profiling_description,
			'human_intervention_available' => $this->human_intervention_available,
			'algorithm_details_url'       => $this->algorithm_details_url,
		);
	}

	/**
	 * Get automated decisions enabled.
	 *
	 * @return bool
	 */
	public function is_automated_decisions_enabled(): bool {
		return $this->automated_decisions_enabled;
	}

	/**
	 * Get decision logic description.
	 *
	 * @return string
	 */
	public function get_decision_logic_description(): string {
		return $this->decision_logic_description;
	}

	/**
	 * Get profiling enabled.
	 *
	 * @return bool
	 */
	public function is_profiling_enabled(): bool {
		return $this->profiling_enabled;
	}

	/**
	 * Get profiling description.
	 *
	 * @return string
	 */
	public function get_profiling_description(): string {
		return $this->profiling_description;
	}

	/**
	 * Get human intervention available.
	 *
	 * @return bool
	 */
	public function is_human_intervention_available(): bool {
		return $this->human_intervention_available;
	}

	/**
	 * Get algorithm details URL.
	 *
	 * @return string
	 */
	public function get_algorithm_details_url(): string {
		return $this->algorithm_details_url;
	}
}


