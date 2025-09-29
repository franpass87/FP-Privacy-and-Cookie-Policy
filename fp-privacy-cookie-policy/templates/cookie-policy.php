<?php
/**
 * Cookie policy template.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

$retention = isset( $options['retention_days'] ) ? (int) $options['retention_days'] : 180;
?>
<section class="fp-cookie-policy">
<h2><?php echo esc_html__( 'About cookies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Cookies are small text files stored on your device. They allow us to remember your preferences, ensure the website works properly and measure performance. Some cookies are strictly necessary while others require your consent.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'How we use cookies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We group cookies into categories so you can tailor your experience. You can update your preferences at any time using the cookie preferences button.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Retention of consent', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'Your consent choices are stored for %d days unless you change them earlier.', 'fp-privacy' ), $retention ) ); ?></p>

<?php foreach ( $groups as $category => $services ) : ?>
<div class="fp-cookie-category">
<h3><?php echo esc_html( ucfirst( str_replace( '_', ' ', $category ) ) ); ?></h3>
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
<?php foreach ( $services as $service ) : ?>
<tr>
<td><?php echo esc_html( $service['name'] ); ?></td>
<td><?php echo esc_html( $service['purpose'] ); ?></td>
<td><?php echo esc_html( implode( ', ', (array) $service['cookies'] ) ); ?></td>
<td><?php echo esc_html( $service['retention'] ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endforeach; ?>

<h2><?php echo esc_html__( 'Managing cookies', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'You can revisit your preferences using the cookie preferences button or adjust your browser settings to delete or block cookies. Blocking essential cookies may impact site functionality.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Last update', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'This policy was generated on %s.', 'fp-privacy' ), wp_date( get_option( 'date_format' ) ) ) ); ?></p>
</section>
