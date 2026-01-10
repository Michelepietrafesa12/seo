/**
 * ProSEO Master - Performance Optimization Script
 *
 * Core Web Vitals optimizations:
 * - Intersection Observer for lazy loading
 * - Priority hints for images
 * - Preload critical resources
 */

(function() {
    'use strict';

    // Lazy load images using Intersection Observer
    if ('IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;

                    // Load the image
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                        img.removeAttribute('data-srcset');
                    }

                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });

        // Observe all lazy images
        document.querySelectorAll('img[data-src], img[loading="lazy"]').forEach(function(img) {
            imageObserver.observe(img);
        });
    }

    // Preload LCP image if detected
    function preloadLCPImage() {
        var lcpCandidates = document.querySelectorAll('.product-cover img, .carousel-item.active img, .hero-image img');

        lcpCandidates.forEach(function(img, index) {
            if (index === 0) {
                var link = document.createElement('link');
                link.rel = 'preload';
                link.as = 'image';
                link.href = img.src || img.dataset.src;
                document.head.appendChild(link);
            }
        });
    }

    // Defer non-critical scripts
    function deferNonCriticalScripts() {
        var scripts = document.querySelectorAll('script[data-defer]');
        scripts.forEach(function(script) {
            var newScript = document.createElement('script');
            if (script.src) {
                newScript.src = script.src;
            } else {
                newScript.textContent = script.textContent;
            }
            newScript.async = true;
            document.body.appendChild(newScript);
            script.remove();
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            preloadLCPImage();
            deferNonCriticalScripts();
        });
    } else {
        preloadLCPImage();
        deferNonCriticalScripts();
    }

    // Report Web Vitals to console (for debugging)
    if (window.PerformanceObserver) {
        try {
            // LCP - Largest Contentful Paint
            new PerformanceObserver(function(entryList) {
                var entries = entryList.getEntries();
                var lastEntry = entries[entries.length - 1];
                console.log('ProSEO Master - LCP:', Math.round(lastEntry.startTime), 'ms');
            }).observe({type: 'largest-contentful-paint', buffered: true});

            // FID - First Input Delay
            new PerformanceObserver(function(entryList) {
                var entries = entryList.getEntries();
                entries.forEach(function(entry) {
                    console.log('ProSEO Master - FID:', Math.round(entry.processingStart - entry.startTime), 'ms');
                });
            }).observe({type: 'first-input', buffered: true});

            // CLS - Cumulative Layout Shift
            var clsValue = 0;
            new PerformanceObserver(function(entryList) {
                var entries = entryList.getEntries();
                entries.forEach(function(entry) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                });
                console.log('ProSEO Master - CLS:', clsValue.toFixed(4));
            }).observe({type: 'layout-shift', buffered: true});

        } catch (e) {
            // PerformanceObserver not fully supported
        }
    }

})();
