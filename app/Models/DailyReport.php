<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class DailyReport extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'im_daily_reports';
    protected $primaryKey = 'id';


    protected $fillable = [
        'date',
        'site_id ',
        'network_id',
        'size_id',
        'impressions',
        'revenue',
        'clicks',
        'status',
        'type',
        'manual_change',
        'favourite',
        'total_request',
        'measurable_impressions',
        'elegible_impressions',
        'unfilled_impressions',
        'code_served'
    ];
}