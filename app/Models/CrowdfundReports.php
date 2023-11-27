<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class CrowdfundReports extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_crowdfund_reports';
    protected $guarded = [];
}
