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

        // Determine which file to serve
        $type = Tools::getValue('type', 'basic');

        // Load AI class
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterAI.php';
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
        exit;
    }
}
