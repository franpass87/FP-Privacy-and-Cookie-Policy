<?php
/**
 * Privacy policy template.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

$org      = isset( $options['org_name'] ) ? $options['org_name'] : '';
$address  = isset( $options['address'] ) ? $options['address'] : '';
$dpo_name = isset( $options['dpo_name'] ) ? $options['dpo_name'] : '';
$dpo_mail = isset( $options['dpo_email'] ) ? $options['dpo_email'] : '';
$privacy_mail = isset( $options['privacy_email'] ) ? $options['privacy_email'] : '';
$vat      = isset( $options['vat'] ) ? $options['vat'] : '';
$generated_at    = isset( $generated_at ) ? (int) $generated_at : 0;
$categories_meta = isset( $categories_meta ) && is_array( $categories_meta ) ? $categories_meta : array();

$date_format  = (string) get_option( 'date_format' );
$time_format  = (string) get_option( 'time_format' );
$display_date = trim( $date_format . ' ' . $time_format );

if ( '' === $display_date ) {
    $display_date = 'F j, Y';
}

$last_generated = $generated_at > 0 ? wp_date( $display_date, $generated_at ) : wp_date( $display_date );

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
                $details[] = sprintf( /* translators: %s: cookie domain. */ __( 'Domain: %s', 'fp-privacy' ), $domain );
            }

            if ( '' !== $duration ) {
                $details[] = sprintf( /* translators: %s: cookie duration. */ __( 'Duration: %s', 'fp-privacy' ), $duration );
            }

            if ( '' !== $description ) {
                $details[] = $description;
            }

            if ( '' === $name && empty( $details ) ) {
                continue;
            }

            $label = '' !== $name ? $name : __( 'Unnamed cookie', 'fp-privacy' );

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
// Genera lista delle sezioni per il sommario
$sections = array(
    'overview' => esc_html__( 'Panoramica', 'fp-privacy' ),
    'definitions' => esc_html__( 'Definizioni', 'fp-privacy' ),
    'data-controller' => esc_html__( 'Titolare del trattamento', 'fp-privacy' ),
    'applicable-laws' => esc_html__( 'Normative applicabili', 'fp-privacy' ),
    'data-categories' => esc_html__( 'Categorie di dati che trattiamo', 'fp-privacy' ),
    'data-sources' => esc_html__( 'Origine dei dati', 'fp-privacy' ),
    'mandatory-optional' => esc_html__( 'Dati obbligatori e facoltativi', 'fp-privacy' ),
    'processing-purposes' => esc_html__( 'Purposes of processing', 'fp-privacy' ),
    'legal-bases' => esc_html__( 'Basi giuridiche', 'fp-privacy' ),
    'recipients' => esc_html__( 'Destinatari e trasferimenti di dati', 'fp-privacy' ),
    'processors' => esc_html__( 'Responsabili del trattamento e personale autorizzato', 'fp-privacy' ),
    'security-measures' => esc_html__( 'Misure di sicurezza', 'fp-privacy' ),
    'automated-decision' => esc_html__( 'Processo decisionale automatizzato e profilazione', 'fp-privacy' ),
    'ai-disclosure' => esc_html__( 'Trattamento dati per sistemi AI', 'fp-privacy' ),
    'algorithmic-transparency' => esc_html__( 'Trasparenza Algoritmica', 'fp-privacy' ),
    'retention' => esc_html__( 'Conservazione', 'fp-privacy' ),
    'data-subject-rights' => esc_html__( 'Diritti dell\'interessato', 'fp-privacy' ),
    'exercise-rights' => esc_html__( 'Come esercitare i tuoi diritti', 'fp-privacy' ),
    'consent-withdrawal' => esc_html__( 'Revoca del consenso e gestione dei cookie', 'fp-privacy' ),
    'minors-data' => esc_html__( 'Dati dei minori', 'fp-privacy' ),
    'data-breach' => esc_html__( 'Gestione delle violazioni dei dati', 'fp-privacy' ),
    'dpo' => esc_html__( 'Responsabile della Protezione dei Dati', 'fp-privacy' ),
    'supervisory-authority' => esc_html__( 'Contatto autorità di controllo', 'fp-privacy' ),
    'governance' => esc_html__( 'Governance e aggiornamenti dell\'informativa', 'fp-privacy' ),
    'services-cookies' => esc_html__( 'Servizi e cookie', 'fp-privacy' ),
    'last-update' => esc_html__( 'Ultimo aggiornamento', 'fp-privacy' ),
);
?>
<section class="fp-privacy-policy">
<?php if ( ! empty( $sections ) ) : ?>
<nav class="fp-privacy-toc" aria-label="<?php esc_attr_e( 'Table of contents', 'fp-privacy' ); ?>">
    <h2><?php esc_html_e( 'Table of contents', 'fp-privacy' ); ?></h2>
    <ul class="fp-privacy-toc-list">
        <?php foreach ( $sections as $id => $title ) : ?>
            <?php
            // Salta sezioni condizionali che potrebbero non essere presenti
            if ( 'dpo' === $id && ! $dpo_name && ! $dpo_mail ) {
                continue;
            }
            if ( 'ai-disclosure' === $id ) {
                $ai_disclosure = isset( $ai_disclosure ) ? $ai_disclosure : '';
                if ( empty( $ai_disclosure ) ) {
                    continue;
                }
            }
            if ( 'algorithmic-transparency' === $id ) {
                $algorithmic_transparency = isset( $algorithmic_transparency ) ? $algorithmic_transparency : '';
                if ( empty( $algorithmic_transparency ) ) {
                    continue;
                }
            }
            ?>
            <li><a href="#fp-privacy-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php endif; ?>

<h2 id="fp-privacy-overview"><?php echo esc_html__( 'Panoramica', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'La presente informativa sulla privacy spiega come raccogliamo, utilizziamo, conserviamo, condividiamo e proteggiamo i dati personali quando visiti o interagisci con il nostro sito web e i nostri servizi. Ci impegniamo a trattare i dati personali in piena conformità con il Regolamento (UE) 2016/679 (Regolamento Generale sulla Protezione dei Dati, "GDPR"), la Direttiva ePrivacy 2002/58/CE come modificata e recepita negli Stati membri dell\'UE, le leggi nazionali sulla privacy applicabili e le linee guida pertinenti emanate dal Comitato Europeo per la Protezione dei Dati (EDPB) e dalle autorità di controllo nazionali. La presente informativa riflette lo stato delle linee guida sulla privacy dell\'UE e del SEE aggiornate a ottobre 2025 e incorpora la giurisprudenza recente della Corte di Giustizia dell\'Unione Europea (CGUE). Siamo trasparenti riguardo alle nostre attività di trattamento dei dati e ti forniamo un controllo significativo sulle tue informazioni personali.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-definitions"><?php echo esc_html__( 'Definizioni', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Ai fini della presente informativa: "Dati personali" indica qualsiasi informazione relativa a una persona fisica identificata o identificabile (interessato); si considera identificabile la persona fisica che può essere identificata, direttamente o indirettamente, con particolare riferimento a un identificativo come il nome, un numero di identificazione, dati relativi all\'ubicazione, un identificativo online o a uno o più elementi caratteristici della sua identità fisica, fisiologica, genetica, psichica, economica, culturale o sociale. "Trattamento" indica qualsiasi operazione o insieme di operazioni, compiute con o senza l\'ausilio di processi automatizzati e applicate a dati personali o insiemi di dati personali, come la raccolta, la registrazione, l\'organizzazione, la strutturazione, la conservazione, l\'adattamento o la modifica, l\'estrazione, la consultazione, l\'uso, la comunicazione mediante trasmissione, diffusione o qualsiasi altra forma di messa a disposizione, il raffronto o l\'interconnessione, la limitazione, la cancellazione o la distruzione. "Titolare del trattamento" indica la persona fisica o giuridica che determina le finalità e i mezzi del trattamento di dati personali. "Responsabile del trattamento" indica la persona fisica o giuridica che tratta dati personali per conto del titolare del trattamento. "Servizi" si riferisce al nostro sito web, alle applicazioni, alle funzionalità, ai contenuti e ai servizi correlati che offriamo o mettiamo a tua disposizione.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-data-controller"><?php echo esc_html__( 'Titolare del trattamento', 'fp-privacy' ); ?></h2>
<p>
<?php echo esc_html( $org ); ?>
<?php if ( $vat ) : ?> — <?php echo esc_html( sprintf( __( 'P.IVA/Codice fiscale: %s', 'fp-privacy' ), $vat ) ); ?><?php endif; ?><br/>
<?php echo esc_html( $address ); ?><br/>
<?php if ( $privacy_mail ) : ?><?php echo esc_html( sprintf( __( 'Contatto: %s', 'fp-privacy' ), $privacy_mail ) ); ?><?php endif; ?>
</p>

<h2 id="fp-privacy-applicable-laws"><?php echo esc_html__( 'Normative applicabili', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Tutte le attività di trattamento sono svolte in stretta conformità con il GDPR (Regolamento (UE) 2016/679), la Direttiva ePrivacy (Direttiva 2002/58/CE) come recepita nella legislazione nazionale degli Stati membri dell\'UE, le norme sulla protezione dei consumatori ai sensi del Regolamento (UE) 2016/679, la Direttiva 2005/29/CE relativa alle pratiche commerciali sleali e qualsiasi obbligo settoriale specifico applicabile ai nostri servizi. Rispettiamo inoltre le leggi nazionali sulla protezione dei dati che attuano questi quadri normativi europei, comprese le disposizioni sulla riservatezza delle comunicazioni elettroniche, il marketing diretto, i cookie e tecnologie di tracciamento simili. Le nostre attività di trattamento rispettano i diritti e le libertà fondamentali degli interessati come garantiti dalla Carta dei diritti fondamentali dell\'Unione europea, in particolare l\'articolo 7 (rispetto della vita privata e familiare) e l\'articolo 8 (protezione dei dati personali). Seguiamo le raccomandazioni e le linee guida emanate dal Comitato europeo per la protezione dei dati, dal Gruppo di lavoro Articolo 29 e dalle autorità di controllo nazionali competenti.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-data-categories"><?php echo esc_html__( 'Categorie di dati che trattiamo', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'A seconda di come interagisci con il nostro sito web e i nostri servizi, potremmo trattare diverse categorie di dati personali tra cui: dati identificativi e di contatto (come nome completo, indirizzo email, numero di telefono, indirizzo postale, nome utente); dati di account e autenticazione (come password, domande di sicurezza, token di autenticazione); dati tecnici (come indirizzo IP, tipo e versione del browser, sistema operativo, identificatori del dispositivo, token univoci del dispositivo, identificatori pubblicitari, informazioni di connessione, log di accesso, URL di riferimento); dati di utilizzo e comportamentali (come pagine visitate, funzionalità utilizzate, azioni intraprese, pattern di clic, movimenti del mouse, profondità di scorrimento, tempo trascorso sulle pagine, query di ricerca, timestamp delle interazioni); dati di geolocalizzazione (come posizione GPS precisa quando viene concessa l\'autorizzazione, o posizione approssimativa derivata dall\'indirizzo IP); dati di preferenze e impostazioni (come scelte di consenso sui cookie, preferenze di marketing, preferenze linguistiche, impostazioni di accessibilità, preferenze di notifica); dati transazionali e commerciali (come cronologia degli acquisti, dettagli di pagamento, informazioni di fatturazione, record degli ordini); dati di comunicazione (come corrispondenza con l\'assistenza clienti, feedback, risposte a sondaggi, trascrizioni di chat); e dati di profilo e derivati (come interessi dedotti, preferenze, caratteristiche demografiche basate sulle tue interazioni). Categorie aggiuntive di informazioni raccolte da specifici servizi di terze parti integrati nel nostro sito web sono descritte in dettaglio nella tabella dei servizi e dei cookie di seguito, insieme alle rispettive finalità e basi giuridiche.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-data-sources"><?php echo esc_html__( 'Origine dei dati', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Raccogliamo dati personali da varie fonti: (1) Dati che fornisci direttamente: quando ti registri per un account, compili moduli, ti iscrivi a newsletter, effettui acquisti, richiedi informazioni, partecipi a sondaggi, comunichi con l\'assistenza clienti, pubblichi contenuti, partecipi a funzionalità della community o comunque invii volontariamente informazioni attraverso i nostri servizi. (2) Dati che raccogliamo automaticamente: quando visiti o utilizzi i nostri servizi, raccogliamo automaticamente determinati dati tecnici e di utilizzo tramite cookie, web beacon, pixel, archiviazione locale, log del server e tecnologie di tracciamento simili. Ciò include informazioni sul tuo dispositivo, browser, indirizzo IP, pagine visualizzate, funzionalità utilizzate, timestamp, fonti di riferimento e pattern di navigazione. Consulta la nostra Informativa sui Cookie per informazioni dettagliate su cookie e tecnologie simili. (3) Dati da terze parti: potremmo ricevere dati personali da partner commerciali fidati, fornitori di servizi, fornitori di analisi, reti pubblicitarie, piattaforme di social media (quando connetti il tuo account o interagisci con funzionalità social), processori di pagamento, servizi di prevenzione delle frodi, fornitori di arricchimento dati e fonti pubblicamente accessibili (come registri pubblici, directory, profili di social media impostati come pubblici) quando legalmente consentito e necessario per scopi commerciali legittimi. (4) Dati da fonti combinate: potremmo combinare dati raccolti da diverse fonti per creare un quadro più completo dei nostri utenti, migliorare i nostri servizi, personalizzare le esperienze e rafforzare la sicurezza, sempre nel rispetto dei requisiti applicabili in materia di protezione dei dati.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-mandatory-optional"><?php echo esc_html__( 'Dati obbligatori e facoltativi', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Ogni volta che vengono richiesti dati personali tramite moduli o altre interfacce, distinguiamo e indichiamo chiaramente quali campi dati sono obbligatori (richiesti) per fornire il servizio richiesto e quali sono facoltativi (volontari). I campi obbligatori sono tipicamente contrassegnati con un asterisco (*) o un altro indicatore visivo chiaro, e spieghiamo perché l\'informazione è necessaria. Il rifiuto di condividere dati facoltativi non avrà conseguenze negative sulla tua capacità di utilizzare i nostri servizi, ricevere assistenza o esercitare i tuoi diritti. Tuttavia, la mancata fornitura di dati obbligatori potrebbe impedirci di evadere la tua richiesta, completare una transazione, creare un account, rispondere alla tua richiesta o fornire determinate funzionalità o servizi. In tali casi, spiegheremo le conseguenze della mancata fornitura delle informazioni richieste. La distinzione tra dati obbligatori e facoltativi si basa sui principi di necessità e proporzionalità ai sensi dell\'articolo 5(1)(c) del GDPR, garantendo che raccogliamo solo dati adeguati, pertinenti e limitati a quanto necessario per le finalità specificate.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-processing-purposes"><?php echo esc_html__( 'Purposes of processing', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Trattiamo i dati personali per le seguenti finalità specifiche: (1) Erogazione e prestazione dei servizi: per fornire, gestire, mantenere ed erogare i nostri servizi, funzionalità e caratteristiche; per creare e gestire account utente; per elaborare transazioni e evadere ordini; per fornire assistenza clienti e rispondere a richieste e domande; per inviare comunicazioni relative ai servizi, notifiche e aggiornamenti. (2) Sicurezza e prevenzione delle frodi: per rilevare, prevenire e rispondere a incidenti di sicurezza, frodi, abusi, attività illegali e violazioni dei nostri termini di servizio; per verificare l\'identità e autenticare gli utenti; per proteggere i diritti, la proprietà e la sicurezza della nostra organizzazione, degli utenti e del pubblico. (3) Analisi e misurazione delle prestazioni: per analizzare i pattern di utilizzo, misurare l\'efficacia, comprendere il comportamento degli utenti, monitorare le prestazioni del servizio, identificare problemi tecnici e generare informazioni statistiche; per condurre ricerca e sviluppo. (4) Miglioramento e innovazione del servizio: per migliorare le funzionalità esistenti, sviluppare nuove funzionalità e servizi, migliorare l\'esperienza utente, testare nuove funzionalità e ottimizzare i nostri contenuti, design e offerte. (5) Personalizzazione ed esperienze su misura: per fornire contenuti personalizzati, raccomandazioni, pubblicità ed esperienze adattate ai tuoi interessi e preferenze, solo dove hai concesso il relativo consenso o dove consentito dalla legge applicabile. (6) Marketing e comunicazioni: per inviare comunicazioni promozionali, newsletter, materiali di marketing e informazioni su prodotti e servizi che potrebbero interessarti, solo previo consenso quando richiesto dalla legge. (7) Conformità e obblighi legali: per conformarsi a obblighi legali, requisiti normativi, ordinanze del tribunale, richieste governative e per far valere i nostri diritti legali e accordi. (8) Operazioni commerciali: per gestire le nostre operazioni commerciali, mantenere registrazioni, condurre amministrazione interna, eseguire funzioni contabili e di revisione. Ogni finalità di trattamento è associata a una specifica base giuridica come dettagliato nella sezione Basi giuridiche di seguito.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-legal-bases"><?php echo esc_html__( 'Basi giuridiche', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Ogni attività di trattamento si basa su una o più delle seguenti basi giuridiche ai sensi dell\'articolo 6(1) del GDPR: (a) Consenso (Articolo 6.1.a GDPR): per attività di trattamento facoltative come analisi non essenziali, cookie di marketing, profilazione a scopi di marketing, pubblicità di terze parti, funzionalità di servizio facoltative e comunicazioni di marketing. Il consenso deve essere libero, specifico, informato e inequivocabile, fornito attraverso un\'azione affermativa chiara. Puoi ritirare il consenso in qualsiasi momento senza pregiudicare la liceità del trattamento basato sul consenso prima della sua revoca. (b) Necessità contrattuale (Articolo 6.1.b GDPR): quando il trattamento è necessario per l\'esecuzione di un contratto di cui sei parte (come i nostri Termini di Servizio) o per adottare misure su tua richiesta prima della conclusione di un contratto. Ciò include il trattamento necessario per fornire i servizi richiesti, creare e gestire account, elaborare pagamenti e consegnare prodotti o servizi acquistati. (c) Conformità con obblighi legali (Articolo 6.1.c GDPR): quando il trattamento è necessario per adempiere un obbligo legale al quale siamo soggetti, come requisiti fiscali e contabili, conformità normativa, risposte a richieste governative legittime, cooperazione con le forze dell\'ordine e obblighi di conservazione delle registrazioni. (d) Interessi legittimi (Articolo 6.1.f GDPR): quando il trattamento è necessario per il perseguimento del nostro interesse legittimo o di quello di una terza parte, a meno che tali interessi non siano superati dai tuoi interessi o diritti e libertà fondamentali. Ci basiamo sugli interessi legittimi per: sicurezza e prevenzione delle frodi; analisi essenziali per comprendere le prestazioni del servizio e i problemi tecnici; sicurezza della rete e delle informazioni; marketing diretto a clienti esistenti per prodotti simili; continuità aziendale e ripristino di emergenza; esercizio o difesa di diritti legali. Prima di fare affidamento sull\'interesse legittimo, conduciamo e documentiamo un test di bilanciamento (Valutazione dell\'Interesse Legittimo) che soppesa i nostri interessi rispetto ai tuoi diritti, considera la natura e la sensibilità dei dati, implementa adeguate garanzie e segue le più recenti linee guida del Comitato europeo per la protezione dei dati e delle autorità di controllo nazionali. (e) Interessi vitali (Articolo 6.1.d GDPR): in casi rari, quando il trattamento è necessario per proteggere gli interessi vitali degli interessati o di un\'altra persona fisica. (f) Interesse pubblico o autorità ufficiale (Articolo 6.1.e GDPR): quando applicabile per il trattamento effettuato nell\'interesse pubblico o nell\'esercizio di pubblici poteri. La specifica base giuridica per ciascuna finalità di trattamento e servizio è indicata nella tabella dei servizi e dei cookie di seguito.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-recipients"><?php echo esc_html__( 'Destinatari e trasferimenti di dati', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'I dati personali possono essere comunicati o condivisi con le seguenti categorie di destinatari, strettamente limitati a quanto necessario per le finalità indicate: (1) Fornitori di servizi e responsabili del trattamento: fornitori terzi, appaltatori e fornitori di servizi che trattano i dati per nostro conto in base a contratti scritti di trattamento dei dati (fornitori di hosting, cloud storage, servizi CDN, consegna email, processori di pagamento, fornitori di analisi, piattaforme di assistenza clienti, servizi di sicurezza). (2) Partner commerciali: partner fidati con cui collaboriamo per fornire servizi, evadere ordini o fornire funzionalità integrate, soggetti a obblighi contrattuali di riservatezza e protezione dei dati. (3) Partner pubblicitari e di marketing: quando hai fornito il consenso, potremmo condividere dati con reti pubblicitarie, piattaforme di marketing e servizi di social media per scopi di pubblicità mirata e marketing. (4) Consulenti professionali: avvocati, commercialisti, revisori, assicuratori e altri consulenti professionali quando necessario per operazioni commerciali o conformità legale. (5) Autorità competenti: forze dell\'ordine, organismi di regolamentazione, tribunali, agenzie governative e altre autorità pubbliche quando richiesto dalla legge, in risposta a procedimenti legali, per proteggere diritti e sicurezza, o per conformarsi a obblighi normativi. (6) Operazioni societarie: in relazione a qualsiasi fusione, vendita, acquisizione, ristrutturazione o trasferimento di beni, potenziali acquirenti o investitori potrebbero ricevere dati personali soggetti a obblighi di riservatezza. (7) Con il tuo consenso: altre terze parti quando hai fornito uno specifico consenso o su tua indicazione. Quando i destinatari sono stabiliti al di fuori dello Spazio Economico Europeo (SEE), i trasferimenti internazionali di dati avvengono solo quando: (i) la Commissione europea ha emesso una decisione di adeguatezza riconoscendo che il paese di destinazione fornisce un livello adeguato di protezione (Articolo 45 GDPR); oppure (ii) sono in atto garanzie adeguate, come le Clausole Contrattuali Standard approvate dalla Commissione europea (Articolo 46 GDPR), le Norme Vincolanti d\'Impresa, codici di condotta approvati o meccanismi di certificazione; e (iii) misure tecniche, organizzative e contrattuali aggiuntive sono implementate in conformità con le più recenti raccomandazioni del Comitato europeo per la protezione dei dati (in particolare a seguito della decisione Schrems II della Corte di giustizia dell\'Unione europea) per garantire un livello di protezione sostanzialmente equivalente. Valutiamo il regime giuridico dei paesi di destinazione e implementiamo misure supplementari ove necessario. I dettagli dei trasferimenti internazionali specifici e delle garanzie sono disponibili su richiesta.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-processors"><?php echo esc_html__( 'Responsabili del trattamento e personale autorizzato', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'L\'accesso ai dati personali è rigorosamente controllato e limitato in base al principio della necessità di conoscere. Solo il personale autorizzato che è stato adeguatamente formato sui principi di protezione dei dati, obblighi di riservatezza, procedure di sicurezza e politiche pertinenti ha accesso ai dati personali. Tutti i dipendenti, collaboratori e altro personale con accesso ai dati personali sono vincolati da obblighi contrattuali di riservatezza e sono soggetti ad azioni disciplinari in caso di violazioni. Implementiamo controlli di accesso basati sui ruoli, meccanismi di autenticazione, registrazione delle attività e revisioni periodiche degli accessi. I fornitori di servizi esterni, i venditori e altre terze parti che trattano dati personali per nostro conto ("responsabili del trattamento") operano in base a contratti scritti di trattamento dei dati (noti anche come Data Processing Agreements o DPA) che riflettono pienamente i requisiti dell\'articolo 28 del GDPR. Questi contratti richiedono ai responsabili del trattamento di: trattare i dati solo su istruzioni documentate; implementare adeguate misure di sicurezza tecniche e organizzative; mantenere la riservatezza; assistere con le richieste di diritti degli interessati; assistere con incidenti di sicurezza e notifiche di violazione dei dati; cancellare o restituire i dati personali al termine del rapporto; dimostrare la conformità attraverso audit e ispezioni; coinvolgere sub-responsabili solo con previa autorizzazione e in base a obblighi contrattuali equivalenti. Conduciamo valutazioni di due diligence dei responsabili del trattamento prima dell\'incarico, monitoriamo e valutiamo regolarmente la loro conformità agli obblighi contrattuali, esaminiamo report di audit e certificazioni di sicurezza, e manteniamo un registro aggiornato dei responsabili del trattamento e delle attività di trattamento come richiesto dall\'articolo 30 del GDPR.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-security-measures"><?php echo esc_html__( 'Misure di sicurezza', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Implementiamo adeguate misure tecniche e organizzative per garantire un livello di sicurezza adeguato al rischio, in conformità con l\'articolo 32 del GDPR, tenendo conto dello stato dell\'arte, dei costi di attuazione, della natura, dell\'ambito, del contesto e delle finalità del trattamento, e dei rischi per i diritti e le libertà delle persone fisiche. Le nostre misure di sicurezza includono: (1) Misure tecniche: cifratura dei dati personali in transito utilizzando protocolli TLS/SSL e a riposo utilizzando algoritmi di cifratura standard del settore; meccanismi di autenticazione sicuri tra cui politiche di password robuste, autenticazione a più fattori e gestione delle sessioni; controlli di accesso con permessi basati sui ruoli, principio del privilegio minimo e revisioni periodiche degli accessi; sicurezza di rete tra cui firewall, sistemi di rilevamento e prevenzione delle intrusioni, segmentazione della rete e monitoraggio della sicurezza; registrazione e monitoraggio degli accessi, delle attività, degli eventi di sicurezza e delle anomalie; valutazioni periodiche delle vulnerabilità, test di penetrazione e audit di sicurezza; pratiche di sviluppo software sicuro, revisioni del codice e test di sicurezza; procedure di backup e ripristino dei dati per garantire disponibilità e resilienza; pseudonimizzazione e anonimizzazione quando appropriato per ridurre i rischi. (2) Misure organizzative: politiche, procedure e linee guida sulla protezione dei dati; principi di minimizzazione dei dati e limitazione della conservazione applicati durante tutto il ciclo di vita dei dati; programmi di formazione e sensibilizzazione del personale sulla protezione dei dati, sicurezza e riservatezza; procedure di risposta agli incidenti e gestione delle violazioni dei dati; valutazioni periodiche della conformità e audit interni; gestione dei fornitori e valutazioni di sicurezza di terze parti; pianificazione della continuità aziendale e del ripristino di emergenza; principi di privacy by design e privacy by default integrati nei sistemi e nei processi; valutazioni d\'impatto sulla protezione dei dati (DPIA) documentate per attività di trattamento ad alto rischio. (3) Misure fisiche: controlli di accesso fisico alle strutture e alle sale server; controlli e monitoraggio ambientali; smaltimento sicuro di supporti e apparecchiature. Testiamo, valutiamo e verifichiamo regolarmente l\'efficacia di queste misure tecniche e organizzative, le aggiorniamo per affrontare minacce in evoluzione e manteniamo l\'allineamento con le migliori pratiche del settore e le linee guida normative.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-automated-decision"><?php echo esc_html__( 'Processo decisionale automatizzato e profilazione', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Potremmo utilizzare la profilazione, che indica qualsiasi forma di trattamento automatizzato di dati personali per valutare determinati aspetti personali, in particolare per analizzare o prevedere aspetti riguardanti le tue preferenze, interessi, comportamento, ubicazione o spostamenti. Le attività di profilazione possono includere: analisi dei tuoi pattern di utilizzo per raccomandare contenuti o prodotti; segmentazione degli utenti per scopi di marketing; personalizzazione dei contenuti del sito web e delle interfacce utente; previsione degli interessi in base alla cronologia di navigazione e alle interazioni. La profilazione viene effettuata solo quando basata su una base giuridica valida (tipicamente il tuo consenso o i nostri interessi legittimi dopo il test di bilanciamento) e con adeguate garanzie. Il processo decisionale automatizzato si riferisce all\'adozione di decisioni esclusivamente tramite mezzi automatizzati senza alcun coinvolgimento umano. Non effettuiamo processi decisionali automatizzati che producono effetti giuridici che ti riguardano o che incidono in modo analogo significativamente su di te (come definito nell\'articolo 22 del GDPR) a meno che: (i) sia necessario per la conclusione o l\'esecuzione di un contratto tra te e noi; (ii) sia autorizzato dal diritto dell\'Unione o dello Stato membro cui siamo soggetti e che prevede misure adeguate a tutela dei tuoi diritti, libertà e interessi legittimi; o (iii) si basi sul tuo consenso esplicito. Nei casi in cui viene utilizzato il processo decisionale automatizzato, implementiamo adeguate garanzie tra cui: fornire informazioni significative sulla logica utilizzata; garantire la disponibilità di intervento umano; permetterti di esprimere il tuo punto di vista e contestare la decisione; condurre valutazioni periodiche di accuratezza e distorsione. Hai il diritto di non essere sottoposto a decisioni basate unicamente sul trattamento automatizzato, compresa la profilazione, che producono effetti giuridici che ti riguardano o che incidono in modo analogo significativamente su di te, e puoi esercitare questo diritto contattandoci come indicato nella sezione "Come esercitare i tuoi diritti".', 'fp-privacy' ); ?></p>

<?php
// AI Disclosure section (if enabled)
$ai_disclosure_html = isset( $ai_disclosure ) ? $ai_disclosure : '';
if ( ! empty( $ai_disclosure_html ) ) {
    echo $ai_disclosure_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped - HTML already escaped in generator
}

// Algorithmic Transparency section (if enabled)
$algorithmic_transparency_html = isset( $algorithmic_transparency ) ? $algorithmic_transparency : '';
if ( ! empty( $algorithmic_transparency_html ) ) {
    echo $algorithmic_transparency_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped - HTML already escaped in generator
}
?>

<h2 id="fp-privacy-retention"><?php echo esc_html__( 'Conservazione', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'I dati personali sono conservati solo per il tempo necessario al conseguimento delle finalità per le quali sono stati raccolti, in conformità con il principio di limitazione della conservazione ai sensi dell\'articolo 5(1)(e) del GDPR. I nostri periodi di conservazione si basano su: (1) Conservazione basata sulle finalità: i dati sono conservati per il tempo necessario a fornire i servizi, mantenere gli account, adempiere agli obblighi contrattuali e raggiungere le finalità descritte nella presente informativa. (2) Requisiti di conservazione legali e normativi: alcuni dati devono essere conservati per periodi specifici per conformarsi a obblighi legali come le leggi fiscali (tipicamente 7-10 anni per le registrazioni finanziarie), i requisiti contabili, gli obblighi normativi, le leggi sul lavoro e altri obblighi di conservazione previsti dalla legge. (3) Rivendicazioni legali e contenzioso: i dati possono essere conservati più a lungo quando necessario per accertare, esercitare o difendere diritti in sede giudiziaria, tipicamente fino alla scadenza dei termini di prescrizione applicabili. (4) Conservazione basata sul consenso: quando il trattamento si basa sul consenso, i dati sono conservati fino alla revoca del consenso, a meno che non si applichi un\'altra base giuridica o obblighi di conservazione legale richiedano la continuazione della conservazione. (5) Conservazione basata sull\'interesse legittimo: quando basata su interessi legittimi, i dati sono conservati per tutto il tempo in cui persiste l\'interesse legittimo e non è superato dai tuoi diritti. I periodi di conservazione specifici includono: i dati dell\'account sono conservati mentre il tuo account è attivo e per un periodo limitato dopo la chiusura; le registrazioni transazionali sono conservate secondo le normative finanziarie e fiscali applicabili; le registrazioni del consenso al marketing sono conservate per il periodo richiesto dalla legge per dimostrare la conformità (tipicamente 3-5 anni dopo la revoca); le registrazioni di gestione del consenso (consensi ai cookie) sono conservate come richiesto dalla legge e dalle linee guida applicabili (tipicamente 6-24 mesi); i log di accesso e i dati di sicurezza sono tipicamente conservati per 6-12 mesi a meno che non sia richiesta una conservazione più lunga per indagini di sicurezza; i cookie e le tecnologie simili seguono la durata indicata nelle tabelle dei cookie e nella nostra Informativa sui Cookie. Al termine dei periodi di conservazione applicabili, i dati personali sono cancellati, distrutti o anonimizzati in modo sicuro (resi non identificabili) in modo tale che non possano più essere attribuiti a una persona fisica identificabile. Conduciamo revisioni periodiche dei dati conservati per garantire la conformità con le politiche di conservazione e la cancellazione dei dati che non sono più necessari. I calendari di conservazione dettagliati per specifiche categorie di dati e attività di trattamento sono disponibili su richiesta.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-data-subject-rights"><?php echo esc_html__( 'Diritti dell\'interessato', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Ai sensi del GDPR e delle leggi applicabili sulla protezione dei dati, hai i seguenti diritti riguardo ai tuoi dati personali: (1) Diritto di accesso (Articolo 15 GDPR): Hai il diritto di ottenere conferma che sia o meno in corso un trattamento di dati personali che ti riguardano e, in tal caso, di accedere ai dati personali e alle informazioni sul trattamento incluse le finalità, le categorie di dati, i destinatari, i periodi di conservazione e le fonti dei dati. (2) Diritto di rettifica (Articolo 16 GDPR): Hai il diritto di ottenere senza ingiustificato ritardo la rettifica dei dati personali inesatti e l\'integrazione dei dati personali incompleti. (3) Diritto alla cancellazione / diritto all\'oblio (Articolo 17 GDPR): Hai il diritto di ottenere la cancellazione dei dati personali che ti riguardano senza ingiustificato ritardo quando: i dati non sono più necessari rispetto alle finalità; revochi il consenso e non sussiste altro fondamento giuridico; ti opponi al trattamento basato su interessi legittimi e non sussistono motivi legittimi prevalenti; i dati sono stati trattati illecitamente; la cancellazione è necessaria per adempiere un obbligo legale; o i dati sono stati raccolti relativamente all\'offerta di servizi della società dell\'informazione ai minori. Questo diritto non si applica quando il trattamento è necessario per l\'adempimento di un obbligo legale, per l\'accertamento, l\'esercizio o la difesa di un diritto in sede giudiziaria, o altre eccezioni ai sensi dell\'articolo 17(3). (4) Diritto di limitazione di trattamento (Articolo 18 GDPR): Hai il diritto di ottenere la limitazione del trattamento quando: contesti l\'esattezza dei dati (per il periodo necessario alla verifica); il trattamento è illecito e ti opponi alla cancellazione richiedendo invece la limitazione; non abbiamo più bisogno dei dati ma a te servono per l\'accertamento, l\'esercizio o la difesa di un diritto in sede giudiziaria; o ti sei opposto al trattamento in attesa della verifica in merito all\'eventuale prevalenza dei nostri motivi legittimi rispetto ai tuoi. (5) Diritto alla portabilità dei dati (Articolo 20 GDPR): Hai il diritto di ricevere i dati personali che ti riguardano che ci hai fornito in un formato strutturato, di uso comune e leggibile da dispositivo automatico, e di trasmettere tali dati a un altro titolare del trattamento, quando il trattamento si basa sul consenso o sul contratto ed è effettuato con mezzi automatizzati. (6) Diritto di opposizione (Articolo 21 GDPR): Hai il diritto di opporti in qualsiasi momento al trattamento dei tuoi dati personali basato su interessi legittimi o sull\'esecuzione di un compito di interesse pubblico, per motivi connessi alla tua situazione particolare. Cesseremo il trattamento salvo che sussistano motivi legittimi cogenti che prevalgono sui tuoi interessi, diritti e libertà, oppure per l\'accertamento, l\'esercizio o la difesa di un diritto in sede giudiziaria. Hai un diritto assoluto di opporti al trattamento per finalità di marketing diretto, compresa la profilazione connessa al marketing diretto. (7) Diritto di revoca del consenso (Articolo 7(3) GDPR): Quando il trattamento si basa sul consenso, hai il diritto di revocare il consenso in qualsiasi momento senza pregiudicare la liceità del trattamento basata sul consenso prestato prima della revoca. (8) Diritto di non essere sottoposto a decisioni automatizzate (Articolo 22 GDPR): Hai il diritto di non essere sottoposto a decisioni basate unicamente sul trattamento automatizzato, compresa la profilazione, che producono effetti giuridici che ti riguardano o che incidono in modo analogo significativamente su di te, salvo determinate eccezioni. (9) Diritto di proporre reclamo (Articolo 77 GDPR): Hai il diritto di proporre reclamo a un\'autorità di controllo, in particolare nello Stato membro in cui risiedi abitualmente, lavori oppure del luogo ove si è verificata la presunta violazione.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-exercise-rights"><?php echo esc_html__( 'Come esercitare i tuoi diritti', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Per esercitare uno qualsiasi dei tuoi diritti in materia di protezione dei dati, presenta la tua richiesta: (1) inviando un\'email all\'indirizzo di contatto indicato nella sezione Titolare del trattamento sopra; (2) utilizzando il modulo di contatto dedicato disponibile sul nostro sito web; (3) inviando una comunicazione scritta all\'indirizzo postale indicato nella sezione Titolare del trattamento; oppure (4) contattando il nostro Responsabile della Protezione dei Dati se designato. La tua richiesta dovrebbe identificare chiaramente quale/i diritto/i desideri esercitare e fornire informazioni sufficienti per permetterci di identificarti e localizzare i tuoi dati personali. Risponderemo alla tua richiesta senza ingiustificato ritardo e in ogni caso entro un mese dal ricevimento, in conformità con gli articoli 12 e 15-22 del GDPR. Tale periodo può essere prorogato di due mesi, se necessario, tenuto conto della complessità della richiesta e del numero di richieste. Se proroghiamo il periodo di risposta, ti informeremo della proroga e dei motivi del ritardo entro un mese dal ricevimento della richiesta. Per garantire la sicurezza e proteggerci da richieste fraudolente, potremmo richiedere informazioni aggiuntive per verificare la tua identità prima di rispondere alla tua richiesta, in particolare per richieste di accesso, cancellazione o portabilità. Si tratta di una misura di sicurezza per garantire che i dati personali non vengano divulgati a persone non autorizzate. In caso di ragionevoli dubbi circa la tua identità, potremmo richiedere ulteriori informazioni necessarie per confermare la tua identità. Forniamo informazioni e rispondiamo alle richieste gratuitamente. Tuttavia, se le richieste sono manifestamente infondate o eccessive, in particolare per il loro carattere ripetitivo, potremmo: (i) addebitare un contributo spese ragionevole tenendo conto dei costi amministrativi per fornire le informazioni o intraprendere l\'azione richiesta; oppure (ii) rifiutare di soddisfare la richiesta. In tali casi, dimostreremo il carattere manifestamente infondato o eccessivo della richiesta. Se non intraprenderemo alcuna azione in merito alla tua richiesta, ti informeremo senza ritardo e al più tardi entro un mese dal ricevimento della richiesta, dei motivi della mancata azione e della possibilità di proporre reclamo a un\'autorità di controllo e di proporre ricorso giurisdizionale.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-consent-withdrawal"><?php echo esc_html__( 'Revoca del consenso e gestione dei cookie', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Hai il diritto di revocare il tuo consenso in qualsiasi momento senza pregiudicare la liceità del trattamento basata sul consenso prestato prima della revoca. Puoi modificare e gestire le tue scelte di consenso e preferenze in qualsiasi momento attraverso i seguenti metodi: (1) Interfaccia delle preferenze sui cookie: accedi allo strumento di gestione del consenso ai cookie visualizzato nel footer del nostro sito web o accessibile tramite un pulsante dedicato alle preferenze. Questo strumento ti consente di rivedere, abilitare o disabilitare diverse categorie di cookie e modificare le tue scelte di consenso. (2) Impostazioni del browser: puoi configurare il tuo browser web per rifiutare tutti o alcuni cookie, avvisarti quando i cookie vengono impostati o eliminare i cookie che sono già stati impostati. Nota che la disabilitazione dei cookie potrebbe influire sulla funzionalità del nostro sito web e limitare la tua capacità di utilizzare determinate funzionalità. Le istruzioni per la gestione dei cookie nei browser più diffusi sono disponibili nelle rispettive sezioni di aiuto. (3) Meccanismi di opt-out: per servizi specifici, partner di analisi o pubblicitari, puoi utilizzare i loro meccanismi di opt-out, i cui link sono forniti nella tabella dei servizi e dei cookie di seguito e nella nostra Informativa sui Cookie. (4) Preferenze email: puoi annullare l\'iscrizione alle email di marketing cliccando sul link di annullamento dell\'iscrizione incluso in ogni comunicazione di marketing o modificando le tue preferenze email nelle impostazioni del tuo account. (5) Impostazioni dell\'account: se hai un account, puoi gestire le preferenze di comunicazione, le impostazioni sulla privacy e le opzioni di condivisione dei dati nelle impostazioni del tuo account. Nota che la revoca del consenso o l\'opt-out da determinati trattamenti non incide su: il trattamento necessario per eseguire un contratto con te (come la fornitura dei servizi principali che hai richiesto); il trattamento basato su obblighi legali; il trattamento basato su interessi legittimi (anche se potresti avere il diritto di opporti); e la liceità del trattamento avvenuto prima della revoca del consenso. Anche se annulli l\'iscrizione alle comunicazioni di marketing, potremmo comunque inviarti messaggi relativi ai servizi, transazionali e amministrativi necessari per il tuo utilizzo dei servizi. La revoca del consenso per i cookie essenziali necessari per il funzionamento del servizio può comportare funzionalità limitate o indisponibilità di determinate funzionalità.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-minors-data"><?php echo esc_html__( 'Dati dei minori', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'I nostri servizi non sono destinati, diretti o progettati per attirare minori di età inferiore a quella richiesta dalla legge applicabile per fornire un consenso valido al trattamento dei dati personali. Nell\'Unione europea, tale età è generalmente di 16 anni, sebbene gli Stati membri possano stabilire per legge un\'età inferiore (non inferiore a 13 anni). Non raccogliamo, utilizziamo o divulghiamo consapevolmente dati personali di minori di età inferiore all\'età del consenso applicabile senza il consenso verificabile del genitore o del tutore. Se il trattamento dei dati personali dei minori è necessario per la fornitura di servizi della società dell\'informazione, è lecito solo quando il consenso è prestato o autorizzato dal titolare della responsabilità genitoriale sul minore, e abbiamo compiuto sforzi ragionevoli per verificare che il consenso sia prestato o autorizzato dal titolare della responsabilità genitoriale, tenendo conto della tecnologia disponibile. Se veniamo a conoscenza di aver raccolto inavvertitamente dati personali di un minore al di sotto dell\'età applicabile senza un\'adeguata autorizzazione, consenso genitoriale valido o altra base giuridica lecita, adotteremo immediatamente le seguenti misure: (i) cancellare le informazioni il prima possibile; (ii) non utilizzare o divulgare le informazioni per alcuno scopo; (iii) cessare qualsiasi attività di profilazione o tracciamento; (iv) indagare su come i dati sono stati raccolti e adottare misure per prevenire il ripetersi; e (v) adottare qualsiasi ulteriore misura necessaria per conformarsi alle leggi applicabili e alle linee guida delle autorità di controllo. Incoraggiamo i genitori e i tutori a monitorare le attività online dei loro figli e ad aiutare a far rispettare questa informativa istruendo i minori a non fornire mai informazioni personali attraverso i nostri servizi senza autorizzazione. Se hai motivo di ritenere che un minore al di sotto dell\'età applicabile abbia fornito dati personali a noi, contattaci immediatamente utilizzando i recapiti forniti in questa informativa e adotteremo le azioni appropriate.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-data-breach"><?php echo esc_html__( 'Gestione delle violazioni dei dati', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Manteniamo procedure complete e piani di risposta per rilevare, valutare, segnalare, indagare, rispondere e mitigare le violazioni dei dati personali in conformità con gli articoli 33 e 34 del GDPR. Una violazione dei dati personali indica una violazione di sicurezza che comporta accidentalmente o in modo illecito la distruzione, la perdita, la modifica, la divulgazione non autorizzata o l\'accesso ai dati personali trasmessi, conservati o comunque trattati. Le nostre procedure di gestione delle violazioni dei dati includono: (1) Rilevamento e valutazione: sistemi di monitoraggio e controlli di sicurezza per rilevare potenziali violazioni; procedure per dipendenti, collaboratori e responsabili del trattamento per segnalare presunte violazioni; valutazione rapida della natura, dell\'ambito e delle potenziali conseguenze della violazione; determinazione se la violazione sia suscettibile di presentare un rischio o un rischio elevato per i diritti e le libertà delle persone fisiche. (2) Notifica all\'autorità di controllo (Articolo 33 GDPR): quando una violazione dei dati personali è suscettibile di presentare un rischio per i diritti e le libertà delle persone fisiche, notifichiamo la violazione all\'autorità di controllo competente senza ingiustificato ritardo e, ove possibile, entro 72 ore dal momento in cui ne siamo venuti a conoscenza. Se la notifica non viene effettuata entro 72 ore, forniamo i motivi del ritardo. La notifica include: la natura della violazione comprese le categorie e il numero approssimativo di interessati e di registrazioni dei dati interessati; il nome e i recapiti del nostro Responsabile della Protezione dei Dati o altro punto di contatto; le probabili conseguenze della violazione; e le misure adottate o di cui si propone l\'adozione per porre rimedio alla violazione e attenuarne i possibili effetti negativi. (3) Notifica agli interessati (Articolo 34 GDPR): quando una violazione dei dati personali è suscettibile di presentare un rischio elevato per i diritti e le libertà delle persone fisiche, comunichiamo la violazione agli interessati senza ingiustificato ritardo, in un linguaggio chiaro e semplice. La comunicazione descrive la natura della violazione, fornisce i recapiti del nostro Responsabile della Protezione dei Dati o punto di contatto pertinente, descrive le probabili conseguenze e le misure adottate o di cui si propone l\'adozione per porre rimedio alla violazione e attenuarne gli effetti negativi. La notifica agli interessati non è richiesta se: abbiamo messo in atto adeguate misure tecniche e organizzative di protezione (come la cifratura) che rendono i dati incomprensibili a chiunque non sia autorizzato ad accedervi; abbiamo successivamente adottato misure atte a scongiurare il sopraggiungere di un rischio elevato; o comporterebbe uno sforzo sproporzionato, nel qual caso effettuiamo una comunicazione pubblica o una misura simile. (4) Documentazione interna: documentiamo tutte le violazioni dei dati personali, compresi i fatti, gli effetti e i provvedimenti adottati per porvi rimedio, per consentire all\'autorità di controllo di verificare il rispetto dell\'articolo 33, anche se la notifica non è richiesta. (5) Indagine e risoluzione: conduzione di indagini approfondite per determinare le cause alla radice; implementazione di misure correttive e preventive per affrontare le vulnerabilità; revisione e aggiornamento delle misure e procedure di sicurezza; formazione e sensibilizzazione per prevenire incidenti futuri. (6) Obblighi dei responsabili del trattamento: i nostri contratti di trattamento dei dati richiedono ai responsabili del trattamento di notificarci senza ingiustificato ritardo dopo essere venuti a conoscenza di una violazione dei dati personali che riguarda i nostri dati, consentendoci di rispettare i nostri obblighi di notifica.', 'fp-privacy' ); ?></p>

<?php if ( $dpo_name || $dpo_mail ) : ?>
<h2 id="fp-privacy-dpo"><?php echo esc_html__( 'Responsabile della Protezione dei Dati', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( $dpo_name ); ?> — <?php echo esc_html( $dpo_mail ); ?></p>
<?php endif; ?>

<h2 id="fp-privacy-supervisory-authority"><?php echo esc_html__( 'Contatto autorità di controllo', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Ai sensi dell\'articolo 77 del GDPR, hai il diritto di proporre reclamo a un\'autorità di controllo se ritieni che il trattamento dei tuoi dati personali violi le leggi sulla protezione dei dati o che i tuoi diritti alla privacy siano stati violati. Puoi proporre reclamo a un\'autorità di controllo: (i) nello Stato membro dell\'UE in cui risiedi abitualmente; (ii) nel tuo luogo di lavoro; o (iii) nel luogo in cui si è verificata la presunta violazione. Questo diritto sussiste fatto salvo ogni altro ricorso amministrativo o giurisdizionale, il che significa che puoi presentare un reclamo a un\'autorità di controllo in aggiunta o in alternativa alla ricerca di rimedi attraverso i tribunali. L\'autorità di controllo competente in ciascuno Stato membro dell\'UE e del SEE è responsabile del controllo dell\'applicazione del GDPR, della gestione dei reclami, dello svolgimento di indagini e dell\'imposizione di sanzioni amministrative per le violazioni. L\'autorità di controllo a cui è stato proposto il reclamo ti informerà sullo stato e sull\'esito del reclamo, compresa la possibilità di un ricorso giurisdizionale. I recapiti, i moduli di reclamo e le procedure per tutte le autorità di controllo dell\'UE e del SEE sono disponibili sul sito web del Comitato europeo per la protezione dei dati (EDPB) all\'indirizzo https://edpb.europa.eu/about-edpb/about-edpb/members_en. Ci impegniamo a collaborare con le autorità di controllo e a risolvere eventuali reclami o preoccupazioni relative alle nostre pratiche di trattamento dei dati. Se hai preoccupazioni, ti incoraggiamo a contattarci prima in modo che possiamo tentare di risolvere il problema direttamente. Tuttavia, hai sempre il diritto di proporre reclamo a un\'autorità di controllo.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-governance"><?php echo esc_html__( 'Governance e aggiornamenti dell\'informativa', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Ci impegniamo a mantenere un\'informativa sulla privacy accurata, trasparente e aggiornata che rifletta le nostre attuali pratiche di trattamento dei dati e sia conforme ai requisiti legali applicabili. Esaminiamo e aggiorniamo questa informativa sulla privacy almeno annualmente, o più frequentemente quando: (i) le nostre operazioni di trattamento, le finalità, le basi giuridiche o i flussi di dati cambiano significativamente; (ii) vengono introdotti nuovi servizi, funzionalità o tecnologie; (iii) entrano in vigore nuovi requisiti legali, regolamenti o linee guida; (iv) la giurisprudenza della Corte di giustizia dell\'Unione europea o dei tribunali nazionali influisce sulle nostre attività di trattamento; (v) il Comitato europeo per la protezione dei dati o le autorità di controllo nazionali emettono nuove linee guida, raccomandazioni o decisioni vincolanti; o (vi) qualsiasi altra circostanza richieda aggiornamenti per garantire il continuo allineamento con il GDPR, la Direttiva ePrivacy e il relativo quadro normativo, le implementazioni nazionali e i regolamenti settoriali in vigore a ottobre 2025. Quando apportiamo modifiche sostanziali a questa informativa sulla privacy che potrebbero influire sui tuoi diritti o su come trattiamo i tuoi dati personali, comunicheremo gli aggiornamenti attraverso uno o più dei seguenti metodi, a seconda dei casi: (1) avviso ben visibile sul nostro sito web o all\'interno dei nostri servizi; (2) notifica email diretta agli utenti registrati; (3) notifiche o avvisi in-app; (4) richiesta di rinnovo del consenso quando il trattamento si basa sul consenso e le modifiche influiscono sull\'ambito o sulle finalità del trattamento; o (5) altri mezzi appropriati per garantire che tu sia informato. Per aggiornamenti minori e non sostanziali (come formattazione, chiarimenti, modifiche ai recapiti o aggiornamenti per riflettere cambiamenti organizzativi che non influiscono sul trattamento), potremmo semplicemente aggiornare l\'informativa e modificare la data di "ultimo aggiornamento" senza avviso separato. Ti incoraggiamo a rivedere periodicamente questa informativa sulla privacy per rimanere informato su come raccogliamo, utilizziamo e proteggiamo i tuoi dati personali. La versione corrente di questa informativa è sempre disponibile sul nostro sito web. Le versioni precedenti possono essere disponibili su richiesta.', 'fp-privacy' ); ?></p>

<h2 id="fp-privacy-services-cookies"><?php echo esc_html__( 'Servizi e cookie', 'fp-privacy' ); ?></h2>
<?php foreach ( $groups as $category => $services ) :
    $meta  = isset( $categories_meta[ $category ] ) && is_array( $categories_meta[ $category ] ) ? $categories_meta[ $category ] : array();
    $label = isset( $meta['label'] ) && '' !== $meta['label'] ? $meta['label'] : ucfirst( str_replace( '_', ' ', $category ) );
    $description = isset( $meta['description'] ) ? $meta['description'] : '';
    ?>
<div class="fp-privacy-category-block">
<h3><?php echo esc_html( $label ); ?></h3>
    <?php if ( $description ) : ?>
        <p class="fp-privacy-category-description"><?php echo wp_kses_post( $description ); ?></p>
    <?php endif; ?>
<div class="fp-privacy-table-wrapper">
<table class="fp-privacy-services-table">
<thead>
<tr>
<th><?php esc_html_e( 'Service', 'fp-privacy' ); ?></th>
<th><?php esc_html_e( 'Provider', 'fp-privacy' ); ?></th>
<th><?php esc_html_e( 'Purpose', 'fp-privacy' ); ?></th>
<th><?php esc_html_e( 'Cookies & Retention', 'fp-privacy' ); ?></th>
<th><?php esc_html_e( 'Legal basis', 'fp-privacy' ); ?></th>
</tr>
</thead>
<tbody>
    <?php foreach ( $services as $service ) :
        if ( ! is_array( $service ) ) {
            continue;
        }

        $name        = fp_privacy_get_service_value( $service, 'name' );
        $provider    = fp_privacy_get_service_value( $service, 'provider' );
        $purpose     = fp_privacy_get_service_value( $service, 'purpose' );
        $retention   = fp_privacy_get_service_value( $service, 'retention' );
        $legal_basis = fp_privacy_get_service_value( $service, 'legal_basis' );
        $policy_url  = fp_privacy_get_service_value( $service, 'policy_url' );
        $service_cookies = fp_privacy_format_service_cookies( isset( $service['cookies'] ) ? $service['cookies'] : array() );
        ?>
<tr>
<td>
    <?php echo esc_html( $name ); ?>
    <?php if ( '' !== $policy_url ) : ?> — <a href="<?php echo esc_url( $policy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacy Policy', 'fp-privacy' ); ?></a><?php endif; ?>
</td>
<td><?php echo esc_html( $provider ); ?></td>
<td><?php echo wp_kses_post( $purpose ); ?></td>
<td>
    <?php if ( ! empty( $service_cookies ) ) : ?>
        <span><?php echo esc_html( implode( '; ', $service_cookies ) ); ?></span>
    <?php else : ?>
        <span><?php esc_html_e( 'No cookies declared.', 'fp-privacy' ); ?></span>
    <?php endif; ?>
    <?php if ( '' !== $retention ) : ?>
        <span> — <?php echo esc_html( $retention ); ?></span>
    <?php endif; ?>
</td>
<td><?php echo esc_html( $legal_basis ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endforeach; ?>

<h2 id="fp-privacy-last-update"><?php esc_html_e( 'Last update', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'This privacy policy was generated on %s.', 'fp-privacy' ), $last_generated ) ); ?></p>
</section>
