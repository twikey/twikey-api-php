<?php
namespace Twikey\Api\Helper;

/**
 * Interface PaylinkCallback See SamplePaylinkCallback in the tests for a sample implementation
 * @package Twikey\Api\Helper
 */
interface PaylinkCallback
{
    public function handle($paylink);
}
