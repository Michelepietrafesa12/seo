<?php
/**
 * Pro SEO Module - Controller Admin
 *
 * Gestisce il pannello di amministrazione del modulo SEO
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'proseomodule/classes/ProSeoSitemap.php';

class AdminProSeoController extends ModuleAdminController
{
    /** @var ProSeoModule */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();

        $this->meta_title = $this->l('Pro SEO - Gestione SEO Avanzata');
    }

    /**
     * Init del controller
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Set media per il back office
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(_PS_MODULE_DIR_ . 'proseomodule/views/css/admin.css');
        $this->addJS(_PS_MODULE_DIR_ . 'proseomodule/views/js/admin.js');
    }

    /**
     * Inizializza la toolbar
     */
    public function initToolbar()
    {
        parent::initToolbar();
    }

    /**
     * Inizializza il contenuto della pagina
     */
    public function initContent()
    {
        parent::initContent();

        // Gestisci le azioni
        $this->processActions();

        // Mostra il contenuto
        $this->context->smarty->assign(array(
            'module_dir' => _PS_MODULE_DIR_ . 'proseomodule/',
            'module_path' => $this->module->getPathUri(),
            'ajax_url' => $this->context->link->getAdminLink('AdminProSeo'),
            'token' => Tools::getAdminTokenLite('AdminProSeo'),
            'stats' => $this->getSeoStats(),
            'sitemap_url' => $this->getSitemapUrl(),
            'last_sitemap_update' => $this->getLastSitemapUpdate(),
            'schema_validation_url' => 'https://search.google.com/test/rich-results',
            'ps_shop_url' => $this->context->link->getBaseLink(),
        ));

        $this->content .= $this->renderDashboard();
        $this->context->smarty->assign('content', $this->content);
    }

    /**
     * Processa le azioni
     */
    protected function processActions()
    {
        // Genera sitemap
        if (Tools::isSubmit('generateSitemap')) {
            $this->processGenerateSitemap();
        }

        // Pulisci cache schema
        if (Tools::isSubmit('clearSchemaCache')) {
            $this->processClearSchemaCache();
        }

        // Test schema
        if (Tools::isSubmit('testSchema')) {
            $this->processTestSchema();
        }
    }

    /**
     * Genera la sitemap
     */
    protected function processGenerateSitemap()
    {
        try {
            $sitemap = new ProSeoSitemap($this->context, $this->module);

            if ($sitemap->saveToFile()) {
                $this->confirmations[] = $this->l('Sitemap generata con successo!');
            } else {
                $this->errors[] = $this->l('Errore durante la generazione della sitemap.');
            }
        } catch (Exception $e) {
            $this->errors[] = $this->l('Errore: ') . $e->getMessage();
        }
    }

    /**
     * Pulisce la cache degli schema
     */
    protected function processClearSchemaCache()
    {
        if ($this->module->clearSchemaCache()) {
            $this->confirmations[] = $this->l('Cache schema pulita con successo!');
        } else {
            $this->errors[] = $this->l('Errore durante la pulizia della cache.');
        }
    }

    /**
     * Processa test schema
     */
    protected function processTestSchema()
    {
        $url = Tools::getValue('test_url');
        if (!empty($url)) {
            $validationUrl = 'https://search.google.com/test/rich-results?url=' . urlencode($url);
            Tools::redirect($validationUrl);
        }
    }

    /**
     * Renderizza la dashboard
     *
     * @return string
     */
    protected function renderDashboard()
    {
        $stats = $this->getSeoStats();

        $html = '
        <div class="panel proseo-dashboard">
            <div class="panel-heading">
                <i class="icon-dashboard"></i> ' . $this->l('Dashboard SEO') . '
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="proseo-stat-box">
                        <div class="stat-icon"><i class="icon-tags"></i></div>
                        <div class="stat-value">' . (int) $stats['products_with_ean'] . '/' . (int) $stats['total_products'] . '</div>
                        <div class="stat-label">' . $this->l('Prodotti con EAN') . '</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="proseo-stat-box">
                        <div class="stat-icon"><i class="icon-file-text"></i></div>
                        <div class="stat-value">' . (int) $stats['products_with_description'] . '/' . (int) $stats['total_products'] . '</div>
                        <div class="stat-label">' . $this->l('Prodotti con Descrizione') . '</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="proseo-stat-box">
                        <div class="stat-icon"><i class="icon-picture"></i></div>
                        <div class="stat-value">' . (int) $stats['products_with_images'] . '/' . (int) $stats['total_products'] . '</div>
                        <div class="stat-label">' . $this->l('Prodotti con Immagini') . '</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="proseo-stat-box">
                        <div class="stat-icon"><i class="icon-building"></i></div>
                        <div class="stat-value">' . (int) $stats['products_with_brand'] . '/' . (int) $stats['total_products'] . '</div>
                        <div class="stat-label">' . $this->l('Prodotti con Brand') . '</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-sitemap"></i> ' . $this->l('Sitemap XML') . '
                    </div>
                    <div class="panel-body">
                        <p>' . $this->l('Genera una sitemap XML ottimizzata per i motori di ricerca.') . '</p>
                        <p><strong>' . $this->l('URL Sitemap:') . '</strong> <a href="' . $this->getSitemapUrl() . '" target="_blank">' . $this->getSitemapUrl() . '</a></p>
                        <p><strong>' . $this->l('Ultimo aggiornamento:') . '</strong> ' . $this->getLastSitemapUpdate() . '</p>
                        <form method="post" class="form-horizontal">
                            <button type="submit" name="generateSitemap" class="btn btn-primary">
                                <i class="icon-refresh"></i> ' . $this->l('Genera Sitemap') . '
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-code"></i> ' . $this->l('Cache Schema') . '
                    </div>
                    <div class="panel-body">
                        <p>' . $this->l('Pulisci la cache degli schema markup per rigenerarli.') . '</p>
                        <p><strong>' . $this->l('Schema in cache:') . '</strong> ' . (int) $this->getSchemasCacheCount() . '</p>
                        <form method="post" class="form-horizontal">
                            <button type="submit" name="clearSchemaCache" class="btn btn-warning">
                                <i class="icon-trash"></i> ' . $this->l('Pulisci Cache') . '
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-check-circle"></i> ' . $this->l('Verifica Schema Markup') . '
            </div>
            <div class="panel-body">
                <p>' . $this->l('Verifica che i tuoi schema markup siano corretti usando il Rich Results Test di Google.') . '</p>
                <form method="post" class="form-inline">
                    <div class="form-group">
                        <label for="test_url">' . $this->l('URL da testare:') . '</label>
                        <input type="url" name="test_url" id="test_url" class="form-control" style="width:400px;" placeholder="https://tuosito.com/prodotto" value="' . $this->context->link->getBaseLink() . '">
                    </div>
                    <button type="submit" name="testSchema" class="btn btn-success">
                        <i class="icon-external-link"></i> ' . $this->l('Testa su Google') . '
                    </button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-list-alt"></i> ' . $this->l('Checklist SEO') . '
            </div>
            <div class="panel-body">
                ' . $this->renderSeoChecklist($stats) . '
            </div>
        </div>
        ';

        return $html;
    }

    /**
     * Renderizza la checklist SEO
     *
     * @param array $stats
     * @return string
     */
    protected function renderSeoChecklist($stats)
    {
        $checks = array();

        // Controllo Organization
        $orgName = Configuration::get('PROSEO_ORGANIZATION_NAME');
        $checks[] = array(
            'label' => $this->l('Nome Organizzazione configurato'),
            'status' => !empty($orgName),
            'message' => !empty($orgName) ? $orgName : $this->l('Configura il nome dell\'organizzazione nelle impostazioni del modulo'),
        );

        // Controllo Logo
        $logo = Configuration::get('PROSEO_ORGANIZATION_LOGO');
        $checks[] = array(
            'label' => $this->l('Logo Organizzazione configurato'),
            'status' => !empty($logo),
            'message' => !empty($logo) ? $this->l('Configurato') : $this->l('Configura l\'URL del logo nelle impostazioni del modulo'),
        );

        // Controllo Indirizzo
        $address = Configuration::get('PROSEO_ORGANIZATION_ADDRESS');
        $checks[] = array(
            'label' => $this->l('Indirizzo Organizzazione configurato'),
            'status' => !empty($address),
            'message' => !empty($address) ? $this->l('Configurato') : $this->l('Configura l\'indirizzo nelle impostazioni del modulo'),
        );

        // Controllo Social
        $facebook = Configuration::get('PROSEO_SOCIAL_FACEBOOK');
        $twitter = Configuration::get('PROSEO_SOCIAL_TWITTER');
        $hasSocial = !empty($facebook) || !empty($twitter);
        $checks[] = array(
            'label' => $this->l('Profili Social configurati'),
            'status' => $hasSocial,
            'message' => $hasSocial ? $this->l('Configurato') : $this->l('Configura almeno un profilo social'),
        );

        // Controllo Prodotti con EAN
        $eanPercentage = $stats['total_products'] > 0 ? ($stats['products_with_ean'] / $stats['total_products']) * 100 : 0;
        $checks[] = array(
            'label' => $this->l('Prodotti con codice EAN/GTIN'),
            'status' => $eanPercentage >= 80,
            'message' => sprintf($this->l('%.1f%% dei prodotti hanno EAN'), $eanPercentage),
        );

        // Controllo Prodotti con Immagini
        $imagePercentage = $stats['total_products'] > 0 ? ($stats['products_with_images'] / $stats['total_products']) * 100 : 0;
        $checks[] = array(
            'label' => $this->l('Prodotti con immagini'),
            'status' => $imagePercentage >= 90,
            'message' => sprintf($this->l('%.1f%% dei prodotti hanno immagini'), $imagePercentage),
        );

        // Controllo Prodotti con Descrizione
        $descPercentage = $stats['total_products'] > 0 ? ($stats['products_with_description'] / $stats['total_products']) * 100 : 0;
        $checks[] = array(
            'label' => $this->l('Prodotti con descrizione'),
            'status' => $descPercentage >= 80,
            'message' => sprintf($this->l('%.1f%% dei prodotti hanno descrizione'), $descPercentage),
        );

        // Controllo SSL
        $sslEnabled = Configuration::get('PS_SSL_ENABLED');
        $checks[] = array(
            'label' => $this->l('SSL/HTTPS abilitato'),
            'status' => (bool) $sslEnabled,
            'message' => $sslEnabled ? $this->l('Abilitato') : $this->l('Abilita SSL per migliorare la SEO'),
        );

        // Controllo URL Friendly
        $friendlyUrl = Configuration::get('PS_REWRITING_SETTINGS');
        $checks[] = array(
            'label' => $this->l('URL Friendly abilitati'),
            'status' => (bool) $friendlyUrl,
            'message' => $friendlyUrl ? $this->l('Abilitato') : $this->l('Abilita gli URL Friendly in Preferenze > SEO & URLs'),
        );

        $html = '<table class="table table-striped">';
        $html .= '<thead><tr><th>' . $this->l('Controllo') . '</th><th>' . $this->l('Stato') . '</th><th>' . $this->l('Dettagli') . '</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($checks as $check) {
            $statusIcon = $check['status']
                ? '<span class="label label-success"><i class="icon-check"></i> OK</span>'
                : '<span class="label label-warning"><i class="icon-warning-sign"></i> Attenzione</span>';

            $html .= '<tr>';
            $html .= '<td>' . $check['label'] . '</td>';
            $html .= '<td>' . $statusIcon . '</td>';
            $html .= '<td>' . $check['message'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Ottiene le statistiche SEO
     *
     * @return array
     */
    protected function getSeoStats()
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $stats = array(
            'total_products' => 0,
            'products_with_ean' => 0,
            'products_with_description' => 0,
            'products_with_images' => 0,
            'products_with_brand' => 0,
        );

        // Totale prodotti attivi
        $stats['total_products'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'product` p
            ' . Shop::addSqlAssociation('product', 'p') . '
            WHERE product_shop.active = 1'
        );

        // Prodotti con EAN
        $stats['products_with_ean'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'product` p
            ' . Shop::addSqlAssociation('product', 'p') . '
            WHERE product_shop.active = 1
            AND p.ean13 IS NOT NULL
            AND p.ean13 != \'\''
        );

        // Prodotti con descrizione
        $stats['products_with_description'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT p.id_product)
            FROM `' . _DB_PREFIX_ . 'product` p
            ' . Shop::addSqlAssociation('product', 'p') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop . ')
            WHERE product_shop.active = 1
            AND pl.description_short IS NOT NULL
            AND pl.description_short != \'\'
            AND LENGTH(pl.description_short) > 50'
        );

        // Prodotti con immagini
        $stats['products_with_images'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT p.id_product)
            FROM `' . _DB_PREFIX_ . 'product` p
            ' . Shop::addSqlAssociation('product', 'p') . '
            INNER JOIN `' . _DB_PREFIX_ . 'image` i ON (p.id_product = i.id_product)
            WHERE product_shop.active = 1'
        );

        // Prodotti con brand (manufacturer)
        $stats['products_with_brand'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'product` p
            ' . Shop::addSqlAssociation('product', 'p') . '
            WHERE product_shop.active = 1
            AND p.id_manufacturer > 0'
        );

        return $stats;
    }

    /**
     * Ottiene l'URL della sitemap
     *
     * @return string
     */
    protected function getSitemapUrl()
    {
        return $this->context->link->getBaseLink() . 'sitemap_proseo.xml';
    }

    /**
     * Ottiene la data dell'ultimo aggiornamento della sitemap
     *
     * @return string
     */
    protected function getLastSitemapUpdate()
    {
        $sitemapPath = _PS_ROOT_DIR_ . '/sitemap_proseo.xml';

        if (file_exists($sitemapPath)) {
            return date('d/m/Y H:i:s', filemtime($sitemapPath));
        }

        return $this->l('Mai generata');
    }

    /**
     * Ottiene il numero di schema in cache
     *
     * @return int
     */
    protected function getSchemasCacheCount()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'proseo_schema_cache` WHERE date_expiry > NOW()'
        );
    }

    /**
     * Renderizza la view
     *
     * @return string
     */
    public function renderView()
    {
        return '';
    }
}
