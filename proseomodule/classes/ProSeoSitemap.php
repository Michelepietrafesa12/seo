<?php
/**
 * Pro SEO Module - Classe per generazione Sitemap XML
 *
 * Genera sitemap XML ottimizzata per i motori di ricerca
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSeoSitemap
{
    /** @var Context */
    protected $context;

    /** @var Module */
    protected $module;

    /** @var int Maximum URLs per sitemap */
    protected $maxUrlsPerSitemap = 50000;

    /** @var int Maximum filesize per sitemap (50MB) */
    protected $maxFileSize = 52428800;

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
     * Genera la sitemap completa
     *
     * @return string XML content
     */
    public function generate()
    {
        $urls = array();

        // Homepage
        $urls[] = $this->createUrlEntry(
            $this->context->link->getPageLink('index'),
            date('c'),
            'daily',
            '1.0'
        );

        // Categorie
        $urls = array_merge($urls, $this->getCategoryUrls());

        // Prodotti
        $urls = array_merge($urls, $this->getProductUrls());

        // CMS
        $urls = array_merge($urls, $this->getCmsUrls());

        // Produttori
        $urls = array_merge($urls, $this->getManufacturerUrls());

        return $this->buildXml($urls);
    }

    /**
     * Ottiene gli URL delle categorie
     *
     * @return array
     */
    protected function getCategoryUrls()
    {
        $urls = array();
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $link = $this->context->link;

        $sql = 'SELECT c.id_category, c.date_upd, c.level_depth
                FROM `' . _DB_PREFIX_ . 'category` c
                ' . Shop::addSqlAssociation('category', 'c') . '
                WHERE c.active = 1
                AND c.id_category != ' . (int) Configuration::get('PS_ROOT_CATEGORY') . '
                ORDER BY c.level_depth ASC, c.position ASC';

        $categories = Db::getInstance()->executeS($sql);

        if ($categories) {
            foreach ($categories as $cat) {
                $category = new Category($cat['id_category'], $idLang);

                if (Validate::isLoadedObject($category) && $category->checkAccess($this->context->customer->id)) {
                    // Priorità basata sulla profondità
                    $priority = max(0.4, 0.8 - ($cat['level_depth'] * 0.1));

                    $urls[] = $this->createUrlEntry(
                        $link->getCategoryLink($category),
                        date('c', strtotime($cat['date_upd'])),
                        'weekly',
                        number_format($priority, 1)
                    );
                }
            }
        }

        return $urls;
    }

    /**
     * Ottiene gli URL dei prodotti
     *
     * @return array
     */
    protected function getProductUrls()
    {
        $urls = array();
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $link = $this->context->link;

        $sql = 'SELECT p.id_product, p.date_upd, pl.link_rewrite
                FROM `' . _DB_PREFIX_ . 'product` p
                ' . Shop::addSqlAssociation('product', 'p') . '
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop . ')
                WHERE product_shop.active = 1
                AND product_shop.visibility IN (\'both\', \'search\')
                ORDER BY p.date_upd DESC';

        $products = Db::getInstance()->executeS($sql);

        if ($products) {
            foreach ($products as $prod) {
                $product = new Product($prod['id_product'], false, $idLang);

                if (Validate::isLoadedObject($product)) {
                    $urls[] = $this->createUrlEntry(
                        $link->getProductLink($product),
                        date('c', strtotime($prod['date_upd'])),
                        'weekly',
                        '0.6'
                    );

                    // Aggiungi immagini prodotto (image sitemap)
                    $images = $this->getProductImagesForSitemap($product);
                    if (!empty($images) && isset($urls[count($urls) - 1])) {
                        $urls[count($urls) - 1]['images'] = $images;
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Ottiene le immagini del prodotto per la sitemap
     *
     * @param Product $product
     * @return array
     */
    protected function getProductImagesForSitemap(Product $product)
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

                if (strpos($imageUrl, 'http') !== 0) {
                    $imageUrl = 'https://' . $imageUrl;
                }

                $images[] = array(
                    'loc' => $imageUrl,
                    'title' => $product->name,
                    'caption' => !empty($product->description_short)
                        ? strip_tags(Tools::substr($product->description_short, 0, 200))
                        : $product->name,
                );
            }
        }

        return $images;
    }

    /**
     * Ottiene gli URL delle pagine CMS
     *
     * @return array
     */
    protected function getCmsUrls()
    {
        $urls = array();
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $link = $this->context->link;

        $sql = 'SELECT c.id_cms, cl.link_rewrite
                FROM `' . _DB_PREFIX_ . 'cms` c
                ' . Shop::addSqlAssociation('cms', 'c') . '
                LEFT JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON (c.id_cms = cl.id_cms AND cl.id_lang = ' . $idLang . ' AND cl.id_shop = ' . $idShop . ')
                WHERE cms_shop.active = 1
                AND cms_shop.indexation = 1
                ORDER BY c.position ASC';

        $pages = Db::getInstance()->executeS($sql);

        if ($pages) {
            foreach ($pages as $page) {
                $cms = new CMS($page['id_cms'], $idLang);

                if (Validate::isLoadedObject($cms)) {
                    $urls[] = $this->createUrlEntry(
                        $link->getCMSLink($cms),
                        date('c'),
                        'monthly',
                        '0.5'
                    );
                }
            }
        }

        return $urls;
    }

    /**
     * Ottiene gli URL dei produttori
     *
     * @return array
     */
    protected function getManufacturerUrls()
    {
        $urls = array();
        $idLang = (int) $this->context->language->id;
        $link = $this->context->link;

        $sql = 'SELECT m.id_manufacturer, m.date_upd
                FROM `' . _DB_PREFIX_ . 'manufacturer` m
                ' . Shop::addSqlAssociation('manufacturer', 'm') . '
                WHERE m.active = 1
                ORDER BY m.name ASC';

        $manufacturers = Db::getInstance()->executeS($sql);

        if ($manufacturers) {
            foreach ($manufacturers as $manu) {
                $manufacturer = new Manufacturer($manu['id_manufacturer'], $idLang);

                if (Validate::isLoadedObject($manufacturer)) {
                    $urls[] = $this->createUrlEntry(
                        $link->getManufacturerLink($manufacturer),
                        date('c', strtotime($manu['date_upd'])),
                        'monthly',
                        '0.5'
                    );
                }
            }
        }

        return $urls;
    }

    /**
     * Crea un entry URL per la sitemap
     *
     * @param string $loc
     * @param string $lastmod
     * @param string $changefreq
     * @param string $priority
     * @return array
     */
    protected function createUrlEntry($loc, $lastmod, $changefreq = 'weekly', $priority = '0.5')
    {
        return array(
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        );
    }

    /**
     * Costruisce l'XML della sitemap
     *
     * @param array $urls
     * @return string
     */
    protected function buildXml($urls)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . $this->escapeXml($url['loc']) . "</loc>\n";

            if (!empty($url['lastmod'])) {
                $xml .= "    <lastmod>" . $this->escapeXml($url['lastmod']) . "</lastmod>\n";
            }

            if (!empty($url['changefreq'])) {
                $xml .= "    <changefreq>" . $this->escapeXml($url['changefreq']) . "</changefreq>\n";
            }

            if (!empty($url['priority'])) {
                $xml .= "    <priority>" . $this->escapeXml($url['priority']) . "</priority>\n";
            }

            // Immagini
            if (!empty($url['images'])) {
                foreach ($url['images'] as $image) {
                    $xml .= "    <image:image>\n";
                    $xml .= "      <image:loc>" . $this->escapeXml($image['loc']) . "</image:loc>\n";

                    if (!empty($image['title'])) {
                        $xml .= "      <image:title>" . $this->escapeXml($image['title']) . "</image:title>\n";
                    }

                    if (!empty($image['caption'])) {
                        $xml .= "      <image:caption>" . $this->escapeXml($image['caption']) . "</image:caption>\n";
                    }

                    $xml .= "    </image:image>\n";
                }
            }

            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>";

        return $xml;
    }

    /**
     * Escape per XML
     *
     * @param string $string
     * @return string
     */
    protected function escapeXml($string)
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Salva la sitemap su file
     *
     * @param string $filename
     * @return bool
     */
    public function saveToFile($filename = null)
    {
        if ($filename === null) {
            $filename = _PS_ROOT_DIR_ . '/sitemap_proseo.xml';
        }

        $xml = $this->generate();

        return (bool) file_put_contents($filename, $xml);
    }
}
