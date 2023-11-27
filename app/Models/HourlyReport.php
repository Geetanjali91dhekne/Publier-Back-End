<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class HourlyReport extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'im_gam_hourly_reports';
    protected $guarded = [];
}
