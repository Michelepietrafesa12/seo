<?php
/**
 * ProSEOMaster Helper Class
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSEOMasterHelper
{
    /**
     * Validate a schema against Google's requirements
     * @param array $schema
     * @return array List of warnings/errors
     */
    public static function validateProductSchema($schema)
    {
        $issues = array();

        // Check required fields
        $requiredFields = array('name', 'image', 'offers');

        foreach ($requiredFields as $field) {
            if (empty($schema[$field])) {
                $issues[] = array(
                    'type' => 'error',
                    'message' => sprintf('Missing required field: %s', $field),
                );
            }
        }

        // Check recommended fields
        $recommendedFields = array('brand', 'sku', 'description');

        foreach ($recommendedFields as $field) {
            if (empty($schema[$field])) {
                $issues[] = array(
                    'type' => 'warning',
                    'message' => sprintf('Missing recommended field: %s', $field),
                );
            }
        }

        // Check GTIN/MPN - at least one should be present
        $identifierFields = array('gtin', 'gtin8', 'gtin12', 'gtin13', 'gtin14', 'mpn');
        $hasIdentifier = false;

        foreach ($identifierFields as $field) {
            if (!empty($schema[$field])) {
                $hasIdentifier = true;
                break;
            }
        }

        if (!$hasIdentifier) {
            $issues[] = array(
                'type' => 'warning',
                'message' => 'No product identifier (GTIN or MPN) provided. This may affect rich results eligibility.',
            );
        }

        // Check offers
        if (!empty($schema['offers'])) {
            $offers = $schema['offers'];
            if (empty($offers['price'])) {
                $issues[] = array(
                    'type' => 'error',
                    'message' => 'Missing price in offers',
                );
            }
            if (empty($offers['priceCurrency'])) {
                $issues[] = array(
                    'type' => 'error',
                    'message' => 'Missing priceCurrency in offers',
                );
            }
            if (empty($offers['availability'])) {
                $issues[] = array(
                    'type' => 'warning',
                    'message' => 'Missing availability in offers',
                );
            }
        }

        return $issues;
    }

    /**
     * Generate robots meta content based on page type and settings
     * @param string $pageType
     * @param bool $indexable
     * @param bool $followable
     * @return string
     */
    public static function generateRobotsMeta($pageType, $indexable = true, $followable = true)
    {
        $directives = array();

        // Index directive
        $directives[] = $indexable ? 'index' : 'noindex';

        // Follow directive
        $directives[] = $followable ? 'follow' : 'nofollow';

        // Additional directives for specific page types
        switch ($pageType) {
            case 'search':
            case 'cart':
            case 'order':
            case 'my-account':
                $directives = array('noindex', 'nofollow');
                break;
            case 'product':
            case 'category':
            case 'cms':
                // These should be indexed
                break;
        }

        return implode(', ', $directives);
    }

    /**
     * Sanitize and validate URL
     * @param string $url
     * @return string|null
     */
    public static function sanitizeUrl($url)
    {
        $url = trim($url);

        if (empty($url)) {
            return null;
        }

        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $url;
    }

    /**
     * Get all available schema types for local business
     * @return array
     */
    public static function getLocalBusinessTypes()
    {
        return array(
            'LocalBusiness' => 'Local Business (Generic)',
            'Store' => 'Store',
            'AutoDealer' => 'Auto Dealer',
            'AutoPartsStore' => 'Auto Parts Store',
            'BikeStore' => 'Bike Store',
            'BookStore' => 'Book Store',
            'ClothingStore' => 'Clothing Store',
            'ComputerStore' => 'Computer Store',
            'ConvenienceStore' => 'Convenience Store',
            'DepartmentStore' => 'Department Store',
            'ElectronicsStore' => 'Electronics Store',
            'Florist' => 'Florist',
            'FurnitureStore' => 'Furniture Store',
            'GardenStore' => 'Garden Store',
            'GroceryStore' => 'Grocery Store',
            'HardwareStore' => 'Hardware Store',
            'HobbyShop' => 'Hobby Shop',
            'HomeGoodsStore' => 'Home Goods Store',
            'JewelryStore' => 'Jewelry Store',
            'LiquorStore' => 'Liquor Store',
            'MensClothingStore' => 'Mens Clothing Store',
            'MobilePhoneStore' => 'Mobile Phone Store',
            'MovieRentalStore' => 'Movie Rental Store',
            'MusicStore' => 'Music Store',
            'OfficeEquipmentStore' => 'Office Equipment Store',
            'OutletStore' => 'Outlet Store',
            'PawnShop' => 'Pawn Shop',
            'PetStore' => 'Pet Store',
            'ShoeStore' => 'Shoe Store',
            'SportingGoodsStore' => 'Sporting Goods Store',
            'TireShop' => 'Tire Shop',
            'ToyStore' => 'Toy Store',
            'WholesaleStore' => 'Wholesale Store',
            'Restaurant' => 'Restaurant',
            'Bakery' => 'Bakery',
            'BarOrPub' => 'Bar or Pub',
            'CafeOrCoffeeShop' => 'Cafe or Coffee Shop',
            'FastFoodRestaurant' => 'Fast Food Restaurant',
            'IceCreamShop' => 'Ice Cream Shop',
            'Winery' => 'Winery',
        );
    }

    /**
     * Generate FAQ schema from array of Q&A pairs
     * @param array $faqs Array of ['question' => '', 'answer' => '']
     * @return array
     */
    public static function generateFaqSchema($faqs)
    {
        if (empty($faqs)) {
            return null;
        }

        $mainEntity = array();

        foreach ($faqs as $faq) {
            if (!empty($faq['question']) && !empty($faq['answer'])) {
                $mainEntity[] = array(
                    '@type' => 'Question',
                    'name' => strip_tags($faq['question']),
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => strip_tags($faq['answer']),
                    ),
                );
            }
        }

        if (empty($mainEntity)) {
            return null;
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        );
    }

    /**
     * Generate HowTo schema
     * @param string $name
     * @param string $description
     * @param array $steps Array of step descriptions
     * @param string|null $image
     * @param string|null $totalTime ISO 8601 duration format
     * @return array
     */
    public static function generateHowToSchema($name, $description, $steps, $image = null, $totalTime = null)
    {
        if (empty($name) || empty($steps)) {
            return null;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $name,
            'description' => $description,
            'step' => array(),
        );

        if (!empty($image)) {
            $schema['image'] = $image;
        }

        if (!empty($totalTime)) {
            $schema['totalTime'] = $totalTime;
        }

        $position = 1;
        foreach ($steps as $step) {
            $stepData = array(
                '@type' => 'HowToStep',
                'position' => $position,
            );

            if (is_string($step)) {
                $stepData['text'] = $step;
            } elseif (is_array($step)) {
                if (!empty($step['name'])) {
                    $stepData['name'] = $step['name'];
                }
                if (!empty($step['text'])) {
                    $stepData['text'] = $step['text'];
                }
                if (!empty($step['image'])) {
                    $stepData['image'] = $step['image'];
                }
            }

            $schema['step'][] = $stepData;
            $position++;
        }

        return $schema;
    }

    /**
     * Format price for schema
     * @param float $price
     * @param int $decimals
     * @return string
     */
    public static function formatPrice($price, $decimals = 2)
    {
        return number_format((float) $price, $decimals, '.', '');
    }

    /**
     * Convert date to ISO 8601 format
     * @param string $date
     * @return string
     */
    public static function formatDateISO($date)
    {
        $timestamp = strtotime($date);
        return date('Y-m-d', $timestamp);
    }

    /**
     * Truncate text while preserving word boundaries
     * @param string $text
     * @param int $maxLength
     * @param string $suffix
     * @return string
     */
    public static function truncateText($text, $maxLength = 160, $suffix = '...')
    {
        $text = trim(strip_tags($text));

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $text = substr($text, 0, $maxLength - strlen($suffix));
        $lastSpace = strrpos($text, ' ');

        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }

        return $text . $suffix;
    }

    /**
     * Check if product comments module is installed and enabled
     * @return bool
     */
    public static function isProductCommentsEnabled()
    {
        return Module::isEnabled('productcomments') && class_exists('ProductComment');
    }

    /**
     * Get average rating for a product
     * @param int $idProduct
     * @return float|null
     */
    public static function getProductRating($idProduct)
    {
        if (!self::isProductCommentsEnabled()) {
            return null;
        }

        $avgGrade = ProductComment::getAverageGrade((int) $idProduct);

        return !empty($avgGrade) ? (float) $avgGrade : null;
    }

    /**
     * Get review count for a product
     * @param int $idProduct
     * @return int
     */
    public static function getProductReviewCount($idProduct)
    {
        if (!self::isProductCommentsEnabled()) {
            return 0;
        }

        return (int) ProductComment::getCommentNumber((int) $idProduct);
    }
}
