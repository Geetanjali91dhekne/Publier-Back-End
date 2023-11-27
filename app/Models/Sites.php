<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Sites extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'dt_sites';
    protected $primaryKey = 'site_id';

    protected $guarded = [];

    public function sellerData()
    {
        return $this->hasOne(ManageSellers::class, 'site_id', 'site_id');
    }

    public function accountManager()
    {
        return $this->hasOne(Publisher::class, 'publisher_id', 'account_manager_id')->select(['publisher_id', 'full_name', 'email']);
    }

    public function publisherDetails()
    {
        return $this->hasOne(Publisher::class, 'publisher_id', 'publisher_id')
            ->select(
                'dt_publisher.publisher_id', 'full_name', 'email', 'publisher_type', 'dt_publisher.created_at', 'dt_publisher.status', 'dt_publisher.id as pub_main_id', 'business_name',
                DB::raw('DATE_FORMAT(dt_publisher.created_at, "%Y-%m-%d, %r") as date'),
            );
    }

    public function generalMockup()
    {
        return $this->hasOne(MockUp::class, 'site_id', 'site_id');
    }

    public function generalBilling()
    {
        return $this->hasOne(Billing::class, 'site_id', 'site_id');
    }

    public function generalAgreement()
    {
        return $this->hasOne(Agreement::class, 'site_id', 'site_id');
    }
}