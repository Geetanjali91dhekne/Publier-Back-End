<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdTags extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_ad_tags';
    protected $guarded = [];
}
