# Script di Utilità - Generazione Policy

Questo script permette di generare automaticamente le pagine Privacy Policy e Cookie Policy.

## Uso Rapido

```bash
# Genera le pagine (lingua predefinita)
./generate-policies.php

# Genera per tutte le lingue
./generate-policies.php --all-languages

# Genera solo per l'italiano
./generate-policies.php --lang=it_IT

# Forza la rigenerazione
./generate-policies.php --force

# Verifica senza modificare
./generate-policies.php --dry-run

# Mostra aiuto completo
./generate-policies.php --help
```

## Alternative

### Con WP-CLI (consigliato)

```bash
wp fp-privacy generate-pages --all-languages
```

### Dalla directory WordPress

```bash
php wp-content/plugins/fp-privacy-cookie-policy/bin/generate-policies.php --all-languages
```

## Opzioni Principali

- `--all-languages` - Genera per tutte le lingue configurate
- `--lang=<codice>` - Genera solo per una lingua (es. it_IT)
- `--force` - Forza la sovrascrittura di pagine modificate
- `--bump-revision` - Incrementa la revisione del consenso
- `--dry-run` - Simula senza salvare modifiche

## Documentazione Completa

Per maggiori dettagli, vedi: [`/docs/GENERAZIONE-AUTOMATICA.md`](../docs/GENERAZIONE-AUTOMATICA.md)
