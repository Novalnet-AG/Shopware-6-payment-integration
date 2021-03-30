<?php
/**
*
* This script is used for NovalPaymentSnippetFile_en_GB
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
* Script : NovalPaymentSnippetFile_en_GB.php
*/

declare(strict_types=1);

namespace Novalnet\NovalnetPayment\Resources\translations\en_GB;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class NovalPaymentSnippetFile_en_GB implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'messages.en-GB';
    }

    public function getPath(): string
    {
        return __DIR__.'/messages.en-GB.json';
    }

    public function getIso(): string
    {
        return 'en-GB';
    }

    public function getAuthor(): string
    {
        return 'Novalnet AG';
    }

    public function isBase(): bool
    {
        return false;
    }
}
