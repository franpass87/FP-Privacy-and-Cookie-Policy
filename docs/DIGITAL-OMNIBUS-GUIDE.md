# Guida Digital Omnibus - Trasparenza Algoritmica

## Panoramica

Il Digital Omnibus richiede trasparenza algoritmica per decisioni automatizzate e profilazione. Il plugin FP Privacy supporta la conformità attraverso funzionalità dedicate per la disclosure algoritmica.

## Requisiti Digital Omnibus

### Trasparenza Algoritmica

Il Digital Omnibus richiede che gli utenti siano informati su:
- Logica utilizzata nelle decisioni automatizzate
- Significato e conseguenze delle decisioni
- Profilazione utilizzata
- Disponibilità intervento umano
- Diritti dell'utente

### Conformità GDPR Art. 22

Il GDPR Art. 22 si integra con Digital Omnibus:
- Diritto di non essere sottoposto a decisioni automatizzate
- Diritto di intervento umano
- Diritto di esprimere il proprio punto di vista
- Diritto di contestare la decisione

## Implementazione nel Plugin

### 1. Configurazione Admin

**Percorso**: Privacy & Cookie Settings → Privacy → Trasparenza Algoritmica (Digital Omnibus)

**Campi Disponibili**:
- **Utilizzo decisioni automatizzate**: Checkbox
- **Descrizione logica decisionale**: Textarea
- **Utilizzo profilazione**: Checkbox
- **Descrizione profilazione**: Textarea
- **Intervento umano disponibile**: Checkbox (default: true)
- **URL informazioni dettagliate algoritmi**: URL (opzionale)

### 2. Sezione Policy Generata

Quando configurato, il plugin genera automaticamente:
- Sezione "Trasparenza Algoritmica"
- Sottosezione "Decisioni Automatizzate" (se abilitata)
- Sottosezione "Profilazione" (se abilitata)
- Sottosezione "I tuoi diritti"
- Link a informazioni dettagliate (se fornito)

### 3. Value Object

Il plugin utilizza `AlgorithmicTransparency` Value Object per:
- Type safety
- Validazione dati
- Sanitizzazione input

## Checklist Compliance

### Configurazione Base
- [ ] Decisioni automatizzate configurate (se utilizzate)
- [ ] Profilazione configurata (se utilizzata)
- [ ] Descrizioni chiare e comprensibili
- [ ] Intervento umano configurato correttamente

### Decisioni Automatizzate
- [ ] Checkbox abilitato se utilizzate
- [ ] Descrizione logica decisionale completa
- [ ] Esempi concreti forniti
- [ ] Conseguenze decisioni spiegate
- [ ] Intervento umano disponibile (se applicabile)

### Profilazione
- [ ] Checkbox abilitato se utilizzata
- [ ] Descrizione tecniche profilazione
- [ ] Scopi profilazione indicati
- [ ] Base giuridica chiarita
- [ ] Diritti opposizione spiegati

### Policy Generata
- [ ] Sezione trasparenza algoritmica presente
- [ ] Contenuto conforme Digital Omnibus
- [ ] Diritti utente chiaramente indicati
- [ ] Link informazioni dettagliate funzionanti

### Test Frontend
- [ ] Sezione visibile in Privacy Policy
- [ ] Contenuto leggibile e comprensibile
- [ ] Link funzionanti
- [ ] Traduzioni corrette

## Esempi Configurazione

### Esempio 1: Raccomandazioni Personalizzate

```php
Decisioni automatizzate: Sì
Descrizione: "Utilizziamo algoritmi di machine learning per analizzare le tue preferenze di navigazione e suggerire contenuti rilevanti. L'algoritmo considera: pagine visitate, tempo di permanenza, pattern di clic, contenuti simili visualizzati. Le raccomandazioni vengono aggiornate in tempo reale basandosi sul tuo comportamento. Non producono effetti giuridici e puoi sempre ignorare i suggerimenti."

Profilazione: Sì
Descrizione: "Creiamo un profilo dei tuoi interessi basandoci su: categorie di contenuti visualizzati, pattern di navigazione, interazioni con contenuti. Questo profilo viene utilizzato per personalizzare l'esperienza e mostrare contenuti rilevanti. Puoi opporti alla profilazione in qualsiasi momento."

Intervento umano: Disponibile
```

### Esempio 2: Prevenzione Frodi

```php
Decisioni automatizzate: Sì
Descrizione: "Utilizziamo sistemi automatizzati per rilevare transazioni sospette. Il sistema analizza: pattern di pagamento, geolocalizzazione, dispositivo utilizzato, storico transazioni. Se viene rilevata un'anomalia, la transazione viene bloccata automaticamente. Puoi contattare il supporto per richiedere una revisione umana."

Profilazione: No

Intervento umano: Disponibile
URL dettagli: "https://example.com/fraud-prevention-algorithm"
```

## Best Practices

### 1. Chiarezza
- Spiega la logica in modo comprensibile
- Evita gergo tecnico senza spiegazione
- Fornisci esempi concreti

### 2. Completezza
- Descrivi accuratamente gli algoritmi utilizzati
- Indica i dati utilizzati
- Spiega le conseguenze delle decisioni

### 3. Trasparenza
- Fornisci link a informazioni dettagliate quando disponibili
- Spiega come funzionano gli algoritmi
- Indica limitazioni e garanzie

### 4. Diritti Utente
- Spiega chiaramente i diritti
- Fornisci informazioni di contatto
- Indica come esercitare i diritti

## Conformità GDPR Art. 22

### Requisiti GDPR Art. 22

L'articolo 22 del GDPR richiede:
- Informazioni sulla logica utilizzata
- Significato e conseguenze delle decisioni
- Diritto di intervento umano
- Diritto di esprimere il proprio punto di vista
- Diritto di contestare la decisione

### Implementazione

Il plugin genera automaticamente:
- Informazioni sulla logica (dalla descrizione configurata)
- Diritti utente (template predefinito)
- Informazioni di contatto (da DPO/privacy email)

## Testing

### Test Configurazione
1. Abilita decisioni automatizzate
2. Aggiungi descrizione logica decisionale
3. Abilita profilazione (se applicabile)
4. Configura intervento umano
5. Salva impostazioni
6. Verifica che i dati siano salvati correttamente

### Test Policy Generation
1. Genera Privacy Policy
2. Verifica presenza sezione trasparenza algoritmica
3. Verifica contenuto conforme Digital Omnibus
4. Verifica diritti utente inclusi
5. Testa link informazioni dettagliate

### Test Frontend
1. Visita Privacy Policy
2. Verifica sezione trasparenza algoritmica visibile
3. Verifica contenuto leggibile
4. Testa link informazioni dettagliate
5. Verifica traduzioni corrette

## Troubleshooting

### Sezione non appare in Policy
- Verifica che decisioni automatizzate o profilazione siano abilitate
- Controlla che descrizioni siano configurate
- Rigenera la Privacy Policy

### Contenuto non conforme
- Verifica descrizioni complete
- Assicurati che diritti utente siano inclusi
- Controlla che intervento umano sia configurato

### Link non funzionanti
- Verifica URL informazioni dettagliate
- Controlla che URL sia accessibile
- Testa link in Privacy Policy generata

## Riferimenti

- [Digital Omnibus - Trasparenza Algoritmica](https://digital-strategy.ec.europa.eu/)
- [GDPR Art. 22](https://eur-lex.europa.eu/legal-content/IT/TXT/?uri=CELEX:32016R0679)
- [EDPB Guidelines on Automated Decision-Making](https://edpb.europa.eu/)


