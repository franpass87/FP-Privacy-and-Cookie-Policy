<?php
/**
 * WordPress pages manager.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Interfaces\PageManagerInterface;
use WP_Post;

/**
 * Manages privacy and cookie policy pages.
 */
class PageManager implements PageManagerInterface {
	const PAGE_MANAGED_META_KEY = '_fp_privacy_managed_signature';

	/**
	 * Language normalizer.
	 *
	 * @var LanguageNormalizer
	 */
	private $normalizer;

	/**
	 * Constructor.
	 *
	 * @param LanguageNormalizer $normalizer Language normalizer.
	 */
	public function __construct( LanguageNormalizer $normalizer ) {
		$this->normalizer = $normalizer;
	}

	/**
	 * Ensure required pages exist for all languages.
	 *
	 * @param array<string, mixed> $pages     Current pages configuration.
	 * @param array<int, string>   $languages Active languages.
	 *
	 * @return array<string, mixed> Updated pages configuration.
	 */
	public function ensure_pages_exist( array $pages, array $languages ) {
		$pages = \wp_parse_args(
			$pages,
			array(
				'privacy_policy_page_id' => array(),
				'cookie_policy_page_id'  => array(),
			)
		);

		$updated = false;

		$map = array(
			'privacy_policy_page_id' => array(
				'title'     => \__( 'Privacy Policy', 'fp-privacy' ),
				'shortcode' => 'fp_privacy_policy',
			),
			'cookie_policy_page_id'  => array(
				'title'     => \__( 'Cookie Policy', 'fp-privacy' ),
				'shortcode' => 'fp_cookie_policy',
			),
		);

		foreach ( $map as $key => $config ) {
			foreach ( $languages as $language ) {
				$language = $this->normalizer->normalize( $language );
				$page_id  = isset( $pages[ $key ][ $language ] ) ? (int) $pages[ $key ][ $language ] : 0;

				$result = $this->ensure_page_exists( $page_id, $language, $config, count( $languages ) > 1 );

				if ( $result['updated'] ) {
					$pages[ $key ][ $language ] = $result['page_id'];
					$updated                     = true;
				}
			}
		}

		return $updated ? $pages : null;
	}

	/**
	 * Ensure a single page exists and is properly configured.
	 *
	 * @param int                  $page_id  Current page ID.
	 * @param string               $language Language code.
	 * @param array<string, string> $config   Page configuration (title, shortcode).
	 * @param bool                 $multilang Whether this is a multilingual setup.
	 *
	 * @return array{page_id: int, updated: bool}
	 */
	private function ensure_page_exists( $page_id, $language, array $config, $multilang = false ) {
		$post = $page_id ? \get_post( $page_id ) : null;

		if ( $post instanceof WP_Post && 'page' !== $post->post_type ) {
			$post = null;
		}

		$content = sprintf(
			'[%1$s lang="%2$s"]',
			$config['shortcode'],
			\esc_attr( $language )
		);

		if ( $post instanceof WP_Post && 'trash' === $post->post_status ) {
			$restored = \wp_untrash_post( $post->ID );

			if ( ! \is_wp_error( $restored ) && $restored ) {
				$post = \get_post( $post->ID );
			}
		}

		if ( $post instanceof WP_Post ) {
			$result = $this->update_existing_page( $post, $content );

			if ( $result ) {
				return array(
					'page_id' => $post->ID,
					'updated' => true,
				);
			}

			return array(
				'page_id' => $page_id,
				'updated' => false,
			);
		}

		$title = $config['title'];
		if ( $multilang ) {
			$title = sprintf(
				/* translators: %1$s: page title, %2$s: language code */
				\__( '%1$s (%2$s)', 'fp-privacy' ),
				$config['title'],
				$language
			);
		}

		$created = \wp_insert_post(
			array(
				'post_title'   => $title,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $content,
			)
		);

		if ( $created && ! \is_wp_error( $created ) ) {
			\update_post_meta( $created, self::PAGE_MANAGED_META_KEY, \hash( 'sha256', $content ) );

			return array(
				'page_id' => (int) $created,
				'updated' => true,
			);
		}

		return array(
			'page_id' => 0,
			'updated' => false,
		);
	}

	/**
	 * Update an existing page if it's managed by the plugin.
	 *
	 * @param WP_Post $post    Post object.
	 * @param string  $content Expected content.
	 *
	 * @return bool Whether the page was updated.
	 */
	private function update_existing_page( WP_Post $post, $content ) {
		$current_content    = trim( (string) $post->post_content );
		$expected_signature = \hash( 'sha256', $content );
		$stored_signature   = (string) \get_post_meta( $post->ID, self::PAGE_MANAGED_META_KEY, true );
		$current_signature  = '' !== $current_content ? \hash( 'sha256', $current_content ) : '';
		$is_managed         = '' !== $stored_signature && $current_signature && \hash_equals( $stored_signature, $current_signature );

		if ( $current_content === $content ) {
			if ( $stored_signature !== $expected_signature ) {
				\update_post_meta( $post->ID, self::PAGE_MANAGED_META_KEY, $expected_signature );
			}

			if ( 'publish' !== $post->post_status ) {
				$result = \wp_update_post(
					array(
						'ID'          => $post->ID,
						'post_status' => 'publish',
					),
					true
				);

				return $result && ! \is_wp_error( $result );
			}

			return false;
		}

		if ( ! $is_managed ) {
			if ( '' !== $stored_signature ) {
				\delete_post_meta( $post->ID, self::PAGE_MANAGED_META_KEY );
			}

			return false;
		}

		$result = \wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $content,
			),
			true
		);

		if ( $result && ! \is_wp_error( $result ) ) {
			\update_post_meta( $post->ID, self::PAGE_MANAGED_META_KEY, $expected_signature );

			return true;
		}

		return false;
	}

	/**
	 * Retrieve a policy page id for type and language.
	 *
	 * @param string               $type  privacy_policy|cookie_policy.
	 * @param string               $lang  Locale.
	 * @param array<string, mixed> $pages Pages configuration.
	 *
	 * @return int
	 */
	public function get_page_id( $type, $lang, array $pages ) {
		$lang = $this->normalizer->normalize( $lang );
		$key  = 'privacy_policy' === $type ? 'privacy_policy_page_id' : 'cookie_policy_page_id';
		$map  = isset( $pages[ $key ] ) && \is_array( $pages[ $key ] ) ? $pages[ $key ] : array();

		// First try to get the page ID for the specific language
		if ( ! empty( $map[ $lang ] ) ) {
			return (int) $map[ $lang ];
		}

		// If no specific language page, try to get any page for this type
		// but only if it's not the same as the other type
		$other_key = 'privacy_policy' === $type ? 'cookie_policy_page_id' : 'privacy_policy_page_id';
		$other_map = isset( $pages[ $other_key ] ) && \is_array( $pages[ $other_key ] ) ? $pages[ $other_key ] : array();
		
		foreach ( $map as $page_id ) {
			if ( $page_id ) {
				// Check if this page ID is not the same as any page in the other type
				$is_duplicate = false;
				foreach ( $other_map as $other_page_id ) {
					if ( $page_id == $other_page_id ) {
						$is_duplicate = true;
						break;
					}
				}
				
				// Only return this page ID if it's not a duplicate
				if ( ! $is_duplicate ) {
					return (int) $page_id;
				}
			}
		}

		return 0;
	}
}