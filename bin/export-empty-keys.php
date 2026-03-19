<?php
declare(strict_types=1);
$po = file_get_contents( dirname( __DIR__ ) . '/languages/fp-privacy-en_US.po' );
$lines = preg_split( '/\R/', $po );
$keys  = array();
for ( $i = 0, $n = count( $lines ); $i < $n; $i++ ) {
	if ( preg_match( '/^msgid "(.*)"\s*$/', $lines[ $i ], $m ) ) {
		$nx = ( $i + 1 < $n ) ? trim( $lines[ $i + 1 ] ) : '';
		if ( $nx === 'msgstr ""' ) {
			$id = stripcslashes( $m[1] );
			if ( '' !== $id ) {
				$keys[] = $id;
			}
		}
	}
}
file_put_contents(
	dirname( __DIR__ ) . '/bin/empty-keys-serialized.txt',
	serialize( $keys )
);
echo count( $keys ) . " keys exported\n";
