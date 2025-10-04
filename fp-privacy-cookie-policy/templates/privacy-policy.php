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
$generated_at    = isset( $generated_at ) ? (int) $generated_at : 0;
$categories_meta = isset( $categories_meta ) && is_array( $categories_meta ) ? $categories_meta : array();

$date_format  = (string) get_option( 'date_format' );
$time_format  = (string) get_option( 'time_format' );
$display_date = trim( $date_format . ' ' . $time_format );

if ( '' === $display_date ) {
    $display_date = 'F j, Y';
}

$last_generated = $generated_at > 0 ? wp_date( $display_date, $generated_at ) : wp_date( $display_date );

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
<h2><?php echo esc_html__( 'Overview', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'This privacy policy explains how we process personal data in compliance with Regulation (EU) 2016/679 (General Data Protection Regulation, "GDPR") and applicable national privacy laws, including the most recent guidelines issued by European supervisory authorities.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Data controller', 'fp-privacy' ); ?></h2>
<p>
<?php echo esc_html( $org ); ?>
<?php if ( $vat ) : ?> — <?php echo esc_html( sprintf( __( 'VAT/Tax ID: %s', 'fp-privacy' ), $vat ) ); ?><?php endif; ?><br/>
<?php echo esc_html( $address ); ?><br/>
<?php if ( $privacy_mail ) : ?><?php echo esc_html( sprintf( __( 'Contact: %s', 'fp-privacy' ), $privacy_mail ) ); ?><?php endif; ?>
</p>

<h2><?php echo esc_html__( 'Applicable regulations', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Processing activities are carried out in accordance with the GDPR, the ePrivacy Directive as implemented locally, consumer protection rules and any sector-specific obligations that apply to our services.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Categories of data we process', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Depending on how you interact with the website we may process identification data (such as name or contact details), technical data (IP address, device identifiers, logs), usage data (pages visited, actions taken), and preference data (consent choices, marketing preferences). Additional information collected by specific services is described in the integrations table below.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Purposes of processing', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We process personal data to provide our services, respond to enquiries, ensure security, measure performance, improve our content and deliver tailored experiences only where you have granted the relevant consent.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Legal bases', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Processing is grounded on one or more of the following legal bases: consent (Article 6.1.a GDPR) for optional tools such as analytics or marketing cookies; contractual necessity (Article 6.1.b GDPR) when processing is required to provide requested services; compliance with legal obligations (Article 6.1.c GDPR); and legitimate interest (Article 6.1.f GDPR) for security, fraud prevention and essential analytics balanced against your rights. Optional tools are never activated before consent is granted.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Recipients and data transfers', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Data may be shared with technology partners listed below strictly for the purposes indicated. When partners are established outside the European Economic Area, transfers occur only where an adequacy decision is in place or through Standard Contractual Clauses and additional safeguards consistent with the recommendations of the European Data Protection Board.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Security measures', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We apply technical and organisational measures such as encryption in transit, access controls, data minimisation and staff training to protect personal data against unauthorised access, alteration or disclosure.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Retention', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Personal data are retained only for as long as necessary to fulfil the purposes stated above, comply with statutory retention duties or defend legal claims. Consent records are stored for the period required by law. Technical cookies follow the lifespan indicated in the cookie tables.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Data subject rights', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'You can exercise your rights to access, rectification, erasure, restriction, objection, portability and to withdraw consent at any time without affecting the lawfulness of processing prior to withdrawal. You also have the right not to be subject to decisions based solely on automated processing, including profiling, which produce legal effects concerning you or similarly significantly affect you.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'How to exercise your rights', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Submit your request via email or the dedicated contact form. We will respond within one month pursuant to Articles 12 and 15–22 GDPR and may request additional information to verify your identity. If your request is complex or we receive numerous requests, the response time may be extended by two further months with prior notice.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Withdrawal of consent and cookie management', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'You can adjust your consent choices at any time through the cookie preferences interface displayed on the site footer or by clearing cookies in your browser. Revoking consent does not affect mandatory processing necessary to operate the service.', 'fp-privacy' ); ?></p>

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

<h2><?php echo esc_html__( 'Supervisory authority', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'If you believe your privacy rights have been violated you can lodge a complaint with the competent supervisory authority in your Member State of residence, workplace or where the alleged infringement took place.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Last update', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'This policy was generated on %s.', 'fp-privacy' ), $last_generated ) ); ?></p>
</section>
