<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MockUp extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_mockup';
    protected $guarded = [];

    public function mockupDocuments()
    {
        return $this->hasMany(MockUpDocument::class, 'mockup_id', 'id');
    }
}
