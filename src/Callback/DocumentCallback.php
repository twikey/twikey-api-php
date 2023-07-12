<?php

namespace Twikey\Api\Callback;

/**
 * Interface MandateCallback See SampleMandateCallback in the tests for a sample implementation
 * @package Twikey\Api\Callback
 */
interface DocumentCallback
{
    /**
     * Allow storing the start of the feed
     * :param position: position where the feed started
     * :param number_of_updates: number of items in the feed
     */
    public function start($position, $number_of_updates);

    /**
     * Handle a newly available document
     * :param doc: actual document
     * :param evt_time: time of creation
     */
    public function handleNew($mandate, $evtTime);

    /**
     * Handle an update of a document
     * :param original_doc_number: original reference to the document
     * :param doc: actual document
     * :param reason: reason of change
     * :param evt_time: time of creation
     */
    public function handleUpdate($originalMandateNumber, $mandate, $reason, $evtTime);

    /**
     * Handle an cancelled document
     * :param doc_number: reference to the document
     * :param reason: reason of change
     * :param evt_time: time of creation
     */
    public function handleCancel($mandateNumber, $reason, $evtTime);
}
