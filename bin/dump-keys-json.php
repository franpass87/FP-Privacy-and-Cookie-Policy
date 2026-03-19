<?php
declare(strict_types=1);
$path = __DIR__ . '/empty-keys-serialized.txt';
$keys = unserialize( file_get_contents( $path ), array( 'allowed_classes' => false ) );
file_put_contents( __DIR__ . '/empty-keys.json', json_encode( $keys, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
