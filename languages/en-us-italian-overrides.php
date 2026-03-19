<?php
/**
 * Italian msgid → English msgstr for fp-privacy-en_US.po (admin UI, AI blocks, privacy template).
 *
 * @package FP\Privacy
 */

declare(strict_types=1);

$keys = json_decode(
	(string) file_get_contents( __DIR__ . '/../bin/empty-keys.json' ),
	true
);
$vals_a = json_decode(
	(string) file_get_contents( __DIR__ . '/en-us-values-chunk-a.json' ),
	true
);
$vals_b = json_decode(
	(string) file_get_contents( __DIR__ . '/en-us-values-chunk-b.json' ),
	true
);

if ( ! is_array( $keys ) || ! is_array( $vals_a ) || ! is_array( $vals_b ) ) {
	throw new RuntimeException( 'Invalid translation JSON chunks.' );
}

$vals = array_merge( $vals_a, $vals_b );
if ( count( $keys ) !== count( $vals ) ) {
	throw new RuntimeException(
		sprintf( 'Translation count mismatch: %d keys vs %d values.', count( $keys ), count( $vals ) )
	);
}

/** @var array<string, string> */
return array_combine( $keys, $vals );
