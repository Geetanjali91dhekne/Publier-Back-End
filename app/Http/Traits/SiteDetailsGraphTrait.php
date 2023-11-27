<?php

namespace App\Http\Traits;

use App\Models\DailyReport;
use App\Models\GamReport;
use App\Models\OldDailyReport;
use App\Models\OldGamReport;
use App\Models\PageviewsDailyReports;
use Illuminate\Support\Facades\DB;

trait SiteDetailsGraphTrait
{
    use SiteDetailsTrait;

    public function getRequestsGraphQuery($startDate, $endDate, $site_id, $oldStartDate, $oldEndDate)
    {
        if ($site_id >= 1000) {
            /* new db request */
            $adReqData = $this->getNewRequestGamReportQuery($startDate, $endDate, $site_id);
            $adReqData = $adReqData->groupBy('date')->select('date', DB::raw('SUM(total_request) as total_request'))->get();

            /* previous period request data */
            $oldAdReqData = $this->getNewRequestGamReportQuery($oldStartDate, $oldEndDate, $site_id);
            $oldAdReqData = $oldAdReqData->groupBy('date')->select('date', DB::raw('SUM(total_request) as total_request'));
        } else {
            /* old db request */
            $adReqData = $this->getRequestGamReportQuery($startDate, $endDate, $site_id);
            $adReqData = $adReqData->groupBy('date')->select('date', DB::raw(DB::raw('SUM(total_request) as total_request')))->get();

            /* old db previous period data */
            $oldAdReqData = $this->getRequestGamReportQuery($oldStartDate, $oldEndDate, $site_id);
            $oldAdReqData = $oldAdReqData->groupBy('date')->select('date', DB::raw(DB::raw('SUM(total_request) as total_request')));
        }
        $totalRequest = $adReqData->sum('total_request');

        $resData['lables'] = $adReqData->pluck('date')->toArray();
        $resData['requests'] = $adReqData->pluck('total_request')->toArray();
        $resData['old_requests'] = $oldAdReqData->pluck('total_request')->toArray();
        $resData['old_lables'] = $oldAdReqData->pluck('date')->toArray();
        $resData['total_request'] = $totalRequest;
        return $resData;
    }

    public function getRevenueGraphQuery($startDate, $endDate, $site_id, $clientPer, $oldStartDate, $oldEndDate)
    {
        $adRevData = $this->getSitesReportQuery($startDate, $endDate, $site_id);
        $adRevData = $adRevData->groupBy('date')->select('date', DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue'))->get();
        $totalRevenue = $adRevData->sum('total_revenue');

        $oldAdRevData = $this->getSitesReportQuery($oldStartDate, $oldEndDate, $site_id);
        $oldAdRevData = $oldAdRevData->groupBy('date')->select('date', DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue'));

        $resData['lables'] = $adRevData->pluck('date')->toArray();
        $resData['revenue'] = $adRevData->pluck('total_revenue')->toArray();
        $resData['old_lables'] = $oldAdRevData->pluck('date')->toArray();
        $resData['old_revenue'] = $oldAdRevData->pluck('total_revenue')->toArray();
        $resData['total_revenue'] = $totalRevenue;
        return $resData;
    }

    public function getCpmsGraphQuery($startDate, $endDate, $site_id, $clientPer, $oldStartDate, $oldEndDate)
    {
        $adCpmData = $this->getSitesReportQuery($startDate, $endDate, $site_id);
        $adCpmData = $adCpmData->groupBy('date')->select(
            'date',
            DB::raw('1000 * (SUM(revenue) * ' . $clientPer . ')/SUM(impressions) as cpms'),
            DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue'),
            DB::raw('SUM(impressions) as total_impressions'),
        )->get();
        $total_revenue = $adCpmData->sum('total_revenue');
        $total_impressions = $adCpmData->sum('total_impressions');
        $totalCpm = $total_impressions ? 1000 * ($total_revenue / $total_impressions) : '0';

        $oldAdCpmData = $this->getSitesReportQuery($oldStartDate, $oldEndDate, $site_id);
        $oldAdCpmData = $oldAdCpmData->groupBy('date')->select('date', DB::raw('1000 * (SUM(revenue) * ' . $clientPer . ')/SUM(impressions) as cpms'));

        $resData['lables'] = $adCpmData->pluck('date')->toArray();
        $resData['cpm'] = $adCpmData->pluck('cpms')->toArray();
        $resData['old_lables'] = $oldAdCpmData->pluck('date')->toArray();
        $resData['old_cpm'] = $oldAdCpmData->pluck('cpms')->toArray();
        $resData['total_cpm'] = $totalCpm;
        return $resData;
    }

    public function getImpressionGraphQuery($startDate, $endDate, $site_id, $oldStartDate, $oldEndDate)
    {
        $adImpressionData = $this->getSitesReportQuery($startDate, $endDate, $site_id);
        $adImpressionData = $adImpressionData->groupBy('date')->select('date', DB::raw('SUM(impressions) as total_impressions'))->get();
        $totalImpressions = $adImpressionData->sum('total_impressions');

        $oldAdImpData = $this->getSitesReportQuery($oldStartDate, $oldEndDate, $site_id);
        $oldAdImpData = $oldAdImpData->groupBy('date')->select('date', DB::raw('SUM(impressions) as total_impressions'));

        $resData['lables'] = $adImpressionData->pluck('date')->toArray();
        $resData['impressions'] = $adImpressionData->pluck('total_impressions')->toArray();
        $resData['old_lables'] = $oldAdImpData->pluck('date')->toArray();
        $resData['old_impressions'] = $oldAdImpData->pluck('total_impressions')->toArray();
        $resData['total_impressions'] = $totalImpressions;
        return $resData;
    }

    public function fillComparisonGraphQuery($startDate, $endDate, $site_id)
    {
        $dbReportData = $this->getSitesReportQuery($startDate, $endDate, $site_id);
        $dbReportData = $dbReportData->groupBy('date')
            ->select(
                'date',
                'site_id',
                DB::raw('ifnull(SUM(impressions), 0) as impressions'),
                DB::raw('0 as total_request'),
                DB::raw('0 as unfilled_impressions'),
                DB::raw('0 as code_served'),
            )->get()->toArray();

        if ($site_id >= 1000) {
            $adReqData = $this->getNewRequestGamReportQuery($startDate, $endDate, $site_id);
        } else {
            $adReqData = $this->getRequestGamReportQuery($startDate, $endDate, $site_id);
        }
        $adReqData = $adReqData->groupBy('date')
            ->select(
                'date',
                DB::raw('SUM(total_request) as total_request'),
                DB::raw('SUM(unfilled_impressions) as unfilled_impressions'),
                DB::raw('SUM(code_served) as code_served'),
            )->get()->toArray();

        $bothArr = array_merge($dbReportData, $adReqData);
        $adReportData = array();
        foreach ($bothArr as $val) {
            if (!isset($adReportData[$val['date']])) {
                $adReportData[$val['date']] = $val;
            } else {
                $adReportData[$val['date']]['total_request'] = $val['total_request'];
                $adReportData[$val['date']]['unfilled_impressions'] = $val['unfilled_impressions'];
                $adReportData[$val['date']]['code_served'] = $val['code_served'];
            }
        }

        foreach ($adReportData as $key => $sites) {
            $adReportData[$key]['filled'] = $sites['total_request'] ? 100 * ($sites['impressions'] / $sites['total_request']) : 0;
            $adReportData[$key]['un_filled'] = $sites['total_request'] ? 100 * ($sites['unfilled_impressions'] / $sites['total_request']) : 0;
            $adReportData[$key]['unrendered'] = $sites['total_request'] ? 100 * (($sites['code_served'] - $sites['impressions']) / $sites['total_request']) : 0;
        }
        $resData['lables'] = array_column($adReportData, 'date');
        $resData['filled'] = array_column($adReportData, 'filled');
        $resData['un_filled'] = array_column($adReportData, 'un_filled');
        $resData['unrendered'] = array_column($adReportData, 'unrendered');
        return $resData;
    }

    public function getDemandChannelGraphQuery($startDate, $endDate, $siteId, $clientPer)
    {
        if ($siteId <= 1000) {
            $adDemandStatsData = OldDailyReport::where('im_daily_reports.site_id', $siteId);
            $adDemandStatsData = $adDemandStatsData->whereBetween('im_daily_reports.date', [$startDate, $endDate])->groupBy('im_daily_reports.network_id');
            $adDemandStatsData = $adDemandStatsData
                ->join('im_networks', 'im_daily_reports.network_id', '=', 'im_networks.id')
                ->select([
                    'im_networks.network_name',
                    'im_daily_reports.network_id',
                    'im_daily_reports.site_id',
                    DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'),
                    DB::raw('SUM(impressions) AS impressions'),
                ])
                ->from('im_daily_reports');
        } else {
            $adDemandStatsData = DailyReport::where('im_daily_reports.site_id', $siteId);
            $adDemandStatsData =  $adDemandStatsData->whereBetween('im_daily_reports.date', [$startDate, $endDate])->groupBy('im_daily_reports.network_id');
            $adDemandStatsData = $adDemandStatsData
                ->join('dt_networks', 'im_daily_reports.network_id', '=', 'dt_networks.id')
                ->select([
                    'dt_networks.network_name',
                    'im_daily_reports.network_id',
                    'im_daily_reports.site_id',
                    DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'),
                    DB::raw('SUM(impressions) AS impressions'),
                ])
                ->from('im_daily_reports');
        }
        $adDemandStatsData = $adDemandStatsData->get();
        $allRevenue = $adDemandStatsData->sum('sum_revenue');
        $allImpression = $adDemandStatsData->sum('impressions');
        foreach ($adDemandStatsData as $site) {
            $site->revenue_per = $allRevenue ? ($site->sum_revenue / $allRevenue) * 100 : 0;
            $site->impressions_per = $allImpression ? ($site->impressions / $allImpression) * 100 : 0;
        }
        return $adDemandStatsData;
    }

    public function getOldNetworkDemandGraphQuery($startDate, $endDate, $siteId, $networkIds, $clientPer)
    {
        if ($siteId <= 1000) {
            $sitesReport = OldDailyReport::where('site_id', $siteId)->whereIn('network_id', $networkIds);
        } else {
            $sitesReport = DailyReport::where('site_id', $siteId)->whereIn('network_id', $networkIds);
        }

        $oldDemandData = $sitesReport->whereBetween('date', [$startDate, $endDate])
            ->select(
                'network_id',
                'site_id',
                DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'),
                DB::raw('SUM(impressions) as impressions'),
            )
            ->groupBy('network_id')
            ->get();

        $oldAllRevenue = $oldDemandData->sum('sum_revenue');
        $oldAllImpression = $oldDemandData->sum('impressions');
        foreach ($oldDemandData as $site) {
            $site->revenue_per = $oldAllRevenue ? ($site->sum_revenue / $oldAllRevenue) * 100 : 0;
            $site->impressions_per = $oldAllImpression ? ($site->impressions / $oldAllImpression) * 100 : 0;
        }
        $oldDemandData = $oldDemandData->toArray();
        $previosDemandData = array_replace_recursive(
            array_combine(array_column($oldDemandData, "network_id"), $oldDemandData)
        );
        return $previosDemandData;
    }

    public function demandChannelPercentage($channelData, $previosData)
    {
        foreach ($channelData as $sites) {
            if (array_key_exists($sites->network_id, $previosData)) {
                $oldSites = $previosData[$sites->network_id];
                $oldRevenuePer = (float) $oldSites['revenue_per'];
                $oldImpressionsPer = (float) $oldSites['impressions_per'];
                $sites->revenue_percentage = $sites->revenue_per - $oldRevenuePer;
                $sites->impressions_percentage = $sites->impressions_per - $oldImpressionsPer;
            } else {
                $sites->revenue_percentage = $sites->revenue_per;
                $sites->impressions_percentage = $sites->impressions_per;
            }
        }
        // Sort the array
        $demandChannelData = json_decode(json_encode($channelData), true);
        usort($demandChannelData, function ($a, $b) {
            return $b['revenue_per'] > $a['revenue_per'] ? 1 : -1;
        });
        return $demandChannelData;
    }

    public function getSizesReportGraphQuery($startDate, $endDate, $siteId, $clientPer)
    {

        if ($siteId <= 1000) {
            $adSizeGraphData = OldDailyReport::where('im_daily_reports.site_id', $siteId);
            $adSizeGraphData = $adSizeGraphData->whereBetween('im_daily_reports.date', [$startDate, $endDate])
                ->join('im_sizes', 'im_daily_reports.size_id', '=', 'im_sizes.id')
                ->groupBy('im_daily_reports.size_id')
                ->select([
                    'im_sizes.dimensions',
                    'im_daily_reports.size_id',
                    'im_daily_reports.site_id',
                    DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as sum_revenue'),
                    DB::raw('SUM(impressions) AS impressions'),
                    DB::raw('0 as sum_rpm'),
                ])
                ->from('im_daily_reports');
        } else {
            $adSizeGraphData = DailyReport::where('im_daily_reports.site_id', $siteId);
            $adSizeGraphData = $adSizeGraphData->whereBetween('im_daily_reports.date', [$startDate, $endDate])
                ->join('dt_sizes', 'im_daily_reports.size_id', '=', 'dt_sizes.id')
                ->groupBy('im_daily_reports.size_id')
                ->select([
                    'dt_sizes.dimensions',
                    'im_daily_reports.size_id',
                    'im_daily_reports.site_id',
                    DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as sum_revenue'),
                    DB::raw('SUM(impressions) AS impressions'),
                ])
                ->from('im_daily_reports');
        }
        $adSizeGraphData = $adSizeGraphData->get();
        $allRevenue = $adSizeGraphData->sum('sum_revenue');
        $allImpression = $adSizeGraphData->sum('impressions');
        foreach ($adSizeGraphData as $site) {
            $site->revenue_per = $allRevenue ? ($site->sum_revenue / $allRevenue) * 100 : 0;
            $site->impressions_per = $allImpression ? ($site->impressions / $allImpression) * 100 : 0;
        }
        return $adSizeGraphData;
    }

    public function sizeStatsPercentage($sizesData, $previosData)
    {
        foreach ($sizesData as $sites) {
            if (array_key_exists($sites->size_id, $previosData)) {
                $oldSites = $previosData[$sites->size_id];
                $oldRevenuePer = (float) $oldSites['revenue_per'];
                $oldImpressionsPer = (float) $oldSites['impressions_per'];
                $sites->revenue_percentage = $sites->revenue_per - $oldRevenuePer;
                $sites->impressions_percentage = $sites->impressions_per - $oldImpressionsPer;
            } else {
                $sites->revenue_percentage = $sites->revenue_per;
                $sites->impressions_percentage = $sites->impressions_per;
            }
        }
        $sizesGraphData = json_decode(json_encode($sizesData), true);
        usort($sizesGraphData, function ($a, $b) {
            return $b['revenue_per'] > $a['revenue_per'] ? 1 : -1;
        });
        return $sizesGraphData;
    }
}
