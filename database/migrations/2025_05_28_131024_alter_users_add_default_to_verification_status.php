<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersAddDefaultToVerificationStatus extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Ensure the column exists before altering
            $table->string('verification_status')
                  ->default('pending')
                  ->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove the default (you can adjust this to what it was originally)
            $table->string('verification_status')->default(null)->change();
        });
    }
}
