<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Callback\TransactionCallback;
use Twikey\Api\Exception\TwikeyException;

class TransactionGateway extends BaseGateway
{
    /**
     * @param $data
     * @return array|mixed|object
     * @throws ClientExceptionInterface
     * @throws TwikeyException
     */
    public function create($data, ?array $optionalHeaders = [])
    {
        $response = $this->request('POST', "/creditor/transaction", ['form_params' => $data, 'headers' => $optionalHeaders]);
        $server_output = $this->checkResponse($response, "Creating a new transaction!");
        return json_decode($server_output);
    }

    /**
     * Note this is rate limited
     * @throws ClientExceptionInterface
     * @throws TwikeyException
     */
    public function get($txid, $ref)
    {
        if (empty($ref)) {
            $item = "id=" . $txid;
        } else {
            $item = "ref=" . $ref;
        }

        $response = $this->request('GET', sprintf("/creditor/transaction/detail?%s", $item), []);
        $server_output = $this->checkResponse($response, "Retrieving payments!");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(TransactionCallback $callback):int
    {
        $count = 0;
        do {
            $response = $this->request('GET', "/creditor/transaction", []);
            $server_output = $this->checkResponse($response, "Retrieving transaction feed!");
            $transactions = json_decode($server_output);
            foreach ($transactions->Entries as $tx){
                $count++;
                $callback->handle($tx);
            }
        }
        while(count($transactions->Entries) > 0);
        return $count;
    }

    /**
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function cancel(?string $id, ?string $ref)
    {
        $queryPrefix = isset($id) || isset($ref) ? '?' : null;
        $queryId = isset($id) ? "id=$id" : null;
        $queryRef = isset($ref) ? sprintf("%ref=$ref", isset($id) ? '&' : null) : null;
        $response = $this->request('DELETE', sprintf('/creditor/transaction%s%s%s', $queryPrefix, $queryId, $queryRef), []);
        $server_output = $this->checkResponse($response, "Cancel a transaction!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     * @deprecated Please use the collection gateway
     */
    public function collect(int $ct)
    {
        $response = $this->request('POST', "/creditor/collect", ['form_params' => ["ct" => $ct]]);
        $server_output = $this->checkResponse($response, "Retrieving transaction feed!");
        return json_decode($server_output);
    }
}
