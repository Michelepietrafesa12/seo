<?php
/**
 * ProSEOMaster - Performance Optimizer
 *
 * Optimizations for Core Web Vitals:
 * - Critical CSS extraction
 * - JavaScript defer/async
 * - Image lazy loading
 * - Resource hints (preload, prefetch)
 * - Cache optimization headers
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

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Generate resource hints for faster loading
     * @return string
     */
    public function generateResourceHints()
    {
        $output = '';

        // DNS Prefetch for external domains
        $externalDomains = array(
            'fonts.googleapis.com',
            'fonts.gstatic.com',
            'www.google-analytics.com',
            'www.googletagmanager.com',
            'connect.facebook.net',
        );

        foreach ($externalDomains as $domain) {
            $output .= '<link rel="dns-prefetch" href="//' . $domain . '">' . "\n";
        }

        // Preconnect for critical resources
        $output .= '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
        $output .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

        // Preload critical assets
        $output .= $this->generatePreloadHints();

        return $output;
    }

    /**
     * Generate preload hints for critical resources
     * @return string
     */
    protected function generatePreloadHints()
    {
        $output = '';

        // Preload logo
        $logo = Configuration::get('PS_LOGO');
        if ($logo) {
            $logoUrl = _PS_IMG_ . $logo;
            $output .= '<link rel="preload" href="' . $logoUrl . '" as="image">' . "\n";
        }

        // Preload critical fonts (if known)
        $criticalFonts = array(
            // Add your critical fonts here
        );

        foreach ($criticalFonts as $font) {
            $output .= '<link rel="preload" href="' . $font . '" as="font" type="font/woff2" crossorigin>' . "\n";
        }

        return $output;
    }

    /**
     * Generate lazy loading attributes for images
     * @param string $html
     * @return string
     */
    public function addLazyLoading($html)
    {
        // Add loading="lazy" to images without it
        $html = preg_replace_callback(
            '/<img([^>]+)>/i',
            function ($matches) {
                $imgTag = $matches[0];

                // Skip if already has loading attribute
                if (strpos($imgTag, 'loading=') !== false) {
                    return $imgTag;
                }

                // Skip critical images (above the fold)
                if (strpos($imgTag, 'logo') !== false ||
                    strpos($imgTag, 'hero') !== false ||
                    strpos($imgTag, 'slider') !== false) {
                    return str_replace('<img', '<img loading="eager" fetchpriority="high"', $imgTag);
                }

                // Add lazy loading
                return str_replace('<img', '<img loading="lazy"', $imgTag);
            },
            $html
        );

        // Add loading="lazy" to iframes
        $html = preg_replace_callback(
            '/<iframe([^>]+)>/i',
            function ($matches) {
                $iframeTag = $matches[0];

                if (strpos($iframeTag, 'loading=') !== false) {
                    return $iframeTag;
                }

                return str_replace('<iframe', '<iframe loading="lazy"', $iframeTag);
            },
            $html
        );

        return $html;
    }

    /**
     * Generate script loading optimization attributes
     * @param string $scriptUrl
     * @param string $type ('critical', 'deferred', 'async')
     * @return string
     */
    public function getScriptAttributes($scriptUrl, $type = 'deferred')
    {
        switch ($type) {
            case 'critical':
                return '';
            case 'async':
                return ' async';
            case 'deferred':
            default:
                return ' defer';
        }
    }

    /**
     * Generate image srcset for responsive images
     * @param int $idProduct
     * @param int $idImage
     * @param string $linkRewrite
     * @return string
     */
    public function generateImageSrcset($idProduct, $idImage, $linkRewrite)
    {
        $link = $this->context->link;
        $srcset = array();

        // Define image sizes
        $sizes = array(
            'small' => 150,
            'medium' => 300,
            'large' => 600,
            'home' => 250,
            'thickbox' => 800,
        );

        foreach ($sizes as $sizeName => $width) {
            $formattedName = ImageType::getFormattedName($sizeName);
            if ($formattedName) {
                $imageUrl = $link->getImageLink(
                    $linkRewrite,
                    $idProduct . '-' . $idImage,
                    $formattedName
                );
                $srcset[] = $imageUrl . ' ' . $width . 'w';
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Generate optimized image tag
     * @param array $image
     * @param Product $product
     * @param string $size
     * @param bool $isAboveFold
     * @return string
     */
    public function generateOptimizedImage($image, $product, $size = 'large', $isAboveFold = false)
    {
        $link = $this->context->link;

        $imageUrl = $link->getImageLink(
            $product->link_rewrite,
            $product->id . '-' . $image['id_image'],
            ImageType::getFormattedName($size)
        );

        $alt = !empty($image['legend']) ? $image['legend'] : $product->name;
        $srcset = $this->generateImageSrcset($product->id, $image['id_image'], $product->link_rewrite);

        $loading = $isAboveFold ? 'eager' : 'lazy';
        $fetchpriority = $isAboveFold ? ' fetchpriority="high"' : '';
        $decoding = $isAboveFold ? 'sync' : 'async';

        return sprintf(
            '<img src="%s" srcset="%s" sizes="(max-width: 768px) 100vw, 50vw" alt="%s" loading="%s" decoding="%s"%s>',
            htmlspecialchars($imageUrl),
            htmlspecialchars($srcset),
            htmlspecialchars($alt),
            $loading,
            $decoding,
            $fetchpriority
        );
    }

    /**
     * Generate critical CSS inline style
     * @param string $pageType
     * @return string
     */
    public function getCriticalCss($pageType)
    {
        $criticalCss = '';

        // Base critical CSS
        $baseCss = '
            *,*::before,*::after{box-sizing:border-box}
            html{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
            body{margin:0;font-family:system-ui,-apple-system,sans-serif;line-height:1.5}
            img,video{max-width:100%;height:auto;display:block}
            a{text-decoration:none;color:inherit}
            h1,h2,h3,h4,h5,h6{margin:0 0 0.5em;line-height:1.2}
            p{margin:0 0 1em}
            .container{max-width:1200px;margin:0 auto;padding:0 15px}
            .header{position:relative;z-index:100}
            .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}
        ';

        $criticalCss .= $baseCss;

        // Page-specific critical CSS
        switch ($pageType) {
            case 'product':
                $criticalCss .= '
                    .product-cover{position:relative;overflow:hidden}
                    .product-cover img{width:100%}
                    .product-title{font-size:1.5rem;font-weight:700}
                    .product-price{font-size:1.25rem;font-weight:700;color:#333}
                    .product-add-to-cart{padding:1rem 2rem;background:#333;color:#fff;border:none;cursor:pointer}
                ';
                break;

            case 'category':
                $criticalCss .= '
                    .products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px}
                    .product-miniature{position:relative;background:#fff}
                    .product-thumbnail{aspect-ratio:1;overflow:hidden}
                    .product-thumbnail img{width:100%;height:100%;object-fit:contain}
                ';
                break;

            case 'index':
                $criticalCss .= '
                    .carousel{position:relative;overflow:hidden}
                    .carousel-item{display:none}
                    .carousel-item.active{display:block}
                    .featured-products{padding:2rem 0}
                ';
                break;
        }

        return '<style>' . $this->minifyCss($criticalCss) . '</style>';
    }

    /**
     * Minify CSS
     * @param string $css
     * @return string
     */
    protected function minifyCss($css)
    {
        // Remove comments
        $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css);
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        // Remove spaces around selectors
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        // Remove trailing semicolons
        $css = str_replace(';}', '}', $css);

        return trim($css);
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
                $headers['Cache-Control'] = 'public, max-age=3600, stale-while-revalidate=86400';
                break;

            case 'cart':
            case 'order':
            case 'my-account':
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
                $headers['Cache-Control'] = 'public, max-age=1800';
        }

        return $headers;
    }

    /**
     * Generate Web Vitals monitoring script
     * @return string
     */
    public function getWebVitalsScript()
    {
        return '
<script>
(function() {
    function sendToAnalytics(metric) {
        if (typeof gtag === "function") {
            gtag("event", metric.name, {
                value: Math.round(metric.name === "CLS" ? metric.value * 1000 : metric.value),
                metric_id: metric.id,
                metric_delta: Math.round(metric.name === "CLS" ? metric.delta * 1000 : metric.delta),
                non_interaction: true
            });
        }
    }
    if ("web-vital" in window) {
        webVitals.getCLS(sendToAnalytics);
        webVitals.getFID(sendToAnalytics);
        webVitals.getLCP(sendToAnalytics);
        webVitals.getFCP(sendToAnalytics);
        webVitals.getTTFB(sendToAnalytics);
    }
})();
</script>';
    }
}
