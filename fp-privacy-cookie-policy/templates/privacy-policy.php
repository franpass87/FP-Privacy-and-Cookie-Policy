<?php
/**
 * Privacy policy template.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

$org      = isset( $options['org_name'] ) ? $options['org_name'] : '';
$address  = isset( $options['address'] ) ? $options['address'] : '';
$dpo_name = isset( $options['dpo_name'] ) ? $options['dpo_name'] : '';
$dpo_mail = isset( $options['dpo_email'] ) ? $options['dpo_email'] : '';
$privacy_mail = isset( $options['privacy_email'] ) ? $options['privacy_email'] : '';
$vat      = isset( $options['vat'] ) ? $options['vat'] : '';
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
?>
<section class="fp-privacy-policy">
<h2><?php echo esc_html__( 'Data controller', 'fp-privacy' ); ?></h2>
<p>
<?php echo esc_html( $org ); ?>
<?php if ( $vat ) : ?> — <?php echo esc_html( sprintf( __( 'VAT/Tax ID: %s', 'fp-privacy' ), $vat ) ); ?><?php endif; ?><br/>
<?php echo esc_html( $address ); ?><br/>
<?php if ( $privacy_mail ) : ?><?php echo esc_html( sprintf( __( 'Contact: %s', 'fp-privacy' ), $privacy_mail ) ); ?><?php endif; ?>
</p>

<h2><?php echo esc_html__( 'Purposes of processing', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We process personal data to provide our services, ensure security, measure performance and deliver personalized experiences in accordance with the selected consent preferences.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Legal bases', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Depending on the specific processing activity we rely on consent, contractual necessity or legitimate interest. Marketing and analytics tools are only activated after explicit consent.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Recipients and data transfers', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Data may be shared with technology partners listed below. Transfers outside the EU/EEA are protected through adequacy decisions, Standard Contractual Clauses or equivalent safeguards.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Retention', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Consent records are stored for the period required by law. Technical cookies follow the lifespan indicated in the cookie tables.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Data subject rights', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'You can request access, rectification, erasure, restriction, portability or object to processing by contacting us. You can withdraw consent at any time from the cookie preferences interface.', 'fp-privacy' ); ?></p>

<?php if ( $dpo_name || $dpo_mail ) : ?>
<h2><?php echo esc_html__( 'Data Protection Officer', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( $dpo_name ); ?> — <?php echo esc_html( $dpo_mail ); ?></p>
<?php endif; ?>

<h2><?php echo esc_html__( 'Services and cookies', 'fp-privacy' ); ?></h2>
<?php foreach ( $groups as $category => $services ) :
    $meta  = isset( $categories_meta[ $category ] ) && is_array( $categories_meta[ $category ] ) ? $categories_meta[ $category ] : array();
    $label = isset( $meta['label'] ) && '' !== $meta['label'] ? $meta['label'] : ucfirst( str_replace( '_', ' ', $category ) );
    $description = isset( $meta['description'] ) ? $meta['description'] : '';
    ?>
<div class="fp-privacy-category-block">
<h3><?php echo esc_html( $label ); ?></h3>
    <?php if ( $description ) : ?>
        <p class="fp-privacy-category-description"><?php echo wp_kses_post( $description ); ?></p>
    <?php endif; ?>
<table>
<thead>
<tr>
<th><?php echo esc_html__( 'Service', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Provider', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Purpose', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Cookies & Retention', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Legal basis', 'fp-privacy' ); ?></th>
</tr>
</thead>
<tbody>
    <?php foreach ( $services as $service ) :
        if ( ! is_array( $service ) ) {
            continue;
        }

        $name        = fp_privacy_get_service_value( $service, 'name' );
        $provider    = fp_privacy_get_service_value( $service, 'provider' );
        $purpose     = fp_privacy_get_service_value( $service, 'purpose' );
        $retention   = fp_privacy_get_service_value( $service, 'retention' );
        $legal_basis = fp_privacy_get_service_value( $service, 'legal_basis' );
        $policy_url  = fp_privacy_get_service_value( $service, 'policy_url' );
        $service_cookies = fp_privacy_format_service_cookies( isset( $service['cookies'] ) ? $service['cookies'] : array() );
        ?>
<tr>
<td>
    <?php echo esc_html( $name ); ?>
    <?php if ( '' !== $policy_url ) : ?> — <a href="<?php echo esc_url( $policy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Privacy policy', 'fp-privacy' ); ?></a><?php endif; ?>
</td>
<td><?php echo esc_html( $provider ); ?></td>
<td><?php echo wp_kses_post( $purpose ); ?></td>
<td>
    <?php if ( ! empty( $service_cookies ) ) : ?>
        <span><?php echo esc_html( implode( '; ', $service_cookies ) ); ?></span>
    <?php else : ?>
        <span><?php echo esc_html__( 'No cookies declared.', 'fp-privacy' ); ?></span>
    <?php endif; ?>
    <?php if ( '' !== $retention ) : ?>
        <span> — <?php echo esc_html( $retention ); ?></span>
    <?php endif; ?>
</td>
<td><?php echo esc_html( $legal_basis ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endforeach; ?>

<h2><?php echo esc_html__( 'How to exercise your rights', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Submit your request via email or the dedicated contact form. We will respond within one month and may request additional information to verify your identity.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Supervisory authority', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'If you believe your privacy rights have been violated you can lodge a complaint with the competent supervisory authority.', 'fp-privacy' ); ?></p>
</section>
