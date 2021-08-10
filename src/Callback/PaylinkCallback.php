<?php
namespace Twikey\Api\Callback;

/**
 * Interface PaylinkCallback See SamplePaylinkCallback in the tests for a sample implementation
 * @package Twikey\Api\Callback
 */
interface PaylinkCallback
{
    public function handle($paylink);
}
