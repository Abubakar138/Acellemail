<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomFieldsToSubscribers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $fields = \Acelle\Model\Field::all();
        foreach($fields as $field) {
            \Acelle\Model\Subscriber::addCustomFieldIfNotExist($field->custom_field_name);
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
