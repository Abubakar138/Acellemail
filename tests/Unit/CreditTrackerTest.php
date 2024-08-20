<?php

use Acelle\Library\CreditTracker;
use Acelle\Library\Exception\OutOfCredits;
use function Acelle\Helpers\execute_with_limits;

test('Credit tracker just works', function () {
    $file = '/tmp/test-rate-'.uniqid();
    $tracker = CreditTracker::load($file, $createFile = true);

    expect($tracker->getRemainingCredits())->toBe(CreditTracker::ZERO);
    expect($tracker->isZero())->toBe(true);
    expect($tracker->isUnlimited())->toBe(false);

    $tracker->setCredits(2);
    expect($tracker->getRemainingCredits())->toBe(2);
    expect($tracker->isZero())->toBe(false);
    expect($tracker->isUnlimited())->toBe(false);

    // Count to deduct credit
    $tracker->count();
    expect($tracker->getRemainingCredits())->toBe(1);

    // Count to deduct credit
    $tracker->count();
    expect($tracker->getRemainingCredits())->toBe(0);
});

test('Credit tracker with unlimited credits', function () {
    $file = '/tmp/test-rate-'.uniqid();
    $tracker = CreditTracker::load($file, $createFile = true);

    expect($tracker->getRemainingCredits())->toBe(CreditTracker::ZERO);

    $tracker->setCredits(-1);
    expect($tracker->getRemainingCredits())->toBe(CreditTracker::UNLIMITED);
    expect($tracker->isUnlimited())->toBe(true);

    // Count to deduct credit
    $tracker->count();
    expect($tracker->getRemainingCredits())->toBe(CreditTracker::UNLIMITED);
    expect($tracker->isUnlimited())->toBe(true);

    // Test with execute_with_limits() helper
    $task = fn () => execute_with_limits([  ], [ $tracker ]);

    $task();
    expect($tracker->getRemainingCredits())->toBe(CreditTracker::UNLIMITED);
    expect($tracker->isUnlimited())->toBe(true);

    $task();
    expect($tracker->getRemainingCredits())->toBe(CreditTracker::UNLIMITED);
    expect($tracker->isUnlimited())->toBe(true);
});

test('Credit tracker should throw OutOfCredits', function () {
    $file = '/tmp/test-rate-'.uniqid();
    $tracker = CreditTracker::load($file, $createFile = true);
    $tracker->setCredits(1);   // credits = 1
    $tracker->count();         // credits = 0

    expect($tracker->getRemainingCredits())->toBe(CreditTracker::ZERO);

    // Since credit is already ZERO, another count() shall throw an exception
    $tracker->count();
})->throws(OutOfCredits::class);


test('Cannot assign invalid values for CreditTracker (non-int)', function () {
    $file = '/tmp/test-rate-'.uniqid();
    $tracker = CreditTracker::load($file, $createFile = true);
    $tracker->setCredits('xxxxx');   // invalid
})->throws(Exception::class);

test('Cannot assign invalid values for CreditTracker (<-1)', function () {
    $file = '/tmp/test-rate-'.uniqid();
    $tracker = CreditTracker::load($file, $createFile = true);
    $tracker->setCredits(-2);   // invalid
})->throws(Exception::class);


test("Top-up credits", function() {
    $key1 = 'credit-tracker-test-1';
    $creditTracker1 = CreditTracker::load($key1);
    $creditTracker1->setCredits(100);
    $creditTracker1->topup(1);
    expect($creditTracker1->getRemainingCredits())->toBe(101);
    $creditTracker1->topup(5);
    expect($creditTracker1->getRemainingCredits())->toBe(106);
    $creditTracker1->topup(10);
    expect($creditTracker1->getRemainingCredits())->toBe(116);

    try {
        $creditTracker1->setCredits(CreditTracker::UNLIMITED);
        $creditTracker1->topup(1);

        throw new Exception('Tasks did not throw an exception, something went wrong');
    } catch (Exception $e) {
        // Falling here
    }
});