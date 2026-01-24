<?php
/**
 * ProSEOMaster Legacy Admin Controller
 *
 * Main admin controller using PrestaShop's standard getContent() approach
 * More reliable for PS 8.x than Symfony controller for module configuration
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Admin controller for module configuration
 */
class AdminProSEOMasterController extends ModuleAdminController
{
    /** @var bool */
    public $bootstrap = true;

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    /**
     * Redirect to module configuration (getContent)
     */
    public function initContent()
    {
        parent::initContent();

        // Redirect to standard module configuration page
        // This uses getContent() which is the most reliable approach for PS 8.x
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminModules') .
            '&configure=proseomaster&tab_module=seo&module_name=proseomaster'
        );
    }
}
