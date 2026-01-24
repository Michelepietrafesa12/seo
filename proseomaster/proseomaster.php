<?php
/**
 * ProSEOMaster - Professional SEO Module for PrestaShop 8.x
 *
 * Modern Symfony-based admin controller with full PS 8.2 compatibility
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 * @version     2.5.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Manual autoloader for namespaced classes (src/)
spl_autoload_register(function ($class) {
    $prefix = 'ProSEOMaster\\';
    $baseDir = dirname(__FILE__) . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Include helper classes
require_once dirname(__FILE__) . '/classes/ProSEOMasterHelper.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterSitemap.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterRobots.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterMeta.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterSchemaAdvanced.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterPerformance.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterAudit.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterAI.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterRedirects.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterLinkChecker.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterSchemaValidator.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterBulkEditor.php';

class ProSEOMaster extends Module
{
    /** @var array Configuration fields */
    protected $configFields = array(
        'PROSEOMASTER_ENABLE_PRODUCT_SCHEMA',
        'PROSEOMASTER_ENABLE_BREADCRUMB_SCHEMA',
        'PROSEOMASTER_ENABLE_ORGANIZATION_SCHEMA',
        'PROSEOMASTER_ENABLE_WEBSITE_SCHEMA',
        'PROSEOMASTER_ENABLE_LOCAL_BUSINESS',
        'PROSEOMASTER_ORGANIZATION_NAME',
        'PROSEOMASTER_ORGANIZATION_LOGO',
        'PROSEOMASTER_ORGANIZATION_PHONE',
        'PROSEOMASTER_ORGANIZATION_EMAIL',
        'PROSEOMASTER_SOCIAL_FACEBOOK',
        'PROSEOMASTER_SOCIAL_TWITTER',
        'PROSEOMASTER_SOCIAL_INSTAGRAM',
        'PROSEOMASTER_SOCIAL_LINKEDIN',
        'PROSEOMASTER_SOCIAL_YOUTUBE',
        'PROSEOMASTER_SOCIAL_PINTEREST',
        'PROSEOMASTER_LOCAL_STREET',
        'PROSEOMASTER_LOCAL_CITY',
        'PROSEOMASTER_LOCAL_POSTAL',
        'PROSEOMASTER_LOCAL_REGION',
        'PROSEOMASTER_LOCAL_COUNTRY',
        'PROSEOMASTER_LOCAL_LAT',
        'PROSEOMASTER_LOCAL_LNG',
        'PROSEOMASTER_BUSINESS_TYPE',
        'PROSEOMASTER_ENABLE_OG_TAGS',
        'PROSEOMASTER_ENABLE_TWITTER_CARDS',
        'PROSEOMASTER_TWITTER_SITE',
        'PROSEOMASTER_DEFAULT_OG_IMAGE',
        'PROSEOMASTER_ENABLE_CANONICAL',
        'PROSEOMASTER_ENABLE_HREFLANG',
        'PROSEOMASTER_GTIN_FIELD',
        'PROSEOMASTER_MPN_FIELD',
        'PROSEOMASTER_BRAND_FIELD',
        'PROSEOMASTER_CONDITION_FIELD',
        'PROSEOMASTER_ENABLE_FAQ_SCHEMA',
        'PROSEOMASTER_ENABLE_REVIEW_SCHEMA',
        'PROSEOMASTER_MIN_REVIEWS_AGGREGATE',
        // Advanced SEO settings
        'PROSEOMASTER_ENABLE_COLLECTION_SCHEMA',
        'PROSEOMASTER_ENABLE_MERCHANT_SCHEMA',
        'PROSEOMASTER_PRODUCT_TITLE_TEMPLATE',
        'PROSEOMASTER_PRODUCT_DESC_TEMPLATE',
        'PROSEOMASTER_CATEGORY_TITLE_TEMPLATE',
        'PROSEOMASTER_CATEGORY_DESC_TEMPLATE',
        'PROSEOMASTER_NOINDEX_FILTERED_PAGES',
        'PROSEOMASTER_NOINDEX_DEEP_PAGINATION',
        'PROSEOMASTER_ENABLE_LAZY_LOADING',
        'PROSEOMASTER_ENABLE_RESOURCE_HINTS',
        'PROSEOMASTER_ENABLE_CRITICAL_CSS',
        'PROSEOMASTER_ENABLE_DEFER_JS',
        'PROSEOMASTER_ENABLE_IMAGE_DIMENSIONS',
        'PROSEOMASTER_ENABLE_FONT_OPTIMIZATION',
        'PROSEOMASTER_ENABLE_IFRAME_OPTIMIZATION',
        'PROSEOMASTER_AUTO_GENERATE_SITEMAP',
        'PROSEOMASTER_SITEMAP_LAST_GENERATED',
        // AI Optimization
        'PROSEOMASTER_ENABLE_AI_SEO',
        'PROSEOMASTER_ENABLE_LLMS_TXT',
        'PROSEOMASTER_ENABLE_AI_META_TAGS',
        // Cron
        'PROSEOMASTER_CRON_TOKEN',
    );

    public function __construct()
    {
        $this->name = 'proseomaster';
        $this->tab = 'seo';
        $this->version = '2.5.0';
        $this->author = 'Michele Pietrafesa';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->displayName = $this->l('ProSEO Master - Advanced SEO & Schema');
        $this->description = $this->l('Professional SEO module with structured data (Schema.org), Open Graph, Twitter Cards, and advanced meta tags management for improved organic rankings.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    /**
     * Module installation
     * @return bool
     */
    public function install()
    {
        // Default configuration values
        $defaultConfig = array(
            'PROSEOMASTER_ENABLE_PRODUCT_SCHEMA' => 1,
            'PROSEOMASTER_ENABLE_BREADCRUMB_SCHEMA' => 1,
            'PROSEOMASTER_ENABLE_ORGANIZATION_SCHEMA' => 1,
            'PROSEOMASTER_ENABLE_WEBSITE_SCHEMA' => 1,
            'PROSEOMASTER_ENABLE_LOCAL_BUSINESS' => 0,
            'PROSEOMASTER_ENABLE_OG_TAGS' => 1,
            'PROSEOMASTER_ENABLE_TWITTER_CARDS' => 1,
            'PROSEOMASTER_ENABLE_CANONICAL' => 1,
            'PROSEOMASTER_ENABLE_HREFLANG' => 1,
            'PROSEOMASTER_ENABLE_FAQ_SCHEMA' => 1,
            'PROSEOMASTER_ENABLE_REVIEW_SCHEMA' => 1,
            'PROSEOMASTER_MIN_REVIEWS_AGGREGATE' => 1,
            'PROSEOMASTER_GTIN_FIELD' => 'ean13',
            'PROSEOMASTER_MPN_FIELD' => 'reference',
            'PROSEOMASTER_BRAND_FIELD' => 'manufacturer',
            'PROSEOMASTER_CONDITION_FIELD' => 'condition',
            'PROSEOMASTER_BUSINESS_TYPE' => 'Store',
            // Advanced SEO defaults
            'PROSEOMASTER_ENABLE_COLLECTION_SCHEMA' => 1,
            'PROSEOMASTER_ENABLE_MERCHANT_SCHEMA' => 1,
            'PROSEOMASTER_PRODUCT_TITLE_TEMPLATE' => '{product_name} | {category} | {shop_name}',
            'PROSEOMASTER_PRODUCT_DESC_TEMPLATE' => '{description_short} Acquista {product_name} online. {availability}. Spedizione veloce.',
            'PROSEOMASTER_CATEGORY_TITLE_TEMPLATE' => '{category_name} | {shop_name}',
            'PROSEOMASTER_CATEGORY_DESC_TEMPLATE' => 'Scopri la nostra selezione di {category_name}. {products_count} prodotti disponibili. Acquista online.',
            'PROSEOMASTER_NOINDEX_FILTERED_PAGES' => 1,
            'PROSEOMASTER_NOINDEX_DEEP_PAGINATION' => 1,
            'PROSEOMASTER_ENABLE_LAZY_LOADING' => 1,
            'PROSEOMASTER_ENABLE_RESOURCE_HINTS' => 1,
            'PROSEOMASTER_ENABLE_CRITICAL_CSS' => 0, // OFF default - can break theme
            'PROSEOMASTER_ENABLE_DEFER_JS' => 0, // OFF default - can break JS
            'PROSEOMASTER_ENABLE_IMAGE_DIMENSIONS' => 1,
            'PROSEOMASTER_ENABLE_FONT_OPTIMIZATION' => 0, // OFF default - can break fonts
            'PROSEOMASTER_ENABLE_IFRAME_OPTIMIZATION' => 1,
            'PROSEOMASTER_AUTO_GENERATE_SITEMAP' => 0,
            // AI Optimization defaults
            'PROSEOMASTER_ENABLE_AI_SEO' => 1,
            'PROSEOMASTER_ENABLE_LLMS_TXT' => 1,
            'PROSEOMASTER_ENABLE_AI_META_TAGS' => 1,
            // Cron token (generate unique token)
            'PROSEOMASTER_CRON_TOKEN' => $this->generateCronToken(),
        );

        foreach ($defaultConfig as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        // Install redirects table
        $redirects = new ProSEOMasterRedirects();
        if (!$redirects->install()) {
            return false;
        }

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('actionOutputHTMLBefore') &&
            $this->registerHook('moduleRoutes') &&
            $this->registerHook('actionProductDelete') &&
            $this->registerHook('actionCategoryDelete') &&
            $this->registerHook('actionDispatcher') &&
            $this->installTab();
    }

    /**
     * Module uninstallation
     * @return bool
     */
    public function uninstall()
    {
        foreach ($this->configFields as $field) {
            Configuration::deleteByName($field);
        }

        // Uninstall redirects table
        $redirects = new ProSEOMasterRedirects();
        $redirects->uninstall();

        return parent::uninstall() && $this->uninstallTab();
    }

    /**
     * Install admin tab
     * @return bool
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminProSEOMaster';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'ProSEO Master';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * Uninstall admin tab
     * @return bool
     */
    private function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminProSEOMaster');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * Indicates this module uses new translation system
     * Required for PrestaShop 8.x Symfony integration
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Get admin controller route for Symfony
     * Points to the modern Symfony controller
     *
     * @return string
     */
    public function getAdminLink(): string
    {
        return $this->context->link->getAdminLink('AdminProSEOMaster');
    }

    /**
     * Check if PrestaShop version supports Symfony routing
     *
     * @return bool
     */
    private function isSymfonyRoutingAvailable(): bool
    {
        return version_compare(_PS_VERSION_, '1.7.6.0', '>=');
    }

    /**
     * Generate unique cron token
     * @return string
     */
    protected function generateCronToken()
    {
        return bin2hex(random_bytes(16));
    }

    // getContent method moved to end of file to include redirect manager

    /**
     * Regenerate cron token action
     * @return string
     */
    protected function regenerateCronTokenAction()
    {
        $newToken = $this->generateCronToken();
        Configuration::updateValue('PROSEOMASTER_CRON_TOKEN', $newToken);
        return $this->displayConfirmation($this->l('Cron security token has been regenerated. Update your cron job with the new URL.'));
    }

    /**
     * Render SEO Dashboard with quick stats
     * @return string
     */
    protected function renderDashboard()
    {
        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-dashboard"></i> ' . $this->l('SEO Dashboard') . '</h3>';
        $html .= '<div class="row">';

        // Quick stats
        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#00a65a;color:#fff;text-align:center;padding:20px;">';
        $html .= '<h2 style="margin:0;">' . $this->getActiveProductCount() . '</h2>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Active Products') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#00c0ef;color:#fff;text-align:center;padding:20px;">';
        $html .= '<h2 style="margin:0;">' . $this->getActiveCategoryCount() . '</h2>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Active Categories') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $sitemapDate = Configuration::get('PROSEOMASTER_SITEMAP_LAST_GENERATED');
        $html .= '<div class="panel" style="background:#f39c12;color:#fff;text-align:center;padding:20px;">';
        $html .= '<h2 style="margin:0;font-size:14px;">' . ($sitemapDate ? date('d/m/Y', strtotime($sitemapDate)) : 'N/A') . '</h2>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Last Sitemap') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#dd4b39;color:#fff;text-align:center;padding:20px;">';
        $html .= '<h2 style="margin:0;">' . $this->getMissingMetaCount() . '</h2>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Missing Meta') . '</p>';
        $html .= '</div></div>';

        $html .= '</div>';

        // Action buttons
        $html .= '<div class="row" style="margin-top:15px;">';
        $html .= '<div class="col-lg-12">';
        $html .= '<form method="post" style="display:inline-block;margin-right:10px;">';
        $html .= '<button type="submit" name="generateSitemap" class="btn btn-primary">';
        $html .= '<i class="icon-sitemap"></i> ' . $this->l('Generate Sitemap');
        $html .= '</button></form>';

        $html .= '<form method="post" style="display:inline-block;margin-right:10px;">';
        $html .= '<button type="submit" name="generateRobots" class="btn btn-info">';
        $html .= '<i class="icon-file-text"></i> ' . $this->l('Generate Robots.txt');
        $html .= '</button></form>';

        $html .= '<form method="post" style="display:inline-block;margin-right:10px;">';
        $html .= '<button type="submit" name="runSeoAudit" class="btn btn-warning">';
        $html .= '<i class="icon-search"></i> ' . $this->l('Run SEO Audit');
        $html .= '</button></form>';

        $html .= '<form method="post" style="display:inline-block;">';
        $html .= '<button type="submit" name="generateHtaccess" class="btn btn-success">';
        $html .= '<i class="icon-rocket"></i> ' . $this->l('Generate .htaccess Rules');
        $html .= '</button></form>';

        $html .= '</div></div>';
        $html .= '</div>';

        // Sitemap & Cron Section
        $html .= $this->renderSitemapSection();

        return $html;
    }

    /**
     * Render Sitemap & Cron Job section
     * @return string
     */
    protected function renderSitemapSection()
    {
        $shopUrl = $this->context->link->getPageLink('index', true);
        $sitemapUrl = $shopUrl . 'sitemap.xml';
        $sitemapDate = Configuration::get('PROSEOMASTER_SITEMAP_LAST_GENERATED');
        $cronToken = Configuration::get('PROSEOMASTER_CRON_TOKEN');

        // Generate token if not exists
        if (empty($cronToken)) {
            $cronToken = $this->generateCronToken();
            Configuration::updateValue('PROSEOMASTER_CRON_TOKEN', $cronToken);
        }

        $cronUrl = $shopUrl . 'module/proseomaster/cron?action=sitemap&token=' . $cronToken;

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-sitemap"></i> ' . $this->l('Sitemap & Cron Job') . '</h3>';

        // Sitemap Links
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-6">';
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label"><strong>' . $this->l('Sitemap URL') . ':</strong></label>';
        $html .= '<div class="input-group">';
        $html .= '<input type="text" class="form-control" value="' . htmlspecialchars($sitemapUrl, ENT_QUOTES, 'UTF-8') . '" readonly onclick="this.select()">';
        $html .= '<span class="input-group-btn">';
        $html .= '<a href="' . htmlspecialchars($sitemapUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="btn btn-default" title="' . $this->l('Open Sitemap') . '"><i class="icon-external-link"></i></a>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<p class="help-block">' . $this->l('Submit this URL to Google Search Console and Bing Webmaster Tools.') . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="col-lg-6">';
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label"><strong>' . $this->l('Last Generated') . ':</strong></label>';
        $html .= '<p class="form-control-static">';
        if ($sitemapDate) {
            $html .= '<span class="badge badge-success" style="background:#00a65a;font-size:12px;">' . date('d/m/Y H:i:s', strtotime($sitemapDate)) . '</span>';
        } else {
            $html .= '<span class="badge badge-warning" style="background:#f39c12;font-size:12px;">' . $this->l('Never generated') . '</span>';
        }
        $html .= '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Cron Job Section
        $html .= '<hr>';
        $html .= '<h4><i class="icon-time"></i> ' . $this->l('Automatic Sitemap Generation (Cron Job)') . '</h4>';
        $html .= '<div class="alert alert-info">';
        $html .= '<p><strong>' . $this->l('Use this URL to automatically regenerate the sitemap via cron job:') . '</strong></p>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label"><strong>' . $this->l('Cron URL') . ':</strong></label>';
        $html .= '<div class="input-group">';
        $html .= '<input type="text" class="form-control" id="cronUrl" value="' . htmlspecialchars($cronUrl) . '" readonly onclick="this.select()">';
        $html .= '<span class="input-group-btn">';
        $html .= '<button type="button" class="btn btn-default" onclick="copyToClipboard(\'cronUrl\')" title="' . $this->l('Copy to clipboard') . '"><i class="icon-copy"></i></button>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // Cron examples
        $html .= '<div class="row" style="margin-top:15px;">';
        $html .= '<div class="col-lg-6">';
        $html .= '<div class="panel" style="background:#f9f9f9;">';
        $html .= '<h5><i class="icon-linux"></i> ' . $this->l('Linux Crontab Example') . ':</h5>';
        $html .= '<pre style="background:#333;color:#0f0;padding:10px;font-size:11px;overflow-x:auto;">';
        $html .= '# ' . $this->l('Daily at 3:00 AM') . "\n";
        $html .= '0 3 * * * curl -s "' . htmlspecialchars($cronUrl, ENT_QUOTES, 'UTF-8') . '" > /dev/null 2>&1' . "\n\n";
        $html .= '# ' . $this->l('Every 6 hours') . "\n";
        $html .= '0 */6 * * * wget -q -O - "' . htmlspecialchars($cronUrl, ENT_QUOTES, 'UTF-8') . '" > /dev/null 2>&1';
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="col-lg-6">';
        $html .= '<div class="panel" style="background:#f9f9f9;">';
        $html .= '<h5><i class="icon-cogs"></i> ' . $this->l('cPanel Cron Job') . ':</h5>';
        $html .= '<ol style="padding-left:20px;">';
        $html .= '<li>' . $this->l('Go to cPanel > Cron Jobs') . '</li>';
        $html .= '<li>' . $this->l('Set schedule (e.g., Once Per Day)') . '</li>';
        $html .= '<li>' . $this->l('Command:') . '</li>';
        $html .= '</ol>';
        $html .= '<pre style="background:#333;color:#0f0;padding:10px;font-size:11px;overflow-x:auto;">';
        $html .= '/usr/bin/curl -s "' . htmlspecialchars($cronUrl, ENT_QUOTES, 'UTF-8') . '"';
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Security token management
        $html .= '<hr>';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-6">';
        $html .= '<form method="post">';
        $html .= '<button type="submit" name="regenerateCronToken" class="btn btn-warning">';
        $html .= '<i class="icon-refresh"></i> ' . $this->l('Regenerate Security Token');
        $html .= '</button>';
        $html .= '<p class="help-block">' . $this->l('Generate a new token if the current one has been compromised.') . '</p>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '<div class="col-lg-6">';
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label"><strong>' . $this->l('Current Token') . ':</strong></label>';
        $html .= '<input type="text" class="form-control" value="' . htmlspecialchars($cronToken, ENT_QUOTES, 'UTF-8') . '" readonly style="font-family:monospace;">';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // JavaScript for copy to clipboard
        $html .= '<script>
        function copyToClipboard(elementId) {
            var copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("' . $this->l('Copied to clipboard!') . '");
        }
        </script>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Get active product count
     * @return int
     */
    protected function getActiveProductCount()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
             WHERE ps.active = 1 AND ps.id_shop = ' . (int) $this->context->shop->id
        );
    }

    /**
     * Get active category count
     * @return int
     */
    protected function getActiveCategoryCount()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'category c
             INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs ON c.id_category = cs.id_category
             WHERE c.active = 1 AND cs.id_shop = ' . (int) $this->context->shop->id . ' AND c.id_category > 2'
        );
    }

    /**
     * Get count of products missing meta tags
     * @return int
     */
    protected function getMissingMetaCount()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
             INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
             WHERE ps.active = 1 AND ps.id_shop = ' . (int) $this->context->shop->id . '
             AND pl.id_lang = ' . (int) $this->context->language->id . '
             AND (pl.meta_title = "" OR pl.meta_title IS NULL OR pl.meta_description = "" OR pl.meta_description IS NULL)'
        );
    }

    /**
     * Generate sitemap action
     * @return string
     */
    protected function generateSitemapAction()
    {
        try {
            $sitemap = new ProSEOMasterSitemap();
            $files = $sitemap->generateSitemap();
            Configuration::updateValue('PROSEOMASTER_SITEMAP_LAST_GENERATED', date('Y-m-d H:i:s'));

            // Submit to search engines
            $sitemap->submitToSearchEngines();

            return $this->displayConfirmation(
                sprintf($this->l('Sitemap generated successfully! %d files created.'), count($files))
            );
        } catch (Exception $e) {
            return $this->displayError($this->l('Error generating sitemap: ') . $e->getMessage());
        }
    }

    /**
     * Generate robots.txt action
     * @return string
     */
    protected function generateRobotsAction()
    {
        try {
            $robots = new ProSEOMasterRobots();
            $robots->backupRobotsTxt();

            if ($robots->saveRobotsTxt()) {
                return $this->displayConfirmation($this->l('Robots.txt generated successfully!'));
            } else {
                return $this->displayError($this->l('Error saving robots.txt file.'));
            }
        } catch (Exception $e) {
            return $this->displayError($this->l('Error generating robots.txt: ') . $e->getMessage());
        }
    }

    /**
     * Run SEO audit action
     * @return string
     */
    protected function runSeoAuditAction()
    {
        try {
            $audit = new ProSEOMasterAudit();
            $results = $audit->runFullAudit();

            $html = '<div class="panel">';
            $html .= '<h3><i class="icon-search"></i> ' . $this->l('SEO Audit Results') . '</h3>';

            // Score display
            $score = $results['score'];
            $scoreColor = $audit->getScoreColor($score);
            $scoreLabel = $audit->getScoreLabel($score);

            $html .= '<div style="text-align:center;padding:20px;">';
            $html .= '<div style="display:inline-block;width:120px;height:120px;border-radius:50%;background:' . $scoreColor . ';color:#fff;line-height:120px;font-size:36px;font-weight:bold;">';
            $html .= $score;
            $html .= '</div>';
            $html .= '<p style="font-size:18px;margin-top:10px;"><strong>' . $scoreLabel . '</strong></p>';
            $html .= '</div>';

            // Summary
            $html .= '<div class="row">';
            $html .= '<div class="col-md-3 text-center"><span class="badge" style="background:#dd4b39;font-size:16px;padding:10px 15px;">' . $results['summary']['critical'] . '</span><br>' . $this->l('Critical') . '</div>';
            $html .= '<div class="col-md-3 text-center"><span class="badge" style="background:#f39c12;font-size:16px;padding:10px 15px;">' . $results['summary']['warning'] . '</span><br>' . $this->l('Warnings') . '</div>';
            $html .= '<div class="col-md-3 text-center"><span class="badge" style="background:#00c0ef;font-size:16px;padding:10px 15px;">' . $results['summary']['notice'] . '</span><br>' . $this->l('Notices') . '</div>';
            $html .= '<div class="col-md-3 text-center"><span class="badge" style="background:#00a65a;font-size:16px;padding:10px 15px;">' . $results['summary']['passed'] . '</span><br>' . $this->l('Passed') . '</div>';
            $html .= '</div>';

            // Issues list
            $allIssues = array();
            foreach ($results['results'] as $key => $value) {
                if (strpos($key, '_issues') !== false && is_array($value)) {
                    $allIssues = array_merge($allIssues, $value);
                }
            }

            if (!empty($allIssues)) {
                $html .= '<hr><h4>' . $this->l('Issues Found') . '</h4>';
                $html .= '<table class="table">';
                $html .= '<thead><tr><th>' . $this->l('Severity') . '</th><th>' . $this->l('Issue') . '</th><th>' . $this->l('Solution') . '</th></tr></thead><tbody>';

                foreach ($allIssues as $issue) {
                    $severityClass = $issue['severity'] === 'critical' ? 'danger' : ($issue['severity'] === 'warning' ? 'warning' : 'info');
                    $html .= '<tr class="' . $severityClass . '">';
                    $html .= '<td><span class="label label-' . $severityClass . '">' . ucfirst($issue['severity']) . '</span></td>';
                    $html .= '<td>' . $issue['message'] . '</td>';
                    $html .= '<td>' . ($issue['solution'] ?? '-') . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody></table>';
            }

            $html .= '</div>';

            return $html;
        } catch (Exception $e) {
            return $this->displayError($this->l('Error running SEO audit: ') . $e->getMessage());
        }
    }

    /**
     * Generate .htaccess performance rules action
     * @return string
     */
    protected function generateHtaccessAction()
    {
        try {
            $performance = new ProSEOMasterPerformance();
            $rules = $performance->generateHtaccessRules();

            $htaccessPath = _PS_ROOT_DIR_ . '/.htaccess';

            // Check if ProSEO rules already exist
            if (file_exists($htaccessPath)) {
                $currentContent = file_get_contents($htaccessPath);

                // Remove old ProSEO rules if present
                if (strpos($currentContent, '# BEGIN ProSEO Master Performance Rules') !== false) {
                    $currentContent = preg_replace(
                        '/# BEGIN ProSEO Master Performance Rules.*?# END ProSEO Master Performance Rules/s',
                        '',
                        $currentContent
                    );
                }

                // Prepend new rules
                $newContent = trim($rules) . "\n\n" . trim($currentContent);

                // Backup original .htaccess
                copy($htaccessPath, $htaccessPath . '.backup.' . date('YmdHis'));

                // Write new content
                if (file_put_contents($htaccessPath, $newContent)) {
                    return $this->displayConfirmation(
                        $this->l('.htaccess performance rules generated successfully! A backup was created.')
                    );
                }
            }

            return $this->displayError($this->l('Error: Could not write to .htaccess file. Please check permissions.'));
        } catch (Exception $e) {
            return $this->displayError($this->l('Error generating .htaccess rules: ') . $e->getMessage());
        }
    }

    /**
     * Process form submission
     * @return string
     */
    protected function postProcess()
    {
        $errors = array();

        foreach ($this->configFields as $field) {
            $value = Tools::getValue($field, '');

            // Sanitize values
            if (strpos($field, 'EMAIL') !== false) {
                if (!empty($value) && !Validate::isEmail($value)) {
                    $errors[] = $this->l('Invalid email format');
                    continue;
                }
            }

            if (strpos($field, 'URL') !== false || strpos($field, 'SOCIAL_') !== false) {
                if (!empty($value) && !Validate::isUrl($value)) {
                    $errors[] = sprintf($this->l('Invalid URL format for %s'), $field);
                    continue;
                }
            }

            Configuration::updateValue($field, pSQL($value));
        }

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors));
        }

        return $this->displayConfirmation($this->l('Settings updated successfully'));
    }

    /**
     * Render configuration form
     * @return string
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitProSEOMasterConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigForm());
    }

    /**
     * Get configuration form structure
     * @return array
     */
    protected function getConfigForm()
    {
        $businessTypes = array(
            array('id' => 'Store', 'name' => 'Store'),
            array('id' => 'LocalBusiness', 'name' => 'Local Business'),
            array('id' => 'Restaurant', 'name' => 'Restaurant'),
            array('id' => 'AutoDealer', 'name' => 'Auto Dealer'),
            array('id' => 'ClothingStore', 'name' => 'Clothing Store'),
            array('id' => 'ElectronicsStore', 'name' => 'Electronics Store'),
            array('id' => 'FurnitureStore', 'name' => 'Furniture Store'),
            array('id' => 'GroceryStore', 'name' => 'Grocery Store'),
            array('id' => 'HardwareStore', 'name' => 'Hardware Store'),
            array('id' => 'HomeGoodsStore', 'name' => 'Home Goods Store'),
            array('id' => 'JewelryStore', 'name' => 'Jewelry Store'),
            array('id' => 'ShoeStore', 'name' => 'Shoe Store'),
            array('id' => 'SportingGoodsStore', 'name' => 'Sporting Goods Store'),
        );

        $gtinFields = array(
            array('id' => 'ean13', 'name' => 'EAN-13'),
            array('id' => 'upc', 'name' => 'UPC'),
            array('id' => 'isbn', 'name' => 'ISBN'),
            array('id' => 'none', 'name' => $this->l('Not available')),
        );

        $mpnFields = array(
            array('id' => 'reference', 'name' => $this->l('Reference')),
            array('id' => 'supplier_reference', 'name' => $this->l('Supplier Reference')),
            array('id' => 'none', 'name' => $this->l('Not available')),
        );

        $brandFields = array(
            array('id' => 'manufacturer', 'name' => $this->l('Manufacturer')),
            array('id' => 'supplier', 'name' => $this->l('Supplier')),
            array('id' => 'shop_name', 'name' => $this->l('Shop Name')),
        );

        return array(
            // Schema Markup Settings
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Schema Markup Settings'),
                        'icon' => 'icon-code',
                    ),
                    'input' => array(
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Product Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_PRODUCT_SCHEMA',
                            'desc' => $this->l('Enable structured data for products (required for rich snippets in Google)'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Breadcrumb Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_BREADCRUMB_SCHEMA',
                            'desc' => $this->l('Enable BreadcrumbList structured data'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Organization Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_ORGANIZATION_SCHEMA',
                            'desc' => $this->l('Enable Organization structured data'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable WebSite Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_WEBSITE_SCHEMA',
                            'desc' => $this->l('Enable WebSite structured data with SearchAction (sitelinks search box)'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable LocalBusiness Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_LOCAL_BUSINESS',
                            'desc' => $this->l('Enable LocalBusiness structured data (for physical stores)'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Review Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_REVIEW_SCHEMA',
                            'desc' => $this->l('Include AggregateRating in product schema when reviews are available'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Minimum Reviews for Aggregate'),
                            'name' => 'PROSEOMASTER_MIN_REVIEWS_AGGREGATE',
                            'desc' => $this->l('Minimum number of reviews required to show aggregate rating'),
                            'class' => 'fixed-width-sm',
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Product Schema Field Mapping
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Product Schema Field Mapping'),
                        'icon' => 'icon-link',
                    ),
                    'input' => array(
                        array(
                            'type' => 'select',
                            'label' => $this->l('GTIN Field'),
                            'name' => 'PROSEOMASTER_GTIN_FIELD',
                            'desc' => $this->l('Select which product field to use as GTIN (EAN/UPC/ISBN)'),
                            'options' => array(
                                'query' => $gtinFields,
                                'id' => 'id',
                                'name' => 'name',
                            ),
                        ),
                        array(
                            'type' => 'select',
                            'label' => $this->l('MPN Field'),
                            'name' => 'PROSEOMASTER_MPN_FIELD',
                            'desc' => $this->l('Select which product field to use as MPN (Manufacturer Part Number)'),
                            'options' => array(
                                'query' => $mpnFields,
                                'id' => 'id',
                                'name' => 'name',
                            ),
                        ),
                        array(
                            'type' => 'select',
                            'label' => $this->l('Brand Field'),
                            'name' => 'PROSEOMASTER_BRAND_FIELD',
                            'desc' => $this->l('Select which field to use as product brand'),
                            'options' => array(
                                'query' => $brandFields,
                                'id' => 'id',
                                'name' => 'name',
                            ),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Organization Settings
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Organization Information'),
                        'icon' => 'icon-building',
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Organization Name'),
                            'name' => 'PROSEOMASTER_ORGANIZATION_NAME',
                            'desc' => $this->l('Legal name of your organization (leave empty to use shop name)'),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Logo URL'),
                            'name' => 'PROSEOMASTER_ORGANIZATION_LOGO',
                            'desc' => $this->l('Full URL to your organization logo (recommended: 112x112px minimum, PNG or JPG)'),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Phone Number'),
                            'name' => 'PROSEOMASTER_ORGANIZATION_PHONE',
                            'desc' => $this->l('Customer service phone number with country code (e.g., +39 02 1234567)'),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Email'),
                            'name' => 'PROSEOMASTER_ORGANIZATION_EMAIL',
                            'desc' => $this->l('Contact email address'),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Social Media Profiles
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Social Media Profiles'),
                        'icon' => 'icon-share-alt',
                    ),
                    'description' => $this->l('Enter the full URLs of your social media profiles. These will be included in the Organization schema.'),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Facebook'),
                            'name' => 'PROSEOMASTER_SOCIAL_FACEBOOK',
                            'prefix' => '<i class="icon-facebook"></i>',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Twitter/X'),
                            'name' => 'PROSEOMASTER_SOCIAL_TWITTER',
                            'prefix' => '<i class="icon-twitter"></i>',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Instagram'),
                            'name' => 'PROSEOMASTER_SOCIAL_INSTAGRAM',
                            'prefix' => '<i class="icon-instagram"></i>',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('LinkedIn'),
                            'name' => 'PROSEOMASTER_SOCIAL_LINKEDIN',
                            'prefix' => '<i class="icon-linkedin"></i>',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('YouTube'),
                            'name' => 'PROSEOMASTER_SOCIAL_YOUTUBE',
                            'prefix' => '<i class="icon-youtube"></i>',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Pinterest'),
                            'name' => 'PROSEOMASTER_SOCIAL_PINTEREST',
                            'prefix' => '<i class="icon-pinterest"></i>',
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Local Business Settings
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Local Business Information'),
                        'icon' => 'icon-map-marker',
                    ),
                    'description' => $this->l('Fill these fields only if you have a physical store location.'),
                    'input' => array(
                        array(
                            'type' => 'select',
                            'label' => $this->l('Business Type'),
                            'name' => 'PROSEOMASTER_BUSINESS_TYPE',
                            'options' => array(
                                'query' => $businessTypes,
                                'id' => 'id',
                                'name' => 'name',
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Street Address'),
                            'name' => 'PROSEOMASTER_LOCAL_STREET',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('City'),
                            'name' => 'PROSEOMASTER_LOCAL_CITY',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Postal Code'),
                            'name' => 'PROSEOMASTER_LOCAL_POSTAL',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Region/State'),
                            'name' => 'PROSEOMASTER_LOCAL_REGION',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Country'),
                            'name' => 'PROSEOMASTER_LOCAL_COUNTRY',
                            'desc' => $this->l('Two-letter country code (e.g., IT, US, DE)'),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Latitude'),
                            'name' => 'PROSEOMASTER_LOCAL_LAT',
                            'class' => 'fixed-width-lg',
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Longitude'),
                            'name' => 'PROSEOMASTER_LOCAL_LNG',
                            'class' => 'fixed-width-lg',
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Open Graph & Twitter Cards
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Open Graph & Twitter Cards'),
                        'icon' => 'icon-share',
                    ),
                    'input' => array(
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Open Graph Tags'),
                            'name' => 'PROSEOMASTER_ENABLE_OG_TAGS',
                            'desc' => $this->l('Enable Open Graph meta tags for better social sharing'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Twitter Cards'),
                            'name' => 'PROSEOMASTER_ENABLE_TWITTER_CARDS',
                            'desc' => $this->l('Enable Twitter Card meta tags'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Twitter @username'),
                            'name' => 'PROSEOMASTER_TWITTER_SITE',
                            'desc' => $this->l('Your Twitter username without @'),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Default OG Image'),
                            'name' => 'PROSEOMASTER_DEFAULT_OG_IMAGE',
                            'desc' => $this->l('Full URL to default image for social sharing (recommended: 1200x630px)'),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Technical SEO
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Technical SEO'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => array(
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Canonical URLs'),
                            'name' => 'PROSEOMASTER_ENABLE_CANONICAL',
                            'desc' => $this->l('Add canonical URL meta tag to prevent duplicate content'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Hreflang Tags'),
                            'name' => 'PROSEOMASTER_ENABLE_HREFLANG',
                            'desc' => $this->l('Add hreflang tags for multilingual sites'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
        );
    }

    /**
     * Get configuration field values
     * @return array
     */
    protected function getConfigFieldsValues()
    {
        $values = array();
        foreach ($this->configFields as $field) {
            $values[$field] = Configuration::get($field);
        }
        return $values;
    }

    /**
     * Hook: displayHeader
     * Injects SEO meta tags, schema markup, and performance optimizations
     * @param array $params
     * @return string
     */
    public function hookDisplayHeader($params)
    {
        $output = '';
        $pageType = $this->getPageType();

        // --- PERFORMANCE OPTIMIZATIONS ---

        // 1. Resource hints (preconnect, dns-prefetch)
        if (Configuration::get('PROSEOMASTER_ENABLE_RESOURCE_HINTS')) {
            $performance = new ProSEOMasterPerformance();
            $output .= $performance->generateResourceHints();
            $output .= $performance->generatePreloadTags($pageType);
        }

        // 2. Font optimization
        if (Configuration::get('PROSEOMASTER_ENABLE_FONT_OPTIMIZATION')) {
            $performance = isset($performance) ? $performance : new ProSEOMasterPerformance();
            $output .= $performance->generateFontOptimization();
        }

        // 3. Critical CSS inline (for FCP/LCP optimization)
        if (Configuration::get('PROSEOMASTER_ENABLE_CRITICAL_CSS')) {
            $performance = isset($performance) ? $performance : new ProSEOMasterPerformance();
            $output .= $performance->getCriticalCss($pageType);
        }

        // 4. Performance meta tags
        if (Configuration::get('PROSEOMASTER_ENABLE_RESOURCE_HINTS')) {
            $performance = isset($performance) ? $performance : new ProSEOMasterPerformance();
            $output .= $performance->generatePerformanceMetaTags();
        }

        // --- AI SEO OPTIMIZATIONS ---

        // Add AI-specific meta tags (Google AI Mode, GPT, Claude, etc.)
        if (Configuration::get('PROSEOMASTER_ENABLE_AI_META_TAGS')) {
            $ai = new ProSEOMasterAI();
            $output .= $ai->generateAIMetaTags();
        }

        // --- SEO OPTIMIZATIONS ---

        // Add Open Graph and Twitter Card meta tags
        $output .= $this->generateSocialMetaTags();

        // Add hreflang tags
        if (Configuration::get('PROSEOMASTER_ENABLE_HREFLANG')) {
            $output .= $this->generateHreflangTags();
        }

        // Add JSON-LD Schema Markup
        $output .= $this->generateSchemaMarkup();

        return $output;
    }

    /**
     * Get current page type
     * @return string
     */
    protected function getPageType()
    {
        $controller = $this->context->controller;
        $page = $controller->getPageName();

        switch ($page) {
            case 'product':
                return 'product';
            case 'category':
                return 'category';
            case 'cart':
            case 'order':
            case 'order-opc':
                return 'cart';
            case 'cms':
                return 'cms';
            case 'index':
                return 'index';
            default:
                return 'default';
        }
    }

    /**
     * Generate social meta tags (Open Graph & Twitter Cards)
     * @return string
     */
    protected function generateSocialMetaTags()
    {
        $output = '';
        $controller = $this->context->controller;
        $page = $controller->getPageName();

        // Safe access to Smarty template variables (PHP 8.x null safety)
        $pageVars = isset($this->context->smarty->tpl_vars['page']) ? $this->context->smarty->tpl_vars['page']->value : array();
        $title = $pageVars['meta']['title'] ?? Configuration::get('PS_SHOP_NAME');
        $description = $pageVars['meta']['description'] ?? '';
        $url = $this->getCurrentUrl();
        $image = Configuration::get('PROSEOMASTER_DEFAULT_OG_IMAGE');
        $siteName = Configuration::get('PS_SHOP_NAME');

        // Get product-specific data
        if ($page === 'product' && isset($this->context->smarty->tpl_vars['product'])) {
            $product = $this->context->smarty->tpl_vars['product']->value ?? array();
            if (is_array($product)) {
                $title = $product['name'] ?? $title;
                $description = strip_tags($product['description_short'] ?? $description);
                if (!empty($product['cover']['large']['url'])) {
                    $image = $product['cover']['large']['url'];
                }
            }
        }

        // Get category-specific data
        if ($page === 'category' && isset($this->context->smarty->tpl_vars['category'])) {
            $category = $this->context->smarty->tpl_vars['category']->value ?? null;
            if (is_object($category)) {
                $title = $category->name ?? $title;
                $description = strip_tags($category->description ?? $description);
            }
        }

        // Open Graph Tags
        if (Configuration::get('PROSEOMASTER_ENABLE_OG_TAGS')) {
            $output .= '<!-- ProSEO Master: Open Graph -->' . "\n";
            $output .= '<meta property="og:type" content="' . ($page === 'product' ? 'product' : 'website') . '" />' . "\n";
            $output .= '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            $output .= '<meta property="og:description" content="' . htmlspecialchars(Tools::truncateString($description, 200), ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            $output .= '<meta property="og:url" content="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            $output .= '<meta property="og:site_name" content="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            $output .= '<meta property="og:locale" content="' . $this->context->language->locale . '" />' . "\n";

            if (!empty($image)) {
                $output .= '<meta property="og:image" content="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
                $output .= '<meta property="og:image:alt" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            }

            // Product-specific OG tags
            if ($page === 'product' && isset($product) && is_array($product)) {
                if (!empty($product['price_amount'])) {
                    $output .= '<meta property="product:price:amount" content="' . $product['price_amount'] . '" />' . "\n";
                    $output .= '<meta property="product:price:currency" content="' . $this->context->currency->iso_code . '" />' . "\n";
                }
                $output .= '<meta property="product:availability" content="' . ($product['quantity'] > 0 ? 'in stock' : 'out of stock') . '" />' . "\n";
            }
        }

        // Twitter Cards
        if (Configuration::get('PROSEOMASTER_ENABLE_TWITTER_CARDS')) {
            $output .= '<!-- ProSEO Master: Twitter Cards -->' . "\n";
            $output .= '<meta name="twitter:card" content="summary_large_image" />' . "\n";
            $output .= '<meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            $output .= '<meta name="twitter:description" content="' . htmlspecialchars(Tools::truncateString($description, 200), ENT_QUOTES, 'UTF-8') . '" />' . "\n";

            if (!empty($image)) {
                $output .= '<meta name="twitter:image" content="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            }

            $twitterSite = Configuration::get('PROSEOMASTER_TWITTER_SITE');
            if (!empty($twitterSite)) {
                $output .= '<meta name="twitter:site" content="@' . htmlspecialchars($twitterSite, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            }
        }

        return $output;
    }

    /**
     * Generate hreflang tags for multilingual sites
     * Supports language-country codes (it-IT, en-GB) for better targeting
     * @return string
     */
    protected function generateHreflangTags()
    {
        $output = '';
        $languages = Language::getLanguages(true, $this->context->shop->id);

        if (count($languages) <= 1) {
            return $output;
        }

        $output .= '<!-- ProSEO Master: Hreflang -->' . "\n";
        $controller = $this->context->controller;
        $page = $controller->getPageName();

        // Get default country for language-country format
        $defaultCountry = Configuration::get('PS_COUNTRY_DEFAULT');
        $country = new Country($defaultCountry, $this->context->language->id);
        $countryIso = strtoupper($country->iso_code);

        $addedLangs = array();

        foreach ($languages as $lang) {
            $url = $this->getAlternateUrl($lang['id_lang'], $page);
            if (!$url) {
                continue;
            }

            // Get proper hreflang code
            $hreflang = $this->getHreflangCode($lang, $countryIso);

            // Avoid duplicates
            if (in_array($hreflang, $addedLangs)) {
                continue;
            }
            $addedLangs[] = $hreflang;

            $output .= '<link rel="alternate" hreflang="' . $hreflang . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />' . "\n";

            // If we have language_code like "it", also add country-specific "it-IT" if different
            $langOnly = substr($lang['language_code'], 0, 2);
            $langCountry = strtolower($langOnly) . '-' . strtoupper($langOnly);

            if ($hreflang === $langOnly && !in_array($langCountry, $addedLangs)) {
                $output .= '<link rel="alternate" hreflang="' . $langCountry . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
                $addedLangs[] = $langCountry;
            }
        }

        // Add x-default pointing to default language
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $defaultUrl = $this->getAlternateUrl($defaultLang, $page);
        if ($defaultUrl) {
            $output .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($defaultUrl, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        }

        return $output;
    }

    /**
     * Get proper hreflang code from language
     * Handles both simple (it, en) and complex (it-IT, en-GB) formats
     * @param array $lang
     * @param string $defaultCountryIso
     * @return string
     */
    protected function getHreflangCode($lang, $defaultCountryIso)
    {
        // If language_code already has region (e.g., en-GB, pt-BR)
        if (strpos($lang['language_code'], '-') !== false) {
            return strtolower($lang['language_code']);
        }

        // If language_code is just language (e.g., it, en)
        // Return as is - Google accepts both formats
        return strtolower($lang['language_code']);
    }

    /**
     * Get alternate URL for a specific language
     * @param int $idLang
     * @param string $page
     * @return string|null
     */
    protected function getAlternateUrl($idLang, $page)
    {
        $link = $this->context->link;

        switch ($page) {
            case 'product':
                if (isset($this->context->smarty->tpl_vars['product'])) {
                    $product = $this->context->smarty->tpl_vars['product']->value ?? array();
                    if (is_array($product) && isset($product['id_product'])) {
                        return $link->getProductLink($product['id_product'], null, null, null, $idLang);
                    }
                }
                break;
            case 'category':
                if (isset($this->context->smarty->tpl_vars['category'])) {
                    $category = $this->context->smarty->tpl_vars['category']->value ?? null;
                    if (is_object($category)) {
                        return $link->getCategoryLink($category->id, null, $idLang);
                    }
                }
                break;
            case 'cms':
                if (Tools::getValue('id_cms')) {
                    return $link->getCMSLink(Tools::getValue('id_cms'), null, null, $idLang);
                }
                break;
            case 'index':
                return $link->getPageLink('index', true, $idLang);
            default:
                return $link->getPageLink($page, true, $idLang);
        }

        return null;
    }

    /**
     * Generate all schema markup
     * @return string
     */
    protected function generateSchemaMarkup()
    {
        $output = '';
        $schemas = array();

        // WebSite Schema
        if (Configuration::get('PROSEOMASTER_ENABLE_WEBSITE_SCHEMA')) {
            $websiteSchema = $this->generateWebSiteSchema();
            if ($websiteSchema) {
                $schemas[] = $websiteSchema;
            }
        }

        // Organization Schema
        if (Configuration::get('PROSEOMASTER_ENABLE_ORGANIZATION_SCHEMA')) {
            $orgSchema = $this->generateOrganizationSchema();
            if ($orgSchema) {
                $schemas[] = $orgSchema;
            }
        }

        // LocalBusiness Schema
        if (Configuration::get('PROSEOMASTER_ENABLE_LOCAL_BUSINESS')) {
            $localSchema = $this->generateLocalBusinessSchema();
            if ($localSchema) {
                $schemas[] = $localSchema;
            }
        }

        // Breadcrumb Schema
        if (Configuration::get('PROSEOMASTER_ENABLE_BREADCRUMB_SCHEMA')) {
            $breadcrumbSchema = $this->generateBreadcrumbSchema();
            if ($breadcrumbSchema) {
                $schemas[] = $breadcrumbSchema;
            }
        }

        // Product Schema
        $page = $this->context->controller->getPageName();
        if ($page === 'product' && Configuration::get('PROSEOMASTER_ENABLE_PRODUCT_SCHEMA')) {
            $productSchema = $this->generateProductSchema();
            if ($productSchema) {
                $schemas[] = $productSchema;
            }
        }

        // ItemList Schema for category pages
        if ($page === 'category') {
            $itemListSchema = $this->generateItemListSchema();
            if ($itemListSchema) {
                $schemas[] = $itemListSchema;
            }
        }

        // Output all schemas
        if (!empty($schemas)) {
            $output .= '<!-- ProSEO Master: JSON-LD Schema -->' . "\n";
            foreach ($schemas as $schema) {
                $output .= '<script type="application/ld+json">' . "\n";
                $output .= json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $output .= "\n" . '</script>' . "\n";
            }
        }

        return $output;
    }

    /**
     * Generate WebSite schema
     * @return array|null
     */
    protected function generateWebSiteSchema()
    {
        $shopUrl = $this->context->link->getPageLink('index', true);
        $shopName = Configuration::get('PS_SHOP_NAME');

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $shopUrl . '#website',
            'url' => $shopUrl,
            'name' => $shopName,
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->context->link->getPageLink('search', true) . '?s={search_term_string}'
                ),
                'query-input' => 'required name=search_term_string'
            )
        );

        return $schema;
    }

    /**
     * Generate Organization schema
     * @return array|null
     */
    protected function generateOrganizationSchema()
    {
        $shopUrl = $this->context->link->getPageLink('index', true);
        $shopName = Configuration::get('PROSEOMASTER_ORGANIZATION_NAME');
        if (empty($shopName)) {
            $shopName = Configuration::get('PS_SHOP_NAME');
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $shopUrl . '#organization',
            'name' => $shopName,
            'url' => $shopUrl,
        );

        // Logo
        $logo = Configuration::get('PROSEOMASTER_ORGANIZATION_LOGO');
        if (!empty($logo)) {
            $schema['logo'] = array(
                '@type' => 'ImageObject',
                'url' => $logo,
            );
            $schema['image'] = $logo;
        }

        // Contact Point
        $phone = Configuration::get('PROSEOMASTER_ORGANIZATION_PHONE');
        $email = Configuration::get('PROSEOMASTER_ORGANIZATION_EMAIL');
        if (!empty($phone) || !empty($email)) {
            $contactPoint = array(
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
            );
            if (!empty($phone)) {
                $contactPoint['telephone'] = $phone;
            }
            if (!empty($email)) {
                $contactPoint['email'] = $email;
            }
            $schema['contactPoint'] = $contactPoint;
        }

        // Social profiles
        $sameAs = array();
        $socialFields = array(
            'PROSEOMASTER_SOCIAL_FACEBOOK',
            'PROSEOMASTER_SOCIAL_TWITTER',
            'PROSEOMASTER_SOCIAL_INSTAGRAM',
            'PROSEOMASTER_SOCIAL_LINKEDIN',
            'PROSEOMASTER_SOCIAL_YOUTUBE',
            'PROSEOMASTER_SOCIAL_PINTEREST',
        );
        foreach ($socialFields as $field) {
            $value = Configuration::get($field);
            if (!empty($value)) {
                $sameAs[] = $value;
            }
        }
        if (!empty($sameAs)) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }

    /**
     * Generate LocalBusiness schema
     * @return array|null
     */
    protected function generateLocalBusinessSchema()
    {
        $street = Configuration::get('PROSEOMASTER_LOCAL_STREET');
        $city = Configuration::get('PROSEOMASTER_LOCAL_CITY');

        if (empty($street) || empty($city)) {
            return null;
        }

        $shopUrl = $this->context->link->getPageLink('index', true);
        $businessType = Configuration::get('PROSEOMASTER_BUSINESS_TYPE') ?: 'Store';
        $shopName = Configuration::get('PROSEOMASTER_ORGANIZATION_NAME') ?: Configuration::get('PS_SHOP_NAME');

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $businessType,
            '@id' => $shopUrl . '#localbusiness',
            'name' => $shopName,
            'url' => $shopUrl,
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => $street,
                'addressLocality' => $city,
            ),
        );

        // Additional address fields
        $postal = Configuration::get('PROSEOMASTER_LOCAL_POSTAL');
        if (!empty($postal)) {
            $schema['address']['postalCode'] = $postal;
        }

        $region = Configuration::get('PROSEOMASTER_LOCAL_REGION');
        if (!empty($region)) {
            $schema['address']['addressRegion'] = $region;
        }

        $country = Configuration::get('PROSEOMASTER_LOCAL_COUNTRY');
        if (!empty($country)) {
            $schema['address']['addressCountry'] = $country;
        }

        // Geo coordinates
        $lat = Configuration::get('PROSEOMASTER_LOCAL_LAT');
        $lng = Configuration::get('PROSEOMASTER_LOCAL_LNG');
        if (!empty($lat) && !empty($lng)) {
            $schema['geo'] = array(
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            );
        }

        // Phone
        $phone = Configuration::get('PROSEOMASTER_ORGANIZATION_PHONE');
        if (!empty($phone)) {
            $schema['telephone'] = $phone;
        }

        // Logo
        $logo = Configuration::get('PROSEOMASTER_ORGANIZATION_LOGO');
        if (!empty($logo)) {
            $schema['image'] = $logo;
        }

        return $schema;
    }

    /**
     * Generate Breadcrumb schema
     * @return array|null
     */
    protected function generateBreadcrumbSchema()
    {
        if (!isset($this->context->smarty->tpl_vars['breadcrumb'])) {
            return null;
        }

        $breadcrumb = $this->context->smarty->tpl_vars['breadcrumb']->value ?? array();
        if (empty($breadcrumb['links'])) {
            return null;
        }

        $itemListElement = array();
        $position = 1;

        foreach ($breadcrumb['links'] as $link) {
            $item = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $link['title'],
            );

            if (!empty($link['url'])) {
                $item['item'] = $link['url'];
            }

            $itemListElement[] = $item;
            $position++;
        }

        if (empty($itemListElement)) {
            return null;
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemListElement,
        );
    }

    /**
     * Generate Product schema - CRITICAL for SEO
     * @return array|null
     */
    protected function generateProductSchema()
    {
        if (!isset($this->context->smarty->tpl_vars['product'])) {
            return null;
        }

        $productData = $this->context->smarty->tpl_vars['product']->value ?? array();
        if (!is_array($productData) || empty($productData['id_product'])) {
            return null;
        }

        $product = new Product((int) $productData['id_product'], true, $this->context->language->id);
        if (!Validate::isLoadedObject($product)) {
            return null;
        }

        $shopUrl = $this->context->link->getPageLink('index', true);
        $productUrl = $this->context->link->getProductLink($product);

        // Base schema
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $productUrl . '#product',
            'name' => $product->name,
            'url' => $productUrl,
        );

        // Description
        $description = strip_tags($product->description_short);
        if (!empty($description)) {
            $schema['description'] = $this->cleanText($description);
        }

        // Images
        $images = $this->getProductImages($product);
        if (!empty($images)) {
            $schema['image'] = count($images) === 1 ? $images[0] : $images;
        }

        // SKU
        if (!empty($product->reference)) {
            $schema['sku'] = $product->reference;
        }

        // GTIN (EAN/UPC/ISBN) - Required by Google for product rich results
        $gtinField = Configuration::get('PROSEOMASTER_GTIN_FIELD');
        $gtin = $this->getProductGtin($product, $gtinField);
        if (!empty($gtin)) {
            // Determine GTIN type based on length
            $gtinLength = strlen(preg_replace('/[^0-9]/', '', $gtin));
            if ($gtinLength === 13) {
                $schema['gtin13'] = $gtin;
            } elseif ($gtinLength === 12) {
                $schema['gtin12'] = $gtin;
            } elseif ($gtinLength === 8) {
                $schema['gtin8'] = $gtin;
            } elseif ($gtinLength === 14) {
                $schema['gtin14'] = $gtin;
            } else {
                $schema['gtin'] = $gtin;
            }
        }

        // MPN - Manufacturer Part Number
        $mpnField = Configuration::get('PROSEOMASTER_MPN_FIELD');
        $mpn = $this->getProductMpn($product, $mpnField);
        if (!empty($mpn)) {
            $schema['mpn'] = $mpn;
        }

        // Brand - Required by Google
        $brand = $this->getProductBrand($product);
        if (!empty($brand)) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brand,
            );
        }

        // Condition
        $schema['itemCondition'] = $this->mapProductCondition($product->condition);

        // Offers - Required for Product rich results
        $schema['offers'] = $this->generateOfferSchema($product, $productUrl);

        // AggregateRating and Reviews
        if (Configuration::get('PROSEOMASTER_ENABLE_REVIEW_SCHEMA')) {
            $reviewSchema = $this->generateReviewSchema($product);
            if (!empty($reviewSchema['aggregateRating'])) {
                $schema['aggregateRating'] = $reviewSchema['aggregateRating'];
            }
            if (!empty($reviewSchema['review'])) {
                $schema['review'] = $reviewSchema['review'];
            }
        }

        // Additional properties
        if (!empty($product->weight) && $product->weight > 0) {
            $schema['weight'] = array(
                '@type' => 'QuantitativeValue',
                'value' => (float) $product->weight,
                'unitCode' => Configuration::get('PS_WEIGHT_UNIT'),
            );
        }

        // Category
        $category = new Category((int) $product->id_category_default, $this->context->language->id);
        if (Validate::isLoadedObject($category)) {
            $schema['category'] = $category->name;
        }

        // Product ID - Important for Google Merchant Center
        $schema['productID'] = (string) $product->id;

        // Color attribute (if available)
        $color = $this->getProductAttribute($product, 'color');
        if (!empty($color)) {
            $schema['color'] = $color;
        }

        // Size attribute (if available)
        $size = $this->getProductAttribute($product, 'size');
        if (!empty($size)) {
            $schema['size'] = $size;
        }

        // Material attribute (if available)
        $material = $this->getProductAttribute($product, 'material');
        if (!empty($material)) {
            $schema['material'] = $material;
        }

        // Additional product features
        $features = $this->getProductFeatures($product);
        if (!empty($features)) {
            $schema['additionalProperty'] = $features;
        }

        // Merchant Return Policy - Required by Google Merchant Center
        $returnPolicy = $this->generateReturnPolicySchema();
        if (!empty($returnPolicy)) {
            $schema['offers']['hasMerchantReturnPolicy'] = $returnPolicy;
        }

        // Shipping details in offer
        if (isset($schema['offers']) && is_array($schema['offers'])) {
            $schema['offers']['shippingDetails'] = $this->generateShippingSchema();
        }

        return $schema;
    }

    /**
     * Get product attribute value by type
     * @param Product $product
     * @param string $attributeType (color, size, material)
     * @return string|null
     */
    protected function getProductAttribute($product, $attributeType)
    {
        // Map attribute type to group names (multilingual support)
        $groupNames = array(
            'color' => array('color', 'colore', 'couleur', 'farbe', 'colour', 'cor'),
            'size' => array('size', 'taglia', 'taille', 'größe', 'groesse', 'tamaño', 'tamanho'),
            'material' => array('material', 'materiale', 'matériau', 'matière'),
        );

        if (!isset($groupNames[$attributeType])) {
            return null;
        }

        $combinations = $product->getAttributeCombinations($this->context->language->id);

        foreach ($combinations as $combination) {
            $groupName = strtolower($combination['group_name']);
            if (in_array($groupName, $groupNames[$attributeType])) {
                return $combination['attribute_name'];
            }
        }

        return null;
    }

    /**
     * Get product features as additionalProperty
     * @param Product $product
     * @return array
     */
    protected function getProductFeatures($product)
    {
        $features = array();
        $productFeatures = $product->getFeatures();

        foreach ($productFeatures as $feature) {
            $featureName = new Feature($feature['id_feature'], $this->context->language->id);
            $featureValue = new FeatureValue($feature['id_feature_value'], $this->context->language->id);

            if (Validate::isLoadedObject($featureName) && Validate::isLoadedObject($featureValue)) {
                $features[] = array(
                    '@type' => 'PropertyValue',
                    'name' => $featureName->name,
                    'value' => $featureValue->value,
                );
            }
        }

        return $features;
    }

    /**
     * Generate MerchantReturnPolicy schema
     * Required by Google Merchant Center for product rich results
     * @return array
     */
    protected function generateReturnPolicySchema()
    {
        $shopUrl = $this->context->link->getPageLink('index', true);

        return array(
            '@type' => 'MerchantReturnPolicy',
            '@id' => $shopUrl . '#returnpolicy',
            'applicableCountry' => $this->context->country->iso_code,
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => 14,
            'returnMethod' => 'https://schema.org/ReturnByMail',
            'returnFees' => 'https://schema.org/FreeReturn',
        );
    }

    /**
     * Generate OfferShippingDetails schema
     * @return array
     */
    protected function generateShippingSchema()
    {
        $shopUrl = $this->context->link->getPageLink('index', true);
        $currency = $this->context->currency->iso_code;

        return array(
            '@type' => 'OfferShippingDetails',
            '@id' => $shopUrl . '#shipping',
            'shippingDestination' => array(
                '@type' => 'DefinedRegion',
                'addressCountry' => $this->context->country->iso_code,
            ),
            'shippingRate' => array(
                '@type' => 'MonetaryAmount',
                'value' => '0',
                'currency' => $currency,
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
     * Get product images
     * @param Product $product
     * @return array
     */
    protected function getProductImages($product)
    {
        $images = array();
        $productImages = $product->getImages($this->context->language->id);

        foreach ($productImages as $img) {
            $imageUrl = $this->context->link->getImageLink(
                $product->link_rewrite,
                $product->id . '-' . $img['id_image'],
                ImageType::getFormattedName('large')
            );

            // Ensure HTTPS
            if (Configuration::get('PS_SSL_ENABLED')) {
                $imageUrl = str_replace('http://', 'https://', $imageUrl);
            }

            $images[] = $imageUrl;
        }

        return $images;
    }

    /**
     * Get product GTIN based on configured field
     * @param Product $product
     * @param string $gtinField
     * @return string|null
     */
    protected function getProductGtin($product, $gtinField)
    {
        switch ($gtinField) {
            case 'ean13':
                return !empty($product->ean13) ? $product->ean13 : null;
            case 'upc':
                return !empty($product->upc) ? $product->upc : null;
            case 'isbn':
                return !empty($product->isbn) ? $product->isbn : null;
            default:
                return null;
        }
    }

    /**
     * Get product MPN based on configured field
     * @param Product $product
     * @param string $mpnField
     * @return string|null
     */
    protected function getProductMpn($product, $mpnField)
    {
        switch ($mpnField) {
            case 'reference':
                return !empty($product->reference) ? $product->reference : null;
            case 'supplier_reference':
                return !empty($product->supplier_reference) ? $product->supplier_reference : null;
            default:
                return null;
        }
    }

    /**
     * Get product brand based on configured field
     * @param Product $product
     * @return string|null
     */
    protected function getProductBrand($product)
    {
        $brandField = Configuration::get('PROSEOMASTER_BRAND_FIELD');

        switch ($brandField) {
            case 'manufacturer':
                if ((int) $product->id_manufacturer > 0) {
                    $manufacturer = new Manufacturer((int) $product->id_manufacturer, $this->context->language->id);
                    if (Validate::isLoadedObject($manufacturer)) {
                        return $manufacturer->name;
                    }
                }
                break;
            case 'supplier':
                if ((int) $product->id_supplier > 0) {
                    $supplier = new Supplier((int) $product->id_supplier, $this->context->language->id);
                    if (Validate::isLoadedObject($supplier)) {
                        return $supplier->name;
                    }
                }
                break;
            case 'shop_name':
                return Configuration::get('PS_SHOP_NAME');
        }

        // Fallback to shop name if no brand found (required by Google)
        return Configuration::get('PS_SHOP_NAME');
    }

    /**
     * Map PrestaShop condition to Schema.org ItemCondition
     * @param string $condition
     * @return string
     */
    protected function mapProductCondition($condition)
    {
        $conditions = array(
            'new' => 'https://schema.org/NewCondition',
            'used' => 'https://schema.org/UsedCondition',
            'refurbished' => 'https://schema.org/RefurbishedCondition',
        );

        return isset($conditions[$condition]) ? $conditions[$condition] : 'https://schema.org/NewCondition';
    }

    /**
     * Generate Offer schema for product
     * @param Product $product
     * @param string $productUrl
     * @return array
     */
    protected function generateOfferSchema($product, $productUrl)
    {
        $currency = $this->context->currency->iso_code;
        $priceWithTax = $product->getPrice(true, null, 2);
        $quantity = Product::getQuantity($product->id);

        // Determine availability
        if ($quantity > 0) {
            $availability = 'https://schema.org/InStock';
        } elseif ($product->out_of_stock == 1) {
            // Allow orders when out of stock
            $availability = 'https://schema.org/BackOrder';
        } else {
            $availability = 'https://schema.org/OutOfStock';
        }

        $offer = array(
            '@type' => 'Offer',
            'url' => $productUrl,
            'priceCurrency' => $currency,
            'price' => number_format($priceWithTax, 2, '.', ''),
            'availability' => $availability,
            'itemCondition' => $this->mapProductCondition($product->condition),
        );

        // Seller/Merchant
        $shopName = Configuration::get('PROSEOMASTER_ORGANIZATION_NAME') ?: Configuration::get('PS_SHOP_NAME');
        $offer['seller'] = array(
            '@type' => 'Organization',
            'name' => $shopName,
        );

        // Price valid until (for discounts)
        if ($product->specificPrice && !empty($product->specificPrice['to'])) {
            $priceValidUntil = $product->specificPrice['to'];
            if ($priceValidUntil !== '0000-00-00 00:00:00') {
                $offer['priceValidUntil'] = date('Y-m-d', strtotime($priceValidUntil));
            }
        } else {
            // Default: price valid for 1 year
            $offer['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));
        }

        // Shipping information (if applicable)
        $shippingInfo = $this->getShippingInfo($product);
        if (!empty($shippingInfo)) {
            $offer['shippingDetails'] = $shippingInfo;
        }

        return $offer;
    }

    /**
     * Get shipping information for product
     * @param Product $product
     * @return array|null
     */
    protected function getShippingInfo($product)
    {
        // Get default carrier
        $carriers = Carrier::getCarriersForOrder(
            $this->context->country->id_zone,
            null,
            $this->context->cart,
            null,
            null,
            PS_CARRIERS_ONLY
        );

        if (empty($carriers)) {
            return null;
        }

        $carrier = reset($carriers);
        $deliveryTime = $carrier['delay'];

        return array(
            '@type' => 'OfferShippingDetails',
            'shippingDestination' => array(
                '@type' => 'DefinedRegion',
                'addressCountry' => $this->context->country->iso_code,
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
                    'maxValue' => 7,
                    'unitCode' => 'DAY',
                ),
            ),
        );
    }

    /**
     * Generate Review and AggregateRating schema
     * Supports multiple review modules: productcomments, yotpo, trustpilot, stamped, etc.
     * @param Product $product
     * @return array
     */
    protected function generateReviewSchema($product)
    {
        $result = array();
        $avgRating = null;
        $reviewCount = 0;
        $reviews = array();

        // Try different review modules in priority order

        // 1. PrestaShop native productcomments
        if (Module::isEnabled('productcomments') && class_exists('ProductComment')) {
            $avgRating = ProductComment::getAverageGrade($product->id);
            $reviewCount = ProductComment::getCommentNumber($product->id);
            $reviews = ProductComment::getByProduct($product->id, 1, 10, true);
        }

        // 2. Yotpo Reviews
        if ($reviewCount == 0 && Module::isEnabled('yotpo')) {
            $yotpoData = $this->getYotpoReviews($product->id);
            if ($yotpoData) {
                $avgRating = $yotpoData['average_rating'];
                $reviewCount = $yotpoData['review_count'];
            }
        }

        // 3. Trustpilot module
        if ($reviewCount == 0 && Module::isEnabled('trustpilotreviews')) {
            $trustpilotData = $this->getTrustpilotReviews($product->id);
            if ($trustpilotData) {
                $avgRating = $trustpilotData['average_rating'];
                $reviewCount = $trustpilotData['review_count'];
            }
        }

        // 4. Stamped.io reviews
        if ($reviewCount == 0 && Module::isEnabled('stampedio')) {
            $stampedData = $this->getStampedReviews($product->id);
            if ($stampedData) {
                $avgRating = $stampedData['average_rating'];
                $reviewCount = $stampedData['review_count'];
            }
        }

        // 5. Judge.me reviews
        if ($reviewCount == 0 && Module::isEnabled('judgeme')) {
            $judgemeData = $this->getJudgemeReviews($product->id);
            if ($judgemeData) {
                $avgRating = $judgemeData['average_rating'];
                $reviewCount = $judgemeData['review_count'];
            }
        }

        // 6. Generic database check for custom review tables
        if ($reviewCount == 0) {
            $genericData = $this->getGenericReviewData($product->id);
            if ($genericData) {
                $avgRating = $genericData['average_rating'];
                $reviewCount = $genericData['review_count'];
            }
        }

        $minReviews = (int) Configuration::get('PROSEOMASTER_MIN_REVIEWS_AGGREGATE');

        // Only add aggregate rating if minimum reviews threshold is met
        if ($reviewCount >= $minReviews && !empty($avgRating) && $avgRating > 0) {
            $result['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $avgRating, 1, '.', ''),
                'bestRating' => '5',
                'worstRating' => '1',
                'reviewCount' => (int) $reviewCount,
                'ratingCount' => (int) $reviewCount,
            );
        }

        // Get individual reviews (limit to 10 for performance)
        if (!empty($reviews)) {
            $result['review'] = array();
            foreach ($reviews as $review) {
                $reviewSchema = array(
                    '@type' => 'Review',
                    'reviewRating' => array(
                        '@type' => 'Rating',
                        'ratingValue' => (int) ($review['grade'] ?? $review['rating'] ?? 5),
                        'bestRating' => '5',
                        'worstRating' => '1',
                    ),
                    'author' => array(
                        '@type' => 'Person',
                        'name' => $review['customer_name'] ?? $review['author'] ?? 'Customer',
                    ),
                    'datePublished' => date('Y-m-d', strtotime($review['date_add'] ?? $review['date'] ?? 'now')),
                );

                // Add review body if available
                $reviewBody = $review['content'] ?? $review['body'] ?? $review['text'] ?? '';
                if (!empty($reviewBody)) {
                    $reviewSchema['reviewBody'] = $this->cleanText($reviewBody);
                }

                $result['review'][] = $reviewSchema;
            }
        }

        return $result;
    }

    /**
     * Get Yotpo reviews data
     * @param int $idProduct
     * @return array|null
     */
    protected function getYotpoReviews($idProduct)
    {
        // Yotpo stores data via API, check if module has cached data
        if (class_exists('YotpoReviews')) {
            try {
                $yotpo = new YotpoReviews();
                if (method_exists($yotpo, 'getProductReviews')) {
                    return $yotpo->getProductReviews($idProduct);
                }
            } catch (Exception $e) {
                // Silently fail
            }
        }
        return null;
    }

    /**
     * Get Trustpilot reviews data
     * @param int $idProduct
     * @return array|null
     */
    protected function getTrustpilotReviews($idProduct)
    {
        // Check for Trustpilot integration
        try {
            $result = Db::getInstance()->getRow(
                'SELECT AVG(rating) as average_rating, COUNT(*) as review_count
                 FROM ' . _DB_PREFIX_ . 'trustpilot_reviews
                 WHERE id_product = ' . (int) $idProduct
            );
            if ($result && $result['review_count'] > 0) {
                return array(
                    'average_rating' => $result['average_rating'],
                    'review_count' => $result['review_count'],
                );
            }
        } catch (Exception $e) {
            // Table may not exist
        }
        return null;
    }

    /**
     * Get Stamped.io reviews data
     * @param int $idProduct
     * @return array|null
     */
    protected function getStampedReviews($idProduct)
    {
        try {
            $result = Db::getInstance()->getRow(
                'SELECT AVG(rating) as average_rating, COUNT(*) as review_count
                 FROM ' . _DB_PREFIX_ . 'stamped_reviews
                 WHERE id_product = ' . (int) $idProduct
            );
            if ($result && $result['review_count'] > 0) {
                return array(
                    'average_rating' => $result['average_rating'],
                    'review_count' => $result['review_count'],
                );
            }
        } catch (Exception $e) {
            // Table may not exist
        }
        return null;
    }

    /**
     * Get Judge.me reviews data
     * @param int $idProduct
     * @return array|null
     */
    protected function getJudgemeReviews($idProduct)
    {
        try {
            $result = Db::getInstance()->getRow(
                'SELECT AVG(rating) as average_rating, COUNT(*) as review_count
                 FROM ' . _DB_PREFIX_ . 'judgeme_reviews
                 WHERE id_product = ' . (int) $idProduct
            );
            if ($result && $result['review_count'] > 0) {
                return array(
                    'average_rating' => $result['average_rating'],
                    'review_count' => $result['review_count'],
                );
            }
        } catch (Exception $e) {
            // Table may not exist
        }
        return null;
    }

    /**
     * Try to get review data from common review table patterns
     * @param int $idProduct
     * @return array|null
     */
    protected function getGenericReviewData($idProduct)
    {
        // Common table patterns for review modules
        $tablePatterns = array(
            'product_comment',
            'product_review',
            'reviews',
            'product_reviews',
            'customer_reviews',
        );

        foreach ($tablePatterns as $pattern) {
            try {
                $tableName = _DB_PREFIX_ . $pattern;

                // Check if table exists using information_schema (PS 8.x compatible)
                $tableExists = Db::getInstance()->executeS(
                    "SELECT TABLE_NAME FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = '" . pSQL($tableName) . "'"
                );

                if (!empty($tableExists)) {
                    // Try to get rating data
                    $result = Db::getInstance()->getRow(
                        'SELECT AVG(COALESCE(rating, grade, note, score)) as average_rating,
                                COUNT(*) as review_count
                         FROM ' . bqSQL($tableName) . '
                         WHERE id_product = ' . (int) $idProduct . '
                         AND (active = 1 OR validate = 1 OR validated = 1 OR status = 1)'
                    );

                    if ($result && $result['review_count'] > 0) {
                        return array(
                            'average_rating' => $result['average_rating'],
                            'review_count' => $result['review_count'],
                        );
                    }
                }
            } catch (Exception $e) {
                // Continue to next pattern
            }
        }

        return null;
    }

    /**
     * Generate ItemList schema for category pages
     * @return array|null
     */
    protected function generateItemListSchema()
    {
        if (!isset($this->context->smarty->tpl_vars['listing'])) {
            return null;
        }

        $listing = $this->context->smarty->tpl_vars['listing']->value ?? array();
        if (empty($listing['products'])) {
            return null;
        }

        $itemListElement = array();
        $position = 1;

        foreach ($listing['products'] as $product) {
            $itemListElement[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'url' => $product['url'],
                'name' => $product['name'],
            );
            $position++;

            // Limit to 50 items for performance
            if ($position > 50) {
                break;
            }
        }

        if (empty($itemListElement)) {
            return null;
        }

        $categoryName = '';
        if (isset($this->context->smarty->tpl_vars['category'])) {
            $category = $this->context->smarty->tpl_vars['category']->value ?? null;
            if (is_object($category)) {
                $categoryName = $category->name;
            }
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $categoryName,
            'numberOfItems' => count($itemListElement),
            'itemListElement' => $itemListElement,
        );
    }

    /**
     * Get current page URL
     * @return string
     */
    protected function getCurrentUrl()
    {
        $protocol = Tools::usingSecureMode() ? 'https://' : 'http://';
        $host = Tools::getHttpHost(false, true);
        $requestUri = Tools::getValue('REQUEST_URI', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
        return $protocol . $host . $requestUri;
    }

    /**
     * Clean text for schema output
     * @param string $text
     * @return string
     */
    protected function cleanText($text)
    {
        // Remove HTML tags
        $text = strip_tags($text);
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // Remove excess whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim
        $text = trim($text);
        // Truncate if too long
        if (strlen($text) > 5000) {
            $text = substr($text, 0, 4997) . '...';
        }
        return $text;
    }

    /**
     * Hook: actionFrontControllerSetMedia
     * Add any required CSS/JS
     * @param array $params
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        // Add resource hints for performance
        if (Configuration::get('PROSEOMASTER_ENABLE_RESOURCE_HINTS')) {
            $performance = new ProSEOMasterPerformance();
            $this->context->controller->registerJavascript(
                'proseomaster-performance',
                'modules/' . $this->name . '/views/js/performance.js',
                array('position' => 'bottom', 'priority' => 1000)
            );
        }
    }

    /**
     * Hook: actionOutputHTMLBefore
     * Modify HTML output for performance optimization (Core Web Vitals)
     * @param array $params
     */
    public function hookActionOutputHTMLBefore($params)
    {
        if (!isset($params['html'])) {
            return;
        }

        $html = $params['html'];
        $pageType = $this->getPageType();

        // SAFETY: Disable risky optimizations on checkout/payment/order pages
        $isCheckoutPage = $this->isCheckoutOrPaymentPage();

        $performance = new ProSEOMasterPerformance();

        // 1. Add lazy loading to images (except above-the-fold)
        // SAFE on checkout - only affects image loading
        if (Configuration::get('PROSEOMASTER_ENABLE_LAZY_LOADING')) {
            $html = $performance->addLazyLoading($html);
        }

        // 2. Add explicit dimensions to images (prevents CLS)
        // SAFE on checkout - only adds attributes
        if (Configuration::get('PROSEOMASTER_ENABLE_IMAGE_DIMENSIONS')) {
            $html = $performance->addImageDimensions($html);
        }

        // 3. Defer non-critical JavaScript
        // DISABLED on checkout/payment pages to protect payment scripts
        if (Configuration::get('PROSEOMASTER_ENABLE_DEFER_JS') && !$isCheckoutPage) {
            $html = $performance->deferJavaScript($html);
        }

        // 4. Optimize iframes (lazy load, add dimensions)
        // DISABLED on checkout - could affect payment iframes (Stripe, PayPal, etc.)
        if (Configuration::get('PROSEOMASTER_ENABLE_IFRAME_OPTIMIZATION') && !$isCheckoutPage) {
            $html = $performance->optimizeIframes($html);
        }

        // 5. Add fetchpriority to LCP candidates
        // SAFE on checkout
        if (Configuration::get('PROSEOMASTER_ENABLE_LAZY_LOADING')) {
            $html = $performance->addFetchPriority($html);
        }

        // 6. Optimize font loading (non-blocking Google Fonts)
        // SAFE on checkout
        if (Configuration::get('PROSEOMASTER_ENABLE_FONT_OPTIMIZATION')) {
            $html = $performance->inlinePreloadFonts($html);
        }

        // 7. Add inline performance script (before </body>)
        // DISABLED on checkout to avoid any interference
        if (Configuration::get('PROSEOMASTER_ENABLE_RESOURCE_HINTS') && !$isCheckoutPage) {
            $performanceScript = $performance->getPerformanceScript();
            $html = str_replace('</body>', $performanceScript . "\n</body>", $html);
        }

        $params['html'] = $html;
    }

    /**
     * Check if current page is checkout, payment, or order related
     * These pages need maximum JavaScript compatibility for payment processing
     * @return bool
     */
    protected function isCheckoutOrPaymentPage()
    {
        $controller = $this->context->controller;
        $pageName = $controller->getPageName();

        // List of checkout/payment related pages
        $checkoutPages = array(
            'cart',
            'order',
            'order-opc',
            'order-confirmation',
            'checkout',
            'payment',
            'module-paypal',
            'module-stripe',
            'module-mollie',
            'module-adyen',
            'module-braintree',
            'module-klarna',
            'supercheckout',
            'onepagecheckout',
            'onepagecheckoutps',
            'thecheckout',
            'steasycheckout',
        );

        // Check page name
        if (in_array($pageName, $checkoutPages)) {
            return true;
        }

        // Check if page name contains payment/checkout keywords
        if (preg_match('/(checkout|payment|pay|order|cart)/i', $pageName)) {
            return true;
        }

        // Check URL for payment module routes
        $requestUri = Tools::getValue('REQUEST_URI', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        if (preg_match('/(checkout|payment|pay|order|cart|module.*pay)/i', $requestUri)) {
            return true;
        }

        // Check if it's a payment module controller
        if ($controller instanceof ModuleFrontController) {
            $moduleName = $controller->module->name ?? '';
            if (preg_match('/(pay|checkout|stripe|paypal|mollie|adyen|braintree|klarna)/i', $moduleName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hook: moduleRoutes
     * Add custom routes for sitemap and llms.txt access
     * @return array
     */
    public function hookModuleRoutes()
    {
        return array(
            'module-proseomaster-sitemap' => array(
                'controller' => 'sitemap',
                'rule' => 'sitemap.xml',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'proseomaster',
                ),
            ),
            // LLMs.txt for AI crawlers
            'module-proseomaster-llms' => array(
                'controller' => 'llms',
                'rule' => 'llms.txt',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'proseomaster',
                    'type' => 'basic',
                ),
            ),
            'module-proseomaster-llms-full' => array(
                'controller' => 'llms',
                'rule' => 'llms-full.txt',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'proseomaster',
                    'type' => 'full',
                ),
            ),
        );
    }

    /**
     * Render advanced configuration form
     * @return string
     */
    protected function renderAdvancedForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitProSEOMasterConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getAdvancedConfigForm());
    }

    /**
     * Get advanced configuration form structure
     * @return array
     */
    protected function getAdvancedConfigForm()
    {
        return array(
            // Meta Tags Templates
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Meta Tags Templates'),
                        'icon' => 'icon-edit',
                    ),
                    'description' => $this->l('Configure dynamic meta tag templates. Available placeholders: {product_name}, {category}, {shop_name}, {price}, {description_short}, {availability}, {products_count}'),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Product Title Template'),
                            'name' => 'PROSEOMASTER_PRODUCT_TITLE_TEMPLATE',
                            'desc' => $this->l('Template for product page titles'),
                            'class' => 'input-xxlarge',
                        ),
                        array(
                            'type' => 'textarea',
                            'label' => $this->l('Product Description Template'),
                            'name' => 'PROSEOMASTER_PRODUCT_DESC_TEMPLATE',
                            'desc' => $this->l('Template for product meta descriptions'),
                            'rows' => 3,
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Category Title Template'),
                            'name' => 'PROSEOMASTER_CATEGORY_TITLE_TEMPLATE',
                            'desc' => $this->l('Template for category page titles'),
                            'class' => 'input-xxlarge',
                        ),
                        array(
                            'type' => 'textarea',
                            'label' => $this->l('Category Description Template'),
                            'name' => 'PROSEOMASTER_CATEGORY_DESC_TEMPLATE',
                            'desc' => $this->l('Template for category meta descriptions'),
                            'rows' => 3,
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Advanced Schema
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Advanced Schema Markup'),
                        'icon' => 'icon-code',
                    ),
                    'input' => array(
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable CollectionPage Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_COLLECTION_SCHEMA',
                            'desc' => $this->l('Add CollectionPage schema to category pages'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Merchant Center Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_MERCHANT_SCHEMA',
                            'desc' => $this->l('Add Google Merchant Center compatible product data'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable FAQ Schema'),
                            'name' => 'PROSEOMASTER_ENABLE_FAQ_SCHEMA',
                            'desc' => $this->l('Generate FAQ schema from product features'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Crawl Optimization
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Crawl Optimization'),
                        'icon' => 'icon-search',
                    ),
                    'input' => array(
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Noindex Filtered Pages'),
                            'name' => 'PROSEOMASTER_NOINDEX_FILTERED_PAGES',
                            'desc' => $this->l('Add noindex to category pages with filters applied'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Noindex Deep Pagination'),
                            'name' => 'PROSEOMASTER_NOINDEX_DEEP_PAGINATION',
                            'desc' => $this->l('Add noindex to paginated pages beyond page 5'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // Performance Optimization
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Performance Optimization (Core Web Vitals)'),
                        'icon' => 'icon-rocket',
                    ),
                    'description' => $this->l('Optimize your site for Google\'s Core Web Vitals: LCP (Largest Contentful Paint), FCP (First Contentful Paint), CLS (Cumulative Layout Shift), INP (Interaction to Next Paint). These settings help improve PageSpeed scores and organic rankings.'),
                    'input' => array(
                        array(
                            'type' => 'html',
                            'name' => 'performance_info',
                            'html_content' => '<div class="alert alert-info"><strong>' . $this->l('Performance Targets:') . '</strong><br>
                                <ul style="margin:10px 0 0 20px;">
                                    <li><strong>LCP:</strong> ' . $this->l('Should be under 2.5 seconds') . '</li>
                                    <li><strong>FCP:</strong> ' . $this->l('Should be under 1.8 seconds') . '</li>
                                    <li><strong>CLS:</strong> ' . $this->l('Should be under 0.1') . '</li>
                                    <li><strong>TTFB:</strong> ' . $this->l('Should be under 0.8 seconds') . '</li>
                                </ul>
                            </div>',
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Lazy Loading'),
                            'name' => 'PROSEOMASTER_ENABLE_LAZY_LOADING',
                            'desc' => $this->l('Add loading="lazy" to images below the fold. Improves LCP and initial page load by deferring non-critical images.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Resource Hints'),
                            'name' => 'PROSEOMASTER_ENABLE_RESOURCE_HINTS',
                            'desc' => $this->l('Add preconnect and dns-prefetch hints for external resources (Google Fonts, Analytics, etc.). Reduces TTFB.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Critical CSS'),
                            'name' => 'PROSEOMASTER_ENABLE_CRITICAL_CSS',
                            'desc' => $this->l('Inline critical CSS for above-the-fold content. Dramatically improves FCP and LCP by rendering content faster.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Defer JavaScript'),
                            'name' => 'PROSEOMASTER_ENABLE_DEFER_JS',
                            'desc' => $this->l('Add defer/async to non-critical scripts. Improves INP and reduces render-blocking resources.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Image Dimensions'),
                            'name' => 'PROSEOMASTER_ENABLE_IMAGE_DIMENSIONS',
                            'desc' => $this->l('Add explicit width/height and aspect-ratio to images. Prevents CLS (layout shifts) during image loading.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable Font Optimization'),
                            'name' => 'PROSEOMASTER_ENABLE_FONT_OPTIMIZATION',
                            'desc' => $this->l('Add font-display:swap and optimize Google Fonts loading. Prevents CLS from font loading and improves FCP.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable iFrame Optimization'),
                            'name' => 'PROSEOMASTER_ENABLE_IFRAME_OPTIMIZATION',
                            'desc' => $this->l('Add lazy loading and explicit dimensions to iframes (videos, maps). Reduces initial page weight and CLS.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'html',
                            'name' => 'htaccess_info',
                            'html_content' => '<div class="alert alert-warning"><i class="icon-warning-sign"></i> <strong>' . $this->l('Server-Side Optimization:') . '</strong> ' . $this->l('Click "Generate .htaccess Rules" in the dashboard above to enable GZIP compression, browser caching, and other server-level optimizations. This is critical for TTFB improvement.') . '</div>',
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            // AI SEO Optimization
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('AI SEO Optimization (Google AI Mode, GPT, Claude)'),
                        'icon' => 'icon-magic',
                    ),
                    'description' => $this->l('Optimize your site for AI-powered search engines and assistants: Google AI Overviews, ChatGPT, Claude, Bing Copilot, Perplexity, and other LLMs. This helps your content appear in AI-generated answers and recommendations.'),
                    'input' => array(
                        array(
                            'type' => 'html',
                            'name' => 'ai_info',
                            'html_content' => '<div class="alert alert-info"><strong>' . $this->l('AI Search is the Future:') . '</strong><br>
                                <ul style="margin:10px 0 0 20px;">
                                    <li><strong>Google AI Mode:</strong> ' . $this->l('AI-powered search results with citations') . '</li>
                                    <li><strong>ChatGPT/GPT:</strong> ' . $this->l('OpenAI\'s conversational search') . '</li>
                                    <li><strong>Claude:</strong> ' . $this->l('Anthropic\'s AI assistant') . '</li>
                                    <li><strong>Perplexity:</strong> ' . $this->l('AI-native search engine') . '</li>
                                    <li><strong>Bing Copilot:</strong> ' . $this->l('Microsoft\'s AI-powered search') . '</li>
                                </ul>
                            </div>',
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable AI SEO'),
                            'name' => 'PROSEOMASTER_ENABLE_AI_SEO',
                            'desc' => $this->l('Master switch for all AI optimization features.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable llms.txt'),
                            'name' => 'PROSEOMASTER_ENABLE_LLMS_TXT',
                            'desc' => $this->l('Generate llms.txt file (like robots.txt but for AI). Provides structured information about your store for AI crawlers. Access at: /llms.txt and /llms-full.txt'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable AI Meta Tags'),
                            'name' => 'PROSEOMASTER_ENABLE_AI_META_TAGS',
                            'desc' => $this->l('Add meta tags that help AI systems understand and cite your content correctly.'),
                            'is_bool' => true,
                            'values' => array(
                                array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
                                array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                            ),
                        ),
                        array(
                            'type' => 'html',
                            'name' => 'ai_links',
                            'html_content' => '<div class="alert alert-success"><i class="icon-check"></i> <strong>' . $this->l('AI Files Available:') . '</strong><br>
                                <ul style="margin:10px 0 0 20px;">
                                    <li><a href="' . $this->context->link->getPageLink('index', true) . 'llms.txt" target="_blank">/llms.txt</a> - ' . $this->l('Basic store information for AI') . '</li>
                                    <li><a href="' . $this->context->link->getPageLink('index', true) . 'llms-full.txt" target="_blank">/llms-full.txt</a> - ' . $this->l('Complete product catalog for AI') . '</li>
                                </ul>
                                <p style="margin-top:10px;">' . $this->l('These files help AI systems like ChatGPT and Google AI understand your store and recommend your products.') . '</p>
                            </div>',
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
        );
    }

    /**
     * Hook: actionProductDelete
     * Create automatic redirect when product is deleted
     * @param array $params
     */
    public function hookActionProductDelete($params)
    {
        if (!isset($params['id_product'])) {
            return;
        }

        $idProduct = (int) $params['id_product'];
        $product = new Product($idProduct, true, $this->context->language->id);

        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $redirects = new ProSEOMasterRedirects();
        $redirects->autoRedirectDeletedProduct($idProduct, $product->id_category_default);
    }

    /**
     * Hook: actionCategoryDelete
     * Create automatic redirect when category is deleted
     * @param array $params
     */
    public function hookActionCategoryDelete($params)
    {
        if (!isset($params['category'])) {
            return;
        }

        $category = $params['category'];
        $idCategory = (int) $category->id;
        $idParent = (int) $category->id_parent;

        $redirects = new ProSEOMasterRedirects();
        $redirects->autoRedirectDeletedCategory($idCategory, $idParent);
    }

    /**
     * Hook: actionDispatcher
     * Handle 301 redirects for old URLs
     * @param array $params
     */
    public function hookActionDispatcher($params)
    {
        // Only process on 404 errors
        if (http_response_code() !== 404) {
            // Check if URL exists in redirects anyway
            $redirects = new ProSEOMasterRedirects();
            $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $redirect = $redirects->getRedirect($requestUri);

            if ($redirect) {
                $newUrl = $redirect['new_url'];

                // Make absolute URL if needed
                if (strpos($newUrl, 'http') !== 0) {
                    $baseUrl = $this->context->link->getBaseLink();
                    $newUrl = rtrim($baseUrl, '/') . $newUrl;
                }

                header('HTTP/1.1 ' . $redirect['redirect_type'] . ' Moved Permanently');
                header('Location: ' . $newUrl);
                header('Connection: close');
                exit;
            }
        }
    }

    /**
     * Get content with redirect manager
     * Override to add redirect handling
     * @return string
     */
    public function getContent()
    {
        $output = '';

        // Handle form submissions
        if (Tools::isSubmit('submitProSEOMasterConfig')) {
            $output .= $this->postProcess();
        }

        // Handle sitemap generation
        if (Tools::isSubmit('generateSitemap')) {
            $output .= $this->generateSitemapAction();
        }

        // Handle robots.txt generation
        if (Tools::isSubmit('generateRobots')) {
            $output .= $this->generateRobotsAction();
        }

        // Handle SEO audit
        if (Tools::isSubmit('runSeoAudit')) {
            $output .= $this->runSeoAuditAction();
        }

        // Handle .htaccess generation
        if (Tools::isSubmit('generateHtaccess')) {
            $output .= $this->generateHtaccessAction();
        }

        // Handle cron token regeneration
        if (Tools::isSubmit('regenerateCronToken')) {
            $output .= $this->regenerateCronTokenAction();
        }

        // Handle redirect actions
        if (Tools::isSubmit('addRedirect')) {
            $output .= $this->addRedirectAction();
        }

        if (Tools::isSubmit('deleteRedirect')) {
            $output .= $this->deleteRedirectAction();
        }

        if (Tools::isSubmit('toggleRedirect')) {
            $output .= $this->toggleRedirectAction();
        }

        if (Tools::isSubmit('importRedirects')) {
            $output .= $this->importRedirectsAction();
        }

        if (Tools::isSubmit('exportRedirects')) {
            $this->exportRedirectsAction();
        }

        if (Tools::isSubmit('cleanRedirects')) {
            $output .= $this->cleanRedirectsAction();
        }

        // Handle link checker actions
        if (Tools::isSubmit('runLinkChecker')) {
            $output .= $this->runLinkCheckerAction();
        }

        if (Tools::isSubmit('exportBrokenLinks')) {
            $this->exportBrokenLinksAction();
        }

        // Handle schema testing
        if (Tools::isSubmit('testProductSchema')) {
            $output .= $this->testProductSchemaAction();
        }

        if (Tools::isSubmit('auditAllSchemas')) {
            $output .= $this->auditAllSchemasAction();
        }

        // Handle internal linking analysis
        if (Tools::isSubmit('analyzeLinking')) {
            $output .= $this->analyzeLinkingAction();
        }

        // Handle SEO export
        if (Tools::isSubmit('exportSeoData')) {
            $this->exportSeoDataAction();
        }

        // Handle SEO import
        if (Tools::isSubmit('importSeoData')) {
            $output .= $this->importSeoDataAction();
        }

        // Render dashboard + forms + all sections
        return $output . $this->renderDashboard() . $this->renderRedirectManager() . $this->renderLinkChecker() . $this->renderSchemaTester() . $this->renderBulkEditor() . $this->renderForm() . $this->renderAdvancedForm();
    }

    /**
     * Add redirect action
     * @return string
     */
    protected function addRedirectAction()
    {
        $oldUrl = Tools::getValue('redirect_old_url');
        $newUrl = Tools::getValue('redirect_new_url');
        $redirectType = (int) Tools::getValue('redirect_type', 301);

        if (empty($oldUrl) || empty($newUrl)) {
            return $this->displayError($this->l('Both old URL and new URL are required.'));
        }

        $redirects = new ProSEOMasterRedirects();
        if ($redirects->addRedirect($oldUrl, $newUrl, $redirectType)) {
            return $this->displayConfirmation($this->l('Redirect added successfully.'));
        }

        return $this->displayError($this->l('Error adding redirect. The URL may already exist.'));
    }

    /**
     * Delete redirect action
     * @return string
     */
    protected function deleteRedirectAction()
    {
        $idRedirect = (int) Tools::getValue('id_redirect');

        if ($idRedirect <= 0) {
            return $this->displayError($this->l('Invalid redirect ID.'));
        }

        $redirects = new ProSEOMasterRedirects();
        if ($redirects->deleteRedirect($idRedirect)) {
            return $this->displayConfirmation($this->l('Redirect deleted successfully.'));
        }

        return $this->displayError($this->l('Error deleting redirect.'));
    }

    /**
     * Toggle redirect active status
     * @return string
     */
    protected function toggleRedirectAction()
    {
        $idRedirect = (int) Tools::getValue('id_redirect');

        if ($idRedirect <= 0) {
            return $this->displayError($this->l('Invalid redirect ID.'));
        }

        $redirects = new ProSEOMasterRedirects();
        if ($redirects->toggleActive($idRedirect)) {
            return $this->displayConfirmation($this->l('Redirect status updated.'));
        }

        return $this->displayError($this->l('Error updating redirect status.'));
    }

    /**
     * Import redirects from CSV
     * @return string
     */
    protected function importRedirectsAction()
    {
        if (!isset($_FILES['redirect_csv']) || $_FILES['redirect_csv']['error'] !== UPLOAD_ERR_OK) {
            return $this->displayError($this->l('Please upload a valid CSV file.'));
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($_FILES['redirect_csv']['size'] > $maxSize) {
            return $this->displayError($this->l('File size exceeds maximum allowed (5MB).'));
        }

        // Validate file extension
        $allowedExtensions = array('csv', 'txt');
        $fileExt = strtolower(pathinfo($_FILES['redirect_csv']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedExtensions)) {
            return $this->displayError($this->l('Invalid file type. Only CSV and TXT files are allowed.'));
        }

        // Validate MIME type
        $allowedMimeTypes = array('text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['redirect_csv']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return $this->displayError($this->l('Invalid file MIME type. Only CSV files are allowed.'));
        }

        $csvContent = file_get_contents($_FILES['redirect_csv']['tmp_name']);
        $redirects = new ProSEOMasterRedirects();
        $results = $redirects->importFromCsv($csvContent);

        $message = sprintf(
            $this->l('Import completed: %d successful, %d errors, %d skipped.'),
            $results['success'],
            $results['errors'],
            $results['skipped']
        );

        if ($results['errors'] > 0) {
            return $this->displayWarning($message);
        }

        return $this->displayConfirmation($message);
    }

    /**
     * Export redirects to CSV
     */
    protected function exportRedirectsAction()
    {
        $redirects = new ProSEOMasterRedirects();
        $csv = $redirects->exportToCsv();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="redirects_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }

    /**
     * Clean old unused redirects
     * @return string
     */
    protected function cleanRedirectsAction()
    {
        $redirects = new ProSEOMasterRedirects();
        $deleted = $redirects->cleanOldRedirects(90);

        return $this->displayConfirmation(
            sprintf($this->l('%d unused redirects older than 90 days have been removed.'), $deleted)
        );
    }

    /**
     * Render redirect manager section
     * @return string
     */
    protected function renderRedirectManager()
    {
        $redirects = new ProSEOMasterRedirects();
        $stats = $redirects->getStatistics();
        $allRedirects = $redirects->getAllRedirects(false, 50, 0);

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-random"></i> ' . $this->l('301 Redirect Manager') . '</h3>';

        // Statistics
        $html .= '<div class="row" style="margin-bottom:20px;">';
        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#3c8dbc;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $stats['total'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Total Redirects') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#00a65a;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $stats['active'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Active Redirects') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#f39c12;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $stats['total_hits'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Total Hits') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#605ca8;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $stats['auto_generated'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Auto-Generated') . '</p>';
        $html .= '</div></div>';
        $html .= '</div>';

        // Add redirect form
        $html .= '<div class="panel" style="background:#f9f9f9;">';
        $html .= '<h4><i class="icon-plus"></i> ' . $this->l('Add New Redirect') . '</h4>';
        $html .= '<form method="post" class="form-horizontal">';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-2">' . $this->l('Old URL') . ':</label>';
        $html .= '<div class="col-lg-4">';
        $html .= '<input type="text" name="redirect_old_url" class="form-control" placeholder="/old-product-url" required>';
        $html .= '</div>';

        $html .= '<label class="control-label col-lg-2">' . $this->l('New URL') . ':</label>';
        $html .= '<div class="col-lg-4">';
        $html .= '<input type="text" name="redirect_new_url" class="form-control" placeholder="/new-product-url" required>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-2">' . $this->l('Type') . ':</label>';
        $html .= '<div class="col-lg-2">';
        $html .= '<select name="redirect_type" class="form-control">';
        $html .= '<option value="301">301 (Permanent)</option>';
        $html .= '<option value="302">302 (Temporary)</option>';
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="col-lg-8">';
        $html .= '<button type="submit" name="addRedirect" class="btn btn-primary">';
        $html .= '<i class="icon-plus"></i> ' . $this->l('Add Redirect');
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';

        // Import/Export
        $html .= '<div class="row" style="margin:20px 0;">';
        $html .= '<div class="col-lg-6">';
        $html .= '<form method="post" enctype="multipart/form-data" class="form-inline">';
        $html .= '<div class="input-group">';
        $html .= '<input type="file" name="redirect_csv" class="form-control" accept=".csv">';
        $html .= '<span class="input-group-btn">';
        $html .= '<button type="submit" name="importRedirects" class="btn btn-info">';
        $html .= '<i class="icon-upload"></i> ' . $this->l('Import CSV');
        $html .= '</button>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<p class="help-block">' . $this->l('CSV format: old_url,new_url,redirect_type (301/302)') . '</p>';
        $html .= '</form>';
        $html .= '</div>';

        $html .= '<div class="col-lg-6 text-right">';
        $html .= '<form method="post" style="display:inline-block;">';
        $html .= '<button type="submit" name="exportRedirects" class="btn btn-success">';
        $html .= '<i class="icon-download"></i> ' . $this->l('Export CSV');
        $html .= '</button>';
        $html .= '</form>';
        $html .= ' ';
        $html .= '<form method="post" style="display:inline-block;">';
        $html .= '<button type="submit" name="cleanRedirects" class="btn btn-warning" onclick="return confirm(\'' . $this->l('Delete unused auto-generated redirects older than 90 days?') . '\');">';
        $html .= '<i class="icon-trash"></i> ' . $this->l('Clean Old Redirects');
        $html .= '</button>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</div>';

        // Redirects table
        $html .= '<h4><i class="icon-list"></i> ' . $this->l('Existing Redirects') . '</h4>';
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . $this->l('Old URL') . '</th>';
        $html .= '<th>' . $this->l('New URL') . '</th>';
        $html .= '<th>' . $this->l('Type') . '</th>';
        $html .= '<th>' . $this->l('Hits') . '</th>';
        $html .= '<th>' . $this->l('Last Hit') . '</th>';
        $html .= '<th>' . $this->l('Auto') . '</th>';
        $html .= '<th>' . $this->l('Status') . '</th>';
        $html .= '<th>' . $this->l('Actions') . '</th>';
        $html .= '</tr></thead><tbody>';

        if (empty($allRedirects)) {
            $html .= '<tr><td colspan="8" class="text-center">' . $this->l('No redirects yet. Add your first redirect above or delete a product to auto-generate one.') . '</td></tr>';
        } else {
            foreach ($allRedirects as $redirect) {
                $html .= '<tr>';
                $html .= '<td><code>' . htmlspecialchars($redirect['old_url']) . '</code></td>';
                $html .= '<td><code>' . htmlspecialchars($redirect['new_url']) . '</code></td>';
                $html .= '<td><span class="badge">' . $redirect['redirect_type'] . '</span></td>';
                $html .= '<td>' . $redirect['hits'] . '</td>';
                $html .= '<td>' . ($redirect['last_hit'] ? date('d/m/Y H:i', strtotime($redirect['last_hit'])) : '-') . '</td>';
                $html .= '<td>' . ($redirect['auto_generated'] ? '<i class="icon-check text-success"></i>' : '-') . '</td>';
                $html .= '<td>';
                if ($redirect['active']) {
                    $html .= '<span class="badge" style="background:#00a65a;">' . $this->l('Active') . '</span>';
                } else {
                    $html .= '<span class="badge" style="background:#dd4b39;">' . $this->l('Inactive') . '</span>';
                }
                $html .= '</td>';
                $html .= '<td>';
                $html .= '<form method="post" style="display:inline;">';
                $html .= '<input type="hidden" name="id_redirect" value="' . $redirect['id_redirect'] . '">';
                $html .= '<button type="submit" name="toggleRedirect" class="btn btn-xs btn-default" title="' . $this->l('Toggle status') . '">';
                $html .= '<i class="icon-power-off"></i>';
                $html .= '</button>';
                $html .= '</form> ';
                $html .= '<form method="post" style="display:inline;">';
                $html .= '<input type="hidden" name="id_redirect" value="' . $redirect['id_redirect'] . '">';
                $html .= '<button type="submit" name="deleteRedirect" class="btn btn-xs btn-danger" onclick="return confirm(\'' . $this->l('Delete this redirect?') . '\');" title="' . $this->l('Delete') . '">';
                $html .= '<i class="icon-trash"></i>';
                $html .= '</button>';
                $html .= '</form>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        // Info box
        $html .= '<div class="alert alert-info">';
        $html .= '<i class="icon-info-circle"></i> <strong>' . $this->l('How it works:') . '</strong><br>';
        $html .= '<ul style="margin:10px 0 0 20px;">';
        $html .= '<li>' . $this->l('301 redirects tell search engines the page has permanently moved (transfers SEO value)') . '</li>';
        $html .= '<li>' . $this->l('302 redirects are for temporary moves (does not transfer SEO value)') . '</li>';
        $html .= '<li>' . $this->l('Auto-generated redirects are created when you delete products/categories') . '</li>';
        $html .= '<li>' . $this->l('The "Hits" counter shows how many times the redirect was used') . '</li>';
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Run link checker action
     * @return string
     */
    protected function runLinkCheckerAction()
    {
        $scanType = Tools::getValue('scan_type', 'quick');
        $linkChecker = new ProSEOMasterLinkChecker();

        $startTime = microtime(true);

        switch ($scanType) {
            case 'full':
                $results = $linkChecker->scanAllProducts(0);
                break;
            case 'products':
                $results = $linkChecker->scanAllProducts(100);
                break;
            case 'cms':
                $results = $linkChecker->scanCmsPages();
                break;
            case 'images':
                $results = $linkChecker->checkProductImages(100);
                break;
            default:
                $results = $linkChecker->quickScan(30);
        }

        $duration = round(microtime(true) - $startTime, 2);

        // Store results in session for display and export
        $this->context->cookie->proseo_link_results = json_encode($results);
        $this->context->cookie->write();

        return $this->renderLinkCheckerResults($results, $duration, $scanType);
    }

    /**
     * Export broken links to CSV
     */
    protected function exportBrokenLinksAction()
    {
        $resultsJson = isset($this->context->cookie->proseo_link_results) ? $this->context->cookie->proseo_link_results : '';

        if (empty($resultsJson)) {
            return;
        }

        $results = json_decode($resultsJson, true);

        $csv = "Type,URL,Source Type,Source ID,Source Name,HTTP Code,Details\n";

        // Broken links
        if (!empty($results['broken_links'])) {
            foreach ($results['broken_links'] as $link) {
                $csv .= '"Broken Link",';
                $csv .= '"' . $link['url'] . '",';
                $csv .= '"' . $link['source_type'] . '",';
                $csv .= $link['source_id'] . ',';
                $csv .= '"' . str_replace('"', '""', $link['source_name']) . '",';
                $csv .= $link['http_code'] . ',';
                $csv .= '"' . str_replace('"', '""', $link['error']) . '"' . "\n";
            }
        }

        // Broken images
        if (!empty($results['broken_images'])) {
            foreach ($results['broken_images'] as $image) {
                $csv .= '"Broken Image",';
                $csv .= '"' . $image['url'] . '",';
                $csv .= '"' . $image['source_type'] . '",';
                $csv .= $image['source_id'] . ',';
                $csv .= '"' . str_replace('"', '""', $image['source_name']) . '",';
                $csv .= $image['http_code'] . ',';
                $csv .= '"' . str_replace('"', '""', $image['error']) . '"' . "\n";
            }
        }

        // Redirects
        if (!empty($results['redirects'])) {
            foreach ($results['redirects'] as $redirect) {
                $csv .= '"Redirect",';
                $csv .= '"' . $redirect['url'] . '",';
                $csv .= '"' . $redirect['source_type'] . '",';
                $csv .= $redirect['source_id'] . ',';
                $csv .= '"' . str_replace('"', '""', $redirect['source_name']) . '",';
                $csv .= $redirect['http_code'] . ',';
                $csv .= '"' . $redirect['redirect_url'] . '"' . "\n";
            }
        }

        // Missing images
        if (!empty($results['missing_images'])) {
            foreach ($results['missing_images'] as $image) {
                $csv .= '"Missing Image",';
                $csv .= '"' . $image['expected_path'] . '",';
                $csv .= '"product",';
                $csv .= $image['product_id'] . ',';
                $csv .= '"' . str_replace('"', '""', $image['product_name']) . '",';
                $csv .= '404,';
                $csv .= '"Image ID: ' . $image['image_id'] . '"' . "\n";
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="broken_links_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }

    /**
     * Render link checker results
     * @param array $results
     * @param float $duration
     * @param string $scanType
     * @return string
     */
    protected function renderLinkCheckerResults($results, $duration, $scanType)
    {
        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-search"></i> ' . $this->l('Link Checker Results') . '</h3>';

        // Summary stats
        $html .= '<div class="alert alert-info">';
        $html .= '<strong>' . $this->l('Scan completed in') . ' ' . $duration . ' ' . $this->l('seconds') . '</strong><br>';

        if (isset($results['stats'])) {
            $html .= sprintf(
                $this->l('Scanned %d products, checked %d links.'),
                $results['stats']['products_scanned'],
                $results['stats']['links_checked']
            );
        }
        $html .= '</div>';

        // Stats boxes
        $brokenLinks = count($results['broken_links'] ?? array());
        $brokenImages = count($results['broken_images'] ?? array());
        $redirects = count($results['redirects'] ?? array());
        $missingImages = count($results['missing_images'] ?? array());

        $html .= '<div class="row" style="margin-bottom:20px;">';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:' . ($brokenLinks > 0 ? '#dd4b39' : '#00a65a') . ';color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $brokenLinks . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Broken Links') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:' . ($brokenImages + $missingImages > 0 ? '#dd4b39' : '#00a65a') . ';color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . ($brokenImages + $missingImages) . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Broken Images') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:' . ($redirects > 0 ? '#f39c12' : '#00a65a') . ';color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $redirects . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Redirects Found') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#00c0ef;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . count($results['external_links'] ?? array()) . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('External Links') . '</p>';
        $html .= '</div></div>';

        $html .= '</div>';

        // Export button
        if ($brokenLinks > 0 || $brokenImages > 0 || $redirects > 0 || $missingImages > 0) {
            $html .= '<form method="post" style="margin-bottom:20px;">';
            $html .= '<button type="submit" name="exportBrokenLinks" class="btn btn-success">';
            $html .= '<i class="icon-download"></i> ' . $this->l('Export Results to CSV');
            $html .= '</button>';
            $html .= '</form>';
        }

        // Broken Links Table
        if ($brokenLinks > 0) {
            $html .= '<h4 style="color:#dd4b39;"><i class="icon-unlink"></i> ' . $this->l('Broken Links') . '</h4>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . $this->l('URL') . '</th>';
            $html .= '<th>' . $this->l('Found In') . '</th>';
            $html .= '<th>' . $this->l('HTTP Code') . '</th>';
            $html .= '<th>' . $this->l('Error') . '</th>';
            $html .= '<th>' . $this->l('Action') . '</th>';
            $html .= '</tr></thead><tbody>';

            foreach (array_slice($results['broken_links'], 0, 20) as $link) {
                $html .= '<tr>';
                $html .= '<td><code style="word-break:break-all;">' . htmlspecialchars($link['url']) . '</code>';
                if ($link['is_external']) {
                    $html .= ' <span class="badge">External</span>';
                }
                $html .= '</td>';
                $html .= '<td>';
                if ($link['source_type'] === 'product') {
                    $editUrl = $this->context->link->getAdminLink('AdminProducts') . '&id_product=' . $link['source_id'] . '&updateproduct';
                    $html .= '<a href="' . $editUrl . '" target="_blank">' . htmlspecialchars($link['source_name']) . '</a>';
                } else {
                    $html .= htmlspecialchars($link['source_name']);
                }
                $html .= '</td>';
                $html .= '<td><span class="badge" style="background:#dd4b39;">' . $link['http_code'] . '</span></td>';
                $html .= '<td>' . htmlspecialchars($link['error']) . '</td>';
                $html .= '<td>';
                if ($link['source_type'] === 'product') {
                    $editUrl = $this->context->link->getAdminLink('AdminProducts') . '&id_product=' . $link['source_id'] . '&updateproduct';
                    $html .= '<a href="' . $editUrl . '" target="_blank" class="btn btn-xs btn-primary"><i class="icon-pencil"></i> ' . $this->l('Edit') . '</a>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            if ($brokenLinks > 20) {
                $html .= '<p class="text-muted">' . sprintf($this->l('Showing 20 of %d broken links. Export to CSV for complete list.'), $brokenLinks) . '</p>';
            }
        }

        // Broken Images Table
        if ($brokenImages > 0 || $missingImages > 0) {
            $html .= '<h4 style="color:#dd4b39;margin-top:20px;"><i class="icon-picture-o"></i> ' . $this->l('Broken/Missing Images') . '</h4>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . $this->l('Image URL/Path') . '</th>';
            $html .= '<th>' . $this->l('Found In') . '</th>';
            $html .= '<th>' . $this->l('HTTP Code') . '</th>';
            $html .= '<th>' . $this->l('Action') . '</th>';
            $html .= '</tr></thead><tbody>';

            $allBrokenImages = array_merge($results['broken_images'] ?? array(), $results['missing_images'] ?? array());

            foreach (array_slice($allBrokenImages, 0, 20) as $image) {
                $html .= '<tr>';
                $html .= '<td><code style="word-break:break-all;">' . htmlspecialchars($image['url'] ?? $image['expected_path'] ?? '') . '</code></td>';
                $html .= '<td>';
                $productId = $image['source_id'] ?? $image['product_id'] ?? 0;
                $productName = $image['source_name'] ?? $image['product_name'] ?? '';
                if ($productId > 0) {
                    $editUrl = $this->context->link->getAdminLink('AdminProducts') . '&id_product=' . $productId . '&updateproduct';
                    $html .= '<a href="' . $editUrl . '" target="_blank">' . htmlspecialchars($productName) . '</a>';
                } else {
                    $html .= htmlspecialchars($productName);
                }
                $html .= '</td>';
                $html .= '<td><span class="badge" style="background:#dd4b39;">' . ($image['http_code'] ?? '404') . '</span></td>';
                $html .= '<td>';
                if ($productId > 0) {
                    $editUrl = $this->context->link->getAdminLink('AdminProducts') . '&id_product=' . $productId . '&updateproduct';
                    $html .= '<a href="' . $editUrl . '" target="_blank" class="btn btn-xs btn-primary"><i class="icon-pencil"></i> ' . $this->l('Edit') . '</a>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        // Redirects Table
        if ($redirects > 0) {
            $html .= '<h4 style="color:#f39c12;margin-top:20px;"><i class="icon-random"></i> ' . $this->l('Redirects (should be updated)') . '</h4>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . $this->l('Original URL') . '</th>';
            $html .= '<th>' . $this->l('Redirects To') . '</th>';
            $html .= '<th>' . $this->l('Found In') . '</th>';
            $html .= '<th>' . $this->l('Code') . '</th>';
            $html .= '</tr></thead><tbody>';

            foreach (array_slice($results['redirects'], 0, 20) as $redirect) {
                $html .= '<tr>';
                $html .= '<td><code>' . htmlspecialchars($redirect['url']) . '</code></td>';
                $html .= '<td><code>' . htmlspecialchars($redirect['redirect_url']) . '</code></td>';
                $html .= '<td>' . htmlspecialchars($redirect['source_name']) . '</td>';
                $html .= '<td><span class="badge" style="background:#f39c12;">' . $redirect['http_code'] . '</span></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        // All OK message
        if ($brokenLinks === 0 && $brokenImages === 0 && $redirects === 0 && $missingImages === 0) {
            $html .= '<div class="alert alert-success">';
            $html .= '<i class="icon-check-circle"></i> <strong>' . $this->l('All links and images are valid!') . '</strong>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render link checker section
     * @return string
     */
    protected function renderLinkChecker()
    {
        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-unlink"></i> ' . $this->l('Broken Link Checker') . '</h3>';

        $html .= '<div class="alert alert-info">';
        $html .= '<i class="icon-info-circle"></i> ' . $this->l('Scan your product descriptions and CMS pages for broken links, missing images, and redirects that should be updated.');
        $html .= '</div>';

        $html .= '<form method="post" class="form-horizontal">';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Scan Type') . ':</label>';
        $html .= '<div class="col-lg-9">';
        $html .= '<select name="scan_type" class="form-control fixed-width-xl">';
        $html .= '<option value="quick">' . $this->l('Quick Scan - Products updated in last 30 days') . '</option>';
        $html .= '<option value="products">' . $this->l('Products - First 100 products') . '</option>';
        $html .= '<option value="full">' . $this->l('Full Scan - All products (may take time)') . '</option>';
        $html .= '<option value="cms">' . $this->l('CMS Pages - All CMS content') . '</option>';
        $html .= '<option value="images">' . $this->l('Product Images - Check for missing files') . '</option>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<div class="col-lg-offset-3 col-lg-9">';
        $html .= '<button type="submit" name="runLinkChecker" class="btn btn-primary">';
        $html .= '<i class="icon-search"></i> ' . $this->l('Run Link Check');
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</form>';

        $html .= '<hr>';
        $html .= '<h5>' . $this->l('What this tool checks:') . '</h5>';
        $html .= '<ul>';
        $html .= '<li><strong>' . $this->l('Broken Links') . ':</strong> ' . $this->l('Links in product descriptions that return 404 or other errors') . '</li>';
        $html .= '<li><strong>' . $this->l('Broken Images') . ':</strong> ' . $this->l('Images referenced in content that no longer exist') . '</li>';
        $html .= '<li><strong>' . $this->l('Redirects') . ':</strong> ' . $this->l('Links that redirect (301/302) and should be updated to the final URL') . '</li>';
        $html .= '<li><strong>' . $this->l('External Links') . ':</strong> ' . $this->l('Links pointing to other websites') . '</li>';
        $html .= '</ul>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Test product schema action
     * @return string
     */
    protected function testProductSchemaAction()
    {
        $idProduct = (int) Tools::getValue('schema_product_id');

        if ($idProduct <= 0) {
            return $this->displayError($this->l('Please enter a valid product ID.'));
        }

        $validator = new ProSEOMasterSchemaValidator();
        $preview = $validator->generateProductSchemaPreview($idProduct, $this->context->language->id);

        if (isset($preview['error'])) {
            return $this->displayError($preview['error']);
        }

        return $this->renderSchemaPreview($preview, $idProduct);
    }

    /**
     * Audit all schemas action
     * @return string
     */
    protected function auditAllSchemasAction()
    {
        $validator = new ProSEOMasterSchemaValidator();
        $results = $validator->auditAllSchemas();

        return $this->renderSchemaAuditResults($results);
    }

    /**
     * Analyze internal linking action
     * @return string
     */
    protected function analyzeLinkingAction()
    {
        $audit = new ProSEOMasterAudit();
        $results = $audit->analyzeInternalLinking();

        return $this->renderLinkingAnalysis($results);
    }

    /**
     * Render schema preview
     * @param array $preview
     * @param int $idProduct
     * @return string
     */
    protected function renderSchemaPreview($preview, $idProduct)
    {
        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-code"></i> ' . $this->l('Schema Preview') . ' - Product #' . $idProduct . '</h3>';

        // Validation status
        $validation = $preview['validation'];
        if ($validation['valid']) {
            $html .= '<div class="alert alert-success">';
            $html .= '<i class="icon-check-circle"></i> <strong>' . $this->l('Schema is valid!') . '</strong> ';
            $html .= $this->l('Score') . ': ' . $validation['score'] . '/100';
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-danger">';
            $html .= '<i class="icon-times-circle"></i> <strong>' . $this->l('Schema has errors') . '</strong>';
            $html .= '</div>';
        }

        // Errors
        if (!empty($validation['errors'])) {
            $html .= '<h4 style="color:#dd4b39;"><i class="icon-times"></i> ' . $this->l('Errors') . '</h4>';
            $html .= '<ul class="list-unstyled">';
            foreach ($validation['errors'] as $error) {
                $html .= '<li style="color:#dd4b39;"><i class="icon-times"></i> ' . htmlspecialchars($error) . '</li>';
            }
            $html .= '</ul>';
        }

        // Warnings
        if (!empty($validation['warnings'])) {
            $html .= '<h4 style="color:#f39c12;"><i class="icon-warning"></i> ' . $this->l('Warnings') . '</h4>';
            $html .= '<ul class="list-unstyled">';
            foreach ($validation['warnings'] as $warning) {
                $html .= '<li style="color:#f39c12;"><i class="icon-warning"></i> ' . htmlspecialchars($warning) . '</li>';
            }
            $html .= '</ul>';
        }

        // JSON-LD Preview
        $html .= '<h4><i class="icon-code"></i> ' . $this->l('JSON-LD Code') . '</h4>';
        $html .= '<pre style="background:#2d2d2d;color:#f8f8f2;padding:15px;border-radius:4px;overflow-x:auto;max-height:400px;">';
        $html .= '<code>' . htmlspecialchars($preview['json']) . '</code>';
        $html .= '</pre>';

        // Copy button
        $html .= '<button type="button" class="btn btn-default" onclick="navigator.clipboard.writeText(' . htmlspecialchars(json_encode($preview['json']), ENT_QUOTES) . '); alert(\'Copied!\');">';
        $html .= '<i class="icon-copy"></i> ' . $this->l('Copy JSON-LD');
        $html .= '</button>';

        // Test links
        $html .= '<h4 style="margin-top:20px;"><i class="icon-external-link"></i> ' . $this->l('Test with Google Tools') . '</h4>';
        $html .= '<div class="btn-group">';
        foreach ($preview['test_urls'] as $name => $url) {
            $label = str_replace('_', ' ', ucwords($name));
            $html .= '<a href="' . $url . '" target="_blank" class="btn btn-info">';
            $html .= '<i class="icon-external-link"></i> ' . $label;
            $html .= '</a> ';
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render schema audit results
     * @param array $results
     * @return string
     */
    protected function renderSchemaAuditResults($results)
    {
        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-tasks"></i> ' . $this->l('Schema Audit Results') . '</h3>';

        // Stats
        $html .= '<div class="row" style="margin-bottom:20px;">';

        $html .= '<div class="col-lg-4">';
        $html .= '<div class="panel" style="background:#3c8dbc;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $results['products_checked'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Products Checked') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-4">';
        $errorColor = $results['products_with_errors'] > 0 ? '#dd4b39' : '#00a65a';
        $html .= '<div class="panel" style="background:' . $errorColor . ';color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $results['products_with_errors'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('With Errors') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-4">';
        $warnColor = $results['products_with_warnings'] > 0 ? '#f39c12' : '#00a65a';
        $html .= '<div class="panel" style="background:' . $warnColor . ';color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $results['products_with_warnings'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('With Warnings') . '</p>';
        $html .= '</div></div>';

        $html .= '</div>';

        // Common issues
        if (!empty($results['common_issues'])) {
            $html .= '<h4><i class="icon-list"></i> ' . $this->l('Common Issues') . '</h4>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>' . $this->l('Issue') . '</th><th>' . $this->l('Count') . '</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($results['common_issues'] as $issue => $count) {
                $html .= '<tr><td>' . htmlspecialchars($issue) . '</td><td>' . $count . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        // Products needing attention (first 10)
        if (!empty($results['products_needing_attention'])) {
            $html .= '<h4><i class="icon-warning"></i> ' . $this->l('Products Needing Attention') . '</h4>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>ID</th><th>' . $this->l('Product') . '</th><th>' . $this->l('Errors') . '</th></tr></thead>';
            $html .= '<tbody>';
            foreach (array_slice($results['products_needing_attention'], 0, 10) as $product) {
                $html .= '<tr>';
                $html .= '<td>' . $product['id'] . '</td>';
                $html .= '<td>' . htmlspecialchars($product['name']) . '</td>';
                $html .= '<td>' . implode('<br>', array_map('htmlspecialchars', $product['errors'])) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render internal linking analysis
     * @param array $results
     * @return string
     */
    protected function renderLinkingAnalysis($results)
    {
        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-link"></i> ' . $this->l('Internal Linking Analysis') . '</h3>';

        // Stats
        $html .= '<div class="row" style="margin-bottom:20px;">';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#3c8dbc;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $results['stats']['total_products'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Total Products') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $orphanColor = $results['stats']['orphan_count'] > 0 ? '#dd4b39' : '#00a65a';
        $html .= '<div class="panel" style="background:' . $orphanColor . ';color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $results['stats']['orphan_count'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Orphan Products') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#f39c12;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $results['stats']['low_link_count'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Low Link Products') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-3 col-md-6">';
        $html .= '<div class="panel" style="background:#605ca8;color:#fff;text-align:center;padding:15px;">';
        $html .= '<h3 style="margin:0;">' . $results['stats']['avg_internal_links'] . '</h3>';
        $html .= '<p style="margin:5px 0 0;">' . $this->l('Avg Links/Product') . '</p>';
        $html .= '</div></div>';

        $html .= '</div>';

        // Suggestions
        if (!empty($results['suggestions'])) {
            $html .= '<h4><i class="icon-lightbulb-o"></i> ' . $this->l('Suggestions') . '</h4>';
            foreach ($results['suggestions'] as $suggestion) {
                $bgColor = $suggestion['priority'] === 'high' ? '#dd4b39' : ($suggestion['priority'] === 'medium' ? '#f39c12' : '#00a65a');
                $html .= '<div class="alert" style="background:' . $bgColor . ';color:#fff;border:none;">';
                $html .= '<strong>' . htmlspecialchars($suggestion['title']) . '</strong><br>';
                $html .= htmlspecialchars($suggestion['description']) . '<br>';
                $html .= '<em>' . $this->l('Action') . ': ' . htmlspecialchars($suggestion['action']) . '</em>';
                $html .= '</div>';
            }
        }

        // Orphan products (first 10)
        if (!empty($results['orphan_products'])) {
            $html .= '<h4><i class="icon-unlink"></i> ' . $this->l('Orphan Products (no internal links)') . '</h4>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>ID</th><th>' . $this->l('Product') . '</th><th>' . $this->l('Action') . '</th></tr></thead>';
            $html .= '<tbody>';
            foreach (array_slice($results['orphan_products'], 0, 10) as $product) {
                $editUrl = $this->context->link->getAdminLink('AdminProducts') . '&id_product=' . $product['id'] . '&updateproduct';
                $html .= '<tr>';
                $html .= '<td>' . $product['id'] . '</td>';
                $html .= '<td>' . htmlspecialchars($product['name']) . '</td>';
                $html .= '<td><a href="' . $editUrl . '" target="_blank" class="btn btn-xs btn-primary"><i class="icon-pencil"></i> ' . $this->l('Edit') . '</a></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            if (count($results['orphan_products']) > 10) {
                $html .= '<p class="text-muted">' . sprintf($this->l('Showing 10 of %d orphan products'), count($results['orphan_products'])) . '</p>';
            }
        }

        // Top linked products
        if (!empty($results['top_linked_products'])) {
            $html .= '<h4><i class="icon-star"></i> ' . $this->l('Top Linked Products') . '</h4>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>ID</th><th>' . $this->l('Product') . '</th><th>' . $this->l('Links') . '</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($results['top_linked_products'] as $product) {
                $html .= '<tr>';
                $html .= '<td>' . $product['id'] . '</td>';
                $html .= '<td>' . htmlspecialchars($product['name']) . '</td>';
                $html .= '<td><span class="badge" style="background:#00a65a;">' . $product['link_count'] . '</span></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render schema tester section
     * @return string
     */
    protected function renderSchemaTester()
    {
        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-code"></i> ' . $this->l('Structured Data Tester') . '</h3>';

        $html .= '<div class="alert alert-info">';
        $html .= '<i class="icon-info-circle"></i> ' . $this->l('Test your product schemas before publishing. Validate JSON-LD and get links to Google\'s testing tools.');
        $html .= '</div>';

        // Test single product
        $html .= '<div class="panel" style="background:#f9f9f9;">';
        $html .= '<h4><i class="icon-search"></i> ' . $this->l('Test Single Product') . '</h4>';
        $html .= '<form method="post" class="form-inline">';
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label">' . $this->l('Product ID') . ': </label> ';
        $html .= '<input type="number" name="schema_product_id" class="form-control" placeholder="123" style="width:100px;"> ';
        $html .= '<button type="submit" name="testProductSchema" class="btn btn-primary">';
        $html .= '<i class="icon-search"></i> ' . $this->l('Test Schema');
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';

        // Audit all
        $html .= '<div class="row" style="margin-top:20px;">';

        $html .= '<div class="col-lg-6">';
        $html .= '<form method="post">';
        $html .= '<button type="submit" name="auditAllSchemas" class="btn btn-warning btn-lg btn-block">';
        $html .= '<i class="icon-tasks"></i> ' . $this->l('Audit All Product Schemas');
        $html .= '</button>';
        $html .= '<p class="text-muted text-center" style="margin-top:5px;">' . $this->l('Check first 100 products for schema issues') . '</p>';
        $html .= '</form>';
        $html .= '</div>';

        $html .= '<div class="col-lg-6">';
        $html .= '<form method="post">';
        $html .= '<button type="submit" name="analyzeLinking" class="btn btn-info btn-lg btn-block">';
        $html .= '<i class="icon-link"></i> ' . $this->l('Analyze Internal Linking');
        $html .= '</button>';
        $html .= '<p class="text-muted text-center" style="margin-top:5px;">' . $this->l('Find orphan products and linking opportunities') . '</p>';
        $html .= '</form>';
        $html .= '</div>';

        $html .= '</div>';

        // External tools
        $html .= '<hr>';
        $html .= '<h5>' . $this->l('External Testing Tools') . ':</h5>';
        $html .= '<ul>';
        $html .= '<li><a href="https://search.google.com/test/rich-results" target="_blank">' . $this->l('Google Rich Results Test') . '</a> - ' . $this->l('Test any URL for rich results eligibility') . '</li>';
        $html .= '<li><a href="https://validator.schema.org/" target="_blank">' . $this->l('Schema.org Validator') . '</a> - ' . $this->l('Validate JSON-LD syntax and structure') . '</li>';
        $html .= '<li><a href="https://developers.facebook.com/tools/debug/" target="_blank">' . $this->l('Facebook Debugger') . '</a> - ' . $this->l('Test Open Graph tags') . '</li>';
        $html .= '<li><a href="https://cards-dev.twitter.com/validator" target="_blank">' . $this->l('Twitter Card Validator') . '</a> - ' . $this->l('Test Twitter Cards') . '</li>';
        $html .= '</ul>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Export SEO data action
     */
    protected function exportSeoDataAction()
    {
        $bulkEditor = new ProSEOMasterBulkEditor();
        $csv = $bulkEditor->exportSeoData($this->context->language->id);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="seo_data_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        echo $csv;
        exit;
    }

    /**
     * Import SEO data action
     * @return string
     */
    protected function importSeoDataAction()
    {
        if (!isset($_FILES['seo_import_csv']) || $_FILES['seo_import_csv']['error'] !== UPLOAD_ERR_OK) {
            return $this->displayError($this->l('Please upload a valid CSV file.'));
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($_FILES['seo_import_csv']['size'] > $maxSize) {
            return $this->displayError($this->l('File size exceeds maximum allowed (5MB).'));
        }

        // Validate file extension
        $allowedExtensions = array('csv', 'txt');
        $fileExt = strtolower(pathinfo($_FILES['seo_import_csv']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedExtensions)) {
            return $this->displayError($this->l('Invalid file type. Only CSV and TXT files are allowed.'));
        }

        // Validate MIME type
        $allowedMimeTypes = array('text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['seo_import_csv']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return $this->displayError($this->l('Invalid file MIME type. Only CSV files are allowed.'));
        }

        $csvContent = file_get_contents($_FILES['seo_import_csv']['tmp_name']);
        $bulkEditor = new ProSEOMasterBulkEditor();
        $results = $bulkEditor->importFromCsv($csvContent, $this->context->language->id);

        $message = sprintf(
            $this->l('Import completed: %d products updated, %d categories updated, %d errors.'),
            $results['products_updated'],
            $results['categories_updated'],
            $results['errors']
        );

        if ($results['errors'] > 0) {
            return $this->displayWarning($message);
        }

        return $this->displayConfirmation($message);
    }

    /**
     * Render Bulk Editor section
     * @return string
     */
    protected function renderBulkEditor()
    {
        $bulkEditor = new ProSEOMasterBulkEditor();
        $summary = $bulkEditor->getSeoSummary($this->context->language->id);

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-edit"></i> ' . $this->l('SEO Data Manager') . '</h3>';

        $html .= '<div class="alert alert-info">';
        $html .= '<i class="icon-info-circle"></i> ' . $this->l('Export all your SEO data (meta titles, descriptions) to CSV, edit in Excel/Sheets, and import back. Perfect for bulk SEO optimization.');
        $html .= '</div>';

        // Summary Stats
        $html .= '<div class="row" style="margin-bottom:20px;">';

        // Products stats
        $html .= '<div class="col-lg-6">';
        $html .= '<div class="panel" style="background:#f9f9f9;">';
        $html .= '<h4><i class="icon-tag"></i> ' . $this->l('Products') . '</h4>';
        $html .= '<table class="table">';
        $html .= '<tr><td>' . $this->l('Total Products') . '</td><td><strong>' . $summary['products']['total'] . '</strong></td></tr>';
        $html .= '<tr><td><span style="color:#dd4b39;">' . $this->l('Missing Meta Title') . '</span></td><td><strong>' . $summary['products']['missing_title'] . '</strong></td></tr>';
        $html .= '<tr><td><span style="color:#f39c12;">' . $this->l('Missing Meta Description') . '</span></td><td><strong>' . $summary['products']['missing_description'] . '</strong></td></tr>';
        $html .= '</table>';
        $html .= '</div></div>';

        // Categories stats
        $html .= '<div class="col-lg-6">';
        $html .= '<div class="panel" style="background:#f9f9f9;">';
        $html .= '<h4><i class="icon-folder"></i> ' . $this->l('Categories') . '</h4>';
        $html .= '<table class="table">';
        $html .= '<tr><td>' . $this->l('Total Categories') . '</td><td><strong>' . $summary['categories']['total'] . '</strong></td></tr>';
        $html .= '<tr><td><span style="color:#dd4b39;">' . $this->l('Missing Meta Title') . '</span></td><td><strong>' . $summary['categories']['missing_title'] . '</strong></td></tr>';
        $html .= '<tr><td><span style="color:#f39c12;">' . $this->l('Missing Meta Description') . '</span></td><td><strong>' . $summary['categories']['missing_description'] . '</strong></td></tr>';
        $html .= '</table>';
        $html .= '</div></div>';

        $html .= '</div>';

        // Export/Import buttons
        $html .= '<div class="row" style="margin-top:20px;">';

        $html .= '<div class="col-lg-6">';
        $html .= '<div class="panel" style="background:#e8f4e8;border:1px solid #00a65a;">';
        $html .= '<h4><i class="icon-download"></i> ' . $this->l('Export SEO Data') . '</h4>';
        $html .= '<p>' . $this->l('Download all products, categories, and CMS pages with their meta tags in CSV format.') . '</p>';
        $html .= '<form method="post">';
        $html .= '<button type="submit" name="exportSeoData" class="btn btn-success btn-lg">';
        $html .= '<i class="icon-download"></i> ' . $this->l('Export to CSV');
        $html .= '</button>';
        $html .= '</form>';
        $html .= '</div></div>';

        $html .= '<div class="col-lg-6">';
        $html .= '<div class="panel" style="background:#e8f0f4;border:1px solid #3c8dbc;">';
        $html .= '<h4><i class="icon-upload"></i> ' . $this->l('Import SEO Data') . '</h4>';
        $html .= '<p>' . $this->l('Upload a CSV file to bulk update meta tags. Use the same format as the export.') . '</p>';
        $html .= '<form method="post" enctype="multipart/form-data">';
        $html .= '<div class="form-group">';
        $html .= '<input type="file" name="seo_import_csv" class="form-control" accept=".csv" required>';
        $html .= '</div>';
        $html .= '<button type="submit" name="importSeoData" class="btn btn-info btn-lg">';
        $html .= '<i class="icon-upload"></i> ' . $this->l('Import from CSV');
        $html .= '</button>';
        $html .= '</form>';
        $html .= '</div></div>';

        $html .= '</div>';

        // Instructions
        $html .= '<hr>';
        $html .= '<h5>' . $this->l('How to use') . ':</h5>';
        $html .= '<ol>';
        $html .= '<li>' . $this->l('Click "Export to CSV" to download all your SEO data') . '</li>';
        $html .= '<li>' . $this->l('Open the CSV in Excel or Google Sheets') . '</li>';
        $html .= '<li>' . $this->l('Edit the "Meta Title" and "Meta Description" columns') . '</li>';
        $html .= '<li>' . $this->l('Ideal lengths: Title 30-60 chars, Description 70-160 chars') . '</li>';
        $html .= '<li>' . $this->l('Save as CSV and upload using "Import from CSV"') . '</li>';
        $html .= '</ol>';

        $html .= '<div class="alert alert-warning">';
        $html .= '<i class="icon-warning"></i> <strong>' . $this->l('Tip') . ':</strong> ';
        $html .= $this->l('Always export first to have the correct format. Only modify the Meta Title and Meta Description columns.');
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}
