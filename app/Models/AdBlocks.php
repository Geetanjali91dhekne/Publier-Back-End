<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdBlocks extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'hre_publir_write';
    protected $table = 'hre_adblock';
    protected $guarded = [];

    protected $dates = ['deleted_at'];

    protected function countries(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value),
        );
    }

    protected function browsers(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value),
        );
    }


    public function schedule_preset()
    {
        // return $this->hasMany(AdBlockSchedules::class, 'widget_id', 'widget_id');
        return $this->hasMany(AdBlockSchedules::class, 'widget_id', 'widget_id')->select(['widget_id', 'start_date', 'end_date']);
    }

    public function preset_style()
    {
        return $this->hasOne(AdBlockStyles::class, 'widget_id', 'widget_id');
    }
}
