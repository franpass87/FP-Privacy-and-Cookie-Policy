<?php
declare(strict_types=1);
$p = dirname( __DIR__ ) . '/languages/fp-privacy-en_US.po';
$lines = file( $p, FILE_IGNORE_NEW_LINES );
for ( $i = 0, $n = count( $lines ); $i < $n; $i++ ) {
	if ( preg_match( '/^msgid\s+"(.*)"\s*$/', $lines[ $i ], $m ) ) {
		$id = stripcslashes( $m[1] );
		$nx = ( $i + 1 < $n ) ? trim( $lines[ $i + 1 ] ) : '';
		if ( '' !== $id && $nx === 'msgstr ""' ) {
			echo json_encode( $id, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
		}
	}
}
