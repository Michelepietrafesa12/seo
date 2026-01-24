<?php
/**
 * ProSEOMaster - Bulk Editor & SEO Data Export
 *
 * Features:
 * - Bulk edit meta tags for products and categories
 * - Export SEO data to CSV/Excel
 * - Import meta tags from CSV
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 * @version     1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterBulkEditor
{
    /** @var Context */
    protected $context;

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Get products with SEO data for bulk editing
     * @param int $idLang
     * @param int $limit
     * @param int $offset
     * @param string $filter
     * @return array
     */
    public function getProductsForBulkEdit($idLang, $limit = 50, $offset = 0, $filter = '')
    {
        $idShop = (int) $this->context->shop->id;

        $sql = 'SELECT p.id_product, pl.name, pl.link_rewrite, pl.meta_title, pl.meta_description,
                       p.reference, p.ean13, p.id_manufacturer,
                       (SELECT name FROM ' . _DB_PREFIX_ . 'manufacturer WHERE id_manufacturer = p.id_manufacturer) as manufacturer_name
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang . ' AND pl.id_shop = ' . $idShop . '
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = ' . $idShop . '
                WHERE ps.active = 1';

        if (!empty($filter)) {
            // Escape LIKE wildcard characters to prevent injection
            $escapedFilter = str_replace(array('%', '_'), array('\\%', '\\_'), $filter);
            $sql .= ' AND (pl.name LIKE "%' . pSQL($escapedFilter) . '%" OR p.reference LIKE "%' . pSQL($escapedFilter) . '%")';
        }

        $sql .= ' ORDER BY p.id_product ASC';
        $sql .= ' LIMIT ' . (int) $offset . ', ' . (int) $limit;

        $products = Db::getInstance()->executeS($sql);

        // Add SEO analysis
        foreach ($products as &$product) {
            $product['meta_title_length'] = strlen($product['meta_title']);
            $product['meta_desc_length'] = strlen($product['meta_description']);
            $product['meta_title_status'] = $this->getTitleStatus($product['meta_title']);
            $product['meta_desc_status'] = $this->getDescriptionStatus($product['meta_description']);
        }

        return $products;
    }

    /**
     * Get categories for bulk edit
     * @param int $idLang
     * @return array
     */
    public function getCategoriesForBulkEdit($idLang)
    {
        $idShop = (int) $this->context->shop->id;

        $sql = 'SELECT c.id_category, cl.name, cl.link_rewrite, cl.meta_title, cl.meta_description, c.level_depth
                FROM ' . _DB_PREFIX_ . 'category c
                INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . $idShop . '
                INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs ON c.id_category = cs.id_category AND cs.id_shop = ' . $idShop . '
                WHERE c.active = 1
                AND c.id_category > 2
                ORDER BY c.level_depth ASC, c.nleft ASC';

        $categories = Db::getInstance()->executeS($sql);

        foreach ($categories as &$cat) {
            $cat['meta_title_length'] = strlen($cat['meta_title']);
            $cat['meta_desc_length'] = strlen($cat['meta_description']);
            $cat['meta_title_status'] = $this->getTitleStatus($cat['meta_title']);
            $cat['meta_desc_status'] = $this->getDescriptionStatus($cat['meta_description']);
        }

        return $categories;
    }

    /**
     * Get title status
     * @param string $title
     * @return string
     */
    protected function getTitleStatus($title)
    {
        $length = strlen($title);

        if ($length === 0) {
            return 'missing';
        } elseif ($length < 30) {
            return 'short';
        } elseif ($length > 60) {
            return 'long';
        } else {
            return 'ok';
        }
    }

    /**
     * Get description status
     * @param string $desc
     * @return string
     */
    protected function getDescriptionStatus($desc)
    {
        $length = strlen($desc);

        if ($length === 0) {
            return 'missing';
        } elseif ($length < 70) {
            return 'short';
        } elseif ($length > 160) {
            return 'long';
        } else {
            return 'ok';
        }
    }

    /**
     * Update product meta tags
     * @param int $idProduct
     * @param int $idLang
     * @param string $metaTitle
     * @param string $metaDescription
     * @param string $linkRewrite
     * @return bool
     */
    public function updateProductMeta($idProduct, $idLang, $metaTitle, $metaDescription, $linkRewrite = null)
    {
        $idShop = (int) $this->context->shop->id;

        $data = array(
            'meta_title' => pSQL($metaTitle),
            'meta_description' => pSQL($metaDescription),
        );

        if ($linkRewrite !== null && !empty($linkRewrite)) {
            $data['link_rewrite'] = pSQL(Tools::str2url($linkRewrite));
        }

        return Db::getInstance()->update(
            'product_lang',
            $data,
            'id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang . ' AND id_shop = ' . $idShop
        );
    }

    /**
     * Update category meta tags
     * @param int $idCategory
     * @param int $idLang
     * @param string $metaTitle
     * @param string $metaDescription
     * @return bool
     */
    public function updateCategoryMeta($idCategory, $idLang, $metaTitle, $metaDescription)
    {
        $idShop = (int) $this->context->shop->id;

        return Db::getInstance()->update(
            'category_lang',
            array(
                'meta_title' => pSQL($metaTitle),
                'meta_description' => pSQL($metaDescription),
            ),
            'id_category = ' . (int) $idCategory . ' AND id_lang = ' . (int) $idLang . ' AND id_shop = ' . $idShop
        );
    }

    /**
     * Bulk update from array
     * @param array $updates Array of [id => [meta_title, meta_description, link_rewrite]]
     * @param string $type 'product' or 'category'
     * @param int $idLang
     * @return int Number of updated items
     */
    public function bulkUpdate($updates, $type, $idLang)
    {
        $count = 0;

        foreach ($updates as $id => $data) {
            $metaTitle = isset($data['meta_title']) ? $data['meta_title'] : '';
            $metaDescription = isset($data['meta_description']) ? $data['meta_description'] : '';
            $linkRewrite = isset($data['link_rewrite']) ? $data['link_rewrite'] : null;

            if ($type === 'product') {
                if ($this->updateProductMeta($id, $idLang, $metaTitle, $metaDescription, $linkRewrite)) {
                    $count++;
                }
            } elseif ($type === 'category') {
                if ($this->updateCategoryMeta($id, $idLang, $metaTitle, $metaDescription)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Export all SEO data to CSV
     * @param int $idLang
     * @return string CSV content
     */
    public function exportSeoData($idLang)
    {
        $idShop = (int) $this->context->shop->id;
        $link = $this->context->link;

        $csv = "Type,ID,Name,URL,Meta Title,Meta Title Length,Meta Description,Meta Desc Length,Reference,EAN13,Brand,Status\n";

        // Products
        $products = Db::getInstance()->executeS(
            'SELECT p.id_product, pl.name, pl.link_rewrite, pl.meta_title, pl.meta_description,
                    p.reference, p.ean13, p.id_manufacturer,
                    (SELECT name FROM ' . _DB_PREFIX_ . 'manufacturer WHERE id_manufacturer = p.id_manufacturer) as manufacturer_name
             FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang . ' AND pl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = ' . $idShop . '
             WHERE ps.active = 1
             ORDER BY p.id_product ASC'
        );

        foreach ($products as $product) {
            $url = $link->getProductLink($product['id_product'], $product['link_rewrite'], null, null, $idLang);
            $titleStatus = $this->getTitleStatus($product['meta_title']);
            $descStatus = $this->getDescriptionStatus($product['meta_description']);
            $status = ($titleStatus === 'ok' && $descStatus === 'ok') ? 'OK' : 'Needs Review';

            $csv .= '"Product",';
            $csv .= $product['id_product'] . ',';
            $csv .= '"' . str_replace('"', '""', $product['name']) . '",';
            $csv .= '"' . $url . '",';
            $csv .= '"' . str_replace('"', '""', $product['meta_title']) . '",';
            $csv .= strlen($product['meta_title']) . ',';
            $csv .= '"' . str_replace('"', '""', $product['meta_description']) . '",';
            $csv .= strlen($product['meta_description']) . ',';
            $csv .= '"' . $product['reference'] . '",';
            $csv .= '"' . $product['ean13'] . '",';
            $csv .= '"' . str_replace('"', '""', $product['manufacturer_name']) . '",';
            $csv .= '"' . $status . '"' . "\n";
        }

        // Categories
        $categories = Db::getInstance()->executeS(
            'SELECT c.id_category, cl.name, cl.link_rewrite, cl.meta_title, cl.meta_description, c.level_depth
             FROM ' . _DB_PREFIX_ . 'category c
             INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs ON c.id_category = cs.id_category AND cs.id_shop = ' . $idShop . '
             WHERE c.active = 1 AND c.id_category > 2
             ORDER BY c.level_depth ASC'
        );

        foreach ($categories as $category) {
            $url = $link->getCategoryLink($category['id_category'], $category['link_rewrite'], $idLang);
            $titleStatus = $this->getTitleStatus($category['meta_title']);
            $descStatus = $this->getDescriptionStatus($category['meta_description']);
            $status = ($titleStatus === 'ok' && $descStatus === 'ok') ? 'OK' : 'Needs Review';

            $csv .= '"Category",';
            $csv .= $category['id_category'] . ',';
            $csv .= '"' . str_replace('"', '""', $category['name']) . '",';
            $csv .= '"' . $url . '",';
            $csv .= '"' . str_replace('"', '""', $category['meta_title']) . '",';
            $csv .= strlen($category['meta_title']) . ',';
            $csv .= '"' . str_replace('"', '""', $category['meta_description']) . '",';
            $csv .= strlen($category['meta_description']) . ',';
            $csv .= '"",'; // No reference
            $csv .= '"",'; // No EAN
            $csv .= '"",'; // No brand
            $csv .= '"' . $status . '"' . "\n";
        }

        // CMS Pages
        $cmsPages = Db::getInstance()->executeS(
            'SELECT c.id_cms, cl.meta_title as name, cl.link_rewrite, cl.meta_title, cl.meta_description
             FROM ' . _DB_PREFIX_ . 'cms c
             INNER JOIN ' . _DB_PREFIX_ . 'cms_lang cl ON c.id_cms = cl.id_cms AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'cms_shop cs ON c.id_cms = cs.id_cms AND cs.id_shop = ' . $idShop . '
             WHERE c.active = 1'
        );

        foreach ($cmsPages as $page) {
            $url = $link->getCMSLink($page['id_cms'], $page['link_rewrite'], null, $idLang);
            $titleStatus = $this->getTitleStatus($page['meta_title']);
            $descStatus = $this->getDescriptionStatus($page['meta_description']);
            $status = ($titleStatus === 'ok' && $descStatus === 'ok') ? 'OK' : 'Needs Review';

            $csv .= '"CMS Page",';
            $csv .= $page['id_cms'] . ',';
            $csv .= '"' . str_replace('"', '""', $page['name']) . '",';
            $csv .= '"' . $url . '",';
            $csv .= '"' . str_replace('"', '""', $page['meta_title']) . '",';
            $csv .= strlen($page['meta_title']) . ',';
            $csv .= '"' . str_replace('"', '""', $page['meta_description']) . '",';
            $csv .= strlen($page['meta_description']) . ',';
            $csv .= '"",'; // No reference
            $csv .= '"",'; // No EAN
            $csv .= '"",'; // No brand
            $csv .= '"' . $status . '"' . "\n";
        }

        return $csv;
    }

    /**
     * Import meta tags from CSV
     * @param string $csvContent
     * @param int $idLang
     * @return array Results
     */
    public function importFromCsv($csvContent, $idLang)
    {
        $results = array(
            'products_updated' => 0,
            'categories_updated' => 0,
            'errors' => 0,
            'messages' => array(),
        );

        $lines = explode("\n", $csvContent);

        foreach ($lines as $lineNum => $line) {
            // Skip header
            if ($lineNum === 0) {
                continue;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = str_getcsv($line);

            if (count($parts) < 6) {
                $results['errors']++;
                continue;
            }

            $type = strtolower(trim($parts[0]));
            $id = (int) $parts[1];
            // Skip name (2), URL (3)
            $metaTitle = trim($parts[4]);
            // Skip title length (5)
            $metaDescription = isset($parts[6]) ? trim($parts[6]) : '';

            if ($type === 'product' && $id > 0) {
                if ($this->updateProductMeta($id, $idLang, $metaTitle, $metaDescription)) {
                    $results['products_updated']++;
                } else {
                    $results['errors']++;
                }
            } elseif ($type === 'category' && $id > 0) {
                if ($this->updateCategoryMeta($id, $idLang, $metaTitle, $metaDescription)) {
                    $results['categories_updated']++;
                } else {
                    $results['errors']++;
                }
            }
        }

        return $results;
    }

    /**
     * Get SEO summary statistics
     * @param int $idLang
     * @return array
     */
    public function getSeoSummary($idLang)
    {
        $idShop = (int) $this->context->shop->id;

        $summary = array(
            'products' => array(
                'total' => 0,
                'missing_title' => 0,
                'missing_description' => 0,
                'short_title' => 0,
                'long_title' => 0,
                'short_description' => 0,
                'long_description' => 0,
                'perfect' => 0,
            ),
            'categories' => array(
                'total' => 0,
                'missing_title' => 0,
                'missing_description' => 0,
                'perfect' => 0,
            ),
        );

        // Products
        $summary['products']['total'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_shop WHERE id_shop = ' . $idShop . ' AND active = 1'
        );

        $summary['products']['missing_title'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = ' . $idShop . '
             WHERE ps.active = 1 AND (pl.meta_title IS NULL OR pl.meta_title = "")'
        );

        $summary['products']['missing_description'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product AND ps.id_shop = ' . $idShop . '
             WHERE ps.active = 1 AND (pl.meta_description IS NULL OR pl.meta_description = "")'
        );

        // Categories
        $summary['categories']['total'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'category_shop cs
             INNER JOIN ' . _DB_PREFIX_ . 'category c ON c.id_category = cs.id_category
             WHERE cs.id_shop = ' . $idShop . ' AND c.active = 1 AND c.id_category > 2'
        );

        $summary['categories']['missing_title'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'category c
             INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = ' . $idLang . ' AND cl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs ON c.id_category = cs.id_category AND cs.id_shop = ' . $idShop . '
             WHERE c.active = 1 AND c.id_category > 2 AND (cl.meta_title IS NULL OR cl.meta_title = "")'
        );

        $summary['categories']['missing_description'] = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'category c
             INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = ' . $idLang . ' AND cl.id_shop = ' . $idShop . '
             INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs ON c.id_category = cs.id_category AND cs.id_shop = ' . $idShop . '
             WHERE c.active = 1 AND c.id_category > 2 AND (cl.meta_description IS NULL OR cl.meta_description = "")'
        );

        return $summary;
    }

    /**
     * Get total product count
     * @return int
     */
    public function getProductCount()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_shop
             WHERE id_shop = ' . (int) $this->context->shop->id . ' AND active = 1'
        );
    }
}
