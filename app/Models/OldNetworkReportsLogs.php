<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldNetworkReportsLogs extends Model
{
    use HasFactory;
    protected $connection = 'imcustom2_write';
    protected $table = 'dt_network_reports_logs';
    protected $primaryKey = 'id';


    protected $fillable = [
        'date',
        'site_id',
        'network_id',
        'size_id',
        'impressions',
        'revenue',
        'clicks',
        'type'
      
    ];

}
