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
            $table->boolean('favourite')->default(0)->after('manual_change');
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
            $table->dropColumn('favourite');
        });
    }
};
