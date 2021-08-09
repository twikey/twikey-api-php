<?php
namespace Twikey\Api\Helper;

/**
 * Interface RefundCallback
 * @package Twikey\Api\Helper
 */
interface RefundCallback
{
    public function handle($creditTransfer);
}
