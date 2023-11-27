<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdBlockSchedules extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'hre_publir_write';
    protected $table = 'hre_adblock_schedules';
    protected $guarded = [];

    protected $dates = ['deleted_at'];
}
