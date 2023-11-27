<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldDailyReport extends Model
{
    use HasFactory;
    protected $connection = 'imcustom2_write';
    protected $table = 'im_daily_reports';
    protected $primaryKey = 'id';


    protected $fillable = [
        'date',
        'site_id',
        'network_id',
        'size_id',
        'impressions',
        'revenue',
        'clicks',
        'status',
        'type',
        'manual_change'
    ];

}