<?php

namespace App\Http\Traits\QuickShop;

use App\Models\MerchReports;
use App\Models\MrOrders;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;




trait GraphTrait
{

    public function totalEarningsGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $quickEarnData = MrOrders::query();
        if ($siteId) $quickEarnData = $quickEarnData->where('site_id', $siteId);
        $quickEarnData = $quickEarnData
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as date'),
                DB::raw('SUM(amount) * ' . $clientPer . ' as sum_earnings'),
            )
            ->groupBy('created_at')
            ->get()->toArray();

        $quickEarn = [];
        foreach ($quickEarnData as $val) {
            if (!isset($quickEarn[$val['date']])) {
                $quickEarn[$val['date']] = $val;
            } else {
                $quickEarn[$val['date']]['sum_earnings'] += $val['sum_earnings'];
            }
        }
        return $quickEarn;
    }

    public function itemSoldGraphData($startDate, $endDate, $siteId = null)
    {
        $quickItemsData = MrOrders::query();
        if ($siteId) $quickItemsData = $quickItemsData->where('site_id', $siteId);
        $quickItemsData = $quickItemsData
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as date'),
                DB::raw('COUNT(quantity) as sum_items'),
            )
            ->groupBy('created_at')
            ->get()->toArray();

        $quickItems = [];
        foreach ($quickItemsData as $val) {
            if (!isset($quickItems[$val['date']])) {
                $quickItems[$val['date']] = $val;
            } else {
                $quickItems[$val['date']]['sum_items'] += $val['sum_items'];
            }
        }

        return $quickItems;
    }

    public function purchaseValueGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $quickPurchaseData = MrOrders::query();
        if ($siteId) $quickPurchaseData = $quickPurchaseData->where('site_id', $siteId);
        $quickPurchaseData = $quickPurchaseData
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as date'),
                DB::raw('SUM(amount) * ' . $clientPer . ' as sum_revenue'),
                DB::raw('COUNT(quantity) as sum_items'),
                
            )
            ->groupBy('created_at')
            ->get()->toArray();

        $quickPurchase = [];
        foreach ($quickPurchaseData as $val) {
            if (!isset($quickPurchase[$val['date']])) {
                $quickPurchase[$val['date']] = $val;
            } else {
                $quickPurchase[$val['date']]['sum_revenue'] += $val['sum_revenue'];
                $quickPurchase[$val['date']]['sum_items'] += $val['sum_items'];
            }
        }
        // echo"<pre>";
        // print_r

        foreach ($quickPurchase as $key => $avg) {
            $quickPurchase[$key]['total_avg_purchase'] = $avg['sum_items'] ? ($avg['sum_revenue'] / $avg['sum_items']) : 0;
        }


        return $quickPurchase;
    }

    public function productPvsGraphData($startDate, $endDate, $siteId = null)
    {
        $quickProductData = MerchReports::query();
        if ($siteId) $quickProductData = $quickProductData->where('site_id', $siteId);
        $quickProductData = $quickProductData
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as date'),
                DB::raw('SUM(product_pageviews) as sum_product_pvs'),
            )
            ->groupBy('date')
            ->get()->toArray();

        $quickProduct = [];
        foreach ($quickProductData as $val) {
            if (!isset($quickProduct[$val['date']])) {
                $quickProduct[$val['date']] = $val;
            } else {
                $quickProduct[$val['date']]['sum_product_pvs'] += (float)$val['sum_product_pvs'];
            }
        }
        return $quickProduct;
    }

    public function converstionRatioGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $quickConverstionData = MrOrders::query();
        if ($siteId) $quickConverstionData = $quickConverstionData->where('dt_mr_orders.site_id', $siteId);
        $quickConverstionData = $quickConverstionData->whereBetween('dt_mr_orders.created_at', [$startDate, $endDate])
            ->join('dt_merch_reports', 'dt_mr_orders.site_id', '=', 'dt_merch_reports.site_id')
            ->select(
                DB::raw('SUM(dt_mr_orders.amount) * ' . $clientPer . ' as sum_revenue'),
                DB::raw('SUM(dt_merch_reports.product_pageviews) as sum_page_views'),
            )
            ->groupBy('dt_mr_orders.created_at')
            ->get();

        $quickConverstion = [];
        foreach ($quickConverstionData as $val) {
            if (!isset($quickConverstion[$val['date']])) {
                $quickConverstion[$val['date']] = $val;
            } else {
                $quickConverstion[$val['date']]['sum_product_pvs'] += $val['sum_product_pvs'];
                $quickConverstion[$val['date']]['sum_revenue'] += $val['sum_revenue'];
            }
        }

        foreach ($quickConverstion as $key => $ratio) {
            $quickConverstion[$key]['total_converstion_ratio'] = $ratio['sum_page_views'] ? (($ratio['sum_revenue'] * 100) / $ratio['sum_page_views']) : 0;
        }
        return $quickConverstion;
    }

    public function getCountriesGraphQuery($startDate, $endDate, $clientPer, $siteId = null)
    {
        $quickCountriesGraph = MrOrders::query();
        if ($siteId) $quickCountriesGraph = $quickCountriesGraph->where('site_id', $siteId);
        $quickCountriesGraph = $quickCountriesGraph->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'country',
                DB::raw('SUM(amount) * ' . $clientPer . ' as sum_earning'),
            )
            ->groupBy('country')
            ->get();

        return $quickCountriesGraph;
    }
}