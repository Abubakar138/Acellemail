<?php

namespace Acelle\Jobs;

class UpdateSegmentJob extends Base
{
    protected $segment;

    public function __construct($segment)
    {
        $this->segment = $segment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->segment->updateCache();
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->segment->id;
    }
}
