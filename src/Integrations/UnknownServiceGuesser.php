<?php
/**
 * Unknown service guesser.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

use function strtolower;
use function strpos;
use function substr;
use function preg_replace;
use function ucwords;
use function str_replace;
use function explode;
use function end;
use function in_array;

/**
 * Handles intelligent guessing of service properties from domain names.
 */
class UnknownServiceGuesser {
	/**
	 * Analyze a domain to extract service information intelligently.
	 *
	 * @param string $domain Domain to analyze.
	 *
	 * @return array<string, string>
	 */
	public static function analyze_service( $domain ) {
		$name          = self::guess_service_name( $domain );
		$category      = self::guess_category( $domain );
		$purpose       = self::guess_purpose( $domain, $category );
		$legal_basis   = self::guess_legal_basis( $category );
		$data_location = self::guess_data_location( $domain );

		return array(
			'name'          => $name,
			'category'      => $category,
			'provider'      => $name . ' (Third-party)',
			'policy_url'    => 'https://' . $domain . '/privacy',
			'legal_basis'   => $legal_basis,
			'purpose'       => $purpose,
			'data_location' => $data_location,
		);
	}

	/**
	 * Guess service category based on domain patterns.
	 *
	 * @param string $domain Domain to analyze.
	 *
	 * @return string
	 */
	public static function guess_category( $domain ) {
		$domain_lower = strtolower( $domain );

		// Analytics patterns
		$analytics_patterns = array( 'analytics', 'stats', 'track', 'metric', 'measure', 'insight', 'data' );
		foreach ( $analytics_patterns as $pattern ) {
			if ( false !== strpos( $domain_lower, $pattern ) ) {
				return 'statistics';
			}
		}

		// Marketing/Advertising patterns
		$marketing_patterns = array( 'ads', 'ad-', 'advert', 'marketing', 'promo', 'campaign', 'pixel', 'tag', 'retarget' );
		foreach ( $marketing_patterns as $pattern ) {
			if ( false !== strpos( $domain_lower, $pattern ) ) {
				return 'marketing';
			}
		}

		// CDN patterns (necessary)
		$cdn_patterns = array( 'cdn', 'static', 'assets', 'media', 'img', 'image' );
		foreach ( $cdn_patterns as $pattern ) {
			if ( false !== strpos( $domain_lower, $pattern ) ) {
				return 'necessary';
			}
		}

		// Chat/Support patterns
		$chat_patterns = array( 'chat', 'support', 'help', 'talk', 'message' );
		foreach ( $chat_patterns as $pattern ) {
			if ( false !== strpos( $domain_lower, $pattern ) ) {
				return 'marketing';
			}
		}

		// Video patterns
		$video_patterns = array( 'video', 'player', 'embed', 'stream' );
		foreach ( $video_patterns as $pattern ) {
			if ( false !== strpos( $domain_lower, $pattern ) ) {
				return 'marketing';
			}
		}

		// Default to marketing (most conservative for GDPR)
		return 'marketing';
	}

	/**
	 * Guess service purpose based on category and domain.
	 *
	 * @param string $domain   Domain name.
	 * @param string $category Service category.
	 *
	 * @return string
	 */
	public static function guess_purpose( $domain, $category ) {
		$purposes = array(
			'statistics' => 'Analytics and user behavior tracking',
			'marketing'  => 'Marketing, advertising, or user engagement',
			'necessary'  => 'Core website functionality and performance',
		);

		$base_purpose = isset( $purposes[ $category ] ) ? $purposes[ $category ] : 'Third-party service integration';

		// Add more specific purpose based on domain keywords
		$domain_lower = strtolower( $domain );

		if ( false !== strpos( $domain_lower, 'font' ) ) {
			return 'Web font delivery';
		}
		if ( false !== strpos( $domain_lower, 'map' ) ) {
			return 'Interactive map display';
		}
		if ( false !== strpos( $domain_lower, 'payment' ) || false !== strpos( $domain_lower, 'pay' ) ) {
			return 'Payment processing';
		}
		if ( false !== strpos( $domain_lower, 'chat' ) || false !== strpos( $domain_lower, 'support' ) ) {
			return 'Customer support and live chat';
		}
		if ( false !== strpos( $domain_lower, 'video' ) || false !== strpos( $domain_lower, 'player' ) ) {
			return 'Video content delivery';
		}

		return $base_purpose;
	}

	/**
	 * Guess legal basis based on category.
	 *
	 * @param string $category Service category.
	 *
	 * @return string
	 */
	public static function guess_legal_basis( $category ) {
		$legal_basis = array(
			'statistics' => 'Consent',
			'marketing'  => 'Consent',
			'necessary'  => 'Legitimate interest',
		);

		return isset( $legal_basis[ $category ] ) ? $legal_basis[ $category ] : 'Consent';
	}

	/**
	 * Guess data location based on domain TLD.
	 *
	 * @param string $domain Domain name.
	 *
	 * @return string
	 */
	public static function guess_data_location( $domain ) {
		// Extract TLD
		$parts = explode( '.', $domain );
		$tld   = end( $parts );

		$eu_tlds = array( 'eu', 'de', 'fr', 'it', 'es', 'nl', 'be', 'pl', 'se', 'dk', 'fi', 'at', 'ie', 'pt', 'gr', 'cz', 'ro', 'hu' );

		if ( in_array( $tld, $eu_tlds, true ) ) {
			return 'European Union';
		}

		$us_indicators = array( 'com', 'net', 'io', 'co' );
		if ( in_array( $tld, $us_indicators, true ) ) {
			return 'United States (presumed)';
		}

		$country_mapping = array(
			'uk' => 'United Kingdom',
			'ca' => 'Canada',
			'au' => 'Australia',
			'jp' => 'Japan',
			'cn' => 'China',
			'in' => 'India',
			'br' => 'Brazil',
			'ru' => 'Russia',
		);

		return isset( $country_mapping[ $tld ] ) ? $country_mapping[ $tld ] : 'Unknown';
	}

	/**
	 * Guess service name from domain.
	 *
	 * @param string $domain Domain name.
	 *
	 * @return string
	 */
	public static function guess_service_name( $domain ) {
		// Remove www. and common TLDs
		$name = preg_replace( '/^www\./', '', $domain );
		$name = preg_replace( '/\.(com|net|org|io|co|ai|app|dev)$/', '', $name );

		// Convert to title case
		$name = ucwords( str_replace( array( '-', '_', '.' ), ' ', $name ) );

		return $name;
	}
}















