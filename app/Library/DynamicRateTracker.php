<?php

namespace Acelle\Library;

use Carbon\Carbon;
use Exception;
use Acelle\Library\Exception\RateLimitExceeded;
use Closure;
use Illuminate\Support\Facades\Cache;

/*
 * Limitation of this algorithm
 *
 *   + What if a process reserves all the credits but did not use it all?
 *   + It is even worse if the credits reservation is too long (no other process of another server can use it)
 *       - even if we use only 01 dedicated worker per campaign,
 *         we still have to deal with resource sharing for global sending servers (used by many customers and campaigns)
 *
 *   + Doubled credits used at window point
 *
 */

class DynamicRateTracker
{
    protected $resourceKey;
    protected $reservedPath;
    protected $limits;

    protected $seperator = ':';

    public function __construct(string $resourceKey, $limits = []) // RateLimit class
    {
        $this->resourceKey = $resourceKey;

        // IMPORTANT: this path is shared among processes of the same server (same filesystem)
        $this->reservedPath = "/tmp/dynamic-rate-tracker-pid-".md5($this->resourceKey);

        $this->limits = $limits;

        if (!file_exists($this->reservedPath)) {
            touch($this->reservedPath);
        }
    }

    public function getReservedPath()
    {
        return $this->reservedPath;
    }

    // Reverse of count()
    // @deprecated: rollback is not needed as even a failed operation is also counted in rate limits
    public function rollback()
    {
        // This is important, in case a tracker is counted (successfully, returning ok) in the execute_with_limits() function
        // and is later on rolled back
        if (empty($this->limits)) {
            return;
        }

        $lock = new Lockable($this->reservedPath);
        $lock->getExclusiveLock(function ($fopen) {
            list($until, $credits) = $this->parseReservedCredits($fopen);

            if ($until) {
                $this->updateReservedCredits($until, $credits + 1);
            } else {
                // do nothing as there is no reserved credits avaialable
            }

        });
    }

    public function getRateLimits()
    {
        return $this->limits;
    }

    // Example of $period: "24 hours", "1 week"
    // i.e. Clean up credit tracking logs that are older than "24 hours", "1 week"
    public function cleanup(string $period = null)
    {
        // no cleanup needed
    }

    private function updateReservedCredits(Carbon $until, $credits)
    {
        file_put_contents($this->reservedPath, "{$until->timestamp}{$this->seperator}{$credits}");
    }

    private function countReservedCredits(Carbon $now, $fopen)
    {
        list($until, $credits) = $this->parseReservedCredits($fopen);

        if ($until && $until->gte($now) && $credits > RateLimit::ZERO) {
            $this->updateReservedCredits($until, $credits - 1);
            return true;
        } else {
            return false;
        }
    }

    // Test and throw exception, do not count()
    // Used by DelayedJob
    public function test(Carbon $now = null)
    {
        $this->count($now, $count = false);
    }

    public function count(Carbon $now = null, $count = true)
    {
        if (empty($this->limits)) {
            return;
        }

        $now = $now ?: Carbon::now();
        $available = false;

        // Step 1: check if there is a local reservation
        $lock = new Lockable($this->reservedPath);
        $lock->getExclusiveLock(function ($fopen) use ($now, &$available) {
            if ($this->countReservedCredits($now, $fopen)) {
                $available = true;
            }
        });

        if ($available) {
            // If there is a valid local reservation, then move forward
            // without having to check the remote tracker
            return;
        }

        // If not available locally, check the remote
        with_cache_lock($this->resourceKey, function () use ($now, $count) {
            if ($this->isReservedByOthers($now)) {
                // no local reserved
                // and there is a reserved-until key (by other)

                $reservedUntil = (int)Cache::get($this->resourceKey);
                $until = new Carbon($reservedUntil);

                throw new RateLimitExceeded("Calculated rate limit exceeded: {$this->getMinCredits()}/{$this->getShortestLimitPeriod()} | {$this->getLimitsDescription()}. Will be availalbe in {$until->diffForHumans()}, at {$until->toString()}");
            }

            if (!$count) {
                // this is for Delay job only
                // test if it shoudl resume a campaign, do not count ONE use credits
                return;
            }

            // Okie, so ready
            $until = $now->add($this->getShortestLimitPeriod());
            $credits = $this->getMinCredits();

            // reserve credits for local & remote
            // also count 1 credit used
            $this->reserve($until, $credits - 1);

        }, $timeout = 15);
    }

    public function isReservedByOthers(Carbon $now)
    {
        $reservedUntil = Cache::get($this->resourceKey);

        if (!is_null($reservedUntil) && (new Carbon((int)$reservedUntil))->gte($now)) {
            return true;
        } else {
            return false;
        }
    }

    private function reserve(Carbon $until, $credits)
    {
        // Set local
        $this->updateReservedCredits($until, $credits);

        // Set remote reservef flag
        Cache::put($this->resourceKey, $until->timestamp);
    }

    public function parseReservedCredits($fopen = null)
    {
        if (is_null($fopen)) {
            $fopen = fopen($this->getReservedPath(), 'r');
        }

        rewind($fopen);
        $contents = fgets($fopen);
        if (empty($contents)) {
            return [null, null];
        }

        list($until, $credits) = explode($this->seperator, $contents);

        return [new Carbon((int)$until), (int)$credits];
    }

    public function getShortestLimitPeriod()
    {
        $shortest = null;
        $now = now();
        foreach($this->limits as $limit) {
            $d = $now->copy()->add($limit->getPeriod());
            if (is_null($shortest) || $now->copy()->add($shortest->getPeriod())->gte($d)) {
                $shortest = $limit;
            }
        }

        return $shortest->getPeriod();
    }

    public function getMinCredits()
    {
        $min = null;
        foreach($this->limits as $limit) {
            if (is_null($min) || $limit->getAmount() < $min) {
                $min = $limit->getAmount();
            }
        }

        return $min;
    }

    public function clearReservation()
    {
        file_put_contents($this->getReservedPath(), '');
        Cache::forget($this->resourceKey);
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
