<?php
/**
 * Pro SEO Module - Controller Frontend Sitemap
 *
 * Genera e serve la sitemap XML dinamicamente
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'proseomodule/classes/ProSeoSitemap.php';

class ProSeoModuleSitemapModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    /**
     * Init content
     */
    public function initContent()
    {
        // Non chiamare parent::initContent() per evitare template
        $this->outputSitemap();
    }

    /**
     * Output della sitemap XML
     */
    protected function outputSitemap()
    {
        // Imposta headers
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        header('X-Robots-Tag: noindex');

        // Genera sitemap
        $sitemap = new ProSeoSitemap($this->context, $this->module);
        echo $sitemap->generate();

        exit;
    }
}
