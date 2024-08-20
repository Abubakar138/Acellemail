<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;
use Acelle\Library\InMemoryRateTracker;
use Acelle\Library\RateLimit;
use Acelle\Library\Exception\RateLimitExceeded;

class InMemoryRateTrackerTest extends TestCase
{
    public function test_in_memory_rate_tracker_just_works()
    {
        $this->assertEquals(1, 1);

        $key = 'test-in-memory-rate-tracker';
        $tracker = new InMemoryRateTracker($key);
        $tracker->cleanup();

        $now = Carbon::now();
        $oneMinuteAgo = $now->clone()->add('1 minute ago');

        // 4 times
        $tracker->count($oneMinuteAgo);
        $tracker->count($oneMinuteAgo);
        $tracker->count($now);
        $tracker->count($now);

        /*
         * After 4 counts, the lock file should look like this
         *
         * 202307300013:2
         * 202307300014:2
         *
         */

        $used = $tracker->getCreditsUsed($oneMinuteAgo, $now);
        $this->assertEquals($used, 4);

        // Rollback 1
        $tracker->rollback();

        /*
         * After rollback 1, the lock file should look like this
         *
         * 202307300013:2
         * 202307300014:1
         *
         */

        $used = $tracker->getCreditsUsed($oneMinuteAgo, $now);
        $this->assertEquals($used, 3);

        // Rollback 2
        $tracker->rollback();

        /*
         * After rollback 2, the lock file should look like this
         *
         * 202307300013:2
         *
         */

        $used = $tracker->getCreditsUsed($oneMinuteAgo, $now);
        $this->assertEquals($used, 2);

        sleep(1);

        $used = $tracker->getCreditsUsed(now()->add('1 minute ago'), $now);
        $this->assertEquals($used, 2);

        $used = $tracker->getCreditsUsed(now()->add('6 seconds ago'), $now);
        $this->assertEquals($used, 0);
    }

    public function test_cleanup_with_period()
    {
        $key = 'test-in-memory-rate-tracker';
        $tracker = new InMemoryRateTracker($key);
        $tracker->cleanup();

        $this->assertEquals($tracker->getCreditsUsed(), 0);

        $now = now();
        $twoMinuteAgo = $now->copy()->add('2 minute ago');
        $oneMinuteAgo = $now->copy()->add('1 minute ago');

        // 2 time
        $tracker->count($twoMinuteAgo);
        $tracker->count($twoMinuteAgo);

        // 3 times
        $tracker->count($oneMinuteAgo);
        $tracker->count($oneMinuteAgo);
        $tracker->count($oneMinuteAgo);

        // two: 2
        // one: 3
        $this->assertEquals($tracker->getCreditsUsed(), 5);

        // 1 time
        $tracker->count($now);

        // two: 2
        // one: 3
        // now: 1
        $this->assertEquals($tracker->getCreditsUsed(), 6);

        // Clean up
        $tracker->cleanup('2 minute'); // keep records during the last 2 minute, including 'two', 'one' and 'now'
        $this->assertEquals($tracker->getCreditsUsed(), 6);

        $tracker->cleanup('1 minute'); // keep records during the last 1 minute, including 'one' and 'now'
        $this->assertEquals($tracker->getCreditsUsed(), 4);
    }

    public function test_with_limits()
    {
        $key = 'test-in-memory-rate-tracker';
        $limits = [
            new RateLimit(2, 1, 'minute', '#'),
            new RateLimit(5, 24, 'hour', '#'),
        ];

        $tracker = new InMemoryRateTracker($key, $limits);
        $tracker->cleanup();

        // 2 time
        $tracker->count();
        $tracker->count();

        $this->assertEquals($tracker->getLimitsDescription(), "#, #");
        $this->assertEquals($tracker->getCreditsUsed(), 2);
        $this->expectException(RateLimitExceeded::class);
        $tracker->count();
    }
}
