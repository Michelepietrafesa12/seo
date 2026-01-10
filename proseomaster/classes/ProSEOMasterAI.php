<?php
/**
 * ProSEOMaster - AI SEO Optimization
 *
 * Optimize content for AI systems:
 * - Google AI Mode / AI Overviews
 * - ChatGPT / OpenAI
 * - Claude / Anthropic
 * - Bing Chat / Copilot
 * - Perplexity AI
 * - Other LLM-based search engines
 *
 * Implements:
 * - llms.txt standard (https://llmstxt.org/)
 * - AI-specific meta tags
 * - Structured content for AI crawlers
 * - Knowledge graph optimization
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterAI
{
    /** @var Context */
    protected $context;

    /** @var array AI crawler user agents */
    protected $aiCrawlers = array(
        'GPTBot',           // OpenAI
        'ChatGPT-User',     // ChatGPT browsing
        'Claude-Web',       // Anthropic Claude
        'Anthropic-AI',     // Anthropic
        'Google-Extended',  // Google AI (Bard/Gemini)
        'Googlebot',        // Google (includes AI Overviews)
        'Bingbot',          // Bing (includes Copilot)
        'PerplexityBot',    // Perplexity AI
        'YouBot',           // You.com
        'CCBot',            // Common Crawl (used by many AI)
        'Bytespider',       // ByteDance AI
        'Applebot',         // Apple (Siri, etc.)
        'FacebookBot',      // Meta AI
        'cohere-ai',        // Cohere
        'Diffbot',          // Diffbot
        'ImagesiftBot',     // AI image analysis
        'Omgilibot',        // Webz.io AI
    );

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Generate llms.txt content
     * Standard format for LLM crawlers to understand site structure
     * @return string
     */
    public function generateLlmsTxt()
    {
        $shopName = Configuration::get('PS_SHOP_NAME');
        $shopUrl = $this->context->link->getPageLink('index', true);
        $shopDescription = Configuration::get('PS_SHOP_DETAILS') ?: $this->getShopMetaDescription();

        $content = "# " . $shopName . "\n\n";
        $content .= "> " . $shopDescription . "\n\n";

        // About section
        $content .= "## About\n\n";
        $content .= $this->getAboutSection() . "\n\n";

        // Main sections/categories
        $content .= "## Main Sections\n\n";
        $content .= $this->getCategoriesSection() . "\n\n";

        // Products highlight
        $content .= "## Featured Products\n\n";
        $content .= $this->getFeaturedProductsSection() . "\n\n";

        // Contact & Support
        $content .= "## Contact Information\n\n";
        $content .= $this->getContactSection() . "\n\n";

        // Important links
        $content .= "## Important Links\n\n";
        $content .= $this->getImportantLinksSection() . "\n\n";

        // Policies
        $content .= "## Policies\n\n";
        $content .= $this->getPoliciesSection() . "\n\n";

        // Optional: Full documentation link
        $content .= "## Extended Information\n\n";
        $content .= "- [Full Product Catalog](" . $shopUrl . "llms-full.txt): Complete product information\n";
        $content .= "- [Sitemap](" . $shopUrl . "sitemap.xml): XML sitemap for all pages\n";

        return $content;
    }

    /**
     * Generate extended llms-full.txt with complete product catalog
     * @return string
     */
    public function generateLlmsFullTxt()
    {
        $shopName = Configuration::get('PS_SHOP_NAME');
        $shopUrl = $this->context->link->getPageLink('index', true);

        $content = "# " . $shopName . " - Complete Product Catalog\n\n";
        $content .= "> This is the extended version of llms.txt containing detailed product information.\n\n";

        // All categories with products
        $content .= "## Product Catalog\n\n";
        $content .= $this->getFullCatalogSection() . "\n\n";

        // Brands/Manufacturers
        $content .= "## Brands\n\n";
        $content .= $this->getBrandsSection() . "\n\n";

        // FAQ if available
        $content .= "## Frequently Asked Questions\n\n";
        $content .= $this->getFaqSection() . "\n\n";

        return $content;
    }

    /**
     * Get shop meta description
     * @return string
     */
    protected function getShopMetaDescription()
    {
        $meta = Meta::getMetaByPage('index', $this->context->language->id);
        return isset($meta['description']) ? $meta['description'] : '';
    }

    /**
     * Get about section content
     * @return string
     */
    protected function getAboutSection()
    {
        $content = '';

        $orgName = Configuration::get('PROSEOMASTER_ORGANIZATION_NAME') ?: Configuration::get('PS_SHOP_NAME');
        $content .= "**" . $orgName . "** is an online store";

        // Add business type if set
        $businessType = Configuration::get('PROSEOMASTER_BUSINESS_TYPE');
        if ($businessType && $businessType !== 'Store') {
            $content .= " specializing in " . strtolower(preg_replace('/([A-Z])/', ' $1', $businessType));
        }

        $content .= ".\n\n";

        // Add location if available
        $city = Configuration::get('PROSEOMASTER_LOCAL_CITY');
        $country = Configuration::get('PROSEOMASTER_LOCAL_COUNTRY');
        if ($city && $country) {
            $content .= "Located in " . $city . ", " . $country . ".\n\n";
        }

        // Add key stats
        $productCount = $this->getActiveProductCount();
        $categoryCount = $this->getActiveCategoryCount();
        $content .= "Currently offering **" . $productCount . " products** across **" . $categoryCount . " categories**.\n";

        return $content;
    }

    /**
     * Get categories section
     * @return string
     */
    protected function getCategoriesSection()
    {
        $content = '';
        $categories = Category::getHomeCategories($this->context->language->id, true);

        foreach ($categories as $category) {
            $catObj = new Category($category['id_category'], $this->context->language->id);
            $url = $this->context->link->getCategoryLink($catObj);
            $productCount = $catObj->getProducts($this->context->language->id, 1, 1, null, null, true);

            $content .= "- [" . $category['name'] . "](" . $url . ")";
            if ($productCount > 0) {
                $content .= " (" . $productCount . " products)";
            }
            $content .= "\n";

            // Add subcategories (max 5 per category)
            $subcategories = Category::getChildren($category['id_category'], $this->context->language->id, true);
            $subCount = 0;
            foreach ($subcategories as $subcat) {
                if ($subCount >= 5) {
                    break;
                }
                $subCatObj = new Category($subcat['id_category'], $this->context->language->id);
                $subUrl = $this->context->link->getCategoryLink($subCatObj);
                $content .= "  - [" . $subcat['name'] . "](" . $subUrl . ")\n";
                $subCount++;
            }
        }

        return $content;
    }

    /**
     * Get featured products section
     * @return string
     */
    protected function getFeaturedProductsSection()
    {
        $content = '';

        // Get best sellers or featured products
        $products = Product::getNewProducts($this->context->language->id, 0, 10);

        if (empty($products)) {
            // Fallback to random active products
            $products = $this->getRandomProducts(10);
        }

        foreach ($products as $product) {
            $productObj = new Product($product['id_product'], false, $this->context->language->id);
            $url = $this->context->link->getProductLink($productObj);
            $price = Product::getPriceStatic($product['id_product'], true);
            $currency = $this->context->currency->sign;

            $content .= "- **[" . $product['name'] . "](" . $url . ")**";
            $content .= " - " . $currency . number_format($price, 2);

            // Add short description if available
            $shortDesc = strip_tags($product['description_short'] ?? '');
            if ($shortDesc) {
                $shortDesc = $this->truncateText($shortDesc, 100);
                $content .= "\n  " . $shortDesc;
            }

            $content .= "\n";
        }

        return $content;
    }

    /**
     * Get contact section
     * @return string
     */
    protected function getContactSection()
    {
        $content = '';

        $phone = Configuration::get('PROSEOMASTER_ORGANIZATION_PHONE');
        $email = Configuration::get('PROSEOMASTER_ORGANIZATION_EMAIL');
        $street = Configuration::get('PROSEOMASTER_LOCAL_STREET');
        $city = Configuration::get('PROSEOMASTER_LOCAL_CITY');
        $postal = Configuration::get('PROSEOMASTER_LOCAL_POSTAL');
        $country = Configuration::get('PROSEOMASTER_LOCAL_COUNTRY');

        if ($phone) {
            $content .= "- **Phone**: " . $phone . "\n";
        }
        if ($email) {
            $content .= "- **Email**: " . $email . "\n";
        }

        if ($street && $city) {
            $address = $street . ", " . ($postal ? $postal . " " : "") . $city;
            if ($country) {
                $address .= ", " . $country;
            }
            $content .= "- **Address**: " . $address . "\n";
        }

        // Contact page link
        $contactUrl = $this->context->link->getPageLink('contact', true);
        $content .= "- **Contact Form**: [Contact Us](" . $contactUrl . ")\n";

        return $content;
    }

    /**
     * Get important links section
     * @return string
     */
    protected function getImportantLinksSection()
    {
        $content = '';
        $shopUrl = $this->context->link->getPageLink('index', true);

        // Standard links
        $links = array(
            'Homepage' => $shopUrl,
            'All Products' => $this->context->link->getPageLink('new-products', true),
            'Best Sellers' => $this->context->link->getPageLink('best-sales', true),
            'Special Offers' => $this->context->link->getPageLink('prices-drop', true),
            'Contact' => $this->context->link->getPageLink('contact', true),
            'Sitemap' => $this->context->link->getPageLink('sitemap', true),
        );

        foreach ($links as $name => $url) {
            $content .= "- [" . $name . "](" . $url . ")\n";
        }

        // Social media
        $socialLinks = $this->getSocialLinks();
        if (!empty($socialLinks)) {
            $content .= "\n### Social Media\n\n";
            foreach ($socialLinks as $name => $url) {
                $content .= "- [" . $name . "](" . $url . ")\n";
            }
        }

        return $content;
    }

    /**
     * Get policies section
     * @return string
     */
    protected function getPoliciesSection()
    {
        $content = '';

        // Get CMS pages for common policies
        $policyPages = array(
            'terms-and-conditions' => 'Terms and Conditions',
            'privacy' => 'Privacy Policy',
            'legal-notice' => 'Legal Notice',
            'secure-payment' => 'Payment Information',
            'delivery' => 'Shipping Information',
        );

        $cmsPages = CMS::getCMSPages($this->context->language->id, null, true);

        foreach ($cmsPages as $page) {
            $url = $this->context->link->getCMSLink($page['id_cms']);
            $content .= "- [" . $page['meta_title'] . "](" . $url . ")\n";
        }

        return $content;
    }

    /**
     * Get full catalog section for llms-full.txt
     * @return string
     */
    protected function getFullCatalogSection()
    {
        $content = '';
        $categories = Category::getHomeCategories($this->context->language->id, true);

        foreach ($categories as $category) {
            $catObj = new Category($category['id_category'], $this->context->language->id);
            $url = $this->context->link->getCategoryLink($catObj);

            $content .= "### " . $category['name'] . "\n\n";
            $content .= "[View Category](" . $url . ")\n\n";

            // Get products in category (limit 20 per category)
            $products = $catObj->getProducts($this->context->language->id, 1, 20);

            if (!empty($products)) {
                foreach ($products as $product) {
                    $productUrl = $this->context->link->getProductLink($product['id_product']);
                    $price = Product::getPriceStatic($product['id_product'], true);
                    $currency = $this->context->currency->sign;

                    $content .= "- **" . $product['name'] . "**\n";
                    $content .= "  - Price: " . $currency . number_format($price, 2) . "\n";
                    $content .= "  - URL: " . $productUrl . "\n";

                    // SKU/Reference
                    if (!empty($product['reference'])) {
                        $content .= "  - SKU: " . $product['reference'] . "\n";
                    }

                    // Availability
                    $quantity = Product::getQuantity($product['id_product']);
                    $availability = $quantity > 0 ? 'In Stock' : 'Out of Stock';
                    $content .= "  - Availability: " . $availability . "\n";

                    $content .= "\n";
                }
            }

            $content .= "\n";
        }

        return $content;
    }

    /**
     * Get brands section
     * @return string
     */
    protected function getBrandsSection()
    {
        $content = '';
        $manufacturers = Manufacturer::getManufacturers(false, $this->context->language->id, true);

        foreach ($manufacturers as $manufacturer) {
            $url = $this->context->link->getManufacturerLink($manufacturer['id_manufacturer']);
            $content .= "- [" . $manufacturer['name'] . "](" . $url . ")\n";
        }

        return $content;
    }

    /**
     * Get FAQ section
     * @return string
     */
    protected function getFaqSection()
    {
        $content = '';

        // Common e-commerce FAQs
        $faqs = array(
            'How can I place an order?' => 'Browse our products, add items to your cart, and proceed to checkout. You can pay securely using various payment methods.',
            'What payment methods do you accept?' => 'We accept major credit cards, PayPal, and other secure payment options.',
            'How long does shipping take?' => 'Shipping times vary depending on your location. Standard delivery typically takes 3-7 business days.',
            'Can I return an item?' => 'Yes, we offer returns within 14-30 days of purchase. Please check our return policy for details.',
            'How can I track my order?' => 'Once shipped, you will receive a tracking number via email to monitor your delivery.',
            'Do you ship internationally?' => 'Yes, we ship to many countries worldwide. Shipping costs and times vary by destination.',
        );

        foreach ($faqs as $question => $answer) {
            $content .= "**Q: " . $question . "**\n";
            $content .= "A: " . $answer . "\n\n";
        }

        return $content;
    }

    /**
     * Generate AI-specific meta tags for header
     * @return string
     */
    public function generateAIMetaTags()
    {
        $output = '<!-- ProSEO Master: AI Optimization Meta Tags -->' . "\n";

        // AI content declaration - tells AI systems this is legitimate e-commerce content
        $output .= '<meta name="ai-content-declaration" content="This is an official e-commerce website with real products and services." />' . "\n";

        // Allow AI to process and cite this content
        $output .= '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />' . "\n";

        // Specific AI crawler permissions
        $output .= '<meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />' . "\n";
        $output .= '<meta name="bingbot" content="index, follow" />' . "\n";

        // Google AI Overviews specific
        $output .= '<meta name="google" content="notranslate, nopagereadaloud" />' . "\n";

        // LLMs.txt location hint
        $shopUrl = $this->context->link->getPageLink('index', true);
        $output .= '<link rel="llms" href="' . $shopUrl . 'llms.txt" type="text/plain" />' . "\n";
        $output .= '<link rel="llms-full" href="' . $shopUrl . 'llms-full.txt" type="text/plain" />' . "\n";

        // Author/publisher for AI attribution
        $orgName = Configuration::get('PROSEOMASTER_ORGANIZATION_NAME') ?: Configuration::get('PS_SHOP_NAME');
        $output .= '<meta name="author" content="' . htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        $output .= '<meta name="publisher" content="' . htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8') . '" />' . "\n";

        // Content freshness (important for AI to know content is current)
        $output .= '<meta name="revised" content="' . date('Y-m-d') . '" />' . "\n";

        // Speakable structured data hint
        $output .= '<meta name="speakable" content="true" />' . "\n";

        return $output;
    }

    /**
     * Generate Speakable schema for voice assistants and AI
     * @param string $pageType
     * @param array $content
     * @return array|null
     */
    public function generateSpeakableSchema($pageType, $content = array())
    {
        $shopUrl = $this->context->link->getPageLink('index', true);

        // Base speakable selectors
        $cssSelectors = array(
            'h1',
            '.product-title',
            '.product-description-short',
            '.product-price',
            '.category-description',
            'article p:first-of-type',
        );

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'speakable' => array(
                '@type' => 'SpeakableSpecification',
                'cssSelector' => $cssSelectors,
            ),
        );

        // Add page-specific speakable content
        switch ($pageType) {
            case 'product':
                if (isset($content['name']) && isset($content['price'])) {
                    $schema['speakable']['xpath'] = array(
                        '/html/head/title',
                        "//h1[contains(@class, 'product')]",
                        "//div[contains(@class, 'product-description')]//p[1]",
                    );
                }
                break;

            case 'category':
                $schema['speakable']['xpath'] = array(
                    '/html/head/title',
                    "//h1",
                    "//div[contains(@class, 'category-description')]//p[1]",
                );
                break;
        }

        return $schema;
    }

    /**
     * Generate AI-optimized product description
     * Creates clear, factual content that AI systems can easily parse
     * @param Product $product
     * @return string
     */
    public function generateAIProductSummary($product)
    {
        $summary = '';

        // Product identity
        $summary .= "Product: " . $product->name . "\n";

        // SKU/Reference
        if (!empty($product->reference)) {
            $summary .= "SKU: " . $product->reference . "\n";
        }

        // Price
        $price = Product::getPriceStatic($product->id, true);
        $currency = $this->context->currency->iso_code;
        $summary .= "Price: " . $currency . " " . number_format($price, 2) . "\n";

        // Availability
        $quantity = Product::getQuantity($product->id);
        $summary .= "Availability: " . ($quantity > 0 ? 'In Stock (' . $quantity . ' available)' : 'Out of Stock') . "\n";

        // Brand
        if ((int) $product->id_manufacturer > 0) {
            $manufacturer = new Manufacturer((int) $product->id_manufacturer, $this->context->language->id);
            if (Validate::isLoadedObject($manufacturer)) {
                $summary .= "Brand: " . $manufacturer->name . "\n";
            }
        }

        // Category
        $category = new Category((int) $product->id_category_default, $this->context->language->id);
        if (Validate::isLoadedObject($category)) {
            $summary .= "Category: " . $category->name . "\n";
        }

        // Condition
        $conditions = array(
            'new' => 'New',
            'used' => 'Used',
            'refurbished' => 'Refurbished',
        );
        $summary .= "Condition: " . ($conditions[$product->condition] ?? 'New') . "\n";

        // Description (clean, truncated)
        $description = strip_tags($product->description_short);
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
        $description = preg_replace('/\s+/', ' ', trim($description));
        if ($description) {
            $summary .= "Description: " . $this->truncateText($description, 300) . "\n";
        }

        return $summary;
    }

    /**
     * Add robots.txt rules for AI crawlers
     * @return string
     */
    public function getAIRobotsTxtRules()
    {
        $rules = "\n# AI Crawlers - Allow for AI search and assistants\n";

        // Allow all major AI crawlers
        $aiRules = array(
            'User-agent: GPTBot',
            'Allow: /',
            'Allow: /llms.txt',
            'Allow: /llms-full.txt',
            '',
            'User-agent: ChatGPT-User',
            'Allow: /',
            '',
            'User-agent: Claude-Web',
            'Allow: /',
            '',
            'User-agent: Anthropic-AI',
            'Allow: /',
            '',
            'User-agent: Google-Extended',
            'Allow: /',
            '',
            'User-agent: PerplexityBot',
            'Allow: /',
            '',
            'User-agent: Cohere-AI',
            'Allow: /',
            '',
            '# LLMs.txt location',
            'Sitemap: ' . $this->context->link->getPageLink('index', true) . 'llms.txt',
        );

        $rules .= implode("\n", $aiRules);

        return $rules;
    }

    /**
     * Get social links
     * @return array
     */
    protected function getSocialLinks()
    {
        $links = array();

        $socialFields = array(
            'Facebook' => Configuration::get('PROSEOMASTER_SOCIAL_FACEBOOK'),
            'Twitter' => Configuration::get('PROSEOMASTER_SOCIAL_TWITTER'),
            'Instagram' => Configuration::get('PROSEOMASTER_SOCIAL_INSTAGRAM'),
            'LinkedIn' => Configuration::get('PROSEOMASTER_SOCIAL_LINKEDIN'),
            'YouTube' => Configuration::get('PROSEOMASTER_SOCIAL_YOUTUBE'),
            'Pinterest' => Configuration::get('PROSEOMASTER_SOCIAL_PINTEREST'),
        );

        foreach ($socialFields as $name => $url) {
            if (!empty($url)) {
                $links[$name] = $url;
            }
        }

        return $links;
    }

    /**
     * Get active product count
     * @return int
     */
    protected function getActiveProductCount()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product p
             INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
             WHERE ps.active = 1 AND ps.id_shop = ' . (int) $this->context->shop->id
        );
    }

    /**
     * Get active category count
     * @return int
     */
    protected function getActiveCategoryCount()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'category c
             INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs ON c.id_category = cs.id_category
             WHERE c.active = 1 AND cs.id_shop = ' . (int) $this->context->shop->id . ' AND c.id_category > 2'
        );
    }

    /**
     * Get random products
     * @param int $limit
     * @return array
     */
    protected function getRandomProducts($limit = 10)
    {
        $sql = 'SELECT p.id_product, pl.name, pl.description_short
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product = ps.id_product
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
                WHERE ps.active = 1
                AND ps.id_shop = ' . (int) $this->context->shop->id . '
                AND pl.id_lang = ' . (int) $this->context->language->id . '
                ORDER BY RAND()
                LIMIT ' . (int) $limit;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Truncate text to specified length
     * @param string $text
     * @param int $length
     * @return string
     */
    protected function truncateText($text, $length = 150)
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        $text = substr($text, 0, $length);
        $lastSpace = strrpos($text, ' ');
        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }

        return $text . '...';
    }

    /**
     * Check if request is from AI crawler
     * @return bool
     */
    public function isAICrawler()
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        foreach ($this->aiCrawlers as $crawler) {
            if (stripos($userAgent, $crawler) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get AI crawler name if detected
     * @return string|null
     */
    public function getAICrawlerName()
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        foreach ($this->aiCrawlers as $crawler) {
            if (stripos($userAgent, $crawler) !== false) {
                return $crawler;
            }
        }

        return null;
    }
}
