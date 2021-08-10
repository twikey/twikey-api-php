<?php
namespace Twikey\Api\Callback;

/**
 * Interface RefundCallback
 * @package Twikey\Api\Callback
 */
interface RefundCallback
{
    public function handle($creditTransfer);
}
