<?php
namespace Twikey\Api\Callback;

/**
 * Interface MandateCallback See SampleMandateCallback in the tests for a sample implementation
 * @package Twikey\Api\Callback
 */
interface DocumentCallback
{
    public function handleNew($data);
    public function handleUpdate($data);
    public function handleCancel($data);
}
