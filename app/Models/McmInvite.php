<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class McmInvite extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_mcm_invite';
    protected $guarded = [];
}
