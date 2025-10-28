<?php
/**
 * Admin menu.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

/**
 * Registers admin menu structure.
 */
class Menu {
const MENU_SLUG = 'fp-privacy';

/**
 * Hooks.
 *
 * @return void
 */
public function hooks() {
\add_action( 'admin_menu', array( $this, 'register_menu' ) );
}

/**
 * Register menu and subpages.
 *
 * @return void
 */
public function register_menu() {
if ( ! \current_user_can( 'manage_options' ) ) {
return;
}

\add_menu_page(
\__( 'Privacy & Cookie', 'fp-privacy' ),
\__( 'Privacy & Cookie', 'fp-privacy' ),
'manage_options',
self::MENU_SLUG,
array( $this, 'render_settings_page' ),
'dashicons-shield-alt',
59
);

\add_submenu_page(
self::MENU_SLUG,
\__( 'Settings', 'fp-privacy' ),
\__( 'Settings', 'fp-privacy' ),
'manage_options',
self::MENU_SLUG,
array( $this, 'render_settings_page' )
);

\add_submenu_page(
self::MENU_SLUG,
\__( 'Policy editor', 'fp-privacy' ),
\__( 'Policy editor', 'fp-privacy' ),
'manage_options',
'fp-privacy-policy-editor',
array( $this, 'render_policy_editor' )
);

\add_submenu_page(
self::MENU_SLUG,
\__( 'Consent log', 'fp-privacy' ),
\__( 'Consent log', 'fp-privacy' ),
'manage_options',
'fp-privacy-consent-log',
array( $this, 'render_consent_log' )
);

// QUICK WIN #3: Analytics Dashboard
\add_submenu_page(
self::MENU_SLUG,
\__( 'Analytics', 'fp-privacy' ),
\__( 'Analytics', 'fp-privacy' ),
'manage_options',
'fp-privacy-analytics',
array( $this, 'render_analytics' )
);

\add_submenu_page(
self::MENU_SLUG,
\__( 'Tools', 'fp-privacy' ),
\__( 'Tools', 'fp-privacy' ),
'manage_options',
'fp-privacy-tools',
array( $this, 'render_tools' )
);

\add_submenu_page(
self::MENU_SLUG,
\__( 'Quick guide', 'fp-privacy' ),
\__( 'Quick guide', 'fp-privacy' ),
'manage_options',
'fp-privacy-guide',
array( $this, 'render_guide' )
);
}

/**
 * Render settings page.
 *
 * @return void
 */
public function render_settings_page() {
\do_action( 'fp_privacy_admin_page_settings' );
}

/**
 * Render policy editor page.
 *
 * @return void
 */
public function render_policy_editor() {
\do_action( 'fp_privacy_admin_page_policy_editor' );
}

/**
 * Render consent log page.
 *
 * @return void
 */
public function render_consent_log() {
\do_action( 'fp_privacy_admin_page_consent_log' );
}

/**
 * Render tools page.
 *
 * @return void
 */
public function render_tools() {
\do_action( 'fp_privacy_admin_page_tools' );
}

/**
 * Render analytics page.
 *
 * @return void
 */
public function render_analytics() {
\do_action( 'fp_privacy_admin_page_analytics' );
}

/**
 * Render guide page.
 *
 * @return void
 */
public function render_guide() {
\do_action( 'fp_privacy_admin_page_guide' );
}
}
