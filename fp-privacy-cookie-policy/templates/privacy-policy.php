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
<?php foreach ( $groups as $category => $services ) : ?>
<div class="fp-privacy-category-block">
<h3><?php echo esc_html( ucfirst( str_replace( '_', ' ', $category ) ) ); ?></h3>
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
<?php foreach ( $services as $service ) : ?>
<tr>
<td><?php echo esc_html( $service['name'] ); ?><?php if ( ! empty( $service['policy_url'] ) ) : ?> — <a href="<?php echo esc_url( $service['policy_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Privacy policy', 'fp-privacy' ); ?></a><?php endif; ?></td>
<td><?php echo esc_html( $service['provider'] ); ?></td>
<td><?php echo esc_html( $service['purpose'] ); ?></td>
<td><?php echo esc_html( implode( ', ', (array) $service['cookies'] ) ); ?> — <?php echo esc_html( $service['retention'] ); ?></td>
<td><?php echo esc_html( $service['legal_basis'] ); ?></td>
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
