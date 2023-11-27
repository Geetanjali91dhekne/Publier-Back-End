<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_billing';
    protected $guarded = [];
}
