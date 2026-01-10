# Pro SEO Module per PrestaShop 1.7

Modulo SEO professionale e completo per PrestaShop 1.7, compatibile con PHP 7.2+.

## Caratteristiche Principali

### Schema Markup (JSON-LD)
- **Product Schema** completo con:
  - Nome, descrizione, immagini
  - Prezzo, disponibilità, SKU
  - GTIN/EAN, MPN
  - Brand/Produttore
  - Condizione prodotto (Nuovo, Usato, Ricondizionato)
  - Aggregate Rating e Recensioni
  - Offers con seller, shipping e return policy
  - Dimensioni e peso
  - Colore e materiale (da attributi)

- **Organization/LocalBusiness Schema**
  - Dati aziendali
  - Logo
  - Contatti
  - Indirizzo
  - Profili social

- **BreadcrumbList Schema**
  - Generazione automatica del percorso di navigazione
  - Supporto per prodotti, categorie, CMS, brand

- **WebSite Schema**
  - SearchAction per sitelinks search box

### Meta Tags Avanzati
- **Open Graph** per Facebook
  - og:type, og:title, og:description, og:image
  - product:price, product:availability per i prodotti

- **Twitter Cards**
  - summary_large_image per prodotti con immagini
  - Supporto per twitter:site

- **Canonical URLs**
  - Gestione automatica per evitare contenuti duplicati
  - Supporto paginazione

- **Hreflang**
  - Supporto automatico per siti multilingua
  - Tag x-default

- **Robots Meta**
  - Gestione noindex per pagine non rilevanti
  - Configurazione per tipo di pagina

### Sitemap XML
- Generazione sitemap ottimizzata
- Supporto Image Sitemap
- Priorità automatiche basate sulla struttura

### Pannello di Controllo
- Dashboard con statistiche SEO
- Checklist SEO interattiva
- Test schema con Rich Results Test
- Gestione cache schema

## Requisiti
- PrestaShop 1.7.0 - 1.7.x
- PHP 7.2 o superiore
- MySQL 5.6 o superiore

## Installazione

1. Scarica il modulo
2. Carica la cartella `proseomodule` in `/modules/`
3. Vai in Back Office > Moduli > Catalogo Moduli
4. Cerca "Pro SEO" e clicca su Installa
5. Configura il modulo con i dati della tua azienda

## Configurazione

### Schema Markup
1. Abilita/disabilita i vari tipi di schema
2. Configura la condizione default dei prodotti
3. Imposta i giorni di validità del prezzo

### Organizzazione
1. Inserisci il nome azienda
2. Carica l'URL del logo (consigliato: 600x60px)
3. Aggiungi telefono, email, indirizzo

### Social
1. Aggiungi gli URL dei profili social
2. Configura l'username Twitter per le Cards

### Meta Tags
1. Abilita Open Graph e Twitter Cards
2. Configura Canonical e Hreflang

## Validazione

Per verificare che gli schema siano corretti:

1. Vai nel pannello Pro SEO
2. Inserisci un URL del tuo sito
3. Clicca "Testa su Google"

Oppure usa direttamente:
- [Rich Results Test](https://search.google.com/test/rich-results)
- [Schema Markup Validator](https://validator.schema.org/)

## Errori Comuni Google Search Console

### "Missing field: priceValidUntil"
Il modulo lo gestisce automaticamente con la configurazione "Validità Prezzo (giorni)".

### "Missing field: brand"
Assicurati che i prodotti abbiano un produttore associato in PrestaShop.

### "Missing field: review"
Le recensioni vengono incluse automaticamente se usi il modulo productcomments.

### "Missing field: gtin"
Compila il campo EAN dei prodotti in PrestaShop.

## Supporto

Per segnalare bug o richiedere funzionalità, contatta il team di sviluppo.

## Licenza

Academic Free License 3.0 (AFL-3.0)

## Changelog

### 1.0.0
- Release iniziale
- Schema Product, Organization, Breadcrumb, WebSite
- Open Graph e Twitter Cards
- Canonical e Hreflang
- Sitemap XML
- Pannello amministrazione
