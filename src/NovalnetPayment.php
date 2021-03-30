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

namespace Novalnet\NovalnetPayment;

use Novalnet\NovalnetPayment\Installer\CustomFieldInstaller;
use Novalnet\NovalnetPayment\Installer\DefaultConfiguration;
use Novalnet\NovalnetPayment\Installer\PaymentMethodInstaller;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class NovalnetPayment extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/DependencyInjection'));
        $loader->load('util.xml');
    }

    public function install(InstallContext $installContext): void
    {
        (new PaymentMethodInstaller($this->container))->install($installContext);
        (new CustomFieldInstaller($this->container))->install($installContext);
        (new DefaultConfiguration($this->container))->addDefaultConfiguration();
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        // Only set the payment method to inactive when uninstalling. Removing the payment method would
        // cause data consistency issues, since the payment method might have been used in several orders
        (new PaymentMethodInstaller($this->container))->uninstall($uninstallContext);
        (new CustomFieldInstaller($this->container))->uninstall($uninstallContext);
        (new DefaultConfiguration($this->container))->removeConfiguration($uninstallContext);
        parent::uninstall($uninstallContext);
    }

    public function activate(ActivateContext $context): void
    {
        parent::activate($context);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        (new PaymentMethodInstaller($this->container))->deactivate($deactivateContext);
        (new CustomFieldInstaller($this->container))->deactivate($deactivateContext);
        parent::deactivate($deactivateContext);
    }
}
