<?php
declare(strict_types=1);

namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Exception\TwikeyException;

class CustomerGateway extends BaseGateway
{
    /**
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function update($data)
    {
        if (!isset($data['customerNumber'])) {
            throw new TwikeyException("A customerNumber is required");
        }
        $response = $this->request(
            'PATCH',
            \sprintf("/creditor/customer/%s", $data['customerNumber']),
            ['query' => $data]
        );
        $server_output = $this->checkResponse($response, "Update a customer!");
        return json_decode($server_output);
    }
}
