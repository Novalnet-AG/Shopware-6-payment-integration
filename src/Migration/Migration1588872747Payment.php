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

namespace Novalnet\NovalnetPayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1588872747Payment extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1588872747;
    }

    public function update(Connection $connection): void
    {
        $this->createMailEvents($connection);
        $connection->executeQuery('
            CREATE TABLE IF NOT EXISTS `novalnet_transaction_details` (
              `id` BINARY(16) NOT NULL,
              `tid` BIGINT(20) COMMENT "Novalnet Transaction Reference ID",
              `payment_type` VARCHAR(50) COMMENT "Executed Payment type of this order",
              `amount` INT(11) COMMENT "Transaction amount",
              `paid_amount` INT(11) COMMENT "Paid amount",
              `gateway_status` VARCHAR(30) COMMENT "Novalnet transaction status",
              `order_no` VARCHAR(64) COMMENT "Order ID from shop",
              `customer_no` VARCHAR(255) COMMENT "Customer Number from shop",
              `lang` VARCHAR(5) COMMENT "Order language",
              `additional_details` LONGTEXT DEFAULT NULL COMMENT "Configuration Values of repective Order",
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Novalnet Transaction History";
        ');
    }

    public function createMailEvents(Connection $connection): void
    {
        $orderCofirmationTemplateId = Uuid::randomBytes();
        $mailTypeId = $this->getMailTypeMapping()['novalnet_order_confirmation_mail']['id'];
        $deLangId = $enLangId = '';
        
        if ($this->fetchLanguageId('de-DE', $connection) != '') {
            $deLangId = Uuid::fromBytesToHex($this->fetchLanguageId('de-DE', $connection));
        }
        
        if ($this->fetchLanguageId('en-GB', $connection) != '') {
            $enLangId = Uuid::fromBytesToHex($this->fetchLanguageId('en-GB', $connection));
        }
            
        if (!$this->checkMailType($connection)) {
            $connection->insert(
                'mail_template_type',
                [
                'id' => Uuid::fromHexToBytes($mailTypeId),
                'technical_name' => 'novalnet_order_confirmation_mail',
                'available_entities' => json_encode($this->getMailTypeMapping()['novalnet_order_confirmation_mail']['availableEntities']),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            $connection->insert(
                'mail_template',
                [
                    'id' => $orderCofirmationTemplateId,
                    'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                    'system_default' => 1,
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            if ($enLangId != '') {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $orderCofirmationTemplateId,
                        'language_id' => Uuid::fromHexToBytes($enLangId),
                        'subject' => 'Order confirmation',
                        'description' => 'Novalnet Order confirmation',
                        'sender_name' => '{{ salesChannel.name }}',
                        'content_html' => $this->getHtmlTemplateEn(),
                        'content_plain' => $this->getPlainTemplateEn(),
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
                
                $connection->insert(
                    'mail_template_type_translation',
                    [
                        'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                        'language_id' => Uuid::fromHexToBytes($enLangId),
                        'name' => 'Order confirmation',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
            
            if ($deLangId != '') {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $orderCofirmationTemplateId,
                        'language_id' => Uuid::fromHexToBytes($deLangId),
                        'subject' => 'Bestellbestätigung',
                        'description' => 'Novalnet Bestellbestätigung',
                        'sender_name' => '{{ salesChannel.name }}',
                        'content_html' => $this->getHtmlTemplateDe(),
                        'content_plain' => $this->getPlainTemplateDe(),
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );

                $connection->insert(
                    'mail_template_type_translation',
                    [
                        'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                        'language_id' => Uuid::fromHexToBytes($deLangId),
                        'name' => 'Bestellbestätigung',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
            
            if (!in_array(Defaults::LANGUAGE_SYSTEM, [$enLangId, $deLangId])) {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $orderCofirmationTemplateId,
                        'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                        'subject' => 'Order confirmation',
                        'description' => 'Novalnet Order confirmation',
                        'sender_name' => '{{ salesChannel.name }}',
                        'content_html' => $this->getHtmlTemplateEn(),
                        'content_plain' => $this->getPlainTemplateEn(),
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
                
                $connection->insert(
                    'mail_template_type_translation',
                    [
                        'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                        'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                        'name' => 'Order confirmation',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
        }
    }

    private function getMailTypeMapping(): array
    {
        return[
            'novalnet_order_confirmation_mail' => [
                'id' => Uuid::randomHex(),
                'name' => 'Order confirmation',
                'nameDe' => 'Bestellbestätigung',
                'availableEntities' => ['order' => 'order', 'salesChannel' => 'sales_channel'],
            ],
        ];
    }

    private function getHtmlTemplateEn(): string
    {
        return '<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
    
{% set currencyIsoCode = order.currency.isoCode %}
{{order.orderCustomer.salutation.letterName }} {{order.orderCustomer.firstName}} {{order.orderCustomer.lastName}},<br>
<br>
Thank you for your order at {{ salesChannel.name }} (Number: {{order.orderNumber}}) on {{ order.orderDateTime|date }}.<br>
<br>
<strong>Information on your order:</strong><br>
<br>

<table width="80%" border="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
    <tr>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Pos.</strong></td>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Description</strong></td>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Quantities</strong></td>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Price</strong></td>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Total</strong></td>
    </tr>

    {% for lineItem in order.lineItems %}
    <tr>
        <td style="border-bottom:1px solid #cccccc;">{{ loop.index }} </td>
        <td style="border-bottom:1px solid #cccccc;">
          {{ lineItem.label|u.wordwrap(80) }}<br>
          Art. No.: {{ lineItem.payload.productNumber|u.wordwrap(80) }}
        </td>
        <td style="border-bottom:1px solid #cccccc;">{{ lineItem.quantity }}</td>
        <td style="border-bottom:1px solid #cccccc;">{{ lineItem.unitPrice|currency(currencyIsoCode) }}</td>
        <td style="border-bottom:1px solid #cccccc;">{{ lineItem.totalPrice|currency(currencyIsoCode) }}</td>
    </tr>
    {% endfor %}
</table>

{% set delivery =order.deliveries.first %}
<p>
    <br>
    <br>
    Shipping costs: {{order.deliveries.first.shippingCosts.totalPrice|currency(currencyIsoCode) }}<br>
    Net total: {{ order.amountNet|currency(currencyIsoCode) }}<br>
        {% for calculatedTax in order.price.calculatedTaxes %}
			{% if order.taxStatus is same as(\'net\') %}plus{% else %}including{% endif %} {{ calculatedTax.taxRate }}% VAT. {{ calculatedTax.tax|currency(currencyIsoCode) }}<br>
		{% endfor %}
        <strong>Total gross: {{ order.amountTotal|currency(currencyIsoCode) }}</strong><br>
    <br>
    
    <strong>Selected payment type:</strong> {{ order.transactions.first.paymentMethod.name }}<br>
    {{ order.transactions.first.paymentMethod.description }}<br>
    <br>
    
    <strong>Selected shipping type:</strong> {{ delivery.shippingMethod.name }}<br>
    {{ delivery.shippingMethod.description }}<br>
    <br>
    
    {% set billingAddress = order.addresses.get(order.billingAddressId) %}
    <strong>Billing address:</strong><br>
    {{ billingAddress.company }}<br>
    {{ billingAddress.firstName }} {{ billingAddress.lastName }}<br>
    {{ billingAddress.street }} <br>
    {{ billingAddress.zipcode }} {{ billingAddress.city }}<br>
    {{ billingAddress.country.name }}<br>
    <br>
    
    <strong>Shipping address:</strong><br>
    {{ delivery.shippingOrderAddress.company }}<br>
    {{ delivery.shippingOrderAddress.firstName }} {{ delivery.shippingOrderAddress.lastName }}<br>
    {{ delivery.shippingOrderAddress.street }} <br>
    {{ delivery.shippingOrderAddress.zipcode}} {{ delivery.shippingOrderAddress.city }}<br>
    {{ delivery.shippingOrderAddress.country.name }}<br>
    <br>
    {% if billingAddress.vatId %}

        Your VAT-ID: {{ billingAddress.vatId }}
        In case of a successful order and if you are based in one of the EU countries, you will receive your goods exempt from turnover tax.<br>
    {% endif %}
    <br>
    
    <strong>Comments:</strong><br>
    {{ note|replace({"/ ": "<br>"}) | raw }}
    <br>
    
    If you have any questions, do not hesitate to contact us.

</p>
<br>
</div>';
    }

    private function getPlainTemplateEn(): string
    {
        return '{% set currencyIsoCode = order.currency.isoCode %}
{{ order.orderCustomer.salutation.letterName }} {{order.orderCustomer.firstName}} {{order.orderCustomer.lastName}},

Thank you for your order at {{ salesChannel.name }} (Number: {{order.orderNumber}}) on {{ order.orderDateTime|date }}.

Information on your order:

Pos.   Art.No.			Description			Quantities			Price			Total 

{% for lineItem in order.lineItems %}
{{ loop.index }}      {{ lineItem.payload.productNumber|u.wordwrap(80) }}				{{ lineItem.label|u.wordwrap(80) }}			{{ lineItem.quantity }}			{{ lineItem.unitPrice|currency(currencyIsoCode) }}			{{ lineItem.totalPrice|currency(currencyIsoCode) }}
{% endfor %}

{% set delivery = order.deliveries.first %}

Shipping costs: {{order.deliveries.first.shippingCosts.totalPrice|currency(currencyIsoCode) }}
Net total: {{ order.amountNet|currency(currencyIsoCode) }}
	{% for calculatedTax in order.price.calculatedTaxes %}
        {% if order.taxStatus is same as(\'net\') %}plus{% else %}including{% endif %} {{ calculatedTax.taxRate }}% VAT. {{ calculatedTax.tax|currency(currencyIsoCode) }}<br>
     {% endfor %}
	Total gross: {{ order.amountTotal|currency(currencyIsoCode) }}

Selected payment type: {{ order.transactions.first.paymentMethod.name }}
{{ order.transactions.first.paymentMethod.description }}

Selected shipping type: {{ delivery.shippingMethod.name }}
{{ delivery.shippingMethod.description }}

{% set billingAddress = order.addresses.get(order.billingAddressId) %}
Billing address:
{{ billingAddress.company }}
{{ billingAddress.firstName }} {{ billingAddress.lastName }}
{{ billingAddress.street }}
{{ billingAddress.zipcode }} {{ billingAddress.city }}
{{ billingAddress.country.name }}

Shipping address:
{{ delivery.shippingOrderAddress.company }}
{{ delivery.shippingOrderAddress.firstName }} {{ delivery.shippingOrderAddress.lastName }}
{{ delivery.shippingOrderAddress.street }} 
{{ delivery.shippingOrderAddress.zipcode}} {{ delivery.shippingOrderAddress.city }}
{{ delivery.shippingOrderAddress.country.name }}

{% if billingAddress.vatId %}
Your VAT-ID: {{ billingAddress.vatId }}
In case of a successful order and if you are based in one of the EU countries, you will receive your goods exempt from turnover tax.
{% endif %}

Comments:
{{ note|replace({"/ ": "<br>"}) | raw }}

If you have any questions, do not hesitate to contact us.

';
    }

    private function getHtmlTemplateDe(): string
    {
        return '<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
    
{% set currencyIsoCode = order.currency.isoCode %}
{{order.orderCustomer.salutation.letterName }} {{order.orderCustomer.firstName}} {{order.orderCustomer.lastName}},<br>
<br>
vielen Dank für Ihre Bestellung im {{ salesChannel.name }} (Nummer: {{order.orderNumber}}) am {{ order.orderDateTime|date }}.<br>
<br>
<strong>Informationen zu Ihrer Bestellung:</strong><br>
<br>

<table width="80%" border="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
    <tr>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Pos.</strong></td>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Bezeichnung</strong></td>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Menge</strong></td>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Preis</strong></td>
        <td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Summe</strong></td>
    </tr>

    {% for lineItem in order.lineItems %}
    <tr>
        <td style="border-bottom:1px solid #cccccc;">{{ loop.index }} </td>
        <td style="border-bottom:1px solid #cccccc;">
          {{ lineItem.label|u.wordwrap(80) }}<br>
          Artikel-Nr: {{ lineItem.payload.productNumber|u.wordwrap(80) }}
        </td>
        <td style="border-bottom:1px solid #cccccc;">{{ lineItem.quantity }}</td>
        <td style="border-bottom:1px solid #cccccc;">{{ lineItem.unitPrice|currency(currencyIsoCode) }}</td>
        <td style="border-bottom:1px solid #cccccc;">{{ lineItem.totalPrice|currency(currencyIsoCode) }}</td>
    </tr>
    {% endfor %}
</table>

{% set delivery =order.deliveries.first %}
<p>
    <br>
    <br>
    Versandkosten: {{order.deliveries.first.shippingCosts.totalPrice|currency(currencyIsoCode) }}<br>
    Gesamtkosten Netto: {{ order.amountNet|currency(currencyIsoCode) }}<br>
        {% for calculatedTax in order.price.calculatedTaxes %}
			{% if order.taxStatus is same as(\'net\') %}plus{% else %}including{% endif %} {{ calculatedTax.taxRate }}% VAT. {{ calculatedTax.tax|currency(currencyIsoCode) }}<br>
		{% endfor %}
        <strong>Gesamtkosten Brutto: {{ order.amountTotal|currency(currencyIsoCode) }}</strong><br>
    <br>
    
    <strong>Gewählte Zahlungsart:</strong> {{ order.transactions.first.paymentMethod.name }}<br>
    {{ order.transactions.first.paymentMethod.description }}<br>
    <br>
    
    <strong>Gewählte Versandtart:</strong> {{ delivery.shippingMethod.name }}<br>
    {{ delivery.shippingMethod.description }}<br>
    <br>
    
    {% set billingAddress = order.addresses.get(order.billingAddressId) %}
    <strong>Rechnungsaddresse:</strong><br>
    {{ billingAddress.company }}<br>
    {{ billingAddress.firstName }} {{ billingAddress.lastName }}<br>
    {{ billingAddress.street }} <br>
    {{ billingAddress.zipcode }} {{ billingAddress.city }}<br>
    {{ billingAddress.country.name }}<br>
    <br>
    
    <strong>Lieferadresse:</strong><br>
    {{ delivery.shippingOrderAddress.company }}<br>
    {{ delivery.shippingOrderAddress.firstName }} {{ delivery.shippingOrderAddress.lastName }}<br>
    {{ delivery.shippingOrderAddress.street }} <br>
    {{ delivery.shippingOrderAddress.zipcode}} {{ delivery.shippingOrderAddress.city }}<br>
    {{ delivery.shippingOrderAddress.country.name }}<br>
    <br>
    
    {% if billingAddress.vatId %}
        Ihre Umsatzsteuer-ID: {{ billingAddress.vatId }}
        Bei erfolgreicher Prüfung und sofern Sie aus dem EU-Ausland
        bestellen, erhalten Sie Ihre Ware umsatzsteuerbefreit. <br>
    {% endif %}
    <br>
    
    <strong>Kommentare:</strong><br>
    {{ note|replace({"/ ": "<br>"}) | raw }}
    <br>
    
    Für Rückfragen stehen wir Ihnen jederzeit gerne zur Verfügung.

</p>
<br>
</div>';
    }

    private function getPlainTemplateDe(): string
    {
        return '{% set currencyIsoCode = order.currency.isoCode %}
{{order.orderCustomer.salutation.letterName }} {{order.orderCustomer.firstName}} {{order.orderCustomer.lastName}},

vielen Dank für Ihre Bestellung im {{ salesChannel.name }} (Nummer: {{order.orderNumber}}) am {{ order.orderDateTime|date }}.

Informationen zu Ihrer Bestellung:

Pos.   Artikel-Nr.			Beschreibung			Menge			Preis			Summe
{% for lineItem in order.lineItems %}
{{ loop.index }}     {{ lineItem.payload.productNumber|u.wordwrap(80) }}				{{ lineItem.label|u.wordwrap(80) }}			{{ lineItem.quantity }}			{{ lineItem.unitPrice|currency(currencyIsoCode) }}			{{ lineItem.totalPrice|currency(currencyIsoCode) }}
{% endfor %}

{% set delivery =order.deliveries.first %}

Versandtkosten: {{order.deliveries.first.shippingCosts.totalPrice|currency(currencyIsoCode) }}
Gesamtkosten Netto: {{ order.amountNet|currency(currencyIsoCode) }}
	 {% for calculatedTax in order.price.calculatedTaxes %}
        {% if order.taxStatus is same as(\'net\') %}plus{% else %}including{% endif %} {{ calculatedTax.taxRate }}% VAT. {{ calculatedTax.tax|currency(currencyIsoCode) }}<br>
     {% endfor %}
	Gesamtkosten Brutto: {{ order.amountTotal|currency(currencyIsoCode) }}

Gewählte Zahlungsart: {{ order.transactions.first.paymentMethod.name }}
{{ order.transactions.first.paymentMethod.description }}

Gewählte Versandtart: {{ delivery.shippingMethod.name }}
{{ delivery.shippingMethod.description }}

{% set billingAddress = order.addresses.get(order.billingAddressId) %}
Rechnungsadresse:
{{ billingAddress.company }}
{{ billingAddress.firstName }} {{ billingAddress.lastName }}
{{ billingAddress.street }}
{{ billingAddress.zipcode }} {{ billingAddress.city }}
{{ billingAddress.country.name }}

Lieferadresse:
{{ delivery.shippingOrderAddress.company }}
{{ delivery.shippingOrderAddress.firstName }} {{ delivery.shippingOrderAddress.lastName }}
{{ delivery.shippingOrderAddress.street }} 
{{ delivery.shippingOrderAddress.zipcode}} {{ delivery.shippingOrderAddress.city }}
{{ delivery.shippingOrderAddress.country.name }}

{% if billingAddress.vatId %}
Ihre Umsatzsteuer-ID: {{ billingAddress.vatId }}
Bei erfolgreicher Prüfung und sofern Sie aus dem EU-Ausland
bestellen, erhalten Sie Ihre Ware umsatzsteuerbefreit.
{% endif %}

Kommentare:
{{ note|replace({"/ ": "<br>"}) | raw }}
    
Für Rückfragen stehen wir Ihnen jederzeit gerne zur Verfügung.

';
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function fetchLanguageId(string $code, Connection $connection): ?string
    {
        /** @var string|null $langId */
        $langId = $connection->fetchColumn('
        SELECT `language`.`id` FROM `language` INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id` WHERE `code` = :code LIMIT 1
        ', ['code' => $code]);

        if (!$langId) {
            return null;
        }

        return $langId;
    }

    private function checkMailType(Connection $connection): ?bool
    {
        $mailTypeId = $connection->fetchColumn('
        SELECT `id` FROM `mail_template_type` WHERE `technical_name` = :technical_name LIMIT 1
        ', ['technical_name' => 'novalnet_order_confirmation_mail']);

        if (!$mailTypeId) {
            return false;
        }

        return true;
    }
}
