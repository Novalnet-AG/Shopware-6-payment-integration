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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class PaymentMethodInstaller implements InstallerInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;
    
    /**
     * @var array
     */
    private $paymentMethods;
    
    /**
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * @var array
     */
    private $langEN;
    
    /**
     * @var array
     */
    private $langDE;
    
    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;
    
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodSalesChannelRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(ContainerInterface $container)
    {
        $this->paymentMethodRepository = $container->get('payment_method.repository');
        $this->salesChannelRepository = $container->get('sales_channel.repository');
        $this->paymentMethodSalesChannelRepository = $container->get('sales_channel_payment_method.repository');
        $this->container = $container;
        $this->paymentMethods = ['novalnetcreditcard', 'novalnetpaypal', 'novalnetsepa', 'novalnetsofort', 'novalnetideal', 'novalneteps', 'novalnetgiropay', 'novalnetinvoice', 'novalnetprepayment', 'novalnetcashpayment', 'novalnetprzelewy24'];
        $this->langEN = $this->getLanguage('en_GB');
        $this->langDE = $this->getLanguage('de_DE');
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        if (empty($this->paymentMethods)) {
            return;
        }

        foreach ($this->paymentMethods as $paymentMethod) {
            $this->updatePaymentMethod($paymentMethod, $context->getContext());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        $this->setPaymentMethodIsActive(false, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        $this->setPaymentMethodIsActive(false, $context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        if (empty($this->paymentMethods)) {
            return;
        }

        foreach ($this->paymentMethods as $paymentMethod) {
            $this->updatePaymentMethod($paymentMethod, $context->getContext());
        }
    }

    /**
     * Insert Novalnet Payments into core table.
     *
     * @param string $paymentMethod
     * @param object $context
     *
     * @return null
     */
    private function updatePaymentMethod(string $paymentMethod, Context $context): void
    {
        $controllerPath = 'Novalnet\NovalnetPayment\Service'."\/".$this->langEN['handler_name_'.$paymentMethod];
        $orginalPath = str_replace('/', '', $controllerPath);
        $paymentMethodExists = (new PaymentMethodIdProvider($this->paymentMethodRepository))->getNovalnetPaymentMethodId($context, $orginalPath);
        
        // Payment method exists already, no need to continue here
        if ($paymentMethodExists) {
            return;
        }
        
        $paymentData = [
                    // payment handler will be selected by the identifier
                    'handlerIdentifier' => $orginalPath,
                    'name' => $this->langEN['payment_name_'.$paymentMethod],
                    'description' => $this->langEN['frontend_description_'.$paymentMethod],
                    'translations' => [
                        'de-DE' => [
                            'name' => $this->langDE['payment_name_'.$paymentMethod],
                            'description' => $this->langDE['frontend_description_'.$paymentMethod],
                            'customFields' => [
                                'novalnet_payment_method_name' => $paymentMethod,
                            ],
                        ],
                        'en-GB' => [
                            'name' => $this->langEN['payment_name_'.$paymentMethod],
                            'description' => $this->langEN['frontend_description_'.$paymentMethod],
                            'customFields' => [
                                'novalnet_payment_method_name' => $paymentMethod,
                            ],
                        ],
                    ],
                    'customFields' => [
                        'novalnet_payment_method_name' => $paymentMethod,
                    ],
                 ];
        
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($paymentData): void {
            $this->paymentMethodRepository->upsert([$paymentData], $context);
        });

        $paymentMethodId = (new PaymentMethodIdProvider($this->paymentMethodRepository))->getNovalnetPaymentMethodId($context, $orginalPath);
        $channels = $this->salesChannelRepository->searchIds(new Criteria(), $context);

        foreach ($channels->getIds() as $channel) {
            $data = [
                    'salesChannelId' => $channel,
                    'paymentMethodId' => $paymentMethodId,
                ];

            $this->paymentMethodSalesChannelRepository->upsert([$data], $context);
        }
    }

    /**
     * Set active (or) deactivate the payment methods.
     *
     * @param bool $active
     * @param object $context
     *
     * @return null
     */
    private function setPaymentMethodIsActive(bool $active, Context $context): void
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            $controllerPath = 'Novalnet\NovalnetPayment\Service'."\/".$this->langEN['handler_name_'.$paymentMethod];
            $orginalPath = str_replace('/', '', $controllerPath);
            $paymentMethodId = (new PaymentMethodIdProvider($this->paymentMethodRepository))->getNovalnetPaymentMethodId($context, $orginalPath);

            // Payment does not even exist, so nothing to (de-)activate here
            if (!$paymentMethodId) {
                return;
            }

            $paymentMethod = [
                'id' => $paymentMethodId,
                'active' => $active,
            ];
            $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($paymentMethod): void {
                $this->paymentMethodRepository->upsert([$paymentMethod], $context);
            });
        }
    }

    /**
     * Get language description from csv file.
     *
     * @param string $lang
     *
     * @return array
     */
    public static function getLanguage($lang)
    {
        $novalnetLang = [];
        $filename = dirname(__DIR__, 1).'/Helper/locale/'.$lang.'.csv';
        if (file_exists($filename)) {
            if ($file = fopen($filename, 'r')) {
                while ($data = fgetcsv($file, 0, ';', '"')) {
                    $novalnetLang[$data[0]] = $data[1];
                }
            }
        }
        return $novalnetLang;
    }
}
