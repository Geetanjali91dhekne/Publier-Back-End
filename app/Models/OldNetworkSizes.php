<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldNetworkSizes extends Model
{
    use HasFactory;
    // protected $connection = 'mysql2';
    protected $connection = 'imcustom2_write';
    protected $table = 'im_networks_sizes';
    protected $primaryKey = 'id';


    protected $fillable = [
        'network_id',
        'size_id',
        'size_alias'
    ];
}