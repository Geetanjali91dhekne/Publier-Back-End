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
        Schema::create('site_temp_reports', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->text('favourite_by_users');
            $table->integer('site_id');
            $table->integer('total_request');
            $table->double('total_request_percentage', 18, 2);
            $table->integer('total_impressions');
            $table->double('impressions_percentage', 18, 2);
            $table->double('net_total_revenue', 18, 2);
            $table->double('net_revenue_percentage', 18, 2);
            $table->double('gross_total_revenue', 18, 2);
            $table->double('gross_revenue_percentage', 18, 2);
            $table->double('net_total_cpms', 18, 2);
            $table->double('net_total_cpms_percentage', 18, 2);
            $table->double('gross_total_cpms', 18, 2);
            $table->double('gross_total_cpms_percentage', 18, 2);
            $table->double('total_fillrate', 18, 2);
            $table->double('total_fillrate_percentage', 18, 2);
            $table->string('time_interval');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_temp_reports');
    }
};
