<?php
/**
 * Cookie policy template.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

$retention       = isset( $options['retention_days'] ) ? (int) $options['retention_days'] : 180;
$generated_at    = isset( $generated_at ) ? (int) $generated_at : 0;
$date_format     = (string) get_option( 'date_format' );
$time_format     = (string) get_option( 'time_format' );
$display_format  = trim( $date_format . ' ' . $time_format );
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
<h2><?php echo esc_html__( 'About cookies and tracking technologies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Cookies are small text files stored on your device together with similar technologies such as local storage, SDKs or pixels. They enable core functionality, remember your preferences and help us measure interactions. Except for strictly necessary cookies, we only place cookies after obtaining your explicit consent in line with the GDPR and the ePrivacy Directive.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Regulatory compliance', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Cookie usage is based on your consent pursuant to Articles 6.1.a and 7 GDPR, Article 5(3) of the ePrivacy Directive and the latest guidance issued by European supervisory authorities up to October 2025. Evidence of consent is securely stored and may be provided to supervisory authorities upon request.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Types of cookies and technologies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We classify cookies and similar identifiers as strictly necessary, performance, functional, analytics, marketing or personalization tools. Some technologies such as local storage or fingerprinting scripts are treated with the same safeguards as cookies and require your consent when not strictly necessary.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'How we use cookies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We group cookies into categories so you can tailor your experience. Each category contains the services and technologies described in the tables below, including provider, purpose, cookie duration and links to external privacy information where available.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Consent capture and records', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Your preferences are collected through the cookie banner or the dedicated preferences centre using granular toggles. We log the consent status, timestamp, device information and version of this policy to maintain accountability. You can withdraw or modify consent at any time without affecting the lawfulness of past processing.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Retention of consent', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'Your consent choices are stored for %d days unless you change them earlier.', 'fp-privacy' ), $retention ) ); ?></p>

<h2><?php echo esc_html__( 'Third-country transfers', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Some providers may process data outside the EU/EEA. Where this occurs we rely on adequacy decisions or Standard Contractual Clauses combined with supplementary measures such as encryption, pseudonymisation and transfer impact assessments to ensure an equivalent level of protection.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Managing preferences', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'You can revisit your preferences using the cookie preferences button available on every page or adjust your browser settings to delete or block cookies. Blocking essential cookies may impact site functionality. Detailed instructions for major browsers are linked within the preferences centre.', 'fp-privacy' ); ?></p>

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

<h2><?php echo esc_html__( 'Additional controls', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'You can also use tools provided by third parties, such as industry opt-out platforms for advertising cookies or device-level settings that reset mobile identifiers. Where available we integrate with consent frameworks (for example IAB TCF 2.2) to honour your choices across participating vendors.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Your rights', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'For more information about how we handle personal data and how to exercise your rights of access, rectification, erasure, restriction, objection, portability or to lodge a complaint with a supervisory authority, please refer to our privacy policy.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Policy reviews', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We reassess this cookie policy whenever we add new services, modify retention periods or when regulatory requirements evolve. The current version incorporates guidance available up to October 2025 and any future changes will be published on this page.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Last update', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'This policy was generated on %s.', 'fp-privacy' ), $last_generated ) ); ?></p>
</section>
