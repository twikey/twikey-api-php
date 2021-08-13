<?php

namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Callback\RefundCallback;
use Twikey\Api\TwikeyException;

class RefundGateway extends BaseGateway
{

    /**
     * Read until empty
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(RefundCallback $callback, $lang = 'en')
    {
        $count = 0;
        do {
            $response = $this->request('GET', "/creditor/transfer", [], $lang);
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
