<?php
declare(strict_types=1);

namespace Twikey\Api;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Twikey\Api\Exception\TwikeyException;
use Twikey\Api\Gateway\CollectionGateway;
use Twikey\Api\Gateway\CustomerGateway;
use Twikey\Api\Gateway\DocumentGateway;
use Twikey\Api\Gateway\InvoiceGateway;
use Twikey\Api\Gateway\LinkGateway;
use Twikey\Api\Gateway\RefundGateway;
use Twikey\Api\Gateway\TransactionGateway;
use Twikey\Api\Gateway\SubscriptionGateway;

const TWIKEY_DEBUG = false;

class Twikey
{
    const VERSION = '0.4.1';

    private string $lang = 'en';
    private string $endpoint;
    private string $user_agent;
    private string $salt = 'own';
    private string $api_key;
    private ?string $priv_key;
    private int $lastLogin = 0;
    private ?string $apitoken = null;

    public DocumentGateway $document;
    public TransactionGateway $transaction;
    public LinkGateway $link;
    public InvoiceGateway $invoice;
    public RefundGateway $refund;
    public CollectionGateway $collection;
    public CustomerGateway $customer;
    public SubscriptionGateway $subscription;

    /**
     * @var ClientInterface
     */
    private ClientInterface $http_client;

    public function __construct(ClientInterface $http_client, string $api_key,
                                string $base_url = "https://api.twikey.com",
                                string $user_agent = "twikey-php/v" . Twikey::VERSION,
                                string $priv_key = "")
    {
        $this->http_client = $http_client;
        $this->endpoint = $base_url;
        $this->api_key = trim($api_key);
        $this->priv_key = trim($priv_key);
        $this->user_agent = trim($user_agent);
        $this->document = new DocumentGateway($this);
        $this->transaction = new TransactionGateway($this);
        $this->link = new LinkGateway($this);
        $this->invoice = new InvoiceGateway($this);
        $this->refund = new RefundGateway($this);
        $this->collection = new CollectionGateway($this);
        $this->customer = new CustomerGateway($this);
        $this->subscription = new SubscriptionGateway($this);
    }

    /**
     * @throws TwikeyException
     */
    public static function validateSignature(string $website_key, string $documentNumber, string $status, string $token, string $signature): bool
    {
        $payload = sprintf("%s/%s", $documentNumber, $status);
        if ($token != "") {
            $payload = sprintf("%s/%s/%s", $documentNumber, $status, $token);
        }
        $calculated = strtoupper(hash_hmac('sha256', $payload, $website_key));
        if (!hash_equals($calculated, $signature)) {
            error_log("Invalid signature : expected=" . $calculated . ' was=' . $signature, 0);
            throw new TwikeyException('Invalid signature','err_not_authorised');
        }
        return true;
    }

    /**
     * @param $queryString $_SERVER['QUERY_STRING']
     * @param $signatureHeader $_SERVER['HTTP_X_SIGNATURE']
     */
    public static function validateWebhook(string $apikey, string $queryString, string $signatureHeader): bool
    {
        $calculated = strtoupper(hash_hmac('sha256', urldecode($queryString), $apikey));
        return hash_equals($calculated, $signatureHeader);
    }


    public function ping() : bool
    {
        try
        {
            $this->refreshTokenIfRequired();
            return true;
        }
        catch (Exception|ClientExceptionInterface $e){
            return false;
        }
    }

    /**
     * Refreshes login token if required
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function refreshTokenIfRequired($force = false) : string
    {
        if (!$force && (time() - $this->lastLogin) < 82800 /*23 h*/) {
            return $this->apitoken;
        }

        $options['headers'] = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            "User-Agent" => $this->user_agent,
            "Accept-Language" => $this->lang
        ];
        $options['form_params'] = [
            "apiToken" => $this->api_key
        ];
        if ($this->priv_key != "") {
            $otp = $this->calcOtp($this->priv_key);
            $options['form_params']["otp"] = $otp;
        }

        $response = $this->http_client->request('POST', $this->endpoint . '/creditor', $options);
        if (count($response->getHeader("Authorization")) == 1) {
            $this->apitoken = $response->getHeader("Authorization")[0];
            $this->lastLogin = time();
            return $this->apitoken;
        } else if (count($response->getHeader("Apierror")) == 1) {
            $this->apitoken = "";
            $this->lastLogin = 0;
            throw new TwikeyException($response->getHeader("Apierror")[0]);
        } else {
            throw new TwikeyException("General Twikey exception : " . $response->getReasonPhrase());
        }
    }

    /**
     * Explicit logout
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function logout()
    {
        $options['headers'] = [
            "User-Agent" => $this->user_agent,
            "Accept-Language" => $this->lang,
            "Authorization" => $this->apitoken
        ];
        $response = $this->http_client->request('GET', $this->endpoint . '/creditor', $options);
        if ($response->getStatusCode() >= 400) {
            throw new TwikeyException($response->getReasonPhrase());
        }
    }

    private function calcOtp ($priv_key) : string {
        $secret = $this->salt . hex2bin($priv_key);
        $len=8;
        $ctr = (int)floor(time() / 30);

        $binctr = pack ('NNC*', $ctr>>32, $ctr & 0xFFFFFFFF);
        $hash = hash_hmac ("sha256", $binctr, $secret);
        // This is where hashing stops and truncation begins
        $ofs = 2*hexdec (substr ($hash, 39, 1));
        $int = hexdec (substr ($hash, $ofs, 8)) & 0x7FFFFFFF;
        $pin = substr ("".$int, -$len);
        return str_pad ($pin, $len, "0", STR_PAD_LEFT);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws TwikeyException
     */
    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        $fulluri = sprintf("%s%s", $this->endpoint, $uri);
        $headers = $options['headers'] ?? [];
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            "User-Agent" => $this->user_agent,
            "Accept-Language" => $this->lang,
            "Authorization" => $this->refreshTokenIfRequired()
        ], $headers);
        $options['headers'] = $headers;
        return $this->http_client->request($method, $fulluri, $options);
    }

    /**
     * @throws TwikeyException
     */
    public function checkResponse($response, $context = "No context") : ?string
    {
        if ($response) {
            $http_code = $response->getStatusCode();
            $server_output = (string)$response->getBody();
            if ($http_code == 400) { // normal user error
                try {
                    $jsonError = json_decode($server_output);
                    $twikeyCode = $jsonError->code;
                    $translatedError = $jsonError->message;
                    error_log(sprintf("%s : Error = %s: %s [%d]", $context, $twikeyCode, $translatedError, $http_code), 0);
                } catch (Exception $e) {
                    $translatedError = "General error";
                    error_log(sprintf("%s : Error = %s [%d]", $context, $server_output, $http_code), 0);
                }
                throw new TwikeyException($translatedError);
            } else if ($http_code > 400) {
                error_log(sprintf("%s : Error = %s (%s)", $context, $server_output, $this->endpoint), 0);
                throw new TwikeyException("General error");
            }
            if (TWIKEY_DEBUG) {
                error_log(sprintf("Response %s : %s", $context, $server_output), 0);
            }
            return $server_output;
        }
        error_log(sprintf("Weird response %s : %s", $context, $response), 0);
        return null;
    }
}
