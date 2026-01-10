<?php
/**
 * ProSEOMaster - Upgrade to v2.5.0
 *
 * Adds:
 * - 301 Redirect Manager database table
 * - New hooks for product/category deletion
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade module to version 2.5.0
 * @param Module $module
 * @return bool
 */
function upgrade_module_2_5_0($module)
{
    // Include the redirects class
    require_once dirname(__FILE__) . '/../classes/ProSEOMasterRedirects.php';

    // Install redirects table
    $redirects = new ProSEOMasterRedirects();
    if (!$redirects->install()) {
        return false;
    }

    // Register new hooks
    $hooks = array(
        'actionProductDelete',
        'actionCategoryDelete',
        'actionDispatcher',
    );

    foreach ($hooks as $hook) {
        if (!$module->registerHook($hook)) {
            // Log error but continue
            PrestaShopLogger::addLog(
                'ProSEOMaster: Failed to register hook ' . $hook,
                2,
                null,
                'Module',
                $module->id
            );
        }
    }

    return true;
}
