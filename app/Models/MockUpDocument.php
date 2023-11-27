<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MockUpDocument extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_mockup_document';
    protected $guarded = [];
}
