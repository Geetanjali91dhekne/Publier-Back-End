<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManageSellers extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_manage_sellers';
    protected $guarded = [];

    public $timestamps = false;
}
