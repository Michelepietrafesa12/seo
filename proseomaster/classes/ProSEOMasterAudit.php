<?php
/**
 * ProSEOMaster - SEO Audit Tool
 *
 * Comprehensive SEO audit for:
 * - Missing meta tags
 * - Duplicate content detection
 * - Schema validation
 * - Image optimization check
 * - Internal linking analysis
 * - Product data completeness
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterAudit
{
    /** @var Context */
    protected $context;

    /** @var array Audit results */
    protected $results = array();

    /** @var array Issue counts by severity */
    protected $issueCounts = array(
        'critical' => 0,
        'warning' => 0,
        'notice' => 0,
        'passed' => 0,
    );

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Run complete SEO audit
     * @return array
     */
    public function runFullAudit()
    {
        $this->results = array();
        $this->issueCounts = array('critical' => 0, 'warning' => 0, 'notice' => 0, 'passed' => 0);

        // Product audits
        $this->auditProducts();

        // Category audits
        $this->auditCategories();

        // CMS Pages audit
        $this->auditCmsPages();

        // Technical SEO audit
        $this->auditTechnicalSeo();

        // Image audit
        $this->auditImages();

        // Schema audit
        $this->auditSchemaReadiness();

        return array(
            'results' => $this->results,
            'summary' => $this->issueCounts,
            'score' => $this->calculateSeoScore(),
            'date' => date('Y-m-d H:i:s'),
        );
    }

    /**
     * Audit all products
     */
    protected function auditProducts()
    {
        $idLang = $this->context->language->id;
        $idShop = $this->context->shop->id;

        // Get all active products
        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name, pl.description, pl.description_short, pl.meta_title, pl.meta_description, pl.link_rewrite');
        $sql->select('p.reference, p.ean13, p.upc, p.isbn, p.id_manufacturer, p.condition');
        $sql->from('product', 'p');
        $sql->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang . ' AND pl.id_shop = ' . (int) $idShop);
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int) $idShop);
        $sql->where('ps.active = 1');

        $products = Db::getInstance()->executeS($sql);

        $issues = array();

        foreach ($products as $product) {
            $productIssues = $this->auditSingleProduct($product);
            if (!empty($productIssues)) {
                $issues[] = array(
                    'id' => $product['id_product'],
                    'name' => $product['name'],
                    'issues' => $productIssues,
                );
            }
        }

        // Summary stats
        $this->results['products'] = array(
            'total' => count($products),
            'with_issues' => count($issues),
            'issues' => $issues,
        );

        // Count products missing critical SEO elements
        $missingMeta = 0;
        $missingGtin = 0;
        $missingBrand = 0;
        $shortDescription = 0;

        foreach ($products as $product) {
            if (empty($product['meta_title']) || empty($product['meta_description'])) {
                $missingMeta++;
            }
            if (empty($product['ean13']) && empty($product['upc']) && empty($product['isbn'])) {
                $missingGtin++;
            }
            if (empty($product['id_manufacturer'])) {
                $missingBrand++;
            }
            if (strlen(strip_tags($product['description_short'])) < 50) {
                $shortDescription++;
            }
        }

        // Add critical issues
        if ($missingMeta > 0) {
            $this->addIssue('products', 'warning', sprintf('%d prodotti senza meta title o meta description', $missingMeta), 'Aggiungi meta title e description ottimizzati per migliorare il CTR nei risultati di ricerca');
        }

        if ($missingGtin > 0) {
            $this->addIssue('products', 'warning', sprintf('%d prodotti senza GTIN (EAN/UPC)', $missingGtin), 'Il GTIN è raccomandato da Google per i rich snippets dei prodotti');
        }

        if ($missingBrand > 0) {
            $this->addIssue('products', 'notice', sprintf('%d prodotti senza brand/produttore', $missingBrand), 'Associa un produttore per completare lo schema Product');
        }

        if ($shortDescription > 0) {
            $this->addIssue('products', 'notice', sprintf('%d prodotti con descrizione breve insufficiente', $shortDescription), 'Le descrizioni brevi dovrebbero essere di almeno 50 caratteri');
        }
    }

    /**
     * Audit single product
     * @param array $product
     * @return array
     */
    protected function auditSingleProduct($product)
    {
        $issues = array();

        // Meta title check
        if (empty($product['meta_title'])) {
            $issues[] = array('type' => 'warning', 'message' => 'Meta title mancante');
        } elseif (strlen($product['meta_title']) > 60) {
            $issues[] = array('type' => 'notice', 'message' => 'Meta title troppo lungo (max 60 caratteri)');
        } elseif (strlen($product['meta_title']) < 30) {
            $issues[] = array('type' => 'notice', 'message' => 'Meta title troppo corto (min 30 caratteri)');
        }

        // Meta description check
        if (empty($product['meta_description'])) {
            $issues[] = array('type' => 'warning', 'message' => 'Meta description mancante');
        } elseif (strlen($product['meta_description']) > 160) {
            $issues[] = array('type' => 'notice', 'message' => 'Meta description troppo lunga (max 160 caratteri)');
        } elseif (strlen($product['meta_description']) < 70) {
            $issues[] = array('type' => 'notice', 'message' => 'Meta description troppo corta (min 70 caratteri)');
        }

        // Description check
        if (empty($product['description_short'])) {
            $issues[] = array('type' => 'warning', 'message' => 'Descrizione breve mancante');
        }

        if (empty($product['description'])) {
            $issues[] = array('type' => 'notice', 'message' => 'Descrizione completa mancante');
        }

        // Product identifiers
        if (empty($product['ean13']) && empty($product['upc']) && empty($product['isbn'])) {
            $issues[] = array('type' => 'warning', 'message' => 'Nessun GTIN (EAN/UPC/ISBN) presente');
        }

        if (empty($product['reference'])) {
            $issues[] = array('type' => 'notice', 'message' => 'Riferimento/SKU mancante');
        }

        // Brand
        if (empty($product['id_manufacturer'])) {
            $issues[] = array('type' => 'notice', 'message' => 'Produttore non associato');
        }

        // Images check
        $images = Image::getImages($this->context->language->id, $product['id_product']);
        if (empty($images)) {
            $issues[] = array('type' => 'critical', 'message' => 'Nessuna immagine presente');
        } elseif (count($images) < 3) {
            $issues[] = array('type' => 'notice', 'message' => 'Poche immagini (consigliato: almeno 3)');
        }

        return $issues;
    }

    /**
     * Audit categories
     */
    protected function auditCategories()
    {
        $idLang = $this->context->language->id;
        $idShop = $this->context->shop->id;

        $sql = new DbQuery();
        $sql->select('c.id_category, cl.name, cl.description, cl.meta_title, cl.meta_description, cl.link_rewrite');
        $sql->from('category', 'c');
        $sql->innerJoin('category_lang', 'cl', 'c.id_category = cl.id_category AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . (int) $idShop);
        $sql->innerJoin('category_shop', 'cs', 'c.id_category = cs.id_category AND cs.id_shop = ' . (int) $idShop);
        $sql->where('c.active = 1');
        $sql->where('c.id_category > 2'); // Exclude root and home

        $categories = Db::getInstance()->executeS($sql);

        $missingMeta = 0;
        $missingDescription = 0;
        $issues = array();

        foreach ($categories as $category) {
            $categoryIssues = array();

            if (empty($category['meta_title'])) {
                $missingMeta++;
                $categoryIssues[] = array('type' => 'warning', 'message' => 'Meta title mancante');
            }

            if (empty($category['meta_description'])) {
                $missingMeta++;
                $categoryIssues[] = array('type' => 'warning', 'message' => 'Meta description mancante');
            }

            if (empty($category['description']) || strlen(strip_tags($category['description'])) < 100) {
                $missingDescription++;
                $categoryIssues[] = array('type' => 'notice', 'message' => 'Descrizione categoria insufficiente (min 100 caratteri)');
            }

            if (!empty($categoryIssues)) {
                $issues[] = array(
                    'id' => $category['id_category'],
                    'name' => $category['name'],
                    'issues' => $categoryIssues,
                );
            }
        }

        $this->results['categories'] = array(
            'total' => count($categories),
            'with_issues' => count($issues),
            'issues' => $issues,
        );

        if ($missingMeta > 0) {
            $this->addIssue('categories', 'warning', sprintf('%d categorie senza meta tags completi', $missingMeta), 'I meta tags sono fondamentali per il posizionamento delle pagine categoria');
        }

        if ($missingDescription > 0) {
            $this->addIssue('categories', 'notice', sprintf('%d categorie con descrizione insufficiente', $missingDescription), 'Le descrizioni categoria aiutano Google a comprendere il contenuto');
        }
    }

    /**
     * Audit CMS pages
     */
    protected function auditCmsPages()
    {
        $idLang = $this->context->language->id;
        $idShop = $this->context->shop->id;

        $cmsPages = CMS::listCms($idLang, false, true, $idShop);

        $missingMeta = 0;
        $shortContent = 0;

        foreach ($cmsPages as $cms) {
            $cmsObj = new CMS($cms['id_cms'], $idLang);

            if (empty($cmsObj->meta_title) || empty($cmsObj->meta_description)) {
                $missingMeta++;
            }

            if (strlen(strip_tags($cmsObj->content)) < 300) {
                $shortContent++;
            }
        }

        $this->results['cms'] = array(
            'total' => count($cmsPages),
            'missing_meta' => $missingMeta,
            'short_content' => $shortContent,
        );

        if ($missingMeta > 0) {
            $this->addIssue('cms', 'warning', sprintf('%d pagine CMS senza meta tags', $missingMeta));
        }

        if ($shortContent > 0) {
            $this->addIssue('cms', 'notice', sprintf('%d pagine CMS con contenuto breve (<300 caratteri)', $shortContent));
        }
    }

    /**
     * Audit technical SEO elements
     */
    protected function auditTechnicalSeo()
    {
        $issues = array();

        // Check robots.txt
        $robotsPath = _PS_ROOT_DIR_ . '/robots.txt';
        if (!file_exists($robotsPath)) {
            $this->addIssue('technical', 'critical', 'File robots.txt non trovato', 'Genera il robots.txt dalle impostazioni del modulo');
        } else {
            $robotsContent = file_get_contents($robotsPath);
            if (strpos($robotsContent, 'Sitemap:') === false) {
                $this->addIssue('technical', 'warning', 'Sitemap non referenziata in robots.txt');
            }
            if (strpos($robotsContent, 'Disallow: /') !== false && strpos($robotsContent, 'User-agent: *') !== false) {
                // Check if it's blocking everything
                if (preg_match('/User-agent:\s*\*\s*\n\s*Disallow:\s*\/\s*$/m', $robotsContent)) {
                    $this->addIssue('technical', 'critical', 'robots.txt sta bloccando tutti i crawler!');
                }
            }
        }

        // Check sitemap
        $sitemapPath = _PS_ROOT_DIR_ . '/sitemap.xml';
        if (!file_exists($sitemapPath)) {
            $this->addIssue('technical', 'critical', 'Sitemap XML non trovata', 'Genera la sitemap dalle impostazioni del modulo');
        }

        // Check SSL
        if (!Configuration::get('PS_SSL_ENABLED')) {
            $this->addIssue('technical', 'critical', 'SSL/HTTPS non abilitato', 'HTTPS è un fattore di ranking. Abilita SSL nelle impostazioni del negozio');
        }

        // Check friendly URLs
        if (!Configuration::get('PS_REWRITING_SETTINGS')) {
            $this->addIssue('technical', 'critical', 'URL amichevoli non abilitati', 'Gli URL amichevoli migliorano il SEO e la user experience');
        }

        // Check canonical URLs
        if (!Configuration::get('PS_CANONICAL_REDIRECT')) {
            $this->addIssue('technical', 'warning', 'Redirect canonici non abilitati', 'Abilita i redirect canonici per evitare contenuti duplicati');
        }

        // Check .htaccess
        $htaccessPath = _PS_ROOT_DIR_ . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $this->addIssue('technical', 'warning', 'File .htaccess non trovato');
        } else {
            $htaccessContent = file_get_contents($htaccessPath);

            // Check compression
            if (strpos($htaccessContent, 'mod_deflate') === false && strpos($htaccessContent, 'mod_gzip') === false) {
                $this->addIssue('technical', 'notice', 'Compressione GZIP non configurata in .htaccess');
            }

            // Check browser caching
            if (strpos($htaccessContent, 'mod_expires') === false) {
                $this->addIssue('technical', 'notice', 'Cache del browser non ottimizzata in .htaccess');
            }
        }

        // Check default language
        $defaultLang = new Language(Configuration::get('PS_LANG_DEFAULT'));
        if (!$defaultLang->active) {
            $this->addIssue('technical', 'critical', 'La lingua predefinita non è attiva');
        }

        $this->results['technical'] = $issues;
    }

    /**
     * Audit images
     */
    protected function auditImages()
    {
        $idLang = $this->context->language->id;

        // Check products without images
        $sql = 'SELECT COUNT(DISTINCT p.id_product) as count
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
                LEFT JOIN ' . _DB_PREFIX_ . 'image i ON p.id_product = i.id_product
                WHERE ps.active = 1 AND i.id_image IS NULL';

        $noImages = Db::getInstance()->getValue($sql);

        if ($noImages > 0) {
            $this->addIssue('images', 'critical', sprintf('%d prodotti senza immagini', $noImages));
        }

        // Check images without alt text
        $sql = 'SELECT COUNT(*) as count
                FROM ' . _DB_PREFIX_ . 'image i
                LEFT JOIN ' . _DB_PREFIX_ . 'image_lang il ON i.id_image = il.id_image AND il.id_lang = ' . (int) $idLang . '
                WHERE il.legend IS NULL OR il.legend = ""';

        $noAlt = Db::getInstance()->getValue($sql);

        if ($noAlt > 0) {
            $this->addIssue('images', 'warning', sprintf('%d immagini senza testo alternativo (ALT)', $noAlt), 'Il testo ALT è importante per l\'accessibilità e il SEO delle immagini');
        }

        // Check cover images
        $sql = 'SELECT COUNT(DISTINCT p.id_product) as count
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
                INNER JOIN ' . _DB_PREFIX_ . 'image i ON p.id_product = i.id_product
                LEFT JOIN ' . _DB_PREFIX_ . 'image_shop ish ON i.id_image = ish.id_image AND ish.cover = 1
                WHERE ps.active = 1
                GROUP BY p.id_product
                HAVING MAX(ish.cover) IS NULL';

        $noCover = Db::getInstance()->getValue($sql);

        if ($noCover > 0) {
            $this->addIssue('images', 'warning', sprintf('%d prodotti senza immagine di copertina definita', $noCover));
        }

        $this->results['images'] = array(
            'products_without_images' => $noImages,
            'images_without_alt' => $noAlt,
            'products_without_cover' => $noCover,
        );
    }

    /**
     * Audit schema markup readiness
     */
    protected function auditSchemaReadiness()
    {
        $issues = array();

        // Check Organization configuration
        $orgName = Configuration::get('PROSEOMASTER_ORGANIZATION_NAME');
        $orgLogo = Configuration::get('PROSEOMASTER_ORGANIZATION_LOGO');

        if (empty($orgName) && empty(Configuration::get('PS_SHOP_NAME'))) {
            $this->addIssue('schema', 'critical', 'Nome organizzazione non configurato');
        }

        if (empty($orgLogo)) {
            $this->addIssue('schema', 'warning', 'Logo organizzazione non configurato', 'Il logo è richiesto per lo schema Organization');
        }

        // Check if Product schema is enabled
        if (!Configuration::get('PROSEOMASTER_ENABLE_PRODUCT_SCHEMA')) {
            $this->addIssue('schema', 'warning', 'Schema Product disabilitato', 'Abilita lo schema Product per i rich snippets dei prodotti');
        }

        // Check social profiles for Organization
        $socialProfiles = array(
            'PROSEOMASTER_SOCIAL_FACEBOOK',
            'PROSEOMASTER_SOCIAL_TWITTER',
            'PROSEOMASTER_SOCIAL_INSTAGRAM',
        );

        $hasSocial = false;
        foreach ($socialProfiles as $profile) {
            if (!empty(Configuration::get($profile))) {
                $hasSocial = true;
                break;
            }
        }

        if (!$hasSocial) {
            $this->addIssue('schema', 'notice', 'Nessun profilo social configurato', 'I profili social arricchiscono lo schema Organization');
        }

        // Check LocalBusiness configuration
        if (Configuration::get('PROSEOMASTER_ENABLE_LOCAL_BUSINESS')) {
            if (empty(Configuration::get('PROSEOMASTER_LOCAL_STREET')) || empty(Configuration::get('PROSEOMASTER_LOCAL_CITY'))) {
                $this->addIssue('schema', 'warning', 'LocalBusiness abilitato ma indirizzo incompleto');
            }
        }

        $this->results['schema'] = $issues;
    }

    /**
     * Add issue to results
     * @param string $category
     * @param string $severity
     * @param string $message
     * @param string $solution
     */
    protected function addIssue($category, $severity, $message, $solution = '')
    {
        if (!isset($this->results[$category . '_issues'])) {
            $this->results[$category . '_issues'] = array();
        }

        $this->results[$category . '_issues'][] = array(
            'severity' => $severity,
            'message' => $message,
            'solution' => $solution,
        );

        $this->issueCounts[$severity]++;
    }

    /**
     * Calculate overall SEO score
     * @return int
     */
    protected function calculateSeoScore()
    {
        $score = 100;

        // Deduct points for issues
        $score -= $this->issueCounts['critical'] * 15;
        $score -= $this->issueCounts['warning'] * 5;
        $score -= $this->issueCounts['notice'] * 2;

        // Ensure score is between 0 and 100
        return max(0, min(100, $score));
    }

    /**
     * Get score label based on score value
     * @param int $score
     * @return string
     */
    public function getScoreLabel($score)
    {
        if ($score >= 90) {
            return 'Eccellente';
        } elseif ($score >= 70) {
            return 'Buono';
        } elseif ($score >= 50) {
            return 'Discreto';
        } elseif ($score >= 30) {
            return 'Da migliorare';
        } else {
            return 'Critico';
        }
    }

    /**
     * Get score color based on score value
     * @param int $score
     * @return string
     */
    public function getScoreColor($score)
    {
        if ($score >= 90) {
            return '#00a65a'; // Green
        } elseif ($score >= 70) {
            return '#00c0ef'; // Blue
        } elseif ($score >= 50) {
            return '#f39c12'; // Yellow
        } elseif ($score >= 30) {
            return '#ff851b'; // Orange
        } else {
            return '#dd4b39'; // Red
        }
    }

    /**
     * Analyze internal linking structure
     * @return array
     */
    public function analyzeInternalLinking()
    {
        $results = array(
            'orphan_products' => array(),
            'orphan_categories' => array(),
            'low_link_products' => array(),
            'top_linked_products' => array(),
            'suggestions' => array(),
            'stats' => array(
                'total_products' => 0,
                'orphan_count' => 0,
                'low_link_count' => 0,
                'avg_internal_links' => 0,
            ),
        );

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        // Get all products
        $products = Db::getInstance()->executeS(
            'SELECT p.id_product, pl.name, pl.link_rewrite, p.id_category_default
             FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = ' . $idShop . '
             WHERE ps.active = 1'
        );

        $results['stats']['total_products'] = count($products);

        // Count internal links for each product
        $linkCounts = array();
        foreach ($products as $product) {
            $linkCount = $this->countInternalLinksToProduct($product['id_product'], $idLang, $idShop);
            $linkCounts[$product['id_product']] = $linkCount;

            if ($linkCount === 0) {
                // Orphan product - no internal links pointing to it
                $results['orphan_products'][] = array(
                    'id' => $product['id_product'],
                    'name' => $product['name'],
                    'category' => $product['id_category_default'],
                );
                $results['stats']['orphan_count']++;
            } elseif ($linkCount < 3) {
                // Low link count
                $results['low_link_products'][] = array(
                    'id' => $product['id_product'],
                    'name' => $product['name'],
                    'link_count' => $linkCount,
                );
                $results['stats']['low_link_count']++;
            }
        }

        // Calculate average
        if (count($linkCounts) > 0) {
            $results['stats']['avg_internal_links'] = round(array_sum($linkCounts) / count($linkCounts), 1);
        }

        // Get top linked products
        arsort($linkCounts);
        $topLinked = array_slice($linkCounts, 0, 10, true);
        foreach ($topLinked as $productId => $count) {
            $productData = array_filter($products, function ($p) use ($productId) {
                return $p['id_product'] == $productId;
            });
            $productData = reset($productData);
            if ($productData) {
                $results['top_linked_products'][] = array(
                    'id' => $productId,
                    'name' => $productData['name'],
                    'link_count' => $count,
                );
            }
        }

        // Analyze categories
        $results['orphan_categories'] = $this->findOrphanCategories($idLang, $idShop);

        // Generate suggestions
        $results['suggestions'] = $this->generateLinkingSuggestions($results);

        return $results;
    }

    /**
     * Count internal links pointing to a product
     * @param int $idProduct
     * @param int $idLang
     * @param int $idShop
     * @return int
     */
    protected function countInternalLinksToProduct($idProduct, $idLang, $idShop)
    {
        $count = 0;

        // Check product URL patterns that might be linked
        $product = new Product($idProduct, false, $idLang);
        $productUrl = $this->context->link->getProductLink($idProduct, $product->link_rewrite, null, null, $idLang);
        $urlPath = parse_url($productUrl, PHP_URL_PATH);

        // Count links in other product descriptions
        $linkPattern = '%' . pSQL($urlPath) . '%';

        $count += (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_lang
             WHERE id_lang = ' . $idLang . '
             AND id_shop = ' . $idShop . '
             AND id_product != ' . $idProduct . '
             AND (description LIKE "' . $linkPattern . '" OR description_short LIKE "' . $linkPattern . '")'
        );

        // Count links in CMS pages
        $count += (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'cms_lang
             WHERE id_lang = ' . $idLang . '
             AND id_shop = ' . $idShop . '
             AND content LIKE "' . $linkPattern . '"'
        );

        // Count links in category descriptions
        $count += (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'category_lang
             WHERE id_lang = ' . $idLang . '
             AND id_shop = ' . $idShop . '
             AND description LIKE "' . $linkPattern . '"'
        );

        // Count "related products" links (accessories)
        $count += (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'accessory
             WHERE id_product_2 = ' . $idProduct
        );

        // Count cross-sell links if module exists (using information_schema for PS 8.x)
        $crossSellingExists = Db::getInstance()->executeS(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . pSQL(_DB_PREFIX_ . 'crossselling') . "'"
        );
        if ($crossSellingExists) {
            $count += (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'crossselling
                 WHERE id_product_2 = ' . $idProduct
            );
        }

        return $count;
    }

    /**
     * Find categories with no products
     * @param int $idLang
     * @param int $idShop
     * @return array
     */
    protected function findOrphanCategories($idLang, $idShop)
    {
        $orphans = array();

        $categories = Db::getInstance()->executeS(
            'SELECT c.id_category, cl.name, c.level_depth
             FROM ' . _DB_PREFIX_ . 'category c
             INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = ' . $idLang . ' AND cl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs ON c.id_category = cs.id_category AND cs.id_shop = ' . $idShop . '
             LEFT JOIN ' . _DB_PREFIX_ . 'category_product cp ON c.id_category = cp.id_category
             WHERE c.active = 1
             AND c.id_category > 2
             GROUP BY c.id_category
             HAVING COUNT(cp.id_product) = 0'
        );

        foreach ($categories as $cat) {
            $orphans[] = array(
                'id' => $cat['id_category'],
                'name' => $cat['name'],
                'depth' => $cat['level_depth'],
            );
        }

        return $orphans;
    }

    /**
     * Generate linking suggestions based on analysis
     * @param array $results
     * @return array
     */
    protected function generateLinkingSuggestions($results)
    {
        $suggestions = array();

        // Suggestion for orphan products
        if (count($results['orphan_products']) > 0) {
            $suggestions[] = array(
                'priority' => 'high',
                'title' => 'Prodotti orfani rilevati',
                'description' => sprintf(
                    '%d prodotti non hanno link interni che puntano ad essi. Google potrebbe avere difficoltà a scoprirli.',
                    count($results['orphan_products'])
                ),
                'action' => 'Aggiungi link nelle descrizioni di categorie correlate o crea sezioni "Prodotti correlati".',
            );
        }

        // Suggestion for low link products
        if (count($results['low_link_products']) > 0) {
            $suggestions[] = array(
                'priority' => 'medium',
                'title' => 'Prodotti con pochi link interni',
                'description' => sprintf(
                    '%d prodotti hanno meno di 3 link interni. Un buon linking interno migliora il crawling e la distribuzione del PageRank.',
                    count($results['low_link_products'])
                ),
                'action' => 'Usa i "Prodotti accessori" di PrestaShop e link nelle descrizioni.',
            );
        }

        // Suggestion for orphan categories
        if (count($results['orphan_categories']) > 0) {
            $suggestions[] = array(
                'priority' => 'medium',
                'title' => 'Categorie vuote',
                'description' => sprintf(
                    '%d categorie non contengono prodotti. Questo può confondere i visitatori e sprecare crawl budget.',
                    count($results['orphan_categories'])
                ),
                'action' => 'Aggiungi prodotti a queste categorie o rimuovile/nascondile.',
            );
        }

        // Suggestion based on average
        if ($results['stats']['avg_internal_links'] < 2) {
            $suggestions[] = array(
                'priority' => 'high',
                'title' => 'Linking interno insufficiente',
                'description' => sprintf(
                    'La media di link interni per prodotto è %.1f. Un buon obiettivo è almeno 3-5 link per pagina.',
                    $results['stats']['avg_internal_links']
                ),
                'action' => 'Implementa una strategia di linking: usa breadcrumb, prodotti correlati, cross-sell e link contestuali.',
            );
        }

        // Positive feedback if good
        if (count($suggestions) === 0) {
            $suggestions[] = array(
                'priority' => 'info',
                'title' => 'Buona struttura di linking',
                'description' => 'La struttura del linking interno appare ben organizzata.',
                'action' => 'Continua a monitorare e aggiungere link contestuali nelle nuove descrizioni.',
            );
        }

        return $suggestions;
    }

    /**
     * Get anchor text suggestions for a product
     * @param int $idProduct
     * @param int $idLang
     * @return array
     */
    public function getAnchorTextSuggestions($idProduct, $idLang)
    {
        $suggestions = array();

        $product = new Product($idProduct, false, $idLang);

        // Main suggestion: product name
        $suggestions[] = $product->name;

        // Category + product name
        if ($product->id_category_default) {
            $category = new Category($product->id_category_default, $idLang);
            if (Validate::isLoadedObject($category)) {
                $suggestions[] = $category->name . ' ' . $product->name;
            }
        }

        // Manufacturer + product name
        if ($product->id_manufacturer) {
            $manufacturer = new Manufacturer($product->id_manufacturer, $idLang);
            if (Validate::isLoadedObject($manufacturer)) {
                $suggestions[] = $manufacturer->name . ' ' . $product->name;
            }
        }

        // Reference if available
        if (!empty($product->reference)) {
            $suggestions[] = $product->reference;
        }

        // Action-based anchor
        $suggestions[] = 'Scopri ' . $product->name;
        $suggestions[] = 'Acquista ' . $product->name;

        return array_unique($suggestions);
    }

    /**
     * Calculate SEO score for dashboard (public wrapper)
     * @param int $idLang
     * @param int $idShop
     * @return int
     */
    public function calculateSeoScore($idLang = null, $idShop = null)
    {
        // Run a quick audit to populate issueCounts
        $this->runQuickAudit($idLang, $idShop);

        $score = 100;
        $score -= $this->issueCounts['critical'] * 15;
        $score -= $this->issueCounts['warning'] * 5;
        $score -= $this->issueCounts['notice'] * 2;

        return max(0, min(100, $score));
    }

    /**
     * Run quick audit for score calculation
     * @param int|null $idLang
     * @param int|null $idShop
     */
    protected function runQuickAudit($idLang = null, $idShop = null)
    {
        $this->issueCounts = array('critical' => 0, 'warning' => 0, 'notice' => 0, 'passed' => 0);

        $idLang = $idLang ?: (int) $this->context->language->id;
        $idShop = $idShop ?: (int) $this->context->shop->id;

        // Count products without meta
        $productsWithoutMeta = $this->getProductsWithoutMeta($idLang, $idShop);
        if (count($productsWithoutMeta) > 0) {
            $this->issueCounts['warning']++;
        }

        // Count categories without meta
        $categoriesWithoutMeta = $this->getCategoriesWithoutMeta($idLang, $idShop);
        if (count($categoriesWithoutMeta) > 0) {
            $this->issueCounts['warning']++;
        }

        // Check duplicates
        $duplicateTitles = $this->getDuplicateTitles($idLang, $idShop);
        if (count($duplicateTitles) > 0) {
            $this->issueCounts['warning']++;
        }

        $duplicateDescriptions = $this->getDuplicateDescriptions($idLang, $idShop);
        if (count($duplicateDescriptions) > 0) {
            $this->issueCounts['notice']++;
        }

        // Check technical SEO
        if (!file_exists(_PS_ROOT_DIR_ . '/robots.txt')) {
            $this->issueCounts['critical']++;
        }
        if (!file_exists(_PS_ROOT_DIR_ . '/sitemap.xml')) {
            $this->issueCounts['critical']++;
        }
        if (!Configuration::get('PS_SSL_ENABLED')) {
            $this->issueCounts['critical']++;
        }
    }

    /**
     * Get products without meta tags
     * @param int $idLang
     * @param int $idShop
     * @return array
     */
    public function getProductsWithoutMeta($idLang, $idShop)
    {
        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name');
        $sql->from('product', 'p');
        $sql->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang . ' AND pl.id_shop = ' . (int) $idShop);
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int) $idShop);
        $sql->where('ps.active = 1');
        $sql->where('(pl.meta_title IS NULL OR pl.meta_title = "" OR pl.meta_description IS NULL OR pl.meta_description = "")');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get categories without meta tags
     * @param int $idLang
     * @param int $idShop
     * @return array
     */
    public function getCategoriesWithoutMeta($idLang, $idShop)
    {
        $sql = new DbQuery();
        $sql->select('c.id_category, cl.name');
        $sql->from('category', 'c');
        $sql->innerJoin('category_lang', 'cl', 'c.id_category = cl.id_category AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . (int) $idShop);
        $sql->innerJoin('category_shop', 'cs', 'c.id_category = cs.id_category AND cs.id_shop = ' . (int) $idShop);
        $sql->where('c.active = 1');
        $sql->where('c.id_category > 2');
        $sql->where('(cl.meta_title IS NULL OR cl.meta_title = "" OR cl.meta_description IS NULL OR cl.meta_description = "")');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get duplicate meta titles
     * @param int $idLang
     * @param int $idShop
     * @return array
     */
    public function getDuplicateTitles($idLang, $idShop)
    {
        $sql = 'SELECT pl.meta_title, COUNT(*) as cnt, GROUP_CONCAT(p.id_product) as product_ids
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
                    AND pl.id_lang = ' . (int) $idLang . ' AND pl.id_shop = ' . (int) $idShop . '
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
                    AND ps.id_shop = ' . (int) $idShop . '
                WHERE ps.active = 1 AND pl.meta_title != "" AND pl.meta_title IS NOT NULL
                GROUP BY pl.meta_title
                HAVING cnt > 1';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get duplicate meta descriptions
     * @param int $idLang
     * @param int $idShop
     * @return array
     */
    public function getDuplicateDescriptions($idLang, $idShop)
    {
        $sql = 'SELECT pl.meta_description, COUNT(*) as cnt, GROUP_CONCAT(p.id_product) as product_ids
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
                    AND pl.id_lang = ' . (int) $idLang . ' AND pl.id_shop = ' . (int) $idShop . '
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
                    AND ps.id_shop = ' . (int) $idShop . '
                WHERE ps.active = 1 AND pl.meta_description != "" AND pl.meta_description IS NOT NULL
                GROUP BY pl.meta_description
                HAVING cnt > 1';

        return Db::getInstance()->executeS($sql);
    }
}
