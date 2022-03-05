<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Callback\InvoiceCallback;
use Twikey\Api\Exception\TwikeyException;

class InvoiceGateway extends BaseGateway
{
    /**
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function create($data, ?array $optionalHeaders = [])
    {
        $response = $this->request('POST', "/creditor/invoice", ['form_params' => $data, 'headers' => $optionalHeaders]);
        $server_output = $this->checkResponse($response, "Creating a new invoice!");
        return json_decode($server_output);
    }

    /**
     * Note this is rate limited
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function get($id)
    {
        $response = $this->request('GET', sprintf("/creditor/invoice/%s", $id), []);
        $server_output = $this->checkResponse($response, "Verifying a invoice ");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(InvoiceCallback $callback): int
    {
        $count = 0;
        do {
            $response = $this->request('GET', "/creditor/invoice", []);
            $server_output = $this->checkResponse($response, "Retrieving invoice feed!");
            $invoices = json_decode($server_output);
            foreach ($invoices as $invoice){
                $count++;
                $callback->handle($invoice);
            }
        }
        while(count($invoices) > 0);
        return $count;
    }
}
