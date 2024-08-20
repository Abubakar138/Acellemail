<?php

namespace Acelle\Library;

use Exception;

class RateLimit
{
    protected $amount;
    protected $periodValue;
    protected $periodUnit;
    protected $description;

    public const UNLIMITED = -1;
    public const ZERO = 0;

    public function __construct(int $amount, int $periodValue, string $periodUnit, $description = null)
    {
        if (self::UNLIMITED == $amount || $amount < self::ZERO || !is_int($amount)) {
            throw new Exception('Invalid RATE LIMIT amount '.$amount);
        }

        $this->amount = $amount;
        $this->periodValue = $periodValue;
        $this->periodUnit = $periodUnit;
        $this->description = $description;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getPeriodValue(): int
    {
        return $this->periodValue;
    }

    public function getPeriodUnit(): string
    {
        return $this->periodUnit;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getPeriod()
    {
        return sprintf("%s %s", $this->getPeriodValue(), $this->getPeriodUnit());
    }
}
