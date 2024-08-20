<?php

use Acelle\Library\RateTracker;
use Carbon\Carbon;

test('Rollback just works', function () {
    $file = '/tmp/test-rate-'.uniqid();
    $tracker = new RateTracker($file);

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
    $linesCount = sizeof(file($tracker->getLockFilePath()));
    expect($used)->toBe(4);
    expect($linesCount)->toBe(2);

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
    $linesCount = sizeof(file($tracker->getLockFilePath()));
    expect($used)->toBe(3);
    expect($linesCount)->toBe(2);

    // Rollback 2
    $tracker->rollback();

    /*
     * After rollback 2, the lock file should look like this
     *
     * 202307300013:2
     *
     */

    $used = $tracker->getCreditsUsed($oneMinuteAgo, $now);
    $linesCount = sizeof(file($tracker->getLockFilePath()));
    expect($used)->toBe(2);
    expect($linesCount)->toBe(1);
});

test('Clean up tracker', function() {

    $file = '/tmp/test-rate';
    $tracker = new RateTracker($file);

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
});