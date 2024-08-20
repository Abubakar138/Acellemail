<?php

namespace Acelle\Listeners;

use Acelle\Events\MailListUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MailListUpdatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MailListUpdated  $event
     * @return void
     */
    public function handle(MailListUpdated $event)
    {
        safe_dispatch(new \Acelle\Jobs\UpdateMailListJob($event->mailList));
    }
}
