<?php
/**
 * Force update category labels and descriptions with translations
 * 
 * Run this after updating the category defaults to use __() translations
 * 
 * @package FP\Privacy
 */

// Can be run via CLI or browser
if ( ! defined( 'ABSPATH' ) ) {
	$wp_load = __DIR__ . '/../../../wp-load.php';
	if ( file_exists( $wp_load ) ) {
		require_once $wp_load;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	die( "WordPress non caricato correttamente.\n" );
}

// Set text/plain header for browser
if ( php_sapi_name() !== 'cli' ) {
	header( 'Content-Type: text/plain; charset=utf-8' );
}

echo "=== Aggiornamento Categorie FP Privacy ===\n\n";

// Get current options
$option_key = 'fp_privacy_options';
$options = get_option( $option_key );

if ( ! $options || ! is_array( $options ) ) {
	die( "❌ Opzioni FP Privacy non trovate nel database.\n" );
}

echo "✓ Opzioni caricate dal database\n";

// Get active languages
$languages = isset( $options['languages_active'] ) && is_array( $options['languages_active'] ) 
	? $options['languages_active'] 
	: array( 'it_IT' );

echo "✓ Lingue attive: " . implode( ', ', $languages ) . "\n\n";

// Define new translated categories
$category_defaults = array(
	'necessary'   => array(
		'label'       => array( 
			'it_IT'   => 'Strettamente necessari',
			'en_US'   => 'Strictly necessary',
			'default' => 'Strettamente necessari',
		),
		'description' => array( 
			'it_IT'   => 'Cookie essenziali richiesti per il funzionamento del sito web e non possono essere disabilitati.',
			'en_US'   => 'Essential cookies required for the website to function and cannot be disabled.',
			'default' => 'Cookie essenziali richiesti per il funzionamento del sito web e non possono essere disabilitati.',
		),
		'locked'      => true,
	),
	'preferences' => array(
		'label'       => array( 
			'it_IT'   => 'Preferenze',
			'en_US'   => 'Preferences',
			'default' => 'Preferenze',
		),
		'description' => array( 
			'it_IT'   => 'Memorizzano le preferenze utente come lingua o posizione.',
			'en_US'   => 'Store user preferences such as language or location.',
			'default' => 'Memorizzano le preferenze utente come lingua o posizione.',
		),
		'locked'      => false,
	),
	'statistics'  => array(
		'label'       => array( 
			'it_IT'   => 'Statistiche',
			'en_US'   => 'Statistics',
			'default' => 'Statistiche',
		),
		'description' => array( 
			'it_IT'   => 'Raccolgono statistiche anonime per migliorare i nostri servizi.',
			'en_US'   => 'Collect anonymous statistics to improve our services.',
			'default' => 'Raccolgono statistiche anonime per migliorare i nostri servizi.',
		),
		'locked'      => false,
	),
	'marketing'   => array(
		'label'       => array( 
			'it_IT'   => 'Marketing',
			'en_US'   => 'Marketing',
			'default' => 'Marketing',
		),
		'description' => array( 
			'it_IT'   => 'Abilitano la pubblicità personalizzata e il tracciamento.',
			'en_US'   => 'Enable personalized advertising and tracking.',
			'default' => 'Abilitano la pubblicità personalizzata e il tracciamento.',
		),
		'locked'      => false,
	),
);

// Update categories
if ( ! isset( $options['categories'] ) || ! is_array( $options['categories'] ) ) {
	$options['categories'] = array();
}

$updated_count = 0;

foreach ( $category_defaults as $slug => $new_data ) {
	if ( ! isset( $options['categories'][ $slug ] ) ) {
		$options['categories'][ $slug ] = array(
			'label'       => $new_data['label'],
			'description' => $new_data['description'],
			'locked'      => $new_data['locked'],
			'services'    => array(),
		);
		echo "✓ Creata categoria: {$slug}\n";
		$updated_count++;
	} else {
		// Update existing category
		$options['categories'][ $slug ]['label'] = $new_data['label'];
		$options['categories'][ $slug ]['description'] = $new_data['description'];
		$options['categories'][ $slug ]['locked'] = $new_data['locked'];
		
		// Preserve existing services
		if ( ! isset( $options['categories'][ $slug ]['services'] ) ) {
			$options['categories'][ $slug ]['services'] = array();
		}
		
		echo "✓ Aggiornata categoria: {$slug}\n";
		$updated_count++;
	}
}

// Save updated options
$result = update_option( $option_key, $options, false );

if ( $result ) {
	echo "\n✅ Opzioni aggiornate con successo!\n";
	echo "   {$updated_count} categorie aggiornate\n\n";
	
	echo "=== Verifica Finale ===\n";
	foreach ( array_keys( $category_defaults ) as $slug ) {
		if ( isset( $options['categories'][ $slug ] ) ) {
			$cat = $options['categories'][ $slug ];
			echo "\n{$slug}:\n";
			echo "  - Label IT: " . ( $cat['label']['it_IT'] ?? 'N/A' ) . "\n";
			echo "  - Label EN: " . ( $cat['label']['en_US'] ?? 'N/A' ) . "\n";
			echo "  - Locked: " . ( $cat['locked'] ? 'Sì' : 'No' ) . "\n";
		}
	}
	
	echo "\n✅ Completato! Le categorie sono ora tradotte correttamente.\n";
	echo "   Svuota la cache del browser e ricarica la pagina.\n";
} else {
	echo "\n❌ Errore durante il salvataggio delle opzioni.\n";
	exit( 1 );
}

