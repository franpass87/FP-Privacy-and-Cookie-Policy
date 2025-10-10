# Generazione Automatica delle Pagine Privacy e Cookie Policy

Questo documento spiega come generare automaticamente le pagine Privacy Policy e Cookie Policy utilizzando il plugin FP Privacy & Cookie Policy.

## Indice

- [Panoramica](#panoramica)
- [Metodo 1: Comando WP-CLI](#metodo-1-comando-wp-cli)
- [Metodo 2: Script PHP Standalone](#metodo-2-script-php-standalone)
- [Metodo 3: Interfaccia Admin](#metodo-3-interfaccia-admin)
- [Opzioni Disponibili](#opzioni-disponibili)
- [Esempi d'Uso](#esempi-duso)
- [Domande Frequenti](#domande-frequenti)

## Panoramica

Il sistema di generazione automatica delle policy:

1. ✅ **Rileva automaticamente** i servizi integrati sul sito (Google Analytics, Facebook Pixel, ecc.)
2. ✅ **Crea le pagine WordPress** se non esistono già
3. ✅ **Genera il contenuto** completo e conforme al GDPR basato sui servizi rilevati
4. ✅ **Aggiorna le pagine** con il contenuto generato
5. ✅ **Salva uno snapshot** dei servizi per riferimento futuro
6. ✅ **Supporta multilingua** con generazione per ogni lingua configurata

## Metodo 1: Comando WP-CLI

Il metodo più semplice e potente per generare le policy automaticamente.

### Installazione

WP-CLI è pre-installato nella maggior parte degli hosting WordPress moderni. Verifica con:

```bash
wp --version
```

### Uso Base

```bash
# Genera le pagine per la lingua predefinita
wp fp-privacy generate-pages

# Genera per tutte le lingue configurate
wp fp-privacy generate-pages --all-languages

# Genera solo per l'italiano
wp fp-privacy generate-pages --lang=it_IT
```

### Opzioni Avanzate

```bash
# Forza la rigenerazione anche se le pagine sono state modificate
wp fp-privacy generate-pages --force

# Incrementa la revisione del consenso dopo la generazione
wp fp-privacy generate-pages --bump-revision

# Verifica cosa verrebbe fatto senza modificare nulla
wp fp-privacy generate-pages --dry-run

# Combina più opzioni
wp fp-privacy generate-pages --all-languages --force --bump-revision
```

### Output di Esempio

```
🚀 Avvio generazione automatica delle policy...
🌍 Generazione per tutte le lingue: it_IT, en_US
📄 Verifica esistenza delle pagine...
✓ Pagine verificate/create

🔍 Rilevamento servizi integrati...
  ✓ Google Analytics [analytics]
  ✓ Facebook Pixel [marketing]
  ✓ YouTube [functional]
✓ Rilevati 3 servizi

🌐 Elaborazione lingua: it_IT
  Privacy Policy ID: 123
  Cookie Policy ID: 124
  📝 Generazione Privacy Policy...
  📝 Generazione Cookie Policy...
  📊 Privacy Policy: 45,678 caratteri
  📊 Cookie Policy: 12,345 caratteri
  💾 Aggiornamento pagine...
  ✓ Pagine aggiornate con successo!

💾 Salvataggio snapshot servizi...
✓ Snapshot salvato

═══════════════════════════════════════════
✅ Generazione completata!
📊 Lingue processate: 2
🔌 Servizi rilevati: 3
⏰ Timestamp: 2025-10-10 15:30:45
```

## Metodo 2: Script PHP Standalone

Se non hai accesso a WP-CLI, puoi usare lo script PHP standalone.

### Uso

```bash
# Dalla directory del plugin
php bin/generate-policies.php

# Con opzioni
php bin/generate-policies.php --all-languages
php bin/generate-policies.php --lang=it_IT --force
php bin/generate-policies.php --dry-run
```

### Aiuto

```bash
php bin/generate-policies.php --help
```

### Caratteristiche

- ✅ Output colorato e formattato
- ✅ Stesso set di opzioni di WP-CLI
- ✅ Non richiede WP-CLI installato
- ✅ Eseguibile direttamente da SSH o cron job
- ✅ Feedback dettagliato con emoji e colori

## Metodo 3: Interfaccia Admin

Puoi anche generare le policy dall'interfaccia di amministrazione WordPress.

1. Vai su **WordPress Admin → Privacy & Cookie → Policy Editor**
2. Clicca sul pulsante **"Detect integrations and regenerate"**
3. Le pagine verranno automaticamente aggiornate

Questo metodo è utile per utenti non tecnici o per test veloci.

## Opzioni Disponibili

| Opzione | Descrizione | Valori |
|---------|-------------|--------|
| `--force` | Forza la rigenerazione anche se le pagine sono state modificate manualmente | Flag booleano |
| `--all-languages` | Genera le policy per tutte le lingue configurate nel plugin | Flag booleano |
| `--lang=<codice>` | Genera solo per la lingua specificata | `it_IT`, `en_US`, `fr_FR`, ecc. |
| `--bump-revision` | Incrementa la revisione del consenso (richiederà nuovo consenso agli utenti) | Flag booleano |
| `--dry-run` | Simula l'esecuzione senza salvare modifiche (utile per test) | Flag booleano |

## Esempi d'Uso

### Scenario 1: Prima Installazione

Hai appena installato il plugin e vuoi creare le pagine per la prima volta:

```bash
wp fp-privacy generate-pages --all-languages
```

### Scenario 2: Hai Aggiunto un Nuovo Servizio

Hai integrato un nuovo servizio (es. Mailchimp) e vuoi aggiornare le policy:

```bash
wp fp-privacy generate-pages --all-languages --bump-revision
```

L'opzione `--bump-revision` incrementerà la versione del consenso, richiedendo agli utenti di accettare nuovamente.

### Scenario 3: Test Prima di Applicare

Vuoi vedere cosa verrebbe modificato senza salvare:

```bash
wp fp-privacy generate-pages --dry-run
```

### Scenario 4: Rigenerazione Forzata

Le pagine sono state modificate manualmente ma vuoi sovrascriverle:

```bash
wp fp-privacy generate-pages --force
```

### Scenario 5: Multilingua

Hai un sito in italiano e inglese:

```bash
# Genera per entrambe le lingue
wp fp-privacy generate-pages --all-languages

# Oppure solo per l'italiano
wp fp-privacy generate-pages --lang=it_IT
```

### Scenario 6: Automazione con Cron

Puoi automatizzare la rigenerazione periodica aggiungendo al crontab:

```bash
# Rigenera ogni settimana (domenica alle 3:00)
0 3 * * 0 cd /path/to/wordpress && wp fp-privacy generate-pages --all-languages

# Oppure con lo script PHP
0 3 * * 0 /usr/bin/php /path/to/plugin/bin/generate-policies.php --all-languages
```

## Domande Frequenti

### Come funziona il rilevamento automatico dei servizi?

Il plugin scansiona il codice sorgente delle pagine del sito alla ricerca di pattern specifici (script, pixel, iframe) dei servizi più comuni come:

- Google Analytics (GA4, Universal Analytics)
- Google Tag Manager
- Facebook Pixel
- Google Ads
- YouTube
- Vimeo
- E molti altri...

### Le pagine possono essere modificate manualmente?

Sì! Il plugin rispetta le modifiche manuali. Se modifichi una pagina:

- La rigenerazione normale NON sovrascriverà le modifiche
- Puoi forzare la sovrascrittura con `--force`
- Il plugin marca le pagine come "gestite" solo se generate automaticamente

### Cosa succede se aggiungo nuovi servizi?

1. Esegui nuovamente il comando di generazione
2. Il plugin rileverà i nuovi servizi
3. Le policy verranno aggiornate con le nuove informazioni
4. Usa `--bump-revision` per richiedere nuovo consenso agli utenti

### Il contenuto generato è conforme al GDPR?

Sì! I template sono stati redatti seguendo:

- ✅ GDPR (Regolamento UE 2016/679)
- ✅ ePrivacy Directive
- ✅ Linee guida EDPB aggiornate a ottobre 2025
- ✅ Giurisprudenza CGUE (inclusa Schrems II)
- ✅ Best practices delle autorità di controllo

**Tuttavia**, è sempre consigliato:
1. Rivedere il contenuto generato
2. Personalizzarlo per le specifiche del tuo business
3. Farlo verificare da un legale se necessario

### Posso personalizzare i template?

Sì, i template si trovano in `fp-privacy-cookie-policy/templates/`:

- `privacy-policy.php` - Template Privacy Policy
- `cookie-policy.php` - Template Cookie Policy

Puoi modificarli direttamente o copiarli nel tema con l'override di WordPress.

### Come aggiungo servizi personalizzati?

Puoi aggiungere servizi manualmente nelle impostazioni del plugin:

1. Vai su **Privacy & Cookie → Settings**
2. Nella sezione **Categories**, aggiungi il servizio alla categoria appropriata
3. Compila: nome, provider, scopo, cookie, base giuridica, ecc.
4. Rigenera le policy

### La generazione è reversibile?

Sì! WordPress mantiene le revisioni dei post. Puoi:

1. Andare su **Pages → Privacy Policy → Revisions**
2. Ripristinare una versione precedente
3. Oppure usare `--dry-run` prima di applicare modifiche

### Quanto tempo richiede la generazione?

Solitamente pochi secondi:

- Rilevamento servizi: 1-3 secondi
- Generazione contenuto: < 1 secondo per lingua
- Salvataggio pagine: < 1 secondo

Totale: ~5-10 secondi per un sito multilingua con 10+ servizi.

### Posso usarlo in un ambiente multisite?

Sì! Il plugin supporta WordPress multisite. Esegui il comando per ogni sito:

```bash
# Per un sito specifico
wp fp-privacy generate-pages --url=https://site1.example.com

# Per tutti i siti
wp site list --field=url | xargs -I {} wp fp-privacy generate-pages --url={}
```

## Supporto

Per problemi o domande:

- 📧 Email: [Contatto plugin]
- 🐛 Bug report: GitHub Issues
- 📖 Documentazione completa: `/docs`

---

**Ultimo aggiornamento**: 10 ottobre 2025  
**Versione plugin**: 0.1.1
