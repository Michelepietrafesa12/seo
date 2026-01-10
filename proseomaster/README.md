# ProSEO Master - Plugin SEO Professionale per PrestaShop 1.7

## Descrizione

ProSEO Master è un modulo SEO completo e professionale per PrestaShop 1.7, progettato per ottimizzare il posizionamento organico del tuo e-commerce e garantire la conformità con le linee guida di Google Search Console.

## Caratteristiche Principali

### Schema Markup (Dati Strutturati)

- **Product Schema**: Schema prodotto completo con tutti i campi richiesti da Google:
  - Nome, descrizione, immagini
  - SKU, GTIN (EAN13/UPC/ISBN), MPN
  - Brand/Produttore
  - Prezzo, disponibilità, valuta
  - Condizione prodotto (nuovo/usato/ricondizionato)
  - Recensioni e valutazione aggregata
  - Informazioni sulla spedizione

- **Organization Schema**: Dati strutturati dell'organizzazione con:
  - Logo e informazioni aziendali
  - Contatti (telefono, email)
  - Profili social media

- **WebSite Schema**: Con SearchAction per il sitelinks search box

- **BreadcrumbList Schema**: Per una migliore navigazione nei risultati di ricerca

- **LocalBusiness Schema**: Per negozi con sede fisica (opzionale)

- **ItemList Schema**: Per le pagine categoria

### Meta Tags Social

- **Open Graph Tags**: Per una migliore condivisione su Facebook, LinkedIn, etc.
- **Twitter Cards**: Per anteprime ottimizzate su Twitter/X

### SEO Tecnico

- **URL Canonici**: Previene problemi di contenuti duplicati
- **Hreflang Tags**: Per siti multilingue
- **Mappatura campi personalizzabile**: Configura quale campo usare per GTIN, MPN, Brand

## Requisiti

- PrestaShop 1.7.0 o superiore
- PHP 7.2 o superiore

## Installazione

1. Scarica il modulo `proseomaster`
2. **IMPORTANTE**: Aggiungi un file `logo.png` (32x32 pixel) nella cartella principale del modulo
3. Carica la cartella `proseomaster` in `/modules/` del tuo PrestaShop
4. Vai nel Back Office > Moduli > Gestione Moduli
5. Cerca "ProSEO Master" e clicca su "Installa"
6. Configura il modulo secondo le tue esigenze

## Configurazione

Dopo l'installazione, accedi alla configurazione del modulo per:

### 1. Schema Markup
- Abilita/disabilita i vari tipi di schema
- Configura il numero minimo di recensioni per mostrare l'AggregateRating

### 2. Mappatura Campi Prodotto
- **GTIN**: Scegli tra EAN-13, UPC, ISBN o nessuno
- **MPN**: Usa il campo Reference o Supplier Reference
- **Brand**: Usa Manufacturer, Supplier o Nome Negozio

### 3. Informazioni Organizzazione
- Nome legale dell'organizzazione
- URL del logo (minimo 112x112px, consigliato PNG o JPG)
- Numero di telefono con prefisso internazionale
- Email di contatto

### 4. Profili Social Media
- Facebook, Twitter, Instagram
- LinkedIn, YouTube, Pinterest

### 5. Attività Locale (opzionale)
- Tipo di attività
- Indirizzo completo
- Coordinate geografiche (latitudine/longitudine)

### 6. Open Graph & Twitter Cards
- Abilita/disabilita i tag
- Username Twitter
- Immagine predefinita per condivisione (1200x630px consigliato)

### 7. SEO Tecnico
- Gestione URL canonici
- Tag hreflang per multilingue

## Verifica Implementazione

### Google Search Console

1. Vai su [Google Rich Results Test](https://search.google.com/test/rich-results)
2. Inserisci l'URL di una pagina prodotto
3. Verifica che non ci siano errori nei dati strutturati

### Schema Markup Validator

1. Vai su [Schema.org Validator](https://validator.schema.org/)
2. Inserisci l'URL della pagina
3. Controlla che tutti i campi siano correttamente popolati

## Campi Schema Prodotto Richiesti da Google

Per ottenere i rich snippets di Google, assicurati che i tuoi prodotti abbiano:

| Campo | Obbligatorio | Note |
|-------|--------------|------|
| name | Sì | Nome del prodotto |
| image | Sì | Almeno un'immagine |
| offers.price | Sì | Prezzo del prodotto |
| offers.priceCurrency | Sì | Codice valuta (EUR, USD, etc.) |
| offers.availability | Consigliato | InStock, OutOfStock, BackOrder |
| brand | Consigliato | Nome del brand/produttore |
| sku | Consigliato | Codice SKU univoco |
| gtin/gtin13/gtin12 | Consigliato | Codice EAN/UPC |
| mpn | Consigliato | Codice produttore |
| aggregateRating | Consigliato | Richiede recensioni reali |

## Risoluzione Problemi

### "Missing field" nella Search Console

Verifica che:
1. Il prodotto abbia tutti i campi compilati nel back office
2. I campi GTIN/MPN siano configurati correttamente nel modulo
3. Il produttore sia associato al prodotto

### Le recensioni non appaiono

Verifica che:
1. Il modulo `productcomments` sia installato e attivo
2. Ci siano recensioni approvate per il prodotto
3. Il numero minimo di recensioni sia raggiunto

### Open Graph non funziona

Verifica che:
1. Le immagini prodotto siano accessibili pubblicamente
2. L'URL dell'immagine predefinita sia corretto
3. Non ci siano altri moduli che iniettano tag OG

## Compatibilità

- **PrestaShop**: 1.7.0 - 1.7.8.x
- **PHP**: 7.2, 7.3, 7.4, 8.0, 8.1
- **Multilingue**: Sì
- **Multinegozio**: Sì

## Changelog

### Versione 1.0.0
- Rilascio iniziale
- Schema Product completo
- Schema Organization e WebSite
- Schema BreadcrumbList e ItemList
- Schema LocalBusiness
- Open Graph e Twitter Cards
- URL Canonici e Hreflang
- Supporto recensioni e AggregateRating

## Supporto

Per problemi o richieste di funzionalità, contatta il supporto tecnico.

## Licenza

MIT License - Libero utilizzo per progetti commerciali e non.
