<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Twikey\Api\Callback\PaylinkCallback;
use Twikey\Api\TwikeyException;

class LinkGateway extends BaseGateway
{
    /**
     * @throws TwikeyException
     */
    public function create($data, $lang = 'en')
    {
        $response = $this->request('POST', "/creditor/payment/link", ['form_params' => $data], $lang);
        $server_output = $this->checkResponse($response, "Creating a new paymentlink!");
        return json_decode($server_output);
    }

    /**
     * Note this is rate limited
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function get($linkid, $ref, $lang = 'en')
    {
        if (empty($ref)) {
            $item = "id=" . $linkid;
        } else {
            $item = "ref=" . $ref;
        }
        $response = $this->request('POST', sprintf("/creditor/payment/link?%s", $item), [], $lang);
        $server_output = $this->checkResponse($response, "Verifying a paymentlink ");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(PaylinkCallback $callback,$lang = 'en')
    {
        $count = 0;
        do {
            $response = $this->request('GET', "/creditor/payment/link/feed", [], $lang);
            $server_output = $this->checkResponse($response, "Retrieving paymentlink feed!");
            $links = json_decode($server_output);
            foreach ($links as $pl){
                $count++;
                $callback->handle($pl);
            }
        }
        while(count($links) > 0);
        return $count;
    }
}
