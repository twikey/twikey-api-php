<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Callback\DocumentCallback;
use Twikey\Api\Exception\TwikeyException;

class DocumentGateway extends BaseGateway
{
    /**
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
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function update($data)
    {
        $response = $this->request('POST', "/creditor/mandate/update", ['form_params' => $data]);
        $server_output = $this->checkResponse($response, "Update a mandate!");
        return json_decode($server_output);
    }

    /**
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function cancel($mndtId, $rsn, $notify = false)
    {
        $response = $this->request('DELETE', sprintf("/creditor/mandate?mndtId=%s&rsn=%s&notify=%s", $mndtId, $rsn, $notify), []);
        $server_output = $this->checkResponse($response, "Cancel a mandate!");
        return json_decode($server_output);
    }

    /**
     * Read until empty
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function feed(DocumentCallback $callback): int
    {
        $count = 0;
        do {
            $response = $this->request('GET', "/creditor/mandate", []);
            $server_output = $this->checkResponse($response, "Retrieving mandate feed!");
            $updates = json_decode($server_output);
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
                    $callback->handleCancel($update->OrgnlMndtId,$update->CxlRsn,$update->EvtTime);
                }
            }
        } while(count($updates->Messages) > 0);
        return $count;
    }

    /**
     * Note this is rate limited
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

}
