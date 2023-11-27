<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class OldSubscriptionsReports extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'dt_subscription_reports';
    protected $guarded = [];
}
