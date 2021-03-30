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

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class RedirectController extends StorefrontController
{
    /** @var SessionInterface */
    private $SessionInterface;

    public function __construct(SessionInterface $SessionInterface)
    {
        $this->SessionInterface = $SessionInterface;
    }

    /**
     * @Route("/novalnet/request", name="frontend.action.novalnetpayment.request-action", defaults={"csrf_protected"=false}, methods={"GET"})
     */
    public function RequestAction(Request $request, SalesChannelContext $context): Response
    {

                if (!empty($this->SessionInterface->get('novalnet_redirect'))) {
            $requestData = $this->SessionInterface->get('novalnetParams');
            return $this->renderStorefront('@NovalnetPayment/redirect/index.html.twig', ['data' => $requestData, 'redirectUrl' => $request->query->get('url')]);
        } else {
            return $this->forwardToRoute('frontend.checkout.cart.page', ['csrf_protected' => false]);
        }
    }

    /**
     * @Route("/novalnet/response", name="frontend.action.novalnetpayment.response-action", defaults={"csrf_protected"=false}, methods={"GET","POST"})
     */
    public function ResponseAction(Request $request, SalesChannelContext $context): Response
    {
        $siteResponse = (array) $request->attributes;
        foreach ($siteResponse as $values) {
            $Response = $values;
        }

        //coding for fixing the samesite issue
        $sameSiteFix = !empty($Response['sess_lost']) ? $Response['sess_lost'] : '';
        if (empty($sameSiteFix) && empty($this->SessionInterface->get('novalnetParams'))) {
            header_remove('Set-Cookie');
            return $this->forwardToRoute('frontend.action.novalnetpayment.response-action', ['sess_lost' => 1]);
        }
            
        $response = (array) $request->request;
        foreach ($response as $data) {
            $novalnetResponse = $data;
        }


        $paymentToken = (!empty($novalnetResponse['inputval1']) ? $novalnetResponse['inputval1'] : (!empty($novalnetResponse['paymentToken']) ? $novalnetResponse['paymentToken'] : ''));
        return $this->forwardToRoute('payment.finalize.transaction', ['_sw_payment_token' => $paymentToken, 'response' => $novalnetResponse, 'csrf_protected' => false]);
    }
}
