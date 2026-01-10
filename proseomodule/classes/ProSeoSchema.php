<?php
/**
 * Pro SEO Module - Classe per generazione Schema Markup JSON-LD
 *
 * Genera schema conformi a schema.org e Google Search Console
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSeoSchema
{
    /** @var Context */
    protected $context;

    /** @var Module */
    protected $module;

    /** @var int Cache TTL in seconds */
    protected $cacheTtl = 3600;

    /**
     * Costruttore
     *
     * @param Context $context
     * @param Module $module
     */
    public function __construct(Context $context, Module $module)
    {
        $this->context = $context;
        $this->module = $module;
    }

    /**
     * Genera schema JSON-LD wrapper
     *
     * @param array $schema
     * @return string
     */
    protected function wrapSchema($schema)
    {
        if (empty($schema)) {
            return '';
        }

        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return "\n" . '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
    }

    /**
     * Genera schema Organization
     *
     * @return string
     */
    public function getOrganizationSchema()
    {
        $isLocalBusiness = (bool) Configuration::get('PROSEO_ENABLE_LOCAL_BUSINESS');
        $type = $isLocalBusiness ? 'LocalBusiness' : 'Organization';

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $this->getOrganizationName(),
            'url' => $this->module->getShopUrl(),
        );

        // Logo
        $logo = Configuration::get('PROSEO_ORGANIZATION_LOGO');
        if (!empty($logo)) {
            $schema['logo'] = array(
                '@type' => 'ImageObject',
                'url' => $logo,
            );
        }

        // Contatti
        $phone = Configuration::get('PROSEO_ORGANIZATION_PHONE');
        $email = Configuration::get('PROSEO_ORGANIZATION_EMAIL');

        if (!empty($phone) || !empty($email)) {
            $schema['contactPoint'] = array(
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
            );

            if (!empty($phone)) {
                $schema['contactPoint']['telephone'] = $phone;
            }
            if (!empty($email)) {
                $schema['contactPoint']['email'] = $email;
            }
        }

        // Indirizzo
        $address = $this->buildAddress();
        if (!empty($address)) {
            $schema['address'] = $address;
        }

        // Social profiles
        $socialProfiles = $this->getSocialProfiles();
        if (!empty($socialProfiles)) {
            $schema['sameAs'] = $socialProfiles;
        }

        return $this->wrapSchema($schema);
    }

    /**
     * Genera schema WebSite con SearchAction
     *
     * @return string
     */
    public function getWebSiteSchema()
    {
        $shopUrl = $this->module->getShopUrl();

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => Configuration::get('PS_SHOP_NAME'),
            'url' => $shopUrl,
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $shopUrl . 'ricerca?s={search_term_string}',
                ),
                'query-input' => 'required name=search_term_string',
            ),
        );

        return $this->wrapSchema($schema);
    }

    /**
     * Genera schema Product completo
     *
     * @return string
     */
    public function getProductSchema()
    {
        $idProduct = (int) Tools::getValue('id_product');
        if (!$idProduct) {
            return '';
        }

        // Check cache
        $cacheKey = 'product_schema_' . $idProduct . '_' . $this->context->language->id . '_' . $this->context->shop->id;
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $product = new Product($idProduct, true, $this->context->language->id, $this->context->shop->id);

        if (!Validate::isLoadedObject($product) || !$product->active) {
            return '';
        }

        $schema = $this->buildProductSchema($product);
        $output = $this->wrapSchema($schema);

        // Save to cache
        $this->saveToCache($cacheKey, $output);

        return $output;
    }

    /**
     * Costruisce lo schema Product
     *
     * @param Product $product
     * @return array
     */
    protected function buildProductSchema(Product $product)
    {
        $idProduct = (int) $product->id;
        $idLang = (int) $this->context->language->id;
        $link = $this->context->link;

        // Schema base
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->cleanText($product->name),
            'description' => $this->cleanText($product->description_short, 5000),
            'url' => $link->getProductLink($product),
        );

        // SKU
        if (!empty($product->reference)) {
            $schema['sku'] = $product->reference;
        }

        // Product ID
        $schema['productID'] = $idProduct;

        // Immagini
        $images = $this->getProductImages($product);
        if (!empty($images)) {
            $schema['image'] = count($images) === 1 ? $images[0] : $images;
        }

        // Brand / Manufacturer
        if (Configuration::get('PROSEO_ENABLE_BRAND') && $product->id_manufacturer) {
            $manufacturer = new Manufacturer($product->id_manufacturer, $idLang);
            if (Validate::isLoadedObject($manufacturer)) {
                $schema['brand'] = array(
                    '@type' => 'Brand',
                    'name' => $manufacturer->name,
                );
            }
        }

        // GTIN / EAN
        if (Configuration::get('PROSEO_ENABLE_GTIN') && !empty($product->ean13)) {
            $gtin = $product->ean13;
            $gtinLength = strlen($gtin);

            if ($gtinLength === 13) {
                $schema['gtin13'] = $gtin;
            } elseif ($gtinLength === 12) {
                $schema['gtin12'] = $gtin;
            } elseif ($gtinLength === 14) {
                $schema['gtin14'] = $gtin;
            } elseif ($gtinLength === 8) {
                $schema['gtin8'] = $gtin;
            } else {
                $schema['gtin'] = $gtin;
            }
        }

        // MPN
        if (Configuration::get('PROSEO_ENABLE_MPN') && !empty($product->supplier_reference)) {
            $schema['mpn'] = $product->supplier_reference;
        } elseif (Configuration::get('PROSEO_ENABLE_MPN') && !empty($product->reference)) {
            $schema['mpn'] = $product->reference;
        }

        // Condizione prodotto
        $schema['itemCondition'] = $this->getItemCondition($product);

        // Categoria
        if ($product->id_category_default) {
            $category = new Category($product->id_category_default, $idLang);
            if (Validate::isLoadedObject($category)) {
                $schema['category'] = $category->name;
            }
        }

        // Peso
        if ($product->weight > 0) {
            $schema['weight'] = array(
                '@type' => 'QuantitativeValue',
                'value' => (float) $product->weight,
                'unitCode' => 'KGM',
            );
        }

        // Dimensioni
        if ($product->depth > 0 || $product->height > 0 || $product->width > 0) {
            if ($product->depth > 0) {
                $schema['depth'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => (float) $product->depth,
                    'unitCode' => 'CMT',
                );
            }
            if ($product->height > 0) {
                $schema['height'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => (float) $product->height,
                    'unitCode' => 'CMT',
                );
            }
            if ($product->width > 0) {
                $schema['width'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => (float) $product->width,
                    'unitCode' => 'CMT',
                );
            }
        }

        // Color se disponibile
        $color = $this->getProductColor($product);
        if (!empty($color)) {
            $schema['color'] = $color;
        }

        // Material se disponibile
        $material = $this->getProductMaterial($product);
        if (!empty($material)) {
            $schema['material'] = $material;
        }

        // Offers
        $schema['offers'] = $this->buildOfferSchema($product);

        // Aggregate Rating
        if (Configuration::get('PROSEO_ENABLE_AGGREGATE_RATING')) {
            $rating = $this->getAggregateRating($product);
            if (!empty($rating)) {
                $schema['aggregateRating'] = $rating;
            }
        }

        // Reviews
        $reviews = $this->getProductReviews($product);
        if (!empty($reviews)) {
            $schema['review'] = $reviews;
        }

        return $schema;
    }

    /**
     * Costruisce lo schema Offer
     *
     * @param Product $product
     * @return array
     */
    protected function buildOfferSchema(Product $product)
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        // Prezzo con tasse
        $priceWithTax = Product::getPriceStatic(
            $product->id,
            true,
            null,
            2,
            null,
            false,
            true,
            1,
            false,
            null,
            null,
            null,
            $specificPrice
        );

        // Prezzo originale (se in sconto)
        $priceWithoutReduction = Product::getPriceStatic(
            $product->id,
            true,
            null,
            2,
            null,
            false,
            false,
            1,
            false
        );

        $currency = $this->context->currency;
        $currencyCode = $currency->iso_code;

        // Disponibilità
        $availability = $this->getAvailability($product);

        // Data validità prezzo
        $priceValidUntil = date('Y-m-d', strtotime('+' . (int) Configuration::get('PROSEO_PRICE_VALID_UNTIL_DAYS') . ' days'));

        $offer = array(
            '@type' => 'Offer',
            'url' => $this->context->link->getProductLink($product),
            'priceCurrency' => $currencyCode,
            'price' => number_format($priceWithTax, 2, '.', ''),
            'priceValidUntil' => $priceValidUntil,
            'availability' => $availability,
            'itemCondition' => $this->getItemCondition($product),
        );

        // Seller
        $offer['seller'] = array(
            '@type' => 'Organization',
            'name' => $this->getOrganizationName(),
        );

        // Shipping details (opzionale ma raccomandato)
        $shippingDetails = $this->getShippingDetails();
        if (!empty($shippingDetails)) {
            $offer['shippingDetails'] = $shippingDetails;
        }

        // Merchant return policy (opzionale ma raccomandato)
        $returnPolicy = $this->getReturnPolicy();
        if (!empty($returnPolicy)) {
            $offer['hasMerchantReturnPolicy'] = $returnPolicy;
        }

        return $offer;
    }

    /**
     * Ottiene le immagini del prodotto
     *
     * @param Product $product
     * @return array
     */
    protected function getProductImages(Product $product)
    {
        $images = array();
        $productImages = Image::getImages($this->context->language->id, $product->id);

        if (!empty($productImages)) {
            $link = $this->context->link;
            foreach ($productImages as $image) {
                $imageUrl = $link->getImageLink(
                    $product->link_rewrite,
                    $image['id_image'],
                    ImageType::getFormattedName('large')
                );

                // Assicurati che l'URL sia completo
                if (strpos($imageUrl, 'http') !== 0) {
                    $imageUrl = 'https://' . $imageUrl;
                }

                $images[] = $imageUrl;
            }
        }

        return $images;
    }

    /**
     * Ottiene la disponibilità del prodotto
     *
     * @param Product $product
     * @return string
     */
    protected function getAvailability(Product $product)
    {
        $stockAvailable = StockAvailable::getQuantityAvailableByProduct($product->id);

        if ($stockAvailable > 0) {
            return 'https://schema.org/InStock';
        } elseif ($product->out_of_stock == 1) {
            // Accetta ordini anche se non disponibile
            return 'https://schema.org/BackOrder';
        } elseif ($product->out_of_stock == 2) {
            // Usa comportamento globale
            if (Configuration::get('PS_ORDER_OUT_OF_STOCK')) {
                return 'https://schema.org/BackOrder';
            }
        }

        // Verifica se il prodotto è stato dismesso
        if (!$product->active) {
            return 'https://schema.org/Discontinued';
        }

        return 'https://schema.org/OutOfStock';
    }

    /**
     * Ottiene la condizione del prodotto
     *
     * @param Product $product
     * @return string
     */
    protected function getItemCondition(Product $product)
    {
        $conditionMap = array(
            'new' => 'https://schema.org/NewCondition',
            'used' => 'https://schema.org/UsedCondition',
            'refurbished' => 'https://schema.org/RefurbishedCondition',
        );

        if (isset($product->condition) && isset($conditionMap[$product->condition])) {
            return $conditionMap[$product->condition];
        }

        // Default dalla configurazione
        $defaultCondition = Configuration::get('PROSEO_DEFAULT_PRODUCT_CONDITION');
        return 'https://schema.org/' . $defaultCondition;
    }

    /**
     * Ottiene l'aggregate rating del prodotto
     *
     * @param Product $product
     * @return array|null
     */
    protected function getAggregateRating(Product $product)
    {
        // Prova a ottenere le recensioni dal modulo productcomments
        if (Module::isInstalled('productcomments') && Module::isEnabled('productcomments')) {
            $sql = 'SELECT AVG(grade) as average, COUNT(*) as count
                    FROM `' . _DB_PREFIX_ . 'product_comment`
                    WHERE id_product = ' . (int) $product->id . '
                    AND validate = 1';

            $result = Db::getInstance()->getRow($sql);

            if ($result && $result['count'] > 0) {
                return array(
                    '@type' => 'AggregateRating',
                    'ratingValue' => round((float) $result['average'], 1),
                    'reviewCount' => (int) $result['count'],
                    'bestRating' => 5,
                    'worstRating' => 1,
                );
            }
        }

        return null;
    }

    /**
     * Ottiene le recensioni del prodotto
     *
     * @param Product $product
     * @return array
     */
    protected function getProductReviews(Product $product)
    {
        $reviews = array();

        if (Module::isInstalled('productcomments') && Module::isEnabled('productcomments')) {
            $sql = 'SELECT pc.*, c.firstname, c.lastname
                    FROM `' . _DB_PREFIX_ . 'product_comment` pc
                    LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON pc.id_customer = c.id_customer
                    WHERE pc.id_product = ' . (int) $product->id . '
                    AND pc.validate = 1
                    ORDER BY pc.date_add DESC
                    LIMIT 10';

            $results = Db::getInstance()->executeS($sql);

            if (!empty($results)) {
                foreach ($results as $review) {
                    $authorName = 'Cliente';
                    if (!empty($review['customer_name'])) {
                        $authorName = $review['customer_name'];
                    } elseif (!empty($review['firstname'])) {
                        $authorName = $review['firstname'];
                        if (!empty($review['lastname'])) {
                            $authorName .= ' ' . substr($review['lastname'], 0, 1) . '.';
                        }
                    }

                    $reviewSchema = array(
                        '@type' => 'Review',
                        'author' => array(
                            '@type' => 'Person',
                            'name' => $authorName,
                        ),
                        'datePublished' => date('Y-m-d', strtotime($review['date_add'])),
                        'reviewRating' => array(
                            '@type' => 'Rating',
                            'ratingValue' => (int) $review['grade'],
                            'bestRating' => 5,
                            'worstRating' => 1,
                        ),
                    );

                    if (!empty($review['title'])) {
                        $reviewSchema['name'] = $this->cleanText($review['title'], 100);
                    }

                    if (!empty($review['content'])) {
                        $reviewSchema['reviewBody'] = $this->cleanText($review['content'], 1000);
                    }

                    $reviews[] = $reviewSchema;
                }
            }
        }

        return $reviews;
    }

    /**
     * Genera schema Category/CollectionPage
     *
     * @return string
     */
    public function getCategorySchema()
    {
        $idCategory = (int) Tools::getValue('id_category');
        if (!$idCategory) {
            return '';
        }

        $category = new Category($idCategory, $this->context->language->id);

        if (!Validate::isLoadedObject($category) || !$category->active) {
            return '';
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $this->cleanText($category->name),
            'description' => $this->cleanText($category->description, 5000),
            'url' => $this->context->link->getCategoryLink($category),
        );

        // Immagine categoria
        if (!empty($category->id_image)) {
            $imageUrl = $this->context->link->getCatImageLink(
                $category->link_rewrite,
                $category->id_image,
                ImageType::getFormattedName('category')
            );
            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = 'https://' . $imageUrl;
            }
            $schema['image'] = $imageUrl;
        }

        // Numero di prodotti
        $productCount = $category->getProducts($this->context->language->id, 1, 1, null, null, true);
        if ($productCount > 0) {
            $schema['numberOfItems'] = (int) $productCount;
        }

        return $this->wrapSchema($schema);
    }

    /**
     * Costruisce l'indirizzo schema
     *
     * @return array|null
     */
    protected function buildAddress()
    {
        $street = Configuration::get('PROSEO_ORGANIZATION_ADDRESS');
        $city = Configuration::get('PROSEO_ORGANIZATION_CITY');
        $postalCode = Configuration::get('PROSEO_ORGANIZATION_POSTAL_CODE');
        $country = Configuration::get('PROSEO_ORGANIZATION_COUNTRY');

        if (empty($street) && empty($city)) {
            return null;
        }

        $address = array(
            '@type' => 'PostalAddress',
        );

        if (!empty($street)) {
            $address['streetAddress'] = $street;
        }
        if (!empty($city)) {
            $address['addressLocality'] = $city;
        }
        if (!empty($postalCode)) {
            $address['postalCode'] = $postalCode;
        }
        if (!empty($country)) {
            $address['addressCountry'] = $country;
        }

        return $address;
    }

    /**
     * Ottiene i profili social configurati
     *
     * @return array
     */
    protected function getSocialProfiles()
    {
        $profiles = array();
        $socialKeys = array(
            'PROSEO_SOCIAL_FACEBOOK',
            'PROSEO_SOCIAL_TWITTER',
            'PROSEO_SOCIAL_INSTAGRAM',
            'PROSEO_SOCIAL_LINKEDIN',
            'PROSEO_SOCIAL_YOUTUBE',
            'PROSEO_SOCIAL_PINTEREST',
        );

        foreach ($socialKeys as $key) {
            $value = Configuration::get($key);
            if (!empty($value)) {
                $profiles[] = $value;
            }
        }

        return $profiles;
    }

    /**
     * Ottiene il nome dell'organizzazione
     *
     * @return string
     */
    protected function getOrganizationName()
    {
        $name = Configuration::get('PROSEO_ORGANIZATION_NAME');
        if (empty($name)) {
            $name = Configuration::get('PS_SHOP_NAME');
        }
        return $name;
    }

    /**
     * Ottiene il colore del prodotto dagli attributi
     *
     * @param Product $product
     * @return string|null
     */
    protected function getProductColor(Product $product)
    {
        $idLang = (int) $this->context->language->id;

        $sql = 'SELECT al.name
                FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pa.id_product_attribute = pac.id_product_attribute
                JOIN `' . _DB_PREFIX_ . 'attribute` a ON pac.id_attribute = a.id_attribute
                JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON a.id_attribute_group = ag.id_attribute_group
                JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON a.id_attribute = al.id_attribute AND al.id_lang = ' . $idLang . '
                JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = ' . $idLang . '
                WHERE pa.id_product = ' . (int) $product->id . '
                AND (LOWER(agl.name) LIKE \'%color%\' OR LOWER(agl.name) LIKE \'%colore%\' OR LOWER(agl.public_name) LIKE \'%color%\')
                LIMIT 1';

        $result = Db::getInstance()->getValue($sql);

        return $result ? $result : null;
    }

    /**
     * Ottiene il materiale del prodotto dagli attributi
     *
     * @param Product $product
     * @return string|null
     */
    protected function getProductMaterial(Product $product)
    {
        $idLang = (int) $this->context->language->id;

        $sql = 'SELECT al.name
                FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pa.id_product_attribute = pac.id_product_attribute
                JOIN `' . _DB_PREFIX_ . 'attribute` a ON pac.id_attribute = a.id_attribute
                JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON a.id_attribute_group = ag.id_attribute_group
                JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON a.id_attribute = al.id_attribute AND al.id_lang = ' . $idLang . '
                JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = ' . $idLang . '
                WHERE pa.id_product = ' . (int) $product->id . '
                AND (LOWER(agl.name) LIKE \'%material%\' OR LOWER(agl.name) LIKE \'%materiale%\')
                LIMIT 1';

        $result = Db::getInstance()->getValue($sql);

        return $result ? $result : null;
    }

    /**
     * Ottiene i dettagli di spedizione
     *
     * @return array|null
     */
    protected function getShippingDetails()
    {
        // Ottiene il primo carrier attivo per avere un riferimento
        $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, Carrier::ALL_CARRIERS);

        if (empty($carriers)) {
            return null;
        }

        $carrier = reset($carriers);
        $country = Configuration::get('PROSEO_ORGANIZATION_COUNTRY');
        if (empty($country)) {
            $country = 'IT';
        }

        return array(
            '@type' => 'OfferShippingDetails',
            'shippingDestination' => array(
                '@type' => 'DefinedRegion',
                'addressCountry' => $country,
            ),
            'deliveryTime' => array(
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => array(
                    '@type' => 'QuantitativeValue',
                    'minValue' => 0,
                    'maxValue' => 2,
                    'unitCode' => 'DAY',
                ),
                'transitTime' => array(
                    '@type' => 'QuantitativeValue',
                    'minValue' => 1,
                    'maxValue' => 5,
                    'unitCode' => 'DAY',
                ),
            ),
        );
    }

    /**
     * Ottiene la policy di reso
     *
     * @return array|null
     */
    protected function getReturnPolicy()
    {
        return array(
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => Configuration::get('PROSEO_ORGANIZATION_COUNTRY') ?: 'IT',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => 14,
            'returnMethod' => 'https://schema.org/ReturnByMail',
            'returnFees' => 'https://schema.org/FreeReturn',
        );
    }

    /**
     * Pulisce il testo per lo schema
     *
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    protected function cleanText($text, $maxLength = 255)
    {
        if (empty($text)) {
            return '';
        }

        // Rimuovi HTML
        $text = strip_tags($text);

        // Decodifica entità HTML
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Rimuovi spazi multipli
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        // Tronca se necessario
        if (Tools::strlen($text) > $maxLength) {
            $text = Tools::substr($text, 0, $maxLength - 3) . '...';
        }

        return $text;
    }

    /**
     * Ottiene dati dalla cache
     *
     * @param string $cacheKey
     * @return string|false
     */
    protected function getFromCache($cacheKey)
    {
        $sql = 'SELECT schema_data FROM `' . _DB_PREFIX_ . 'proseo_schema_cache`
                WHERE cache_key = \'' . pSQL($cacheKey) . '\'
                AND date_expiry > NOW()';

        $result = Db::getInstance()->getValue($sql);

        return $result !== false ? $result : false;
    }

    /**
     * Salva dati nella cache
     *
     * @param string $cacheKey
     * @param string $data
     * @return bool
     */
    protected function saveToCache($cacheKey, $data)
    {
        // Prima elimina eventuali vecchie entry
        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'proseo_schema_cache` WHERE cache_key = \'' . pSQL($cacheKey) . '\''
        );

        $expiry = date('Y-m-d H:i:s', time() + $this->cacheTtl);

        return Db::getInstance()->insert('proseo_schema_cache', array(
            'cache_key' => pSQL($cacheKey),
            'schema_data' => pSQL($data, true),
            'date_add' => date('Y-m-d H:i:s'),
            'date_expiry' => $expiry,
        ));
    }
}
