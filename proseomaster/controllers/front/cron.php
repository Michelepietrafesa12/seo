<?php
/**
 * ProSEOMaster - Cron Controller
 *
 * Handles automated sitemap generation via cron job
 * URL: /module/proseomaster/cron?action=sitemap&token=YOUR_TOKEN
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 */

class ProSEOMasterCronModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    /** @var bool */
    public $display_column_left = false;

    /** @var bool */
    public $display_column_right = false;

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();

        // Verify token
        $token = Tools::getValue('token');
        $storedToken = Configuration::get('PROSEOMASTER_CRON_TOKEN');

        if (empty($token) || $token !== $storedToken) {
            $this->outputJson(array(
                'success' => false,
                'error' => 'Invalid or missing token',
            ), 403);
        }

        // Get action
        $action = Tools::getValue('action');

        switch ($action) {
            case 'sitemap':
                $this->generateSitemap();
                break;

            case 'robots':
                $this->generateRobots();
                break;

            default:
                $this->outputJson(array(
                    'success' => false,
                    'error' => 'Invalid action. Use: sitemap, robots',
                ), 400);
        }
    }

    /**
     * Generate sitemap
     */
    protected function generateSitemap()
    {
        try {
            require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterSitemap.php';

            $sitemap = new ProSEOMasterSitemap();
            $result = $sitemap->generate();

            if ($result) {
                // Update last generated timestamp
                Configuration::updateValue('PROSEOMASTER_SITEMAP_LAST_GENERATED', date('Y-m-d H:i:s'));

                $this->outputJson(array(
                    'success' => true,
                    'message' => 'Sitemap generated successfully',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'sitemap_url' => $this->context->link->getPageLink('index', true) . 'sitemap.xml',
                ));
            } else {
                $this->outputJson(array(
                    'success' => false,
                    'error' => 'Failed to generate sitemap',
                ), 500);
            }
        } catch (Exception $e) {
            $this->outputJson(array(
                'success' => false,
                'error' => $e->getMessage(),
            ), 500);
        }
    }

    /**
     * Generate robots.txt
     */
    protected function generateRobots()
    {
        try {
            require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterRobots.php';

            $robots = new ProSEOMasterRobots();
            $result = $robots->generate();

            if ($result) {
                $this->outputJson(array(
                    'success' => true,
                    'message' => 'Robots.txt generated successfully',
                    'timestamp' => date('Y-m-d H:i:s'),
                ));
            } else {
                $this->outputJson(array(
                    'success' => false,
                    'error' => 'Failed to generate robots.txt',
                ), 500);
            }
        } catch (Exception $e) {
            $this->outputJson(array(
                'success' => false,
                'error' => $e->getMessage(),
            ), 500);
        }
    }

    /**
     * Output JSON response and exit
     * @param array $data
     * @param int $httpCode
     */
    protected function outputJson($data, $httpCode = 200)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
