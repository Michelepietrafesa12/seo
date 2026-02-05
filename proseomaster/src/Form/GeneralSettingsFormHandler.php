<?php
/**
 * ProSEOMaster - General Settings Form Handler
 *
 * Handles form data retrieval and saving for module configuration
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 */

declare(strict_types=1);

namespace ProSEOMaster\Form;

use Configuration;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Form handler for general settings
 */
class GeneralSettingsFormHandler
{
    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var array Configuration field mappings - must match keys in proseomaster.php */
    private const CONFIG_FIELDS = [
        'enable_product_schema' => 'PROSEOMASTER_ENABLE_PRODUCT_SCHEMA',
        'product_title_template' => 'PROSEOMASTER_PRODUCT_TITLE_TEMPLATE',
        'product_desc_template' => 'PROSEOMASTER_PRODUCT_DESC_TEMPLATE',
        'enable_organization_schema' => 'PROSEOMASTER_ENABLE_ORGANIZATION_SCHEMA',
        'enable_og_tags' => 'PROSEOMASTER_ENABLE_OG_TAGS',
        'enable_twitter_cards' => 'PROSEOMASTER_ENABLE_TWITTER_CARDS',
        'enable_resource_hints' => 'PROSEOMASTER_ENABLE_RESOURCE_HINTS',
        'enable_lazy_loading' => 'PROSEOMASTER_ENABLE_LAZY_LOADING',
        'enable_canonical' => 'PROSEOMASTER_ENABLE_CANONICAL',
        'enable_ai_seo' => 'PROSEOMASTER_ENABLE_AI_SEO',
        'enable_llms_txt' => 'PROSEOMASTER_ENABLE_LLMS_TXT',
    ];

    /**
     * Constructor
     *
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * Get the form with current configuration values
     *
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->formFactory->createBuilder(GeneralSettingsType::class, $this->getData())
            ->getForm();
    }

    /**
     * Get current configuration data
     *
     * @return array
     */
    private function getData(): array
    {
        $data = [];

        foreach (self::CONFIG_FIELDS as $formField => $configKey) {
            $value = Configuration::get($configKey);

            // Convert string '1'/'0' to boolean for switch fields
            if (strpos($formField, 'enable_') === 0) {
                $data[$formField] = (bool) $value;
            } else {
                $data[$formField] = $value ?: '';
            }
        }

        return $data;
    }

    /**
     * Save form data to configuration
     *
     * @param array $data Form data
     * @return array Errors
     */
    public function save(array $data): array
    {
        $errors = [];

        foreach (self::CONFIG_FIELDS as $formField => $configKey) {
            if (isset($data[$formField])) {
                $value = $data[$formField];

                // Convert boolean to '1'/'0' for storage
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }

                if (!Configuration::updateValue($configKey, $value)) {
                    $errors[] = sprintf('Failed to save %s', $formField);
                }
            }
        }

        return $errors;
    }
}
