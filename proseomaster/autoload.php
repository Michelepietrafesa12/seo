<?php
/**
 * ProSEOMaster Autoloader
 *
 * PSR-4 autoloader for module namespaced classes
 * This file should be included early to ensure classes are available
 * for Symfony DI container compilation
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Prevent double registration
if (!defined('PROSEOMASTER_AUTOLOADER_REGISTERED')) {
    define('PROSEOMASTER_AUTOLOADER_REGISTERED', true);

    spl_autoload_register(function ($class) {
        // ProSEOMaster namespace
        $prefix = 'ProSEOMaster\\';
        $baseDir = __DIR__ . '/src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}
