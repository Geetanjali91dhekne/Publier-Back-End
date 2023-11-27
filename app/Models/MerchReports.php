<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MerchReports extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_merch_reports';
    protected $guarded = [];
}
