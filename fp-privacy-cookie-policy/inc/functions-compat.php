<?php
/**
 * Compatibility helpers.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! function_exists( 'fp_privacy_array_get' ) ) {
/**
 * Safely get array value.
 *
 * @param array  $array   Source array.
 * @param string $key     Key.
 * @param mixed  $default Default.
 *
 * @return mixed
 */
function fp_privacy_array_get( $array, $key, $default = null ) {
return isset( $array[ $key ] ) ? $array[ $key ] : $default;
}
}
