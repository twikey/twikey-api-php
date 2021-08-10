<?php
namespace Twikey\Api\Callback;

/**
 * Interface InvoiceCallback See SampleInvoiceCallback in the tests for a sample implementation
 * @package Twikey\Api\Callback
 */
interface InvoiceCallback
{
    public function handle($invoice);
}
