<?php
/**
 * Navigazione orizzontale tra le sezioni admin del plugin (design system FP).
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Admin;

/**
 * Subnav condivisa su tutte le pagine FP Privacy admin.
 */
final class AdminSubnav {
	/**
	 * Slug della pagina corrente (param `page`).
	 *
	 * @param string $current_slug Slug attivo.
	 *
	 * @return void
	 */
	public static function render( string $current_slug ): void {
		$items = self::items();
		$base  = \admin_url( 'admin.php' );
		?>
		<nav class="fp-privacy-admin-subnav" aria-label="<?php \esc_attr_e( 'FP Privacy admin sections', 'fp-privacy' ); ?>">
			<ul class="fp-privacy-admin-subnav__list">
				<?php foreach ( $items as $slug => $meta ) : ?>
					<?php
					$url   = \add_query_arg( 'page', $slug, $base );
					$label = $meta['label'];
					$icon  = (string) $meta['icon'];
					$active = ( $slug === $current_slug );
					?>
					<li class="fp-privacy-admin-subnav__item">
						<a
							class="fp-privacy-admin-subnav__link<?php echo $active ? ' is-active' : ''; ?>"
							<?php echo $active ? ' aria-current="page"' : ''; ?>
							href="<?php echo \esc_url( $url ); ?>"
						>
							<span class="dashicons <?php echo \esc_attr( $icon ); ?>" aria-hidden="true"></span>
							<?php echo \esc_html( $label ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * True se la voce Diagnostica è registrata nel menu WP (evita link morti se il servizio non è iniettato).
	 *
	 * @return bool
	 */
	public static function diagnostics_menu_available(): bool {
		global $submenu;

		if ( empty( $submenu['fp-privacy'] ) || ! \is_array( $submenu['fp-privacy'] ) ) {
			return false;
		}

		foreach ( $submenu['fp-privacy'] as $row ) {
			if ( isset( $row[2] ) && 'fp-privacy-diagnostics' === $row[2] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Voci in ordine operativo → contenuti → sistema → supporto.
	 *
	 * @return array<string, array{label: string, icon: string}>
	 */
	private static function items(): array {
		$items = array(
			Menu::MENU_SLUG            => array(
				'label' => \__( 'Settings', 'fp-privacy' ),
				'icon'  => 'dashicons-admin-settings',
			),
			'fp-privacy-consent-log'   => array(
				'label' => \__( 'Consent log', 'fp-privacy' ),
				'icon'  => 'dashicons-list-view',
			),
			'fp-privacy-analytics'     => array(
				'label' => \__( 'Analytics', 'fp-privacy' ),
				'icon'  => 'dashicons-chart-area',
			),
			'fp-privacy-policy-editor' => array(
				'label' => \__( 'Policy editor', 'fp-privacy' ),
				'icon'  => 'dashicons-edit',
			),
			'fp-privacy-tools'         => array(
				'label' => \__( 'Tools', 'fp-privacy' ),
				'icon'  => 'dashicons-admin-tools',
			),
			'fp-privacy-diagnostics'   => array(
				'label' => \__( 'Diagnostics', 'fp-privacy' ),
				'icon'  => 'dashicons-info',
			),
			'fp-privacy-guide'         => array(
				'label' => \__( 'Quick guide', 'fp-privacy' ),
				'icon'  => 'dashicons-book-alt',
			),
		);

		if ( ! self::diagnostics_menu_available() ) {
			unset( $items['fp-privacy-diagnostics'] );
		}

		/**
		 * Filtra le voci della subnav orizzontale (es. integrazioni che nascondono sezioni).
		 *
		 * @param array<string, array{label: string, icon: string}> $items Voci.
		 */
		$filtered = \apply_filters( 'fp_privacy_admin_subnav_items', $items );

		return \is_array( $filtered ) ? $filtered : $items;
	}
}
