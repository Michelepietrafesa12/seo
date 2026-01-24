<?php
/**
 * ProSEOMaster - General Settings Form Handler
 *
 * Handles form data retrieval and saving for module configuration
 *
 * @author      SEO Expert
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

    /** @var array Configuration field mappings */
    private const CONFIG_FIELDS = [
        'enable_meta_optimization' => 'PROSEOMASTER_ENABLE_META_OPTIMIZATION',
        'meta_title_template' => 'PROSEOMASTER_META_TITLE_TEMPLATE',
        'meta_description_template' => 'PROSEOMASTER_META_DESCRIPTION_TEMPLATE',
        'enable_schema' => 'PROSEOMASTER_ENABLE_SCHEMA',
        'enable_og_tags' => 'PROSEOMASTER_ENABLE_OG_TAGS',
        'enable_twitter_cards' => 'PROSEOMASTER_ENABLE_TWITTER_CARDS',
        'enable_preload' => 'PROSEOMASTER_ENABLE_PRELOAD',
        'enable_lazy_images' => 'PROSEOMASTER_ENABLE_LAZY_IMAGES',
        'enable_dns_prefetch' => 'PROSEOMASTER_ENABLE_DNS_PREFETCH',
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
