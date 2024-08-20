<?php

namespace Acelle\Library;

use Acelle\Library\Exception\OutOfCredits;
use Illuminate\Support\Facades\Cache;
use Exception;

class InMemoryCreditTracker
{
    public const ZERO = 0;
    public const UNLIMITED = -1;

    protected $resourceKey;

    public static function load(string $resourceKey)
    {
        $tracker = new self($resourceKey);
        return $tracker;
    }

    private function __construct($resourceKey)
    {
        $this->resourceKey = $resourceKey;
    }

    public function getRemainingCredits()
    {
        $credits = Cache::get($this->resourceKey);
        if (empty($credits)) {
            return self::ZERO;
        }

        return (int)$credits;
    }

    private function test()
    {
        if ($this->getRemainingCredits() == self::ZERO) {
            throw new OutOfCredits('Credits exceeded');
        }
    }

    public function count()
    {
        with_cache_lock($this->resourceKey, function () {
            $this->test();

            $remainingCredits = $this->getRemainingCredits();
            if ($remainingCredits != self::UNLIMITED) {
                $remainingCredits -= 1;
                Cache::put($this->resourceKey, $remainingCredits);
            }
        });
    }

    public function setCredits($amount)
    {
        if (!is_int($amount)) {
            throw new Exception('Invalid value for CreditTracker credits. Try using "(int)$credit": '.$amount);
        }

        if ($amount < self::UNLIMITED) {
            throw new Exception('Invalid value for CreditTracker credits (Integer >= -1): '.$amount);
        }

        Cache::put($this->resourceKey, $amount);

        return $this;
    }

    public function rollback()
    {
        with_cache_lock($this->resourceKey, function () {
            $remainingCredits = $this->getRemainingCredits();

            if ($remainingCredits != self::UNLIMITED) {
                $remainingCredits += 1;
                Cache::put($this->resourceKey, $remainingCredits);
            }
        });
    }

    public function isUnlimited()
    {
        return $this->getRemainingCredits() == self::UNLIMITED;
    }

    public function isZero()
    {
        return $this->getRemainingCredits() == self::ZERO;
    }

    public function topup($amount)
    {
        with_cache_lock($this->resourceKey, function () use ($amount) {
            $remainingCredits = $this->getRemainingCredits();

            if ($remainingCredits != self::UNLIMITED) {
                $remainingCredits += $amount;
                Cache::put($this->resourceKey, $remainingCredits);
            } else {
                throw new Exception('Cannot topup credits, already UNLIMITED');
            }
        });
    }
}
