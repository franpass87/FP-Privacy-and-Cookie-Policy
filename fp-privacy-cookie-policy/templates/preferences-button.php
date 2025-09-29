<?php
/**
 * Preferences button template.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}
?>
<button type="button" class="fp-privacy-preferences" data-fp-privacy-open>
<?php echo esc_html__( 'Manage cookie preferences', 'fp-privacy' ); ?>
</button>
