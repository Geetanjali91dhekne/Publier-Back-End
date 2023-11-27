<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PublirProducts extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_publir_products';
    protected $guarded = [];
}
