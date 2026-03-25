<?php
/**
 * Policy editor renderer.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

/**
 * Handles rendering of policy editor page.
 */
class PolicyEditorRenderer {
	/**
	 * Diff generator.
	 *
	 * @var PolicyDiffGenerator
	 */
	private $diff_generator;

	/**
	 * Constructor.
	 *
	 * @param PolicyDiffGenerator $diff_generator Diff generator.
	 */
	public function __construct( PolicyDiffGenerator $diff_generator ) {
		$this->diff_generator = $diff_generator;
	}

	/**
	 * Render page.
	 *
	 * @param array<int, string>        $languages     Active languages.
	 * @param array<string, \WP_Post|null> $privacy_posts Privacy posts keyed by language.
	 * @param array<string, \WP_Post|null> $cookie_posts  Cookie posts keyed by language.
	 *
	 * @return void
	 */
	public function render( array $languages, array $privacy_posts, array $cookie_posts ) {
		?>
		<div class="wrap fp-privacy-policy-editor fp-privacy-admin-page">
			<h1 class="screen-reader-text"><?php \esc_html_e( 'Policy editor', 'fp-privacy' ); ?></h1>
			<?php
			AdminHeader::render(
				'dashicons-edit',
				\__( 'Policy editor', 'fp-privacy' ),
				\__( 'Edit privacy and cookie policy content per language.', 'fp-privacy' )
			);
			AdminSubnav::render( 'fp-privacy-policy-editor' );
			?>

			<div class="fp-privacy-card fp-privacy-card--intro">
				<div class="fp-privacy-card-body">
					<p class="description"><?php \esc_html_e( 'Customize the generated documents or regenerate them using the detector.', 'fp-privacy' ); ?></p>
				</div>
			</div>

			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-policy-save-form">
				<?php \wp_nonce_field( 'fp_privacy_save_policy', 'fp_privacy_policy_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_save_policy" />

				<?php foreach ( $languages as $index => $language ) :
					$lang_key = \sanitize_key( $language . '_' . $index );
					$privacy  = $privacy_posts[ $language ];
					$cookie   = $cookie_posts[ $language ];
					?>
					<div class="fp-privacy-language-section fp-privacy-card">
						<div class="fp-privacy-card-header">
							<div class="fp-privacy-card-header-left">
								<span class="dashicons dashicons-translation" aria-hidden="true"></span>
								<h2 class="fp-privacy-card-title"><?php echo \esc_html( $language ); ?></h2>
							</div>
						</div>
						<div class="fp-privacy-card-body">
						<h3 class="fp-privacy-language-section__subtitle"><?php echo \esc_html( \sprintf( \__( 'Privacy policy (%s)', 'fp-privacy' ), $language ) ); ?></h3>
						<?php \wp_editor( $privacy ? $privacy->post_content : '', 'fp_privacy_policy_' . $lang_key, array( 'textarea_name' => 'privacy_content[' . \esc_attr( $language ) . ']', 'textarea_rows' => 12 ) ); ?>

						<h3 class="fp-privacy-language-section__subtitle"><?php echo \esc_html( \sprintf( \__( 'Cookie policy (%s)', 'fp-privacy' ), $language ) ); ?></h3>
						<?php \wp_editor( $cookie ? $cookie->post_content : '', 'fp_privacy_cookie_' . $lang_key, array( 'textarea_name' => 'cookie_content[' . \esc_attr( $language ) . ']', 'textarea_rows' => 12 ) ); ?>
						</div>
					</div>
				<?php endforeach; ?>

				<?php \submit_button( \__( 'Save policies', 'fp-privacy' ) ); ?>
			</form>

			<div class="fp-privacy-card">
				<div class="fp-privacy-card-header">
					<div class="fp-privacy-card-header-left">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Detector & regenerate', 'fp-privacy' ); ?></h2>
					</div>
				</div>
				<div class="fp-privacy-card-body">
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="fp-privacy-regenerate">
				<?php \wp_nonce_field( 'fp_privacy_regenerate_policy', 'fp_privacy_regenerate_nonce' ); ?>
				<input type="hidden" name="action" value="fp_privacy_regenerate_policy" />
				<p class="description"><?php \esc_html_e( 'Run the integration detector and overwrite policy pages with freshly generated HTML.', 'fp-privacy' ); ?></p>
				<?php \submit_button( \__( 'Detect integrations and regenerate', 'fp-privacy' ), 'secondary' ); ?>
			</form>
				</div>
			</div>

			<?php $diff = $this->diff_generator->get_diff_preview( $languages, $privacy_posts, $cookie_posts ); ?>
			<?php if ( $diff ) : ?>
				<div class="fp-privacy-card">
					<div class="fp-privacy-card-header">
						<div class="fp-privacy-card-header-left">
							<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
							<h2 class="fp-privacy-card-title"><?php \esc_html_e( 'Differences between generated templates and current documents', 'fp-privacy' ); ?></h2>
						</div>
					</div>
					<div class="fp-privacy-card-body fp-privacy-diff"><?php echo wp_kses_post( $diff ); ?></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}















