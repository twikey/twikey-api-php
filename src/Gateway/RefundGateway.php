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
    public function feed(RefundCallback $callback):int
    {
        $count = 0;
        do {
            $response = $this->request('GET', "/creditor/transfer", []);
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
