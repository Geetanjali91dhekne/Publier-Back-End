<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class WriteAccessOldSites extends Model
{
    use HasFactory;

    protected $connection = 'imcustom2_write';
    protected $table = 'im_sites';
    protected $primaryKey = 'id';


    protected $fillable = [
        'id',
        'site_name',
        'site_url ',
        'publisher_id',
        'favourite_by_user_ids',

    ];
}