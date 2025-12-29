# Guida Compliance 2025/2026 - FP Privacy & Cookie Policy

## Panoramica

Questa guida descrive le funzionalità di compliance implementate nel plugin FP Privacy & Cookie Policy per conformarsi alle direttive e normative europee aggiornate al 2025/2026.

## Normative Coperte

### 1. GDPR (Regolamento Generale sulla Protezione dei Dati)
- **Articolo 7.3**: Revoca del consenso
- **Articolo 13.2(f)**: Trasparenza logica automatizzata
- **Articolo 22**: Decisioni automatizzate e profilazione
- **Articolo 30**: Registro delle attività di trattamento

### 2. AI Act (Regolamento sull'Intelligenza Artificiale)
- **Articolo 13**: Trasparenza sistemi AI
- Obblighi di disclosure per sistemi AI/ML

### 3. Digital Omnibus
- Trasparenza algoritmica
- Disclosure decisioni automatizzate

### 4. EDPB Guidelines 2025
- Granularità avanzata del consenso
- Sub-categorie per servizi individuali

### 5. ePrivacy Directive
- **Articolo 5.3**: Revoca consenso cookie

## Funzionalità Implementate

### 1. Revoca Consenso

**Conformità**: GDPR Art. 7.3, ePrivacy Art. 5.3

**Implementazione**:
- Endpoint REST: `POST /wp-json/fp-privacy/v1/consent/revoke`
- Funzione JavaScript: `revokeConsent()`
- Pulsante revoca in modal preferenze
- Log eventi `consent_revoked`
- Aggiornamento Google Consent Mode

**Utilizzo**:
```javascript
// Revoca consenso programmaticamente
revokeConsent();
```

**Configurazione Admin**:
- Nessuna configurazione richiesta
- Funzionalità sempre disponibile

### 2. Supporto AI Act

**Conformità**: AI Act Art. 13, GDPR Art. 13.2(f)

**Implementazione**:
- Sezione dedicata in Privacy Policy
- Configurazione admin per sistemi AI utilizzati
- Template automatico conforme

**Configurazione Admin**:
1. Vai a **Privacy & Cookie Settings** → **Privacy**
2. Abilita "Trattamento dati per sistemi AI"
3. Configura sistemi AI utilizzati
4. Aggiungi descrizioni per decisioni automatizzate e profilazione

**Sezioni Policy Generate**:
- Trattamento dati per sistemi AI
- Sistemi AI utilizzati
- Decisioni automatizzate
- Profilazione
- Diritti utente

### 3. Trasparenza Algoritmica (Digital Omnibus)

**Conformità**: Digital Omnibus, GDPR Art. 22

**Implementazione**:
- Value Object `AlgorithmicTransparency`
- Sezione admin dedicata
- Generazione automatica sezione policy

**Configurazione Admin**:
1. Vai a **Privacy & Cookie Settings** → **Privacy**
2. Sezione "Trasparenza Algoritmica (Digital Omnibus)"
3. Abilita decisioni automatizzate/profilazione
4. Aggiungi descrizioni logica decisionale
5. Configura disponibilità intervento umano

**Sezioni Policy Generate**:
- Trasparenza Algoritmica
- Decisioni Automatizzate
- Profilazione
- Diritti utente

### 4. Granularità Avanzata Consenso (EDPB 2025)

**Conformità**: EDPB Guidelines 2025

**Implementazione**:
- Toggle individuali per ogni servizio rilevato
- Supporto sub-categorie
- UI admin per abilitazione

**Configurazione Admin**:
1. Vai a **Privacy & Cookie Settings** → **Cookies**
2. Sezione "Granularità Consenso (EDPB 2025)"
3. Abilita "Toggle individuali per servizi"
4. Gli utenti vedranno toggle per ogni servizio (GA4, GTM, Facebook Pixel, ecc.)

**Comportamento Frontend**:
- Quando abilitato, ogni servizio ha un toggle individuale
- Gli utenti possono accettare/rifiutare servizi specifici
- Il payload consenso include dettaglio per servizio

### 5. Tracking Eventi Revoca

**Conformità**: GDPR Art. 30 (Accountability)

**Implementazione**:
- Eventi log: `consent_revoked`, `consent_withdrawn`
- Metriche analytics: tasso revoca
- Dashboard con trend revoca

**Visualizzazione**:
- Vai a **Privacy & Cookie Settings** → **Analytics**
- Stat card "Revoche Consenso"
- Stat card "Tasso Revoca"
- Tabella eventi recenti mostra revoche

## Checklist Compliance

### GDPR Art. 7.3 (Revoca Consenso)
- [x] Endpoint REST per revoca
- [x] Funzione frontend revoca
- [x] Log eventi revoca
- [x] Aggiornamento Google Consent Mode
- [x] Pulsante revoca in UI

### AI Act Art. 13 (Trasparenza AI)
- [x] Sezione policy AI
- [x] Configurazione admin sistemi AI
- [x] Template disclosure automatico
- [x] Supporto multi-lingua

### Digital Omnibus (Trasparenza Algoritmica)
- [x] Sezione policy trasparenza algoritmica
- [x] Configurazione admin decisioni automatizzate
- [x] Descrizione logica decisionale
- [x] Supporto intervento umano

### EDPB 2025 (Granularità)
- [x] Toggle individuali servizi
- [x] Supporto sub-categorie
- [x] UI admin configurazione
- [x] Payload consenso dettagliato

### ePrivacy (Revoca Cookie)
- [x] Revoca consenso cookie
- [x] Cookie cleanup automatico
- [x] Banner riappare dopo revoca

## Best Practices

### 1. Configurazione AI Disclosure
- Descrivi chiaramente i sistemi AI utilizzati
- Indica il livello di rischio per ogni sistema
- Fornisci link a informazioni dettagliate se disponibili

### 2. Trasparenza Algoritmica
- Spiega in modo comprensibile la logica decisionale
- Indica se l'intervento umano è disponibile
- Fornisci esempi concreti di decisioni automatizzate

### 3. Granularità Consenso
- Abilita sub-categorie solo se necessario
- Verifica che i servizi siano correttamente rilevati
- Testa l'esperienza utente con toggle individuali

### 4. Revoca Consenso
- Monitora il tasso di revoca in Analytics
- Se il tasso è alto (>10%), rivedi le pratiche di consenso
- Assicurati che il banner riappaia correttamente dopo revoca

## Testing

### Test Revoca Consenso
1. Accetta tutti i cookie
2. Apri modal preferenze
3. Clicca "Revoca tutti i consensi"
4. Conferma nel dialog
5. Verifica che il banner riappaia
6. Verifica che i cookie siano eliminati
7. Controlla Analytics per evento `consent_revoked`

### Test AI Disclosure
1. Abilita AI disclosure in admin
2. Configura almeno un sistema AI
3. Genera Privacy Policy
4. Verifica presenza sezione AI
5. Verifica contenuto conforme ad AI Act Art. 13

### Test Trasparenza Algoritmica
1. Abilita decisioni automatizzate in admin
2. Aggiungi descrizione logica decisionale
3. Genera Privacy Policy
4. Verifica sezione trasparenza algoritmica
5. Verifica contenuto conforme Digital Omnibus

### Test Granularità Avanzata
1. Abilita sub-categorie in admin
2. Assicurati che servizi siano rilevati
3. Apri modal preferenze frontend
4. Verifica toggle individuali per servizi
5. Testa accettazione/rifiuto servizi specifici

## Supporto

Per domande o problemi relativi alla compliance:
- Consulta la documentazione specifica: `AI-ACT-COMPLIANCE.md`, `DIGITAL-OMNIBUS-GUIDE.md`
- Verifica i log del plugin se `debug_logging` è abilitato
- Controlla Analytics per metriche compliance

## Aggiornamenti

Questa guida riflette lo stato delle normative aggiornato a ottobre 2025. Le normative possono evolversi, quindi è importante:
- Monitorare aggiornamenti EDPB
- Rivedere periodicamente le configurazioni
- Aggiornare le policy quando necessario


