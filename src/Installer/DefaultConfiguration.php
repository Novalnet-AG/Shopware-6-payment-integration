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

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class DefaultConfiguration
{
    /**
     * @var EntityRepositoryInterface
     */
    private $systemConfigRepository;

    /**
     * @var SystemConfigService
     */
    private $systemConfig;

    public const SYSTEM_CONFIG_DOMAIN = 'NovalnetPayment.settings.';
   
    /**
     * @var bool
     */
    protected  $defaultConfig = ['gatewayTimeout' => 240,'paymentLogo' => true,'sepa.forceGuarantee' => true, 'invoice.forceGuarantee' => true,'creditcard.cc3D'  => true ,'creditcard.css' => 'body{color: #8798a9;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}input{border-radius: 3px;background-clip: padding-box;box-sizing: border-box;line-height: 1.1875rem;padding: .625rem .625rem .5625rem .625rem;box-shadow: inset 0 1px 1px #dadae5;background: #f8f8fa;border: 1px solid #dadae5;border-top-color: #cbcbdb;color: #8798a9;text-align: left;font: inherit;letter-spacing: normal;margin: 0;word-spacing: normal;text-transform: none;text-indent: 0px;text-shadow: none;display: inline-block;height:40px;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}input:focus{background-color: white;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}'];

    public function __construct(ContainerInterface $container)
    {
        $this->systemConfigRepository = $container->get('system_config.repository');
        $this->systemConfig = $container->get(SystemConfigService::class);
    }

    public function addDefaultConfiguration(): void
    {
        foreach ($this->defaultConfig as $key => $value) {
            if (null === $value || $value === []) {
                continue;
            }
            $this->systemConfig->set(self::SYSTEM_CONFIG_DOMAIN.$key, $value);
        }
    }
    
    public function removeConfiguration(UninstallContext $context): void
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', self::SYSTEM_CONFIG_DOMAIN));
            
        $idSearchResult = $this->systemConfigRepository->searchIds($criteria, $context->getContext());

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $idSearchResult->getIds());

        $this->systemConfigRepository->delete($ids, $context->getContext());
    }
}
