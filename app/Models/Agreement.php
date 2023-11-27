<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_agreement';
    protected $guarded = [];

    public function agreementDocuments()
    {
        return $this->hasMany(AgreementDocument::class, 'agreement_id', 'id');
    }

}
