<?php
/**
 * Pro SEO Module - Classe per generazione Schema BreadcrumbList
 *
 * Genera schema breadcrumb conformi a schema.org
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProSeoBreadcrumb
{
    /** @var Context */
    protected $context;

    /** @var Module */
    protected $module;

    /**
     * Costruttore
     *
     * @param Context $context
     * @param Module $module
     */
    public function __construct(Context $context, Module $module)
    {
        $this->context = $context;
        $this->module = $module;
    }

    /**
     * Genera lo schema BreadcrumbList
     *
     * @return string
     */
    public function generateBreadcrumbSchema()
    {
        $breadcrumbItems = $this->getBreadcrumbItems();

        if (empty($breadcrumbItems) || count($breadcrumbItems) < 2) {
            return '';
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(),
        );

        $position = 1;
        foreach ($breadcrumbItems as $item) {
            $listItem = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $this->cleanText($item['name']),
            );

            // L'ultimo elemento non ha URL (pagina corrente)
            if (!empty($item['url'])) {
                $listItem['item'] = $item['url'];
            }

            $schema['itemListElement'][] = $listItem;
            $position++;
        }

        return $this->wrapSchema($schema);
    }

    /**
     * Ottiene gli elementi del breadcrumb in base alla pagina corrente
     *
     * @return array
     */
    protected function getBreadcrumbItems()
    {
        $items = array();
        $link = $this->context->link;
        $idLang = (int) $this->context->language->id;
        $controller = $this->context->controller;
        $controllerName = get_class($controller);

        // Home sempre presente
        $items[] = array(
            'name' => $this->getHomeLabel(),
            'url' => $link->getPageLink('index'),
        );

        switch ($controllerName) {
            case 'ProductController':
                $items = array_merge($items, $this->getProductBreadcrumb());
                break;

            case 'CategoryController':
                $items = array_merge($items, $this->getCategoryBreadcrumb());
                break;

            case 'CmsController':
                $items = array_merge($items, $this->getCmsBreadcrumb());
                break;

            case 'ManufacturerController':
                $items = array_merge($items, $this->getManufacturerBreadcrumb());
                break;

            case 'SupplierController':
                $items = array_merge($items, $this->getSupplierBreadcrumb());
                break;

            case 'SearchController':
                $items[] = array(
                    'name' => 'Ricerca',
                    'url' => '',
                );
                break;

            case 'ContactController':
                $items[] = array(
                    'name' => 'Contatti',
                    'url' => '',
                );
                break;

            case 'CartController':
                $items[] = array(
                    'name' => 'Carrello',
                    'url' => '',
                );
                break;

            case 'OrderController':
                $items[] = array(
                    'name' => 'Ordine',
                    'url' => '',
                );
                break;

            case 'MyAccountController':
                $items[] = array(
                    'name' => 'Il mio account',
                    'url' => '',
                );
                break;

            default:
                // Per la homepage non aggiungiamo nulla
                if ($controllerName === 'IndexController') {
                    return array(); // Nessun breadcrumb per la homepage
                }
                break;
        }

        return $items;
    }

    /**
     * Ottiene il breadcrumb per un prodotto
     *
     * @return array
     */
    protected function getProductBreadcrumb()
    {
        $items = array();
        $idProduct = (int) Tools::getValue('id_product');
        $idLang = (int) $this->context->language->id;
        $link = $this->context->link;

        if (!$idProduct) {
            return $items;
        }

        $product = new Product($idProduct, false, $idLang);

        if (!Validate::isLoadedObject($product)) {
            return $items;
        }

        // Ottieni la categoria del prodotto e i suoi genitori
        $idCategory = $product->id_category_default;
        if ($idCategory) {
            $categoryItems = $this->getCategoryPath($idCategory);
            $items = array_merge($items, $categoryItems);
        }

        // Aggiungi il prodotto (senza URL perché è la pagina corrente)
        $items[] = array(
            'name' => $product->name,
            'url' => '',
        );

        return $items;
    }

    /**
     * Ottiene il breadcrumb per una categoria
     *
     * @return array
     */
    protected function getCategoryBreadcrumb()
    {
        $idCategory = (int) Tools::getValue('id_category');

        if (!$idCategory) {
            return array();
        }

        $categoryPath = $this->getCategoryPath($idCategory);

        // L'ultimo elemento (categoria corrente) non deve avere URL
        if (!empty($categoryPath)) {
            $lastIndex = count($categoryPath) - 1;
            $categoryPath[$lastIndex]['url'] = '';
        }

        return $categoryPath;
    }

    /**
     * Ottiene il percorso completo di una categoria
     *
     * @param int $idCategory
     * @return array
     */
    protected function getCategoryPath($idCategory)
    {
        $items = array();
        $idLang = (int) $this->context->language->id;
        $link = $this->context->link;

        $category = new Category($idCategory, $idLang);

        if (!Validate::isLoadedObject($category)) {
            return $items;
        }

        // Ottieni tutti i genitori
        $parents = $category->getParentsCategories($idLang);

        // Inverti l'ordine (dal root al corrente)
        $parents = array_reverse($parents);

        // Root category ID (solitamente 1 o 2)
        $rootCategoryId = (int) Configuration::get('PS_ROOT_CATEGORY');
        $homeCategoryId = (int) Configuration::get('PS_HOME_CATEGORY');

        foreach ($parents as $parent) {
            $parentId = (int) $parent['id_category'];

            // Salta root category e home category (già in home)
            if ($parentId === $rootCategoryId || $parentId === $homeCategoryId) {
                continue;
            }

            $parentCategory = new Category($parentId, $idLang);

            if (Validate::isLoadedObject($parentCategory) && $parentCategory->active) {
                $items[] = array(
                    'name' => $parentCategory->name,
                    'url' => $link->getCategoryLink($parentCategory),
                );
            }
        }

        return $items;
    }

    /**
     * Ottiene il breadcrumb per una pagina CMS
     *
     * @return array
     */
    protected function getCmsBreadcrumb()
    {
        $items = array();
        $idCms = (int) Tools::getValue('id_cms');
        $idCmsCategory = (int) Tools::getValue('id_cms_category');
        $idLang = (int) $this->context->language->id;
        $link = $this->context->link;

        if ($idCms) {
            $cms = new CMS($idCms, $idLang);

            if (Validate::isLoadedObject($cms)) {
                // Ottieni la categoria CMS se esiste
                if ($cms->id_cms_category > 1) {
                    $cmsCategory = new CMSCategory($cms->id_cms_category, $idLang);
                    if (Validate::isLoadedObject($cmsCategory)) {
                        $items[] = array(
                            'name' => $cmsCategory->name,
                            'url' => $link->getCMSCategoryLink($cmsCategory),
                        );
                    }
                }

                $items[] = array(
                    'name' => $cms->meta_title,
                    'url' => '',
                );
            }
        } elseif ($idCmsCategory) {
            $cmsCategory = new CMSCategory($idCmsCategory, $idLang);

            if (Validate::isLoadedObject($cmsCategory)) {
                // Ottieni i genitori della categoria CMS
                $parents = $cmsCategory->getParentsCategories($idLang);
                $parents = array_reverse($parents);

                foreach ($parents as $parent) {
                    if ($parent['id_cms_category'] <= 1) {
                        continue;
                    }

                    $parentCat = new CMSCategory($parent['id_cms_category'], $idLang);
                    if (Validate::isLoadedObject($parentCat)) {
                        $items[] = array(
                            'name' => $parentCat->name,
                            'url' => $link->getCMSCategoryLink($parentCat),
                        );
                    }
                }

                // Ultima categoria senza URL
                if (!empty($items)) {
                    $items[count($items) - 1]['url'] = '';
                }
            }
        }

        return $items;
    }

    /**
     * Ottiene il breadcrumb per un produttore
     *
     * @return array
     */
    protected function getManufacturerBreadcrumb()
    {
        $items = array();
        $idManufacturer = (int) Tools::getValue('id_manufacturer');
        $idLang = (int) $this->context->language->id;
        $link = $this->context->link;

        // Aggiungi "Marche" come categoria intermedia
        $items[] = array(
            'name' => 'Marche',
            'url' => $link->getPageLink('manufacturer'),
        );

        if ($idManufacturer) {
            $manufacturer = new Manufacturer($idManufacturer, $idLang);

            if (Validate::isLoadedObject($manufacturer)) {
                $items[] = array(
                    'name' => $manufacturer->name,
                    'url' => '',
                );
            }
        }

        return $items;
    }

    /**
     * Ottiene il breadcrumb per un fornitore
     *
     * @return array
     */
    protected function getSupplierBreadcrumb()
    {
        $items = array();
        $idSupplier = (int) Tools::getValue('id_supplier');
        $idLang = (int) $this->context->language->id;
        $link = $this->context->link;

        // Aggiungi "Fornitori" come categoria intermedia
        $items[] = array(
            'name' => 'Fornitori',
            'url' => $link->getPageLink('supplier'),
        );

        if ($idSupplier) {
            $supplier = new Supplier($idSupplier, $idLang);

            if (Validate::isLoadedObject($supplier)) {
                $items[] = array(
                    'name' => $supplier->name,
                    'url' => '',
                );
            }
        }

        return $items;
    }

    /**
     * Ottiene l'etichetta per Home
     *
     * @return string
     */
    protected function getHomeLabel()
    {
        return 'Home';
    }

    /**
     * Pulisce il testo per lo schema
     *
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    protected function cleanText($text, $maxLength = 100)
    {
        if (empty($text)) {
            return '';
        }

        // Rimuovi HTML
        $text = strip_tags($text);

        // Decodifica entità HTML
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Rimuovi spazi multipli
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        // Tronca se necessario
        if (Tools::strlen($text) > $maxLength) {
            $text = Tools::substr($text, 0, $maxLength - 3) . '...';
        }

        return $text;
    }

    /**
     * Genera schema JSON-LD wrapper
     *
     * @param array $schema
     * @return string
     */
    protected function wrapSchema($schema)
    {
        if (empty($schema)) {
            return '';
        }

        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return "\n<!-- Pro SEO - Breadcrumb Schema -->\n" . '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
    }
}
