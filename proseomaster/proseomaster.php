<?php
/**
 * ProSEOMaster - Professional SEO Module for PrestaShop 1.7
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 * @version     2.1.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Include helper classes
require_once dirname(__FILE__) . '/classes/ProSEOMasterHelper.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterSitemap.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterRobots.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterMeta.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterSchemaAdvanced.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterPerformance.php';
require_once dirname(__FILE__) . '/classes/ProSEOMasterAudit.php';

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
    );

    public function __construct()
    {
        $this->name = 'proseomaster';
        $this->tab = 'seo';
        $this->version = '2.1.0';
        $this->author = 'SEO Expert';
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
            'PROSEOMASTER_ENABLE_CRITICAL_CSS' => 1,
            'PROSEOMASTER_ENABLE_DEFER_JS' => 1,
            'PROSEOMASTER_ENABLE_IMAGE_DIMENSIONS' => 1,
            'PROSEOMASTER_ENABLE_FONT_OPTIMIZATION' => 1,
            'PROSEOMASTER_ENABLE_IFRAME_OPTIMIZATION' => 1,
            'PROSEOMASTER_AUTO_GENERATE_SITEMAP' => 0,
        );

        foreach ($defaultConfig as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayAfterBodyOpeningTag') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('actionOutputHTMLBefore') &&
            $this->registerHook('moduleRoutes') &&
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
     * Module configuration page
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

        // Render dashboard + forms
        return $output . $this->renderDashboard() . $this->renderForm() . $this->renderAdvancedForm();
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

        $title = $this->context->smarty->tpl_vars['page']->value['meta']['title'] ?? Configuration::get('PS_SHOP_NAME');
        $description = $this->context->smarty->tpl_vars['page']->value['meta']['description'] ?? '';
        $url = $this->getCurrentUrl();
        $image = Configuration::get('PROSEOMASTER_DEFAULT_OG_IMAGE');
        $siteName = Configuration::get('PS_SHOP_NAME');

        // Get product-specific data
        if ($page === 'product' && isset($this->context->smarty->tpl_vars['product'])) {
            $product = $this->context->smarty->tpl_vars['product']->value;
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
            $category = $this->context->smarty->tpl_vars['category']->value;
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

        foreach ($languages as $lang) {
            $hreflang = $lang['language_code'];
            $url = $this->getAlternateUrl($lang['id_lang'], $page);
            if ($url) {
                $output .= '<link rel="alternate" hreflang="' . $hreflang . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            }
        }

        // Add x-default
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $defaultUrl = $this->getAlternateUrl($defaultLang, $page);
        if ($defaultUrl) {
            $output .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($defaultUrl, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        }

        return $output;
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
                    $product = $this->context->smarty->tpl_vars['product']->value;
                    if (is_array($product) && isset($product['id_product'])) {
                        return $link->getProductLink($product['id_product'], null, null, null, $idLang);
                    }
                }
                break;
            case 'category':
                if (isset($this->context->smarty->tpl_vars['category'])) {
                    $category = $this->context->smarty->tpl_vars['category']->value;
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

        $breadcrumb = $this->context->smarty->tpl_vars['breadcrumb']->value;
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

        $productData = $this->context->smarty->tpl_vars['product']->value;
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

        return $schema;
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
     * @param Product $product
     * @return array
     */
    protected function generateReviewSchema($product)
    {
        $result = array();

        // Check if product comments module is active
        if (!Module::isEnabled('productcomments')) {
            return $result;
        }

        // Get average rating and review count
        $avgRating = null;
        $reviewCount = 0;

        // Try to get data from productcomments module
        if (class_exists('ProductComment')) {
            $avgRating = ProductComment::getAverageGrade($product->id);
            $reviewCount = ProductComment::getCommentNumber($product->id);
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
            );
        }

        // Get individual reviews (limit to 10 for performance)
        if (class_exists('ProductComment') && $reviewCount > 0) {
            $reviews = ProductComment::getByProduct($product->id, 1, 10, true);
            if (!empty($reviews)) {
                $result['review'] = array();
                foreach ($reviews as $review) {
                    $result['review'][] = array(
                        '@type' => 'Review',
                        'reviewRating' => array(
                            '@type' => 'Rating',
                            'ratingValue' => (int) $review['grade'],
                            'bestRating' => '5',
                            'worstRating' => '1',
                        ),
                        'author' => array(
                            '@type' => 'Person',
                            'name' => $review['customer_name'],
                        ),
                        'datePublished' => date('Y-m-d', strtotime($review['date_add'])),
                        'reviewBody' => $this->cleanText($review['content']),
                    );
                }
            }
        }

        return $result;
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

        $listing = $this->context->smarty->tpl_vars['listing']->value;
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
            $category = $this->context->smarty->tpl_vars['category']->value;
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
        $protocol = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
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
        $performance = new ProSEOMasterPerformance();

        // 1. Add lazy loading to images (except above-the-fold)
        if (Configuration::get('PROSEOMASTER_ENABLE_LAZY_LOADING')) {
            $html = $performance->addLazyLoading($html);
        }

        // 2. Add explicit dimensions to images (prevents CLS)
        if (Configuration::get('PROSEOMASTER_ENABLE_IMAGE_DIMENSIONS')) {
            $html = $performance->addImageDimensions($html);
        }

        // 3. Defer non-critical JavaScript
        if (Configuration::get('PROSEOMASTER_ENABLE_DEFER_JS')) {
            $html = $performance->deferJavaScript($html);
        }

        // 4. Optimize iframes (lazy load, add dimensions)
        if (Configuration::get('PROSEOMASTER_ENABLE_IFRAME_OPTIMIZATION')) {
            $html = $performance->optimizeIframes($html);
        }

        // 5. Add fetchpriority to LCP candidates
        if (Configuration::get('PROSEOMASTER_ENABLE_LAZY_LOADING')) {
            $html = $performance->addFetchPriority($html);
        }

        // 6. Optimize font loading (non-blocking Google Fonts)
        if (Configuration::get('PROSEOMASTER_ENABLE_FONT_OPTIMIZATION')) {
            $html = $performance->inlinePreloadFonts($html);
        }

        // 7. Add inline performance script (before </body>)
        if (Configuration::get('PROSEOMASTER_ENABLE_RESOURCE_HINTS')) {
            $performanceScript = $performance->getPerformanceScript();
            $html = str_replace('</body>', $performanceScript . "\n</body>", $html);
        }

        $params['html'] = $html;
    }

    /**
     * Hook: moduleRoutes
     * Add custom routes for sitemap access
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
        );
    }
}
