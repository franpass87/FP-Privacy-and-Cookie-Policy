<?php
/**
 * Build en-us-values-chunk-b.json (36 strings, indices 37–72 of empty-keys.json).
 */
declare(strict_types=1);

$chunk_b = array(
	'Script handles to block (one per line)' => 'Script handles to block (one per line)',
	'Style handles to block (one per line)'  => 'Style handles to block (one per line)',
);

$chunk_b += array(
	'La granularità avanzata del consenso (EDPB 2025) consente agli utenti di controllare individualmente ogni servizio rilevato, non solo le categorie principali.' => 'Advanced consent granularity (EDPB 2025) lets users control each detected service individually, not only the main categories.',
	'Quando abilitato, ogni servizio rilevato (es: Google Analytics 4, Google Tag Manager, Facebook Pixel) avrà un toggle individuale nella modal preferenze.' => 'When enabled, each detected service (e.g. Google Analytics 4, Google Tag Manager, Facebook Pixel) gets its own toggle in the preferences modal.',
	'Questo migliora la conformità con le linee guida EDPB 2025 sulla granularità del consenso.' => 'This improves alignment with EDPB 2025 guidance on consent granularity.',
	'Abilita la granularità avanzata del consenso per conformità EDPB 2025.' => 'Enable advanced consent granularity for EDPB 2025 compliance.',
	'Granularità Consenso Avanzata' => 'Advanced consent granularity',
	'Abilita toggle individuali per servizi' => 'Enable per-service toggles',
	'Quando abilitato, gli utenti possono controllare individualmente ogni servizio rilevato (es: GA4, GTM, Facebook Pixel) invece di accettare/rifiutare solo le categorie principali. Questo migliora la conformità con le linee guida EDPB 2025.' => 'When enabled, users can control each detected service individually (e.g. GA4, GTM, Facebook Pixel) instead of only accepting or rejecting main categories. This improves alignment with EDPB 2025 guidance.',
	'Salva impostazioni privacy' => 'Save privacy settings',
);

$keys = json_decode( (string) file_get_contents( __DIR__ . '/empty-keys.json' ), true );
if ( ! is_array( $keys ) ) {
	throw new RuntimeException( 'empty-keys.json invalid' );
}

$extra_path = __DIR__ . '/chunk-b-extra.json';
$extra      = json_decode( (string) file_get_contents( $extra_path ), true );
if ( ! is_array( $extra ) || 26 !== count( $extra ) ) {
	throw new RuntimeException( 'chunk-b-extra.json must contain exactly 26 strings.' );
}

$slice = array_slice( $keys, 37, 36 );
$vals  = array();
$xi    = 0;
foreach ( $slice as $k ) {
	if ( isset( $chunk_b[ $k ] ) ) {
		$vals[] = $chunk_b[ $k ];
		continue;
	}
	$vals[] = $extra[ $xi++ ];
}
if ( 26 !== $xi ) {
	throw new RuntimeException( sprintf( 'chunk-b-extra consumed %d entries, expected 26.', $xi ) );
}

file_put_contents(
	dirname( __DIR__ ) . '/languages/en-us-values-chunk-b.json',
	json_encode( $vals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n"
);
echo 'Wrote chunk-b with ' . count( $vals ) . " entries.\n";
