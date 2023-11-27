<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class SubUsersLogs extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'im_subs_logs';
    protected $guarded = [];
}
