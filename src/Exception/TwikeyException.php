<?php
declare(strict_types=1);
namespace Twikey\Api\Exception;

use Exception;
use Throwable;

/**
 * Class TwikeyException
 * @package Twikey\Api\Exception;
 */
class TwikeyException extends Exception
{
    public $twikey_code;

    public function __construct($message, $twikey_code = 'err_unknown', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->twikey_code = $twikey_code;
    }

    public function getTwikeyCode()
    {
        return $this -> twikey_code;
    }
}
