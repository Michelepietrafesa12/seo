<?php
/**
 * ProSEOMaster - Sitemap Controller
 *
 * Serves the sitemap.xml file or redirects to static file
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 */

class ProSEOMasterSitemapModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    /**
     * Initialize and serve sitemap
     */
    public function init()
    {
        parent::init();

        $sitemapPath = _PS_ROOT_DIR_ . '/sitemap.xml';

        // If static sitemap exists, serve it
        if (file_exists($sitemapPath)) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
            readfile($sitemapPath);
            exit;
        }

        // If not, generate it on the fly
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterSitemap.php';
        $sitemap = new ProSEOMasterSitemap();

        try {
            $sitemap->generateSitemap();

            // Now serve the generated file
            if (file_exists($sitemapPath)) {
                header('Content-Type: application/xml; charset=utf-8');
                header('Cache-Control: public, max-age=3600');
                readfile($sitemapPath);
                exit;
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'ProSEOMaster: Sitemap generation failed - ' . $e->getMessage(),
                3,
                null,
                'ProSEOMaster'
            );
        }

        // Fallback: 404 if sitemap cannot be generated
        header('HTTP/1.1 404 Not Found');
        exit('Sitemap not available. Please generate it from the module configuration.');
    }
}
