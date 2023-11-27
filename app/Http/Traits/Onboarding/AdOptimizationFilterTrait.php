<?php

namespace App\Http\Traits\Onboarding;

use App\Models\PrebidVersion;
use App\Models\PublirProducts;
use App\Models\Publisher;
use App\Models\Sites;

trait AdOptimizationFilterTrait
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
        $nameData = $nameData->select('full_name', 'publisher_id')->get()->toArray();
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
        $accountData = $accountData->select('full_name', 'publisher_id')->where('publisher_type', 'super_admin')->get()->toArray();
        return $accountData;
    }
}