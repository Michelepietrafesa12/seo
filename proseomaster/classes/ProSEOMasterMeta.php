<?php
/**
 * ProSEOMaster - Advanced Meta Tags Manager
 *
 * Generates optimized meta tags with:
 * - Dynamic title templates
 * - AI-style description optimization
 * - Pagination handling (rel prev/next)
 * - Proper canonical management
 * - noindex for low-value pages
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterMeta
{
    /** @var Context */
    protected $context;

    /** @var string Title separator */
    protected $titleSeparator = ' | ';

    /** @var int Maximum title length */
    protected $maxTitleLength = 60;

    /** @var int Maximum description length */
    protected $maxDescriptionLength = 155;

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Generate optimized product title
     * @param Product $product
     * @param Category|null $category
     * @return string
     */
    public function generateProductTitle($product, $category = null)
    {
        $template = Configuration::get('PROSEOMASTER_PRODUCT_TITLE_TEMPLATE');

        if (empty($template)) {
            $template = '{product_name} | {category} | {shop_name}';
        }

        $categoryName = '';
        if ($category && Validate::isLoadedObject($category)) {
            $categoryName = $category->name;
        } elseif ($product->id_category_default) {
            $cat = new Category($product->id_category_default, $this->context->language->id);
            if (Validate::isLoadedObject($cat)) {
                $categoryName = $cat->name;
            }
        }

        $manufacturer = '';
        if ($product->id_manufacturer) {
            $mfr = new Manufacturer($product->id_manufacturer, $this->context->language->id);
            if (Validate::isLoadedObject($mfr)) {
                $manufacturer = $mfr->name;
            }
        }

        $replacements = array(
            '{product_name}' => $product->name,
            '{category}' => $categoryName,
            '{manufacturer}' => $manufacturer,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{price}' => Tools::displayPrice($product->getPrice(true)),
            '{reference}' => $product->reference,
        );

        $title = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Clean up empty placeholders and extra separators
        $title = preg_replace('/\s*\|\s*\|\s*/', ' | ', $title);
        $title = preg_replace('/\s*\|\s*$/', '', $title);
        $title = preg_replace('/^\s*\|\s*/', '', $title);

        return $this->truncateTitle($title);
    }

    /**
     * Generate optimized product description
     * @param Product $product
     * @return string
     */
    public function generateProductDescription($product)
    {
        $template = Configuration::get('PROSEOMASTER_PRODUCT_DESC_TEMPLATE');

        if (empty($template)) {
            // Default template with call-to-action keywords
            $template = '{description_short} Acquista {product_name} online. {availability}. Spedizione veloce.';
        }

        // Get availability text
        $quantity = Product::getQuantity($product->id);
        if ($quantity > 0) {
            $availability = 'Disponibile';
        } elseif ($product->out_of_stock == 1) {
            $availability = 'Ordinabile';
        } else {
            $availability = 'Non disponibile';
        }

        $descriptionShort = strip_tags($product->description_short);
        $descriptionShort = $this->truncateText($descriptionShort, 100);

        $replacements = array(
            '{product_name}' => $product->name,
            '{description_short}' => $descriptionShort,
            '{price}' => Tools::displayPrice($product->getPrice(true)),
            '{availability}' => $availability,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
        );

        $description = str_replace(array_keys($replacements), array_values($replacements), $template);

        return $this->truncateDescription($description);
    }

    /**
     * Generate optimized category title
     * @param Category $category
     * @param int $page
     * @return string
     */
    public function generateCategoryTitle($category, $page = 1)
    {
        $template = Configuration::get('PROSEOMASTER_CATEGORY_TITLE_TEMPLATE');

        if (empty($template)) {
            $template = '{category_name} | {shop_name}';
        }

        // Get parent category
        $parentName = '';
        if ($category->id_parent > 2) {
            $parent = new Category($category->id_parent, $this->context->language->id);
            if (Validate::isLoadedObject($parent)) {
                $parentName = $parent->name;
            }
        }

        $replacements = array(
            '{category_name}' => $category->name,
            '{parent_category}' => $parentName,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
        );

        $title = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Add page number for pagination
        if ($page > 1) {
            $title .= ' - Pagina ' . $page;
        }

        // Clean up
        $title = preg_replace('/\s*\|\s*\|\s*/', ' | ', $title);
        $title = preg_replace('/\s*\|\s*$/', '', $title);

        return $this->truncateTitle($title);
    }

    /**
     * Generate optimized category description
     * @param Category $category
     * @param int $productsCount
     * @return string
     */
    public function generateCategoryDescription($category, $productsCount = 0)
    {
        $template = Configuration::get('PROSEOMASTER_CATEGORY_DESC_TEMPLATE');

        if (empty($template)) {
            $template = 'Scopri la nostra selezione di {category_name}. {products_count} prodotti disponibili. Acquista online con spedizione veloce.';
        }

        $description = strip_tags($category->description);
        $description = $this->truncateText($description, 100);

        $replacements = array(
            '{category_name}' => $category->name,
            '{description}' => $description,
            '{products_count}' => $productsCount,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
        );

        $metaDesc = str_replace(array_keys($replacements), array_values($replacements), $template);

        return $this->truncateDescription($metaDesc);
    }

    /**
     * Generate pagination meta tags (rel prev/next)
     * @param string $baseUrl
     * @param int $currentPage
     * @param int $totalPages
     * @return string
     */
    public function generatePaginationMeta($baseUrl, $currentPage, $totalPages)
    {
        $output = '';

        // Clean base URL from existing page parameters
        $baseUrl = preg_replace('/[?&]page=\d+/', '', $baseUrl);
        $separator = strpos($baseUrl, '?') !== false ? '&' : '?';

        // rel="prev"
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $prevUrl = $prevPage === 1 ? $baseUrl : $baseUrl . $separator . 'page=' . $prevPage;
            $output .= '<link rel="prev" href="' . htmlspecialchars($prevUrl) . '" />' . "\n";
        }

        // rel="next"
        if ($currentPage < $totalPages) {
            $nextUrl = $baseUrl . $separator . 'page=' . ($currentPage + 1);
            $output .= '<link rel="next" href="' . htmlspecialchars($nextUrl) . '" />' . "\n";
        }

        return $output;
    }

    /**
     * Generate canonical URL with proper filter handling
     * @param string $currentUrl
     * @param string $pageType
     * @return string
     */
    public function generateCanonicalUrl($currentUrl, $pageType)
    {
        $parsedUrl = parse_url($currentUrl);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];

        // Parameters to preserve in canonical
        $preserveParams = array();

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);

            // Define which parameters to preserve based on page type
            switch ($pageType) {
                case 'category':
                    // Only preserve page parameter if > 1
                    if (isset($queryParams['page']) && (int) $queryParams['page'] > 1) {
                        $preserveParams['page'] = (int) $queryParams['page'];
                    }
                    break;

                case 'product':
                    // Preserve product attribute combination
                    if (isset($queryParams['id_product_attribute'])) {
                        $preserveParams['id_product_attribute'] = (int) $queryParams['id_product_attribute'];
                    }
                    break;

                case 'search':
                    // Preserve search query
                    if (isset($queryParams['s'])) {
                        $preserveParams['s'] = $queryParams['s'];
                    }
                    break;
            }
        }

        // Build canonical URL
        if (!empty($preserveParams)) {
            $baseUrl .= '?' . http_build_query($preserveParams);
        }

        return $baseUrl;
    }

    /**
     * Determine if page should be noindexed
     * @param string $pageType
     * @param array $params
     * @return bool
     */
    public function shouldNoindex($pageType, $params = array())
    {
        // Always noindex these pages
        $noindexPages = array(
            'cart',
            'order',
            'order-confirmation',
            'my-account',
            'identity',
            'addresses',
            'address',
            'history',
            'order-detail',
            'order-slip',
            'credit-slip',
            'discount',
            'guest-tracking',
            'order-follow',
            'authentication',
            'password',
            'password-recovery',
            'registration',
            'search',
            '404',
            'pagenotfound',
        );

        if (in_array($pageType, $noindexPages)) {
            return true;
        }

        // Noindex paginated pages beyond page 5 (optional strategy)
        if (isset($params['page']) && (int) $params['page'] > 5) {
            return Configuration::get('PROSEOMASTER_NOINDEX_DEEP_PAGINATION');
        }

        // Noindex filtered category pages
        if ($pageType === 'category' && $this->hasFiltersApplied($params)) {
            return Configuration::get('PROSEOMASTER_NOINDEX_FILTERED_PAGES');
        }

        // Noindex search results
        if ($pageType === 'search') {
            return true;
        }

        return false;
    }

    /**
     * Check if URL has filter parameters applied
     * @param array $params
     * @return bool
     */
    protected function hasFiltersApplied($params)
    {
        $filterParams = array(
            'q',
            'orderby',
            'orderway',
            'n',
            'resultsPerPage',
            'color',
            'size',
            'material',
            'price',
            'weight',
        );

        foreach ($filterParams as $param) {
            if (isset($params[$param])) {
                return true;
            }
        }

        // Check for faceted navigation parameters
        foreach ($params as $key => $value) {
            if (strpos($key, 'facet') !== false || strpos($key, 'filter') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate robots meta tag
     * @param bool $index
     * @param bool $follow
     * @param array $additionalDirectives
     * @return string
     */
    public function generateRobotsMeta($index = true, $follow = true, $additionalDirectives = array())
    {
        $directives = array();

        $directives[] = $index ? 'index' : 'noindex';
        $directives[] = $follow ? 'follow' : 'nofollow';

        // Add additional directives
        if (in_array('noarchive', $additionalDirectives)) {
            $directives[] = 'noarchive';
        }
        if (in_array('nosnippet', $additionalDirectives)) {
            $directives[] = 'nosnippet';
        }
        if (in_array('noimageindex', $additionalDirectives)) {
            $directives[] = 'noimageindex';
        }

        // max-snippet directive
        if (isset($additionalDirectives['max-snippet'])) {
            $directives[] = 'max-snippet:' . (int) $additionalDirectives['max-snippet'];
        }

        // max-image-preview directive
        if (isset($additionalDirectives['max-image-preview'])) {
            $directives[] = 'max-image-preview:' . $additionalDirectives['max-image-preview'];
        }

        // max-video-preview directive
        if (isset($additionalDirectives['max-video-preview'])) {
            $directives[] = 'max-video-preview:' . (int) $additionalDirectives['max-video-preview'];
        }

        return '<meta name="robots" content="' . implode(', ', $directives) . '" />';
    }

    /**
     * Truncate title to optimal length
     * @param string $title
     * @return string
     */
    protected function truncateTitle($title)
    {
        $title = trim(strip_tags($title));

        if (strlen($title) <= $this->maxTitleLength) {
            return $title;
        }

        // Try to cut at separator
        $separatorPos = strrpos(substr($title, 0, $this->maxTitleLength), $this->titleSeparator);
        if ($separatorPos !== false && $separatorPos > 20) {
            return trim(substr($title, 0, $separatorPos));
        }

        // Cut at word boundary
        $title = substr($title, 0, $this->maxTitleLength - 3);
        $lastSpace = strrpos($title, ' ');
        if ($lastSpace !== false && $lastSpace > 30) {
            $title = substr($title, 0, $lastSpace);
        }

        return $title . '...';
    }

    /**
     * Truncate description to optimal length
     * @param string $description
     * @return string
     */
    protected function truncateDescription($description)
    {
        $description = trim(strip_tags($description));
        $description = preg_replace('/\s+/', ' ', $description);

        if (strlen($description) <= $this->maxDescriptionLength) {
            return $description;
        }

        $description = substr($description, 0, $this->maxDescriptionLength - 3);
        $lastSpace = strrpos($description, ' ');

        if ($lastSpace !== false && $lastSpace > 80) {
            $description = substr($description, 0, $lastSpace);
        }

        return $description . '...';
    }

    /**
     * Truncate text to specified length
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    protected function truncateText($text, $maxLength)
    {
        $text = trim(strip_tags($text));

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $text = substr($text, 0, $maxLength);
        $lastSpace = strrpos($text, ' ');

        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }

        return $text . '...';
    }
}
