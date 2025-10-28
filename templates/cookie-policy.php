<?php
/**
 * Cookie policy template.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

$retention       = isset( $options['retention_days'] ) ? (int) $options['retention_days'] : 180;
$generated_at    = isset( $generated_at ) ? (int) $generated_at : 0;
$date_format     = (string) get_option( 'date_format' );
$time_format     = (string) get_option( 'time_format' );
$display_format  = trim( $date_format . ' ' . $time_format );
$categories_meta = isset( $categories_meta ) && is_array( $categories_meta ) ? $categories_meta : array();

if ( ! function_exists( 'fp_privacy_format_service_cookies' ) ) {
    /**
     * Format service cookies into readable strings.
     *
     * @param mixed $cookies Raw cookies array.
     *
     * @return array<int, string>
     */
    function fp_privacy_format_service_cookies( $cookies ) {
        if ( ! is_array( $cookies ) ) {
            return array();
        }

        $formatted = array();

        foreach ( $cookies as $cookie ) {
            if ( ! is_array( $cookie ) ) {
                continue;
            }

            $name        = isset( $cookie['name'] ) ? (string) $cookie['name'] : '';
            $domain      = isset( $cookie['domain'] ) ? (string) $cookie['domain'] : '';
            $duration    = isset( $cookie['duration'] ) ? (string) $cookie['duration'] : '';
            $description = isset( $cookie['description'] ) ? (string) $cookie['description'] : '';

            $details = array();

            if ( '' !== $domain ) {
                $details[] = sprintf( 'Dominio: %s', $domain );
            }

            if ( '' !== $duration ) {
                $details[] = sprintf( 'Durata: %s', $duration );
            }

            if ( '' !== $description ) {
                $details[] = $description;
            }

            if ( '' === $name && empty( $details ) ) {
                continue;
            }

            $label = '' !== $name ? $name : 'Cookie senza nome';

            if ( $details ) {
                $label .= ' (' . implode( ' — ', $details ) . ')';
            }

            $formatted[] = $label;
        }

        return $formatted;
    }
}

if ( ! function_exists( 'fp_privacy_get_service_value' ) ) {
    /**
     * Safely fetch a scalar service value.
     *
     * @param mixed  $service Service payload.
     * @param string $key     Array key to retrieve.
     *
     * @return string
     */
    function fp_privacy_get_service_value( $service, $key ) {
        if ( ! is_array( $service ) || ! isset( $service[ $key ] ) ) {
            return '';
        }

        $value = $service[ $key ];

        if ( is_scalar( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
            return (string) $value;
        }

        return '';
    }
}

if ( '' === $display_format ) {
    $display_format = 'F j, Y';
}

$last_generated = '';

if ( $generated_at > 0 ) {
    $last_generated = wp_date( $display_format, $generated_at );
}

if ( '' === $last_generated ) {
    $last_generated = wp_date( $display_format );
}
?>
<section class="fp-cookie-policy">
<h2>Informazioni sui cookie e tecnologie di tracciamento</h2>
<p>I cookie sono piccoli file di testo memorizzati sul tuo dispositivo insieme a tecnologie simili come local storage, SDK o pixel. Consentono funzionalità essenziali, memorizzano le tue preferenze e ci aiutano a misurare le interazioni. Ad eccezione dei cookie strettamente necessari, inseriamo cookie solo dopo aver ottenuto il tuo consenso esplicito in conformità con il GDPR e la Direttiva ePrivacy.</p>

<h2>Conformità normativa</h2>
<p>L'utilizzo dei cookie si basa sul tuo consenso ai sensi degli articoli 6.1.a e 7 del GDPR, dell'articolo 5(3) della Direttiva ePrivacy e delle più recenti linee guida emesse dalle autorità di controllo europee fino a ottobre 2025. La prova del consenso viene conservata in modo sicuro e può essere fornita alle autorità di controllo su richiesta.</p>

<h2>Tipi di cookie e tecnologie</h2>
<p>Classifichiamo i cookie e gli identificatori simili come strumenti strettamente necessari, di prestazione, funzionali, di analisi, di marketing o di personalizzazione. Alcune tecnologie come il local storage o gli script di fingerprinting sono trattate con le stesse garanzie dei cookie e richiedono il tuo consenso quando non sono strettamente necessarie.</p>

<h2>Come utilizziamo i cookie</h2>
<p>Raggruppiamo i cookie in categorie in modo che tu possa personalizzare la tua esperienza. Ogni categoria contiene i servizi e le tecnologie descritti nelle tabelle sottostanti, inclusi provider, scopo, durata dei cookie e collegamenti a informazioni sulla privacy esterne, ove disponibili.</p>

<h2>Registrazione e conservazione del consenso</h2>
<p>Le tue preferenze vengono raccolte tramite il banner dei cookie o il centro preferenze dedicato utilizzando interruttori granulari. Registriamo lo stato del consenso, il timestamp, le informazioni sul dispositivo e la versione di questa policy per mantenere la responsabilità. Puoi ritirare o modificare il consenso in qualsiasi momento senza influire sulla liceità del trattamento passato.</p>

<h2>Conservazione del consenso</h2>
<p><?php echo esc_html( sprintf( 'Le tue scelte di consenso vengono conservate per %d giorni a meno che tu non le modifichi prima.', $retention ) ); ?></p>

<h2>Trasferimenti verso paesi terzi</h2>
<p>Alcuni provider potrebbero elaborare dati al di fuori dell'UE/SEE. Quando ciò si verifica, ci basiamo su decisioni di adeguatezza o Clausole Contrattuali Standard combinate con misure supplementari come crittografia, pseudonimizzazione e valutazioni d'impatto sui trasferimenti per garantire un livello di protezione equivalente.</p>

<h2>Gestione delle preferenze</h2>
<p>Puoi rivedere le tue preferenze utilizzando il pulsante delle preferenze sui cookie disponibile su ogni pagina o modificare le impostazioni del tuo browser per eliminare o bloccare i cookie. Il blocco dei cookie essenziali potrebbe influire sulla funzionalità del sito. Istruzioni dettagliate per i principali browser sono disponibili nel centro preferenze.</p>

<?php foreach ( $groups as $category => $services ) :
    $meta  = isset( $categories_meta[ $category ] ) && is_array( $categories_meta[ $category ] ) ? $categories_meta[ $category ] : array();
    $label = isset( $meta['label'] ) && '' !== $meta['label'] ? $meta['label'] : ucfirst( str_replace( '_', ' ', $category ) );
    $description = isset( $meta['description'] ) ? $meta['description'] : '';
    ?>
<div class="fp-cookie-category">
<h3><?php echo esc_html( $label ); ?></h3>
    <?php if ( $description ) : ?>
        <p class="fp-cookie-category-description"><?php echo wp_kses_post( $description ); ?></p>
    <?php endif; ?>
<table>
<thead>
<tr>
<th>Servizio</th>
<th>Scopo</th>
<th>Cookie</th>
<th>Conservazione</th>
</tr>
</thead>
<tbody>
<?php foreach ( $services as $service ) :
    if ( ! is_array( $service ) ) {
        continue;
    }

    $name      = fp_privacy_get_service_value( $service, 'name' );
    $purpose   = fp_privacy_get_service_value( $service, 'purpose' );
    $retention = fp_privacy_get_service_value( $service, 'retention' );
    $service_cookies = fp_privacy_format_service_cookies( isset( $service['cookies'] ) ? $service['cookies'] : array() );
    ?>
<tr>
<td><?php echo esc_html( $name ); ?></td>
<td><?php echo wp_kses_post( $purpose ); ?></td>
<td>
    <?php if ( ! empty( $service_cookies ) ) : ?>
        <span><?php echo esc_html( implode( '; ', $service_cookies ) ); ?></span>
    <?php else : ?>
        <span>Nessun cookie dichiarato.</span>
    <?php endif; ?>
</td>
<td><?php echo esc_html( $retention ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endforeach; ?>

<h2>Controlli aggiuntivi</h2>
<p>Puoi anche utilizzare strumenti forniti da terze parti, come piattaforme di opt-out del settore per i cookie pubblicitari o impostazioni a livello di dispositivo che reimpostano gli identificatori mobili. Dove disponibile, ci integriamo con framework di consenso (ad esempio IAB TCF 2.2) per rispettare le tue scelte tra i fornitori partecipanti.</p>

<h2>I tuoi diritti</h2>
<p>Per ulteriori informazioni su come trattiamo i dati personali e su come esercitare i tuoi diritti di accesso, rettifica, cancellazione, limitazione, opposizione, portabilità o per presentare un reclamo a un'autorità di controllo, consulta la nostra informativa sulla privacy.</p>

<h2>Revisioni della policy</h2>
<p>Rivediamo questa cookie policy ogni volta che aggiungiamo nuovi servizi, modifichiamo i periodi di conservazione o quando i requisiti normativi evolvono. La versione corrente incorpora le linee guida disponibili fino a ottobre 2025 e qualsiasi modifica futura verrà pubblicata su questa pagina.</p>

<h2>Ultimo aggiornamento</h2>
<p><?php echo esc_html( sprintf( 'Questa policy è stata generata il %s.', $last_generated ) ); ?></p>
</section>
