<?php
/**
 * Fill empty msgstr in fp-privacy-en_US.po using a JSON map, then default msgid for remaining English strings.
 *
 * @package FP\Privacy
 */

declare(strict_types=1);

$plugin_root = dirname( __DIR__ );
$po_path     = $plugin_root . '/languages/fp-privacy-en_US.po';
$map_path    = $plugin_root . '/languages/map-en-us.json';

if ( ! is_readable( $po_path ) ) {
	fwrite( STDERR, "Missing PO: {$po_path}\n" );
	exit( 1 );
}

$map = array();
if ( is_readable( $map_path ) ) {
	$json = file_get_contents( $map_path );
	if ( false !== $json ) {
		$decoded = json_decode( $json, true );
		$map     = is_array( $decoded ) ? $decoded : array();
	}
}

/**
 * Escape string for PO msgstr double-quoted format.
 */
function fp_privacy_po_escape( string $s ): string {
	return strtr(
		$s,
		array(
			"\\" => '\\\\',
			"\"" => '\\"',
			"\n" => '\\n',
			"\r" => '\\r',
			"\t" => '\\t',
		)
	);
}

/**
 * Heuristic: Italian UI / legal copy (not exhaustive).
 */
function fp_privacy_likely_italian( string $msgid ): bool {
	if ( preg_match( '/[àèéìòùÀÈÉÌÒÙ]/u', $msgid ) ) {
		return true;
	}
	$lower = mb_strtolower( $msgid, 'UTF-8' );
	$hints = array(
		' per ', ' che ', ' non ', ' con ', ' sono ', ' dalla ', ' degli ', ' delle ', ' trattamento',
		' informativa', ' consenso', ' dati personali', ' impostazioni', ' strumenti',
	);
	foreach ( $hints as $h ) {
		if ( str_contains( $lower, $h ) ) {
			return true;
		}
	}
	return false;
}

$lines   = file( $po_path, FILE_IGNORE_NEW_LINES );
$out     = array();
$count   = 0;
$skipped = 0;

for ( $i = 0, $n = count( $lines ); $i < $n; $i++ ) {
	$line = $lines[ $i ];

	if ( preg_match( '/^msgid\s+"(.*)"\s*$/', $line, $m ) ) {
		$msgid_raw = $m[1];
		$msgid     = stripcslashes( $msgid_raw );
		$next      = ( $i + 1 < $n ) ? trim( $lines[ $i + 1 ] ) : '';

		if ( '' === $msgid && $next === 'msgstr ""' ) {
			$out[] = $line;
			continue;
		}

		if ( $next === 'msgstr ""' && '' !== $msgid ) {
			$replacement = null;
			if ( isset( $map[ $msgid ] ) && is_string( $map[ $msgid ] ) && '' !== $map[ $msgid ] ) {
				$replacement = $map[ $msgid ];
			} elseif ( ! fp_privacy_likely_italian( $msgid ) ) {
				$replacement = $msgid;
			} else {
				$skipped++;
			}

			if ( null !== $replacement ) {
				$out[]           = $line;
				$out[]           = 'msgstr "' . fp_privacy_po_escape( $replacement ) . '"';
				$i++;
				$count++;
				continue;
			}
		}
	}

	$out[] = $line;
}

file_put_contents( $po_path, implode( "\n", $out ) . "\n" );

echo "Filled {$count} msgstr entries. Italian without map (still empty): {$skipped}\n";
