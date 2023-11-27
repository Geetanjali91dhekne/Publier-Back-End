<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class PageviewsHourlyReports extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'im_pageviews_hourly_reports';
    protected $guarded = [];
}
