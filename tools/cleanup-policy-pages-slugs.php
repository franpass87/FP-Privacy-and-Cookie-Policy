<?php
/**
 * One-shot CLI: cestina pagine policy duplicate e imposta slug canonici (senza numeri) per lingua predefinita.
 *
 * Esegue:
 * - Non tocca gli ID presenti in fp_privacy_settings[pages] (tutte le lingue) né le traduzioni WPML di quelle pagine.
 * - Cestina le altre pagine il cui slug combacia con informativa-sulla-privacy*, privacy-policy*, cookie-policy*.
 * - Assegna post_name `privacy-policy` e `cookie-policy` alle pagine canoniche della lingua predefinita (WPML o sito).
 * - Per altre lingue (stesso post_name unico in wp_posts): usa suffisso `-en` ecc. (senza cifre tipo -29).
 * - Sostituisce URL noti rotti in wp_options / wp_postmeta (menu).
 *
 * Uso dalla root WordPress (public):
 *   php wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/tools/cleanup-policy-pages-slugs.php
 *
 * Override ID (opzionale):
 *   FP_KEEP_PRIVACY_ID=281 FP_KEEP_COOKIE_ID=282 php ...
 *
 * ID aggiuntivi da non cestinare mai (es. traduzione EN non in mappa), separati da virgola:
 *   FP_EXTRA_PROTECT_IDS=100,200 php ...
 *
 * @package FP\Privacy
 */

declare(strict_types=1);

if ( php_sapi_name() !== 'cli' ) {
	exit( 'CLI only.' );
}

$wp_load = '';
$root    = getenv( 'WORDPRESS_ROOT' );
if ( is_string( $root ) && $root !== '' && is_readable( rtrim( $root, '/\\' ) . '/wp-load.php' ) ) {
	$wp_load = rtrim( $root, '/\\' ) . '/wp-load.php';
}
if ( '' === $wp_load && isset( $argv[1] ) && is_string( $argv[1] ) && $argv[1] !== '' ) {
	$arg = rtrim( $argv[1], '/\\' );
	if ( is_readable( $arg . '/wp-load.php' ) ) {
		$wp_load = $arg . '/wp-load.php';
	}
}
if ( '' === $wp_load ) {
	$dir = __DIR__;
	for ( $i = 0; $i < 14; $i++ ) {
		$candidate = $dir . '/wp-load.php';
		if ( is_readable( $candidate ) ) {
			$wp_load = $candidate;
			break;
		}
		$parent = dirname( $dir );
		if ( $parent === $dir ) {
			break;
		}
		$dir = $parent;
	}
}
if ( '' === $wp_load ) {
	fwrite( STDERR, "wp-load.php non trovato. Imposta WORDPRESS_ROOT o passa il path della root WP come primo argomento.\n" );
	exit( 1 );
}

require $wp_load;

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "Nessun amministratore.\n" );
	exit( 1 );
}
wp_set_current_user( (int) $admins[0] );

$opt       = get_option( 'fp_privacy_settings', array() );
$pages_cfg = isset( $opt['pages'] ) && is_array( $opt['pages'] ) ? $opt['pages'] : array();

$privacy_map = isset( $pages_cfg['privacy_policy_page_id'] ) && is_array( $pages_cfg['privacy_policy_page_id'] )
	? $pages_cfg['privacy_policy_page_id'] : array();
$cookie_map  = isset( $pages_cfg['cookie_policy_page_id'] ) && is_array( $pages_cfg['cookie_policy_page_id'] )
	? $pages_cfg['cookie_policy_page_id'] : array();

$default_lang = 'it';
if ( has_filter( 'wpml_default_language' ) ) {
	$dl = apply_filters( 'wpml_default_language', null );
	if ( is_string( $dl ) && $dl !== '' ) {
		$default_lang = $dl;
	}
}

$it_locale = 'it_IT';
foreach ( array_keys( $privacy_map + $cookie_map ) as $k ) {
	if ( is_string( $k ) && str_starts_with( strtolower( str_replace( '-', '_', $k ) ), 'it' ) ) {
		$it_locale = str_replace( '-', '_', $k );
		break;
	}
}

$keep_privacy = (int) getenv( 'FP_KEEP_PRIVACY_ID' );
$keep_cookie  = (int) getenv( 'FP_KEEP_COOKIE_ID' );

if ( $keep_privacy <= 0 ) {
	foreach ( array( $it_locale, 'it_IT', 'it' ) as $k ) {
		if ( isset( $privacy_map[ $k ] ) && (int) $privacy_map[ $k ] > 0 ) {
			$keep_privacy = (int) $privacy_map[ $k ];
			break;
		}
	}
}
if ( $keep_privacy <= 0 ) {
	$keep_privacy = 99;
}

if ( $keep_cookie <= 0 ) {
	foreach ( array( $it_locale, 'it_IT', 'it' ) as $k ) {
		if ( isset( $cookie_map[ $k ] ) && (int) $cookie_map[ $k ] > 0 ) {
			$keep_cookie = (int) $cookie_map[ $k ];
			break;
		}
	}
}
if ( $keep_cookie <= 0 ) {
	$keep_cookie = 282;
}

/**
 * Raccoglie ID da una mappa lingua => post ID.
 *
 * @param array<string, int|string> $map Mappa lingua => ID.
 * @return array<int, int>
 */
$merge_map_ids = static function ( array $map ): array {
	$out = array();
	foreach ( $map as $id ) {
		// Mappa: chiave lingua, valore ID.
		$i = (int) $id;
		if ( $i > 0 ) {
			$out[] = $i;
		}
	}
	return $out;
};

$extra_protect = array();
$raw_extra     = getenv( 'FP_EXTRA_PROTECT_IDS' );
if ( is_string( $raw_extra ) && $raw_extra !== '' ) {
	foreach ( explode( ',', $raw_extra ) as $bit ) {
		$e = (int) trim( $bit );
		if ( $e > 0 ) {
			$extra_protect[] = $e;
		}
	}
}

$never_trash = array_unique(
	array_filter(
		array_merge(
			array( $keep_privacy, $keep_cookie ),
			$merge_map_ids( $privacy_map ),
			$merge_map_ids( $cookie_map ),
			$extra_protect
		)
	)
);

/**
 * Aggiunge traduzioni WPML dello stesso elemento.
 *
 * @param array<int, int> $ids ID post.
 * @return array<int, int>
 */
$wpml_expand = static function ( array $ids ): array {
	if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
		return $ids;
	}
	global $sitepress;
	if ( ! is_object( $sitepress ) || ! method_exists( $sitepress, 'get_element_trid' ) ) {
		return $ids;
	}
	$seen_trid = array();
	$out       = $ids;
	foreach ( $ids as $pid ) {
		$trid = (int) $sitepress->get_element_trid( (int) $pid, 'post_page' );
		if ( $trid <= 0 || isset( $seen_trid[ $trid ] ) ) {
			continue;
		}
		$seen_trid[ $trid ] = true;
		$translations       = $sitepress->get_element_translations( $trid, 'post_page' );
		if ( is_array( $translations ) ) {
			foreach ( $translations as $t ) {
				if ( is_object( $t ) && isset( $t->element_id ) ) {
					$out[] = (int) $t->element_id;
				}
			}
		}
	}
	return array_values( array_unique( array_filter( $out ) ) );
};

$never_trash = $wpml_expand( $never_trash );

echo 'ID protetti (mai cestinati): ' . implode( ', ', $never_trash ) . "\n";
echo "Canonical IT privacy={$keep_privacy} cookie={$keep_cookie} default_lang={$default_lang}\n";

global $wpdb;

$rows = $wpdb->get_results(
	"SELECT ID, post_name, post_title, post_status FROM {$wpdb->posts}
	WHERE post_type = 'page'
	AND post_status IN ('publish','draft','pending','private')
	AND (
		post_name REGEXP '^(informativa-sulla-privacy|privacy-policy)'
		OR post_name REGEXP '^cookie-policy'
	)",
	ARRAY_A
);

if ( ! is_array( $rows ) ) {
	fwrite( STDERR, "Query fallita.\n" );
	exit( 1 );
}

$to_trash = array();
foreach ( $rows as $row ) {
	$id = (int) $row['ID'];
	if ( in_array( $id, $never_trash, true ) ) {
		continue;
	}
	$to_trash[] = $id;
}

echo 'Cestino ' . count( $to_trash ) . ' pagine duplicate…' . "\n";
foreach ( $to_trash as $tid ) {
	$r = wp_trash_post( $tid );
	echo ( $r ? "  trash {$tid}\n" : "  FAIL {$tid}\n" );
}

/**
 * Lingua elemento WPML o default.
 *
 * @param int $post_id Post ID.
 * @return string Codice lingua WPML (es. it, en).
 */
$post_lang = static function ( int $post_id ) use ( $default_lang ): string {
	$details = apply_filters( 'wpml_post_language_details', null, $post_id );
	if ( is_array( $details ) && ! empty( $details['language_code'] ) ) {
		return (string) $details['language_code'];
	}
	return $default_lang;
};

// Slug canonici: lingua default -> senza suffisso; altre lingue -> -{lang} (es. privacy-policy-en).
$assign_slug = static function ( int $post_id, string $base_slug, string $def_lang ) use ( $post_lang ): void {
	global $wpdb;
	$lang     = $post_lang( $post_id );
	$desired  = ( $lang === $def_lang ) ? $base_slug : $base_slug . '-' . $lang;
	$conflict = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_name = %s AND ID != %d AND post_status NOT IN ('trash','auto-draft') LIMIT 1",
			$desired,
			$post_id
		)
	);
	if ( $conflict > 0 ) {
		wp_update_post(
			array(
				'ID'        => $conflict,
				'post_name' => $desired . '-moved-' . $conflict,
			)
		);
		echo "  conflitto slug: post {$conflict} rinominato per liberare {$desired}\n";
	}
	wp_update_post(
		array(
			'ID'        => $post_id,
			'post_name' => $desired,
		)
	);
	echo "  post {$post_id} -> {$desired}\n";
};

echo "Rinomina slug pagine protette…\n";

global $sitepress;

$privacy_trid = 0;
$cookie_trid  = 0;
$wpml_ok      = defined( 'ICL_SITEPRESS_VERSION' ) && is_object( $sitepress ) && method_exists( $sitepress, 'get_element_trid' );
if ( $wpml_ok ) {
	$privacy_trid = (int) $sitepress->get_element_trid( $keep_privacy, 'post_page' );
	$cookie_trid  = (int) $sitepress->get_element_trid( $keep_cookie, 'post_page' );
}

$privacy_ids_fallback = array_merge( array( $keep_privacy ), $merge_map_ids( $privacy_map ) );
$cookie_ids_fallback  = array_merge( array( $keep_cookie ), $merge_map_ids( $cookie_map ) );

foreach ( array_unique( $never_trash ) as $pid ) {
	$post = get_post( $pid );
	if ( ! $post instanceof WP_Post || 'trash' === $post->post_status ) {
		continue;
	}
	$is_privacy = false;
	$is_cookie  = false;
	if ( $wpml_ok ) {
		$t = (int) $sitepress->get_element_trid( (int) $pid, 'post_page' );
		if ( $privacy_trid > 0 && $t === $privacy_trid ) {
			$is_privacy = true;
		}
		if ( $cookie_trid > 0 && $t === $cookie_trid ) {
			$is_cookie = true;
		}
	}
	if ( ! $is_privacy && in_array( (int) $pid, array_unique( $privacy_ids_fallback ), true ) ) {
		$is_privacy = true;
	}
	if ( ! $is_cookie && in_array( (int) $pid, array_unique( $cookie_ids_fallback ), true ) ) {
		$is_cookie = true;
	}
	if ( $is_privacy && ! $is_cookie ) {
		$assign_slug( (int) $pid, 'privacy-policy', $default_lang );
	} elseif ( $is_cookie && ! $is_privacy ) {
		$assign_slug( (int) $pid, 'cookie-policy', $default_lang );
	} elseif ( $is_privacy && $is_cookie ) {
		echo "  skip {$pid}: stesso trid privacy e cookie (anomalia)\n";
	}
}

// Sincronizza opzioni: assicura chiavi principali.
if ( ! isset( $opt['pages'] ) || ! is_array( $opt['pages'] ) ) {
	$opt['pages'] = array();
}
if ( ! isset( $opt['pages']['privacy_policy_page_id'] ) || ! is_array( $opt['pages']['privacy_policy_page_id'] ) ) {
	$opt['pages']['privacy_policy_page_id'] = array();
}
if ( ! isset( $opt['pages']['cookie_policy_page_id'] ) || ! is_array( $opt['pages']['cookie_policy_page_id'] ) ) {
	$opt['pages']['cookie_policy_page_id'] = array();
}

$opt['pages']['privacy_policy_page_id'][ $it_locale ] = $keep_privacy;
$opt['pages']['cookie_policy_page_id'][ $it_locale ]  = $keep_cookie;

foreach ( $privacy_map as $loc => $id ) {
	if ( (int) $id > 0 ) {
		$opt['pages']['privacy_policy_page_id'][ $loc ] = (int) $id;
	}
}
foreach ( $cookie_map as $loc => $id ) {
	if ( (int) $id > 0 ) {
		$opt['pages']['cookie_policy_page_id'][ $loc ] = (int) $id;
	}
}

update_option( 'fp_privacy_settings', $opt );

// Sostituzioni URL comuni (footer/menu serializzati).
$replacements = array(
	home_url( '/informativa-sulla-privacy-24/' )   => home_url( '/privacy-policy/' ),
	home_url( '/cookie-policy-3/' )                => home_url( '/cookie-policy/' ),
	'http://fp-development.local/informativa-sulla-privacy-24/' => home_url( '/privacy-policy/' ),
	'http://fp-development.local/cookie-policy-3/'              => home_url( '/cookie-policy/' ),
	'https://fp-development.local/informativa-sulla-privacy-24/' => home_url( '/privacy-policy/' ),
	'https://fp-development.local/cookie-policy-3/'             => home_url( '/cookie-policy/' ),
);

foreach ( $replacements as $from => $to ) {
	if ( $from === $to ) {
		continue;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, %s, %s) WHERE option_value LIKE %s",
			$from,
			$to,
			'%' . $wpdb->esc_like( $from ) . '%'
		)
	);
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_value LIKE %s",
			$from,
			$to,
			'%' . $wpdb->esc_like( $from ) . '%'
		)
	);
}

flush_rewrite_rules( false );

echo "Completato. Controlla menu, widget e svuota cache.\n";
