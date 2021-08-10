<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Twikey\Api\Callback\DocumentCallback;
use Twikey\Api\TwikeyException;

class DocumentGateway extends BaseGateway
{

    /**
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function create($data, $lang = 'en')
    {
        $response = $this->request('POST', '/creditor/invite', ['form_params' => $data], $lang);
        $server_output = $this->checkResponse($response, "Creating a new mandate!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException     *
     * @throws ClientExceptionInterface
     */
    public function update($data, $lang = 'en')
    {
        $response = $this->request('POST', "/creditor/mandate/update", ['form_params' => $data], $lang);
        $server_output = $this->checkResponse($response, "Update a mandate!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function cancel($mndtId, $rsn, $lang = 'en')
    {
        $response = $this->request('DELETE', sprintf("/creditor/mandate?mndtId=%s&rsn=%s", $mndtId, $rsn), [], $lang);
        $server_output = $this->checkResponse($response, "Cancel a mandate!");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(DocumentCallback $callback, $lang = 'en')
    {
        do {
            $response = $this->request('GET', "/creditor/mandate", [], $lang);
            $server_output = $this->checkResponse($response, "Retrieving mandate feed!");
            $updates = json_decode($server_output);
            if ($callback != null) {
                foreach ($updates->Messages as $update) {
                    $isUpdate = isset($update->AmdmntRsn);
                    $isCancel = isset($update->CxlRsn);

                    //print_r($update);
                    if (!$isUpdate && !$isCancel) {
                        // new mandate
                        $callback->handleNew($update);
                    } else if ($isUpdate) {
                        // handle update
                        $callback->handleUpdate($update);
                    } else if ($isCancel) {
                        // handle cancel
                        $callback->handleCancel($update);
                    }
                }
            }
        } while(count($updates->Messages) > 0);
    }
}
