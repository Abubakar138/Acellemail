<?php

/**
 * Blacklist class.
 *
 * Model for blacklisted email addresses
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   MVC Model
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Tool;
use Acelle\Library\StringHelper;
use DB;

use function Acelle\Helpers\read_csv;

class Blacklist extends Model
{
    public const IMPORT_TEMP_DIR = 'app/tmp';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'reason',
    ];

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::select('blacklists.*');
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('customer_id');
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public function delist($customer = null)
    {
        if (is_null($customer)) {
            $sql = sprintf('UPDATE %s SET status = %s WHERE status = %s AND email = %s', table('subscribers'), db_quote(Subscriber::STATUS_SUBSCRIBED), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote($this->email));
        } else {
            // slow: $sql = sprintf('UPDATE %s SET status = %s WHERE status = %s AND email = %s AND mail_list_id IN (SELECT id FROM %s WHERE customer_id = %s)', table('subscribers'), db_quote(Subscriber::STATUS_SUBSCRIBED), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote($this->email), table('mail_lists'), $customer->id);
            $sql = sprintf('UPDATE %s s INNER JOIN %s m ON m.id = s.mail_list_id SET s.status = %s WHERE s.status = %s AND s.email = %s AND m.customer_id = %s', table('subscribers'), table('mail_lists'), db_quote(Subscriber::STATUS_SUBSCRIBED), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote($this->email), $customer->id);
        }
        \DB::statement($sql);
    }

    /**
     * Blacklist all subscribers of the same email address.
     *
     * @return collect
     */
    public static function doBlacklist($customer = null)
    {
        $sql = sprintf('UPDATE %s s INNER JOIN %s b ON b.email = s.email SET status = %s WHERE s.status = %s AND b.customer_id IS NULL', table('subscribers'), table('blacklists'), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote(Subscriber::STATUS_SUBSCRIBED));
        \DB::statement($sql);

        // user wide blacklist
        if (!is_null($customer)) {
            $sql = sprintf('UPDATE %s s INNER JOIN %s b ON b.email = s.email INNER JOIN %s m ON m.id = s.mail_list_id SET s.status = %s WHERE s.status = %s AND m.customer_id = %s', table('subscribers'), table('blacklists'), table('mail_lists'), db_quote(Subscriber::STATUS_BLACKLISTED), db_quote(Subscriber::STATUS_SUBSCRIBED), $customer->id);
            \DB::statement($sql);
        }
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $user = $request->user();
        $query = self::select('blacklists.*');

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('blacklists.email', 'like', '%'.$keyword.'%');
                });
            }
        }

        // Other filter
        if (!empty($request->customer_id)) {
            $query = $query->where('blacklists.customer_id', '=', $request->customer_id);
        }

        if (!empty($request->admin_id)) {
            $query = $query->whereNull('customer_id');
        }

        return $query;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public static function search($request)
    {
        $query = self::filter($request);

        if (!empty($request->sort_order)) {
            $query = $query->orderBy($request->sort_order, $request->sort_direction);
        }

        return $query;
    }

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    /**
     * Import from file.
     *
     * @return collect
     */
    public static function import($file, $customer = null, $progressCallback = null)
    {
        $failed = 0;
        $processed = 0;

        list($headers, $total, $results) = read_csv($file);

        if (!is_null($progressCallback)) {
            $progressCallback($processed, $total, $failed, $message = 'Loading file content...');
        }

        $customerId = (is_null($customer)) ? null : $customer->id;

        each_batch($results, $batchSize = config('custom.import_batch_size'), $skipHeader = false, function ($batch) use ($customerId, &$processed, &$failed, $total, $progressCallback) {
            // Insert subscriber fields from the batch to the temporary table
            // extract only fields whose name matches TAG NAME of MailList

            $data = collect($batch)->map(function ($r) use ($customerId) {
                $record = [
                    'email' => $r[0],
                    'customer_id' => $customerId,
                ];

                // replace the non-break space (not a normal space) as well as all other spaces
                $record['email'] = strtolower(preg_replace('/[ \s*]*/', '', trim($record['email'])));

                // In certain cases, a UTF-8 BOM is added to email
                // For example: "﻿madxperts@gmail.com" (open with Sublime to see the char)
                // So we need to remove it, at least for email field
                $record['email'] = StringHelper::removeUTF8BOM($record['email']);

                return $record;
            })->toArray();


            // make the import data table unique by email
            $data = array_unique_by($data, function ($r) {
                return $r['email'];
            });

            // validate and filter out invalid records
            $data = array_where($data, function ($record) use (&$failed) {
                $valid = Tool::isValidEmail($record['email']);
                if (!$valid) {
                    $failed += 1;
                }

                return $valid;
            });

            $tmpTableName = static::createTmpTableForImporting();

            DB::table($tmpTableName)->insert($data);

            $insertSql = strtr(
                '

                INSERT INTO %blacklists (email, customer_id, created_at, updated_at)
                SELECT tmp.email, %customer_id, NOW(), NOW()
                FROM %tmp tmp
                LEFT JOIN %blacklists main ON (tmp.email = main.email)
                WHERE main.email IS NULL',
                [
                    '%blacklists' => table('blacklists'),
                    '%customer_id' => $customerId ?? 'NULL',
                    '%tmp' => table($tmpTableName),
                ]
            );

            // Actually INSERT
            DB::statement($insertSql);

            $processed += sizeof($batch);

            if (!is_null($progressCallback)) {
                $progressCallback($processed, $total, $failed, trans('messages.blacklist.import_process_running', ['processed' => $processed]));
            }
        });

        self::doBlacklist($customer);

        if (!is_null($progressCallback)) {
            $progressCallback($processed = $total, $total, $failed, trans('messages.blacklist.import_process_complete', ['processed' => $total]));
        }
    }

    public static function upload(\Illuminate\Http\UploadedFile $httpFile)
    {
        $filename = "blacklst-import-".uniqid().".txt";
        $path = storage_path(self::IMPORT_TEMP_DIR);

        // store it to storage/
        $httpFile->move($path, $filename);

        // Example of outcome: /home/acelle/storage/app/tmp/import-000000.csv
        $filepath = join_paths($path, $filename);

        return $filepath;
    }

    /**
     * Add email to admin blacklist.
     */
    public static function addEmaill($email)
    {
        $email = trim(strtolower($email));

        if (Tool::isValidEmail($email)) {
            $exist = self::global()->where('email', '=', $email)->count();

            if (!$exist) {
                $blacklist = new self();
                $blacklist->email = $email;
                $blacklist->save();
            }
        }
    }

    public static function createTmpTableForImporting()
    {
        $tmpTableName = '__tmp_blacklist';
        $tmpTable = table($tmpTableName);

        // @todo: hard-coded charset and COLLATE
        $sql = "CREATE TABLE {$tmpTable}(`email` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, `customer_id` INTEGER) ENGINE=InnoDB;";
        DB::statement("DROP TABLE IF EXISTS {$tmpTable};"); // Seperate queries
        DB::statement($sql);

        return $tmpTableName;
    }
}
