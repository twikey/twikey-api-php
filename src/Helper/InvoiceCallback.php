<?php
namespace Twikey\Api\Helper;

/**
 * Interface InvoiceCallback See SampleInvoiceCallback in the tests for a sample implementation
 * @package Twikey\Api\Helper
 */
interface InvoiceCallback
{
    public function handle($invoice);
}
