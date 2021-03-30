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

namespace Novalnet\NovalnetPayment\Util;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class PaymentMethodIdProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    public function __construct(EntityRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function getNovalnetPaymentMethodId(Context $context, $path): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $path));

        $result = $this->paymentRepository->searchIds($criteria, $context);

        if ($result->getTotal() == 0) {
            return null;
        }

        $paymentMethodIds = $result->getIds();

        return array_shift($paymentMethodIds);
    }

    public function checkCustomField(Context $context, string $customFieldName): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $customFieldName));

        $result = $this->paymentRepository->searchIds($criteria, $context);
        if ($result->getTotal() == 0) {
            return null;
        }

        $customFieldIds = $result->getIds();
        return array_shift($customFieldIds);
    }
}
