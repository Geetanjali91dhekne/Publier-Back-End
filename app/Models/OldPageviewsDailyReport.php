<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldPageviewsDailyReport extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'dt_pageviews_daily_reports';
    protected $primaryKey = 'id';

    protected $guarded = [];
}
