<?php
/**
*
* This script is used for SEPA Payment
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
* Script : NovalnetSepa.php
*/
declare(strict_types=1);

namespace Novalnet\NovalnetPayment\Service;

use Novalnet\NovalnetPayment\Helper\NovalnetHelper;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class NovalnetSepa implements SynchronousPaymentHandlerInterface
{
    /**
     * @var OrderTransactionStateHandler
     */
    private $transactionStateHandler;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepository;
    
    /**
     * @var NovalnetHelper
     */
    private $helper;
    
    /**
     * @var string
     */
    private $note;

    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        ContainerInterface $container,
        NovalnetHelper $helper
    ) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->orderTransactionRepository = $container->get('order_transaction.repository');
        $this->helper = $helper;
    }

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
		if(empty($dataBag->get('nn_iban')))
		{
			foreach ( $_REQUEST as $key => $values ) {
				$dataBag->set($key, $values);
			}
		}
		
        $responseParams = $this->helper->getRequestParams($salesChannelContext, $transaction, $dataBag);
        $this->addNovalnetTransactionTid($transaction, $responseParams, $salesChannelContext->getContext());

        if (!empty($responseParams['tid_status'])) {
            if (in_array($responseParams['tid_status'], [100, 99, 75])) {
                $this->helper->PrepareMailContent($transaction, $salesChannelContext, $this->note);
            }
            if ('100' === $responseParams['tid_status']) {
                $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());
            } elseif ('99' === $responseParams['tid_status']) {
                //skip nothing to do here order is already open
            }
        } else {
            $this->transactionStateHandler->cancel($transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());
        }
    }

    private function addNovalnetTransactionTid(SyncPaymentTransactionStruct $transaction, $novalnetResponse, Context $context): void
    {
        $paymentName = $transaction->getOrderTransaction()->getPaymentMethod()->getCustomFields()['novalnet_payment_method_name'];
        $paymentId = $transaction->getOrderTransaction()->getPaymentMethod()->getId();
        $this->note = $this->helper->prepareComments($novalnetResponse, $paymentName, $context, $paymentId);
        $data = [
            'id' => $transaction->getOrderTransaction()->getId(),
            'customFields' => [
                    'novalnet_tid' => $novalnetResponse['tid'],
                    'novalnet_comments' => $this->note,
            ],
        ];
        $this->orderTransactionRepository->update([$data], $context);
    }
}
