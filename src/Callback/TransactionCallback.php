<?php
namespace Twikey\Api\Callback;

/**
 * Interface TransactionCallback See SampleTransactionCallback in the tests for a sample implementation
 * @package Twikey\Api\Callback
 */
interface TransactionCallback
{
    public function handle($transaction);
}
