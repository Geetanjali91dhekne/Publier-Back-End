<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralCustomDocument extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_general_custom_document';
    protected $guarded = [];
}
