<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->integer('measurable_impressions')->default(0)->after('total_request');
            $table->integer('elegible_impressions')->default(0)->after('measurable_impressions');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->after('elegible_impressions');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->after('created_at');
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
            $table->dropColumn('measurable_impressions');
            $table->dropColumn('elegible_impressions');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });
    }
};
