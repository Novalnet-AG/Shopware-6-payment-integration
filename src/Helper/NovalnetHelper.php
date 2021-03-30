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

namespace Novalnet\NovalnetPayment\Helper;

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\MailTemplate\Service\MailServiceInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Request;

class NovalnetHelper
{
    /**
     * @var array
     */
    private $redirectPayments = ['novalnetideal', 'novalnetpaypal', 'novalnetgiropay', 'novalneteps', 'novalnetprzelewy24', 'novalnetsofort'];
    
    /**
     * @var array
     */
    private $securedParams = ['auth_code', 'product', 'tariff', 'amount', 'test_mode'];
    
    /**
     * @var array
     */
    private $configDetails;

    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepo;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EntityRepository
     */
    private $mailTemplateRepository;

    /**
     * @var MailServiceInterface
     */
    private $mailService;

    /**
     * @var SessionInterface
     */
    private $SessionInterface;

    /**
     * @var SessionInterface
     */
    private $container;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var string
     */
    private $shopToken;

    /**
     * @var string
     */
    private $accessKey;
    
    /**
     * @var string
     */
    private $shopVersion;
    
    /**
     * @var string
     */
    private $paymentName;
    
    /**
     * @var Request
     */
    private $request;
    
    private $logger;

    public function __construct(
        EntityRepositoryInterface $languageRepo,
        SystemConfigService $systemConfigService,
        RouterInterface $router,
        Connection $connection,
        EntityRepository $mailTemplateRepository,
        MailServiceInterface $mailService,
        SessionInterface $SessionInterface,
        ContainerInterface $container,  
        LoggerInterface $logger,     
        string $shopVersion
    ) {
        $this->connection = $connection;
        $this->languageRepo = $languageRepo;
        $this->router = $router;
        $this->mailService = $mailService;
        $this->shopVersion = $shopVersion;
        $this->request     = $container->get('request_stack')->getCurrentRequest();
        $this->SessionInterface = $SessionInterface;
        $this->systemConfigService = $systemConfigService;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->orderRepository = $container->get('order.repository');
        $this->paymentRepository = $container->get('payment_method.repository');
        $this->logger = $logger;
        
    }

    /**
     * Novalnet individual payment information.
     *
     * @param null
     *
     * @return array
     */
    public function getPaymentInfo(): array
    {
        $paymentArray = [
            'novalnetcreditcard' => [
                'key' => 6,
                'payment_type' => 'CREDITCARD',
            ],
            'novalnetpaypal' => [
                'key' => 34,
                'payment_type' => 'PAYPAL',
            ],
            'novalnetsepa' => [
                'key' => 37,
                'payment_type' => 'DIRECT_DEBIT_SEPA',
            ],
            'novalnetsofort' => [
                'key' => 33,
                'payment_type' => 'ONLINE_TRANSFER',
            ],
            'novalnetideal' => [
                'key' => 49,
                'payment_type' => 'IDEAL',
            ],
            'novalneteps' => [
                'key' => 50,
                'payment_type' => 'EPS',
            ],
            'novalnetgiropay' => [
                'key' => 69,
                'payment_type' => 'GIROPAY',
            ],
            'novalnetinvoice' => [
                'key' => 27,
                'payment_type' => 'INVOICE_START',
            ],
            'novalnetprepayment' => [
                'key' => 27,
                'payment_type' => 'INVOICE_START',
            ],
            'novalnetcashpayment' => [
                'key' => 59,
                'payment_type' => 'CASHPAYMENT',
            ],
            'novalnetprzelewy24' => [
                'key' => 78,
                'payment_type' => 'PRZELEWY24',
            ],
        ];

        return $paymentArray;
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

    /**
     * Request to payment gateway action.
     *
     * @param array  $params
     * @param string $url
     * @param string $stringConversion
     *
     * @return array
     */
    public function curlRequest($params, $url, $stringConversion = true)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);

        // CURL time-out
        curl_setopt($curl, CURLOPT_TIMEOUT, 240);

        // Execute cURL
        $result = curl_exec($curl);

        if ($stringConversion) {
            parse_str($result, $response);
            return $response;
        }

        return $result;
    }

    /**
     * Returns the Novalnet backend configuration.
     *
     * @param $salesChannelContext
     *
     * @return array
     */
    public function getNovalnetConfig(SalesChannelContext $salesChannelContext): array
    {
        $values = $this->systemConfigService->getDomain(
            'NovalnetPayment.settings.',
            $salesChannelContext->getSalesChannel()->getId(),
            true
        );

        $propertyValuePairs = [];

        /** @var string $key */
        foreach ($values as $key => $value) {
            $property = substr($key, strlen('NovalnetPayment.settings.'));
            $propertyValuePairs[$property] = $value;
        }
        return $propertyValuePairs;
    }

    /**
     * Get the all request params and payment params.
     *
     * @param object $salesChannelContext
     * @param object $orderDetails
     * @param object $dataBag
     *
     * @return array
     */
    public function getRequestParams($salesChannelContext, $orderDetails, $dataBag = null): array
    {
        $this->configDetails = $this->getNovalnetConfig($salesChannelContext);
        $this->shopToken = Random::getAlphanumericString(32);
        if (!$this->validateMerchantParams($this->configDetails)) {
            throw new InvalidOrderException($orderDetails->getOrderTransaction()->getId());
        }
        $this->paymentName = $orderDetails->getOrderTransaction()->getPaymentMethod()->getCustomFields()['novalnet_payment_method_name'];
        $merchantData = $this->getMerchantDetails($this->configDetails);
        $customerData = $this->getCustomerDataBagFromPayment($salesChannelContext->getCustomer());
        $orderData = $this->getOrderDetails($orderDetails, $salesChannelContext);
        $paymentData = $this->getPaymentDetails($orderDetails, $salesChannelContext->getCustomer(), $dataBag);
        $requestParams = array_merge($merchantData, $customerData, $orderData, $paymentData);
        if (!empty($this->configDetails['creditcard.cc3D']) || !empty($this->configDetails['creditcard.forcecc3D'])) {
            array_push($this->redirectPayments, 'novalnetcreditcard');
        }
        if (in_array($this->paymentName, $this->redirectPayments)) {
            $this->getRedirectParameter($requestParams, $orderDetails,$salesChannelContext);
            $this->getAdditionalParameter($requestParams, $orderDetails);
            $requestParams = array_map('trim', $requestParams);
            $this->SessionInterface->set('novalnetParams', $requestParams);
            $this->SessionInterface->set('novalnet_redirect', '1');
            return $requestParams;
        } else {
            $requestParams = array_map('trim', $requestParams);
            $responseParams = $this->curlRequest($requestParams, 'https://payport.novalnet.de/paygate.jsp');

            if (!isset($responseParams['lang'])) {
                $responseParams['lang'] = $requestParams['lang'];
            }
            $this->insertNovalnetTransactionDetails($responseParams, $this->configDetails, $this->paymentName);
            return $responseParams;
        }
    }

    /**
     * Get the customer dataBag.
     *
     * @param object $customer
     *
     * @return array
     */
    public function getCustomerDataBagFromPayment(CustomerEntity $customer): array
    {
        $street = $customer->getActiveBillingAddress()->getStreet().' '.$customer->getActiveBillingAddress()->getAdditionalAddressLine1().' '.$customer->getActiveBillingAddress()->getAdditionalAddressLine2();
        
        $customerDataBag = [
        'firstname' => $customer->getActiveBillingAddress()->getFirstName(),
        'lastname' => $customer->getActiveBillingAddress()->getLastName(),
        'gender' => (('mr' === $customer->getSalutation()->getSalutationKey()) ? 'm' : (('mrs' === $customer->getSalutation()->getSalutationKey()) ? 'f' : 'u')),
        'email' => $customer->getEmail(),
        'street' => $street,
        'city' => $customer->getActiveBillingAddress()->getCity(),
        'zip' => $customer->getActiveBillingAddress()->getZipCode(),
        'remote_ip' => $this->request->getClientIp(),
        'country_code' => $customer->getActiveBillingAddress()->getCountry()->getIso(),
        'search_in_street' => 1,
        'customer_no' => $customer->getCustomerNumber(),
        'system_name' => 'Shopware',
        'system_ip' => $this->request->server->get('SERVER_ADDR'),
        'system_version' => 'Shopware'.$this->shopVersion.'-NN1.0.5',
        'system_url' => $this->router->generate('frontend.checkout.finish.order', ['sw-token' => $this->shopToken], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if (!empty($customer->getActiveBillingAddress()->getPhoneNumber())) {
            $customerDataBag['tel'] = $customer->getActiveBillingAddress()->getPhoneNumber();
        }

        if (!empty($customer->getActiveBillingAddress()->getCompany())) {
            $customerDataBag['company'] = $customer->getActiveBillingAddress()->getCompany();
            if (!empty($customer->getActiveBillingAddress()->getVatId())) {
                $customerDataBag['vat_id'] = $customer->getActiveBillingAddress()->getVatId();
            }
        }
        return $customerDataBag;
    }

    /**
     * Get the merchant details.
     *
     * @param array $config
     *
     * @return array
     */
    public function getMerchantDetails($config): array
    {
        return[
        'vendor' => $config['vendorId'],
        'auth_code' => $config['authCode'],
        'tariff' => $config['tariff'],
        'product' => $config['productId'],
        ];
    }

    /**
     * Validate merchant details if empty return false.
     *
     * @param array $configDetails
     *
     * @return boolean
     */
    public function validateMerchantParams($configDetails): bool
    {
        return (bool) (!empty($configDetails['vendorId']) && !empty($configDetails['authCode']) && !empty($configDetails['productId']) && !empty($configDetails['tariff']));
    }

    /**
     * To fetch the order details from repository.
     *
     * @param $transaction
     * @param $context
     *
     * @return array
     */
    private function getOrderDetails($transaction, $context): array
    {
        return[
        'order_no' => $transaction->getOrder()->getOrderNumber(),
        'amount' => $transaction->getOrder()->getPrice()->getTotalPrice() * 100,
        'currency' => $context->getSalesChannel()->getCurrency()->getIsoCode(),
        'lang' => $this->getLocaleCodeFromContext($context->getContext())
        ];
    }

    /**
     * To get the system language selected from customer.
     *
     * @param $context
     *
     * @return string
     */
    public function getLocaleCodeFromContext(Context $context): string
    {
        $languageId = $context->getLanguageId();
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');
        /** @var LanguageCollection $languageCollection */
        $languageCollection = $this->languageRepo->search($criteria, $context)->getEntities();

        $language = $languageCollection->get($languageId);
        if (null === $language) {
            return 'en';
        }

        $locale = $language->getLocale();
        if (!$locale) {
            return 'en';
        }
        $lang = explode('-', $locale->getCode());
        return $lang[0];
    }

    /**
     * To fetch the payment details from the given details.
     *
     * @param $order
     * @param $customer
     * @param $dataBag
     *
     * @return array
     */
    private function getPaymentDetails($order, $customer, $dataBag = null): array
    {
        $paymentParams['key'] = $this->getPaymentInfo()[$this->paymentName]['key'];
        $paymentParams['payment_type'] = $this->getPaymentInfo()[$this->paymentName]['payment_type'];
        $paymentShortName = str_replace('novalnet', '', $this->paymentName);
        $paymentParams['test_mode'] = !empty($this->configDetails[$paymentShortName.'.testMode']) ? $this->configDetails[$paymentShortName.'.testMode'] : 0;

        $manualCheckLimit = !empty($this->configDetails[$paymentShortName.'.onHoldAmount']) ? $this->configDetails[$paymentShortName.'.onHoldAmount'] : 0;
        $orderAmount = $order->getOrder()->getPrice()->getTotalPrice() * 100;

        if (!empty($this->configDetails[$paymentShortName.'.onHold']) && 'authroize' === $this->configDetails[$paymentShortName.'.onHold'] && is_numeric($manualCheckLimit) && $orderAmount > 0 && (int) $orderAmount >= (int) $manualCheckLimit) {
            $paymentParams['on_hold'] = 1;
        }
        
        if (in_array($this->paymentName, ['novalnetinvoice', 'novalnetprepayment'])) {
            if ('novalnetinvoice' === $this->paymentName) {
                $paymentParams['invoice_type'] = 'INVOICE';

                if (!empty($this->configDetails[$paymentShortName.'.guarantee']) && !empty($dataBag->get('invoice_guarantee_success')) && ((!empty($dataBag->get('nn_invoice_birth_date')) && !empty($this->validateAge($dataBag->get('nn_invoice_birth_date')))) || !empty($customer->getActiveBillingAddress()->getCompany()))) {
                    $paymentParams['key'] = '41';
                    $paymentParams['payment_type'] = 'GUARANTEED_INVOICE';
                    if (!empty($dataBag->get('nn_invoice_birth_date'))) {
                        $paymentParams['birth_date'] = date('Y-m-d', strtotime(str_replace('/', '-', $dataBag->get('nn_invoice_birth_date'))));
                    }
                }

                if (!empty($this->configDetails[$paymentShortName.'.dueDate'])) {
                    $paymentParams['due_date'] = $this->getInvoiceDueDate($this->configDetails[$paymentShortName.'.dueDate']);
                }
            } else {
                $paymentParams['invoice_type'] = 'PREPAYMENT';
            }
            $paymentParams['invoice_ref'] = 'BNR-'.$this->configDetails['productId'].'-'.$order->getOrder()->getOrderNumber();
        } elseif ('novalnetsepa' === $this->paymentName) {
            $paymentParams['bank_account_holder'] = $dataBag->get('nn_bank_account_holder');
            $paymentParams['iban'] = $dataBag->get('nn_iban');

            if (!empty($this->configDetails[$paymentShortName.'.guarantee']) && !empty($dataBag->get('sepa_guarantee_success')) && ((!empty($dataBag->get('nn_sepa_birth_date')) && !empty($this->validateAge($dataBag->get('nn_sepa_birth_date')))) || !empty($customer->getActiveBillingAddress()->getCompany()))) {
                $paymentParams['key'] = '40';
                $paymentParams['payment_type'] = 'GUARANTEED_DIRECT_DEBIT_SEPA';
                if (!empty($dataBag->get('nn_sepa_birth_date'))) {
                    $paymentParams['birth_date'] = date('Y-m-d', strtotime(str_replace('/', '-', $dataBag->get('nn_sepa_birth_date'))));
                }
            }

            if (!empty($this->configDetails[$paymentShortName.'.dueDate']) && $this->configDetails[$paymentShortName.'.dueDate'] >= 2 && $this->configDetails[$paymentShortName.'.dueDate'] <= 14) {
                $paymentParams['sepa_due_date'] = date('Y-m-d', strtotime('+'.max(0, intval($this->configDetails[$paymentShortName.'.dueDate'])).' days'));
            }
        } elseif ('novalnetcreditcard' === $this->paymentName) {
            $paymentParams['pan_hash'] = $dataBag->get('novalnet_cc_hash');
            $paymentParams['unique_id'] = $dataBag->get('novalnet_cc_uniqueid');
            if (!empty($this->configDetails['creditcard.cc3D'])) {
                $paymentParams['cc_3d'] = 1;
            }
        } elseif ('novalnetcashpayment' === $this->paymentName && !empty($this->configDetails[$paymentShortName.'.dueDate'])) {
            $paymentParams['cp_due_date'] = $this->getInvoiceDueDate($this->configDetails[$paymentShortName.'.dueDate']);
        }
        return $paymentParams;
    }

    /**
     * Novalnet transaction information.
     *
     * @param array $novalnetResponse
     * @param array $configuration
     * @param array $paymentName
     *
     * @return null
     */
    public function insertNovalnetTransactionDetails($novalnetResponse, $configuration, $paymentName)
    {
        $insertQuery = new MultiInsertQueryQueue($this->connection, 250, false, false);
        switch ($paymentName) {
            case 'novalnetinvoice':
            case 'novalnetprepayment':
            $additionalDetails = [];
            if (!empty($novalnetResponse['tid_status']) && in_array($novalnetResponse['tid_status'], [91, 100])) {
                $additionalDetails = ['account_holder' => $this->CheckIfExists($novalnetResponse['invoice_account_holder']),
                        'account_number' => $this->CheckIfExists($novalnetResponse['invoice_account']),
                        'bank_code' => $this->CheckIfExists($novalnetResponse['invoice_bankcode']),
                        'bank_name' => $this->CheckIfExists($novalnetResponse['invoice_bankname']),
                        'bank_city' => $this->CheckIfExists($novalnetResponse['invoice_bankplace']),
                        'bank_iban' => $this->CheckIfExists($novalnetResponse['invoice_iban']),
                        'bank_bic' => $this->CheckIfExists($novalnetResponse['invoice_bic']),
                        'due_date' => $this->CheckIfExists($novalnetResponse['due_date']),
                        'currency' => $this->CheckIfExists($novalnetResponse['currency']),
                        'test_mode' => $this->CheckIfExists($novalnetResponse['test_mode']),
                        ];
            } else {
                $additionalDetails = [
                                'currency' => $this->CheckIfExists($novalnetResponse['currency']),
                                'test_mode' => $this->CheckIfExists($novalnetResponse['test_mode']),
                            ];
            }
                break;

            default:
                $additionalDetails = [
                                'currency' => $this->CheckIfExists($novalnetResponse['currency']),
                                'test_mode' => $this->CheckIfExists($novalnetResponse['test_mode']),
                            ];
                break;
            }

        $additionalDetails = json_encode($additionalDetails);
        $insertQuery->addInsert('novalnet_transaction_details', [
        'id' => Uuid::randomBytes(),
        'tid' => $novalnetResponse['tid'],
        'payment_type' => $paymentName,
        'amount' => (in_array($paymentName, $this->redirectPayments) ? $novalnetResponse['amount'] : $novalnetResponse['amount'] * 100),
        'paid_amount' => (in_array($paymentName, ['novalnetinvoice', 'novalnetprepayment', 'novalnetcashpayment']) || empty($novalnetResponse['tid_status']) || (!empty($novalnetResponse['tid_status']) && $novalnetResponse['tid_status'] != 100)) ? 0 : (in_array($paymentName, $this->redirectPayments) ? $novalnetResponse['amount'] : $novalnetResponse['amount'] * 100),
        'gateway_status' => !empty($novalnetResponse['tid_status']) ? $novalnetResponse['tid_status'] : $novalnetResponse['status'],
        'order_no' => $novalnetResponse['order_no'],
        'customer_no' => $novalnetResponse['customer_no'],
        'lang' => $novalnetResponse['lang'],
        'additional_details' => $additionalDetails,
        ]);
        $insertQuery->execute();
        $this->updateOrderConfirmationMail($this->connection, 'action.mail.send');
    }

    /**
     * Prepare comments.
     *
     * @param $response
     * @param $paymentName
     * @param $context
     * @param $paymentId
     *
     * @return string
     */
    public function prepareComments($response, $paymentName, Context $context, $paymentId = null)
    {
        $newLine = '/ ';
        $note = '';
        
        if (!empty($response['lang'])) {
            if ('de' == $response['lang']) {
                $novalnetLang = $this->getLanguage('de_DE');
            } else {
                $novalnetLang = $this->getLanguage('en_GB');
            }
        } else {
            if ('de' == $this->getLocaleCodeFromContext($context)) {
                $novalnetLang = $this->getLanguage('de_DE');
            } else {
                $novalnetLang = $this->getLanguage('en_GB');
            }
        }

        if (!empty($response['status']) && '100' !== $response['status']) {
            $note .= (isset($response['status_desc']) ? $response['status_desc'] : (isset($response['status_text']) ? $response['status_text'] : '')).'/ ';
        }

        $note .= $novalnetLang['novalnet_tid_label'].':  '.$response['tid'].$newLine;
        if (!empty($response['test_mode'])) {
            $note .= $novalnetLang['novalnet_message_test_order'].$newLine;
        }

        if (!empty($response['payment_id']) && in_array($response['payment_id'], [41, 40])) {
            $note .= $novalnetLang['guarantee_text'].$newLine;
        }
        if (!empty($response['tid_status']) && '75' === $response['tid_status'] && 'novalnetsepa' === $paymentName) {
            $note .= $novalnetLang['pending_guarantee_text_sepa'].$newLine;
        } elseif (in_array($paymentName, ['novalnetinvoice', 'novalnetprepayment'])) {
            if (!empty($response['tid_status']) && in_array($response['tid_status'], ['100', '91'])) {
                $note .= $newLine.$novalnetLang['novalnet_payment_mail_invoice'].$newLine;

                if (!empty($response['due_date'])) {
                    $note .= $novalnetLang['novalnet_payment_valid_until'].(('en' === $this->getLocaleCodeFromContext($context)) ? date('d/m/Y', strtotime($response['due_date'])) : date('d.m.Y', strtotime($response['due_date']))).$newLine;
                }

                $note .= $novalnetLang['novalnet_account_owner'].$response['invoice_account_holder'].$newLine;
                $note .= $novalnetLang['novalnet_bank_iban'].$response['invoice_iban'].$newLine;
                $note .= $novalnetLang['novalnet_bank_bic'].$response['invoice_bic'].$newLine;

                if (!empty($response['invoice_bankname']) && !empty($response['invoice_bankplace'])) {
                    $note .= $novalnetLang['novalnet_bank_name'].$response['invoice_bankname'].' '.trim($response['invoice_bankplace']).$newLine;
                }

                $note .= $novalnetLang['novalnet_order_amount'].(('de' === $this->getLocaleCodeFromContext($context)) ? str_replace('.', ',', $response['amount']) : $response['amount']).' '.$response['currency'].$newLine;
                $note .= $novalnetLang['novalnet_invoice_note_multiple_reference'].$newLine.$newLine;
                if (!empty($response['invoice_ref'])) {
                    $note .= $novalnetLang['novalnet_reference1'].': '.$response['invoice_ref'].$newLine;
                }
                $note .= $novalnetLang['novalnet_reference2'].': TID '.' '.$response['tid'].$newLine;
            } elseif (!empty($response['tid_status']) && '75' === $response['tid_status']) {
                $note .= $novalnetLang['pending_guarantee_text'].$newLine;
            }
        } elseif ('novalnetcashpayment' === $paymentName) {
            $novalnetSlipExpiryDate = ($response['cp_due_date']) ? $response['cp_due_date'] : '';
            if ($novalnetSlipExpiryDate) {
                $note .= $novalnetLang['cashpayment_slip_exp_date'].': '.(('en' === $this->getLocaleCodeFromContext($context)) ? date('d M Y', strtotime($novalnetSlipExpiryDate)) : date('d.m.Y', strtotime($novalnetSlipExpiryDate))).$newLine;
            }
            $note .= $newLine.$novalnetLang['cashpayment_store'].$newLine;
            $nearestStoreCounts = 1;
            foreach ($response as $key => $value) {
                if (false !== strpos($key, 'nearest_store_title')) {
                    ++$nearestStoreCounts;
                }
            }
            for ($i = 1; $i < $nearestStoreCounts; ++$i) {
                $note .= $response['nearest_store_title_'.$i].$newLine;
                $note .= $response['nearest_store_street_'.$i].$newLine;
                $note .= $response['nearest_store_city_'.$i].$newLine;
                $note .= $response['nearest_store_zipcode_'.$i].$newLine;
                $note .= $response['nearest_store_country_'.$i].$newLine.$newLine;
            }
        }

        return $note;
    }

    /**
     * To generate unique Id.
     *
     * @param null
     *
     * @return string
     */
    public function generateUniqueId()
    {
        $randomArray = explode(',', '8,7,6,5,4,3,2,1,9,0,9,7,6,1,2,3,4,5,6,7,8,9,0');
        shuffle($randomArray);
        return substr(implode($randomArray, ''), 0, 16);
    }

    /**
     * Encodes the parameter request using ENC implementation.
     *
     * @param array $requestParams
     *
     * @return null
     */
    public function encodeData(&$requestParams)
    {
        $uniqId = $this->generateUniqueId();
        foreach ($this->securedParams as $key) {
            // Encoding process
            $requestParams[$key] = $this->generateEncodeData((string) $requestParams[$key], $uniqId);
        }
        $requestParams['uniqid'] = $uniqId;
        $requestParams['hash'] = $this->generateHash($requestParams);
    }

    /**
     * Encodes the parameter request using ENC implementation.
     *
     * @param array  $data
     * @param string $uniqId
     *
     * @return string
     */
    public function generateEncodeData($data, $uniqId)
    {
        // Encryption process
        return htmlentities(base64_encode(openssl_encrypt($data, 'aes-256-cbc', $this->configDetails['accessKey'], 1, $uniqId)));
    }

    /**
     * Generate the hash for redirect payments.
     *
     * @param array $data
     *
     * @return string
     */
    public function generateHash($data)
    {
        // Hash generation using sha256 and encoded merchant details
        return hash('sha256', ($data['auth_code'].$data['product'].$data['tariff'].$data['amount'].$data['test_mode'].$data['uniqid'].strrev($this->configDetails['accessKey'])));
    }

    /**
     * Returns the prepared redirect parameter data.
     *
     * @param $requestParams
     * @param $transaction
     * @param $salesChannelContext
     *
     * @return null
     */
    public function getRedirectParameter(&$requestParams, $transaction,$salesChannelContext): void
    {
        $salesChannelRoute  =   '';
        $salesChannelRoute = $salesChannelContext->getSalesChannel()->getDomains()->first()->getUrl();
        $requestParams['implementation'] = 'ENC';
        if( ! empty( $salesChannelRoute ) ) {
            $requestParams['return_url'] = $salesChannelRoute.'/novalnet/response?sw-token='.$this->shopToken;  
            $requestParams['error_return_url'] = $salesChannelRoute.'/novalnet/response?sw-token='.$this->shopToken;  
        } else {
            $requestParams['return_url'] = $this->router->generate('frontend.action.novalnetpayment.response-action', ['sw-token' => $this->shopToken], UrlGeneratorInterface::ABSOLUTE_URL);
             $requestParams['error_return_url'] = $this->router->generate('frontend.action.novalnetpayment.response-action', ['sw-token' => $this->shopToken], UrlGeneratorInterface::ABSOLUTE_URL); 
        } 
        $requestParams['return_method'] = $requestParams['error_return_method'] = 'POST';
        $this->encodeData($requestParams);
    }

    /**
     * Returns the additional redirect parameter data.
     *
     * @param $requestParams
     * @param $transaction
     *
     * @return null
     */
    public function getAdditionalParameter(&$requestParams, $transaction): void
    {
        $requestParams['input1'] = 'paymentToken';
        $urlComponents = parse_url($transaction->getreturnUrl());
        parse_str($urlComponents['query'], $paymentToken);
        $requestParams['inputval1'] = $paymentToken['_sw_payment_token'];
    }

    /**
     * Decodes the parameter request using ENC implementation.
     *
     * @param array $novalnetResponse
     * @param array $configuration
     *
     * @return bool
     */
    public function decodeData(&$novalnetResponse, $configuration): void
    {
        $this->accessKey = $configuration['accessKey'];

        foreach ($this->securedParams as $value) {
            if (isset($novalnetResponse[$value])) {
                $novalnetResponse[$value] = $this->decrypt($novalnetResponse[$value], $novalnetResponse['uniqid']);
            }
        }
        $this->SessionInterface->set('novalnet_redirect', '');
    }

    /**
     * Decrypts the input data based on the openssl decrypt method.
     *
     * @param string $input
     * @param int    $salt
     *
     * @return string
     */
    protected function decrypt($input, $salt): string
    {
        // Return decrypted Data.
        return openssl_decrypt(base64_decode($input), 'aes-256-cbc', $this->accessKey, 1, $salt);
    }

    /**
     * send novalnet order mail.
     *
     * @param object $context
     * @param object $mailTemplate
     * @param object $order
     * @param string $note
     *
     * @return null
     */
    public function sendMail(
        Context $context,
        MailTemplateEntity $mailTemplate,
        OrderEntity $order,
        $note
    ): void {
        $customer = $order->getOrderCustomer();
        if (null === $customer) {
            return;
        }

        $data = new ParameterBag();
        $data->set(
            'recipients',
            [
                $customer->getEmail() => $customer->getFirstName().' '.$customer->getLastName(),
            ]
        );
        $data->set('senderName', $mailTemplate->getSenderName());
        $data->set('salesChannelId', $order->getSalesChannelId());

        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', $mailTemplate->getContentPlain());
        $data->set('subject', $mailTemplate->getSubject());
        
        try {
        $this->mailService->send(
            $data->all(),
            $context,
            [
                'order' => $order,
                'salesChannel' => $order->getSalesChannel(),
                'note' => $note,
            ]
        );
    }
    
     catch (\Exception $e) {
            $this->logger->error(
                "Could not send mail:\n"
                . $e->getMessage() . "\n"
                . 'Error Code:' . $e->getCode() . "\n"
                . "Template data: \n"
                . json_encode($data->all()) . "\n"
            );
        }
   }

    /**
     * get the order mail template.
     *
     * @param object $context
     * @param $technicalName
     *
     * @return object
     */
    public function getMailTemplate(Context $context, string $technicalName): MailTemplateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);

        /** @var MailTemplateEntity|null $mailTemplate */
        $mailTemplate = $this->mailTemplateRepository->search($criteria, $context)->first();

        return $mailTemplate;
    }

    /**
     * get the order reference details.
     *
     * @param string $orderId
     * @param string $customerId
     *
     * @return object
     */
    public function getOrderCriteria(?string $orderId = null, ?string $customerId = null): Criteria
    {
        if ($orderId) {
            $orderCriteria = new Criteria([$orderId]);
        } else {
            $orderCriteria = new Criteria([]);
        }

        if (null !== $customerId) {
            $orderCriteria->addFilter(
                new EqualsFilter('order.orderCustomer.customerId', $customerId)
            );
        }

        $orderCriteria->addAssociation('orderCustomer.salutation');
        $orderCriteria->addAssociation('orderCustomer.customer');
        $orderCriteria->addAssociation('currency');
        $orderCriteria->addAssociation('stateMachineState');
        $orderCriteria->addAssociation('lineItems');
        $orderCriteria->addAssociation('transactions');
        $orderCriteria->addAssociation('transactions.paymentMethod');
        $orderCriteria->addAssociation('addresses');
        $orderCriteria->addAssociation('deliveries.shippingMethod');
        $orderCriteria->addAssociation('addresses.country');
        $orderCriteria->addAssociation('deliveries.shippingOrderAddress.country');
        $orderCriteria->addAssociation('salesChannel');
        $orderCriteria->addAssociation('price');

        return $orderCriteria;
    }

    /**
     * To get invoice due date details.
     *
     * @param $date String
     *
     * @return mixed
     */
    public function getInvoiceDueDate($date)
    {
        return (is_numeric($date)) ? date('Y-m-d', strtotime('+'.max(0, intval($date)).' days')) : false;
    }

    /**
     * update the shop order mail action.
     *
     * @param $connection
     * @param $values
     *
     * @return null
     */
    public function updateOrderConfirmationMail($connection, $values = '')
    {
        $connection->update('event_action', ['action_name' => $values], ['event_name' => 'checkout.order.placed']);
    }

    /**
     * send novalnet mail.
     *
     * @param $transaction
     * @param $salesChannelContext
     * @param $note
     *
     * @return null
     */
    public function PrepareMailContent($transaction, $salesChannelContext, $note)
    {
        $orderEntiy = $this->getOrderCriteria($transaction->getOrder()->getId(), $transaction->getOrder()->getOrderCustomer()->getCustomerId());
        $order = $this->orderRepository->search($orderEntiy, $salesChannelContext->getContext())->first();
        $mailTemplate = $this->getMailTemplate($salesChannelContext->getContext(), 'novalnet_order_confirmation_mail');
        $this->sendMail($salesChannelContext->getContext(), $mailTemplate, $order, $note);
    }

    /**
     * check if the param exists or not.
     *
     * @param object $param
     *
     * @return string|null
     */
    public function CheckIfExists($param)
    {
        return (!empty($param)) ? $param : 0;
    }

    /**
     * validate the age.
     *
     * @param $date
     *
     * @return bool
     */
    public function validateAge($date)
    {        
        $now = strtotime(str_replace('/', '-',$date));
        //The age to be over, over +18
        $min = strtotime('+18 years',$now);

        if (time() < $min || $now == strtotime('00/00/0000')) {
            return false;
        }
        return true;
    }
}
