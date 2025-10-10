# 🚀 Quick Start - Generazione Automatica Policy

Guida rapida per generare automaticamente le pagine Privacy Policy e Cookie Policy.

## ⚡ TL;DR

```bash
# Con WP-CLI (consigliato)
wp fp-privacy generate-pages --all-languages

# Senza WP-CLI
php bin/generate-policies.php --all-languages
```

## 📋 Cosa Fa

Questo comando:

1. ✅ **Rileva** automaticamente i servizi integrati (Google Analytics, Facebook, ecc.)
2. ✅ **Crea** le pagine Privacy Policy e Cookie Policy se non esistono
3. ✅ **Genera** il contenuto completo conforme al GDPR
4. ✅ **Aggiorna** le pagine WordPress con il contenuto
5. ✅ **Salva** uno snapshot dei servizi rilevati

## 🎯 Casi d'Uso Comuni

### Prima Installazione

```bash
wp fp-privacy generate-pages --all-languages
```

### Hai Aggiunto Nuovi Servizi

```bash
wp fp-privacy generate-pages --all-languages --bump-revision
```

### Test Prima di Applicare

```bash
wp fp-privacy generate-pages --dry-run
```

### Rigenerazione Forzata

```bash
wp fp-privacy generate-pages --force
```

## 🌍 Supporto Multilingua

```bash
# Tutte le lingue configurate
wp fp-privacy generate-pages --all-languages

# Solo italiano
wp fp-privacy generate-pages --lang=it_IT

# Solo inglese
wp fp-privacy generate-pages --lang=en_US
```

## 🔧 Opzioni Disponibili

| Opzione | Descrizione |
|---------|-------------|
| `--all-languages` | Genera per tutte le lingue |
| `--lang=it_IT` | Genera solo per una lingua |
| `--force` | Sovrascrive modifiche manuali |
| `--bump-revision` | Incrementa versione consenso |
| `--dry-run` | Simula senza salvare |

## 📱 Interfaccia Admin

Non ti piace la command line? Puoi anche usare l'interfaccia admin:

1. Vai su **WordPress Admin → Privacy & Cookie → Policy Editor**
2. Clicca **"Detect integrations and regenerate"**
3. ✨ Fatto!

## 📚 Documentazione Completa

Per maggiori dettagli: [`/docs/GENERAZIONE-AUTOMATICA.md`](docs/GENERAZIONE-AUTOMATICA.md)

## ❓ FAQ Veloce

**D: Il contenuto è conforme al GDPR?**  
R: Sì! Segue GDPR, ePrivacy Directive e linee guida EDPB 2025.

**D: Posso modificare le pagine manualmente?**  
R: Sì! Il plugin rispetta le modifiche. Usa `--force` per sovrascrivere.

**D: Rileva automaticamente tutti i servizi?**  
R: Rileva i più comuni (Google Analytics, Facebook, YouTube, ecc.). Puoi aggiungere servizi personalizzati nelle impostazioni.

**D: Quanto tempo richiede?**  
R: 5-10 secondi per un sito multilingua.

## 🆘 Aiuto

```bash
# WP-CLI
wp fp-privacy generate-pages --help

# Script standalone
php bin/generate-policies.php --help
```

---

**Pronto?** Esegui il comando e avrai le tue policy in pochi secondi! 🎉
