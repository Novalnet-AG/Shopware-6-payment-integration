<?php
/**
*
* This script is used for NovalPaymentSnippetFile_de_DE
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
* Script : NovalPaymentSnippetFile_de_DE.php
*/

declare(strict_types=1);

namespace Novalnet\NovalnetPayment\Resources\translations\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class NovalPaymentSnippetFile_de_DE implements SnippetFileInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'messages.de-DE';
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return __DIR__.'/messages.de-DE.json';
    }

    /**
     * {@inheritdoc}
     */
    public function getIso(): string
    {
        return 'de-DE';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthor(): string
    {
        return 'Novalnet AG';
    }

    /**
     * {@inheritdoc}
     */
    public function isBase(): bool
    {
        return false;
    }
}
