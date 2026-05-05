<?php
/**
 * ProSEOMaster - Advanced Schema Markup Generator
 *
 * Generates advanced schema types:
 * - CollectionPage for categories
 * - FAQPage from product features
 * - VideoObject for product videos
 * - Merchant listing structured data
 * - OfferCatalog for product sets
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterSchemaAdvanced
{
    /** @var Context */
    protected $context;

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Generate CollectionPage schema for category pages
     * @param Category $category
     * @param array $products
     * @param int $totalProducts
     * @param int $currentPage
     * @return array
     */
    public function generateCollectionPageSchema($category, $products, $totalProducts, $currentPage = 1)
    {
        $link = $this->context->link;
        $categoryUrl = $link->getCategoryLink($category->id);

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => $categoryUrl . '#collectionpage',
            'name' => $category->name,
            'url' => $categoryUrl,
            'numberOfItems' => (int) $totalProducts,
        );

        // Description
        if (!empty($category->description)) {
            $schema['description'] = $this->cleanText($category->description);
        }

        // Category image
        $imageUrl = $link->getCatImageLink($category->link_rewrite, $category->id);
        if ($imageUrl) {
            $schema['image'] = $imageUrl;
        }

        // Breadcrumb reference
        $schema['breadcrumb'] = array(
            '@id' => $categoryUrl . '#breadcrumb',
        );

        // Main entity - list of products
        if (!empty($products)) {
            $schema['mainEntity'] = array(
                '@type' => 'ItemList',
                'numberOfItems' => count($products),
                'itemListElement' => array(),
            );

            $position = ($currentPage - 1) * count($products) + 1;
            foreach ($products as $product) {
                $schema['mainEntity']['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'url' => $product['url'] ?? $link->getProductLink($product['id_product']),
                    'name' => $product['name'],
                );
                $position++;

                // Limit to avoid huge schemas
                if ($position > 50) {
                    break;
                }
            }
        }

        // Pagination info
        if ($currentPage > 1) {
            $schema['pagination'] = array(
                '@type' => 'Pagination',
                'pageStart' => $currentPage,
            );
        }

        return $schema;
    }

    /**
     * Generate FAQPage schema from product features/attributes
     * @param Product $product
     * @param array $features
     * @return array|null
     */
    public function generateProductFaqSchema($product, $features = null)
    {
        if ($features === null) {
            $features = $product->getFrontFeatures($this->context->language->id);
        }

        if (empty($features)) {
            return null;
        }

        $faqs = array();

        // Generate FAQ from features
        foreach ($features as $feature) {
            if (empty($feature['name']) || empty($feature['value'])) {
                continue;
            }

            // Skip if value is just a number or too short
            if (strlen($feature['value']) < 3 || is_numeric($feature['value'])) {
                continue;
            }

            $faqs[] = array(
                '@type' => 'Question',
                'name' => sprintf('Qual è %s di %s?', $this->formatFeatureName($feature['name']), $product->name),
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => sprintf('%s: %s', $feature['name'], $feature['value']),
                ),
            );
        }

        // Add common e-commerce questions
        $faqs[] = array(
            '@type' => 'Question',
            'name' => sprintf('Il prodotto %s è disponibile?', $product->name),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => Product::getQuantity($product->id) > 0
                    ? 'Sì, il prodotto è disponibile e pronto per la spedizione.'
                    : 'Al momento il prodotto non è disponibile. Contattaci per conoscere le tempistiche di riassortimento.',
            ),
        );

        $faqs[] = array(
            '@type' => 'Question',
            'name' => sprintf('Quanto costa %s?', $product->name),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => sprintf('Il prezzo di %s è %s IVA inclusa.',
                    $product->name,
                    Tools::displayPrice($product->getPrice(true))
                ),
            ),
        );

        if (count($faqs) < 2) {
            return null;
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs,
        );
    }

    /**
     * Generate VideoObject schema for product video
     * @param Product $product
     * @param array $videoData
     * @return array
     */
    public function generateVideoSchema($product, $videoData)
    {
        if (empty($videoData['url'])) {
            return null;
        }

        $link = $this->context->link;

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $videoData['title'] ?? $product->name . ' - Video',
            'description' => $videoData['description'] ?? strip_tags($product->description_short),
            'contentUrl' => $videoData['url'],
        );

        // Thumbnail
        if (!empty($videoData['thumbnail'])) {
            $schema['thumbnailUrl'] = $videoData['thumbnail'];
        } else {
            // Use product cover as thumbnail
            $images = $product->getImages($this->context->language->id);
            if (!empty($images[0])) {
                $schema['thumbnailUrl'] = $link->getImageLink(
                    $product->link_rewrite,
                    $product->id . '-' . $images[0]['id_image'],
                    ImageType::getFormattedName('large')
                );
            }
        }

        // Duration (ISO 8601 format)
        if (!empty($videoData['duration'])) {
            $schema['duration'] = $videoData['duration'];
        }

        // Upload date
        $schema['uploadDate'] = $videoData['uploadDate'] ?? date('Y-m-d', strtotime($product->date_add));

        // Embed URL for YouTube/Vimeo
        if (!empty($videoData['embedUrl'])) {
            $schema['embedUrl'] = $videoData['embedUrl'];
        } elseif (strpos($videoData['url'], 'youtube.com') !== false || strpos($videoData['url'], 'youtu.be') !== false) {
            $schema['embedUrl'] = $this->getYouTubeEmbedUrl($videoData['url']);
        }

        // View count
        if (!empty($videoData['views'])) {
            $schema['interactionStatistic'] = array(
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/WatchAction',
                'userInteractionCount' => (int) $videoData['views'],
            );
        }

        return $schema;
    }

    /**
     * Generate Merchant Listing structured data (for Google Merchant Center)
     * @param Product $product
     * @return array
     */
    public function generateMerchantListingSchema($product)
    {
        $link = $this->context->link;
        $currency = $this->context->currency->iso_code;

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'url' => $link->getProductLink($product),
            'sku' => $product->reference,
        );

        // Google-specific identifiers
        if (!empty($product->ean13)) {
            $schema['gtin13'] = $product->ean13;
        }
        if (!empty($product->upc)) {
            $schema['gtin12'] = $product->upc;
        }
        if (!empty($product->isbn)) {
            $schema['isbn'] = $product->isbn;
        }

        // MPN
        if (!empty($product->reference)) {
            $schema['mpn'] = $product->reference;
        }

        // Brand - required for Google
        if ($product->id_manufacturer) {
            $manufacturer = new Manufacturer($product->id_manufacturer);
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $manufacturer->name,
            );
        } else {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => Configuration::get('PS_SHOP_NAME'),
            );
        }

        // Description
        $schema['description'] = $this->cleanText($product->description_short);

        // Images - multiple images for Google
        $images = $product->getImages($this->context->language->id);
        $imageUrls = array();
        foreach ($images as $img) {
            $imageUrls[] = $link->getImageLink(
                $product->link_rewrite,
                $product->id . '-' . $img['id_image'],
                ImageType::getFormattedName('large')
            );
        }
        $schema['image'] = $imageUrls;

        // Offers with merchant-specific data
        $quantity = Product::getQuantity($product->id);
        $priceWithTax = $product->getPrice(true, null, 2);

        $schema['offers'] = array(
            '@type' => 'Offer',
            'url' => $link->getProductLink($product),
            'priceCurrency' => $currency,
            'price' => number_format($priceWithTax, 2, '.', ''),
            'availability' => $quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => $this->mapCondition($product->condition),
            'priceValidUntil' => date('Y-m-d', strtotime('+365 days')),
            'seller' => array(
                '@type' => 'Organization',
                'name' => Configuration::get('PS_SHOP_NAME'),
            ),
        );

        // Shipping (Google requires this for some categories)
        $schema['offers']['shippingDetails'] = array(
            '@type' => 'OfferShippingDetails',
            'shippingDestination' => array(
                '@type' => 'DefinedRegion',
                'addressCountry' => Country::getIsoById((int) Configuration::get('PS_COUNTRY_DEFAULT')),
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

        // Return policy (Google Shopping requirement)
        $schema['offers']['hasMerchantReturnPolicy'] = array(
            '@type' => 'MerchantReturnPolicy',
            'returnPolicyCountry' => Country::getIsoById((int) Configuration::get('PS_COUNTRY_DEFAULT')),
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => 14,
            'returnMethod' => 'https://schema.org/ReturnByMail',
            'returnFees' => 'https://schema.org/FreeReturn',
        );

        // Category
        if ($product->id_category_default) {
            $category = new Category($product->id_category_default, $this->context->language->id);
            if (Validate::isLoadedObject($category)) {
                $schema['category'] = $category->name;
            }
        }

        // Weight
        if ($product->weight > 0) {
            $schema['weight'] = array(
                '@type' => 'QuantitativeValue',
                'value' => (float) $product->weight,
                'unitCode' => Configuration::get('PS_WEIGHT_UNIT') === 'kg' ? 'KGM' : 'LBR',
            );
        }

        // Dimensions
        if ($product->width > 0 || $product->height > 0 || $product->depth > 0) {
            $dimensionUnit = Configuration::get('PS_DIMENSION_UNIT') === 'cm' ? 'CMT' : 'INH';

            if ($product->width > 0) {
                $schema['width'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => (float) $product->width,
                    'unitCode' => $dimensionUnit,
                );
            }
            if ($product->height > 0) {
                $schema['height'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => (float) $product->height,
                    'unitCode' => $dimensionUnit,
                );
            }
            if ($product->depth > 0) {
                $schema['depth'] = array(
                    '@type' => 'QuantitativeValue',
                    'value' => (float) $product->depth,
                    'unitCode' => $dimensionUnit,
                );
            }
        }

        // Color and Size from attributes
        $attributes = $product->getAttributeCombinations($this->context->language->id);
        if (!empty($attributes)) {
            $colors = array();
            $sizes = array();

            foreach ($attributes as $attr) {
                $groupName = strtolower($attr['group_name']);
                if (strpos($groupName, 'color') !== false || strpos($groupName, 'colore') !== false) {
                    $colors[] = $attr['attribute_name'];
                }
                if (strpos($groupName, 'size') !== false || strpos($groupName, 'taglia') !== false || strpos($groupName, 'misura') !== false) {
                    $sizes[] = $attr['attribute_name'];
                }
            }

            if (!empty($colors)) {
                $schema['color'] = array_unique($colors);
            }
            if (!empty($sizes)) {
                $schema['size'] = array_unique($sizes);
            }
        }

        return $schema;
    }

    /**
     * Generate OfferCatalog for product bundles or sets
     * @param array $products
     * @param string $catalogName
     * @return array
     */
    public function generateOfferCatalogSchema($products, $catalogName)
    {
        $link = $this->context->link;
        $currency = $this->context->currency->iso_code;

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'OfferCatalog',
            'name' => $catalogName,
            'itemListElement' => array(),
        );

        foreach ($products as $product) {
            if (is_array($product)) {
                $productObj = new Product($product['id_product'], false, $this->context->language->id);
            } else {
                $productObj = $product;
            }

            if (!Validate::isLoadedObject($productObj)) {
                continue;
            }

            $schema['itemListElement'][] = array(
                '@type' => 'Offer',
                'itemOffered' => array(
                    '@type' => 'Product',
                    'name' => $productObj->name,
                    'url' => $link->getProductLink($productObj),
                ),
                'price' => number_format($productObj->getPrice(true), 2, '.', ''),
                'priceCurrency' => $currency,
            );
        }

        return $schema;
    }

    /**
     * Generate SiteNavigationElement for menu
     * @param array $categories
     * @return array
     */
    public function generateSiteNavigationSchema($categories)
    {
        $link = $this->context->link;
        $baseUrl = $link->getPageLink('index', true);

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'SiteNavigationElement',
            '@id' => $baseUrl . '#navigation',
            'name' => 'Main Navigation',
            'hasPart' => array(),
        );

        foreach ($categories as $category) {
            $schema['hasPart'][] = array(
                '@type' => 'WebPage',
                'name' => $category['name'],
                'url' => $link->getCategoryLink($category['id_category']),
            );
        }

        return $schema;
    }

    /**
     * Format feature name for FAQ
     * @param string $name
     * @return string
     */
    protected function formatFeatureName($name)
    {
        $name = strtolower($name);

        // Articles
        $articles = array(
            'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'uno', 'una',
            'the', 'a', 'an',
        );

        foreach ($articles as $article) {
            if (strpos($name, $article . ' ') === 0) {
                return $name;
            }
        }

        // Add article if not present
        if (preg_match('/^[aeiou]/i', $name)) {
            return "l'" . $name;
        }

        return 'il ' . $name;
    }

    /**
     * Map product condition to Schema.org
     * @param string $condition
     * @return string
     */
    protected function mapCondition($condition)
    {
        $map = array(
            'new' => 'https://schema.org/NewCondition',
            'used' => 'https://schema.org/UsedCondition',
            'refurbished' => 'https://schema.org/RefurbishedCondition',
        );

        return isset($map[$condition]) ? $map[$condition] : 'https://schema.org/NewCondition';
    }

    /**
     * Get YouTube embed URL from video URL
     * @param string $url
     * @return string|null
     */
    protected function getYouTubeEmbedUrl($url)
    {
        $videoId = null;

        // Standard YouTube URL
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $videoId = $matches[1];
        }
        // Short YouTube URL
        elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $videoId = $matches[1];
        }

        if ($videoId) {
            return 'https://www.youtube.com/embed/' . $videoId;
        }

        return null;
    }

    /**
     * Clean text for schema
     * @param string $text
     * @return string
     */
    protected function cleanText($text)
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > 5000) {
            $text = substr($text, 0, 4997) . '...';
        }

        return $text;
    }
}
