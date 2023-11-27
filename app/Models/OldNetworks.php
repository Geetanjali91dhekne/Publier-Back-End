<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldNetworks extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'im_networks';
    protected $primaryKey = 'id';


    protected $fillable = [
      
    ];

}
