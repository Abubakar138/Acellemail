<?php

namespace Acelle\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Acelle\Model\Automation2;
use Acelle\Model\Notification;
use Acelle\Cashier\Cashier;
use Acelle\Model\Subscription;
use Acelle\Model\Setting;
use Acelle\Model\Campaign;
use Acelle\Model\Customer;
use Laravel\Tinker\Console\TinkerCommand;
use Exception;
use Acelle\Library\Facades\SubscriptionFacade;
use Acelle\Helpers\LicenseHelper;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [

    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        if (!isInitiated()) {
            return;
        }

        // Make sure CLI process is NOT executed as root
        Notification::recordIfFails(function () {
            if (!exec_enabled()) {
                throw new Exception('The exec() function is missing or disabled on the hosting server');
            }

            if (exec('whoami') == 'root') {
                throw new Exception("Cronjob process is executed by 'root' which might cause permission issues. Make sure the cronjob process owner is the same as the acellemail/ folder's owner");
            }
        }, 'CronJob issue');

        // Make sure CLI process is NOT executed as root
        Notification::recordIfFails(function () {
            $minPHPRecommended = config('custom.php_recommended');

            if (!version_compare(PHP_VERSION, $minPHPRecommended, '>=')) {
                throw new Exception(trans('messages.requirement.php_version.not_supuported.description', ['current' => PHP_VERSION, 'required' => $minPHPRecommended]));
            }
        }, $phpMsgTitle = trans('messages.requirement.php_version.not_supuported.title'));

        $schedule->call(function () {
            event(new \Acelle\Events\CronJobExecuted());
        })->name('cronjob_event:log')->everyMinute();

        // Automation2
        $schedule->call(function () {
            Automation2::run();
        })->name('automation:run')->everyFiveMinutes();

        // Bounce/feedback handler
        $schedule->command('handler:run')->everyThirtyMinutes();

        // Sender verifying
        $schedule->command('sender:verify')->everyFiveMinutes();

        // System clean up
        $schedule->command('system:cleanup')->daily();

        // GeoIp database check
        $schedule->command('geoip:check')->everyMinute()->withoutOverlapping(60);

        // Subscription: check expiration
        $schedule->call(function () {
            Notification::recordIfFails(function () {
                SubscriptionFacade::endExpiredSubscriptions();
                SubscriptionFacade::createRenewInvoices();
                SubscriptionFacade::autoChargeRenewInvoices();
            }, 'Error checking subscriptions');
        })->name('subscription:monitor')->everyFiveMinutes();

        // Check for scheduled campaign to execute
        $schedule->call(function () {
            Campaign::checkAndExecuteScheduledCampaigns();
        })->name('check_and_execute_scheduled_campaigns')->everyMinute();

        $licenseTask = $schedule->call(function () {
            Notification::recordIfFails(
                function () {
                    $license = LicenseHelper::getCurrentLicense();

                    if (is_null($license)) {
                        throw new Exception(trans('messages.license.error.no_license'));
                    }

                    LicenseHelper::refreshLicense();
                },
                $title = trans('messages.license.error.invalid'),
                $exceptionCallback = null,
            );
        })->name('verify_license');

        if (config('custom.japan')) {
            $licenseTask->everyMinute();
        } else {
            $licenseTask->weeklyOn(rand(1, 6), '10:'.rand(10, 59)); // randomly from Mon to Sat, at 10:10 - 10:59
        }

        // Auto (force) resume pending campaigns (those are in "sending" status but not actually running)
        $schedule->call(function () {
            Campaign::checkAndForceRerunCampaigns();
        })->name('campaign:force-rerun-pending-campaigns')->everyTenMinutes();

        /*
        // Update list/user cache every 30 minutes
        // @important: potential performance issue here
        $schedule->call(function() {
            $customers = Customer::all();

            foreach($customers as $customer) {
                if (is_null($customer->getCurrentActiveGeneralSubscription())) {
                    continue;
                }

                $lists = $customer->lists;

                foreach ($lists as $list) {
                    safe_dispatch(new \Acelle\Jobs\UpdateMailListJob($list));
                }
                safe_dispatch(new \Acelle\Jobs\UpdateUserJob($customer));
            }
        })->name('update_list_stats')->daily();
        */

        // Queued import/export/campaign
        // Allow overlapping: max 10 proccess as a given time (if cronjob interval is every minute)
        // Job is killed after timeout
        $schedule->command('queue:work --queue=default,batch --tries=1 --max-time=180')->everyMinute();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
