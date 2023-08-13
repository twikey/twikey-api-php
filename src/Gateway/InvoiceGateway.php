<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Callback\InvoiceCallback;
use Twikey\Api\Exception\TwikeyException;

class InvoiceGateway extends BaseGateway
{
    /**
     * @param string $json json_encode'd representation of the invoice
     * @param array|null $optionalHeaders
     * @return mixed|void
     * @link https://www.twikey.com/api/#create-invoice
     * @throws ClientExceptionInterface
     * @throws TwikeyException
     */
    public function create(string $json, ?array $optionalHeaders = [])
    {
        if (!in_array('Content-Type', $optionalHeaders)) {
            $optionalHeaders['Content-Type'] = 'application/json';
        }

        $response = $this->request('POST', "/creditor/invoice", [
            'body' => $json,
            'headers' => $optionalHeaders
        ]);
        $server_output = $this->checkResponse($response, "Creating a new invoice!");
        return json_decode($server_output);
    }

    /**
     * Note this is rate limited
     * @link https://www.twikey.com/api/#invoice-details
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
     * Read all updated invoices
     * @link https://www.twikey.com/api/#invoice-feed
     *
     * @param InvoiceCallback $callback function to be called for every updated invoice
     * @param string $start_position Optional start position
     * @return int Number of invoices updated
     * @throws ClientExceptionInterface
     * @throws TwikeyException
     */
    public function feed(InvoiceCallback $callback, string $start_position = ""): int
    {
        $url = "/creditor/invoice";
        $count = 0;
        $optionalHeaders = [];
        if ($start_position != "") {
            $optionalHeaders["X-RESUME-AFTER"] = $start_position;
        }
        do {
            $response = $this->request('GET', $url, ['headers' => $optionalHeaders]);
            // reset to avoid loop
            $optionalHeaders = [];
            $server_output = $this->checkResponse($response, "Retrieving invoice feed!");
            $json_response = json_decode($server_output);
            $invoices = $json_response->Invoices;
            $callback->start(
                $response->getHeaderLine("X-LAST"), count($invoices)
            );
            foreach ($invoices as $invoice){
                $count++;
                $callback->handle($invoice);
            }
        }
        while(count($invoices) > 0);
        return $count;
    }
}
