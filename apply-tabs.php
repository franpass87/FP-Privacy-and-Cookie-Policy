<?php
/**
 * Script per applicare automaticamente le tabs ai settings
 * 
 * UTILIZZO:
 * 1. Esegui: php apply-tabs.php
 * 2. Ricarica la pagina settings in WordPress
 */

$file = __DIR__ . '/src/Admin/SettingsRenderer.php';
$backup = __DIR__ . '/src/Admin/SettingsRenderer.php.backup';

echo "=== APPLICAZIONE TABS UPDATE ===\n\n";

// 1. Backup
if ( ! file_exists( $backup ) ) {
	copy( $file, $backup );
	echo "✅ Backup creato: SettingsRenderer.php.backup\n";
} else {
	echo "⚠️  Backup già esistente\n";
}

// 2. Leggi il file
$content = file_get_contents( $file );

// 3. Inserisci tabs navigation prima del form
$tabs_nav = <<<'HTML'

<!-- Tabs Navigation -->
<nav class="fp-privacy-tabs-nav">
	<button type="button" class="fp-privacy-tab-button active" data-tab="banner">
		<span class="dashicons dashicons-admin-appearance"></span>
		<span><?php \esc_html_e( 'Banner e Aspetto', 'fp-privacy' ); ?></span>
	</button>
	<button type="button" class="fp-privacy-tab-button" data-tab="cookies">
		<span class="dashicons dashicons-admin-generic"></span>
		<span><?php \esc_html_e( 'Cookie e Script', 'fp-privacy' ); ?></span>
	</button>
	<button type="button" class="fp-privacy-tab-button" data-tab="privacy">
		<span class="dashicons dashicons-shield"></span>
		<span><?php \esc_html_e( 'Privacy e Consenso', 'fp-privacy' ); ?></span>
	</button>
	<button type="button" class="fp-privacy-tab-button" data-tab="advanced">
		<span class="dashicons dashicons-admin-tools"></span>
		<span><?php \esc_html_e( 'Avanzate', 'fp-privacy' ); ?></span>
	</button>
</nav>

HTML;

$content = str_replace(
	'<form method="post" action="<?php echo \esc_url( \admin_url( \'admin-post.php\' ) ); ?>" class="fp-privacy-settings-form">',
	$tabs_nav . '<form method="post" action="<?php echo \esc_url( \admin_url( \'admin-post.php\' ) ); ?>" class="fp-privacy-settings-form">',
	$content
);

echo "✅ Tabs navigation inserita\n";

// 4. TAB 1: Banner e Aspetto
$tab1_start = '<div class="fp-privacy-tab-content active" data-tab-content="banner">' . "\n\n";
$content = str_replace(
	'<h2><?php \esc_html_e( \'Languages\', \'fp-privacy\' ); ?></h2>',
	$tab1_start . '<h2><?php \esc_html_e( \'Languages\', \'fp-privacy\' ); ?></h2>',
	$content
);

$tab1_end = "\n\n" . '<?php \submit_button( \__( \'Salva impostazioni banner\', \'fp-privacy\' ), \'primary\', \'submit-banner\', false ); ?>' . "\n" . '</div>' . "\n\n";
$content = str_replace(
	'<h2><?php \esc_html_e( \'Consent Mode defaults\', \'fp-privacy\' ); ?></h2>',
	$tab1_end . '<h2><?php \esc_html_e( \'Consent Mode defaults\', \'fp-privacy\' ); ?></h2>',
	$content
);

echo "✅ Tab 1 (Banner e Aspetto) creata\n";

// 5. TAB 3: Privacy e Consenso (prima della Tab 2 perché Consent Mode viene prima)
$tab3_start = '<div class="fp-privacy-tab-content" data-tab-content="privacy">' . "\n\n";
$content = str_replace(
	$tab1_end . '<h2><?php \esc_html_e( \'Consent Mode defaults\', \'fp-privacy\' ); ?></h2>',
	$tab1_end . $tab3_start . '<h2><?php \esc_html_e( \'Consent Mode defaults\', \'fp-privacy\' ); ?></h2>',
	$content
);

$tab3_end = "\n\n" . '<?php \submit_button( \__( \'Salva impostazioni privacy\', \'fp-privacy\' ), \'primary\', \'submit-privacy\', false ); ?>' . "\n" . '</div>' . "\n\n";
$content = str_replace(
	'<h2><?php \esc_html_e( \'Integration alerts\', \'fp-privacy\' ); ?></h2>',
	$tab3_end . '<h2><?php \esc_html_e( \'Integration alerts\', \'fp-privacy\' ); ?></h2>',
	$content
);

echo "✅ Tab 3 (Privacy e Consenso) creata\n";

// 6. TAB 4: Avanzate
$tab4_start = '<div class="fp-privacy-tab-content" data-tab-content="advanced">' . "\n\n";
$content = str_replace(
	$tab3_end . '<h2><?php \esc_html_e( \'Integration alerts\', \'fp-privacy\' ); ?></h2>',
	$tab3_end . $tab4_start . '<h2><?php \esc_html_e( \'Integration alerts\', \'fp-privacy\' ); ?></h2>',
	$content
);

$tab4_end = "\n\n" . '<?php \submit_button( \__( \'Salva impostazioni avanzate\', \'fp-privacy\' ), \'primary\', \'submit-advanced\', false ); ?>' . "\n" . '</div>' . "\n\n";
$content = str_replace(
	'<h2><?php \esc_html_e( \'Script blocking\', \'fp-privacy\' ); ?></h2>',
	$tab4_end . '<h2><?php \esc_html_e( \'Script blocking\', \'fp-privacy\' ); ?></h2>',
	$content
);

echo "✅ Tab 4 (Avanzate) creata\n";

// 7. TAB 2: Cookie e Script (va alla fine, include Script blocking e Detected services)
$tab2_start = '<div class="fp-privacy-tab-content" data-tab-content="cookies">' . "\n\n";
$content = str_replace(
	$tab4_end . '<h2><?php \esc_html_e( \'Script blocking\', \'fp-privacy\' ); ?></h2>',
	$tab4_end . $tab2_start . '<h2><?php \esc_html_e( \'Script blocking\', \'fp-privacy\' ); ?></h2>',
	$content
);

// Sposta "Detected services" dentro il form (era fuori)
$detected_section = '<h2><?php \esc_html_e( \'Detected services\', \'fp-privacy\' ); ?></h2>' . "\n" .
					'<?php $this->render_detected_services( $detected ); ?>' . "\n\n" .
					'<p class="description"><?php \esc_html_e( \'Use the policy editor to regenerate your documents after services change.\', \'fp-privacy\' ); ?></p>';

// Rimuovi dalla posizione attuale
$content = preg_replace(
	'/<h2><\?php \\\\esc_html_e\( \'Detected services\', \'fp-privacy\' \); \?><\/h2>.*?<\/p>/s',
	'',
	$content
);

$tab2_end = "\n\n" . $detected_section . "\n\n" . 
			'<?php \submit_button( \__( \'Salva impostazioni cookie\', \'fp-privacy\' ), \'primary\', \'submit-cookies\', false ); ?>' . "\n" . 
			'</div>';

$content = str_replace(
	'<?php \submit_button( \__( \'Save settings\', \'fp-privacy\' ) ); ?>' . "\n" . '</form>',
	$tab2_end . "\n" . '</form>',
	$content
);

echo "✅ Tab 2 (Cookie e Script) creata\n";

// 8. Salva il file modificato
file_put_contents( $file, $content );

echo "\n✅ TABS APPLICATE CON SUCCESSO!\n\n";
echo "📋 Prossimi passi:\n";
echo "   1. Ricarica la pagina settings in WordPress (CTRL+F5)\n";
echo "   2. Verifica che le tabs funzionino\n";
echo "   3. Prova il salvataggio da ogni tab\n\n";
echo "💡 Per ripristinare il backup:\n";
echo "   cp {$backup} {$file}\n\n";

