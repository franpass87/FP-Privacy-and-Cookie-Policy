<?php
/**
 * Policy generator.
 *
 * @package FP\Privacy\Admin
 */

namespace FP\Privacy\Admin;

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
 * View renderer.
 *
 * @var View
 */
private $view;

/**
 * Cached grouped services for the current request.
 *
 * @var array<string, array<int, array<string, mixed>>>|null
 */
private $grouped_services = array();

/**
 * Tracks whether grouped services have been hydrated during the request.
 *
 * @var array<string, bool>
 */
private $groups_refreshed = array();

/**
 * Constructor.
 *
 * @param Options          $options  Options.
 * @param DetectorRegistry $detector Detector.
 * @param View             $view     View renderer.
 */
public function __construct( Options $options, DetectorRegistry $detector, View $view ) {
$this->options  = $options;
$this->detector = $detector;
$this->view     = $view;
}

/**
 * Generate privacy policy HTML.
 *
 * @param string $lang Language.
 *
 * @return string
 */
public function generate_privacy_policy( $lang ) {
        return $this->view->render(
            'privacy-policy.php',
            array(
                'lang'             => $lang,
                'options'          => $this->options->all(),
                'groups'           => $this->group_services( false, $lang ),
                'generated_at'     => $this->get_policy_generated_at( 'privacy', $lang ),
                'categories_meta'  => $this->options->get_categories_for_language( $lang ),
            )
        );
}

/**
 * Generate cookie policy HTML.
 *
 * @param string $lang Language.
 *
 * @return string
 */
public function generate_cookie_policy( $lang ) {
        return $this->view->render(
            'cookie-policy.php',
            array(
                'lang'             => $lang,
                'options'          => $this->options->all(),
                'groups'           => $this->group_services( false, $lang ),
                'generated_at'     => $this->get_policy_generated_at( 'cookie', $lang ),
                'categories_meta'  => $this->options->get_categories_for_language( $lang ),
            )
        );
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
    $lang = $this->options->normalize_language( $lang ?: ($this->options->get_languages()[0] ?? ( function_exists( '\get_locale' ) ? \get_locale() : 'en_US' ) ) );

    $this->ensure_services_cache( $force, $lang );

    return isset( $this->grouped_services[ $lang ] ) ? $this->grouped_services[ $lang ] : array();
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
     * Ensure grouped services are hydrated for the current request.
     *
     * @param bool   $force Force cache refresh.
     * @param string $lang  Language override.
     *
     * @return void
     */
    private function ensure_services_cache( $force = false, $lang = '' ) {
        $lang = $this->options->normalize_language( $lang ?: ($this->options->get_languages()[0] ?? ( function_exists( '\get_locale' ) ? \get_locale() : 'en_US' ) ) );

        if ( isset( $this->groups_refreshed[ $lang ] ) && $this->groups_refreshed[ $lang ] && ! $force && isset( $this->grouped_services[ $lang ] ) ) {
            return;
        }

        $services = $this->detector->detect_services( $force );
        $groups   = array();
        $seen     = array();

        foreach ( $services as $service ) {
            $detected_flag = isset( $service['detected'] ) ? (bool) $service['detected'] : true;

            if ( ! $detected_flag ) {
                continue;
            }

            $service['detected'] = $detected_flag;
            $category          = isset( $service['category'] ) ? $service['category'] : 'uncategorized';

            if ( ! isset( $groups[ $category ] ) ) {
                $groups[ $category ] = array();
                $seen[ $category ]   = array();
            } elseif ( ! isset( $seen[ $category ] ) ) {
                $seen[ $category ] = array();
            }

            $key = '';
            if ( isset( $service['slug'] ) && '' !== $service['slug'] ) {
                $key = \sanitize_key( $service['slug'] );
            } elseif ( isset( $service['name'] ) ) {
                $key = \sanitize_key( $service['name'] );
            }

            if ( '' !== $key && isset( $seen[ $category ][ $key ] ) ) {
                continue;
            }

            if ( '' !== $key ) {
                $seen[ $category ][ $key ] = true;
            }

            $service['category'] = $category;
            $groups[ $category ][] = $service;
        }

        $configured = $this->options->get_categories_for_language( $lang );

        foreach ( $configured as $category => $meta ) {
            if ( ! isset( $groups[ $category ] ) ) {
                $groups[ $category ] = array();
            }

            if ( ! isset( $seen[ $category ] ) ) {
                $seen[ $category ] = array();
            }

            if ( empty( $meta['services'] ) || ! is_array( $meta['services'] ) ) {
                continue;
            }

            foreach ( $meta['services'] as $entry ) {
                $normalized = $this->normalize_manual_service( $entry, $category );

                if ( empty( $normalized ) ) {
                    continue;
                }

                $key = $normalized['slug'];

                if ( '' !== $key && isset( $seen[ $category ][ $key ] ) ) {
                    continue;
                }

                if ( '' !== $key ) {
                    $seen[ $category ][ $key ] = true;
                }

                $groups[ $category ][] = $normalized;
            }
        }

        foreach ( $groups as $category => $items ) {
            $groups[ $category ] = array_values( $items );
        }

        $this->grouped_services[ $lang ] = $groups;
        $this->groups_refreshed[ $lang ] = true;
    }

    /**
     * Normalize a manually configured service entry to match detector output.
     *
     * @param array<string, mixed> $entry     Service entry.
     * @param string               $category  Category slug.
     *
     * @return array<string, mixed>
     */
    private function normalize_manual_service( $entry, $category ) {
        if ( ! is_array( $entry ) ) {
            return array();
        }

        $key = '';

        if ( isset( $entry['key'] ) && '' !== $entry['key'] ) {
            $key = \sanitize_key( $entry['key'] );
        }

        if ( '' === $key && isset( $entry['name'] ) ) {
            $key = \sanitize_key( $entry['name'] );
        }

        $cookies = array();

        if ( isset( $entry['cookies'] ) && is_array( $entry['cookies'] ) ) {
            foreach ( $entry['cookies'] as $cookie ) {
                if ( ! is_array( $cookie ) ) {
                    continue;
                }

                $cookies[] = array(
                    'name'        => isset( $cookie['name'] ) ? (string) $cookie['name'] : '',
                    'domain'      => isset( $cookie['domain'] ) ? (string) $cookie['domain'] : '',
                    'duration'    => isset( $cookie['duration'] ) ? (string) $cookie['duration'] : '',
                    'description' => isset( $cookie['description'] ) ? (string) $cookie['description'] : '',
                );
            }
        }

        $consent_signals = array();

        if ( isset( $entry['uses_consent_mode'] ) && is_array( $entry['uses_consent_mode'] ) ) {
            $allowed_signals = array( 'analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization', 'functionality_storage', 'personalization_storage', 'security_storage' );

            foreach ( $entry['uses_consent_mode'] as $signal_key => $signal_value ) {
                $candidate = '';

                if ( is_string( $signal_key ) && '' !== $signal_key && ! is_numeric( $signal_key ) ) {
                    $candidate = \sanitize_text_field( $signal_key );

                    if ( is_array( $signal_value ) ) {
                        $enabled = false;

                        foreach ( $signal_value as $flag ) {
                            if ( \rest_sanitize_boolean( $flag ) ) {
                                $enabled = true;
                                break;
                            }
                        }
                    } else {
                        $enabled = (bool) \rest_sanitize_boolean( $signal_value );
                    }

                    if ( ! $enabled ) {
                        continue;
                    }
                } else {
                    $candidate = \sanitize_text_field( (string) $signal_value );
                }

                if ( '' === $candidate ) {
                    continue;
                }

                if ( in_array( $candidate, $allowed_signals, true ) && ! in_array( $candidate, $consent_signals, true ) ) {
                    $consent_signals[] = $candidate;
                }
            }
        }

        $name = isset( $entry['name'] ) ? (string) $entry['name'] : '';

        if ( '' === $key && '' === $name ) {
            return array();
        }

        $service = array(
            'slug'             => $key,
            'name'             => $name,
            'provider'         => isset( $entry['provider'] ) ? (string) $entry['provider'] : '',
            'purpose'          => isset( $entry['purpose'] ) ? (string) $entry['purpose'] : '',
            'policy_url'       => isset( $entry['policy_url'] ) ? (string) $entry['policy_url'] : '',
            'retention'        => isset( $entry['retention'] ) ? (string) $entry['retention'] : '',
            'legal_basis'      => isset( $entry['legal_basis'] ) ? (string) $entry['legal_basis'] : '',
            'data_collected'   => isset( $entry['data_collected'] ) ? (string) $entry['data_collected'] : '',
            'data_transfer'    => isset( $entry['data_transfer'] ) ? (string) $entry['data_transfer'] : '',
            'uses_consent_mode'=> $consent_signals,
            'cookies'          => $cookies,
            'category'         => $category,
            'detected'         => true,
        );

        return $service;
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
}
