<?php
$k = json_decode( file_get_contents( __DIR__ . '/empty-keys.json' ), true );
echo count( $k );
