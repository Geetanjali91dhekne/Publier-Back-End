<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteTempReports extends Model
{
    use HasFactory;
    protected $connection = 'hre_publir_write';
    protected $table = 'site_temp_reports';
    protected $primaryKey = 'id';

    protected $guarded = [];
}
