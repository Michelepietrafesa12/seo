<?php
/**
 * ProSEOMaster - Schema Validator
 *
 * Validates JSON-LD structured data:
 * - Required fields check
 * - Google Rich Results compatibility
 * - Preview generation
 * - Test links
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 * @version     1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterSchemaValidator
{
    /** @var Context */
    protected $context;

    /** @var array Validation results */
    protected $errors = array();

    /** @var array Warnings */
    protected $warnings = array();

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Validate Product schema
     * @param array $schema
     * @return array Validation result
     */
    public function validateProductSchema($schema)
    {
        $this->errors = array();
        $this->warnings = array();

        // Required fields for Google
        $requiredFields = array(
            'name' => 'Nome prodotto',
            'image' => 'Immagine prodotto',
            'offers' => 'Informazioni prezzo',
        );

        foreach ($requiredFields as $field => $label) {
            if (!isset($schema[$field]) || empty($schema[$field])) {
                $this->errors[] = "Campo obbligatorio mancante: {$label} ({$field})";
            }
        }

        // Check offers structure
        if (isset($schema['offers'])) {
            $offer = is_array($schema['offers']) && isset($schema['offers']['@type'])
                ? $schema['offers']
                : (isset($schema['offers'][0]) ? $schema['offers'][0] : null);

            if ($offer) {
                if (!isset($offer['price']) || $offer['price'] === '') {
                    $this->errors[] = 'Prezzo mancante nello schema Offer';
                }
                if (!isset($offer['priceCurrency'])) {
                    $this->errors[] = 'Valuta mancante nello schema Offer';
                }
                if (!isset($offer['availability'])) {
                    $this->warnings[] = 'Disponibilità non specificata - consigliato aggiungere availability';
                }
            }
        }

        // Recommended fields
        $recommendedFields = array(
            'description' => 'Descrizione prodotto',
            'sku' => 'SKU/Reference',
            'brand' => 'Marca/Brand',
            'aggregateRating' => 'Valutazioni aggregate',
        );

        foreach ($recommendedFields as $field => $label) {
            if (!isset($schema[$field]) || empty($schema[$field])) {
                $this->warnings[] = "Campo consigliato mancante: {$label} ({$field})";
            }
        }

        // Check GTIN/MPN/Brand (at least one identifier)
        $hasIdentifier = false;
        $identifiers = array('gtin13', 'gtin', 'gtin8', 'gtin12', 'gtin14', 'mpn', 'isbn');
        foreach ($identifiers as $id) {
            if (isset($schema[$id]) && !empty($schema[$id])) {
                $hasIdentifier = true;
                break;
            }
        }

        if (!$hasIdentifier) {
            $this->warnings[] = 'Nessun identificatore prodotto (GTIN, MPN, ISBN) - fortemente consigliato per Google Shopping';
        }

        // Check brand
        if (isset($schema['brand']) && is_array($schema['brand'])) {
            if (!isset($schema['brand']['name']) || empty($schema['brand']['name'])) {
                $this->errors[] = 'Brand definito ma senza nome';
            }
        }

        return array(
            'valid' => count($this->errors) === 0,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'score' => $this->calculateSchemaScore(),
        );
    }

    /**
     * Validate Organization schema
     * @param array $schema
     * @return array
     */
    public function validateOrganizationSchema($schema)
    {
        $this->errors = array();
        $this->warnings = array();

        $requiredFields = array(
            'name' => 'Nome organizzazione',
            'url' => 'URL sito web',
        );

        foreach ($requiredFields as $field => $label) {
            if (!isset($schema[$field]) || empty($schema[$field])) {
                $this->errors[] = "Campo obbligatorio mancante: {$label}";
            }
        }

        // Recommended
        if (!isset($schema['logo']) || empty($schema['logo'])) {
            $this->warnings[] = 'Logo mancante - consigliato per rich results';
        }

        if (!isset($schema['sameAs']) || empty($schema['sameAs'])) {
            $this->warnings[] = 'Profili social (sameAs) mancanti - consigliati per Knowledge Panel';
        }

        if (!isset($schema['contactPoint']) || empty($schema['contactPoint'])) {
            $this->warnings[] = 'Contatti (contactPoint) mancanti - consigliati per customer service';
        }

        return array(
            'valid' => count($this->errors) === 0,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'score' => $this->calculateSchemaScore(),
        );
    }

    /**
     * Validate BreadcrumbList schema
     * @param array $schema
     * @return array
     */
    public function validateBreadcrumbSchema($schema)
    {
        $this->errors = array();
        $this->warnings = array();

        if (!isset($schema['itemListElement']) || empty($schema['itemListElement'])) {
            $this->errors[] = 'Nessun elemento breadcrumb definito';
            return array(
                'valid' => false,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
                'score' => 0,
            );
        }

        foreach ($schema['itemListElement'] as $index => $item) {
            $position = $index + 1;

            if (!isset($item['position'])) {
                $this->errors[] = "Elemento {$position}: position mancante";
            }

            if (!isset($item['item']) || !isset($item['item']['@id'])) {
                $this->errors[] = "Elemento {$position}: URL (@id) mancante";
            }

            if (!isset($item['item']['name']) || empty($item['item']['name'])) {
                $this->warnings[] = "Elemento {$position}: nome mancante";
            }
        }

        return array(
            'valid' => count($this->errors) === 0,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'score' => $this->calculateSchemaScore(),
        );
    }

    /**
     * Calculate schema quality score
     * @return int
     */
    protected function calculateSchemaScore()
    {
        $score = 100;

        // Deduct for errors
        $score -= count($this->errors) * 20;

        // Deduct for warnings
        $score -= count($this->warnings) * 5;

        return max(0, min(100, $score));
    }

    /**
     * Generate preview of schema for a product
     * @param int $idProduct
     * @param int $idLang
     * @return array
     */
    public function generateProductSchemaPreview($idProduct, $idLang)
    {
        $product = new Product($idProduct, true, $idLang);

        if (!Validate::isLoadedObject($product)) {
            return array('error' => 'Prodotto non trovato');
        }

        $link = $this->context->link;
        $productUrl = $link->getProductLink($product);

        // Build basic schema
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => strip_tags($product->description_short),
            'url' => $productUrl,
        );

        // Image
        $coverImage = Product::getCover($idProduct);
        if ($coverImage) {
            $schema['image'] = $link->getImageLink($product->link_rewrite, $coverImage['id_image'], ImageType::getFormattedName('large'));
        }

        // SKU
        if (!empty($product->reference)) {
            $schema['sku'] = $product->reference;
        }

        // GTIN
        if (!empty($product->ean13)) {
            $schema['gtin13'] = $product->ean13;
        }

        // MPN
        if (!empty($product->reference)) {
            $schema['mpn'] = $product->reference;
        }

        // Brand
        if ($product->id_manufacturer) {
            $manufacturer = new Manufacturer($product->id_manufacturer, $idLang);
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $manufacturer->name,
            );
        }

        // Offer
        $price = $product->getPrice(true, null, 2);
        $quantity = Product::getQuantity($idProduct);

        $schema['offers'] = array(
            '@type' => 'Offer',
            'price' => number_format($price, 2, '.', ''),
            'priceCurrency' => $this->context->currency->iso_code,
            'availability' => $quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $productUrl,
        );

        // Validate
        $validation = $this->validateProductSchema($schema);

        return array(
            'schema' => $schema,
            'json' => json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'validation' => $validation,
            'test_urls' => $this->getTestUrls($productUrl),
        );
    }

    /**
     * Get test URLs for structured data validators
     * @param string $pageUrl
     * @return array
     */
    public function getTestUrls($pageUrl)
    {
        $encodedUrl = urlencode($pageUrl);

        return array(
            'google_rich_results' => 'https://search.google.com/test/rich-results?url=' . $encodedUrl,
            'google_structured_data' => 'https://validator.schema.org/#url=' . $encodedUrl,
            'schema_markup_validator' => 'https://validator.schema.org/#url=' . $encodedUrl,
            'facebook_debugger' => 'https://developers.facebook.com/tools/debug/?q=' . $encodedUrl,
            'twitter_validator' => 'https://cards-dev.twitter.com/validator',
        );
    }

    /**
     * Validate JSON-LD syntax
     * @param string $jsonLd
     * @return array
     */
    public function validateJsonLdSyntax($jsonLd)
    {
        $result = array(
            'valid' => false,
            'error' => null,
            'data' => null,
        );

        // Try to decode
        $decoded = json_decode($jsonLd, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $result['error'] = 'JSON non valido: ' . json_last_error_msg();
            return $result;
        }

        // Check for @context
        if (!isset($decoded['@context'])) {
            $result['error'] = 'Manca @context - richiesto per JSON-LD';
            return $result;
        }

        // Check for @type
        if (!isset($decoded['@type'])) {
            $result['error'] = 'Manca @type - richiesto per definire il tipo di schema';
            return $result;
        }

        $result['valid'] = true;
        $result['data'] = $decoded;

        return $result;
    }

    /**
     * Get all schema issues for the site
     * @return array
     */
    public function auditAllSchemas()
    {
        $results = array(
            'products_checked' => 0,
            'products_with_errors' => 0,
            'products_with_warnings' => 0,
            'common_issues' => array(),
            'products_needing_attention' => array(),
        );

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        // Get sample of products
        $products = Db::getInstance()->executeS(
            'SELECT p.id_product, pl.name
             FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . '
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = ' . $idShop . '
             WHERE ps.active = 1
             LIMIT 100'
        );

        $issueCounts = array();

        foreach ($products as $product) {
            $preview = $this->generateProductSchemaPreview($product['id_product'], $idLang);

            if (isset($preview['validation'])) {
                $results['products_checked']++;

                if (!$preview['validation']['valid']) {
                    $results['products_with_errors']++;
                    $results['products_needing_attention'][] = array(
                        'id' => $product['id_product'],
                        'name' => $product['name'],
                        'errors' => $preview['validation']['errors'],
                    );
                } elseif (count($preview['validation']['warnings']) > 0) {
                    $results['products_with_warnings']++;
                }

                // Count common issues
                foreach ($preview['validation']['errors'] as $error) {
                    if (!isset($issueCounts[$error])) {
                        $issueCounts[$error] = 0;
                    }
                    $issueCounts[$error]++;
                }

                foreach ($preview['validation']['warnings'] as $warning) {
                    if (!isset($issueCounts[$warning])) {
                        $issueCounts[$warning] = 0;
                    }
                    $issueCounts[$warning]++;
                }
            }
        }

        // Sort issues by frequency
        arsort($issueCounts);
        $results['common_issues'] = array_slice($issueCounts, 0, 10, true);

        return $results;
    }
}
