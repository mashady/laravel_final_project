<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->unsignedTinyInteger('number_of_beds')->nullable()->after('price');
            $table->unsignedTinyInteger('number_of_bathrooms')->nullable()->after('number_of_beds');
            $table->string('area', 100)->nullable()->after('number_of_bathrooms');
            $table->string('street', 100)->nullable()->after('area');
            $table->string('block', 50)->nullable()->after('street');
            $table->dropColumn('location');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn([
                'number_of_beds',
                'number_of_bathrooms',
                'area',
                'street',
                'block'
            ]);
        });
    }
};