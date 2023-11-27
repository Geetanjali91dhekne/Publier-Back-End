<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;




class Sites extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_sites';
    protected $primaryKey = 'site_id';

    protected $guarded = [];

    public function seller_data()
    {
        return $this->hasOne(ManageSellers::class, 'site_id', 'site_id');
    }
}