<?php
/**
 * ProSEOMaster - General Settings Form Type
 *
 * Symfony Form for module configuration
 *
 * @author      SEO Expert
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
            // Meta Tags Settings
            ->add('enable_meta_optimization', SwitchType::class, [
                'label' => $this->trans('Enable Meta Optimization', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Automatically optimize meta titles and descriptions', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('meta_title_template', TextType::class, [
                'label' => $this->trans('Meta Title Template', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Use {product_name}, {category}, {brand}, {shop_name}', 'Modules.Proseomaster.Admin'),
                'required' => false,
                'constraints' => [
                    new Length(['max' => 200]),
                ],
            ])
            ->add('meta_description_template', TextareaType::class, [
                'label' => $this->trans('Meta Description Template', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Use {product_name}, {category}, {brand}, {price}', 'Modules.Proseomaster.Admin'),
                'required' => false,
                'constraints' => [
                    new Length(['max' => 500]),
                ],
            ])

            // Structured Data
            ->add('enable_schema', SwitchType::class, [
                'label' => $this->trans('Enable Schema Markup', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Add JSON-LD structured data to pages', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
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

            // Performance
            ->add('enable_preload', SwitchType::class, [
                'label' => $this->trans('Enable Resource Preloading', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Preload critical resources for faster page load', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('enable_lazy_images', SwitchType::class, [
                'label' => $this->trans('Enable Lazy Loading', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Lazy load images for better performance', 'Modules.Proseomaster.Admin'),
                'required' => false,
            ])
            ->add('enable_dns_prefetch', SwitchType::class, [
                'label' => $this->trans('Enable DNS Prefetch', 'Modules.Proseomaster.Admin'),
                'help' => $this->trans('Prefetch DNS for external resources', 'Modules.Proseomaster.Admin'),
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
