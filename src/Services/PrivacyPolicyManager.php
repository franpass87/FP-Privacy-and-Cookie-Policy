<?php

/**
 * Privacy Policy Manager Service
 *
 * @package FP\Privacy\Services
 */

namespace FP\Privacy\Services;

class PrivacyPolicyManager
{
    /**
     * Generate a GDPR-compliant privacy policy template
     */
    public function generateTemplate(array $options = []): string
    {
        $siteName = get_bloginfo('name');
        $siteUrl = home_url();
        $adminEmail = get_option('admin_email');

        ob_start();
        ?>
        <h1>Privacy Policy di <?php echo esc_html($siteName); ?></h1>

        <p>Ultimo aggiornamento: <?php echo esc_html(date('d/m/Y')); ?></p>

        <h2>1. Introduzione</h2>
        <p>La presente Privacy Policy descrive le modalità di trattamento dei dati personali degli utenti che visitano il sito <?php echo esc_html($siteUrl); ?>.</p>

        <h2>2. Titolare del Trattamento</h2>
        <p>Il Titolare del trattamento è <?php echo esc_html($siteName); ?>, contattabile all'indirizzo email: <?php echo esc_html($adminEmail); ?>.</p>

        <h2>3. Tipologie di Dati Raccolti</h2>
        <p>Questo sito raccoglie le seguenti tipologie di dati:</p>
        <ul>
            <li><strong>Dati di navigazione:</strong> Indirizzo IP, tipo di browser, sistema operativo, durata della visita</li>
            <li><strong>Cookie:</strong> Il sito utilizza cookie tecnici e, previo consenso, cookie di profilazione e di terze parti</li>
        </ul>

        <h2>4. Finalità del Trattamento</h2>
        <p>I dati personali sono trattati per le seguenti finalità:</p>
        <ul>
            <li>Garantire il corretto funzionamento del sito</li>
            <li>Analizzare le statistiche di utilizzo (solo con consenso)</li>
            <li>Migliorare l'esperienza utente</li>
        </ul>

        <h2>5. Base Giuridica del Trattamento</h2>
        <p>Il trattamento dei dati è basato su:</p>
        <ul>
            <li>Consenso dell'interessato (art. 6, par. 1, lett. a) GDPR)</li>
            <li>Esecuzione di misure precontrattuali o contrattuali (art. 6, par. 1, lett. b) GDPR)</li>
            <li>Legittimo interesse del Titolare (art. 6, par. 1, lett. f) GDPR)</li>
        </ul>

        <h2>6. Modalità di Trattamento</h2>
        <p>I dati sono trattati con strumenti automatizzati per il tempo strettamente necessario a conseguire gli scopi per cui sono stati raccolti.</p>

        <h2>7. Cookie</h2>
        <p>Questo sito utilizza cookie per migliorare l'esperienza di navigazione. Per maggiori informazioni, consulta la nostra <a href="<?php echo esc_url(admin_url('admin.php?page=fp-privacy-scanner')); ?>">Cookie Policy</a>.</p>

        <h2>8. Diritti dell'Interessato</h2>
        <p>Ai sensi degli articoli 15-22 del GDPR, l'utente ha diritto di:</p>
        <ul>
            <li>Accedere ai propri dati personali</li>
            <li>Richiedere la rettifica o la cancellazione</li>
            <li>Limitare il trattamento</li>
            <li>Opporsi al trattamento</li>
            <li>Richiedere la portabilità dei dati</li>
            <li>Revocare il consenso in qualsiasi momento</li>
        </ul>

        <h2>9. Contatti</h2>
        <p>Per esercitare i tuoi diritti o per qualsiasi informazione, contattaci a: <?php echo esc_html($adminEmail); ?></p>

        <h2>10. Modifiche alla Privacy Policy</h2>
        <p>Questa Privacy Policy può essere aggiornata periodicamente. Ti invitiamo a consultarla regolarmente.</p>
        <?php

        return ob_get_clean();
    }
}

