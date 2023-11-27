<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralCustomTask extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_general_custom';
    protected $guarded = [];

    public function customDocuments()
    {
        return $this->hasMany(GeneralCustomDocument::class, 'general_custom_id', 'id');
    }

}
