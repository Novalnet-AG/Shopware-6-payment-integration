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

namespace Novalnet\NovalnetPayment\Twig\Filter;

use Novalnet\NovalnetPayment\Helper\NovalnetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension relate to PHP code and used by the profiler and the default exception templates.
 */
class CustomFilter extends AbstractExtension
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var NovalnetHelper
     */
    private $helper;

    public function __construct(
        TranslatorInterface $translator,
        NovalnetHelper $helper
    ) {
        $this->translator = $translator;
        $this->helper = $helper;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('base64_encode', [$this, 'base64_en']), // base64_encode => name of custom filter, base64_en => name of function to execute when this filter is called.
            new TwigFilter('check_guarantee', [$this, 'check_guarantee_conditions']),
            new TwigFilter('wordwrap', [$this, 'wordwrap']),
            new TwigFilter('test_mode_description', [$this, 'testModeDescription']),
            new TwigFilter('shop_lang', [$this, 'getShopLang']),
        ];
    }

    /**
     * Add base 64 encode filter.
     *
     * @param input
     *
     * @return string
     */
    public function base64_en($input)
    {
        return base64_encode($input);
    }

    /**
     * test mode value.
     *
     * @param $paymentShortName
     * @param $configDetails
     *
     * @return string
     */
    public function testModeDescription($paymentShortName, $configDetails)
    {
        return isset($configDetails[$paymentShortName]['testMode']) && !empty($configDetails[$paymentShortName]['testMode']);
    }

    /**
     * Check the guarantee condition and return value.
     *
     * @param $context
     * @param $page
     * @param $configDetails
     * @param $paymentShortName
     *
     * @return string
     */
    public function check_guarantee_conditions($context, $page, $configDetails, $paymentShortName)
    {
		$totalPrice = 0;

		if(method_exists($page,'getCart'))
			$totalPrice = $page->getCart()->getPrice()->getTotalPrice() * 100;
		else
			$totalPrice = $page->getOrder()->getPrice()->getTotalPrice() * 100;
			
        $currency = $context->getCurrency()->getIsoCode();
        $billingAddress = $context->getCustomer()->getActiveBillingAddress();
        $shippingAddress = $context->getCustomer()->getActiveShippingAddress();
        $cartPrice = $totalPrice;
        $guaranteeMessage = '';
        $billingAry = [
            'street' => $billingAddress->getStreet(),
            'zipcode' => $billingAddress->getZipCode(),
            'city' => $billingAddress->getCity(),
            'countryId' => $billingAddress->getCountry()->getIso(),
        ];
        $shippingAry = [
            'street' => $shippingAddress->getStreet(),
            'zipcode' => $shippingAddress->getZipCode(),
            'city' => $shippingAddress->getCity(),
            'countryId' => $billingAddress->getCountry()->getIso(),
        ];

        $minAmount = (!empty($configDetails[$paymentShortName]['guaranteeMinimumOrderAmount']) && $configDetails[$paymentShortName]['guaranteeMinimumOrderAmount'] >= 999) ? $configDetails[$paymentShortName]['guaranteeMinimumOrderAmount'] : 999;

        if (round($cartPrice) < round($minAmount)) {
            $formatAmount = str_replace('.', ',', number_format($minAmount / 100, 2));
            $guaranteeMessage = sprintf($this->translator->trans('NovalnetPayments.guaranteeError.amountError'), $formatAmount);
        } elseif (!in_array($billingAddress->getCountry()->getIso(), ['DE', 'AT', 'CH'])) {
            $guaranteeMessage = $this->translator->trans('NovalnetPayments.guaranteeError.countryError');
        } elseif ('EUR' !== $currency) {
            $guaranteeMessage = $this->translator->trans('NovalnetPayments.guaranteeError.currencyError');
        } elseif ($billingAry !== $shippingAry) {
            $guaranteeMessage = $this->translator->trans('NovalnetPayments.guaranteeError.addressError');
        }

        if (!empty($guaranteeMessage)) {
            $errorMessage = sprintf($this->translator->trans('NovalnetPayments.guaranteeError.generalError'), $guaranteeMessage);
            return ['error' => $errorMessage];
        } else {
            return ['success' => true];
        }
    }

    /**
     * Add word wrap filter.
     *
     * @param $text
     * @param $length
     *
     * @return string
     */
    public function wordwrap($text, $length)
    {
        return wordwrap($text, $length, "\n", false);
    }

    /**
     * get shop language from sales context.
     *
     * @param $salesChannelContext
     *
     * @return string
     */
    public function getShopLang($salesChannelContext)
    {
        return $this->helper->getLocaleCodeFromContext($salesChannelContext->getContext());
    }
}
