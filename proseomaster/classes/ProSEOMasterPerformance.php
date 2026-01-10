<?php
/**
 * ProSEOMaster - Advanced Performance Optimizer
 *
 * Comprehensive Core Web Vitals optimization:
 * - LCP (Largest Contentful Paint) optimization
 * - FCP (First Contentful Paint) optimization
 * - CLS (Cumulative Layout Shift) prevention
 * - INP (Interaction to Next Paint) optimization
 * - TTFB (Time to First Byte) optimization
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterPerformance
{
    /** @var Context */
    protected $context;

    /** @var array Critical resources to preload */
    protected $criticalResources = array();

    /** @var array Deferred scripts */
    protected $deferredScripts = array();

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Generate all performance optimizations for header
     * @param string $pageType
     * @return string
     */
    public function generateHeaderOptimizations($pageType = 'index')
    {
        $output = '';

        // 1. Critical resource hints (preconnect, dns-prefetch)
        $output .= $this->generateResourceHints();

        // 2. Preload critical resources (LCP image, fonts, critical CSS)
        $output .= $this->generatePreloadTags($pageType);

        // 3. Critical CSS inline
        if (Configuration::get('PROSEOMASTER_ENABLE_CRITICAL_CSS')) {
            $output .= $this->getCriticalCss($pageType);
        }

        // 4. Font optimization
        $output .= $this->generateFontOptimization();

        // 5. Meta tags for performance
        $output .= $this->generatePerformanceMetaTags();

        return $output;
    }

    /**
     * Generate resource hints (preconnect, dns-prefetch)
     * @return string
     */
    public function generateResourceHints()
    {
        $output = '<!-- ProSEO Master: Resource Hints -->' . "\n";

        // Preconnect to critical origins (improves TTFB for external resources)
        $preconnectDomains = array(
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
        );

        foreach ($preconnectDomains as $domain) {
            $output .= '<link rel="preconnect" href="' . $domain . '" crossorigin>' . "\n";
        }

        // DNS-Prefetch for other domains
        $dnsPrefetchDomains = array(
            'connect.facebook.net',
            'www.facebook.com',
            'platform.twitter.com',
            'cdn.jsdelivr.net',
            'cdnjs.cloudflare.com',
            'ajax.googleapis.com',
        );

        foreach ($dnsPrefetchDomains as $domain) {
            $output .= '<link rel="dns-prefetch" href="//' . $domain . '">' . "\n";
        }

        // Preload critical theme CSS to avoid render-blocking
        $output .= $this->generateCssPreloads();

        return $output;
    }

    /**
     * Generate CSS preloads to eliminate render-blocking CSS
     * @return string
     */
    protected function generateCssPreloads()
    {
        $output = '<!-- ProSEO Master: CSS Preloads -->' . "\n";

        // Detect theme CSS files
        $themeCssPath = _PS_THEME_DIR_ . 'assets/css/';
        $cssFiles = array();

        // Common theme CSS files to preload
        $criticalCssFiles = array(
            'theme.css',
            'custom.css',
            'style.css',
        );

        foreach ($criticalCssFiles as $cssFile) {
            if (file_exists($themeCssPath . $cssFile)) {
                $cssUrl = _THEME_CSS_DIR_ . $cssFile . '?v=' . filemtime($themeCssPath . $cssFile);
                $output .= '<link rel="preload" href="' . $cssUrl . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
                $output .= '<noscript><link rel="stylesheet" href="' . $cssUrl . '"></noscript>' . "\n";
            }
        }

        return $output;
    }

    /**
     * Generate preload tags for critical resources
     * @param string $pageType
     * @return string
     */
    public function generatePreloadTags($pageType)
    {
        $output = '<!-- ProSEO Master: Preload Critical Resources -->' . "\n";

        // Preload logo (always critical)
        $logo = Configuration::get('PS_LOGO');
        if ($logo && file_exists(_PS_IMG_DIR_ . $logo)) {
            $logoUrl = _PS_IMG_ . $logo;
            $logoExt = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
            $logoType = $this->getMimeType($logoExt);
            $output .= '<link rel="preload" href="' . $logoUrl . '" as="image" type="' . $logoType . '" fetchpriority="high">' . "\n";
        }

        // Preload LCP image based on page type
        $lcpImage = $this->detectLCPImage($pageType);
        if ($lcpImage) {
            $output .= '<link rel="preload" href="' . $lcpImage['url'] . '" as="image"';
            if (!empty($lcpImage['srcset'])) {
                $output .= ' imagesrcset="' . $lcpImage['srcset'] . '"';
            }
            if (!empty($lcpImage['sizes'])) {
                $output .= ' imagesizes="' . $lcpImage['sizes'] . '"';
            }
            $output .= ' fetchpriority="high">' . "\n";
        }

        // Preload critical fonts
        $criticalFonts = $this->getCriticalFonts();
        foreach ($criticalFonts as $font) {
            $output .= '<link rel="preload" href="' . $font . '" as="font" type="font/woff2" crossorigin>' . "\n";
        }

        return $output;
    }

    /**
     * Detect LCP image based on page type
     * @param string $pageType
     * @return array|null
     */
    protected function detectLCPImage($pageType)
    {
        $link = $this->context->link;

        switch ($pageType) {
            case 'product':
                // Product cover image is likely LCP
                if (isset($this->context->smarty->tpl_vars['product'])) {
                    $product = $this->context->smarty->tpl_vars['product']->value;
                    if (is_array($product) && !empty($product['cover']['large']['url'])) {
                        return array(
                            'url' => $product['cover']['large']['url'],
                            'srcset' => $product['cover']['large']['url'] . ' 800w',
                            'sizes' => '(max-width: 768px) 100vw, 50vw',
                        );
                    }
                }
                break;

            case 'category':
                // First product image or category banner
                if (isset($this->context->smarty->tpl_vars['listing'])) {
                    $listing = $this->context->smarty->tpl_vars['listing']->value;
                    if (!empty($listing['products'][0]['cover']['medium']['url'])) {
                        return array(
                            'url' => $listing['products'][0]['cover']['medium']['url'],
                        );
                    }
                }
                break;

            case 'index':
                // Homepage slider/banner is likely LCP
                // Try to detect from common slider modules
                $sliderImage = $this->detectHomepageSliderImage();
                if ($sliderImage) {
                    return array('url' => $sliderImage);
                }
                break;
        }

        return null;
    }

    /**
     * Detect homepage slider image
     * @return string|null
     */
    protected function detectHomepageSliderImage()
    {
        // Check ps_imageslider module
        if (Module::isEnabled('ps_imageslider')) {
            $sql = 'SELECT image FROM ' . _DB_PREFIX_ . 'homeslider_slides hs
                    INNER JOIN ' . _DB_PREFIX_ . 'homeslider_slides_lang hsl ON hs.id_homeslider_slides = hsl.id_homeslider_slides
                    WHERE hs.active = 1 AND hsl.id_lang = ' . (int) $this->context->language->id . '
                    ORDER BY hs.position ASC LIMIT 1';

            $image = Db::getInstance()->getValue($sql);
            if ($image) {
                return _MODULE_DIR_ . 'ps_imageslider/images/' . $image;
            }
        }

        return null;
    }

    /**
     * Get critical fonts to preload
     * @return array
     */
    protected function getCriticalFonts()
    {
        $fonts = array();

        // Detect fonts from theme
        $themePath = _PS_THEME_DIR_ . 'assets/fonts/';
        if (is_dir($themePath)) {
            $files = glob($themePath . '*.woff2');
            if ($files) {
                // Preload only the most important font (usually first one)
                $fonts[] = str_replace(_PS_ROOT_DIR_, '', $files[0]);
            }
        }

        return $fonts;
    }

    /**
     * Generate font optimization CSS
     * @return string
     */
    public function generateFontOptimization()
    {
        $output = '<!-- ProSEO Master: Font Optimization -->' . "\n";

        // font-display: swap for all @font-face
        $output .= '<style>';
        $output .= '@font-face{font-display:swap!important}';

        // Optimize Google Fonts with font-display
        $output .= '.wf-loading *{opacity:1!important}';

        // Reduce CLS from font loading
        $output .= 'body{font-synthesis:none;text-rendering:optimizeLegibility;-webkit-font-smoothing:antialiased}';

        $output .= '</style>' . "\n";

        return $output;
    }

    /**
     * Generate performance meta tags
     * @return string
     */
    public function generatePerformanceMetaTags()
    {
        $output = '<!-- ProSEO Master: Performance Meta -->' . "\n";

        // Enable back-forward cache
        $output .= '<meta http-equiv="Cache-Control" content="public, max-age=31536000">' . "\n";

        // Color scheme hint (reduces CLS from dark mode)
        $output .= '<meta name="color-scheme" content="light">' . "\n";

        // Viewport with minimum scale (prevents zoom CLS)
        $output .= '<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1">' . "\n";

        return $output;
    }

    /**
     * Generate Critical CSS inline
     * @param string $pageType
     * @return string
     */
    public function getCriticalCss($pageType)
    {
        $css = '<!-- ProSEO Master: Critical CSS -->' . "\n";
        $css .= '<style id="critical-css">';

        // Base critical CSS (above-the-fold essential styles)
        $baseCss = '
            /* Reset & Base */
            *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
            html{-webkit-text-size-adjust:100%;line-height:1.15;scroll-behavior:smooth}
            body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;font-size:1rem;line-height:1.5;color:#212529;background:#fff;-webkit-font-smoothing:antialiased}

            /* Prevent CLS - Reserve space for elements */
            img,video,iframe{max-width:100%;height:auto;display:block}
            img:not([src]){visibility:hidden}
            img[loading="lazy"]{content-visibility:auto}

            /* Header skeleton */
            .header-top,#header{min-height:40px}
            .header-nav{min-height:50px}
            header,#header{position:relative;z-index:1000}

            /* Logo placeholder */
            .logo,.header-logo,#_desktop_logo{min-height:50px;min-width:150px}
            .logo img,.header-logo img{max-height:60px;width:auto}

            /* Navigation skeleton */
            nav,#_desktop_top_menu,.top-menu{min-height:45px}

            /* Container */
            .container{max-width:1200px;margin:0 auto;padding:0 15px;width:100%}
            .row{display:flex;flex-wrap:wrap;margin:0 -15px}
            .col,.col-md-6,.col-lg-4{padding:0 15px}

            /* Buttons base */
            .btn,button{display:inline-block;font-weight:400;text-align:center;vertical-align:middle;cursor:pointer;border:1px solid transparent;padding:.375rem .75rem;font-size:1rem;line-height:1.5;border-radius:.25rem;transition:color .15s,background .15s,border-color .15s}

            /* Forms base */
            input,select,textarea{font-family:inherit;font-size:inherit;line-height:inherit}
            input[type="search"],input[type="text"],input[type="email"]{width:100%;padding:.5rem;border:1px solid #ced4da;border-radius:.25rem}

            /* Links */
            a{color:#007bff;text-decoration:none;background:transparent}
            a:hover{color:#0056b3;text-decoration:underline}

            /* Lists */
            ul,ol{list-style:none}

            /* Utility */
            .clearfix::after{content:"";display:table;clear:both}
            .hidden,.d-none{display:none!important}
            .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}
        ';

        $css .= $this->minifyCss($baseCss);

        // Page-specific critical CSS
        switch ($pageType) {
            case 'index':
                $css .= $this->minifyCss('
                    /* Homepage hero/slider */
                    .carousel,.slider,.banner{position:relative;overflow:hidden;min-height:300px}
                    .carousel-inner,.slider-inner{position:relative;width:100%;overflow:hidden}
                    .carousel-item{display:none;width:100%}
                    .carousel-item.active{display:block}
                    .carousel-item img{width:100%;height:auto;object-fit:cover}

                    /* Featured products grid */
                    .featured-products,.products{padding:2rem 0}
                    .products-grid,.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px}

                    /* Product card skeleton */
                    .product-miniature,.product-item{background:#fff;border-radius:4px;overflow:hidden;min-height:350px}
                    .product-thumbnail,.product-image{aspect-ratio:1/1;overflow:hidden;background:#f8f9fa}
                    .product-thumbnail img{width:100%;height:100%;object-fit:contain}
                ');
                break;

            case 'category':
                $css .= $this->minifyCss('
                    /* Category layout */
                    #products,.products-section{padding:1rem 0}
                    .products-grid,.product-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px}

                    /* Product card */
                    .product-miniature{background:#fff;padding:10px;border-radius:4px;min-height:350px}
                    .thumbnail-container{aspect-ratio:1/1;overflow:hidden;margin-bottom:10px;background:#f8f9fa}
                    .thumbnail-container img{width:100%;height:100%;object-fit:contain}

                    /* Product info skeleton */
                    .product-title{font-size:.9rem;line-height:1.3;min-height:2.6em;overflow:hidden}
                    .product-price-and-shipping{min-height:25px}
                    .price{font-weight:700;font-size:1.1rem}

                    /* Sidebar/Filters */
                    #left-column,.sidebar{width:250px;min-height:200px}
                    .facet-title{padding:10px 0;font-weight:600}

                    /* Pagination */
                    .pagination{display:flex;justify-content:center;padding:20px 0;min-height:50px}
                ');
                break;

            case 'product':
                $css .= $this->minifyCss('
                    /* Product page layout */
                    #content-wrapper,.product-container{padding:20px 0}
                    .product-leftside,.product-images{width:50%}
                    .product-rightside,.product-info{width:50%;padding-left:30px}

                    /* Product images */
                    .product-cover{position:relative;overflow:hidden;aspect-ratio:1/1;background:#f8f9fa}
                    .product-cover img{width:100%;height:100%;object-fit:contain}
                    .product-images-thumbs,.js-qv-product-images{display:flex;gap:10px;margin-top:10px}
                    .product-images-thumbs img{width:80px;height:80px;object-fit:contain;cursor:pointer}

                    /* Product info */
                    h1.product-title,.h1{font-size:1.5rem;font-weight:700;margin-bottom:15px;min-height:1.5em}
                    .product-prices{margin-bottom:20px;min-height:40px}
                    .current-price,.product-price{font-size:1.5rem;font-weight:700}
                    .product-description-short{margin-bottom:20px;min-height:60px}

                    /* Add to cart */
                    .product-add-to-cart,.add-to-cart{margin:20px 0}
                    .add-to-cart .btn{padding:15px 30px;font-size:1.1rem;min-width:200px}
                    .product-quantity{display:flex;align-items:center;margin-bottom:15px}
                    .qty input{width:60px;text-align:center;padding:10px;border:1px solid #ddd}

                    @media(max-width:768px){
                        .product-leftside,.product-rightside,.product-images,.product-info{width:100%;padding:0}
                        .product-rightside,.product-info{margin-top:20px}
                    }
                ');
                break;

            case 'cart':
                $css .= $this->minifyCss('
                    .cart-grid{display:grid;grid-template-columns:1fr 350px;gap:30px}
                    .cart-items{min-height:200px}
                    .cart-item{display:flex;padding:20px 0;border-bottom:1px solid #eee}
                    .cart-item-img{width:100px;margin-right:20px}
                    .cart-summary{background:#f8f9fa;padding:20px;border-radius:4px;min-height:300px}
                    @media(max-width:768px){.cart-grid{grid-template-columns:1fr}}
                ');
                break;
        }

        $css .= '</style>' . "\n";

        // Add script to remove critical CSS after main CSS loads
        $css .= '<script>';
        $css .= 'window.addEventListener("load",function(){var c=document.getElementById("critical-css");if(c){setTimeout(function(){c.remove()},100)}});';
        $css .= '</script>' . "\n";

        return $css;
    }

    /**
     * Process HTML to optimize for Core Web Vitals
     * @param string $html
     * @return string
     */
    public function optimizeHtml($html)
    {
        // 1. Add lazy loading to images (except above-the-fold)
        $html = $this->addLazyLoading($html);

        // 2. Add explicit dimensions to images (prevents CLS)
        $html = $this->addImageDimensions($html);

        // 3. Defer non-critical JavaScript
        $html = $this->deferJavaScript($html);

        // 4. Optimize iframes (lazy load, add dimensions)
        $html = $this->optimizeIframes($html);

        // 5. Add fetchpriority to LCP candidates
        $html = $this->addFetchPriority($html);

        // 6. Preload fonts inline
        $html = $this->inlinePreloadFonts($html);

        return $html;
    }

    /**
     * Add lazy loading to images
     * @param string $html
     * @return string
     */
    public function addLazyLoading($html)
    {
        $imageCount = 0;
        $aboveFoldLimit = 4; // First 4 images are considered above-the-fold

        $html = preg_replace_callback(
            '/<img([^>]+)>/i',
            function ($matches) use (&$imageCount, $aboveFoldLimit) {
                $imgTag = $matches[0];
                $attrs = $matches[1];
                $imageCount++;

                // Skip if already has loading attribute
                if (strpos($attrs, 'loading=') !== false) {
                    return $imgTag;
                }

                // Check if it's a critical image (above the fold)
                $isCritical = (
                    $imageCount <= $aboveFoldLimit ||
                    preg_match('/logo|hero|slider|banner|carousel|cover/i', $attrs)
                );

                if ($isCritical) {
                    // Critical images: eager loading + high priority
                    $imgTag = str_replace('<img', '<img loading="eager" fetchpriority="high" decoding="sync"', $imgTag);
                } else {
                    // Non-critical images: lazy loading
                    $imgTag = str_replace('<img', '<img loading="lazy" decoding="async"', $imgTag);
                }

                return $imgTag;
            },
            $html
        );

        return $html;
    }

    /**
     * Add explicit dimensions to images to prevent CLS
     * @param string $html
     * @return string
     */
    public function addImageDimensions($html)
    {
        $html = preg_replace_callback(
            '/<img([^>]+)>/i',
            function ($matches) {
                $imgTag = $matches[0];
                $attrs = $matches[1];

                // Skip if already has both width and height with numeric values
                if (preg_match('/\bwidth\s*=\s*["\']?\d+/i', $attrs) && preg_match('/\bheight\s*=\s*["\']?\d+/i', $attrs)) {
                    return $imgTag;
                }

                // Try to get dimensions from src
                $width = null;
                $height = null;

                if (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $srcMatch)) {
                    $src = $srcMatch[1];

                    // Try to get real dimensions from local file
                    $dimensions = $this->getImageDimensions($src);
                    if ($dimensions) {
                        $width = $dimensions['width'];
                        $height = $dimensions['height'];
                    } else {
                        // Detect dimensions from URL pattern (PrestaShop image types)
                        $dimensions = $this->detectDimensionsFromUrl($src);
                        if ($dimensions) {
                            $width = $dimensions['width'];
                            $height = $dimensions['height'];
                        }
                    }
                }

                // Apply dimensions
                if ($width && $height) {
                    // Add explicit width and height
                    if (!preg_match('/\bwidth=/i', $attrs)) {
                        $imgTag = str_replace('<img', '<img width="' . $width . '"', $imgTag);
                    }
                    if (!preg_match('/\bheight=/i', $attrs)) {
                        $imgTag = str_replace('<img', '<img height="' . $height . '"', $imgTag);
                    }
                } else {
                    // Fallback: Add aspect-ratio for common image types
                    $aspectRatio = $this->detectAspectRatio($attrs);

                    if (!preg_match('/\bstyle=/i', $attrs)) {
                        $imgTag = str_replace('<img', '<img style="aspect-ratio:' . $aspectRatio . ';object-fit:contain;width:100%;height:auto"', $imgTag);
                    }

                    // Add placeholder dimensions
                    if (!preg_match('/\bwidth=/i', $attrs)) {
                        $imgTag = str_replace('<img', '<img width="300"', $imgTag);
                    }
                    if (!preg_match('/\bheight=/i', $attrs)) {
                        $imgTag = str_replace('<img', '<img height="300"', $imgTag);
                    }
                }

                return $imgTag;
            },
            $html
        );

        return $html;
    }

    /**
     * Get image dimensions from local file
     * @param string $src
     * @return array|null
     */
    protected function getImageDimensions($src)
    {
        // Convert URL to local path
        $localPath = null;

        if (strpos($src, _PS_BASE_URL_) !== false) {
            $localPath = str_replace(_PS_BASE_URL_, _PS_ROOT_DIR_ . '/', $src);
        } elseif (strpos($src, '/') === 0) {
            $localPath = _PS_ROOT_DIR_ . $src;
        }

        // Remove query string
        if ($localPath && strpos($localPath, '?') !== false) {
            $localPath = substr($localPath, 0, strpos($localPath, '?'));
        }

        if ($localPath && file_exists($localPath)) {
            $size = @getimagesize($localPath);
            if ($size && $size[0] > 0 && $size[1] > 0) {
                return array(
                    'width' => $size[0],
                    'height' => $size[1],
                );
            }
        }

        return null;
    }

    /**
     * Detect dimensions from PrestaShop image URL pattern
     * @param string $src
     * @return array|null
     */
    protected function detectDimensionsFromUrl($src)
    {
        // PrestaShop image type dimensions
        $imageTypes = array(
            'large' => array('width' => 800, 'height' => 800),
            'home' => array('width' => 250, 'height' => 250),
            'medium' => array('width' => 452, 'height' => 452),
            'small' => array('width' => 98, 'height' => 98),
            'cart' => array('width' => 125, 'height' => 125),
            'category' => array('width' => 960, 'height' => 350),
        );

        foreach ($imageTypes as $type => $dims) {
            if (preg_match('/_' . $type . '\./', $src) || preg_match('/-' . $type . '\./', $src)) {
                return $dims;
            }
        }

        return null;
    }

    /**
     * Detect appropriate aspect ratio based on image context
     * @param string $attrs
     * @return string
     */
    protected function detectAspectRatio($attrs)
    {
        // Product images: 1:1
        if (preg_match('/product|thumbnail|cart|miniature/i', $attrs)) {
            return '1/1';
        }

        // Banner/slider images: 16:9
        if (preg_match('/banner|slider|carousel|hero/i', $attrs)) {
            return '16/9';
        }

        // Category images: wider
        if (preg_match('/category/i', $attrs)) {
            return '3/1';
        }

        // Logo: assume wider
        if (preg_match('/logo/i', $attrs)) {
            return '3/1';
        }

        // Default: square
        return '1/1';
    }

    /**
     * Defer non-critical JavaScript
     * @param string $html
     * @return string
     */
    public function deferJavaScript($html)
    {
        // Scripts that should NEVER be deferred (critical for site functionality)
        $criticalScripts = array(
            'jquery',
            'core.js',
            'theme.js',
            'prestashop',
            'critical',
            // Payment providers - NEVER defer these
            'paypal',
            'stripe',
            'braintree',
            'adyen',
            'mollie',
            'klarna',
            'afterpay',
            'affirm',
            'clearpay',
            'shopify',
            'checkout',
            'payment',
            'pay',
            'card',
            'creditcard',
            'credit-card',
            'worldpay',
            'sagepay',
            'authorize',
            'square',
            'amazon-pay',
            'apple-pay',
            'google-pay',
            'gpay',
            'venmo',
            'payu',
            'razorpay',
            'paytm',
            'phonepe',
            'nexi',
            'satispay',
            'scalapay',
            'sofort',
            'ideal',
            'bancontact',
            'giropay',
            'przelewy24',
            'blik',
            'multibanco',
            'mbway',
            'paysafecard',
            'skrill',
            'neteller',
            '2checkout',
            'securepay',
            'eway',
            'firstdata',
            'bluepay',
            'cybersource',
            'moneris',
            'bambora',
            'cardconnect',
            'heartland',
            'usaepay',
            'nmi',
            'authorizenet',
            'paynl',
            'buckaroo',
            'sisow',
            'multisafepay',
            'omise',
            'instamojo',
            'cashfree',
            'paymob',
            'fawry',
            'tap',
            'moyasar',
            'hyperpay',
            'payfort',
            'telr',
            'checkout.com',
            // Cart & Order scripts
            'cart',
            'order',
            'basket',
            'purchase',
            'shipping',
            'delivery',
            // Form validation
            'validate',
            'validator',
            'form',
            // reCAPTCHA
            'recaptcha',
            'grecaptcha',
            'hcaptcha',
            'turnstile',
        );

        // Scripts that should be loaded async (tracking/marketing only)
        $asyncScripts = array(
            'analytics',
            'gtag',
            'gtm',
            'facebook',
            'fbevents',
            'fb-pixel',
            'twitter',
            'instagram',
            'pinterest',
            'hotjar',
            'tawk',
            'zendesk',
            'intercom',
            'crisp',
            'livechat',
            'drift',
            'hubspot',
            'mailchimp',
            'klaviyo',
            'omnisend',
            'segment',
            'mixpanel',
            'heap',
            'amplitude',
            'fullstory',
            'lucky',
            'mouseflow',
            'crazy',
            'clarity',
        );

        $html = preg_replace_callback(
            '/<script([^>]*)>/i',
            function ($matches) use ($criticalScripts, $asyncScripts) {
                $scriptTag = $matches[0];
                $attrs = $matches[1];

                // Skip if already has defer or async
                if (preg_match('/\b(defer|async)\b/i', $attrs)) {
                    return $scriptTag;
                }

                // Skip inline scripts without src
                if (!preg_match('/\bsrc=/i', $attrs)) {
                    return $scriptTag;
                }

                // Check if it's a critical script
                foreach ($criticalScripts as $critical) {
                    if (stripos($attrs, $critical) !== false) {
                        return $scriptTag;
                    }
                }

                // Check if should be async
                foreach ($asyncScripts as $async) {
                    if (stripos($attrs, $async) !== false) {
                        return str_replace('<script', '<script async', $scriptTag);
                    }
                }

                // Default: defer
                return str_replace('<script', '<script defer', $scriptTag);
            },
            $html
        );

        return $html;
    }

    /**
     * Optimize iframes
     * @param string $html
     * @return string
     */
    public function optimizeIframes($html)
    {
        $html = preg_replace_callback(
            '/<iframe([^>]+)>/i',
            function ($matches) {
                $iframeTag = $matches[0];
                $attrs = $matches[1];

                // Add lazy loading if not present
                if (strpos($attrs, 'loading=') === false) {
                    $iframeTag = str_replace('<iframe', '<iframe loading="lazy"', $iframeTag);
                }

                // Add dimensions if not present (prevents CLS)
                if (!preg_match('/\bwidth=/i', $attrs)) {
                    $iframeTag = str_replace('<iframe', '<iframe width="100%"', $iframeTag);
                }
                if (!preg_match('/\bheight=/i', $attrs)) {
                    $iframeTag = str_replace('<iframe', '<iframe height="400"', $iframeTag);
                }

                // Add title for accessibility
                if (!preg_match('/\btitle=/i', $attrs)) {
                    $iframeTag = str_replace('<iframe', '<iframe title="Embedded content"', $iframeTag);
                }

                return $iframeTag;
            },
            $html
        );

        return $html;
    }

    /**
     * Add fetchpriority="high" to LCP candidates
     * @param string $html
     * @return string
     */
    public function addFetchPriority($html)
    {
        // Find first large image in content (likely LCP)
        $found = false;

        $html = preg_replace_callback(
            '/<img([^>]+)>/i',
            function ($matches) use (&$found) {
                if ($found) {
                    return $matches[0];
                }

                $attrs = $matches[1];

                // Check if this looks like an LCP candidate
                $isLcpCandidate = (
                    preg_match('/cover|hero|banner|slider|carousel|main|featured/i', $attrs) ||
                    preg_match('/product-cover|product-image|js-qv-product-cover/i', $attrs)
                );

                if ($isLcpCandidate && strpos($attrs, 'fetchpriority=') === false) {
                    $found = true;
                    return str_replace('<img', '<img fetchpriority="high"', $matches[0]);
                }

                return $matches[0];
            },
            $html
        );

        return $html;
    }

    /**
     * Inline preload fonts CSS
     * @param string $html
     * @return string
     */
    public function inlinePreloadFonts($html)
    {
        // Find and extract Google Fonts links
        if (preg_match_all('/<link[^>]+fonts\.googleapis\.com[^>]+>/i', $html, $matches)) {
            foreach ($matches[0] as $fontLink) {
                // Add media="print" onload="this.media='all'" for non-blocking
                if (strpos($fontLink, 'media=') === false) {
                    $newLink = str_replace(
                        '<link',
                        '<link media="print" onload="this.media=\'all\'"',
                        $fontLink
                    );
                    // Add noscript fallback
                    $noscript = '<noscript>' . str_replace(' media="print" onload="this.media=\'all\'"', '', $fontLink) . '</noscript>';

                    $html = str_replace($fontLink, $newLink . $noscript, $html);
                }
            }
        }

        return $html;
    }

    /**
     * Minify CSS
     * @param string $css
     * @return string
     */
    public function minifyCss($css)
    {
        // Remove comments
        $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css);
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        // Remove spaces around selectors
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        // Remove trailing semicolons
        $css = str_replace(';}', '}', $css);
        // Remove newlines
        $css = str_replace(array("\r\n", "\r", "\n"), '', $css);

        return trim($css);
    }

    /**
     * Get MIME type for image extension
     * @param string $ext
     * @return string
     */
    protected function getMimeType($ext)
    {
        $types = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
        );

        return isset($types[$ext]) ? $types[$ext] : 'image/jpeg';
    }

    /**
     * Generate HTTP cache headers
     * @param string $pageType
     * @return array
     */
    public function getCacheHeaders($pageType)
    {
        $headers = array();

        switch ($pageType) {
            case 'product':
            case 'category':
                // Cache for 1 hour, stale-while-revalidate for 1 day
                $headers['Cache-Control'] = 'public, max-age=3600, stale-while-revalidate=86400, stale-if-error=604800';
                $headers['Vary'] = 'Accept-Encoding, Cookie';
                break;

            case 'cart':
            case 'order':
            case 'my-account':
            case 'checkout':
                // No cache for user-specific pages
                $headers['Cache-Control'] = 'private, no-cache, no-store, must-revalidate';
                $headers['Pragma'] = 'no-cache';
                $headers['Expires'] = '0';
                break;

            case 'cms':
                // Cache for 1 day
                $headers['Cache-Control'] = 'public, max-age=86400, stale-while-revalidate=604800';
                break;

            default:
                // Default: 30 minutes
                $headers['Cache-Control'] = 'public, max-age=1800, stale-while-revalidate=3600';
        }

        return $headers;
    }

    /**
     * Generate .htaccess rules for performance
     * @return string
     */
    public function generateHtaccessRules()
    {
        $rules = '
# BEGIN ProSEO Master Performance Rules

# Enable GZIP Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript
    AddOutputFilterByType DEFLATE application/javascript application/x-javascript application/json
    AddOutputFilterByType DEFLATE application/xml application/xhtml+xml application/rss+xml
    AddOutputFilterByType DEFLATE image/svg+xml font/woff font/woff2 font/ttf font/otf
</IfModule>

# Enable Brotli Compression (if available)
<IfModule mod_brotli.c>
    AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css text/javascript
    AddOutputFilterByType BROTLI_COMPRESS application/javascript application/json application/xml
    AddOutputFilterByType BROTLI_COMPRESS image/svg+xml font/woff2
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On

    # Images
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/avif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"

    # Fonts
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType font/ttf "access plus 1 year"
    ExpiresByType font/otf "access plus 1 year"
    ExpiresByType application/font-woff "access plus 1 year"
    ExpiresByType application/font-woff2 "access plus 1 year"

    # CSS & JavaScript
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"

    # HTML
    ExpiresByType text/html "access plus 0 seconds"

    # Data interchange
    ExpiresByType application/json "access plus 0 seconds"
    ExpiresByType application/xml "access plus 0 seconds"
    ExpiresByType text/xml "access plus 0 seconds"
</IfModule>

# Cache-Control Headers
<IfModule mod_headers.c>
    # 1 Year for static assets
    <FilesMatch "\.(ico|pdf|flv|jpg|jpeg|png|gif|webp|avif|svg|js|css|swf|woff|woff2|ttf|otf)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>

    # 1 Month for CSS/JS
    <FilesMatch "\.(css|js)$">
        Header set Cache-Control "public, max-age=2592000"
    </FilesMatch>

    # No cache for HTML
    <FilesMatch "\.(html|htm|php)$">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires "0"
    </FilesMatch>

    # Remove ETag (optional - reduces header size)
    Header unset ETag

    # Security Headers
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"

    # Enable Keep-Alive
    Header set Connection keep-alive
</IfModule>

# Disable ETags
FileETag None

# END ProSEO Master Performance Rules
';

        return $rules;
    }

    /**
     * Generate inline performance JavaScript
     * @return string
     */
    public function getPerformanceScript()
    {
        return '
<script id="proseo-performance">
(function(){
    "use strict";

    // 1. Lazy load images with IntersectionObserver
    if("IntersectionObserver"in window){
        var imgObserver=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    var img=entry.target;
                    if(img.dataset.src){
                        img.src=img.dataset.src;
                        img.removeAttribute("data-src");
                    }
                    if(img.dataset.srcset){
                        img.srcset=img.dataset.srcset;
                        img.removeAttribute("data-srcset");
                    }
                    img.classList.add("loaded");
                    imgObserver.unobserve(img);
                }
            });
        },{rootMargin:"100px 0px",threshold:0.01});

        document.querySelectorAll("img[data-src],img[loading=lazy]").forEach(function(img){
            imgObserver.observe(img);
        });
    }

    // 2. Preload LCP image
    var lcpCandidates=document.querySelectorAll(".product-cover img,.carousel-item.active img,.hero img,.banner img");
    if(lcpCandidates.length>0){
        var lcpImg=lcpCandidates[0];
        if(lcpImg.src&&!document.querySelector("link[rel=preload][href=\'"+lcpImg.src+"\']")){
            var link=document.createElement("link");
            link.rel="preload";
            link.as="image";
            link.href=lcpImg.src;
            document.head.appendChild(link);
        }
    }

    // 3. Defer third-party scripts until user interaction
    var thirdPartyLoaded=false;
    function loadThirdParty(){
        if(thirdPartyLoaded)return;
        thirdPartyLoaded=true;

        document.querySelectorAll("script[data-defer-third-party]").forEach(function(script){
            var newScript=document.createElement("script");
            if(script.src)newScript.src=script.src;
            else newScript.textContent=script.textContent;
            newScript.async=true;
            document.body.appendChild(newScript);
            script.remove();
        });
    }

    // Load third-party on user interaction
    ["scroll","click","mousemove","touchstart","keydown"].forEach(function(event){
        document.addEventListener(event,loadThirdParty,{once:true,passive:true});
    });

    // Or after 5 seconds
    setTimeout(loadThirdParty,5000);

    // 4. Fix CLS from late-loading fonts
    if("fonts"in document){
        document.fonts.ready.then(function(){
            document.body.classList.add("fonts-loaded");
        });
    }

    // 5. Report Core Web Vitals (debug)
    if(window.PerformanceObserver&&location.search.indexOf("debug_cwv")>-1){
        try{
            new PerformanceObserver(function(l){
                l.getEntries().forEach(function(e){
                    console.log("LCP:",Math.round(e.startTime),"ms",e.element);
                });
            }).observe({type:"largest-contentful-paint",buffered:true});

            new PerformanceObserver(function(l){
                l.getEntries().forEach(function(e){
                    console.log("FID:",Math.round(e.processingStart-e.startTime),"ms");
                });
            }).observe({type:"first-input",buffered:true});

            var cls=0;
            new PerformanceObserver(function(l){
                l.getEntries().forEach(function(e){
                    if(!e.hadRecentInput)cls+=e.value;
                });
                console.log("CLS:",cls.toFixed(4));
            }).observe({type:"layout-shift",buffered:true});
        }catch(e){}
    }
})();
</script>';
    }
}
