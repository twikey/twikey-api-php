<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Twikey\Api\Exception\TwikeyException;

class SubscriptionGateway extends BaseGateway
{
    /**
     * A subscription can be added on an agreement.
     * This means than when the subscription is run a new transaction will be created using the defined schedule.
     * If you opted for the automatic sending this will be collected as soon as the bank permits it.
     *
     * @link https://www.twikey.com/api/#add-a-subscription
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function create($data, ?array $optionalHeaders = [])
    {
        $response = $this->request('POST', '/creditor/subscription', ['form_params' => $data, 'headers' => $optionalHeaders]);
        $server_output = $this->checkResponse($response, "Creating a new subscription");
        return json_decode($server_output);
    }

    /**
     * Sometimes a subscription needs to be updated.
     * This endpoint allows the update by using the previously passed reference for a specific agreement.
     *
     * @link https://www.twikey.com/api/#update-a-subscription
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function update(string $mndtId, string $subscriptionRef, $data)
    {
        $response = $this->request('POST', sprintf('/creditor/subscription/%s/%s', $mndtId, $subscriptionRef), ['form_params' => $data]);
        $server_output = $this->checkResponse($response, "Signing a mandate!");
        return json_decode($server_output);
    }

    /**
     * A subscription can be cancelled by using it's ref for a specific agreement.
     *
     * @link https://www.twikey.com/api/#cancel-a-subscription
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function cancel(string $mndtId, string $subscriptionRef)
    {
        $response = $this->request('DELETE', sprintf('/creditor/subscription/%s/%s', $mndtId, $subscriptionRef), []);
        $this->checkResponse($response, "Cancel a subscription!");
    }

    /**
     * A single subscription can be fetched for a specific agreement, but note that this call is rate limited.
     *
     * @link https://www.twikey.com/api/#retrieve-a-single-subscription
     *
     * @throws TwikeyException
     * @throws ClientExceptionInterface
     */
    public function get(string $mndtId, string $subscriptionRef)
    {
        $response = $this->request('GET', sprintf('/creditor/subscription/%s/%s', $mndtId, $subscriptionRef), []);
        $server_output = $this->checkResponse($response, "Get subscription details!");
        return json_decode($server_output);
    }
}
