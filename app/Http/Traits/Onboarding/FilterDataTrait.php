<?php

namespace App\Http\Traits\Onboarding;

use App\Models\PrebidVersion;
use App\Models\PublirProducts;
use App\Models\Publisher;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;

trait FilterDataTrait
{
    public function getSitesListQuery()
    {
        $listData = Sites::query();
        $listData = $listData->where('status', 'Y')->select('site_name', 'site_id')->get()->toArray();
        return $listData;
    }

    public function getLiveProductListQuery()
    {
        $productData = PublirProducts::query();
        $productData = $productData->where('status', 'A')->select('id', 'product_name')->get()->toArray();
        return $productData;
    }

    public function getPublisherListQuery()
    {
        $nameData = Publisher::query();
        $nameData = $nameData
            ->with('publisherSites')
            ->select(
                'dt_publisher.id', 'full_name', 'publisher_id', 'email', 'business_name',
                DB::raw('DATE_FORMAT(updated_at, "%Y-%m-%d %h:%i %p") as date_updated'),
            )
            ->get()->toArray();
        return $nameData;
    }

    public function getPrebidVersionListQuery()
    {
        $prebidData = PrebidVersion::query();
        $prebidData = $prebidData->select('version')->get()->toArray();
        return $prebidData;
    }

    public function getAccountListQuery()
    {
        $accountData = Publisher::query();
        $accountData = $accountData
            ->where('publisher_type', 'super_admin')
            ->join('dt_sites', 'dt_sites.publisher_id', '=', 'dt_publisher.publisher_id', 'left outer')
            ->select(
                'dt_publisher.id', 'full_name', 'dt_publisher.publisher_id', 'email',
                DB::raw('COUNT(dt_sites.publisher_id) as website')
            )
            ->groupBy('dt_publisher.publisher_id')
            ->get()->toArray();
        return $accountData;
    }
}