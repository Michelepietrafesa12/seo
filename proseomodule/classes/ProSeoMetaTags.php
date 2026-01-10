<?php
/**
 * Pro SEO Module - Classe per gestione Meta Tags
 *
 * Gestisce Open Graph, Twitter Cards, Canonical, Hreflang
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSeoMetaTags
{
    /** @var Context */
    protected $context;

    /** @var Module */
    protected $module;

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
     * Genera tutti i meta tags
     *
     * @return string
     */
    public function generateMetaTags()
    {
        $output = '';

        // Open Graph
        if (Configuration::get('PROSEO_ENABLE_META_OG')) {
            $output .= $this->generateOpenGraphTags();
        }

        // Twitter Cards
        if (Configuration::get('PROSEO_ENABLE_META_TWITTER')) {
            $output .= $this->generateTwitterCards();
        }

        // Canonical
        if (Configuration::get('PROSEO_ENABLE_CANONICAL')) {
            $output .= $this->generateCanonical();
        }

        // Robots meta tag personalizzato
        $output .= $this->generateRobotsMeta();

        return $output;
    }

    /**
     * Genera i meta tags Open Graph
     *
     * @return string
     */
    protected function generateOpenGraphTags()
    {
        $output = "\n<!-- Pro SEO - Open Graph Tags -->\n";

        $pageData = $this->getPageData();

        // Tipo OG
        $output .= '<meta property="og:type" content="' . $this->escapeAttr($pageData['og_type']) . '" />' . "\n";

        // Titolo
        $output .= '<meta property="og:title" content="' . $this->escapeAttr($pageData['title']) . '" />' . "\n";

        // Descrizione
        if (!empty($pageData['description'])) {
            $output .= '<meta property="og:description" content="' . $this->escapeAttr($pageData['description']) . '" />' . "\n";
        }

        // URL
        $output .= '<meta property="og:url" content="' . $this->escapeAttr($pageData['url']) . '" />' . "\n";

        // Immagine
        if (!empty($pageData['image'])) {
            $output .= '<meta property="og:image" content="' . $this->escapeAttr($pageData['image']) . '" />' . "\n";
            $output .= '<meta property="og:image:alt" content="' . $this->escapeAttr($pageData['title']) . '" />' . "\n";

            // Dimensioni immagine se disponibili
            if (!empty($pageData['image_width'])) {
                $output .= '<meta property="og:image:width" content="' . (int) $pageData['image_width'] . '" />' . "\n";
            }
            if (!empty($pageData['image_height'])) {
                $output .= '<meta property="og:image:height" content="' . (int) $pageData['image_height'] . '" />' . "\n";
            }
        }

        // Site name
        $output .= '<meta property="og:site_name" content="' . $this->escapeAttr(Configuration::get('PS_SHOP_NAME')) . '" />' . "\n";

        // Locale
        $locale = $this->getLocale();
        $output .= '<meta property="og:locale" content="' . $this->escapeAttr($locale) . '" />' . "\n";

        // Dati specifici per prodotti
        if ($pageData['og_type'] === 'product' && isset($pageData['product'])) {
            $product = $pageData['product'];

            // Prezzo
            $price = Product::getPriceStatic($product->id, true, null, 2);
            $output .= '<meta property="product:price:amount" content="' . number_format($price, 2, '.', '') . '" />' . "\n";
            $output .= '<meta property="product:price:currency" content="' . $this->escapeAttr($this->context->currency->iso_code) . '" />' . "\n";

            // Disponibilità
            $stock = StockAvailable::getQuantityAvailableByProduct($product->id);
            $availability = $stock > 0 ? 'instock' : 'oos';
            $output .= '<meta property="product:availability" content="' . $availability . '" />' . "\n";

            // Condizione
            $output .= '<meta property="product:condition" content="' . $this->escapeAttr($product->condition) . '" />' . "\n";

            // Brand
            if ($product->id_manufacturer) {
                $manufacturer = new Manufacturer($product->id_manufacturer);
                if (Validate::isLoadedObject($manufacturer)) {
                    $output .= '<meta property="product:brand" content="' . $this->escapeAttr($manufacturer->name) . '" />' . "\n";
                }
            }

            // Categoria
            if ($product->id_category_default) {
                $category = new Category($product->id_category_default, $this->context->language->id);
                if (Validate::isLoadedObject($category)) {
                    $output .= '<meta property="product:category" content="' . $this->escapeAttr($category->name) . '" />' . "\n";
                }
            }

            // EAN
            if (!empty($product->ean13)) {
                $output .= '<meta property="product:ean" content="' . $this->escapeAttr($product->ean13) . '" />' . "\n";
            }

            // SKU
            if (!empty($product->reference)) {
                $output .= '<meta property="product:retailer_item_id" content="' . $this->escapeAttr($product->reference) . '" />' . "\n";
            }
        }

        return $output;
    }

    /**
     * Genera i meta tags Twitter Cards
     *
     * @return string
     */
    protected function generateTwitterCards()
    {
        $output = "\n<!-- Pro SEO - Twitter Cards -->\n";

        $pageData = $this->getPageData();

        // Card type
        $cardType = !empty($pageData['image']) ? 'summary_large_image' : 'summary';
        $output .= '<meta name="twitter:card" content="' . $cardType . '" />' . "\n";

        // Site
        $twitterSite = Configuration::get('PROSEO_TWITTER_SITE');
        if (!empty($twitterSite)) {
            $output .= '<meta name="twitter:site" content="' . $this->escapeAttr($twitterSite) . '" />' . "\n";
        }

        // Titolo
        $output .= '<meta name="twitter:title" content="' . $this->escapeAttr($pageData['title']) . '" />' . "\n";

        // Descrizione
        if (!empty($pageData['description'])) {
            $description = Tools::strlen($pageData['description']) > 200
                ? Tools::substr($pageData['description'], 0, 197) . '...'
                : $pageData['description'];
            $output .= '<meta name="twitter:description" content="' . $this->escapeAttr($description) . '" />' . "\n";
        }

        // Immagine
        if (!empty($pageData['image'])) {
            $output .= '<meta name="twitter:image" content="' . $this->escapeAttr($pageData['image']) . '" />' . "\n";
            $output .= '<meta name="twitter:image:alt" content="' . $this->escapeAttr($pageData['title']) . '" />' . "\n";
        }

        // Dati specifici per prodotti
        if ($pageData['og_type'] === 'product' && isset($pageData['product'])) {
            $product = $pageData['product'];

            // Prezzo per Twitter
            $price = Product::getPriceStatic($product->id, true, null, 2);
            $output .= '<meta name="twitter:label1" content="Prezzo" />' . "\n";
            $output .= '<meta name="twitter:data1" content="' . Tools::displayPrice($price) . '" />' . "\n";

            // Disponibilità
            $stock = StockAvailable::getQuantityAvailableByProduct($product->id);
            $output .= '<meta name="twitter:label2" content="Disponibilità" />' . "\n";
            $output .= '<meta name="twitter:data2" content="' . ($stock > 0 ? 'Disponibile' : 'Non disponibile') . '" />' . "\n";
        }

        return $output;
    }

    /**
     * Genera il tag canonical
     *
     * @return string
     */
    protected function generateCanonical()
    {
        $canonicalUrl = $this->getCanonicalUrl();

        if (empty($canonicalUrl)) {
            return '';
        }

        return "\n" . '<link rel="canonical" href="' . $this->escapeAttr($canonicalUrl) . '" />' . "\n";
    }

    /**
     * Genera i tag hreflang per siti multilingua
     *
     * @return string
     */
    public function generateHreflang()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);

        if (count($languages) <= 1) {
            return '';
        }

        $output = "\n<!-- Pro SEO - Hreflang Tags -->\n";
        $controller = $this->context->controller;
        $controllerName = get_class($controller);
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');

        foreach ($languages as $lang) {
            $langId = (int) $lang['id_lang'];
            $langCode = $lang['language_code']; // es: it-IT, en-US
            $isoCode = $lang['iso_code']; // es: it, en

            // Costruisci URL per questa lingua
            $url = $this->getUrlForLanguage($langId, $controllerName);

            if (!empty($url)) {
                $output .= '<link rel="alternate" hreflang="' . $this->escapeAttr($isoCode) . '" href="' . $this->escapeAttr($url) . '" />' . "\n";

                // x-default per la lingua di default
                if ($langId === $defaultLangId) {
                    $output .= '<link rel="alternate" hreflang="x-default" href="' . $this->escapeAttr($url) . '" />' . "\n";
                }
            }
        }

        return $output;
    }

    /**
     * Genera il meta robots personalizzato
     *
     * @return string
     */
    protected function generateRobotsMeta()
    {
        $controller = $this->context->controller;
        $controllerName = get_class($controller);

        $index = 'index';
        $follow = 'follow';

        // Controlla impostazioni per tipo di pagina
        switch ($controllerName) {
            case 'ProductController':
                if (!Configuration::get('PROSEO_ROBOTS_INDEX_PRODUCTS')) {
                    $index = 'noindex';
                }
                break;

            case 'CategoryController':
                if (!Configuration::get('PROSEO_ROBOTS_INDEX_CATEGORIES')) {
                    $index = 'noindex';
                }
                // Pagine di paginazione
                $page = (int) Tools::getValue('page');
                if ($page > 1) {
                    $index = 'noindex';
                }
                break;

            case 'CmsController':
                if (!Configuration::get('PROSEO_ROBOTS_INDEX_CMS')) {
                    $index = 'noindex';
                }
                break;

            case 'SearchController':
            case 'ContactController':
            case 'PasswordController':
            case 'AuthController':
            case 'MyAccountController':
            case 'OrderController':
            case 'CartController':
                $index = 'noindex';
                break;
        }

        // Controlla se ci sono parametri che indicano contenuto duplicato
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $duplicateParams = array('orderby', 'orderway', 'n', 'p', 'from', 'resultsPerPage');

        foreach ($duplicateParams as $param) {
            if (Tools::getIsset($param) && $param !== 'p') { // Mantieni p per paginazione gestita sopra
                $index = 'noindex';
                break;
            }
        }

        // Se le impostazioni di default sono ok, non serve aggiungere il meta
        if ($index === 'index' && $follow === 'follow') {
            return '';
        }

        return "\n" . '<meta name="robots" content="' . $index . ', ' . $follow . '" />' . "\n";
    }

    /**
     * Ottiene i dati della pagina corrente
     *
     * @return array
     */
    protected function getPageData()
    {
        $controller = $this->context->controller;
        $controllerName = get_class($controller);
        $link = $this->context->link;
        $idLang = (int) $this->context->language->id;

        $data = array(
            'title' => '',
            'description' => '',
            'image' => '',
            'url' => '',
            'og_type' => 'website',
            'product' => null,
        );

        switch ($controllerName) {
            case 'IndexController':
                $data['title'] = Configuration::get('PS_SHOP_NAME');
                $data['description'] = Configuration::get('PS_SHOP_DETAILS') ?: $this->getDefaultDescription();
                $data['url'] = $link->getPageLink('index');
                $data['image'] = $this->getStoreLogo();
                break;

            case 'ProductController':
                $idProduct = (int) Tools::getValue('id_product');
                if ($idProduct) {
                    $product = new Product($idProduct, true, $idLang);
                    if (Validate::isLoadedObject($product)) {
                        $data['product'] = $product;
                        $data['title'] = $product->name . ' - ' . Configuration::get('PS_SHOP_NAME');
                        $data['description'] = $this->cleanDescription($product->description_short);
                        $data['url'] = $link->getProductLink($product);
                        $data['image'] = $this->getProductImage($product);
                        $data['og_type'] = 'product';
                    }
                }
                break;

            case 'CategoryController':
                $idCategory = (int) Tools::getValue('id_category');
                if ($idCategory) {
                    $category = new Category($idCategory, $idLang);
                    if (Validate::isLoadedObject($category)) {
                        $data['title'] = $category->name . ' - ' . Configuration::get('PS_SHOP_NAME');
                        $data['description'] = $this->cleanDescription($category->description);
                        $data['url'] = $link->getCategoryLink($category);
                        $data['image'] = $this->getCategoryImage($category);
                        $data['og_type'] = 'website';
                    }
                }
                break;

            case 'CmsController':
                $idCms = (int) Tools::getValue('id_cms');
                if ($idCms) {
                    $cms = new CMS($idCms, $idLang);
                    if (Validate::isLoadedObject($cms)) {
                        $data['title'] = $cms->meta_title ?: $cms->head_seo_title;
                        $data['description'] = $cms->meta_description;
                        $data['url'] = $link->getCMSLink($cms);
                        $data['og_type'] = 'article';
                    }
                }
                break;

            case 'ManufacturerController':
                $idManufacturer = (int) Tools::getValue('id_manufacturer');
                if ($idManufacturer) {
                    $manufacturer = new Manufacturer($idManufacturer, $idLang);
                    if (Validate::isLoadedObject($manufacturer)) {
                        $data['title'] = $manufacturer->name . ' - ' . Configuration::get('PS_SHOP_NAME');
                        $data['description'] = $this->cleanDescription($manufacturer->short_description ?: $manufacturer->description);
                        $data['url'] = $link->getManufacturerLink($manufacturer);
                        $data['og_type'] = 'website';
                    }
                }
                break;

            default:
                // Pagina generica
                $data['title'] = $this->context->controller->getLayout() !== 'layout-full-width'
                    ? Configuration::get('PS_SHOP_NAME')
                    : $this->context->smarty->getTemplateVars('page')['meta']['title'] ?? Configuration::get('PS_SHOP_NAME');
                $data['url'] = $this->getCurrentUrl();
                $data['image'] = $this->getStoreLogo();
                break;
        }

        // Fallback per titolo vuoto
        if (empty($data['title'])) {
            $data['title'] = Configuration::get('PS_SHOP_NAME');
        }

        // Fallback per descrizione vuota
        if (empty($data['description'])) {
            $data['description'] = $this->getDefaultDescription();
        }

        // Fallback per immagine vuota
        if (empty($data['image'])) {
            $data['image'] = $this->getStoreLogo();
        }

        return $data;
    }

    /**
     * Ottiene l'URL canonical della pagina corrente
     *
     * @return string
     */
    protected function getCanonicalUrl()
    {
        $controller = $this->context->controller;
        $controllerName = get_class($controller);
        $link = $this->context->link;
        $idLang = (int) $this->context->language->id;

        switch ($controllerName) {
            case 'IndexController':
                return $link->getPageLink('index');

            case 'ProductController':
                $idProduct = (int) Tools::getValue('id_product');
                if ($idProduct) {
                    $product = new Product($idProduct, false, $idLang);
                    if (Validate::isLoadedObject($product)) {
                        return $link->getProductLink($product);
                    }
                }
                break;

            case 'CategoryController':
                $idCategory = (int) Tools::getValue('id_category');
                if ($idCategory) {
                    $category = new Category($idCategory, $idLang);
                    if (Validate::isLoadedObject($category)) {
                        // Per categorie con paginazione, usa la prima pagina come canonical
                        return $link->getCategoryLink($category);
                    }
                }
                break;

            case 'CmsController':
                $idCms = (int) Tools::getValue('id_cms');
                if ($idCms) {
                    $cms = new CMS($idCms, $idLang);
                    if (Validate::isLoadedObject($cms)) {
                        return $link->getCMSLink($cms);
                    }
                }
                break;

            case 'ManufacturerController':
                $idManufacturer = (int) Tools::getValue('id_manufacturer');
                if ($idManufacturer) {
                    $manufacturer = new Manufacturer($idManufacturer, $idLang);
                    if (Validate::isLoadedObject($manufacturer)) {
                        return $link->getManufacturerLink($manufacturer);
                    }
                }
                break;
        }

        // Per altre pagine, usa l'URL corrente senza parametri extra
        return $this->getCurrentCleanUrl();
    }

    /**
     * Ottiene l'URL per una specifica lingua
     *
     * @param int $langId
     * @param string $controllerName
     * @return string
     */
    protected function getUrlForLanguage($langId, $controllerName)
    {
        $link = $this->context->link;

        switch ($controllerName) {
            case 'IndexController':
                return $link->getPageLink('index', null, $langId);

            case 'ProductController':
                $idProduct = (int) Tools::getValue('id_product');
                if ($idProduct) {
                    $product = new Product($idProduct, false, $langId);
                    if (Validate::isLoadedObject($product)) {
                        return $link->getProductLink($product, null, null, null, $langId);
                    }
                }
                break;

            case 'CategoryController':
                $idCategory = (int) Tools::getValue('id_category');
                if ($idCategory) {
                    $category = new Category($idCategory, $langId);
                    if (Validate::isLoadedObject($category)) {
                        return $link->getCategoryLink($category, null, $langId);
                    }
                }
                break;

            case 'CmsController':
                $idCms = (int) Tools::getValue('id_cms');
                if ($idCms) {
                    $cms = new CMS($idCms, $langId);
                    if (Validate::isLoadedObject($cms)) {
                        return $link->getCMSLink($cms, null, null, $langId);
                    }
                }
                break;

            case 'ManufacturerController':
                $idManufacturer = (int) Tools::getValue('id_manufacturer');
                if ($idManufacturer) {
                    $manufacturer = new Manufacturer($idManufacturer, $langId);
                    if (Validate::isLoadedObject($manufacturer)) {
                        return $link->getManufacturerLink($manufacturer, null, $langId);
                    }
                }
                break;
        }

        return '';
    }

    /**
     * Ottiene l'immagine principale del prodotto
     *
     * @param Product $product
     * @return string
     */
    protected function getProductImage(Product $product)
    {
        $cover = Image::getCover($product->id);

        if (!empty($cover)) {
            $imageUrl = $this->context->link->getImageLink(
                $product->link_rewrite,
                $cover['id_image'],
                ImageType::getFormattedName('large')
            );

            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = 'https://' . $imageUrl;
            }

            return $imageUrl;
        }

        return $this->getStoreLogo();
    }

    /**
     * Ottiene l'immagine della categoria
     *
     * @param Category $category
     * @return string
     */
    protected function getCategoryImage(Category $category)
    {
        if (!empty($category->id_image)) {
            $imageUrl = $this->context->link->getCatImageLink(
                $category->link_rewrite,
                $category->id_image,
                ImageType::getFormattedName('category')
            );

            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = 'https://' . $imageUrl;
            }

            return $imageUrl;
        }

        return $this->getStoreLogo();
    }

    /**
     * Ottiene il logo del negozio
     *
     * @return string
     */
    protected function getStoreLogo()
    {
        $logoConfig = Configuration::get('PROSEO_ORGANIZATION_LOGO');
        if (!empty($logoConfig)) {
            return $logoConfig;
        }

        $logo = Configuration::get('PS_LOGO');
        if (!empty($logo)) {
            return $this->module->getShopUrl() . 'img/' . $logo;
        }

        return '';
    }

    /**
     * Ottiene la descrizione di default del negozio
     *
     * @return string
     */
    protected function getDefaultDescription()
    {
        $desc = Configuration::get('PS_SHOP_DETAILS');
        if (empty($desc)) {
            $desc = 'Acquista online su ' . Configuration::get('PS_SHOP_NAME') . '. Spedizione veloce e pagamenti sicuri.';
        }
        return $this->cleanDescription($desc);
    }

    /**
     * Ottiene l'URL corrente
     *
     * @return string
     */
    protected function getCurrentUrl()
    {
        $ssl = Configuration::get('PS_SSL_ENABLED');
        $protocol = $ssl ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Ottiene l'URL corrente pulito (senza parametri extra)
     *
     * @return string
     */
    protected function getCurrentCleanUrl()
    {
        $ssl = Configuration::get('PS_SSL_ENABLED');
        $protocol = $ssl ? 'https://' : 'http://';
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        return $protocol . $_SERVER['HTTP_HOST'] . $uri;
    }

    /**
     * Ottiene il locale corrente
     *
     * @return string
     */
    protected function getLocale()
    {
        $lang = $this->context->language;
        return $lang->language_code ?: $lang->iso_code . '_' . Tools::strtoupper($lang->iso_code);
    }

    /**
     * Pulisce la descrizione
     *
     * @param string $description
     * @param int $maxLength
     * @return string
     */
    protected function cleanDescription($description, $maxLength = 160)
    {
        if (empty($description)) {
            return '';
        }

        // Rimuovi HTML
        $description = strip_tags($description);

        // Decodifica entità HTML
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');

        // Rimuovi spazi multipli
        $description = preg_replace('/\s+/', ' ', $description);

        // Trim
        $description = trim($description);

        // Tronca se necessario
        if (Tools::strlen($description) > $maxLength) {
            $description = Tools::substr($description, 0, $maxLength - 3) . '...';
        }

        return $description;
    }

    /**
     * Escape per attributi HTML
     *
     * @param string $string
     * @return string
     */
    protected function escapeAttr($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
