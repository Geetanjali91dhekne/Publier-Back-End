<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class Networks extends Model
{
    use HasFactory;

    protected $table = 'dt_networks';
    protected $primaryKey = 'id';


    protected $fillable = [
        'id',
        'network_name',

    ];
}