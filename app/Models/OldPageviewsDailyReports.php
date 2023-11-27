<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class OldPageviewsDailyReports extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';
    protected $table = 'dt_pageviews_daily_reports';
    protected $primaryKey = 'id';


    protected $fillable = [
        'site_id ',
        'date',
        'pageviews',
        'adblock_pageviews',
        'subscription_pageviews',
        'created_time',
    ];
}