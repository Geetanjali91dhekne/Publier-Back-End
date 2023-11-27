<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreatePrebidJs extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_create_prebid_js';
    protected $guarded = [];
}
