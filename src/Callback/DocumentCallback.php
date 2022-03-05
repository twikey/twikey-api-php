<?php
namespace Twikey\Api\Callback;

/**
 * Interface MandateCallback See SampleMandateCallback in the tests for a sample implementation
 * @package Twikey\Api\Callback
 */
interface DocumentCallback
{
    public function handleNew($mandate,$evtTime);
    public function handleUpdate($originalMandateNumber,$mandate,$reason,$evtTime);
    public function handleCancel($mandateNumber,$reason,$evtTime);
}
