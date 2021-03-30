<?php
/**
*
* This script is used for Sofort Payment
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
* Script : NovalnetSofort.php
*/
declare(strict_types=1);

namespace Novalnet\NovalnetPayment\Service;

use Novalnet\NovalnetPayment\Helper\NovalnetHelper;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class NovalnetSofort implements AsynchronousPaymentHandlerInterface
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
     * @var RouterInterface
     */
    private $router;
    
    /**
     * @var string
     */
    private $shopToken;
    
    /**
     * @var array
     */
    private $configDetails;
    
    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        ContainerInterface $container,
        RouterInterface $router,
        NovalnetHelper $helper
    ) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->shopToken = Random::getAlphanumericString(32);
        $this->orderTransactionRepository = $container->get('order_transaction.repository');
        $this->helper = $helper;
        $this->router = $router;
    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $url='https://payport.novalnet.de/online_transfer_payport';
        
        if(!empty($salesChannelContext->getSalesChannel()->getDomains())){     
            $route = $salesChannelContext->getSalesChannel()->getDomains()->first()->getUrl().'/novalnet/request?url='.$url.'&sw-token='.$this->shopToken;    
        }else{
             $route = $this->router->generate('frontend.action.novalnetpayment.request-action',['url' => $url, 'sw-token' => $this->shopToken], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $params = $this->helper->getRequestParams($salesChannelContext, $transaction);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'An error occurred during the communication with external payment gateway'.PHP_EOL.$e->getMessage());
        }
        // Redirect to external gateway
        return new RedirectResponse($route, 302, $params);
    }

    /**
     * @throws CustomerCanceledAsyncPaymentException
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $response = (array) $request->request;
        foreach ($response as $data) {
            $novalnetResponse = $data;
        }
        $this->configDetails = $this->helper->getNovalnetConfig($salesChannelContext);
        $this->helper->decodeData($novalnetResponse, $this->configDetails);
        $lang = $this->helper->getLocaleCodeFromContext($salesChannelContext->getContext());
        if (!$novalnetResponse['lang']) {
            $novalnetResponse['lang'] = $lang;
        }
        $paymentId = $transaction->getOrderTransaction()->getPaymentMethodId();
        $note = $this->helper->prepareComments($novalnetResponse, 'novalnetsofort', $salesChannelContext->getContext(), $paymentId);
        $this->helper->insertNovalnetTransactionDetails($novalnetResponse, $this->configDetails, 'novalnetsofort');
        $this->addNovalnetTransactionTid($transaction, $novalnetResponse, $salesChannelContext->getContext(), $note);

        if (!empty($novalnetResponse['tid_status']) && '100' === $novalnetResponse['tid_status']) {
            $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());
            $this->helper->PrepareMailContent($transaction, $salesChannelContext, $note);
        } else {
            throw new CustomerCanceledAsyncPaymentException(
                $transaction->getOrderTransaction()->getId(),
                isset($novalnetResponse['status_desc']) ? $novalnetResponse['status_desc'] : (isset($novalnetResponse['status_text']) ? $novalnetResponse['status_text'] : '')
            );
        }
    }

    private function addNovalnetTransactionTid(AsyncPaymentTransactionStruct $transaction, $novalnetResponse, Context $context, $note): void
    {
        $data = [
            'id' => $transaction->getOrderTransaction()->getId(),
            'customFields' => [
                    'novalnet_tid' => $novalnetResponse['tid'],
                    'novalnet_comments' => $note,
            ],
        ];
        $this->orderTransactionRepository->update([$data], $context);
    }
}
