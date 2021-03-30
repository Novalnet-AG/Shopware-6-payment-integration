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

use Novalnet\NovalnetPayment\Helper\NovalnetHelper;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class ApiController extends AbstractController
{
    /**
     * @var NovalnetHelper
     */
    private $helper;

    public function __construct(NovalnetHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @Route("/api/v{version}/_action/noval-payment/validate-api-credentials", name="api.action.noval.payment.validate.api.credentials", methods={"GET"})
     */
    public function validateApiCredentials(Request $request): JsonResponse
    {
        $clientId = $request->get('clientId');
        if (!empty(trim($clientId))) {
            $language = explode('_', $request->getPreferredLanguage());
            $requestParams = [
                            'hash' => $clientId,
                            'lang' => $language[0],
                            ];
            $novalnetResponse = $this->helper->curlRequest($requestParams, 'https://payport.novalnet.de/autoconfig', false);
            $resultArr = json_decode($novalnetResponse, true);
            
            if (!empty($resultArr['config_result'])) {
                $resultArr['config_result'] = utf8_decode($resultArr['config_result']);
            }
                
            if (!empty($resultArr['tariff'])) {
                foreach ($resultArr['tariff'] as $key => $values) {
                    $tariffs[] = [
                           'id' => $key,
                           'name' => utf8_decode($values['name']),
                        ];
                }
                return new JsonResponse(['serverResponse' => $resultArr, 'tariffResponse' => $tariffs]);
            } elseif (!empty($resultArr['config_result'])) {
                return new JsonResponse(['serverResponse' => $resultArr]);
            } else {
                return new JsonResponse(['serverResponse' => '']);
            }
        } else {
            return new JsonResponse(['serverResponse' => '']);
        }
    }
}
