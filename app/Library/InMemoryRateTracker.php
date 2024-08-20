<?php

namespace Acelle\Library;

use Carbon\Carbon;
use Exception;
use Acelle\Library\Exception\RateLimitExceeded;
use Closure;
use Illuminate\Support\Facades\Cache;

/*
 * Log every credit used to a log file, group by $mode, which could be 'minute' (default), hour, day, month...
 * For example, a log file grouped by minute may look like this ( lines of YYYYMMDDHHMM:COUNT format)
 *
 * 202307230913:1200
 * 202307230914:1250
 * 202307230914:1052
 * 202307230930:1178
 *
 * Group by 'minute' supports minute granularity limits (emails per 1 minute)
 * Group by 'hour' supports hour granularity limits (emails per 1 hour), but does not support minute (lower than 1 hour) granularity limits
 *
 */

class InMemoryRateTracker
{
    protected $resourceKey;
    protected $mode = 'minute'; // hour, day, month, year
    protected $seperator = ':';
    protected $blockFormat = [
        'minute' => 'YmdHi',
        'hour' => 'YmdH00',
        'day' => 'Ymd0000',
        'month' => 'Ym000000',
        'year' => 'Y00000000',
    ];

    protected $limits;

    public function __construct(string $resourceKey, $limits = []) // RateLimit class
    {
        $this->resourceKey = $resourceKey;
        $this->limits = $limits;
    }

    public function count(Carbon $now = null)
    {
        with_cache_lock($this->resourceKey, function () use ($now) {
            $now = $now ?: Carbon::now();

            // Throw an exception if test fails (quota exceeded)
            $this->test($now);

            // Record credits use
            $this->record($now);

        }, $timeout = 15);
    }

    // Reverse of count()
    // @deprecated: rollback is not needed as even a failed operation is also counted in rate limits
    public function rollback()
    {
        with_cache_lock($this->resourceKey, function () {
            list($lastBlock, $count) = $this->getLastRecord();

            if (is_null($lastBlock)) {
                throw new Exception('Cannot rollback! There is no previous count!');
            }

            if ($count == 1) {
                $this->removeRecord($lastBlock);
            } else {
                $this->updateRecord($lastBlock, $count - 1);
            }
        }, $timeout = 15);
    }

    public function test(Carbon $now = null)
    {
        if (is_null($now)) {
            $now = Carbon::now();
        }

        foreach ($this->limits as $limit) {
            $period = sprintf("%s %s", $limit->getPeriodValue(), $limit->getPeriodUnit());
            $fromDatetime = $now->copy()->subtract($period);

            $creditsUsed = $this->getCreditsUsed($fromDatetime, $now);

            if ($creditsUsed >= $limit->getAmount()) {
                throw new RateLimitExceeded(sprintf("%s exceeded! %s/%s used", $limit->getDescription(), $creditsUsed, $limit->getAmount()));
            }
        }
    }

    private function record(Carbon $now)
    {
        // Make something like: 202307231527
        $currentBlock = $this->makeBlock($now); // create block for the current date/time
        list($lastBlock, $count) = $this->getLastRecord();

        if ($currentBlock == $lastBlock) {
            $this->updateRecord($lastBlock, $count + 1);
        } else {
            $this->updateRecord($currentBlock, $count = 1);
        }
    }

    public function updateRecord($block, $count)
    {
        $store = Cache::get($this->resourceKey);
        $store[$block] = $count;

        Cache::put($this->resourceKey, $store);
    }

    public function removeRecord($block)
    {
        $store = Cache::get($this->resourceKey);
        unset($store[$block]);

        Cache::put($this->resourceKey, $store);
    }

    public function getRecords(Carbon $fromDatetime = null, Carbon $toDatetime = null)
    {
        $fromDatetime = $fromDatetime ?: Carbon::createFromTimestamp(0); // Create the earliest date of 1970-01-01
        $toDatetime = $toDatetime ?: Carbon::now(); // Current date

        $fromDatetimeStr = $this->makeBlock($fromDatetime);
        $toDatetimeStr = $this->makeBlock($toDatetime);

        $records = [];

        $store = Cache::get($this->resourceKey);

        if (is_null($store)) {
            return $records;
        }

        foreach ($store as $block => $count) {
            if ($block >= $fromDatetimeStr && $block <= $toDatetimeStr) {
                $records[] = [$block, $count];
            }
        }

        // Return
        return $records;
    }

    public function getCreditsUsed(Carbon $fromDatetime = null, Carbon $toDatetime = null)
    {
        $records = $this->getRecords($fromDatetime, $toDatetime);
        $counts = array_map(function ($record) {
            list($block, $count) = $record;
            return $count;
        }, $records);
        $total = array_sum($counts);
        return $total;
    }

    public function getRateLimits()
    {
        return $this->limits;
    }

    // Convert the provided datetime $now to a string
    public function makeBlock($now)
    {
        $now = $now ?: Carbon::now();
        $format = $this->blockFormat[$this->mode];
        return $now->format($format);
    }

    private function getLastRecord()
    {
        $store = Cache::get($this->resourceKey);
        if (empty($store)) { // in case of an empty array
            return [ null, null ];
        }

        $lastKey = array_key_last($store);
        $lastValue = $store[$lastKey];

        return [ $lastKey, $lastValue ];
    }

    // Example of $period: "24 hours", "1 week"
    // i.e. Clean up credit tracking logs that are older than "24 hours", "1 week"
    public function cleanup(string $period = null)
    {
        with_cache_lock($this->resourceKey, function () use ($period) {
            if (is_null($period)) {
                Cache::forget($this->resourceKey);
                return;
            }

            $fromDatetime = now()->subtract($period); // Current date
            $fromDatetimeStr = $this->makeBlock($fromDatetime);
            $store = Cache::get($this->resourceKey);

            if (is_null($store)) {
                return;
            }

            $newStore = [];
            foreach ($store as $block => $count) {
                if ($block >= $fromDatetimeStr) {
                    $newStore[$block] = $count;
                }
            }

            Cache::put($this->resourceKey, $newStore);
        });
    }

    public function getLimitsDescription()
    {
        $str = [];
        foreach ($this->getRateLimits() as $limit) {
            $str[] = $limit->getDescription();
        }

        return implode(', ', $str);
    }
}
