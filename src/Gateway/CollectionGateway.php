<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Exception\TwikeyException;

class CollectionGateway extends BaseGateway
{

    /**
     * Generate a collection to be sent to the bank
     *
     * @link https://www.twikey.com/api/#execute-collection
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function create(int $ct, ?string $colltndt, ?array $mndtId, ?bool $prenotify)
    {
        $response = $this->request('POST',
            sprintf('/creditor/collect?ct=%s%s%s%s',
                $ct,
                isset($colltndt) ? "&colltndt=$colltndt" : null,
                isset($mndtId) && !empty($mndtId) ? sprintf("&mndtId=%s", implode (',', $mndtId)) : null,
                isset($prenotify) ? sprintf("&prenotify=%s", $prenotify ? 'true' : 'false') : null),
            []
        );
        $server_output = $this->checkResponse($response, "Executing a collection!");
        return json_decode($server_output);
    }

    /**
     * Fetches a collections' status
     *
     * @link https://www.twikey.com/api/#status-collection
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     * @deprecated Prefer the Transaction feed as it offers more functionality
     */
    public function get(?int $id, ?string $pmtinfid)
    {
        $response = $this->request('GET',
            sprintf('/creditor/collect%s%s%s',
                isset($id) || isset($pmtinfid) ? '?' : null,
                isset($id) ? "id=$id" : null,
                isset($pmtinfid) ? sprintf("%spmtinfid=$pmtinfid", isset($id) ? '&' : null) : null),
            []
        );
        $server_output = $this->checkResponse($response, "Retrieving collections!");
        return json_decode($server_output);
    }
}
