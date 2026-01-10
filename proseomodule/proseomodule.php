<?php
/**
 * Pro SEO Module - Modulo SEO Professionale per PrestaShop 1.7
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 * @version     1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/ProSeoSchema.php';
require_once dirname(__FILE__) . '/classes/ProSeoMetaTags.php';
require_once dirname(__FILE__) . '/classes/ProSeoBreadcrumb.php';
require_once dirname(__FILE__) . '/classes/ProSeoSitemap.php';

class ProSeoModule extends Module
{
    /** @var array Configurazione di default */
    protected $defaultConfig = array(
        'PROSEO_ENABLE_PRODUCT_SCHEMA' => 1,
        'PROSEO_ENABLE_BREADCRUMB_SCHEMA' => 1,
        'PROSEO_ENABLE_ORGANIZATION_SCHEMA' => 1,
        'PROSEO_ENABLE_LOCAL_BUSINESS' => 0,
        'PROSEO_ENABLE_META_OG' => 1,
        'PROSEO_ENABLE_META_TWITTER' => 1,
        'PROSEO_ENABLE_CANONICAL' => 1,
        'PROSEO_ENABLE_HREFLANG' => 1,
        'PROSEO_ORGANIZATION_NAME' => '',
        'PROSEO_ORGANIZATION_LOGO' => '',
        'PROSEO_ORGANIZATION_PHONE' => '',
        'PROSEO_ORGANIZATION_EMAIL' => '',
        'PROSEO_ORGANIZATION_ADDRESS' => '',
        'PROSEO_ORGANIZATION_CITY' => '',
        'PROSEO_ORGANIZATION_POSTAL_CODE' => '',
        'PROSEO_ORGANIZATION_COUNTRY' => '',
        'PROSEO_SOCIAL_FACEBOOK' => '',
        'PROSEO_SOCIAL_TWITTER' => '',
        'PROSEO_SOCIAL_INSTAGRAM' => '',
        'PROSEO_SOCIAL_LINKEDIN' => '',
        'PROSEO_SOCIAL_YOUTUBE' => '',
        'PROSEO_SOCIAL_PINTEREST' => '',
        'PROSEO_TWITTER_SITE' => '',
        'PROSEO_DEFAULT_PRODUCT_CONDITION' => 'NewCondition',
        'PROSEO_ENABLE_AGGREGATE_RATING' => 1,
        'PROSEO_ENABLE_OFFERS' => 1,
        'PROSEO_PRICE_VALID_UNTIL_DAYS' => 30,
        'PROSEO_ENABLE_GTIN' => 1,
        'PROSEO_ENABLE_MPN' => 1,
        'PROSEO_ENABLE_BRAND' => 1,
        'PROSEO_ROBOTS_INDEX_PRODUCTS' => 1,
        'PROSEO_ROBOTS_INDEX_CATEGORIES' => 1,
        'PROSEO_ROBOTS_INDEX_CMS' => 1,
    );

    public function __construct()
    {
        $this->name = 'proseomodule';
        $this->tab = 'seo';
        $this->version = '1.0.0';
        $this->author = 'Pro SEO Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => '1.7.99.99');
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Pro SEO Module');
        $this->description = $this->l('Modulo SEO professionale con schema markup, meta tags ottimizzati, Open Graph, Twitter Cards e gestione completa SEO per e-commerce.');
        $this->confirmUninstall = $this->l('Sei sicuro di voler disinstallare questo modulo? Tutte le configurazioni SEO andranno perse.');
    }

    /**
     * Installazione del modulo
     */
    public function install()
    {
        // Installazione SQL
        if (!$this->installSql()) {
            return false;
        }

        // Configurazione di default
        foreach ($this->defaultConfig as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        // Installazione hooks
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayAfterBodyOpeningTag')
            && $this->registerHook('displayFooterBefore')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionCategoryUpdate')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('moduleRoutes')
            && $this->installTab();
    }

    /**
     * Disinstallazione del modulo
     */
    public function uninstall()
    {
        // Rimozione configurazioni
        foreach (array_keys($this->defaultConfig) as $key) {
            Configuration::deleteByName($key);
        }

        return $this->uninstallSql()
            && $this->uninstallTab()
            && parent::uninstall();
    }

    /**
     * Installazione SQL
     */
    protected function installSql()
    {
        $sql = array();

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'proseo_custom_meta` (
            `id_proseo_meta` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_entity` INT(11) UNSIGNED NOT NULL,
            `entity_type` VARCHAR(50) NOT NULL,
            `id_lang` INT(11) UNSIGNED NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            `custom_title` VARCHAR(255) DEFAULT NULL,
            `custom_description` TEXT DEFAULT NULL,
            `custom_keywords` VARCHAR(255) DEFAULT NULL,
            `robots_index` TINYINT(1) DEFAULT 1,
            `robots_follow` TINYINT(1) DEFAULT 1,
            `canonical_url` VARCHAR(512) DEFAULT NULL,
            `og_title` VARCHAR(255) DEFAULT NULL,
            `og_description` TEXT DEFAULT NULL,
            `og_image` VARCHAR(512) DEFAULT NULL,
            `twitter_title` VARCHAR(255) DEFAULT NULL,
            `twitter_description` TEXT DEFAULT NULL,
            `twitter_image` VARCHAR(512) DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_proseo_meta`),
            KEY `entity_idx` (`id_entity`, `entity_type`, `id_lang`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'proseo_redirects` (
            `id_redirect` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `old_url` VARCHAR(512) NOT NULL,
            `new_url` VARCHAR(512) NOT NULL,
            `redirect_type` INT(3) DEFAULT 301,
            `hits` INT(11) DEFAULT 0,
            `active` TINYINT(1) DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_redirect`),
            UNIQUE KEY `old_url_idx` (`old_url`(255))
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'proseo_schema_cache` (
            `id_cache` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `cache_key` VARCHAR(255) NOT NULL,
            `schema_data` LONGTEXT NOT NULL,
            `date_add` DATETIME NOT NULL,
            `date_expiry` DATETIME NOT NULL,
            PRIMARY KEY (`id_cache`),
            UNIQUE KEY `cache_key_idx` (`cache_key`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Disinstallazione SQL
     */
    protected function uninstallSql()
    {
        $sql = array(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'proseo_custom_meta`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'proseo_redirects`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'proseo_schema_cache`',
        );

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Installazione Tab Admin
     */
    protected function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminProSeo';
        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Pro SEO';
        }

        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * Disinstallazione Tab Admin
     */
    protected function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminProSeo');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * Configurazione del modulo nel Back Office
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitProSeoModule')) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderForm();
    }

    /**
     * Salvataggio configurazione
     */
    protected function postProcess()
    {
        $output = '';
        $errors = array();

        // Salvataggio configurazioni
        foreach (array_keys($this->defaultConfig) as $key) {
            $value = Tools::getValue($key);
            if (!Configuration::updateValue($key, $value)) {
                $errors[] = $this->l('Errore nel salvataggio di: ') . $key;
            }
        }

        if (count($errors)) {
            $output .= $this->displayError(implode('<br>', $errors));
        } else {
            $output .= $this->displayConfirmation($this->l('Configurazione salvata con successo!'));

            // Pulisci cache schema
            $this->clearSchemaCache();
        }

        return $output;
    }

    /**
     * Pulisci cache schema
     */
    public function clearSchemaCache()
    {
        return Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'proseo_schema_cache`');
    }

    /**
     * Renderizza il form di configurazione
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitProSeoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigForm());
    }

    /**
     * Struttura del form di configurazione
     */
    protected function getConfigForm()
    {
        $form = array();

        // Tab Schema Markup
        $form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Schema Markup - Dati Strutturati'),
                    'icon' => 'icon-code',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Abilita Schema Product'),
                        'name' => 'PROSEO_ENABLE_PRODUCT_SCHEMA',
                        'desc' => $this->l('Genera automaticamente lo schema Product per tutti i prodotti (JSON-LD)'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Abilita Schema Breadcrumb'),
                        'name' => 'PROSEO_ENABLE_BREADCRUMB_SCHEMA',
                        'desc' => $this->l('Genera automaticamente lo schema BreadcrumbList'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Abilita Schema Organization'),
                        'name' => 'PROSEO_ENABLE_ORGANIZATION_SCHEMA',
                        'desc' => $this->l('Aggiunge schema Organization nella homepage'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Abilita Schema LocalBusiness'),
                        'name' => 'PROSEO_ENABLE_LOCAL_BUSINESS',
                        'desc' => $this->l('Usa LocalBusiness invece di Organization (per negozi fisici)'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Includi Aggregate Rating'),
                        'name' => 'PROSEO_ENABLE_AGGREGATE_RATING',
                        'desc' => $this->l('Include le recensioni aggregate nello schema prodotto (se disponibili)'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Includi GTIN/EAN'),
                        'name' => 'PROSEO_ENABLE_GTIN',
                        'desc' => $this->l('Include il codice GTIN/EAN nello schema prodotto'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Includi MPN'),
                        'name' => 'PROSEO_ENABLE_MPN',
                        'desc' => $this->l('Include il codice MPN (Manufacturer Part Number) nello schema'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Includi Brand'),
                        'name' => 'PROSEO_ENABLE_BRAND',
                        'desc' => $this->l('Include il brand/produttore nello schema prodotto'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Condizione Prodotto Default'),
                        'name' => 'PROSEO_DEFAULT_PRODUCT_CONDITION',
                        'desc' => $this->l('Condizione di default per i prodotti nello schema'),
                        'options' => array(
                            'query' => array(
                                array('id' => 'NewCondition', 'name' => $this->l('Nuovo')),
                                array('id' => 'UsedCondition', 'name' => $this->l('Usato')),
                                array('id' => 'RefurbishedCondition', 'name' => $this->l('Ricondizionato')),
                                array('id' => 'DamagedCondition', 'name' => $this->l('Danneggiato')),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Validità Prezzo (giorni)'),
                        'name' => 'PROSEO_PRICE_VALID_UNTIL_DAYS',
                        'desc' => $this->l('Numero di giorni di validità del prezzo nello schema Offer'),
                        'class' => 'fixed-width-sm',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Salva'),
                ),
            ),
        );

        // Tab Organizzazione
        $form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Informazioni Organizzazione'),
                    'icon' => 'icon-building',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Nome Azienda'),
                        'name' => 'PROSEO_ORGANIZATION_NAME',
                        'desc' => $this->l('Nome ufficiale dell\'azienda'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('URL Logo'),
                        'name' => 'PROSEO_ORGANIZATION_LOGO',
                        'desc' => $this->l('URL completo del logo aziendale (consigliato: 600x60px, max 250KB)'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Telefono'),
                        'name' => 'PROSEO_ORGANIZATION_PHONE',
                        'desc' => $this->l('Numero di telefono principale'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Email'),
                        'name' => 'PROSEO_ORGANIZATION_EMAIL',
                        'desc' => $this->l('Email di contatto principale'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Indirizzo'),
                        'name' => 'PROSEO_ORGANIZATION_ADDRESS',
                        'desc' => $this->l('Via e numero civico'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Città'),
                        'name' => 'PROSEO_ORGANIZATION_CITY',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('CAP'),
                        'name' => 'PROSEO_ORGANIZATION_POSTAL_CODE',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Paese'),
                        'name' => 'PROSEO_ORGANIZATION_COUNTRY',
                        'desc' => $this->l('Codice paese ISO (es: IT, US, DE)'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Salva'),
                ),
            ),
        );

        // Tab Social
        $form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Profili Social'),
                    'icon' => 'icon-share-alt',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Facebook'),
                        'name' => 'PROSEO_SOCIAL_FACEBOOK',
                        'desc' => $this->l('URL completo della pagina Facebook'),
                        'prefix' => '<i class="icon-facebook"></i>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Twitter/X'),
                        'name' => 'PROSEO_SOCIAL_TWITTER',
                        'desc' => $this->l('URL completo del profilo Twitter/X'),
                        'prefix' => '<i class="icon-twitter"></i>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Twitter @username'),
                        'name' => 'PROSEO_TWITTER_SITE',
                        'desc' => $this->l('Username Twitter per le Twitter Cards (es: @tuostore)'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Instagram'),
                        'name' => 'PROSEO_SOCIAL_INSTAGRAM',
                        'desc' => $this->l('URL completo del profilo Instagram'),
                        'prefix' => '<i class="icon-instagram"></i>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('LinkedIn'),
                        'name' => 'PROSEO_SOCIAL_LINKEDIN',
                        'desc' => $this->l('URL completo della pagina LinkedIn'),
                        'prefix' => '<i class="icon-linkedin"></i>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('YouTube'),
                        'name' => 'PROSEO_SOCIAL_YOUTUBE',
                        'desc' => $this->l('URL completo del canale YouTube'),
                        'prefix' => '<i class="icon-youtube"></i>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Pinterest'),
                        'name' => 'PROSEO_SOCIAL_PINTEREST',
                        'desc' => $this->l('URL completo del profilo Pinterest'),
                        'prefix' => '<i class="icon-pinterest"></i>',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Salva'),
                ),
            ),
        );

        // Tab Meta Tags
        $form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Meta Tags e Open Graph'),
                    'icon' => 'icon-tags',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Abilita Open Graph'),
                        'name' => 'PROSEO_ENABLE_META_OG',
                        'desc' => $this->l('Genera automaticamente i meta tag Open Graph per Facebook'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Abilita Twitter Cards'),
                        'name' => 'PROSEO_ENABLE_META_TWITTER',
                        'desc' => $this->l('Genera automaticamente i meta tag per Twitter Cards'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Abilita Canonical URL'),
                        'name' => 'PROSEO_ENABLE_CANONICAL',
                        'desc' => $this->l('Gestione automatica dei tag canonical per evitare contenuti duplicati'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Abilita Hreflang'),
                        'name' => 'PROSEO_ENABLE_HREFLANG',
                        'desc' => $this->l('Genera automaticamente i tag hreflang per siti multilingua'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Salva'),
                ),
            ),
        );

        // Tab Robots
        $form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Impostazioni Robots'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Index Pagine Prodotto'),
                        'name' => 'PROSEO_ROBOTS_INDEX_PRODUCTS',
                        'desc' => $this->l('Permetti ai motori di ricerca di indicizzare le pagine prodotto'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Index Pagine Categoria'),
                        'name' => 'PROSEO_ROBOTS_INDEX_CATEGORIES',
                        'desc' => $this->l('Permetti ai motori di ricerca di indicizzare le pagine categoria'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Index Pagine CMS'),
                        'name' => 'PROSEO_ROBOTS_INDEX_CMS',
                        'desc' => $this->l('Permetti ai motori di ricerca di indicizzare le pagine CMS'),
                        'is_bool' => true,
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Salva'),
                ),
            ),
        );

        return $form;
    }

    /**
     * Valori correnti della configurazione
     */
    protected function getConfigFormValues()
    {
        $values = array();

        foreach (array_keys($this->defaultConfig) as $key) {
            $values[$key] = Configuration::get($key);
        }

        return $values;
    }

    /**
     * Hook Header - Inserisce tutti i meta tags e schema markup
     */
    public function hookDisplayHeader($params)
    {
        $output = '';
        $controller = $this->context->controller;
        $controllerName = get_class($controller);

        // Meta Tags e Open Graph
        $metaTags = new ProSeoMetaTags($this->context, $this);
        $output .= $metaTags->generateMetaTags();

        // Schema Markup
        $schema = new ProSeoSchema($this->context, $this);

        // Schema Organization (solo homepage)
        if ($controllerName === 'IndexController') {
            if (Configuration::get('PROSEO_ENABLE_ORGANIZATION_SCHEMA')) {
                $output .= $schema->getOrganizationSchema();
            }
            $output .= $schema->getWebSiteSchema();
        }

        // Schema Product (pagina prodotto)
        if ($controllerName === 'ProductController') {
            if (Configuration::get('PROSEO_ENABLE_PRODUCT_SCHEMA')) {
                $output .= $schema->getProductSchema();
            }
        }

        // Schema Category (pagina categoria)
        if ($controllerName === 'CategoryController') {
            $output .= $schema->getCategorySchema();
        }

        // Schema Breadcrumb
        if (Configuration::get('PROSEO_ENABLE_BREADCRUMB_SCHEMA')) {
            $breadcrumb = new ProSeoBreadcrumb($this->context, $this);
            $output .= $breadcrumb->generateBreadcrumbSchema();
        }

        // Hreflang per multilang
        if (Configuration::get('PROSEO_ENABLE_HREFLANG')) {
            $output .= $metaTags->generateHreflang();
        }

        return $output;
    }

    /**
     * Hook after body opening tag (per noscript fallback)
     */
    public function hookDisplayAfterBodyOpeningTag($params)
    {
        return '';
    }

    /**
     * Hook footer before
     */
    public function hookDisplayFooterBefore($params)
    {
        return '';
    }

    /**
     * Hook aggiornamento prodotto - invalida cache
     */
    public function hookActionProductUpdate($params)
    {
        if (isset($params['id_product'])) {
            $this->invalidateProductCache((int)$params['id_product']);
        }
    }

    /**
     * Hook aggiornamento categoria - invalida cache
     */
    public function hookActionCategoryUpdate($params)
    {
        if (isset($params['category']) && $params['category'] instanceof Category) {
            $this->invalidateCategoryCache((int)$params['category']->id);
        }
    }

    /**
     * Invalida cache prodotto
     */
    protected function invalidateProductCache($idProduct)
    {
        $cacheKey = 'product_schema_' . $idProduct . '_%';
        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'proseo_schema_cache` WHERE `cache_key` LIKE \'' . pSQL($cacheKey) . '\''
        );
    }

    /**
     * Invalida cache categoria
     */
    protected function invalidateCategoryCache($idCategory)
    {
        $cacheKey = 'category_schema_' . $idCategory . '_%';
        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'proseo_schema_cache` WHERE `cache_key` LIKE \'' . pSQL($cacheKey) . '\''
        );
    }

    /**
     * Hook Back Office Header
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    /**
     * Routes del modulo
     */
    public function hookModuleRoutes()
    {
        return array(
            'proseo-sitemap' => array(
                'controller' => 'sitemap',
                'rule' => 'proseo-sitemap.xml',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'proseomodule',
                ),
            ),
        );
    }

    /**
     * Ottieni URL base del negozio
     */
    public function getShopUrl()
    {
        return $this->context->link->getBaseLink();
    }

    /**
     * Ottieni configurazione
     */
    public function getConfig($key)
    {
        return Configuration::get($key);
    }
}
