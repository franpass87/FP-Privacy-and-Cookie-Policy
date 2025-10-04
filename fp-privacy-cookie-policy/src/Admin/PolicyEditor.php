<?php
/**
 * Policy editor page.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Utils\Options;

/**
 * Handles policy editing and regeneration.
 */
class PolicyEditor {
/**
 * Options handler.
 *
 * @var Options
 */
private $options;

/**
 * Generator.
 *
 * @var PolicyGenerator
 */
private $generator;

/**
 * Constructor.
 *
 * @param Options         $options   Options handler.
 * @param PolicyGenerator $generator Generator.
 */
public function __construct( Options $options, PolicyGenerator $generator ) {
$this->options   = $options;
$this->generator = $generator;
}

/**
 * Hooks.
 *
 * @return void
 */
public function hooks() {
    \add_action( 'fp_privacy_admin_page_policy_editor', array( $this, 'render_page' ) );
    \add_action( 'admin_post_fp_privacy_save_policy', array( $this, 'handle_save' ) );
    \add_action( 'admin_post_fp_privacy_regenerate_policy', array( $this, 'handle_regenerate' ) );
}

/**
 * Render page.
 *
 * @return void
 */
public function render_page() {
    if ( ! \current_user_can( 'manage_options' ) ) {
        \wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
    }

    $this->options->ensure_pages_exist();

    $languages = $this->options->get_languages();
    if ( empty( $languages ) ) {
        $languages = array( \get_locale() );
    }

    $this->maybe_generate_documents( $languages );

    $privacy_posts = array();
    $cookie_posts  = array();

    foreach ( $languages as $language ) {
        $privacy_id              = $this->options->get_page_id( 'privacy_policy', $language );
        $cookie_id               = $this->options->get_page_id( 'cookie_policy', $language );
        $privacy_posts[ $language ] = $privacy_id ? \get_post( $privacy_id ) : null;
        $cookie_posts[ $language ]  = $cookie_id ? \get_post( $cookie_id ) : null;
    }
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

<?php $diff = $this->get_diff_preview( $languages, $privacy_posts, $cookie_posts ); ?>
<?php if ( $diff ) : ?>
<h2><?php \esc_html_e( 'Differences between generated templates and current documents', 'fp-privacy' ); ?></h2>
<div class="fp-privacy-diff"><?php echo $diff; ?></div>
<?php endif; ?>
</div>
<?php
}


/**
 * Handle manual save.
 *
 * @return void
 */
public function handle_save() {
    if ( ! \current_user_can( 'manage_options' ) ) {
        \wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
    }

    \check_admin_referer( 'fp_privacy_save_policy', 'fp_privacy_policy_nonce' );

    $privacy_contents = isset( $_POST['privacy_content'] ) ? \wp_unslash( $_POST['privacy_content'] ) : array();
    $cookie_contents  = isset( $_POST['cookie_content'] ) ? \wp_unslash( $_POST['cookie_content'] ) : array();

    $languages = $this->options->get_languages();
    if ( empty( $languages ) ) {
        $languages = array( \get_locale() );
    }

    foreach ( $languages as $language ) {
        $language     = $this->options->normalize_language( $language );
        $privacy_id   = $this->options->get_page_id( 'privacy_policy', $language );
        $cookie_id    = $this->options->get_page_id( 'cookie_policy', $language );
        $privacy_body = isset( $privacy_contents[ $language ] ) ? $privacy_contents[ $language ] : '';
        $cookie_body  = isset( $cookie_contents[ $language ] ) ? $cookie_contents[ $language ] : '';

        if ( $privacy_id ) {
            \wp_update_post(
                array(
                    'ID'           => $privacy_id,
                    'post_content' => $privacy_body,
                )
            );
        }

        if ( $cookie_id ) {
            \wp_update_post(
                array(
                    'ID'           => $cookie_id,
                    'post_content' => $cookie_body,
                )
            );
        }
    }

    \wp_safe_redirect( \add_query_arg( 'policy-updated', '1', \wp_get_referer() ) );
    exit;
}


/**
 * Handle regeneration.
 *
 * @return void
 */
public function handle_regenerate() {
    if ( ! \current_user_can( 'manage_options' ) ) {
        \wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
    }

    \check_admin_referer( 'fp_privacy_regenerate_policy', 'fp_privacy_regenerate_nonce' );

    $this->options->ensure_pages_exist();

    $languages = $this->options->get_languages();
    if ( empty( $languages ) ) {
        $languages = array( \get_locale() );
    }

    $generated_privacy = array();
    $generated_cookie  = array();

    foreach ( $languages as $language ) {
        $language   = $this->options->normalize_language( $language );
        $privacy_id = $this->options->get_page_id( 'privacy_policy', $language );
        $cookie_id  = $this->options->get_page_id( 'cookie_policy', $language );

        $privacy = $this->generator->generate_privacy_policy( $language );
        $cookie  = $this->generator->generate_cookie_policy( $language );

        $generated_privacy[ $language ] = $privacy;
        $generated_cookie[ $language ]  = $cookie;

        if ( $privacy_id ) {
            \wp_update_post(
                array(
                    'ID'           => $privacy_id,
                    'post_content' => $privacy,
                )
            );
        }

        if ( $cookie_id ) {
            \wp_update_post(
                array(
                    'ID'           => $cookie_id,
                    'post_content' => $cookie,
                )
            );
        }
    }

    $this->options->bump_revision();

    $timestamp       = time();
    $services        = $this->generator->snapshot( true );
    $this->options->prime_script_rules_from_services( $services );
    $snapshots       = array(
        'services' => array(
            'detected'     => $services,
            'generated_at' => $timestamp,
        ),
        'policies' => array(
            'privacy' => array(),
            'cookie'  => array(),
        ),
    );

    foreach ( $generated_privacy as $lang => $content ) {
        $snapshots['policies']['privacy'][ $lang ] = array(
            'content'      => $content,
            'generated_at' => $timestamp,
        );
    }

    foreach ( $generated_cookie as $lang => $content ) {
        $snapshots['policies']['cookie'][ $lang ] = array(
            'content'      => $content,
            'generated_at' => $timestamp,
        );
    }

    $payload                    = $this->options->all();
    $payload['snapshots']       = $snapshots;
    $payload['detector_alert']  = \array_merge( $this->options->get_default_detector_alert(), array( 'last_checked' => $timestamp ) );

    $this->options->set( $payload );

    \do_action( 'fp_privacy_snapshots_refreshed', $snapshots );

    \wp_safe_redirect( \add_query_arg( 'policy-regenerated', '1', \wp_get_referer() ) );
    exit;
}

/**
 * Ensure generated documents exist for each language.
 *
 * @param array<int, string> $languages Languages configured for the site.
 *
 * @return void
 */
private function maybe_generate_documents( array $languages ) {
    foreach ( $languages as $language ) {
        $language   = $this->options->normalize_language( $language );
        $privacy_id = $this->options->get_page_id( 'privacy_policy', $language );
        $cookie_id  = $this->options->get_page_id( 'cookie_policy', $language );

        if ( $privacy_id ) {
            $this->maybe_generate_document( $privacy_id, 'privacy', $language );
        }

        if ( $cookie_id ) {
            $this->maybe_generate_document( $cookie_id, 'cookie', $language );
        }
    }
}

/**
 * Generate policy content when the stored page is empty or still using the shortcode placeholder.
 *
 * @param int    $post_id  Page identifier.
 * @param string $type     Document type (privacy|cookie).
 * @param string $language Normalized language code.
 *
 * @return void
 */
private function maybe_generate_document( $post_id, $type, $language ) {
    $post = \get_post( $post_id );

    if ( ! ( $post instanceof \WP_Post ) ) {
        return;
    }

    $current_content = trim( (string) $post->post_content );
    $shortcode       = 'privacy' === $type ? 'fp_privacy_policy' : 'fp_cookie_policy';
    $placeholder     = \sprintf( '[%1$s lang="%2$s"]', $shortcode, $language );

    if ( '' !== $current_content && $current_content !== $placeholder ) {
        return;
    }

    $generated = 'privacy' === $type
        ? $this->generator->generate_privacy_policy( $language )
        : $this->generator->generate_cookie_policy( $language );

    $updated = \wp_update_post(
        array(
            'ID'           => $post->ID,
            'post_content' => $generated,
        ),
        true
    );

    if ( ! \is_wp_error( $updated ) ) {
        \delete_post_meta( $post->ID, Options::PAGE_MANAGED_META_KEY );
    }
}


/**
 * Diff preview.
 *
 * @param array<int, string>        $languages     Active languages.
 * @param array<string, \WP_Post?> $privacy_posts Privacy posts keyed by language.
 * @param array<string, \WP_Post?> $cookie_posts  Cookie posts keyed by language.
 *
 * @return string
 */
private function get_diff_preview( array $languages, array $privacy_posts, array $cookie_posts ) {
    $output = '';

    foreach ( $languages as $language ) {
        $language     = $this->options->normalize_language( $language );
        $privacy_post = isset( $privacy_posts[ $language ] ) ? $privacy_posts[ $language ] : null;
        $cookie_post  = isset( $cookie_posts[ $language ] ) ? $cookie_posts[ $language ] : null;

        $generated_privacy = $this->generator->generate_privacy_policy( $language );
        $generated_cookie  = $this->generator->generate_cookie_policy( $language );

        $privacy_current = $privacy_post ? $privacy_post->post_content : '';
        $cookie_current  = $cookie_post ? $cookie_post->post_content : '';

        $privacy_diff = \wp_text_diff( $privacy_current, $generated_privacy, array( 'title' => \sprintf( \__( 'Privacy policy diff (%s)', 'fp-privacy' ), $language ) ) );
        $cookie_diff  = \wp_text_diff( $cookie_current, $generated_cookie, array( 'title' => \sprintf( \__( 'Cookie policy diff (%s)', 'fp-privacy' ), $language ) ) );

        $output .= $privacy_diff . $cookie_diff;
    }

    return $output;
}

}
