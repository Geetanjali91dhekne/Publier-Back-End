<?php

namespace App\Http\Traits\QuickShop;

use App\Models\MerchReports;
use App\Models\MrOrders;

use Illuminate\Support\Facades\DB;

trait TopCardTrait
{
    public function getEarningsGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $quickEarnData = MrOrders::query();
        if ($siteId) $quickEarnData = $quickEarnData->where('site_id', $siteId);
        $quickEarnData = $quickEarnData->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'site_id',
                DB::raw('SUM(amount) * ' . $clientPer . ' as sum_revenue'),
                DB::raw('COUNT(quantity) as sum_items'),
            )
            ->groupBy('created_at')
            ->get();
        return $quickEarnData;
    }

    public function getProductPvsGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $quickProductData = MerchReports::query();
        if ($siteId) $quickProductData = $quickProductData->where('site_id', $siteId);
        $quickProductData = $quickProductData->whereBetween('date', [$startDate, $endDate])
            ->select(
                'date',
                DB::raw('SUM(product_pageviews) as sum_product'),
            )
            ->groupBy('date')
            ->get();
        return $quickProductData;
    }



    public function getQuickShopTopCardQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $totalEar = $this->getEarningsGraphData($startDate, $endDate, $siteId);
        $totalRev = $totalEar->sum('sum_revenue');
        $totalItem = $totalEar->sum('sum_items');
        $avgpurchase = $totalItem ? ($totalRev / $totalItem) : 0;
        $totalProduct = $this->getProductPvsGraphData($startDate, $endDate, $siteId);
        $productPvs = $totalProduct->sum('sum_product');
        $converstionRatio = $productPvs ? (($totalRev * 100) / $productPvs) : 0;

        /* previous period data */
        $previousEarnData = $this->getEarningsGraphData($oldStartDate, $oldEndDate, $siteId);
        $previousTotalRev = $previousEarnData->sum('sum_revenue');
        $previousTotalItem = $previousEarnData->sum('sum_items');
        $previousAvgPurchase = $previousTotalItem ? ($previousTotalRev / $previousTotalItem) : 0;

        $totalProduct = $this->getProductPvsGraphData($startDate, $endDate, $siteId);
        $previousProductPvs = $totalProduct->sum('sum_product');
        $previousconverstionRatio = $previousProductPvs ? (($previousTotalRev * 100) / $previousProductPvs) : 0;


        /* percentage calculation */
        $quickRevenuePer = $previousTotalRev ? ($totalRev - $previousTotalRev) * 100 / $previousTotalRev : 0;
        $totalItemSoldPer = $previousTotalItem ? ($totalItem - $previousTotalItem) * 100 / $previousTotalItem : 0;
        $totalAvgPurchasePer = $previousAvgPurchase ? ($avgpurchase - $previousAvgPurchase) * 100 / $previousAvgPurchase : 0;
        $totalPrductPvsPer = $previousProductPvs ? ($productPvs - $previousProductPvs) * 100 / $previousProductPvs : 0;
        $totalConverstionRatioPer = $previousconverstionRatio ? ($converstionRatio - $previousconverstionRatio) * 100 / $previousconverstionRatio : 0;

        $topCardData = [
            'total_revenue' => $totalRev, 'previous_total_revenue' => $previousTotalRev, 'total_rev_percentage' => $quickRevenuePer,
            'total_item_sold' => $totalItem, 'previous_total_item_sold' => $previousTotalItem, 'total_item_sold_percentage' => $totalItemSoldPer,
            'total_avg_purchase' => $avgpurchase, 'previous_total_avg_purchase' => $previousAvgPurchase, 'total_avg_purchase_percentage' => $totalAvgPurchasePer,
            'total_product_pvs' => $productPvs, 'previous_total_product_pvs' => $previousProductPvs, 'total_product_pvs_percentage' => $totalPrductPvsPer,
            'total_converstion_ratio' => $converstionRatio, 'previous_total_converstion_ratio' => $previousconverstionRatio, 'total_converstion_ratio_percentage' => $totalConverstionRatioPer,
        ];
        return $topCardData;
    }
}
