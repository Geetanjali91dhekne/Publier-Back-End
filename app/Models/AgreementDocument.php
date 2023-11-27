<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementDocument extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_agreement_document';
    protected $guarded = [];
}
