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

namespace Novalnet\NovalnetPayment\Subscriber;

use Doctrine\DBAL\Connection;
use Novalnet\NovalnetPayment\Helper\NovalnetHelper;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PaymentSubscriber implements EventSubscriberInterface
{
    
    /**
     * @var Connection
     */
    private $connection;
    
    /**
     * @var NovalnetHelper
     */
    private $helper;
    
    /**
     * @var SessionInterface
     */
    private $SessionInterface;

    public function __construct(Connection $connection, SessionInterface $SessionInterface, NovalnetHelper $helper)
    {
        $this->connection = $connection;
        $this->SessionInterface = $SessionInterface;
        $this->helper = $helper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'orderPlaced',
        ];
    }

    public function orderPlaced(CheckoutOrderPlacedEvent $event)
    {
        foreach ($event->getOrder()->getTransactions()->getElements() as $key => $orderTransaction) {
            if (!empty($orderTransaction->getPaymentMethod()->getCustomFields()['novalnet_payment_method_name'])) {
                $this->helper->updateOrderConfirmationMail($this->connection);
            }
        }
    }
}
