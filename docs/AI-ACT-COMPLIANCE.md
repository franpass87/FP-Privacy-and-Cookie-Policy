# Checklist Compliance AI Act - FP Privacy & Cookie Policy

## Panoramica

L'AI Act (Regolamento sull'Intelligenza Artificiale) dell'UE richiede trasparenza e disclosure per i sistemi AI utilizzati. Il plugin FP Privacy supporta la conformità all'AI Act Art. 13 attraverso funzionalità dedicate.

## AI Act Art. 13 - Trasparenza Sistemi AI

### Requisiti

L'articolo 13 dell'AI Act richiede che gli utenti siano informati quando interagiscono con sistemi AI, inclusi:
- Identificazione del sistema AI
- Scopo del sistema AI
- Logica decisionale (quando applicabile)
- Diritti dell'utente

### Implementazione nel Plugin

#### 1. Configurazione Admin

**Percorso**: Privacy & Cookie Settings → Privacy → Trattamento dati per sistemi AI

**Campi Disponibili**:
- **Abilita AI Disclosure**: Checkbox per attivare la sezione
- **Sistemi AI Utilizzati**: Lista di sistemi AI con:
  - Nome sistema
  - Scopo
  - Livello di rischio
- **Decisioni Automatizzate**: Checkbox + descrizione
- **Profilazione**: Checkbox + descrizione
- **Testi Personalizzati**: Per ogni lingua attiva

#### 2. Sezione Policy Generata

Quando abilitato, il plugin genera automaticamente una sezione nella Privacy Policy con:
- Titolo: "Trattamento dati per sistemi AI" (o traduzione)
- Descrizione generale
- Lista sistemi AI utilizzati
- Sezione decisioni automatizzate (se abilitata)
- Sezione profilazione (se abilitata)
- Diritti utente
- Informazioni di contatto

#### 3. Template Cookie Policy

Se AI disclosure è abilitato, viene aggiunta anche una sezione nella Cookie Policy:
- "Cookie e tecnologie AI"
- Descrizione cookie utilizzati per sistemi AI
- Diritti utente relativi ai cookie AI

## Checklist Compliance

### Configurazione Base
- [ ] AI Disclosure abilitato in admin
- [ ] Almeno un sistema AI configurato
- [ ] Descrizioni chiare e comprensibili
- [ ] Testi tradotti per tutte le lingue attive

### Sistemi AI
- [ ] Nome sistema chiaro e identificabile
- [ ] Scopo del sistema descritto
- [ ] Livello di rischio indicato (se applicabile)
- [ ] Link a informazioni dettagliate (se disponibile)

### Decisioni Automatizzate
- [ ] Checkbox abilitato se utilizzate
- [ ] Descrizione logica decisionale chiara
- [ ] Esempi concreti forniti
- [ ] Intervento umano disponibile (se applicabile)

### Profilazione
- [ ] Checkbox abilitato se utilizzata
- [ ] Descrizione tecniche di profilazione
- [ ] Scopi profilazione indicati
- [ ] Base giuridica chiarita

### Policy Generata
- [ ] Sezione AI presente in Privacy Policy
- [ ] Contenuto conforme ad AI Act Art. 13
- [ ] Sezione cookie AI presente (se applicabile)
- [ ] Link a informazioni dettagliate funzionanti

### Test Frontend
- [ ] Sezione AI visibile in Privacy Policy
- [ ] Contenuto leggibile e comprensibile
- [ ] Link funzionanti
- [ ] Traduzioni corrette

## Esempi Configurazione

### Esempio 1: Sistema Raccomandazioni

```php
Sistema AI: "Sistema Raccomandazioni Contenuti"
Scopo: "Analizza le tue preferenze di navigazione per suggerire contenuti rilevanti"
Livello di rischio: "Basso"
Decisioni automatizzate: Sì
Descrizione: "Il sistema utilizza machine learning per analizzare i contenuti che visualizzi e suggerisce articoli simili. Le raccomandazioni sono basate su pattern di navigazione e non producono effetti giuridici."
```

### Esempio 2: Chatbot AI

```php
Sistema AI: "Assistente Virtuale AI"
Scopo: "Fornisce supporto clienti automatizzato tramite chatbot"
Livello di rischio: "Medio"
Decisioni automatizzate: Sì
Descrizione: "Il chatbot utilizza elaborazione del linguaggio naturale per rispondere alle domande. In casi complessi, la conversazione viene trasferita a un operatore umano."
Intervento umano: Disponibile
```

## Best Practices

### 1. Chiarezza
- Usa linguaggio semplice e comprensibile
- Evita termini tecnici senza spiegazione
- Fornisci esempi concreti

### 2. Completezza
- Elenca tutti i sistemi AI utilizzati
- Descrivi accuratamente lo scopo
- Indica limitazioni e garanzie

### 3. Trasparenza
- Fornisci link a informazioni dettagliate quando disponibili
- Spiega come funzionano gli algoritmi (in modo comprensibile)
- Indica i dati utilizzati

### 4. Diritti Utente
- Spiega chiaramente i diritti dell'utente
- Fornisci informazioni di contatto
- Indica come esercitare i diritti

## Conformità GDPR

L'AI Act si integra con il GDPR. Assicurati che:
- Base giuridica per trattamento AI sia chiara (consenso/interesse legittimo)
- Profilazione rispetti GDPR Art. 22
- Decisioni automatizzate rispettino GDPR Art. 22
- Dati personali trattati rispettino principi GDPR

## Testing

### Test Configurazione
1. Abilita AI disclosure
2. Aggiungi sistema AI
3. Configura decisioni automatizzate
4. Salva impostazioni
5. Verifica che i dati siano salvati correttamente

### Test Policy Generation
1. Genera Privacy Policy
2. Verifica presenza sezione AI
3. Verifica contenuto conforme
4. Testa traduzioni
5. Verifica link funzionanti

### Test Frontend
1. Visita Privacy Policy
2. Verifica sezione AI visibile
3. Verifica contenuto leggibile
4. Testa link a informazioni dettagliate
5. Verifica traduzioni corrette

## Troubleshooting

### Sezione AI non appare in Policy
- Verifica che AI disclosure sia abilitato
- Controlla che almeno un sistema AI sia configurato
- Rigenera la Privacy Policy

### Contenuto non conforme
- Verifica descrizioni sistemi AI
- Assicurati che decisioni automatizzate siano descritte
- Controlla che diritti utente siano inclusi

### Traduzioni mancanti
- Verifica che testi personalizzati siano configurati per ogni lingua
- Controlla che lingua di default abbia testi
- Rigenera policy dopo modifiche traduzioni

## Riferimenti

- [AI Act - Regolamento UE 2024/1689](https://eur-lex.europa.eu/legal-content/IT/TXT/?uri=CELEX:32021R0106)
- [GDPR Art. 13.2(f)](https://eur-lex.europa.eu/legal-content/IT/TXT/?uri=CELEX:32016R0679)
- [EDPB Guidelines on AI](https://edpb.europa.eu/)


