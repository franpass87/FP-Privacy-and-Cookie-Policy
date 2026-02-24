<?php
/**
 * Policy editor renderer.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Utils\Options;

/**
 * Handles rendering of policy editor page.
 */
class PolicyEditorRenderer {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Diff generator.
	 *
	 * @var PolicyDiffGenerator
	 */
	private $diff_generator;

	/**
	 * Constructor.
	 *
	 * @param Options              $options        Options handler.
	 * @param PolicyDiffGenerator  $diff_generator Diff generator.
	 */
	public function __construct( Options $options, PolicyDiffGenerator $diff_generator ) {
		$this->options        = $options;
		$this->diff_generator = $diff_generator;
	}

	/**
	 * Render page.
	 *
	 * @param array<int, string>        $languages     Active languages.
	 * @param array<string, \WP_Post?> $privacy_posts Privacy posts keyed by language.
	 * @param array<string, \WP_Post?> $cookie_posts  Cookie posts keyed by language.
	 *
	 * @return void
	 */
	public function render( array $languages, array $privacy_posts, array $cookie_posts ) {
		?>
		<div class="wrap fp-privacy-policy-editor">
			<h1><?php \esc_html_e( 'Policy editor', 'fp-privacy' ); ?></h1>
			<p><?php \esc_html_e( 'Customize the generated documents or regenerate them using the detector.', 'fp-privacy' ); ?></p>

			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
				<?php \wp_nonce_field( 'fp_privacy_save_policy', 'fp_privacy_policy_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_save_policy" />

				<?php foreach ( $languages as $index => $language ) :
					$lang_key = \sanitize_key( $language . '_' . $index );
					$privacy  = $privacy_posts[ $language ];
					$cookie   = $cookie_posts[ $language ];
					?>
					<div class="fp-privacy-language-section">
						<h2><?php echo \esc_html( \sprintf( \__( 'Privacy policy (%s)', 'fp-privacy' ), $language ) ); ?></h2>
						<?php \wp_editor( $privacy ? $privacy->post_content : '', 'fp_privacy_policy_' . $lang_key, array( 'textarea_name' => 'privacy_content[' . \esc_attr( $language ) . ']', 'textarea_rows' => 12 ) ); ?>

						<h2><?php echo \esc_html( \sprintf( \__( 'Cookie policy (%s)', 'fp-privacy' ), $language ) ); ?></h2>
						<?php \wp_editor( $cookie ? $cookie->post_content : '', 'fp_privacy_cookie_' . $lang_key, array( 'textarea_name' => 'cookie_content[' . \esc_attr( $language ) . ']', 'textarea_rows' => 12 ) ); ?>
					</div>
				<?php endforeach; ?>

				<?php \submit_button( \__( 'Save policies', 'fp-privacy' ) ); ?>
			</form>

			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-regenerate">
				<?php \wp_nonce_field( 'fp_privacy_regenerate_policy', 'fp_privacy_regenerate_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_regenerate_policy" />
				<?php \submit_button( \__( 'Detect integrations and regenerate', 'fp-privacy' ), 'secondary' ); ?>
			</form>

			<?php $diff = $this->diff_generator->get_diff_preview( $languages, $privacy_posts, $cookie_posts ); ?>
			<?php if ( $diff ) : ?>
				<h2><?php \esc_html_e( 'Differences between generated templates and current documents', 'fp-privacy' ); ?></h2>
				<div class="fp-privacy-diff"><?php echo wp_kses_post( $diff ); ?></div>
			<?php endif; ?>
		</div>
		<?php
	}
}















