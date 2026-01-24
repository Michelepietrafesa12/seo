<?php
/**
 * ProSEOMaster - Symfony Admin Controller
 *
 * Modern PrestaShop 8.x compatible admin controller using Symfony framework
 *
 * @author      SEO Expert
 * @copyright   2024
 * @license     MIT
 */

declare(strict_types=1);

namespace ProSEOMaster\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Configuration;
use Tools;
use Context;

/**
 * Main Admin Controller for ProSEO Master module
 */
class ProSEOMasterController extends FrameworkBundleAdminController
{
    /**
     * Main configuration page
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $formHandler = $this->get('proseomaster.form.handler.general_settings');
        $form = $formHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $formHandler->save($form->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Settings updated successfully.', 'Modules.Proseomaster.Admin'));
            } else {
                $this->flashErrors($errors);
            }

            return $this->redirectToRoute('proseomaster_configuration');
        }

        return $this->render('@Modules/proseomaster/views/templates/admin/configuration.html.twig', [
            'form' => $form->createView(),
            'moduleVersion' => \Module::getInstanceByName('proseomaster')->version,
            'help_link' => false,
        ]);
    }

    /**
     * SEO Dashboard with statistics
     *
     * @param Request $request
     * @return Response
     */
    public function dashboardAction(Request $request): Response
    {
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterAudit.php';

        $audit = new \ProSEOMasterAudit();
        $idLang = (int) Context::getContext()->language->id;
        $idShop = (int) Context::getContext()->shop->id;

        $stats = [
            'seo_score' => $audit->calculateSeoScore($idLang, $idShop),
            'products_without_meta' => count($audit->getProductsWithoutMeta($idLang, $idShop)),
            'categories_without_meta' => count($audit->getCategoriesWithoutMeta($idLang, $idShop)),
            'duplicate_titles' => count($audit->getDuplicateTitles($idLang, $idShop)),
            'duplicate_descriptions' => count($audit->getDuplicateDescriptions($idLang, $idShop)),
        ];

        return $this->render('@Modules/proseomaster/views/templates/admin/dashboard.html.twig', [
            'stats' => $stats,
            'help_link' => false,
        ]);
    }

    /**
     * Sitemap management page
     *
     * @param Request $request
     * @return Response
     */
    public function sitemapAction(Request $request): Response
    {
        $sitemapUrl = Context::getContext()->link->getBaseLink() . 'sitemap.xml';
        $sitemapDate = Configuration::get('PROSEOMASTER_SITEMAP_LAST_GENERATED');
        $cronToken = Configuration::get('PROSEOMASTER_CRON_TOKEN');
        $cronUrl = Context::getContext()->link->getModuleLink('proseomaster', 'cron', [
            'action' => 'sitemap',
            'token' => $cronToken,
        ]);

        return $this->render('@Modules/proseomaster/views/templates/admin/sitemap.html.twig', [
            'sitemap_url' => $sitemapUrl,
            'sitemap_date' => $sitemapDate,
            'cron_url' => $cronUrl,
            'cron_token' => $cronToken,
            'help_link' => false,
        ]);
    }

    /**
     * Generate sitemap action (AJAX)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateSitemapAction(Request $request): JsonResponse
    {
        try {
            require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterSitemap.php';

            $sitemap = new \ProSEOMasterSitemap();
            $result = $sitemap->generateSitemap();

            if ($result) {
                Configuration::updateValue('PROSEOMASTER_SITEMAP_LAST_GENERATED', date('Y-m-d H:i:s'));

                // Ping search engines
                $sitemap->submitToSearchEngines();

                return new JsonResponse([
                    'success' => true,
                    'message' => $this->trans('Sitemap generated successfully.', 'Modules.Proseomaster.Admin'),
                    'timestamp' => date('Y-m-d H:i:s'),
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'message' => $this->trans('Failed to generate sitemap.', 'Modules.Proseomaster.Admin'),
            ], 500);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Redirects management page
     *
     * @param Request $request
     * @return Response
     */
    public function redirectsAction(Request $request): Response
    {
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterRedirects.php';

        $redirects = new \ProSEOMasterRedirects();
        $allRedirects = $redirects->getAllRedirects(100, 0);
        $stats = $redirects->getStats();

        return $this->render('@Modules/proseomaster/views/templates/admin/redirects.html.twig', [
            'redirects' => $allRedirects,
            'stats' => $stats,
            'help_link' => false,
        ]);
    }

    /**
     * Add redirect action (AJAX)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addRedirectAction(Request $request): JsonResponse
    {
        $oldUrl = $request->request->get('old_url');
        $newUrl = $request->request->get('new_url');
        $redirectType = (int) $request->request->get('redirect_type', 301);

        if (empty($oldUrl) || empty($newUrl)) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->trans('Old URL and New URL are required.', 'Modules.Proseomaster.Admin'),
            ], 400);
        }

        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterRedirects.php';

        $redirects = new \ProSEOMasterRedirects();
        $result = $redirects->addRedirect($oldUrl, $newUrl, $redirectType);

        if ($result) {
            return new JsonResponse([
                'success' => true,
                'message' => $this->trans('Redirect added successfully.', 'Modules.Proseomaster.Admin'),
                'id' => $result,
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => $this->trans('Failed to add redirect.', 'Modules.Proseomaster.Admin'),
        ], 500);
    }

    /**
     * Delete redirect action (AJAX)
     *
     * @param Request $request
     * @param int $redirectId
     * @return JsonResponse
     */
    public function deleteRedirectAction(Request $request, int $redirectId): JsonResponse
    {
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterRedirects.php';

        $redirects = new \ProSEOMasterRedirects();
        $result = $redirects->deleteRedirect($redirectId);

        if ($result) {
            return new JsonResponse([
                'success' => true,
                'message' => $this->trans('Redirect deleted successfully.', 'Modules.Proseomaster.Admin'),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => $this->trans('Failed to delete redirect.', 'Modules.Proseomaster.Admin'),
        ], 500);
    }

    /**
     * Link checker page
     *
     * @param Request $request
     * @return Response
     */
    public function linkCheckerAction(Request $request): Response
    {
        return $this->render('@Modules/proseomaster/views/templates/admin/link_checker.html.twig', [
            'help_link' => false,
        ]);
    }

    /**
     * Run link check (AJAX)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function runLinkCheckAction(Request $request): JsonResponse
    {
        $scanType = $request->request->get('scan_type', 'quick');
        $limit = (int) $request->request->get('limit', 100);

        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterLinkChecker.php';

        try {
            $checker = new \ProSEOMasterLinkChecker();

            if ($scanType === 'quick') {
                $results = $checker->quickScan(30);
            } else {
                $results = $checker->scanAllProducts($limit);
            }

            return new JsonResponse([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk editor page
     *
     * @param Request $request
     * @return Response
     */
    public function bulkEditorAction(Request $request): Response
    {
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterBulkEditor.php';

        $bulkEditor = new \ProSEOMasterBulkEditor();
        $idLang = (int) Context::getContext()->language->id;
        $products = $bulkEditor->getProductsForBulkEdit($idLang, 50, 0);
        $summary = $bulkEditor->getSeoSummary($idLang);

        return $this->render('@Modules/proseomaster/views/templates/admin/bulk_editor.html.twig', [
            'products' => $products,
            'summary' => $summary,
            'help_link' => false,
        ]);
    }

    /**
     * Export SEO data (AJAX)
     *
     * @param Request $request
     * @return Response
     */
    public function exportSeoDataAction(Request $request): Response
    {
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterBulkEditor.php';

        $bulkEditor = new \ProSEOMasterBulkEditor();
        $idLang = (int) Context::getContext()->language->id;
        $csv = $bulkEditor->exportSeoData($idLang);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="seo_export_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    /**
     * Schema tester page
     *
     * @param Request $request
     * @return Response
     */
    public function schemaTestAction(Request $request): Response
    {
        require_once _PS_MODULE_DIR_ . 'proseomaster/classes/ProSEOMasterSchemaValidator.php';

        $validator = new \ProSEOMasterSchemaValidator();
        $testUrls = $validator->getTestUrls();

        return $this->render('@Modules/proseomaster/views/templates/admin/schema_test.html.twig', [
            'test_urls' => $testUrls,
            'help_link' => false,
        ]);
    }

    /**
     * Regenerate cron token (AJAX)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function regenerateCronTokenAction(Request $request): JsonResponse
    {
        $newToken = bin2hex(random_bytes(16));
        Configuration::updateValue('PROSEOMASTER_CRON_TOKEN', $newToken);

        $cronUrl = Context::getContext()->link->getModuleLink('proseomaster', 'cron', [
            'action' => 'sitemap',
            'token' => $newToken,
        ]);

        return new JsonResponse([
            'success' => true,
            'token' => $newToken,
            'cron_url' => $cronUrl,
            'message' => $this->trans('Token regenerated successfully.', 'Modules.Proseomaster.Admin'),
        ]);
    }
}
