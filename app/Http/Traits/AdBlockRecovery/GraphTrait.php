<?php

namespace App\Http\Traits\AdBlockRecovery;

use App\Models\AdblockReports;
use App\Models\AdblockWidgetReports;
use App\Models\OldPageviewsDailyReports;
use App\Models\PageviewsDailyReports;
use Illuminate\Support\Facades\DB;


trait GraphTrait
{
    public function adBlockPvsGraphData($startDate, $endDate, $siteId = null, $widgetId = null)
    {
        if($widgetId) {
            $adblockPv = AdblockWidgetReports::query();
            $adblockPv = $adblockPv->where('site_id', $siteId)
                ->where('widget_id', $widgetId)
                ->whereBetween('date', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as date'),
                    DB::raw('SUM(pageviews) as sum_pageview')
                )
                ->groupBy('date')
                ->get()->toArray();
            return $adblockPv;
        }
        $oldPageview = OldPageviewsDailyReports::query();
        if ($siteId) $oldPageview = $oldPageview->where('site_id', $siteId);
        $oldPageview = $oldPageview
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as date'),
                DB::raw('SUM(dt_pageviews_daily_reports.adblock_pageviews) as sum_pageview')
            )
            ->groupBy('date')
            ->get()->toArray();

        $pageviewData = PageviewsDailyReports::query();
        if ($siteId) $pageviewData = $pageviewData->where('site_id', $siteId);
        $pageviewData = $pageviewData
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as date'),
                DB::raw('ifnull(SUM(dt_pageviews_daily_reports.adblock_pageviews), 0) AS sum_pageview'),
            )
            ->groupBy('date')
            ->get()->toArray();

        $bothArr = array_merge($oldPageview, $pageviewData);
        $newPageviewData = array();
        foreach ($bothArr as $val) {
            if (!isset($newPageviewData[$val['date']]))
                $newPageviewData[$val['date']] = $val;
            else
                $newPageviewData[$val['date']]['sum_pageview'] += $val['sum_pageview'];
        }
        return $newPageviewData;
    }

    public function getAdblockUserByWidgetId($startDate, $endDate, $siteId, $widgetId)
    {
        $adblock = AdblockWidgetReports::query();
        $adblock = $adblock->where('site_id', $siteId)
            ->where('widget_id', $widgetId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as date'),
                DB::raw('ifnull(SUM(pageviews), 0) AS sum_adblock'),
                DB::raw('SUM(pageviews) AS sum_pageview'),
            )
            ->groupBy('date')
            ->get()->toArray();

        // $totalPV = PageviewsDailyReports::query();
        // $totalPV = $totalPV->where('site_id', $siteId)
        //     ->whereBetween('date', [$startDate, $endDate])
        //     ->select(
        //         DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as date'),
        //         DB::raw('0 AS sum_adblock'),
        //         DB::raw('SUM(pageviews + subscription_pageviews) as sum_pageview')
        //     )
        //     ->groupBy('date')
        //     ->get()->toArray();

        // $bothArr = array_merge($adblock, $totalPV);
        // $adblockusersData = array();
        // foreach ($bothArr as $val) {
        //     if (!isset($adblockusersData[$val['date']]))
        //         $adblockusersData[$val['date']] = $val;
        //     else {
        //         $adblockusersData[$val['date']]['sum_pageview'] += $val['sum_pageview'];
        //         $adblockusersData[$val['date']]['sum_adblock'] += $val['sum_adblock'];
        //     }
        // }

        foreach ($adblock as $key => $sites) {
            $adblock[$key]['adblock_users'] = $sites['sum_pageview'] ? ($sites['sum_adblock'] / ($sites['sum_pageview'])) * 100 : 0;
        }
        return $adblock;
    }

    public function adBlockUsersGraphData($startDate, $endDate, $siteId = null, $widgetId = null)
    {
        if($widgetId) {
            $adblockUser = $this->getAdblockUserByWidgetId($startDate, $endDate, $siteId, $widgetId);
            return $adblockUser;
        }
        $oldPageview = OldPageviewsDailyReports::query();
        if ($siteId) $oldPageview = $oldPageview->where('site_id', $siteId);
        $oldPageview = $oldPageview
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as date'),
                DB::raw('SUM(dt_pageviews_daily_reports.pageviews + dt_pageviews_daily_reports.subscription_pageviews + dt_pageviews_daily_reports.adblock_pageviews) as sum_pageview'),
                DB::raw('SUM(dt_pageviews_daily_reports.adblock_pageviews) as sum_adblock')
            )
            ->groupBy('date')
            ->get()->toArray();

        $newPageview = PageviewsDailyReports::query();
        if ($siteId) $newPageview = $newPageview->where('site_id', $siteId);
        $newPageview = $newPageview
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as date'),
                DB::raw('SUM(dt_pageviews_daily_reports.pageviews + dt_pageviews_daily_reports.subscription_pageviews + dt_pageviews_daily_reports.adblock_pageviews) as sum_pageview'),
                DB::raw('SUM(dt_pageviews_daily_reports.adblock_pageviews) as sum_adblock')
            )
            ->groupBy('date')
            ->get()->toArray();

        $bothArr = array_merge($oldPageview, $newPageview);
        $adblockusersData = array();
        foreach ($bothArr as $val) {
            if (!isset($adblockusersData[$val['date']]))
                $adblockusersData[$val['date']] = $val;
            else {
                $adblockusersData[$val['date']]['sum_pageview'] += $val['sum_pageview'];
                $adblockusersData[$val['date']]['sum_adblock'] += $val['sum_adblock'];
            }
        }

        foreach ($adblockusersData as $key => $sites) {
            $adblockusersData[$key]['adblock_users'] = $sites['sum_pageview'] ? ($sites['sum_adblock'] / $sites['sum_pageview']) * 100 : 0;
        }

        // Sort the array
        usort($adblockusersData, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $adblockusersData;
    }
}
