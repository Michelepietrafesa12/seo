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
 * @author      Michele Pietrafesa
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
        $this->generateVideoSitemap();

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

                    // Add product images (pass link_rewrite to avoid extra SQL query)
                    $images = $this->getProductImages((int) $product['id_product'], $idLang, $product['link_rewrite']);
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

            // Get CMS pages with date for lastmod (PrestaShop CMS doesn't have date_upd, use date_add)
            $sql = new DbQuery();
            $sql->select('c.id_cms, cl.link_rewrite, c.date_add');
            $sql->from('cms', 'c');
            $sql->innerJoin('cms_lang', 'cl', 'c.id_cms = cl.id_cms AND cl.id_lang = ' . (int) $idLang);
            $sql->innerJoin('cms_shop', 'cs', 'c.id_cms = cs.id_cms AND cs.id_shop = ' . (int) $this->context->shop->id);
            $sql->where('c.active = 1');

            $cmsPages = Db::getInstance()->executeS($sql);
            if (empty($cmsPages)) {
                continue;
            }

            $xml = $this->initSitemapXml();

            foreach ($cmsPages as $cms) {
                $url = $link->getCMSLink((int) $cms['id_cms'], $cms['link_rewrite'], null, $idLang);

                $urlNode = $xml->addChild('url');
                $urlNode->addChild('loc', htmlspecialchars($url));
                // Use date_add as lastmod (CMS pages rarely change after creation)
                $urlNode->addChild('lastmod', date('Y-m-d', strtotime($cms['date_add'])));
                $urlNode->addChild('changefreq', 'monthly');
                $urlNode->addChild('priority', '0.5');
            }

            $filename = 'sitemap_cms_' . $langIso . '.xml';
            $this->saveSitemap($xml, $filename);
        }
    }

    /**
     * Generate manufacturer sitemap
     * Priority varies based on whether manufacturer has description content
     */
    protected function generateManufacturerSitemap()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $link = $this->context->link;

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            // Get manufacturers with description to determine priority
            $sql = new DbQuery();
            $sql->select('m.id_manufacturer, m.date_upd, ml.description, ml.short_description');
            $sql->from('manufacturer', 'm');
            $sql->innerJoin('manufacturer_lang', 'ml', 'm.id_manufacturer = ml.id_manufacturer AND ml.id_lang = ' . (int) $idLang);
            $sql->innerJoin('manufacturer_shop', 'ms', 'm.id_manufacturer = ms.id_manufacturer AND ms.id_shop = ' . (int) $this->context->shop->id);
            $sql->where('m.active = 1');

            $manufacturers = Db::getInstance()->executeS($sql);
            if (empty($manufacturers)) {
                continue;
            }

            $xml = $this->initSitemapXml();

            foreach ($manufacturers as $manufacturer) {
                $url = $link->getManufacturerLink((int) $manufacturer['id_manufacturer'], null, $idLang);

                // Higher priority for manufacturers with meaningful description (100+ chars)
                $hasContent = !empty($manufacturer['description']) && strlen(strip_tags($manufacturer['description'])) > 100;
                $hasShortDesc = !empty($manufacturer['short_description']) && strlen(strip_tags($manufacturer['short_description'])) > 50;
                $priority = ($hasContent || $hasShortDesc) ? '0.7' : '0.5';

                $urlNode = $xml->addChild('url');
                $urlNode->addChild('loc', htmlspecialchars($url));
                $urlNode->addChild('lastmod', date('Y-m-d', strtotime($manufacturer['date_upd'])));
                $urlNode->addChild('changefreq', 'weekly');
                $urlNode->addChild('priority', $priority);
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

        // Static pages with lastmod calculation
        // Daily pages: today's date (content changes daily)
        // Weekly/monthly pages: fixed date
        $today = date('Y-m-d');

        $staticPages = array(
            'index' => array('priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => $today),
            'contact' => array('priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-01')),
            'sitemap' => array('priority' => '0.3', 'changefreq' => 'weekly', 'lastmod' => $today),
            'stores' => array('priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-01')),
            'new-products' => array('priority' => '0.7', 'changefreq' => 'daily', 'lastmod' => $today),
            'best-sales' => array('priority' => '0.7', 'changefreq' => 'daily', 'lastmod' => $today),
            'prices-drop' => array('priority' => '0.7', 'changefreq' => 'daily', 'lastmod' => $today),
        );

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            $xml = $this->initSitemapXml();

            foreach ($staticPages as $page => $settings) {
                $url = $link->getPageLink($page, true, $idLang);

                $urlNode = $xml->addChild('url');
                $urlNode->addChild('loc', htmlspecialchars($url));
                $urlNode->addChild('lastmod', $settings['lastmod']);
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
     * @param bool $includeVideo
     * @return SimpleXMLElement
     */
    protected function initSitemapXml($includeImage = false, $includeVideo = false)
    {
        $namespaces = 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        if ($includeImage) {
            $namespaces .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }
        if ($includeVideo) {
            $namespaces .= ' xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"';
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
        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());

            $filepath = $this->sitemapDir . $filename;

            // Check directory is writable
            if (!is_writable($this->sitemapDir)) {
                PrestaShopLogger::addLog(
                    'ProSEOMaster: Cannot write sitemap - directory not writable: ' . $this->sitemapDir,
                    3,
                    null,
                    'ProSEOMaster'
                );
                return false;
            }

            $result = $dom->save($filepath);

            if ($result === false) {
                PrestaShopLogger::addLog(
                    'ProSEOMaster: Failed to save sitemap: ' . $filename,
                    3,
                    null,
                    'ProSEOMaster'
                );
                return false;
            }

            if ($addToIndex) {
                $this->sitemapFiles[] = $filename;
            }

            return true;
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'ProSEOMaster: Sitemap error - ' . $e->getMessage(),
                3,
                null,
                'ProSEOMaster'
            );
            return false;
        }
    }

    /**
     * Get active products
     * @param int $idLang
     * @return array
     */
    protected function getActiveProducts($idLang)
    {
        // Optimized query with LEFT JOIN instead of correlated subquery for sales
        // This is much faster on large catalogs (2000+ products)
        $sql = 'SELECT p.id_product, pl.link_rewrite, p.date_upd, p.date_add, ps.price,
                       COALESCE(sales.total_qty, 0) as sales_30d
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl
                    ON p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang . '
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps
                    ON p.id_product = ps.id_product AND ps.id_shop = ' . (int) $this->context->shop->id . '
                LEFT JOIN (
                    SELECT od.product_id, SUM(od.product_quantity) as total_qty
                    FROM ' . _DB_PREFIX_ . 'order_detail od
                    INNER JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
                    WHERE o.valid = 1 AND o.date_add > DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY od.product_id
                ) sales ON p.id_product = sales.product_id
                WHERE ps.active = 1
                AND ps.visibility IN ("both", "catalog")
                ORDER BY p.date_upd DESC';

        return Db::getInstance()->executeS($sql);
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

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get product images using direct SQL query
     * Optimized to avoid loading full Product object for each product
     * @param int $idProduct
     * @param int $idLang
     * @param string $linkRewrite Product link_rewrite (passed from product data)
     * @return array
     */
    protected function getProductImages($idProduct, $idLang, $linkRewrite = '')
    {
        $images = array();

        // Direct SQL query - avoids loading full Product object
        $sql = new DbQuery();
        $sql->select('i.id_image, il.legend');
        $sql->from('image', 'i');
        $sql->innerJoin('image_shop', 'ish', 'i.id_image = ish.id_image AND ish.id_shop = ' . (int) $this->context->shop->id);
        $sql->leftJoin('image_lang', 'il', 'i.id_image = il.id_image AND il.id_lang = ' . (int) $idLang);
        $sql->where('i.id_product = ' . (int) $idProduct);
        $sql->orderBy('i.position ASC');

        $rows = Db::getInstance()->executeS($sql);

        if (empty($rows)) {
            return $images;
        }

        // If link_rewrite not provided, get it
        if (empty($linkRewrite)) {
            $linkRewrite = Db::getInstance()->getValue(
                'SELECT link_rewrite FROM ' . _DB_PREFIX_ . 'product_lang
                 WHERE id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang
            );
        }

        foreach ($rows as $img) {
            $imageUrl = $this->context->link->getImageLink(
                $linkRewrite,
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
     * Generate video sitemap for products with embedded videos
     */
    public function generateVideoSitemap()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $link = $this->context->link;

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $langIso = $lang['iso_code'];

            $productsWithVideos = $this->getProductsWithVideos($idLang);

            if (empty($productsWithVideos)) {
                continue;
            }

            $xml = $this->initSitemapXml(false, true);

            foreach ($productsWithVideos as $product) {
                $url = $link->getProductLink(
                    (int) $product['id_product'],
                    $product['link_rewrite'],
                    null,
                    null,
                    $idLang
                );

                $videos = $this->extractVideosFromContent($product['description']);

                foreach ($videos as $video) {
                    $urlNode = $xml->addChild('url');
                    $urlNode->addChild('loc', htmlspecialchars($url));

                    $videoNode = $urlNode->addChild('video:video', null, 'http://www.google.com/schemas/sitemap-video/1.1');

                    // Required fields
                    $videoNode->addChild('video:thumbnail_loc', htmlspecialchars($video['thumbnail']), 'http://www.google.com/schemas/sitemap-video/1.1');
                    $videoNode->addChild('video:title', htmlspecialchars($product['name']), 'http://www.google.com/schemas/sitemap-video/1.1');
                    $videoNode->addChild('video:description', htmlspecialchars(strip_tags($product['description_short'])), 'http://www.google.com/schemas/sitemap-video/1.1');

                    // Content location (player or raw video)
                    if (!empty($video['content_loc'])) {
                        $videoNode->addChild('video:content_loc', htmlspecialchars($video['content_loc']), 'http://www.google.com/schemas/sitemap-video/1.1');
                    }

                    if (!empty($video['player_loc'])) {
                        $videoNode->addChild('video:player_loc', htmlspecialchars($video['player_loc']), 'http://www.google.com/schemas/sitemap-video/1.1');
                    }

                    // Optional but recommended fields
                    if (!empty($video['duration'])) {
                        $videoNode->addChild('video:duration', (int) $video['duration'], 'http://www.google.com/schemas/sitemap-video/1.1');
                    }

                    $videoNode->addChild('video:publication_date', date('Y-m-d', strtotime($product['date_add'])), 'http://www.google.com/schemas/sitemap-video/1.1');
                    $videoNode->addChild('video:family_friendly', 'yes', 'http://www.google.com/schemas/sitemap-video/1.1');
                    $videoNode->addChild('video:live', 'no', 'http://www.google.com/schemas/sitemap-video/1.1');

                    // Platform availability
                    $platform = $videoNode->addChild('video:platform', 'web mobile tv', 'http://www.google.com/schemas/sitemap-video/1.1');
                    $platform->addAttribute('relationship', 'allow');
                }
            }

            if (count($productsWithVideos) > 0) {
                $filename = 'sitemap_videos_' . $langIso . '.xml';
                $this->saveSitemap($xml, $filename);
            }
        }
    }

    /**
     * Get products that have videos in their descriptions
     * @param int $idLang
     * @return array
     */
    protected function getProductsWithVideos($idLang)
    {
        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name, pl.link_rewrite, pl.description, pl.description_short, p.date_add');
        $sql->from('product', 'p');
        $sql->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang);
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int) $this->context->shop->id);
        $sql->where('ps.active = 1');
        $sql->where('(pl.description LIKE "%youtube%" OR pl.description LIKE "%vimeo%" OR pl.description LIKE "%<video%" OR pl.description LIKE "%dailymotion%" OR pl.description LIKE "%wistia%")');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Extract video information from HTML content
     * @param string $content
     * @return array
     */
    protected function extractVideosFromContent($content)
    {
        $videos = array();

        // YouTube embeds (iframe and old embed)
        preg_match_all('/(?:youtube\.com\/(?:embed\/|watch\?v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $content, $ytMatches);
        if (!empty($ytMatches[1])) {
            foreach (array_unique($ytMatches[1]) as $videoId) {
                $videos[] = array(
                    'platform' => 'youtube',
                    'video_id' => $videoId,
                    'thumbnail' => 'https://img.youtube.com/vi/' . $videoId . '/maxresdefault.jpg',
                    'player_loc' => 'https://www.youtube.com/embed/' . $videoId,
                    'content_loc' => '',
                    'duration' => null,
                );
            }
        }

        // Vimeo embeds
        preg_match_all('/vimeo\.com\/(?:video\/)?(\d+)/', $content, $vimeoMatches);
        if (!empty($vimeoMatches[1])) {
            foreach (array_unique($vimeoMatches[1]) as $videoId) {
                $videos[] = array(
                    'platform' => 'vimeo',
                    'video_id' => $videoId,
                    'thumbnail' => 'https://vumbnail.com/' . $videoId . '.jpg',
                    'player_loc' => 'https://player.vimeo.com/video/' . $videoId,
                    'content_loc' => '',
                    'duration' => null,
                );
            }
        }

        // Dailymotion embeds
        preg_match_all('/dailymotion\.com\/(?:video|embed\/video)\/([a-zA-Z0-9]+)/', $content, $dmMatches);
        if (!empty($dmMatches[1])) {
            foreach (array_unique($dmMatches[1]) as $videoId) {
                $videos[] = array(
                    'platform' => 'dailymotion',
                    'video_id' => $videoId,
                    'thumbnail' => 'https://www.dailymotion.com/thumbnail/video/' . $videoId,
                    'player_loc' => 'https://www.dailymotion.com/embed/video/' . $videoId,
                    'content_loc' => '',
                    'duration' => null,
                );
            }
        }

        // Wistia embeds
        preg_match_all('/wistia\.(?:com|net)\/(?:medias|embed)\/([a-zA-Z0-9]+)/', $content, $wistiaMatches);
        if (!empty($wistiaMatches[1])) {
            foreach (array_unique($wistiaMatches[1]) as $videoId) {
                $videos[] = array(
                    'platform' => 'wistia',
                    'video_id' => $videoId,
                    'thumbnail' => 'https://embed-ssl.wistia.com/deliveries/' . $videoId . '.jpg',
                    'player_loc' => 'https://fast.wistia.net/embed/iframe/' . $videoId,
                    'content_loc' => '',
                    'duration' => null,
                );
            }
        }

        // Self-hosted videos (<video> tags)
        preg_match_all('/<video[^>]*>.*?<source[^>]+src=["\']([^"\']+)["\'][^>]*>.*?<\/video>/is', $content, $html5Matches);
        if (!empty($html5Matches[1])) {
            foreach (array_unique($html5Matches[1]) as $videoUrl) {
                // Try to get poster/thumbnail from video tag
                $thumbnail = '';
                preg_match('/poster=["\']([^"\']+)["\']/', $content, $posterMatch);
                if (!empty($posterMatch[1])) {
                    $thumbnail = $posterMatch[1];
                }

                $videos[] = array(
                    'platform' => 'self-hosted',
                    'video_id' => md5($videoUrl),
                    'thumbnail' => $thumbnail ?: $this->context->link->getBaseLink() . 'img/video-placeholder.jpg',
                    'player_loc' => '',
                    'content_loc' => $videoUrl,
                    'duration' => null,
                );
            }
        }

        return $videos;
    }

    /**
     * Submit sitemap to search engines
     * Note: Google Ping API was deprecated in 2023, use Search Console instead
     * Bing and IndexNow still work
     * @return array Results
     */
    public function submitToSearchEngines()
    {
        $sitemapUrl = $this->context->link->getBaseLink() . 'sitemap.xml';
        $baseUrl = $this->context->link->getBaseLink();
        $results = array();

        // Google: Deprecated in 2023 - use Google Search Console API or submit via robots.txt
        $results['google'] = array(
            'submitted' => false,
            'message' => 'Google Ping API deprecated. Submit via Search Console or robots.txt'
        );

        // Bing (still works)
        $bingUrl = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl);
        $results['bing'] = $this->pingUrl($bingUrl);

        // IndexNow API (supported by Bing, Yandex, Seznam, Naver)
        // Requires an API key file at /indexnow_key.txt
        $indexNowKey = Configuration::get('PROSEOMASTER_INDEXNOW_KEY');
        if (!empty($indexNowKey)) {
            $indexNowUrl = 'https://api.indexnow.org/indexnow?url=' . urlencode($sitemapUrl)
                         . '&key=' . urlencode($indexNowKey);
            $results['indexnow'] = $this->pingUrl($indexNowUrl);
        }

        return $results;
    }

    /**
     * Ping URL
     * @param string $url
     * @return bool
     */
    protected function pingUrl($url)
    {
        try {
            if (!function_exists('curl_init')) {
                return false;
            }

            $ch = curl_init($url);
            if ($ch === false) {
                return false;
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // SSL verification - enable in production with CA bundle
            if (Configuration::get('PS_SSL_ENABLED') && file_exists(_PS_TOOL_DIR_ . 'cacert.pem')) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_CAINFO, _PS_TOOL_DIR_ . 'cacert.pem');
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }

            $result = curl_exec($ch);
            $curlError = curl_errno($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Return false if curl failed or returned error
            if ($result === false || $curlError !== 0) {
                return false;
            }

            return $httpCode >= 200 && $httpCode < 300;
        } catch (Exception $e) {
            // Log error and return false on any exception
            if (class_exists('PrestaShopLogger')) {
                PrestaShopLogger::addLog(
                    'ProSEOMaster sitemap ping error: ' . $e->getMessage(),
                    2,
                    $e->getCode(),
                    'ProSEOMaster'
                );
            }
            return false;
        }
    }
}
