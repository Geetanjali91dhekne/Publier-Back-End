<?php

namespace App\Http\Traits;

use App\Models\DailyReport;
use App\Models\GamReport;
use App\Models\OldDailyReport;
use App\Models\OldGamReport;
use App\Models\OldPageviewsDailyReports;
use App\Models\PageviewsDailyReports;
use Illuminate\Support\Facades\DB;

trait SiteDetailsTrait
{

    public function getSitesReportQuery($startDate, $endDate, $siteId) {

        if ($siteId <= 1000) {
            $sitesReport = OldDailyReport::where('site_id', $siteId);
        } else {
            $sitesReport = DailyReport::where('site_id', $siteId);
        }

        $sitesReport = $sitesReport->whereBetween('date', [$startDate, $endDate]);
        return $sitesReport;
    }

    public function getRequestGamReportQuery($startDate, $endDate, $site_id) {
        $requestReportData = OldGamReport::where('site_id', $site_id)->where('type', 'ADX')->whereBetween('date', [$startDate, $endDate]);
        return $requestReportData;
    }

    public function getNewRequestGamReportQuery($startDate, $endDate, $site_id) {
        $requestReportData = GamReport::where('site_id', $site_id)->where('type', 'ADX')->whereBetween('date', [$startDate, $endDate]);
        return $requestReportData;
    }

    public function getDateReportQuery($startDate, $endDate, $siteId, $clientPer)
    {
        if ($siteId >= 1000) {
            $pageview = PageviewsDailyReports::where('site_id', $siteId);
        } else {
            $pageview = OldPageviewsDailyReports::where('site_id', $siteId);
        }
        $pageview = $pageview->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('DATE_FORMAT(date, "%m-%d-%Y") as date'), DB::raw('ifnull(SUM(pageviews), 0) AS total_pageviews'))
            ->groupBy('date')
            ->get()->toArray();

        $dbReportData = $this->getSitesReportQuery($startDate, $endDate, $siteId);
        $dbReportData = $dbReportData->groupBy('date')
            ->select([
                DB::raw('DATE_FORMAT(date, "%m-%d-%Y") as date'),
                DB::raw('ifnull(SUM(total_request), 0) AS sum_ad_request'),
                DB::raw('ifnull(SUM(impressions), 0) as sum_impressions'),
                DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'),
                DB::raw('ifnull(1000 * (SUM(revenue) * ' . $clientPer . ')/SUM(impressions), 0) as sum_cpms'),
                // DB::raw('100 * SUM(im_daily_reports.impressions)/SUM(im_daily_reports.total_request) as sum_fillrate'),
            ])
            ->from('im_daily_reports')
            ->get()->toArray();
        
        $pageviewData = array_replace_recursive(array_combine(array_column($pageview, "date"), $pageview));
        foreach ($dbReportData as $key => $sites) {
            if (array_key_exists($sites['date'], $pageviewData)) {
                $pageviewArr = $pageviewData[$sites['date']];
                $pageview = (float) $pageviewArr['total_pageviews'];
                $dbReportData[$key]['pageview'] = $pageview;
                $dbReportData[$key]['sum_rpm'] = $pageview ? ($sites['sum_revenue'] * 1000) / $pageview : '0';
            } else {
                $dbReportData[$key]['pageview'] = '0';
                $dbReportData[$key]['sum_rpm'] = '0';
            }
        }

        if($siteId >= 1000) {
            $gamReportReq = $this->getNewRequestGamReportQuery($startDate, $endDate, $siteId);
        } else {
            $gamReportReq = $this->getRequestGamReportQuery($startDate, $endDate, $siteId);
        }
        $gamReportReq = $gamReportReq->groupBy('date')
            ->select('site_id',
                DB::raw('DATE_FORMAT(date, "%m-%d-%Y") as date'),
                DB::raw('ifnull(SUM(total_request), 0) as sum_ad_request')
            )->get()->toArray();

        $gamReportReq = array_replace_recursive(array_combine(array_column($gamReportReq, "date"), $gamReportReq));
        foreach ($dbReportData as $key => $sites) {
            if (array_key_exists($sites['date'], $gamReportReq)) {
                $gamReq = $gamReportReq[$sites['date']];
                $adRequest = (float) $gamReq['sum_ad_request'];
                $dbReportData[$key]['sum_ad_request'] += $adRequest;
            }
        }
        foreach($dbReportData as $key => $sites) {
            $dbReportData[$key]['sum_fillrate'] = $sites['sum_ad_request'] ? ($sites['sum_impressions'] / $sites['sum_ad_request']) * 100 : '0';
        }
        $dbReportData = array_values($dbReportData);
        return $dbReportData;
    }

    public function getNetworkTableReportQuery($startDate, $endDate, $siteId, $clientPer)
    {
        if ($siteId >= 1000) {
            $pageviewQuery = PageviewsDailyReports::where('site_id', $siteId)->whereBetween('date', [$startDate, $endDate]);
        } else {
            $pageviewQuery = OldPageviewsDailyReports::where('site_id', $siteId)->whereBetween('date', [$startDate, $endDate]);
        }
        $pageview = $pageviewQuery->sum('pageviews');

        if ($siteId <= 1000) {
            $networkData = OldDailyReport::where('im_daily_reports.site_id', $siteId);
            $networkData = $networkData->whereBetween('im_daily_reports.date', [$startDate, $endDate])->groupBy('im_daily_reports.network_id')
                ->join('im_networks', 'im_daily_reports.network_id', '=', 'im_networks.id')
                ->select([
                    'im_networks.network_name',
                    'im_daily_reports.network_id',
                    'im_daily_reports.site_id',
                    DB::raw('0 as sum_ad_request'),
                    DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'),
                    DB::raw('SUM(impressions) AS sum_impressions'),
                    DB::raw('ifnull(1000 * (SUM(revenue) * ' . $clientPer . ')/SUM(impressions), 0) as sum_cpms'),
                    // DB::raw('ifnull(100 * SUM(impressions)/SUM(total_request), 0) as sum_fillrate'),
                    DB::raw('ifnull((1000 * SUM(revenue * ' . $clientPer . '))/' . $pageview . ', 0) as sum_rpm'),
                ])
                ->from('im_daily_reports')->get()->toArray();
        } else {
            $networkData = DailyReport::where('im_daily_reports.site_id', $siteId);
            $networkData =  $networkData->whereBetween('im_daily_reports.date', [$startDate, $endDate])->groupBy('im_daily_reports.network_id');
            $networkData = $networkData
                ->join('dt_networks', 'im_daily_reports.network_id', '=', 'dt_networks.id')
                ->select([
                    'dt_networks.network_name',
                    'im_daily_reports.network_id',
                    'im_daily_reports.site_id',
                    DB::raw('0 as sum_ad_request'),
                    DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'),
                    DB::raw('SUM(impressions) AS sum_impressions'),
                    DB::raw('ifnull(1000 * (SUM(revenue) * ' . $clientPer . ')/SUM(impressions), 0) as sum_cpms'),
                    // DB::raw('100 * SUM(impressions)/SUM(total_request) as sum_fillrate'),
                    DB::raw('ifnull((1000 * SUM(revenue * ' . $clientPer . '))/' . $pageview . ', 0) as sum_rpm'),
                ])
                ->from('im_daily_reports')->get()->toArray();
        }
        $networkIds = array_column($networkData, 'network_id');
        if($siteId >= 1000) {
            $gamReportReq = $this->getNewRequestGamReportQuery($startDate, $endDate, $siteId);
        } else {
            $gamReportReq = $this->getRequestGamReportQuery($startDate, $endDate, $siteId);
        }
        $gamReportReq = $gamReportReq->whereIn('network_id', $networkIds)
            ->select('site_id', 'network_id',
                DB::raw('DATE_FORMAT(date, "%m-%d-%Y") as date'),
                DB::raw('ifnull(SUM(total_request), 0) as sum_ad_request')
            )
            ->groupBy('network_id')->get()->toArray();

        $bothArr = array_merge($networkData, $gamReportReq);
        $adNetworkData = array();
        foreach ($bothArr as $val) {
            if (!isset($adNetworkData[$val['network_id']]))
                $adNetworkData[$val['network_id']] = $val;
            else
                $adNetworkData[$val['network_id']]['sum_ad_request'] += $val['sum_ad_request'];
        }
        foreach($adNetworkData as $key => $sites) {
            $adNetworkData[$key]['sum_fillrate'] = $sites['sum_ad_request'] ? ($sites['sum_impressions'] / $sites['sum_ad_request']) * 100 : '0';
        }
        return array_values($adNetworkData);
    }

    public function getSizesReportQuery($startDate, $endDate, $siteId, $clientPer)
    {
        if ($siteId >= 1000) {
            $pageviewQuery = PageviewsDailyReports::where('site_id', $siteId)->whereBetween('date', [$startDate, $endDate]);
        } else {
            $pageviewQuery = OldPageviewsDailyReports::where('site_id', $siteId)->whereBetween('date', [$startDate, $endDate]);
        }
        $pageview = $pageviewQuery->sum('pageviews');

        if ($siteId <= 1000) {
            $sizeData = OldDailyReport::where('im_daily_reports.site_id', $siteId);
            $sizeData = $sizeData->whereBetween('im_daily_reports.date', [$startDate, $endDate])
                ->join('im_sizes', 'im_daily_reports.size_id', '=', 'im_sizes.id')
                ->groupBy('im_daily_reports.size_id')
                ->select([
                    'im_sizes.dimensions',
                    'im_daily_reports.size_id',
                    'im_daily_reports.site_id',
                    DB::raw('0 AS sum_ad_request'),
                    DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as sum_revenue'),
                    DB::raw('SUM(impressions) AS sum_impressions'),
                    DB::raw('ifnull(1000 * (SUM(im_daily_reports.revenue) * ' . $clientPer . ')/SUM(im_daily_reports.impressions), 0) as sum_cpms'),
                    // DB::raw('100 * SUM(impressions)/SUM(total_request) as sum_fillrate'),
                    DB::raw('ifnull((1000 * SUM(revenue * ' . $clientPer . '))/' . $pageview . ', 0) as sum_rpm'),
                ])
                ->from('im_daily_reports')->get()->toArray();
        } else {
            $sizeData = DailyReport::where('im_daily_reports.site_id', $siteId);
            $sizeData = $sizeData->whereBetween('im_daily_reports.date', [$startDate, $endDate])
                ->join('dt_sizes', 'im_daily_reports.size_id', '=', 'dt_sizes.id')
                ->groupBy('im_daily_reports.size_id')
                ->select([
                    'dt_sizes.dimensions',
                    'im_daily_reports.size_id',
                    'im_daily_reports.site_id',
                    DB::raw('0 AS sum_ad_request'),
                    DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as sum_revenue'),
                    DB::raw('SUM(impressions) AS sum_impressions'),
                    DB::raw('ifnull(1000 * (SUM(im_daily_reports.revenue) * ' . $clientPer . ')/SUM(im_daily_reports.impressions), 0) as sum_cpms'),
                    // DB::raw('100 * SUM(impressions)/SUM(total_request) as sum_fillrate'),
                    DB::raw('ifnull((1000 * SUM(im_daily_reports.revenue * ' . $clientPer . '))/' . $pageview . ', 0) as sum_rpm'),
                ])
                ->from('im_daily_reports')->get()->toArray();
        }

        $sizeIds = array_column($sizeData, 'size_id');
        if($siteId >= 1000) {
            $gamReportReq = $this->getNewRequestGamReportQuery($startDate, $endDate, $siteId);
        } else {
            $gamReportReq = $this->getRequestGamReportQuery($startDate, $endDate, $siteId);
        }
        $gamReportReq = $gamReportReq->whereIn('size_id', $sizeIds)
            ->select('site_id', 'size_id', DB::raw('ifnull(SUM(total_request), 0) as sum_ad_request'))
            ->groupBy('size_id')->get()->toArray();

        $bothArr = array_merge($sizeData, $gamReportReq);
        $adSizeData = array();
        foreach ($bothArr as $val) {
            if (!isset($adSizeData[$val['size_id']]))
                $adSizeData[$val['size_id']] = $val;
            else
                $adSizeData[$val['size_id']]['sum_ad_request'] += $val['sum_ad_request'];
        }
        foreach($adSizeData as $key => $sites) {
            $adSizeData[$key]['sum_fillrate'] = $sites['sum_ad_request'] ? ($sites['sum_impressions'] / $sites['sum_ad_request']) * 100 : 0;
        }
        $adSizeData = array_values($adSizeData);
        return $adSizeData;
    }
}