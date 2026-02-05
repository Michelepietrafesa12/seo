<?php
/**
 * ProSEOMaster - General Settings Form Type
 *
 * Symfony Form for module configuration
 *
 * @author      Michele Pietrafesa
 * @copyright   2024
 * @license     MIT
 */

declare(strict_types=1);

namespace ProSEOMaster\Form;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for general SEO settings
 */
class GeneralSettingsType extends TranslatorAwareType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Schema Markup Settings
            ->add('enable_product_schema', SwitchType::class, [
                'label' => $this->trans('Enable Product Schema', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Add JSON-LD Product structured data', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('enable_organization_schema', SwitchType::class, [
                'label' => $this->trans('Enable Organization Schema', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Add JSON-LD Organization structured data', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('product_title_template', TextType::class, [
                'label' => $this->trans('Product Title Template', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Use {product_name}, {category}, {brand}, {shop_name}', 'Modules.Proseomaster.Admin'),
                'required' => false,
                'constraints' => [
                    new Length(['max' => 200]),
                ],
            ])
            ->add('product_desc_template', TextareaType::class, [
                'label' => $this->trans('Product Description Template', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Use {product_name}, {category}, {brand}, {price}', 'Modules.Proseomaster.Admin'),
                'required' => false,
                'constraints' => [
                    new Length(['max' => 500]),
                ],
            ])

            // Social Media
            ->add('enable_og_tags', SwitchType::class, [
                'label' => $this->trans('Enable Open Graph Tags', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Add Facebook/social media meta tags', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('enable_twitter_cards', SwitchType::class, [
                'label' => $this->trans('Enable Twitter Cards', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Add Twitter Card meta tags', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('enable_canonical', SwitchType::class, [
                'label' => $this->trans('Enable Canonical URLs', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Add canonical URL meta tags', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])

            // Performance
            ->add('enable_resource_hints', SwitchType::class, [
                'label' => $this->trans('Enable Resource Hints', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Preconnect and DNS prefetch for external resources', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('enable_lazy_loading', SwitchType::class, [
                'label' => $this->trans('Enable Lazy Loading', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Lazy load images for better performance', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])

            // AI SEO
            ->add('enable_ai_seo', SwitchType::class, [
                'label' => $this->trans('Enable AI SEO Features', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Enable AI-powered SEO optimizations', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('enable_llms_txt', SwitchType::class, [
                'label' => $this->trans('Enable llms.txt', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Generate llms.txt for AI crawlers', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.Proseomaster.Admin',
        ]);
    }
}
