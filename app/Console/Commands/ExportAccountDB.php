<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Model\Customer;

use function Acelle\Helpers\set_userdb_connection;

class ExportAccountDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:dbexport {account-uid} {--connection=} {--mysqldump=mysqldump} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Connection information
        $connection = $this->option('connection');

        if (is_null($connection)) {
            throw new \Exception('Option --connection is required');
        }

        // Set DB, in order to validate the connection option
        set_userdb_connection($connection);

        $host = \Config::get("database.connections.{$connection}.host");
        $port = \Config::get("database.connections.{$connection}.port");
        $username = \Config::get("database.connections.{$connection}.username");
        $password = \Config::get("database.connections.{$connection}.password");
        $database = \Config::get("database.connections.{$connection}.database");

        // mysqldump binary path
        $mysqldump = $this->option('mysqldump');

        // Output file
        $output = $this->option('output');

        // Account
        $accountUid = $this->argument('account-uid');
        $customer = Customer::findByUid($accountUid);

        if (is_null($customer)) {
            throw new \Exception("Cannot find customer account with UID '{$accountUid}'");
        }

        if (is_null($output)) {
            throw new \Exception('Option --output is required');
        }

        // DB tables to export
        $tables = [
            'customer'                   => "WHERE id = XXXXX",

            'attachments'                => ['email_id', 'emails'],
            'auto_triggers'              => ['automation2_id', 'automation2s'],
            'automation2s'               => null,
            'automation_templates'       => ['automation2_id', 'automation2s'],
            'bounce_logs'                => "message_id IN (SELECT message_id FROM tracking_logs WHERE customer_id = XXXXX)",
            'campaign_headers'           => ['campaign_id', 'campaigns'],
            'campaign_links'             => ['campaign_id', 'campaigns'],
            'campaign_webhooks'          => ['campaign_id', 'campaigns'],
            'campaigns'                  => null,           // *template_id references templates.id
            'campaigns_lists_segments'   => ['campaign_id', 'campaigns'],
            'click_logs'                 => "message_id IN (SELECT message_id FROM tracking_logs WHERE customer_id = XXXXX)",
            'contacts'                   => "TRUE", // *temporarily take all
            'email_links'                => ['email_id', 'emails'],
            'email_webhooks'             => ['email_id', 'emails'],
            'emails'                     => null, // *template_id references templates.id
            'feedback_logs'              => "message_id IN (SELECT message_id FROM tracking_logs WHERE customer_id = XXXXX)",
            'field_options'             => "field_id IN (SELECT f.id FROM fields f JOIN mail_lists m ON f.mail_list_id = m.id WHERE m.customer_id = XXXXX",
            'fields'                     => ['mail_list_id', 'mail_lists'],
            'forms'                      => null,
            'mail_lists'                 => null,
            'mail_lists_sending_servers' => ['mail_list_id', 'mail_lists'], // *sending_server_id references sending_servers.id
            'mailboxes'                  => null,
            'open_logs'                  => "message_id IN (SELECT message_id FROM tracking_logs WHERE customer_id = XXXXX)",
            'pages'                      => ['mail_list_id', 'mail_lists'], // *layout_id
            'reply_logs'                 => "message_id IN (SELECT message_id FROM tracking_logs WHERE customer_id = XXXXX)",
            'segment_conditions'         => "segment_id IN (SELECT s.id FROM segments s JOIN mail_lists m ON s.mail_list_id = m.id WHERE m.customer_id = XXXXX",
            'segments'                   => ['mail_list_id', 'mail_lists'],
            'senders'                    => null,
            'sending_domains'            => null, // customer_id might be null!
            'sources'                    => "TRUE", // *temporarily take all
            'subscribers'                => ['mail_list_id', 'mail_lists'],
            'tracking_domains'           => null,
            'tracking_logs'              => null, // *sending_server_id, *sub_account_id
            'unsubscribe_logs'           => "TRUE", // *temporarily take all
            'websites'                   => null,
        ];

        $cmd = "{$mysqldump} {$database} --user={$username} --host={$host} --password={$password} --port={$port} > {$output}";

        echo $cmd."\n";

        return 0;
    }
}
