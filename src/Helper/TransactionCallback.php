<?php
namespace Twikey\Api\Helper;

/**
 * Interface TransactionCallback See SampleTransactionCallback in the tests for a sample implementation
 * @package Twikey\Api\Helper
 */
interface TransactionCallback
{
    public function handle($transaction);
}
