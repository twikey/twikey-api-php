<?php
declare(strict_types=1);
namespace Twikey\Api\Gateway;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Twikey\Api\Exception\TwikeyException;
use Twikey\Api\Twikey;

abstract class BaseGateway
{
    /**
     * @var Twikey
     */
    protected Twikey $twikey;

    public function __construct(Twikey $twikey)
    {
        $this->twikey = $twikey;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    protected function request(string $method, string $uri = '', array $options = []): ResponseInterface {
        return $this->twikey->request($method,$uri,$options);
    }

    /**
     * @throws TwikeyException
     */
    protected function checkResponse($response, $context = "No context") : ?string {
        return $this->twikey->checkResponse($response,$context);
    }
}
