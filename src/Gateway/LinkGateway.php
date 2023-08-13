<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Callback\PaylinkCallback;
use Twikey\Api\Exception\TwikeyException;

class LinkGateway extends BaseGateway
{
    /**
     * Create a payment link for an affiliated customer (via email) or via a name (in which case no customer is linked).
     * @link https://www.twikey.com/api/#create-paymentlink
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function create($data, ?array $optionalHeaders = [])
    {
        $response = $this->request('POST', "/creditor/payment/link", ['form_params' => $data, 'headers' => $optionalHeaders]);
        $server_output = $this->checkResponse($response, "Creating a new paymentlink");
        return json_decode($server_output);
    }

    /**
     * Get status of a payment link
     * Note this is rate limited
     * @link https://www.twikey.com/api/#status-paymentlink
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function get(int $linkid, string $ref = null)
    {
        if (empty($ref)) {
            $item = "id=" . $linkid;
        } else {
            $item = "ref=" . urlencode($ref);
        }
        $response = $this->request('GET', sprintf("/creditor/payment/link?%s", $item), []);
        $server_output = $this->checkResponse($response, "Verifying a paymentlink ");
        return json_decode($server_output);
    }

    /**
     * Get payment link feed since the last retrieval
     * and reads it until empty
     *
     * @link https://www.twikey.com/api/#paymentlink-feed
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(PaylinkCallback $callback, $includes = []): int
    {
        $url = "/creditor/payment/link/feed?";
        foreach ($includes as $include) {
            $url .= "include=".$include."&";
        }
        $count = 0;
        do {
            $response = $this->request('GET', $url, []);
            $server_output = $this->checkResponse($response, "Retrieving paymentlink feed");
            $json = json_decode($server_output);
            $links = $json->Links;
            foreach ($links as $pl){
                $count++;
                $callback->handle($pl);
            }
        }
        while(count($links) > 0);
        return $count;
    }

    /**
     * Refund the full or partial amount of a payment link.
     *
     * @link https://www.twikey.com/api/#refund-paymentlink
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function refund(int $id, string $msg, float $amount = 0)
    {
        $data = array(
            "id" => $id, // see Settings > Profile to use
            "msg" => $msg,
        );
        if($amount > 0){
            $data["amount"] = $amount;
        }
        $response = $this->request('POST', "/creditor/payment/link/refund", ['form_params' => $data]);
        $server_output = $this->checkResponse($response, "Refunding a paymentlink");
        return json_decode($server_output);
    }

    /**
     * Remove a payment link.
     *
     * @link https://www.twikey.com/api/#remove-paymentlink
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function remove(int $id)
    {
        $response = $this->request('DELETE', sprintf("/creditor/payment/link?id=%d", $id), []);
        $server_output = $this->checkResponse($response, "Remove a paymentlink");
        return json_decode($server_output);
    }
}
