<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('im_daily_reports', function (Blueprint $table) {
            $table->integer('code_served')->default(0)->after('elegible_impressions');
            $table->integer('unfilled_impressions')->default(0)->after('code_served');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('im_daily_reports', function (Blueprint $table) {
            $table->dropColumn('code_served');
            $table->dropColumn('unfilled_impressions');
        });
    }
};
