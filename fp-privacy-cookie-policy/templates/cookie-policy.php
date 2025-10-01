<?php
/**
 * Cookie policy template.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

$retention     = isset( $options['retention_days'] ) ? (int) $options['retention_days'] : 180;
$generated_at  = isset( $generated_at ) ? (int) $generated_at : 0;
$date_format   = (string) get_option( 'date_format' );
$time_format   = (string) get_option( 'time_format' );
$display_format = trim( $date_format . ' ' . $time_format );
$categories_meta = isset( $categories_meta ) && is_array( $categories_meta ) ? $categories_meta : array();

if ( ! function_exists( 'fp_privacy_format_service_cookies' ) ) {
    /**
     * Format service cookies into readable strings.
     *
     * @param mixed $cookies Raw cookies array.
     *
     * @return array<int, string>
     */
    function fp_privacy_format_service_cookies( $cookies ) {
        if ( ! is_array( $cookies ) ) {
            return array();
        }

        $formatted = array();

        foreach ( $cookies as $cookie ) {
            if ( ! is_array( $cookie ) ) {
                continue;
            }

            $name        = isset( $cookie['name'] ) ? (string) $cookie['name'] : '';
            $domain      = isset( $cookie['domain'] ) ? (string) $cookie['domain'] : '';
            $duration    = isset( $cookie['duration'] ) ? (string) $cookie['duration'] : '';
            $description = isset( $cookie['description'] ) ? (string) $cookie['description'] : '';

            $details = array();

            if ( '' !== $domain ) {
                $details[] = sprintf( /* translators: %s: cookie domain. */ __( 'Domain: %s', 'fp-privacy' ), $domain );
            }

            if ( '' !== $duration ) {
                $details[] = sprintf( /* translators: %s: cookie duration. */ __( 'Duration: %s', 'fp-privacy' ), $duration );
            }

            if ( '' !== $description ) {
                $details[] = $description;
            }

            if ( '' === $name && empty( $details ) ) {
                continue;
            }

            $label = '' !== $name ? $name : __( 'Unnamed cookie', 'fp-privacy' );

            if ( $details ) {
                $label .= ' (' . implode( ' — ', $details ) . ')';
            }

            $formatted[] = $label;
        }

        return $formatted;
    }
}

if ( ! function_exists( 'fp_privacy_get_service_value' ) ) {
    /**
     * Safely fetch a scalar service value.
     *
     * @param mixed  $service Service payload.
     * @param string $key     Array key to retrieve.
     *
     * @return string
     */
    function fp_privacy_get_service_value( $service, $key ) {
        if ( ! is_array( $service ) || ! isset( $service[ $key ] ) ) {
            return '';
        }

        $value = $service[ $key ];

        if ( is_scalar( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
            return (string) $value;
        }

        return '';
    }
}

if ( '' === $display_format ) {
    $display_format = 'F j, Y';
}

$last_generated = '';

if ( $generated_at > 0 ) {
    $last_generated = wp_date( $display_format, $generated_at );
}

if ( '' === $last_generated ) {
    $last_generated = wp_date( $display_format );
}
?>
<section class="fp-cookie-policy">
<h2><?php echo esc_html__( 'About cookies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Cookies are small text files stored on your device. They allow us to remember your preferences, ensure the website works properly and measure performance. Some cookies are strictly necessary while others require your consent.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'How we use cookies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We group cookies into categories so you can tailor your experience. You can update your preferences at any time using the cookie preferences button.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Retention of consent', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'Your consent choices are stored for %d days unless you change them earlier.', 'fp-privacy' ), $retention ) ); ?></p>

<?php foreach ( $groups as $category => $services ) :
    $meta  = isset( $categories_meta[ $category ] ) && is_array( $categories_meta[ $category ] ) ? $categories_meta[ $category ] : array();
    $label = isset( $meta['label'] ) && '' !== $meta['label'] ? $meta['label'] : ucfirst( str_replace( '_', ' ', $category ) );
    $description = isset( $meta['description'] ) ? $meta['description'] : '';
    ?>
<div class="fp-cookie-category">
<h3><?php echo esc_html( $label ); ?></h3>
    <?php if ( $description ) : ?>
        <p class="fp-cookie-category-description"><?php echo wp_kses_post( $description ); ?></p>
    <?php endif; ?>
<table>
<thead>
<tr>
<th><?php echo esc_html__( 'Service', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Purpose', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Cookies', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Retention', 'fp-privacy' ); ?></th>
</tr>
</thead>
<tbody>
<?php foreach ( $services as $service ) :
    if ( ! is_array( $service ) ) {
        continue;
    }

    $name      = fp_privacy_get_service_value( $service, 'name' );
    $purpose   = fp_privacy_get_service_value( $service, 'purpose' );
    $retention = fp_privacy_get_service_value( $service, 'retention' );
    $service_cookies = fp_privacy_format_service_cookies( isset( $service['cookies'] ) ? $service['cookies'] : array() );
    ?>
<tr>
<td><?php echo esc_html( $name ); ?></td>
<td><?php echo wp_kses_post( $purpose ); ?></td>
<td>
    <?php if ( ! empty( $service_cookies ) ) : ?>
        <span><?php echo esc_html( implode( '; ', $service_cookies ) ); ?></span>
    <?php else : ?>
        <span><?php echo esc_html__( 'No cookies declared.', 'fp-privacy' ); ?></span>
    <?php endif; ?>
</td>
<td><?php echo esc_html( $retention ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endforeach; ?>

<h2><?php echo esc_html__( 'Managing cookies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'You can revisit your preferences using the cookie preferences button or adjust your browser settings to delete or block cookies. Blocking essential cookies may impact site functionality.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Last update', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'This policy was generated on %s.', 'fp-privacy' ), $last_generated ) ); ?></p>
</section>
