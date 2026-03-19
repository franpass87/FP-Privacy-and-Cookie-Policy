<?php
/**
 * Apply en-us-italian-overrides.php to fp-privacy-en_US.po (match msgid line, replace following msgstr).
 *
 * @package FP\Privacy
 */

declare(strict_types=1);

$root        = dirname( __DIR__ );
$po_path     = $root . '/languages/fp-privacy-en_US.po';
$overrides   = $root . '/languages/en-us-italian-overrides.php';

if ( ! is_readable( $po_path ) || ! is_readable( $overrides ) ) {
	fwrite( STDERR, "Missing PO or overrides file.\n" );
	exit( 1 );
}

/** @var array<string, string> $map */
$map = require $overrides;

function fp_privacy_po_escape( string $s ): string {
	return strtr(
		$s,
		array(
			'\\' => '\\\\',
			'"'  => '\\"',
			"\n" => '\\n',
			"\r" => '\\r',
			"\t" => '\\t',
		)
	);
}

$lines = file( $po_path, FILE_IGNORE_NEW_LINES );
$out   = array();
$done  = 0;

for ( $i = 0, $n = count( $lines ); $i < $n; $i++ ) {
	$line = $lines[ $i ];
	if ( preg_match( '/^msgid "(.*)"\s*$/', $line, $m ) ) {
		$msgid = stripcslashes( $m[1] );
		$nx    = ( $i + 1 < $n ) ? $lines[ $i + 1 ] : '';
		if ( isset( $map[ $msgid ] ) && preg_match( '/^msgstr "/', $nx ) ) {
			$out[] = $line;
			$out[] = 'msgstr "' . fp_privacy_po_escape( $map[ $msgid ] ) . '"';
			$i++;
			$done++;
			continue;
		}
	}
	$out[] = $line;
}

file_put_contents( $po_path, implode( "\n", $out ) . "\n" );
echo "Applied {$done} override translations.\n";
