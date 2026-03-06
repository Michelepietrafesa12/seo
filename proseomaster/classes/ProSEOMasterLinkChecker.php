<?php
/**
 * ProSEOMaster - Broken Link Checker
 *
 * Scans product descriptions and CMS pages for:
 * - Broken internal links (404)
 * - Broken external links
 * - Missing images
 * - Redirects (301/302)
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 * @version     1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterLinkChecker
{
    /** @var Context */
    protected $context;

    /** @var int Timeout for link checking in seconds */
    protected $timeout = 5;

    /** @var array Cache for checked URLs */
    protected $cache = array();

    /** @var array Results of the scan */
    protected $results = array();

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Run full link check on all products
     * @param int $limit Maximum products to check (0 = all)
     * @return array Results
     */
    public function scanAllProducts($limit = 100)
    {
        $this->results = array(
            'broken_links' => array(),
            'broken_images' => array(),
            'redirects' => array(),
            'external_links' => array(),
            'stats' => array(
                'products_scanned' => 0,
                'links_checked' => 0,
                'broken_count' => 0,
                'redirect_count' => 0,
            ),
        );

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $sql = 'SELECT p.id_product, pl.name, pl.description, pl.description_short
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
                WHERE ps.active = 1
                AND ps.id_shop = ' . $idShop . '
                AND pl.id_lang = ' . $idLang . '
                AND pl.id_shop = ' . $idShop;

        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $products = Db::getInstance()->executeS($sql);

        foreach ($products as $product) {
            $this->scanProductContent($product);
            $this->results['stats']['products_scanned']++;
        }

        return $this->results;
    }

    /**
     * Scan single product content for links
     * @param array $product
     */
    protected function scanProductContent($product)
    {
        $content = $product['description'] . ' ' . $product['description_short'];

        // Extract all links
        $links = $this->extractLinks($content);

        foreach ($links as $link) {
            $this->checkLink($link, 'product', $product['id_product'], $product['name']);
        }

        // Extract all images
        $images = $this->extractImages($content);

        foreach ($images as $image) {
            $this->checkImage($image, 'product', $product['id_product'], $product['name']);
        }
    }

    /**
     * Scan all CMS pages for links
     * @return array Results
     */
    public function scanCmsPages()
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $pages = Db::getInstance()->executeS(
            'SELECT c.id_cms, cl.meta_title, cl.content
             FROM ' . _DB_PREFIX_ . 'cms c
             INNER JOIN ' . _DB_PREFIX_ . 'cms_lang cl ON c.id_cms = cl.id_cms
             INNER JOIN ' . _DB_PREFIX_ . 'cms_shop cs ON c.id_cms = cs.id_cms
             WHERE c.active = 1
             AND cs.id_shop = ' . $idShop . '
             AND cl.id_lang = ' . $idLang . '
             AND cl.id_shop = ' . $idShop
        );

        foreach ($pages as $page) {
            $links = $this->extractLinks($page['content']);

            foreach ($links as $link) {
                $this->checkLink($link, 'cms', $page['id_cms'], $page['meta_title']);
            }

            $images = $this->extractImages($page['content']);

            foreach ($images as $image) {
                $this->checkImage($image, 'cms', $page['id_cms'], $page['meta_title']);
            }
        }

        return $this->results;
    }

    /**
     * Extract all links from HTML content
     * @param string $content
     * @return array
     */
    protected function extractLinks($content)
    {
        $links = array();

        // Match href attributes
        preg_match_all('/href=["\']([^"\']+)["\']/', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $link) {
                // Skip javascript, mailto, tel links
                if (preg_match('/^(javascript:|mailto:|tel:|#)/', $link)) {
                    continue;
                }

                $links[] = $link;
            }
        }

        return array_unique($links);
    }

    /**
     * Extract all images from HTML content
     * @param string $content
     * @return array
     */
    protected function extractImages($content)
    {
        $images = array();

        // Match src attributes in img tags
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/', $content, $matches);

        if (!empty($matches[1])) {
            $images = array_merge($images, $matches[1]);
        }

        // Match background-image URLs
        preg_match_all('/background-image:\s*url\(["\']?([^"\')\s]+)["\']?\)/', $content, $bgMatches);

        if (!empty($bgMatches[1])) {
            $images = array_merge($images, $bgMatches[1]);
        }

        return array_unique($images);
    }

    /**
     * Check if a link is valid
     * @param string $url
     * @param string $sourceType
     * @param int $sourceId
     * @param string $sourceName
     */
    protected function checkLink($url, $sourceType, $sourceId, $sourceName)
    {
        $this->results['stats']['links_checked']++;

        // Make URL absolute if relative
        $absoluteUrl = $this->makeAbsoluteUrl($url);

        // Check cache first
        if (isset($this->cache[$absoluteUrl])) {
            $status = $this->cache[$absoluteUrl];
        } else {
            $status = $this->getUrlStatus($absoluteUrl);
            $this->cache[$absoluteUrl] = $status;
        }

        // Determine if external
        $isExternal = $this->isExternalUrl($absoluteUrl);

        if ($status['code'] === 0 || $status['code'] >= 400) {
            // Broken link
            $this->results['broken_links'][] = array(
                'url' => $url,
                'absolute_url' => $absoluteUrl,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_name' => $sourceName,
                'http_code' => $status['code'],
                'error' => $status['error'],
                'is_external' => $isExternal,
            );
            $this->results['stats']['broken_count']++;
        } elseif ($status['code'] >= 300 && $status['code'] < 400) {
            // Redirect
            $this->results['redirects'][] = array(
                'url' => $url,
                'absolute_url' => $absoluteUrl,
                'redirect_url' => $status['redirect_url'],
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_name' => $sourceName,
                'http_code' => $status['code'],
                'is_external' => $isExternal,
            );
            $this->results['stats']['redirect_count']++;
        }

        if ($isExternal && $status['code'] >= 200 && $status['code'] < 300) {
            // Working external link (for reference)
            $this->results['external_links'][] = array(
                'url' => $absoluteUrl,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_name' => $sourceName,
            );
        }
    }

    /**
     * Check if an image is valid
     * @param string $url
     * @param string $sourceType
     * @param int $sourceId
     * @param string $sourceName
     */
    protected function checkImage($url, $sourceType, $sourceId, $sourceName)
    {
        // Skip data URIs
        if (strpos($url, 'data:') === 0) {
            return;
        }

        // Make URL absolute if relative
        $absoluteUrl = $this->makeAbsoluteUrl($url);

        // Check cache first
        if (isset($this->cache[$absoluteUrl])) {
            $status = $this->cache[$absoluteUrl];
        } else {
            $status = $this->getUrlStatus($absoluteUrl);
            $this->cache[$absoluteUrl] = $status;
        }

        if ($status['code'] === 0 || $status['code'] >= 400) {
            $this->results['broken_images'][] = array(
                'url' => $url,
                'absolute_url' => $absoluteUrl,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_name' => $sourceName,
                'http_code' => $status['code'],
                'error' => $status['error'],
            );
            $this->results['stats']['broken_count']++;
        }
    }

    /**
     * Get URL status via HTTP HEAD request
     * @param string $url
     * @return array
     */
    protected function getUrlStatus($url)
    {
        $result = array(
            'code' => 0,
            'error' => '',
            'redirect_url' => '',
        );

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $result['error'] = 'Invalid URL format';
            return $result;
        }

        // Use cURL for checking
        $ch = curl_init();
        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true, // HEAD request
            CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ProSEOMaster/1.0; +' . $this->context->link->getBaseLink() . ')',
        );

        // SSL verification - enable in production, use CA bundle if available
        if (Configuration::get('PS_SSL_ENABLED') && file_exists(_PS_TOOL_DIR_ . 'cacert.pem')) {
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
            $curlOptions[CURLOPT_CAINFO] = _PS_TOOL_DIR_ . 'cacert.pem';
        } else {
            // Fallback for environments without CA bundle
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $result['code'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result['code'] === 0) {
            $result['error'] = curl_error($ch);
        }

        // Check for redirect
        if ($result['code'] >= 300 && $result['code'] < 400) {
            $result['redirect_url'] = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

            // If CURLINFO_REDIRECT_URL is empty, try to parse from headers
            if (empty($result['redirect_url'])) {
                preg_match('/Location:\s*(.+)/i', $response, $matches);
                if (!empty($matches[1])) {
                    $result['redirect_url'] = trim($matches[1]);
                }
            }
        }

        curl_close($ch);

        return $result;
    }

    /**
     * Make URL absolute
     * @param string $url
     * @return string
     */
    protected function makeAbsoluteUrl($url)
    {
        // Already absolute
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        // Protocol-relative
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        // Relative URL
        $baseUrl = $this->context->link->getBaseLink();

        if (strpos($url, '/') === 0) {
            // Absolute path
            $parsed = parse_url($baseUrl);
            return $parsed['scheme'] . '://' . $parsed['host'] . $url;
        }

        // Relative path
        return rtrim($baseUrl, '/') . '/' . $url;
    }

    /**
     * Check if URL is external
     * @param string $url
     * @return bool
     */
    protected function isExternalUrl($url)
    {
        $baseUrl = $this->context->link->getBaseLink();
        $baseParsed = parse_url($baseUrl);
        $urlParsed = parse_url($url);

        if (!isset($urlParsed['host'])) {
            return false;
        }

        return $baseParsed['host'] !== $urlParsed['host'];
    }

    /**
     * Check product images (from database, not content)
     * @param int $limit
     * @return array
     */
    public function checkProductImages($limit = 100)
    {
        $results = array(
            'missing_images' => array(),
            'products_checked' => 0,
        );

        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;

        $products = Db::getInstance()->executeS(
            'SELECT p.id_product, pl.name, pl.link_rewrite
             FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
             WHERE ps.active = 1
             AND ps.id_shop = ' . $idShop . '
             AND pl.id_lang = ' . $idLang . '
             AND pl.id_shop = ' . $idShop . '
             LIMIT ' . (int) $limit
        );

        foreach ($products as $product) {
            $images = Db::getInstance()->executeS(
                'SELECT i.id_image
                 FROM ' . _DB_PREFIX_ . 'image i
                 INNER JOIN ' . _DB_PREFIX_ . 'image_shop ish ON i.id_image = ish.id_image
                 WHERE i.id_product = ' . (int) $product['id_product'] . '
                 AND ish.id_shop = ' . $idShop
            );

            foreach ($images as $image) {
                $imagePath = _PS_PROD_IMG_DIR_ . Image::getImgFolderStatic($image['id_image']) . $image['id_image'] . '.jpg';

                if (!file_exists($imagePath)) {
                    $results['missing_images'][] = array(
                        'product_id' => $product['id_product'],
                        'product_name' => $product['name'],
                        'image_id' => $image['id_image'],
                        'expected_path' => $imagePath,
                    );
                }
            }

            $results['products_checked']++;
        }

        return $results;
    }

    /**
     * Quick scan - check only products updated in last X days
     * @param int $days
     * @return array
     */
    public function quickScan($days = 30)
    {
        $this->results = array(
            'broken_links' => array(),
            'broken_images' => array(),
            'redirects' => array(),
            'external_links' => array(),
            'stats' => array(
                'products_scanned' => 0,
                'links_checked' => 0,
                'broken_count' => 0,
                'redirect_count' => 0,
            ),
        );

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $dateLimit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $products = Db::getInstance()->executeS(
            'SELECT p.id_product, pl.name, pl.description, pl.description_short
             FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
             WHERE ps.active = 1
             AND ps.id_shop = ' . $idShop . '
             AND pl.id_lang = ' . $idLang . '
             AND pl.id_shop = ' . $idShop . '
             AND p.date_upd >= "' . pSQL($dateLimit) . '"'
        );

        foreach ($products as $product) {
            $this->scanProductContent($product);
            $this->results['stats']['products_scanned']++;
        }

        return $this->results;
    }

    /**
     * Get link check summary for dashboard
     * @return array
     */
    public function getSummary()
    {
        return array(
            'total_links' => $this->results['stats']['links_checked'] ?? 0,
            'broken_links' => count($this->results['broken_links'] ?? array()),
            'broken_images' => count($this->results['broken_images'] ?? array()),
            'redirects' => count($this->results['redirects'] ?? array()),
            'external_links' => count($this->results['external_links'] ?? array()),
        );
    }

    /**
     * Export results to CSV
     * @return string
     */
    public function exportToCsv()
    {
        $csv = "Type,URL,Source Type,Source ID,Source Name,HTTP Code,Error/Redirect URL\n";

        // Broken links
        foreach ($this->results['broken_links'] as $link) {
            $csv .= '"Broken Link",';
            $csv .= '"' . $this->escapeCsvField($link['url']) . '",';
            $csv .= '"' . $this->escapeCsvField($link['source_type']) . '",';
            $csv .= (int) $link['source_id'] . ',';
            $csv .= '"' . $this->escapeCsvField($link['source_name']) . '",';
            $csv .= (int) $link['http_code'] . ',';
            $csv .= '"' . $this->escapeCsvField($link['error']) . '"' . "\n";
        }

        // Broken images
        foreach ($this->results['broken_images'] as $image) {
            $csv .= '"Broken Image",';
            $csv .= '"' . $this->escapeCsvField($image['url']) . '",';
            $csv .= '"' . $this->escapeCsvField($image['source_type']) . '",';
            $csv .= (int) $image['source_id'] . ',';
            $csv .= '"' . $this->escapeCsvField($image['source_name']) . '",';
            $csv .= (int) $image['http_code'] . ',';
            $csv .= '"' . $this->escapeCsvField($image['error']) . '"' . "\n";
        }

        // Redirects
        foreach ($this->results['redirects'] as $redirect) {
            $csv .= '"Redirect",';
            $csv .= '"' . $this->escapeCsvField($redirect['url']) . '",';
            $csv .= '"' . $this->escapeCsvField($redirect['source_type']) . '",';
            $csv .= (int) $redirect['source_id'] . ',';
            $csv .= '"' . $this->escapeCsvField($redirect['source_name']) . '",';
            $csv .= (int) $redirect['http_code'] . ',';
            $csv .= '"' . $this->escapeCsvField($redirect['redirect_url']) . '"' . "\n";
        }

        return $csv;
    }

    /**
     * Escape a CSV field value to prevent CSV injection
     * @param string $field
     * @return string
     */
    protected function escapeCsvField($field)
    {
        $field = str_replace('"', '""', (string) $field);

        // Prevent CSV injection: prefix dangerous characters with a single quote
        if (isset($field[0]) && in_array($field[0], array('=', '+', '-', '@', "\t", "\r"), true)) {
            $field = "'" . $field;
        }

        return $field;
    }
}
