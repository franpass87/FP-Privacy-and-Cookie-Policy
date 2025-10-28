<?php
/**
 * Remote translation helper.
 *
 * @package FP\Privacy\Utils
 */

namespace FP\Privacy\Utils;

use function add_query_arg;
use function explode;
use function html_entity_decode;
use function is_array;
use function is_wp_error;
use function json_decode;
use function sanitize_text_field;
use function strtolower;
use function str_replace;
use function trim;
use function wp_remote_get;
use function wp_remote_retrieve_body;

/**
 * Provides lightweight translation utilities for banner copy.
 */
class Translator {
    private const ENDPOINT = 'https://api.mymemory.translated.net/get';

    private const TRANSLATABLE_FIELDS = array(
        'title',
        'message',
        'btn_accept',
        'btn_reject',
        'btn_prefs',
        'modal_title',
        'modal_close',
        'modal_save',
        'revision_notice',
        'toggle_locked',
        'toggle_enabled',
        'debug_label',
        'link_privacy_policy',
        'link_cookie_policy',
    );

    /**
     * Translate banner texts between locales.
     *
     * @param array<string, string> $source      Source banner texts.
     * @param string                $source_lang Source locale.
     * @param string                $target_lang Target locale.
     *
     * @return array<string, string>
     */
    public function translate_banner_texts( array $source, $source_lang, $target_lang ) {
        $translations = array();

        foreach ( self::TRANSLATABLE_FIELDS as $field ) {
            $value = isset( $source[ $field ] ) ? (string) $source[ $field ] : '';

            if ( '' === trim( $value ) ) {
                $translations[ $field ] = $value;
                continue;
            }

            $translations[ $field ] = $this->translate_string( $value, $source_lang, $target_lang );
        }

        $translations['link_policy'] = isset( $source['link_policy'] ) ? (string) $source['link_policy'] : '';

        return $translations;
    }

    /**
     * Translate a single string.
     *
     * @param string $text        Text to translate.
     * @param string $source_lang Source locale.
     * @param string $target_lang Target locale.
     *
     * @return string
     */
    public function translate_string( $text, $source_lang, $target_lang ) {
        $source = $this->to_language_code( $source_lang );
        $target = $this->to_language_code( $target_lang );

        if ( '' === $source || '' === $target || $source === $target ) {
            return $text;
        }

        $url = add_query_arg(
            array(
                'q'        => $text,
                'langpair' => $source . '|' . $target,
                'de'       => 'support@fp-privacy.local',
            ),
            self::ENDPOINT
        );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $text;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! is_array( $data ) || empty( $data['responseData']['translatedText'] ) ) {
            return $text;
        }

        $translated = (string) $data['responseData']['translatedText'];
        $decoded    = html_entity_decode( $translated, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        if ( '' === trim( $decoded ) ) {
            return $text;
        }

        return $decoded;
    }

    /**
     * Convert a locale to a two-letter language code understood by the API.
     *
     * @param string $locale Locale string.
     *
     * @return string
     */
    private function to_language_code( $locale ) {
        $locale = strtolower( (string) $locale );
        $locale = str_replace( '-', '_', $locale );
        $parts  = explode( '_', $locale );

        return sanitize_text_field( $parts[0] ?? $locale );
    }
}
