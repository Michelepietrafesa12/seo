<?php
/**
 * ProSEOMaster Admin Controller
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminProSEOMasterController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Redirect to module configuration page
     */
    public function initContent()
    {
        parent::initContent();

        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminModules') .
            '&configure=proseomaster&tab_module=seo&module_name=proseomaster'
        );
    }
}
