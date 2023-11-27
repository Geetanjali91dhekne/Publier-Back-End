<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldGamReport extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'dt_gam_reports';
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
        'total_request',
        'measurable_impressions',
        'elegible_impressions',
        'unfilled_impressions',
        'code_served'
    ];

}
