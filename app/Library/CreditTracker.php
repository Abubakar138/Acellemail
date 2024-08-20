<?php

namespace Acelle\Library;

use Acelle\Library\Exception\OutOfCredits;
use Acelle\Library\Lockable;
use Exception;

class CreditTracker
{
    protected $filepath;
    protected $lock;

    public const ZERO = 0;
    public const UNLIMITED = -1;

    // Using CreditTracker::load('file') makes more sense than
    public static function load($filepath, bool $createFileIfNotExists = false)
    {
        $tracker = new self($filepath, $createFileIfNotExists);
        return $tracker;
    }

    private function __construct($filepath, bool $createFileIfNotExists)
    {
        $this->filepath = $filepath;
        $this->lock = $filepath.'-lock';

        if ($createFileIfNotExists && !file_exists($this->filepath)) {
            $this->createFile();
        }
    }

    public function createFile()
    {
        $file = fopen($this->filepath, 'w');
        fclose($file);
    }

    public function getRemainingCredits()
    {
        $credits = file_get_contents($this->filepath);
        if (empty(trim($credits))) {
            return self::ZERO;
        }

        return (int)$credits;
    }

    private function test()
    {
        if ($this->getRemainingCredits() == self::ZERO) {
            throw new OutOfCredits('Credits exceeded'.$this->filepath);
        }
    }

    public function count()
    {
        Lockable::withExclusiveLock($this->lock, function () {
            $this->test();

            $remainingCredits = $this->getRemainingCredits();

            if ($remainingCredits != self::UNLIMITED) {
                $remainingCredits -= 1;
                $remainingCredits = "{$remainingCredits}"; // cast to string
                file_put_contents($this->filepath, $remainingCredits);
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

        file_put_contents($this->filepath, (string)$amount);
        return $this;
    }

    public function rollback()
    {
        Lockable::withExclusiveLock($this->lock, function () {
            $remainingCredits = $this->getRemainingCredits();

            if ($remainingCredits != self::UNLIMITED) {
                $remainingCredits += 1;
                $remainingCredits = "{$remainingCredits}"; // cast to string
                file_put_contents($this->filepath, $remainingCredits);
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
        Lockable::withExclusiveLock($this->lock, function () use ($amount) {
            $remainingCredits = $this->getRemainingCredits();

            if ($remainingCredits != self::UNLIMITED) {
                $remainingCredits += $amount;
                $remainingCredits = "{$remainingCredits}"; // cast to string
                file_put_contents($this->filepath, $remainingCredits);
            } else {
                throw new Exception('Cannot topup credits, already UNLIMITED');
            }
        });
    }
}
