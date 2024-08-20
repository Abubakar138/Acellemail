<?php

namespace Acelle\Library\Contracts;

use Closure;

interface CampaignInterface
{
    // Operation
    public function execute();
    public function run();
    public function pause();
    public function resume();

    public function loadDeliveryJobsByIds(Closure $callback, int $page, array $listOfIds);

    // Set status
    public function setDone();
    public function setQueued();
    public function setSending();
    public function setScheduled();
    public function setPaused();
    public function setError($error = null);

    // Check status
    public function isQueued();
    public function isSending();
    public function isDone();
    public function isPaused();
    public function isError();

    // MISC
    public function extractErrorMessage();
    public static function checkAndExecuteScheduledCampaigns();
}
