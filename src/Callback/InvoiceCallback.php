<?php
namespace Twikey\Api\Callback;

/**
 * Interface InvoiceCallback See SampleInvoiceCallback in the tests for a sample implementation
 * @package Twikey\Api\Callback
 */
interface InvoiceCallback
{
    /**
     * Allow storing the start of the feed
     * :param position: position where the feed started
     * :param number_of_updates: number of items in the feed
     */
    public function start($position, $number_of_updates);

    /**
     * Handle an updated invoice
     * :param doc: actual invoice
     */
    public function handle($invoice);
}
