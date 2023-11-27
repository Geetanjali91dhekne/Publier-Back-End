<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class WriteAccessSites extends Model
{
    use HasFactory;

    protected $connection = 'hre_publir_write';
    protected $table = 'dt_sites';
    protected $primaryKey = 'site_id';


    protected $fillable = [
        'site_name',
        'site_url ',
        'publisher_id',
        'favourite_by_user_ids',
    ];
}