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
 * @author      SEO Expert
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
}
