<?php

namespace Twikey\Api\Callback;

/**
 * Interface PaymentCallback See SamplePaymentCallback in the tests for a sample implementation
 * @package Twikey\Api\Callback
 */
interface PaymentCallback
{
    /**
     * Allow storing the start of the feed
     * :param position: position where the feed started
     * :param number_of_updates: number of items in the feed
     */
    public function start($position, $number_of_updates);

    /**
     * Handle an updated payment event
     * :param doc: actual event
     */
    public function handle($paymentEvent);
}
