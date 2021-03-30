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

namespace Novalnet\NovalnetPayment\Controller;

use Doctrine\DBAL\Connection;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Novalnet\NovalnetPayment\Helper\NovalnetHelper;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Content\MailTemplate\Service\MailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CallbackController extends StorefrontController
{
    /** @Array Type of payment available - Level : 0 */
    private $aryPayments = ['CREDITCARD', 'INVOICE_START', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE', 'PAYPAL', 'ONLINE_TRANSFER', 'IDEAL', 'EPS', 'GIROPAY', 'PRZELEWY24', 'CASHPAYMENT'];

    /** @Array Type of Chargebacks available - Level : 1 */
    private $aryChargebacks = ['RETURN_DEBIT_SEPA', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'PAYPAL_BOOKBACK', 'PRZELEWY24_REFUND', 'REVERSAL', 'CASHPAYMENT_REFUND', 'GUARANTEED_INVOICE_BOOKBACK', 'GUARANTEED_SEPA_BOOKBACK'];

    /** @Array Type of Credit entry payment and Collections available - Level : 2 */
    private $aryCollection = ['INVOICE_CREDIT', 'CREDIT_ENTRY_CREDITCARD', 'CREDIT_ENTRY_SEPA', 'DEBT_COLLECTION_SEPA', 'DEBT_COLLECTION_CREDITCARD', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'];

    private $aPaymentTypes = ['novalnetcreditcard' => ['CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'CREDIT_ENTRY_CREDITCARD', 'DEBT_COLLECTION_CREDITCARD'], 'novalnetsepa' => ['DIRECT_DEBIT_SEPA', 'GUARANTEED_SEPA_BOOKBACK', 'RETURN_DEBIT_SEPA', 'DEBT_COLLECTION_SEPA', 'CREDIT_ENTRY_SEPA', 'REFUND_BY_BANK_TRANSFER_EU', 'GUARANTEED_DIRECT_DEBIT_SEPA'], 'novalnetideal' => ['IDEAL', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'], 'novalnetsofort' => ['ONLINE_TRANSFER', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'], 'novalnetpaypal' => ['PAYPAL', 'PAYPAL_BOOKBACK'], 'novalnetprepayment' => ['INVOICE_START', 'INVOICE_CREDIT', 'REFUND_BY_BANK_TRANSFER_EU'], 'novalnetcashpayment' => ['CASHPAYMENT', 'CASHPAYMENT_CREDIT', 'CASHPAYMENT_REFUND'], 'novalnetinvoice' => ['INVOICE_START', 'INVOICE_CREDIT', 'GUARANTEED_INVOICE', 'REFUND_BY_BANK_TRANSFER_EU', 'GUARANTEED_INVOICE_BOOKBACK', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'], 'novalneteps' => ['EPS', 'ONLINE_TRANSFER_CREDIT', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'], 'novalnetgiropay' => ['GIROPAY', 'ONLINE_TRANSFER_CREDIT', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'], 'novalnetprzelewy24' => ['PRZELEWY24', 'PRZELEWY24_REFUND']];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var NovalnetHelper
     */
    private $helper;

    /**
     * @var string
     */
    private $newLine;

    /**
     * @var SessionInterface
     */
    private $SessionInterface;

    /**
     * @var EntityRepositoryInterface
     */
    private $mailTemplateRepository;
    
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;
    
    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepository;
    
    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;
    
    /**
     * @var Request
     */
    private $request;
    
    /**
     * @var array
     */
    private $configDeatils;
    
    /**
     * @var SalesChannelContext
     */
    private $salesContext;
    
    /**
     * @var array
     */
    private $nnCaptureParams;
    
    /**
     * @var MultiInsertQueryQueue
     */
    private $query;
    
    /**
     * @var EntityRepositoryInterface
     */
    private $order;
    
    /**
     * @var array
     */
    private $nnTransHistory;
    
    /**
     * @var EntityRepositoryInterface
     */
    private $orderServiceItem;
    
    /**
     * @var string
     */
    private $paymentName;
    
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentProvider;
    
    /**
     * @var Request
     */
    private $requestParam;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        EntityRepositoryInterface $paymentService,
        Connection $connection,
        EntityRepositoryInterface $orderRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        MailService $mailService,
        EntityRepositoryInterface $mailTemplateRepository,
        TranslatorInterface $translator,
        SessionInterface $SessionInterface,
        ContainerInterface $container,
        NovalnetHelper $helper
    ) {
        $this->helper = $helper;
        $this->connection = $connection;
        $this->orderRepository = $orderRepository;
        $this->mailService = $mailService;
        $this->newLine = '/ ';
        $this->translator = $translator;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->paymentRepository = $paymentService;
        $this->SessionInterface = $SessionInterface;
        $this->requestParam     = $container->get('request_stack')->getCurrentRequest();
        $this->orderTransactionRepository = $container->get('order_transaction.repository');
        $this->salesChannelRepository = $container->get('sales_channel.repository');
    }

    /**
     * @Route("/novalnet/callback", name="frontend.action.novalnetpayment.status-action", defaults={"csrf_protected"=false}, methods={"GET","POST"})
     */
    public function StatusAction(Request $request, SalesChannelContext $context)
    {
        parse_str($request->getContent(), $this->request);
        $this->request = (!empty($this->request) ? $this->request : $request->query->all());
        $this->configDeatils = $this->helper->getNovalnetConfig($context);
        $this->salesContext = $context;
        if (!$this->validateParams()) {
            return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
        }
        $this->nnCaptureParams = $this->getCaptureParams($this->request);
        $this->query = new MultiInsertQueryQueue($this->connection, 250, false, false);
        $this->nnTransHistory = $this->getOrderReference($context->getContext());

        if (!$this->nnTransHistory) {
            return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
        }

        if ('collections_payments' === $this->getPaymentTypeLevel()) {
            // Credit entry payment and Collections available
            if ($this->nnTransHistory['paid_amount'] < $this->nnTransHistory['amount'] && in_array($this->nnCaptureParams['payment_type'], ['INVOICE_CREDIT', 'CASHPAYMENT_CREDIT', 'ONLINE_TRANSFER_CREDIT'])) {
                $getPreviousPaidAmount = $this->getOrderPaidAmount($this->nnCaptureParams['shop_tid']);
                $totalAmout = $getPreviousPaidAmount + $this->nnCaptureParams['amount'];
                //update novalnet transaction table
                $this->connection->update('novalnet_transaction_details', ['gateway_status' => $this->nnCaptureParams['tid_status'], 'paid_amount' => $totalAmout], ['tid' => $this->nnCaptureParams['shop_tid']]);

                $paidAmount = $this->getOrderPaidAmount($this->nnCaptureParams['shop_tid']);
                if ($paidAmount >= $this->nnTransHistory['amount']) {
                    $this->orderTransactionStateHandler->paid($this->orderServiceItem->getId(), $this->salesContext->getContext());
                }
            } elseif ($this->nnTransHistory['paid_amount'] >= $this->nnTransHistory['amount'] && in_array($this->nnCaptureParams['payment_type'], ['INVOICE_CREDIT', 'CASHPAYMENT_CREDIT', 'ONLINE_TRANSFER_CREDIT'])) {
                $this->debugMessage('Novalnet callback received. Callback Script executed already. Refer Order :'.$this->nnTransHistory['order_no'], $this->nnCaptureParams['order_no']);
                return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
            }
            $browserComments = sprintf('/ '.$this->translator->trans('NovalnetPayments.callback.collectionComments'), $this->nnCaptureParams['tid_payment'], sprintf('%.2f', $this->nnCaptureParams['amount'] / 100).' '.$this->nnCaptureParams['currency'], date('d-m-Y'), date('H:i:s'), $this->nnCaptureParams['tid']);
            $this->updateComments($browserComments);
            // Send notification mail to Merchant
            $this->sendNotifyMail([
                    'comments' => $browserComments,
                    'order_no' => $this->nnCaptureParams['order_no'],
                    'browserComments' => $browserComments,
                ]);
            $this->debugMessage($browserComments, $this->nnCaptureParams['order_no']);
            return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
        } elseif ('charge_back_payments' === $this->getPaymentTypeLevel()) {
            //Level 1 payments - Type of Chargebacks
            $commentChargeback = (in_array($this->nnCaptureParams['payment_type'], ['PAYPAL_BOOKBACK', 'CREDITCARD_BOOKBACK', 'PRZELEWY24_REFUND', 'CASHPAYMENT_REFUND', 'REFUND_BY_BANK_TRANSFER_EU', 'GUARANTEED_INVOICE_BOOKBACK', 'GUARANTEED_SEPA_BOOKBACK'])) ? (('de' === $this->nnTransHistory['lang']) ? 'Rückerstattung / Bookback ' : 'Refund/Bookback') : 'Chargeback';
            $callbackComments = sprintf('/ '.$this->translator->trans('NovalnetPayments.callback.chargebackComments'), $commentChargeback, $this->nnCaptureParams['tid_payment'], sprintf('%.2f', $this->nnCaptureParams['amount'] / 100).' '.$this->nnCaptureParams['currency'], date('d-m-Y'), date('H:i:s'), $this->nnCaptureParams['tid']);
            $this->updateComments($callbackComments);
            // Send notification mail to Merchant
            $this->sendNotifyMail(['comments' => $callbackComments, 'order_no' => $this->nnTransHistory['order_no']]);
            return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
        } elseif ('available_payments' === $this->getPaymentTypeLevel()) { //level 0 payments - Type of payment
            if ('PAYPAL' === $this->nnCaptureParams['payment_type']) {
                if ('100' === $this->nnCaptureParams['tid_status'] && '0' === $this->nnTransHistory['paid_amount']) {
                    // Update order status due to full payment
                    $callbackComments = sprintf('/ '.$this->translator->trans('NovalnetPayments.callback.availableComments'), $this->nnCaptureParams['tid'], sprintf('%0.2f', $this->nnCaptureParams['amount'] / 100).' '.$this->nnCaptureParams['currency'], date('d-m-Y'), date('H:i:s'));
                    $this->connection->update('novalnet_transaction_details', ['gateway_status' => $this->nnCaptureParams['tid_status'], 'paid_amount' => $this->nnCaptureParams['amount']], ['tid' => $this->nnCaptureParams['shop_tid']]);
                    $this->orderTransactionStateHandler->paid($this->orderServiceItem->getId(), $this->salesContext->getContext());
                    $this->updateComments($callbackComments);
                    // Send notification mail to Merchant
                    $this->sendNotifyMail(['comments' => $callbackComments, 'order_no' => $this->nnTransHistory['order_no']]);
                    return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
                }
                $this->debugMessage('Novalnet callback received. Order already paid.', $this->nnCaptureParams['order_no']);
                return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
            } elseif ('PRZELEWY24' === $this->nnCaptureParams['payment_type']) {
                if (in_array($this->nnCaptureParams['tid_status'], ['100', '86'])) {
                    if ('0' === $this->nnTransHistory['paid_amount']) {
                        // Full Payment paid
                        // Update order status due to full payment
                        $callbackComments = sprintf('/ '.$this->translator->trans('NovalnetPayments.callback.availableComments'), $this->nnCaptureParams['tid'], sprintf('%0.2f', $this->nnCaptureParams['amount'] / 100).' '.$this->nnCaptureParams['currency'], date('d-m-Y'), date('H:i:s'));
                        $this->connection->update('novalnet_transaction_details', ['gateway_status' => $this->nnCaptureParams['tid_status'], 'paid_amount' => $this->nnCaptureParams['amount']], ['tid' => $this->nnCaptureParams['shop_tid']]);
                        $this->orderTransactionStateHandler->paid($this->orderServiceItem->getId(), $this->salesContext->getContext());
                        $this->updateComments($callbackComments);
                        // Send notification mail to Merchant
                        $this->sendNotifyMail(['comments' => $callbackComments, 'order_no' => $this->nnTransHistory['order_no']]);
                    } else {
                        $this->debugMessage('Novalnet callback received. Order already paid.', $this->nnCaptureParams['order_no']);
                    }
                    return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
                } else {
                    $message = (($this->nnCaptureParams['status_message']) ? $this->nnCaptureParams['status_message'] : (($this->nnCaptureParams['status_text']) ? $this->nnCaptureParams['status_text'] : (($this->nnCaptureParams['status_desc']) ? $this->nnCaptureParams['status_desc'] : 'Payment was not successful. An error occurred')));
                    $callbackComments = sprintf('/ '.$this->translator->trans('NovalnetPayments.callback.cancelledOrder'), $message);
                    $this->connection->update('novalnet_transaction_details', ['gateway_status' => $this->nnCaptureParams['tid_status']], ['tid' => $this->nnCaptureParams['shop_tid']]);
                    $this->orderTransactionStateHandler->cancel($this->orderServiceItem->getId(), $this->salesContext->getContext());
                    $this->updateComments($callbackComments);
                    // Send notification mail to Merchant
                    $this->sendNotifyMail(['comments' => $callbackComments, 'order_no' => $this->nnTransHistory['order_no']]);
                    return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
                }
            } elseif (in_array($this->nnCaptureParams['payment_type'], ['INVOICE_START', 'GUARANTEED_INVOICE', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'DIRECT_DEBIT_SEPA']) && in_array($this->nnTransHistory['gateway_status'], [75, 91, 99]) && in_array($this->nnCaptureParams['tid_status'], [91, 99, 100]) && '100' === $this->nnCaptureParams['status']) {
                $this->nnCaptureParams['amount'] = sprintf('%.2f', $this->nnCaptureParams['amount'] / 100);
                $comments = '';
                if ('100' === $this->nnCaptureParams['tid_status']) {
                    if ('INVOICE_START' !== $this->nnCaptureParams['payment_type']) {
                        $this->connection->update('novalnet_transaction_details', ['paid_amount' => $this->nnCaptureParams['amount']], ['tid' => $this->nnCaptureParams['shop_tid']]);
                        $this->orderTransactionStateHandler->paid($this->orderServiceItem->getId(), $this->salesContext->getContext());
                    }
                    $callbackComments = sprintf($this->newLine.$this->translator->trans('NovalnetPayments.callback.confirmedComments'), date('d-m-Y'), date('H:i:s'));
                    if ($this->nnCaptureParams['due_date']) {
                        $callbackComments = sprintf($this->newLine.$this->translator->trans('NovalnetPayments.callback.confirmedDueComments'), $this->nnCaptureParams['tid'], $this->nnCaptureParams['due_date']);
                    }
                } elseif (in_array($this->nnCaptureParams['tid_status'], ['99', '91'])) {
                    $callbackComments = sprintf($this->newLine.$this->translator->trans('NovalnetPayments.callback.onHoldComments'), $this->nnCaptureParams['shop_tid'], date('d-m-Y'), date('H:i:s'));
                    if ($this->nnCaptureParams['payment_type'] === 'GUARANTEED_DIRECT_DEBIT_SEPA') {
                        $comments = $this->helper->prepareComments($this->nnCaptureParams, $this->paymentName, $this->salesContext->getContext(), $this->orderServiceItem->getPaymentMethodId());
                    }
                }
                if (in_array($this->nnCaptureParams['payment_type'], ['INVOICE_START', 'GUARANTEED_INVOICE']) || ('GUARANTEED_DIRECT_DEBIT_SEPA' === $this->nnCaptureParams['payment_type'] && '100' === $this->nnCaptureParams['tid_status'])) {
                    $comments = $this->helper->prepareComments($this->nnCaptureParams, $this->paymentName, $this->salesContext->getContext(), $this->orderServiceItem->getPaymentMethodId());
                }
                $novalnetComments = $comments.$callbackComments;
                $this->connection->update('novalnet_transaction_details', ['gateway_status' => $this->nnCaptureParams['tid_status']], ['tid' => $this->nnCaptureParams['shop_tid']]);
                $this->updateComments($novalnetComments);
                // Send notification mail to Merchant
                $this->sendNotifyMail(['comments' => $novalnetComments, 'order_no' => $this->nnTransHistory['order_no']]);
                return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
            } elseif ('CREDITCARD' === $this->nnCaptureParams['payment_type'] && '98' === $this->nnTransHistory['gateway_status'] && '100' === $this->nnCaptureParams['tid_status']) {
                $callbackComments = sprintf($this->newLine.$this->translator->trans('NovalnetPayments.callback.confirmedComments'), date('d-m-Y'), date('H:i:s'));
                $this->connection->update('novalnet_transaction_details', ['gateway_status' => $this->nnCaptureParams['tid_status'], 'paid_amount' => $this->nnCaptureParams['amount']], ['tid' => $this->nnCaptureParams['shop_tid']]);
                $this->orderTransactionStateHandler->paid($this->orderServiceItem->getId(), $this->salesContext->getContext());
                $this->updateComments($callbackComments);
                // Send notification mail to Merchant
                $this->sendNotifyMail(['comments' => $callbackComments, 'order_no' => $this->nnTransHistory['order_no']]);
                return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
            } else {
                $this->debugMessage('Novalnet callback received. Payment type ( '.$this->nnCaptureParams['payment_type'].' ) is
                    not applicable for this process!', $this->nnCaptureParams['order_no']);
                return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
            }
        }
        return $this->renderStorefront('@NovalnetPayment/callback/index.html.twig', ['message' => $this->SessionInterface->get('message')]);
    }

    /**
     * Validate the required params to process callback.
     *
     * @param null
     *
     * @return bool
     */
    public function validateParams()
    {
        // Validate Authenticated IP
        if (empty(gethostbyname('pay-nn.de'))) {
            $this->debugMessage('Novalnet HOST IP missing');
            return false;
        }
        
        $callerIp = !empty($this->requestParam->server->get('HTTP_X_REAL_IP')) ? $this->requestParam->server->get('HTTP_X_REAL_IP') : (!empty($this->requestParam->server->get('HTTP_X_FORWARDED_FOR')) ? $this->requestParam->server->get('HTTP_X_FORWARDED_FOR') : $this->requestParam->server->get('REMOTE_ADDR'));
        
        if ($callerIp !== gethostbyname('pay-nn.de') && (!isset($this->configDeatils['deactivateIp']) || empty($this->configDeatils['deactivateIp']))) {
            $this->debugMessage('Unauthorised access from the IP ['.$callerIp.']');
            return false;
        }

        $requiredParams = ['vendor_id', 'tid', 'payment_type', 'status', 'tid_status'];
        if (isset($this->request['payment_type'])) {
            if (in_array($this->request['payment_type'], array_merge($this->aryChargebacks, $this->aryCollection))) {
                array_push($requiredParams, 'tid_payment');
            }
        }
        foreach ($requiredParams as $v) {
            if (empty($this->request[$v])) {
                $error = 'Novalnet callback received. Required param ('.$v.') missing! <br>';
                break;
            } elseif (in_array($v, ['tid', 'tid_payment']) && !preg_match('/^[0-9]{17}$/', $this->request[$v])) {
                $error = 'Novalnet callback received. TID ['.$this->request[$v].'] is not valid.';
            }
        }
        if (isset($error)) {
            $this->debugMessage($error);
            return false;
        }

        return true;
    }

    /**
     * Display the error message.
     *
     * @param string $errorMsg
     * @param string $orderNo
     *
     * @return null
     */
    public function debugMessage($errorMsg, $orderNo = null)
    {
        if ($orderNo) {
            $message = 'message = '.$errorMsg.'&ordernumber='.$orderNo;
        } else {
            $message = 'message = '.$errorMsg;
        }

        $this->SessionInterface->set('message', $message);
    }

    /**
     * Perform parameter validation process
     * Set empty value if not exist in aryCapture.
     *
     * @param $aryCapture
     *
     * return array
     */
    public function getCaptureParams($aryCapture = []): array
    {
        $aryCapture['shop_tid'] = $aryCapture['tid'];
        if (in_array($aryCapture['payment_type'], array_merge($this->aryChargebacks, $this->aryCollection))) { // Collection Payments or Chargeback Payments
            $aryCapture['shop_tid'] = $aryCapture['tid_payment'];
        }
        return $aryCapture;
    }

    /**
     * Get order reference from the novalnet_transaction_detail table on shop database.
     *
     * @param object $context
     *
     * @return array
     */
    private function getOrderReference(Context $context)
    {
        $dbVal = $this->connection
            ->executeQuery('SELECT order_no,gateway_status,paid_amount,amount,additional_details,lang FROM novalnet_transaction_details WHERE tid = :tid', ['tid' => $this->nnCaptureParams['shop_tid']])
            ->fetchAll();
        $result = (!empty($dbVal[0]) ? $dbVal[0] : '');
        $orderNumber = !empty($result['order_no']) ? $result['order_no'] : $this->nnCaptureParams['order_no'];
        $this->nnCaptureParams['order_no'] = $orderNumber;
        $this->orderServiceItem = $this->getOrderTransaction($orderNumber, $context);

        if (!empty($this->orderServiceItem)) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('id', $this->orderServiceItem->getPaymentMethodId()));
            $this->paymentProvider = $this->paymentRepository->search($criteria, $context)->first();
            if (empty($this->paymentProvider)) {
                $this->debugMessage('Order Reference not exist in Database!', $orderNumber);
                return false;
            }
            $this->paymentName = $this->paymentProvider->getCustomFields()['novalnet_payment_method_name'];
        }

        if (empty($result) && empty($this->orderServiceItem)) {
            if ('100' === $this->nnCaptureParams['status']) {
                $this->criticalMailComments($this->nnCaptureParams);
                return false;
            }
        }

        if ('TRANSACTION_CANCELLATION' === $this->nnCaptureParams['payment_type']) { // transaction cancelled for invoice and sepa payments
            $callbackComments = sprintf($this->newLine.$this->translator->trans('NovalnetPayments.callback.transactionCancelledComments'), date('d-m-Y'), date('H:i:s'));
            $this->orderTransactionStateHandler->cancel($this->orderServiceItem->getId(), $context);
            $this->updateComments($callbackComments);
            $this->connection->update('novalnet_transaction_details', ['gateway_status' => $this->nnCaptureParams['tid_status']], ['order_no' => $orderNumber]);
            // Send notification mail to Merchant
            $this->sendNotifyMail([
                'comments' => $callbackComments,
                'order_no' => $this->nnCaptureParams['order_no'],
                'browserComments' => $callbackComments,
            ]);
            $this->debugMessage($callbackComments);
            return false;
        }

        if (empty($result) && (in_array($this->nnCaptureParams['payment_type'], $this->aryPayments)) && !empty($this->orderServiceItem)) { // If transaction not found in Novalnet table but the order number available in Novalnet system and payment temprorary id matches, handle communication break
            $this->communicationFailure();
            return false;
        }

        if (!empty($this->nnCaptureParams['order_no']) && $result['order_no'] !== $this->nnCaptureParams['order_no']) {
            $this->debugMessage('Novalnet callback received. Order no is not valid', $this->nnCaptureParams['order_no']);
            return false;
        } elseif ((!array_key_exists($this->paymentName, $this->aPaymentTypes)) || !in_array($this->nnCaptureParams['payment_type'], $this->aPaymentTypes[$this->paymentName])) {
            $this->debugMessage('Novalnet callback received. Payment type ['.$this->nnCaptureParams['payment_type'].'] is mismatched!', $orderNumber);
            return false;
        }

        return $result;
    }

    /**
     * Get order transaction from order number.
     *
     * @param string $orderNumber
     * @param object $context
     *
     * @return array
     */
    private function getOrderTransaction(string $orderNumber, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addAssociation('transactions');
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        $orderId = $this->orderRepository->searchIds($criteria, $context);

        if (empty($orderId->getIds())) {
            return null;
        }

        $criteria->addFilter(new EqualsFilter('id', $orderId->getIds()[0]));
        $this->order = $this->orderRepository->search($criteria, $context)->first();

        if (null === $this->order) {
            $this->debugMessage('Order not found');
        }

        $transactionCollection = $this->order->getTransactions();
        if (null === $transactionCollection) {
            $this->debugMessage('Order Transaction is not found');
        }

        $transaction = $transactionCollection->first();
        if (null === $transaction) {
            $this->debugMessage('Order Transaction is not found');
        }

        return $transaction;
    }

    /**
     * Get mail values to send a comments.
     *
     * @param array $nnCaptureParams
     *
     * @return array
     */
    public function criticalMailComments($nnCaptureParams)
    {
        $comments = $this->newLine.'Dear Technic team,'.$this->newLine.$this->newLine;
        $comments .= 'Please evaluate this transaction and contact our payment module team at Novalnet.'.$this->newLine.$this->newLine;
        $comments .= 'Merchant ID: '.$nnCaptureParams['vendor_id'].$this->newLine;
        $comments .= 'Project ID: '.$nnCaptureParams['product_id'].$this->newLine;
        $comments .= 'TID: '.$nnCaptureParams['shop_tid'].$this->newLine;
        $comments .= 'TID status: '.$nnCaptureParams['tid_status'].$this->newLine;
        $comments .= 'Order no: '.$nnCaptureParams['order_no'].$this->newLine;
        $comments .= 'Payment type: '.$nnCaptureParams['payment_type'].$this->newLine;
        $comments .= 'E-mail: '.$nnCaptureParams['email'].$this->newLine;
        $comments = str_replace('<br>', PHP_EOL, $comments);
        $this->sendNotifyMail([
            'comments' => $comments,
            'tid' => $nnCaptureParams['shop_tid'],
            'order_no' => $nnCaptureParams['order_no'],
        ], true);
        return;
    }

    /**
     * Send notify email after callback process.
     *
     * @param array $datas
     * @param bool  $missingTransactionNotify
     *
     * @return null
     */
    public function sendNotifyMail($datas, $missingTransactionNotify = false)
    {
        $comments = !empty($datas['browserComments']) ? $datas['browserComments'] : $datas['comments'];
        $comments = str_replace('/ ', '<br />', $comments);
        $data = new DataBag();
        if ($missingTransactionNotify) {
            $data->set(
                'recipients',
                [
                    'technic@novalnet.de' => 'technic',
                ]
            );
            $data->set('senderName', 'technic');

            $data->set('salesChannelId', null);

            $data->set('contentHtml', $comments);
            $data->set('contentPlain', $comments);
            $data->set('subject', 'Critical error on shop system '.$this->salesContext->getSalesChannel()->getName().' order not found for TID: '.$datas['tid']);
        } elseif (!empty($this->configDeatils['callbackMail']) && !empty($this->configDeatils['mailTo'])) {
            $this->checkEmail($this->configDeatils['mailTo']);

            if (!empty($this->configDeatils['mailBcc'])) {
                $this->checkEmail($this->configDeatils['mailBcc']);
                $data->set('recipientsBcc', $this->configDeatils['mailBcc']);
            }

            $data->set(
                'recipients',
                [
                    $this->configDeatils['mailTo'] => 'Novalnet Callback mail',
                ]
            );
            $data->set('senderName', 'Novalnet');
            $data->set('salesChannelId', null);

            $data->set('contentHtml', $comments);
            $data->set('contentPlain', $comments);
            $data->set('subject', 'Novalnet Callback script notification - Order No : '.$datas['order_no']);
        }

        if ($missingTransactionNotify || !empty($this->configDeatils['callbackMail']) && !empty($this->configDeatils['mailTo'])) {
            $this->mailService->send(
                $data->all(),
                $this->salesContext->getContext(),
                []
            );
        }

        if (!empty($datas['order_number'])) {
            $this->debugMessage($comments, $datas['order_number']);
        } else {
            $this->debugMessage($comments);
        }
        return;
    }

    /**
     * Get failure transactions for redirection payments.
     *
     * @param null
     *
     * @return array
     */
    public function communicationFailure()
    {
        $this->helper->updateOrderConfirmationMail($this->connection, 'action.mail.send');

        $note = $comments = $this->helper->prepareComments($this->nnCaptureParams, $this->paymentName, $this->salesContext->getContext(), $this->orderServiceItem->getPaymentMethodId());

        if ((!array_key_exists($this->paymentName, $this->aPaymentTypes)) || !in_array($this->nnCaptureParams['payment_type'], $this->aPaymentTypes[$this->paymentName])) {
            $this->debugMessage('Novalnet callback received. Payment type ['.$this->nnCaptureParams['payment_type'].'] is mismatched!', $this->nnCaptureParams['order_no']);
        }

        if ('100' === $this->nnCaptureParams['tid_status'] && (in_array($this->nnCaptureParams['payment_type'], ['ONLINE_TRANSFER', 'PAYPAL', 'EPS', 'GIROPAY', 'PRZELEWY24', 'IDEAL', 'CREDITCARD', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE']))) {
            $this->orderTransactionStateHandler->paid($this->orderServiceItem->getId(), $this->salesContext->getContext());
        } elseif ('100' !== $this->nnCaptureParams['status']) {
            $this->orderTransactionStateHandler->cancel($this->orderServiceItem->getId(), $this->salesContext->getContext());
        }

        $data = [
        'id' => $this->orderServiceItem->getId(),
        'customFields' => [
            'novalnet_tid' => $this->nnCaptureParams['tid'],
            'novalnet_comments' => $note,
            ],
        ];

        $this->orderTransactionRepository->update([$data], $this->salesContext->getContext());
        $this->insertNovalnetDetails($this->nnCaptureParams, $note);
        $this->debugMessage($this->newLine.$note, $this->nnCaptureParams['order_no']);
        return;
    }

    /**
     * Update the comments in order transaction table.
     *
     * @param $comments
     *
     * @return null
     */
    public function updateComments($comments)
    {
        $oldComments = '';
        if (!in_array($this->nnCaptureParams['payment_type'], array('INVOICE_START','GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA'))) {
            $oldComments = $this->orderServiceItem->getCustomFields()['novalnet_comments'];
        }
        
        if (!empty($oldComments)) {
            $callbackComments = $oldComments.'/ '.$comments;
        } else {
            $callbackComments = $comments;
        }
        $data = [
            'id' => $this->orderServiceItem->getId(),
            'customFields' => [
                    'novalnet_comments' => $callbackComments,
            ],
        ];

        $this->orderTransactionRepository->update([$data], $this->salesContext->getContext());
    }

    /**
     * Insert data in novalnet transaction detail table.
     *
     * @param $novalnetParams
     * @param $note
     *
     * @return null
     */
    public function insertNovalnetDetails($novalnetParams, $note)
    {
        $optionalParams = [
                        'currency' => $novalnetParams['currency'],
                        'test_mode' => $novalnetParams['test_mode'],
                        ];
        $configuration = json_encode(array_merge($this->configDeatils, $optionalParams));
        $this->query->addInsert('novalnet_transaction_details', [
        'id' => Uuid::randomBytes(),
        'tid' => $novalnetParams['tid'],
        'payment_type' => $this->paymentName,
        'amount' => $novalnetParams['amount'],
        'paid_amount' => in_array($this->paymentName, ['novalnetinvoice', 'novalnetprepayment', 'novalnetcashpayment']) ? 0 : $novalnetParams['amount'],
        'gateway_status' => isset($novalnetParams['tid_status']) ? $novalnetParams['tid_status'] : 0,
        'order_no' => $novalnetParams['order_no'],
        'customer_no' => $novalnetParams['customer_no'],
        'lang' => 'en',
        'additional_details' => $configuration,
        ]);
        $this->query->execute();
    }

    /**
     * Get given payment_type level for process.
     *
     * @param null
     *
     * @return string
     */
    public function getPaymentTypeLevel()
    {
        if (in_array($this->nnCaptureParams['payment_type'], $this->aryPayments)) {
            return 'available_payments';
        } elseif (in_array($this->nnCaptureParams['payment_type'], $this->aryChargebacks)) {
            return 'charge_back_payments';
        } elseif (in_array($this->nnCaptureParams['payment_type'], $this->aryCollection)) {
            return 'collections_payments';
        }
    }

    /**
     * Get paid amount from the novalnet table.
     *
     * @param $tid
     *
     * @return string
     */
    public function getOrderPaidAmount($tid)
    {
        $paidAmount = $this->connection
            ->executeQuery('SELECT paid_amount FROM novalnet_transaction_details WHERE tid = :tid', ['tid' => $tid])
            ->fetchAll();
        return $paidAmount[0]['paid_amount'];
    }

    /**
     * Check mail if validate or not.
     *
     * @param $mail
     *
     * @return null
     */
    public function checkEmail($mail)
    {
        $validator = new EmailValidator();
        if (!$validator->isValid($this->configDeatils['mailTo'], new RFCValidation())) {
            throw new RfcComplianceException(sprintf('Email "%s" does not comply with addr-spec of RFC 2822.', $mail));
        }
    }
}
