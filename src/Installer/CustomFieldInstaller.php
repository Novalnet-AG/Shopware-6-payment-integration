<?php
/**
*
* Novalnet payment plugin
*
* NOTICE OF LICENSE
*
* This source file is subject to Novalnet End User License Agreement
* DISCLAIMER
*
* @author Novalnet AG
* @copyright Copyright (c) Novalnet
* @license https://www.novalnet.de/payment-plugins/kostenlos/lizenz
* @link https://www.novalnet.de
*
* This free contribution made by request.
*
* If you have found this script useful a small
* recommendation as well as a comment on merchant
*
*/
declare(strict_types=1);

namespace Novalnet\NovalnetPayment\Installer;

use Novalnet\NovalnetPayment\Util\PaymentMethodIdProvider;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomFieldInstaller implements InstallerInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $customFieldSetRepository;
    
    /**
     * @var array
     */
    private $customFields;

    /**
     * {@inheritdoc}
     */
    public function __construct(ContainerInterface $container)
    {
        $this->customFieldSetRepository = $container->get('custom_field_set.repository');
        $this->customFields = [
        [
            'name' => 'novalnet',
            'config' => [
                'label' => [
                    'en-GB' => 'Novalnet',
                    'de-DE' => 'Novalnet',
                ],
            ],
            'customFields' => [
                [
                    'name' => 'novalnet_tid',
                    'active' => true,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'componentName' => 'sw-field',
                        'customFieldType' => 'text',
                        'customFieldPosition' => 1,
                        'label' => [
                            'en-GB' => 'Novalnet Transaction ID',
                            'de-DE' => 'Novalnet Transaktions-ID',
                        ],
                    ],
                ],
                [
                    'name' => 'novalnet_comments',
                    'active' => true,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'componentName' => 'sw-field',
                        'customFieldType' => 'text',
                        'customFieldPosition' => 1,
                        'label' => [
                            'en-GB' => 'Novalnet Coments',
                            'de-DE' => 'Novalnet Kommentare',
                        ],
                    ],
                ],
            ],
            'relations' => [
                    [
                        'entityName' => 'order_transaction',
                    ],
            ],
        ], ];
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        $this->addCustomField($context->getContext());
        $this->getDeactivateData(true, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        $this->addCustomField($context->getContext());
        $this->getDeactivateData(true, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        $this->getDeactivateData(false, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        $this->addCustomField($context->getContext());
        $this->getDeactivateData(true, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        $this->getDeactivateData(false, $context->getContext());
    }

    private function getDeactivateData(bool $status, Context $context): void
    {
        $customFieldExistsId = (new PaymentMethodIdProvider($this->customFieldSetRepository))->checkCustomField($context, 'novalnet');
        // Custom field does not even exist, so nothing to (de-)activate here
        if (!$customFieldExistsId) {
            return;
        }

        $customField = [
                'id' => $customFieldExistsId,
                'active' => $status,
        ];
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($customField): void {
            $this->customFieldSetRepository->upsert([$customField], $context);
        });
    }

    private function addCustomField(Context $context): void
    {
        $data = $this->customFields;
        $customFieldExists = (new PaymentMethodIdProvider($this->customFieldSetRepository))->checkCustomField($context, 'novalnet');

        // custom field exists already, no need to continue here
        if ($customFieldExists) {
            return;
        }

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data): void {
            $this->customFieldSetRepository->upsert($data, $context);
        });
    }
}
