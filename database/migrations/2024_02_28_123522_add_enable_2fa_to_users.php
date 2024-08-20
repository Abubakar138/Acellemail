<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnable2faToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('enable_2fa')->default(false);
            $table->boolean('enable_2fa_email')->default(true);
            $table->boolean('enable_2fa_google_authenticator')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('enable_2fa');
            $table->dropColumn('enable_2fa_email');
            $table->dropColumn('enable_2fa_google_authenticator');
        });
    }
}
