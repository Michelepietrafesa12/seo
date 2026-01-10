<?php
/**
 * ProSEOMaster - Advanced Sitemap Generator
 *
 * Generates optimized XML sitemaps with:
 * - Dynamic priority based on product performance
 * - Image sitemap integration
 * - Video sitemap support
 * - Proper changefreq based on update patterns
 * - Automatic sitemap index for large catalogs
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterSitemap
{
    /** @var Context */
    protected $context;

    /** @var int Maximum URLs per sitemap file */
    protected $maxUrlsPerSitemap = 45000;

    /** @var string Sitemap directory */
    protected $sitemapDir;

    /** @var array Generated sitemap files */
    protected $sitemapFiles = array();

    public function __construct()
    {
        $this->context = Context::getContext();
        $this->sitemapDir = _PS_ROOT_DIR_ . '/';
    }

    /**
     * Generate complete sitemap
     * @return array List of generated sitemap files
     */
    public function generateSitemap()
    {
        $this->sitemapFiles = array();

        // Generate individual sitemaps
        $this->generateProductSitemap();
        $this->generateCategorySitemap();
        $this->generateCmsSitemap();
        $this->generateManufacturerSitemap();
        $this->generateSupplierSitemap();
        $this->generatePagesSitemap();

        // Generate sitemap index
        $this->generateSitemapIndex();

        return $this->sitemapFiles;
    }

    /**
     * Generate product sitemap with images
     */
    protected function generateProductSitemap()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $link = $this->context->link;

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            $products = $this->getActiveProducts($idLang);
            $chunks = array_chunk($products, $this->maxUrlsPerSitemap);

            foreach ($chunks as $index => $productChunk) {
                $xml = $this->initSitemapXml(true); // true = include image namespace

                foreach ($productChunk as $product) {
                    $url = $link->getProductLink(
                        (int) $product['id_product'],
                        $product['link_rewrite'],
                        null,
                        null,
                        $idLang
                    );

                    $priority = $this->calculateProductPriority($product);
                    $changefreq = $this->calculateChangefreq($product['date_upd']);

                    $urlNode = $xml->addChild('url');
                    $urlNode->addChild('loc', htmlspecialchars($url));
                    $urlNode->addChild('lastmod', date('Y-m-d', strtotime($product['date_upd'])));
                    $urlNode->addChild('changefreq', $changefreq);
                    $urlNode->addChild('priority', $priority);

                    // Add product images
                    $images = $this->getProductImages((int) $product['id_product'], $idLang);
                    foreach ($images as $image) {
                        $imageNode = $urlNode->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                        $imageNode->addChild('image:loc', htmlspecialchars($image['url']), 'http://www.google.com/schemas/sitemap-image/1.1');
                        if (!empty($image['legend'])) {
                            $imageNode->addChild('image:title', htmlspecialchars($image['legend']), 'http://www.google.com/schemas/sitemap-image/1.1');
                        }
                    }
                }

                $filename = 'sitemap_products_' . $langIso . ($index > 0 ? '_' . ($index + 1) : '') . '.xml';
                $this->saveSitemap($xml, $filename);
            }
        }
    }

    /**
     * Generate category sitemap
     */
    protected function generateCategorySitemap()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $link = $this->context->link;

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            $categories = $this->getActiveCategories($idLang);
            $xml = $this->initSitemapXml(true);

            foreach ($categories as $category) {
                if ((int) $category['id_category'] <= 2) {
                    continue; // Skip root and home categories
                }

                $url = $link->getCategoryLink(
                    (int) $category['id_category'],
                    $category['link_rewrite'],
                    $idLang
                );

                $priority = $this->calculateCategoryPriority($category);

                $urlNode = $xml->addChild('url');
                $urlNode->addChild('loc', htmlspecialchars($url));
                $urlNode->addChild('lastmod', date('Y-m-d', strtotime($category['date_upd'])));
                $urlNode->addChild('changefreq', 'weekly');
                $urlNode->addChild('priority', $priority);

                // Add category image if exists
                if (!empty($category['id_category'])) {
                    $imageUrl = $link->getCatImageLink($category['link_rewrite'], $category['id_category']);
                    if ($imageUrl) {
                        $imageNode = $urlNode->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                        $imageNode->addChild('image:loc', htmlspecialchars($imageUrl), 'http://www.google.com/schemas/sitemap-image/1.1');
                    }
                }
            }

            $filename = 'sitemap_categories_' . $langIso . '.xml';
            $this->saveSitemap($xml, $filename);
        }
    }

    /**
     * Generate CMS pages sitemap
     */
    protected function generateCmsSitemap()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $link = $this->context->link;

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            $cmsPages = CMS::listCms($idLang, false, true);
            if (empty($cmsPages)) {
                continue;
            }

            $xml = $this->initSitemapXml();

            foreach ($cmsPages as $cms) {
                $url = $link->getCMSLink((int) $cms['id_cms'], $cms['link_rewrite'], null, $idLang);

                $urlNode = $xml->addChild('url');
                $urlNode->addChild('loc', htmlspecialchars($url));
                $urlNode->addChild('changefreq', 'monthly');
                $urlNode->addChild('priority', '0.5');
            }

            $filename = 'sitemap_cms_' . $langIso . '.xml';
            $this->saveSitemap($xml, $filename);
        }
    }

    /**
     * Generate manufacturer sitemap
     */
    protected function generateManufacturerSitemap()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $link = $this->context->link;

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            $manufacturers = Manufacturer::getManufacturers(false, $idLang, true);
            if (empty($manufacturers)) {
                continue;
            }

            $xml = $this->initSitemapXml();

            foreach ($manufacturers as $manufacturer) {
                $url = $link->getManufacturerLink((int) $manufacturer['id_manufacturer'], null, $idLang);

                $urlNode = $xml->addChild('url');
                $urlNode->addChild('loc', htmlspecialchars($url));
                $urlNode->addChild('changefreq', 'weekly');
                $urlNode->addChild('priority', '0.6');
            }

            $filename = 'sitemap_manufacturers_' . $langIso . '.xml';
            $this->saveSitemap($xml, $filename);
        }
    }

    /**
     * Generate supplier sitemap
     */
    protected function generateSupplierSitemap()
    {
        if (!Configuration::get('PS_DISPLAY_SUPPLIERS')) {
            return;
        }

        $languages = Language::getLanguages(true, $this->context->shop->id);
        $link = $this->context->link;

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            $suppliers = Supplier::getSuppliers(false, $idLang, true);
            if (empty($suppliers)) {
                continue;
            }

            $xml = $this->initSitemapXml();

            foreach ($suppliers as $supplier) {
                $url = $link->getSupplierLink((int) $supplier['id_supplier'], null, $idLang);

                $urlNode = $xml->addChild('url');
                $urlNode->addChild('loc', htmlspecialchars($url));
                $urlNode->addChild('changefreq', 'weekly');
                $urlNode->addChild('priority', '0.5');
            }

            $filename = 'sitemap_suppliers_' . $langIso . '.xml';
            $this->saveSitemap($xml, $filename);
        }
    }

    /**
     * Generate static pages sitemap
     */
    protected function generatePagesSitemap()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $link = $this->context->link;

        $staticPages = array(
            'index' => array('priority' => '1.0', 'changefreq' => 'daily'),
            'contact' => array('priority' => '0.5', 'changefreq' => 'monthly'),
            'sitemap' => array('priority' => '0.3', 'changefreq' => 'weekly'),
            'stores' => array('priority' => '0.5', 'changefreq' => 'monthly'),
            'new-products' => array('priority' => '0.7', 'changefreq' => 'daily'),
            'best-sales' => array('priority' => '0.7', 'changefreq' => 'daily'),
            'prices-drop' => array('priority' => '0.7', 'changefreq' => 'daily'),
        );

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            $xml = $this->initSitemapXml();

            foreach ($staticPages as $page => $settings) {
                $url = $link->getPageLink($page, true, $idLang);

                $urlNode = $xml->addChild('url');
                $urlNode->addChild('loc', htmlspecialchars($url));
                $urlNode->addChild('changefreq', $settings['changefreq']);
                $urlNode->addChild('priority', $settings['priority']);
            }

            $filename = 'sitemap_pages_' . $langIso . '.xml';
            $this->saveSitemap($xml, $filename);
        }
    }

    /**
     * Generate sitemap index file
     */
    protected function generateSitemapIndex()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');

        $baseUrl = $this->context->link->getBaseLink();

        foreach ($this->sitemapFiles as $file) {
            $sitemap = $xml->addChild('sitemap');
            $sitemap->addChild('loc', $baseUrl . $file);
            $sitemap->addChild('lastmod', date('Y-m-d'));
        }

        $this->saveSitemap($xml, 'sitemap.xml', false);
    }

    /**
     * Initialize sitemap XML structure
     * @param bool $includeImage
     * @return SimpleXMLElement
     */
    protected function initSitemapXml($includeImage = false)
    {
        $namespaces = 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        if ($includeImage) {
            $namespaces .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }

        return new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset ' . $namespaces . '></urlset>');
    }

    /**
     * Save sitemap to file
     * @param SimpleXMLElement $xml
     * @param string $filename
     * @param bool $addToIndex
     */
    protected function saveSitemap($xml, $filename, $addToIndex = true)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        $filepath = $this->sitemapDir . $filename;
        $dom->save($filepath);

        if ($addToIndex) {
            $this->sitemapFiles[] = $filename;
        }
    }

    /**
     * Get active products
     * @param int $idLang
     * @return array
     */
    protected function getActiveProducts($idLang)
    {
        $sql = new DbQuery();
        $sql->select('p.id_product, pl.link_rewrite, p.date_upd, p.date_add, ps.price,
                      (SELECT SUM(od.product_quantity) FROM ' . _DB_PREFIX_ . 'order_detail od
                       INNER JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
                       WHERE od.product_id = p.id_product AND o.valid = 1
                       AND o.date_add > DATE_SUB(NOW(), INTERVAL 30 DAY)) as sales_30d');
        $sql->from('product', 'p');
        $sql->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang);
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int) $this->context->shop->id);
        $sql->where('ps.active = 1');
        $sql->where('ps.visibility IN ("both", "catalog", "search")');
        $sql->orderBy('p.date_upd DESC');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get active categories
     * @param int $idLang
     * @return array
     */
    protected function getActiveCategories($idLang)
    {
        $sql = new DbQuery();
        $sql->select('c.id_category, cl.link_rewrite, c.date_upd, c.level_depth, c.nleft, c.nright');
        $sql->from('category', 'c');
        $sql->innerJoin('category_lang', 'cl', 'c.id_category = cl.id_category AND cl.id_lang = ' . (int) $idLang);
        $sql->innerJoin('category_shop', 'cs', 'c.id_category = cs.id_category AND cs.id_shop = ' . (int) $this->context->shop->id);
        $sql->where('c.active = 1');
        $sql->orderBy('c.level_depth ASC, c.nleft ASC');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get product images
     * @param int $idProduct
     * @param int $idLang
     * @return array
     */
    protected function getProductImages($idProduct, $idLang)
    {
        $images = array();
        $product = new Product($idProduct, false, $idLang);
        $productImages = $product->getImages($idLang);

        foreach ($productImages as $img) {
            $imageUrl = $this->context->link->getImageLink(
                $product->link_rewrite,
                $idProduct . '-' . $img['id_image'],
                ImageType::getFormattedName('large')
            );

            // Ensure HTTPS
            if (Configuration::get('PS_SSL_ENABLED')) {
                $imageUrl = str_replace('http://', 'https://', $imageUrl);
            }

            $images[] = array(
                'url' => $imageUrl,
                'legend' => $img['legend'],
            );
        }

        return $images;
    }

    /**
     * Calculate product priority based on performance metrics
     * @param array $product
     * @return string
     */
    protected function calculateProductPriority($product)
    {
        $priority = 0.5; // Base priority

        // Boost for recent products
        $daysSinceCreation = (time() - strtotime($product['date_add'])) / 86400;
        if ($daysSinceCreation < 30) {
            $priority += 0.2;
        } elseif ($daysSinceCreation < 90) {
            $priority += 0.1;
        }

        // Boost for recent updates
        $daysSinceUpdate = (time() - strtotime($product['date_upd'])) / 86400;
        if ($daysSinceUpdate < 7) {
            $priority += 0.1;
        }

        // Boost for best sellers
        if (isset($product['sales_30d']) && $product['sales_30d'] > 0) {
            if ($product['sales_30d'] > 50) {
                $priority += 0.2;
            } elseif ($product['sales_30d'] > 10) {
                $priority += 0.1;
            } else {
                $priority += 0.05;
            }
        }

        // Cap at 1.0
        return number_format(min(1.0, $priority), 1);
    }

    /**
     * Calculate category priority based on depth
     * @param array $category
     * @return string
     */
    protected function calculateCategoryPriority($category)
    {
        $depth = (int) $category['level_depth'];

        // Higher priority for top-level categories
        switch ($depth) {
            case 2:
                return '0.9';
            case 3:
                return '0.8';
            case 4:
                return '0.7';
            default:
                return '0.6';
        }
    }

    /**
     * Calculate changefreq based on update pattern
     * @param string $lastUpdate
     * @return string
     */
    protected function calculateChangefreq($lastUpdate)
    {
        $daysSinceUpdate = (time() - strtotime($lastUpdate)) / 86400;

        if ($daysSinceUpdate < 1) {
            return 'hourly';
        } elseif ($daysSinceUpdate < 7) {
            return 'daily';
        } elseif ($daysSinceUpdate < 30) {
            return 'weekly';
        } elseif ($daysSinceUpdate < 180) {
            return 'monthly';
        } else {
            return 'yearly';
        }
    }

    /**
     * Submit sitemap to search engines
     * @return array Results
     */
    public function submitToSearchEngines()
    {
        $sitemapUrl = $this->context->link->getBaseLink() . 'sitemap.xml';
        $results = array();

        // Google
        $googleUrl = 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);
        $results['google'] = $this->pingUrl($googleUrl);

        // Bing
        $bingUrl = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl);
        $results['bing'] = $this->pingUrl($bingUrl);

        return $results;
    }

    /**
     * Ping URL
     * @param string $url
     * @return bool
     */
    protected function pingUrl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }
}
