<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldSizes extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table      = 'im_sizes';
    protected $primaryKey = 'id';


    protected $fillable = [
        'id',
        'dimensions',
        'size_name',
        'size_width ',
        'size_height',
    ];
}
