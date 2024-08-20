<?php

namespace Tests\Unit;

use Tests\TestCase;
use Acelle\Library\InMemoryCreditTracker;
use Acelle\Library\Exception\OutOfCredits;
use Exception;

use function Acelle\Helpers\execute_with_limits;

class InMemoryCreditTrackerTest extends TestCase
{
    public function test_credit_tracker_just_works()
    {
        $key1 = 'credit-tracker-test-1';
        $creditTracker1 = InMemoryCreditTracker::load($key1);
        $creditTracker1->setCredits(5);

        $key2 = 'credit-tracker-test-2';
        $creditTracker2 = InMemoryCreditTracker::load($key2);
        $creditTracker2->setCredits(2);

        // Execute 1st time -> ok
        $task = fn () => execute_with_limits([  ], [ $creditTracker1, $creditTracker2 ]);

        // First execution OK
        $task();
        $this->assertEquals($creditTracker1->getRemainingCredits(), 4);
        $this->assertEquals($creditTracker2->getRemainingCredits(), 1);

        // Second execution OK
        $task();
        $this->assertEquals($creditTracker1->getRemainingCredits(), 3);
        $this->assertEquals($creditTracker2->getRemainingCredits(), 0);
    }

    public function test_credit_tracker_with_credit_exceeding()
    {
        $key1 = 'credit-tracker-test-1';
        $creditTracker1 = InMemoryCreditTracker::load($key1);
        $creditTracker1->setCredits(3);

        $key2 = 'credit-tracker-test-2';
        $creditTracker2 = InMemoryCreditTracker::load($key2);
        $creditTracker2->setCredits(0);

        expect($creditTracker1->isZero())->toBe(false);
        expect($creditTracker2->isZero())->toBe(true);

        expect($creditTracker1->isUnlimited())->toBe(false);
        expect($creditTracker2->isUnlimited())->toBe(false);

        // Second execution results in exception
        $task = fn () => execute_with_limits([  ], [ $creditTracker1, $creditTracker2 ]);

        try {
            $task();

            throw new Exception('Tasks did not throw an exception, something went wrong');
        } catch (OutOfCredits $e) {
            // Falling here
        }

        // Credit tracker1 was rolled back
        $this->assertEquals($creditTracker1->getRemainingCredits(), 3);

        // Credit tracker 2 still 0
        $this->assertEquals($creditTracker2->getRemainingCredits(), 0);
    }

    public function test_credit_tracker_and_task_exception()
    {
        $key1 = 'credit-tracker-test-1';
        $creditTracker1 = InMemoryCreditTracker::load($key1);
        $creditTracker1->setCredits(100);

        $key2 = 'credit-tracker-test-2';
        $creditTracker2 = InMemoryCreditTracker::load($key2);
        $creditTracker2->setCredits(100);

        // Task
        $task = fn () => execute_with_limits([  ], [ $creditTracker1, $creditTracker2 ], function () {
            throw new Exception();
        });

        try {
            $task(); // throw exception

            throw new Exception('Tasks did not throw an exception, something went wrong');
        } catch (Exception $e) {
            // OK here
        }

        // Rollback tracker 1
        expect($creditTracker1->getRemainingCredits())->toBe(100);

        // Rollback tracker 2
        expect($creditTracker2->getRemainingCredits())->toBe(100);
    }

    public function test_with_unlimited_credits()
    {
        $key1 = 'credit-tracker-test-1';
        $creditTracker1 = InMemoryCreditTracker::load($key1);
        $creditTracker1->setCredits(-1);

        $task = fn () => execute_with_limits([  ], [ $creditTracker1 ]);

        $task();
        expect($creditTracker1->getRemainingCredits())->toBe(InMemoryCreditTracker::UNLIMITED);
        expect($creditTracker1->isUnlimited())->toBe(true);

        $task();
        expect($creditTracker1->getRemainingCredits())->toBe(InMemoryCreditTracker::UNLIMITED);
        expect($creditTracker1->isUnlimited())->toBe(true);

        $task();
        expect($creditTracker1->getRemainingCredits())->toBe(InMemoryCreditTracker::UNLIMITED);
        expect($creditTracker1->isUnlimited())->toBe(true);
    }
}
