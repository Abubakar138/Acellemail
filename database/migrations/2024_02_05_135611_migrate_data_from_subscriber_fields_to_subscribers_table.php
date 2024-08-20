<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateDataFromSubscriberFieldsToSubscribersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('subscriber_fields')) {
            return;
        }

        $fields = \Acelle\Model\Field::all();
        foreach ($fields as $field) {
            $raw = [ sprintf('UPDATE %s s INNER JOIN %s sf ON s.id = sf.subscriber_id AND sf.field_id = %s', table('subscribers'), table('subscriber_fields'), $field->id)];
            $raw[] = "SET s.{$field->custom_field_name} = sf.value";
            $rawSql = implode(' ', $raw);

            DB::statement($rawSql);
        } 

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
