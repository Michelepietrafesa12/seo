<?php
/**
 * ProSEOMaster - LLMs.txt Controller
 *
 * Serves llms.txt and llms-full.txt files for AI crawlers
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 */

class ProSEOMasterLlmsModuleFrontController extends ModuleFrontController
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

        // Check if AI SEO is enabled
        if (!Configuration::get('PROSEOMASTER_ENABLE_LLMS_TXT')) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        // Determine which file to serve - validate input
        $type = Tools::getValue('type', 'basic');
        if (!in_array($type, array('basic', 'full'), true)) {
            $type = 'basic';
        }

        // Load AI class
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterAI.php';

        try {
            $ai = new ProSEOMasterAI();

            // Generate content
            if ($type === 'full') {
                $content = $ai->generateLlmsFullTxt();
            } else {
                $content = $ai->generateLlmsTxt();
            }

            // Set headers
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: public, max-age=86400'); // Cache for 24 hours
            header('X-Robots-Tag: noindex'); // Don't index the raw file

            // Output and exit
            echo $content;
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'ProSEOMaster llms.txt generation error: ' . $e->getMessage(),
                3,
                null,
                'ProSEOMaster'
            );
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Error generating content';
        }
        exit;
    }
}
