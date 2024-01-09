<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Callback\DocumentCallback;
use Twikey\Api\Exception\TwikeyException;

class DocumentGateway extends BaseGateway
{
    /**
     * Invite a customer to sign a document (either within 6 months or now)
     *
     * @link https://www.twikey.com/api/#invite-a-customer
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function create($data)
    {
        $response = $this->request('POST', '/creditor/invite', ['form_params' => $data]);
        $server_output = $this->checkResponse($response, "Creating a new mandate!");
        return json_decode($server_output);
    }

    /**
     * Interactively sign a document (ie. customer should sign it now)
     *
     * @link https://www.twikey.com/api/#sign-a-mandate
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function sign($data)
    {
        $response = $this->request('POST', '/creditor/sign', ['form_params' => $data]);
        $server_output = $this->checkResponse($response, "Signing a mandate!");
        return json_decode($server_output);
    }

    /**
     * Returns a List of all updated mandates (new, changed or cancelled) since the last call.
     * From the moment there are changes (eg. a new contract/mandate or an update of an existing contract) this call provides all related information to the creditor.
     * The service is initiated by the creditor and provides all MRI information (and extra metadata) to the creditor.
     * This call can either be triggered by a callback once a change was made or periodically when no callback can be made.
     *
     * @link https://www.twikey.com/api/#mandate-feed
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(DocumentCallback $callback, $start_position="", $includes = ["id","mandate","person"]): int
    {
        $url = "/creditor/mandate?";
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
            $server_output = $this->checkResponse($response, "Retrieving mandate feed!");
            $updates = json_decode($server_output);
            $callback->start(
                $response->getHeaderLine("X-LAST"), count($updates->Messages)
            );
            foreach ($updates->Messages as $update) {
                $isUpdate = isset($update->AmdmntRsn);
                $isCancel = isset($update->CxlRsn);
                $count++;
                //print_r($update);
                if (!$isUpdate && !$isCancel) {
                    // new mandate
                    $callback->handleNew($update->Mndt,$update->EvtTime);
                } else if ($isUpdate) {
                    // handle update
                    $callback->handleUpdate($update->OrgnlMndtId,$update->Mndt,$update->AmdmntRsn,$update->EvtTime);
                } else if ($isCancel) {
                    // handle cancel
                    $Mndt = (property_exists($update,'Mndt') ? $update->Mndt : false);
                    $callback->handleCancel($update->OrgnlMndtId,$Mndt,$update->CxlRsn,$update->EvtTime);
                }
            }
        } while(count($updates->Messages) > 0);
        return $count;
    }

    /**
     * Cancel an existing document
     *
     * @link https://www.twikey.com/api/#cancel-a-mandate
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function cancel(string $mndtId,string $rsn, $notify = false)
    {
        $response = $this->request('DELETE', sprintf("/creditor/mandate?mndtId=%s&rsn=%s&notify=%s", $mndtId, $rsn, $notify), []);
        $server_output = $this->checkResponse($response, "Cancel a mandate!");
        return json_decode($server_output);
    }

    /**
     * Retrieve details of a specific mandate.
     * Since the structure of the mandate is the same as in the update feed
     * but doesn't include details about state, 2 extra headers are added.
     *
     * Note: Rate limits apply, though this is perfect for one-offs, for updates we recommend using the feed (see above).
     *
     * @link https://www.twikey.com/api/#fetch-mandate-details
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function get(string $mndtId, bool $force = false)
    {
        $response = $this->request('GET', sprintf("/creditor/mandate/detail?mndtId=%s&force=%s", $mndtId, $force), []);
        $server_output = $this->checkResponse($response, "Get mandate details!");
        $json_response = json_decode($server_output);
        if ($json_response->Mndt
            && ($response->getHeader('X-STATE') || $response->getHeader('X-COLLECTABLE'))
        ) {
            $json_response->Mndt->State = current($response->getHeader('X-STATE'));
            $json_response->Mndt->Collectable = current($response->getHeader('X-COLLECTABLE')) === 'true';
        }
        return $json_response;
    }

    /**
     * Update a document with specific information
     *
     * @link https://www.twikey.com/api/#update-mandate-details
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function update(array $data)
    {
        $response = $this->request('POST', "/creditor/mandate/update", ['form_params' => $data]);
        $server_output = $this->checkResponse($response, "Update a mandate!");
        return json_decode($server_output);
    }

    /**
     * You may want to give your customer access to the mandate details without actually requiring him to get a Twikey account.
     * You can do this by using this call. This call returns a url that you can redirect the user to for a particular mandate.
     *
     * @link https://www.twikey.com/api/#customer-access
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function customeraccess(string $mndtId)
    {
        $response = $this->request('POST',  sprintf("/creditor/customeraccess?mndtId=%s", $mndtId), []);
        $server_output = $this->checkResponse($response, "Customeraccess for a mandate!");
        return json_decode($server_output);
    }

    /**
     * Retrieve pdf of a specific mandate.
     *
     * @link https://www.twikey.com/api/#retrieve-pdf
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function getPdf(string $mndtId)
    {
        $response = $this->request('GET', sprintf("/creditor/mandate/pdf?mndtId=%s", $mndtId), []);
        $server_output = $this->checkResponse($response, "Get document pdf!");
        return $server_output;
    }
}
