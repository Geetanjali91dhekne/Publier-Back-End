<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Publisher as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens;



class WriteAccessPublisher extends Model
//class Publisher extends Authenticatable
{
    // use HasFactory;
    use HasApiTokens, Notifiable;

    protected $connection = 'hre_publir_write';
    protected $table = 'dt_publisher';
    protected $primaryKey = 'id';

    public $timestamps     = false;


    protected $fillable = [
        'id',
        'publisher_id',
        'full_name',
        'email',
        'password',
        'referral_code',
        'publisher_type',
        'access_type',
        'show_network_level_data',
        'verification_link',
        'reset_password_link',
        'profile_pic',
        'parent_gam_id',
        'gam_api_name',
        'gam_api_email',
        'gam_api_passcode',
        'gam_api_status',
        'created_at',
        'verified_at',
        'lastlogin_at',
        'status',
        'updated_at',

    ];

    protected $hidden = [
        'password',
        'jwt_token'
    ];



    // public function getAuthPassword()
    // {
    //     return $this->password;
    // }
}