<?php

namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Callback\RefundCallback;
use Twikey\Api\Exception\TwikeyException;

class RefundGateway extends BaseGateway
{
    /**
     * Read until empty
     * @link https://www.twikey.com/api/#get-credit-transfer-feed
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(RefundCallback $callback, $start_position = "", $includes = []):int
    {
        $url = "/creditor/transfer";
        foreach ($includes as $include) {
            $url .= "include=".$include."&";
        }
        $count = 0;
        $optionalHeaders = [];
        if ($start_position != "") {
            $optionalHeaders["X-RESUME-AFTER"] = $start_position;
        }
        do {
            $response = $this->request('GET', $url, ['headers' => $optionalHeaders]);
            // reset to avoid loop
            $optionalHeaders = [];
            $server_output = $this->checkResponse($response, "Retrieving credit transfer feed!");
            $refunds = json_decode($server_output);
            foreach ($refunds->Entries as $ct){
                $count++;
                $callback->handle($ct);
            }
        } while(count($refunds->Entries) > 0);
        return $count;
    }
}
