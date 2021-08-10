<?php
declare(strict_types=1);
namespace Twikey\Api;

use Exception;
use Psr\Http\Client\ClientInterface;
use Twikey\Api\Gateway\InvoiceGateway;
use Twikey\Api\Gateway\LinkGateway;
use Twikey\Api\Gateway\DocumentGateway;
use Twikey\Api\Gateway\RefundGateway;
use Twikey\Api\Gateway\TransactionGateway;
use Twikey\Api\Exception\TwikeyException;

const TWIKEY_DEBUG = false;

class Twikey
{
    const VERSION = '3.0.0';

    protected string $lang = 'en';

    public DocumentGateway $document;
    public TransactionGateway $transaction;
    public LinkGateway $link;
    public InvoiceGateway $invoice;
    public RefundGateway $refund;

    public function __construct(ClientInterface $httpClient, string $apikey, bool $testMode = false)
    {
        $endpoint = "https://api.twikey.com";
        if ($testMode) {
            $endpoint = "https://api.beta.twikey.com";
        }
        $apiKey = trim($apikey);
        $this->document = new DocumentGateway($httpClient,$endpoint, $apiKey);
        $this->transaction = new TransactionGateway($httpClient,$endpoint, $apiKey);
        $this->link = new LinkGateway($httpClient,$endpoint, $apiKey);
        $this->invoice = new InvoiceGateway($httpClient, $endpoint, $apiKey);
        $this->refund = new RefundGateway($httpClient, $endpoint, $apiKey);
    }

    /**
     * @throws TwikeyException
     */
    public static function validateSignature(string $website_key, string $documentNumber, string $status, string $token, string $signature) : bool
    {
        $payload = sprintf("%s/%s", $documentNumber, $status);
        if ($token != "") {
            $payload = sprintf("%s/%s/%s", $documentNumber, $status, $token);
        }
        $calculated = strtoupper(hash_hmac('sha256', $payload , $website_key));
        if (!hash_equals($calculated, $signature)) {
            error_log("Invalid signature : expected=" . $calculated . ' was=' . $signature, 0);
            throw new TwikeyException('Invalid signature');
        }
        return true;
    }

    /**
     * @param $queryString $_SERVER['QUERY_STRING']
     * @param $signatureHeader $_SERVER['HTTP_X_SIGNATURE']
     */
    public static function validateWebhook(string $apikey, string $queryString, string $signatureHeader) : bool
    {
        $calculated = strtoupper(hash_hmac('sha256', urldecode($queryString), $apikey));

//        error_log("Calculated: " . $calculated);
//        error_log("Given: " . $signatureHeader);
//        error_log("Message: " . $queryString);
//        error_log("Same: " . ($calculated == $signatureHeader));

        return hash_equals($calculated, $signatureHeader);
    }
}
