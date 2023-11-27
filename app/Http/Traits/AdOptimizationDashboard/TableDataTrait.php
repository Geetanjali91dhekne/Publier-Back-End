<?php

namespace App\Http\Traits\AdOptimizationDashboard;

use App\Http\Traits\AdServerTrait;
use App\Models\DailyReport;
use App\Models\GamReport;
use App\Models\OldDailyReport;
use App\Models\OldGamReport;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;

trait TableDataTrait
{
    use AdServerTrait;
    use AdOptimizationTrait;

    public function getRecentTabData($startDate, $endDate, $clientPer, $userId, $adServer, $newAdServerIds)
    {
        $recentData = Sites::query();
        $recentData =  $recentData->orderBy('dt_sites.site_id', 'desc')->limit(12);
        if ($adServer == 'ON') $recentData = $recentData->whereIn('dt_sites.site_id', $newAdServerIds);
        $recentData = $recentData
            ->join('im_daily_reports', function ($join) use ($startDate, $endDate) {
                $join->on('im_daily_reports.site_id', '=', 'dt_sites.site_id');
                $join->whereBetween('im_daily_reports.date', [$startDate, $endDate]);
            })
            ->select(
                'dt_sites.site_name',
                'im_daily_reports.site_id',
                DB::raw('DATE_FORMAT(dt_sites.created_at, "%Y-%m-%d") as date'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as total_revenue'),
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite'))
            ->groupBy('im_daily_reports.site_id')   
            ->having('total_revenue', '>', 0)
            ->get()->toArray();
        return $recentData;
    }

    public function currentTopTrendData($startDate, $endDate, $clientPerNet, $clientPerGross, $adServer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        /* old db record top 10 record */
        $oldDbTopTrendsImpRev = $this->adOptimizationOldSites($startDate, $endDate, $clientPerNet, $clientPerGross);
        if ($adServer == 'ON') $oldDbTopTrendsImpRev = $oldDbTopTrendsImpRev->whereIn('im_sites.id', $oldAdServerIds);
        $oldDbTopTrendsImpRev = $oldDbTopTrendsImpRev->orderBy('gross_total_revenue', 'desc')->limit(10)->get()->toArray();

        $dbSiteIds = array_column($oldDbTopTrendsImpRev, 'site_id');
        $oldDbTopTrendsReq = DB::table("imcustom2.dt_gam_reports");
        $oldDbTopTrendsReq = $oldDbTopTrendsReq->whereBetween('date', [$startDate, $endDate])->where('site_id', '<=', 1000)
            ->select('site_id', DB::raw('ifnull(SUM(total_request), 0) as total_request'))
            ->groupBy('site_id')->whereIn('site_id', $dbSiteIds)->get()->toArray();

        $oldBothArr = array_merge($oldDbTopTrendsImpRev, $oldDbTopTrendsReq);
        $OldDbTopTrends = array();
        foreach ($oldBothArr as $val) {
            if (!isset($OldDbTopTrends[$val->site_id]))
                $OldDbTopTrends[$val->site_id] = $val;
            else
                $OldDbTopTrends[$val->site_id]->total_request += $val->total_request;
        }

        /* new db record top 10 record */
        $topTrendsImpRev = $this->adOptimizationNewSites($startDate, $endDate, $clientPerNet, $clientPerGross);
        if ($adServer == 'ON') $topTrendsImpRev = $topTrendsImpRev->whereIn('dt_sites.site_id', $newAdServerIds);
        $topTrendsImpRev = $topTrendsImpRev->orderBy('gross_total_revenue', 'desc')->limit(10)->get()->toArray();

        $topTrendSiteIds = array_column($topTrendsImpRev, 'site_id');
        $topTrendsReq = DB::table("dt_gam_reports")->whereBetween('date', [$startDate, $endDate])
            ->select('site_id', DB::raw('ifnull(SUM(total_request), 0) as total_request'))
            ->groupBy('site_id')->whereIn('site_id', $topTrendSiteIds)->get()->toArray();
        $newBothArr = array_merge($topTrendsImpRev, $topTrendsReq);
        $topTrendsData = array();
        foreach ($newBothArr as $val) {
            if (!isset($topTrendsData[$val->site_id]))
                $topTrendsData[$val->site_id] = $val;
            else
                $topTrendsData[$val->site_id]->total_request += $val->total_request;
        }

        // merge new + old record and sort by the revenue value
        $allTopTrends = array_merge($topTrendsData, $OldDbTopTrends);
        usort($allTopTrends, function ($element1, $element2) {
            return $element2->gross_total_revenue - $element1->gross_total_revenue;
        });
        if (count($allTopTrends) > 10) $allTopTrends = array_slice($allTopTrends, 0, 10);
        return $allTopTrends;
    }

    public function previousPeriodTopTrendData($oldStartDate, $oldEndDate, $clientPerNet, $clientPerGross, $oldDbSiteIds, $newDbSiteIds)
    {
        /* Previous Period old DB Sites Data */
        $oldPreviousSitesData = $this->adOpsOldSites($oldStartDate, $oldEndDate, $clientPerNet, $clientPerGross);
        $oldPreviousSitesData = $oldPreviousSitesData->whereIn('site_id', $oldDbSiteIds)->get()->toArray();
        $dbPreviousSiteIds = array_column($oldPreviousSitesData, 'site_id');
        $oldDbPreviousReq = OldGamReport::whereBetween('date', [$oldStartDate, $oldEndDate])->where('site_id', '<=', 1000)
            ->where('type', 'ADX')
            ->select('site_id', DB::raw('ifnull(SUM(total_request), 0) as total_request'))
            ->groupBy('site_id')->whereIn('site_id', $dbPreviousSiteIds)->get()->toArray();
        $oldBothPreviousArr = array_merge($oldPreviousSitesData, $oldDbPreviousReq);
        $oldDbPreviousData = array();
        foreach ($oldBothPreviousArr as $val) {
            if (!isset($oldDbPreviousData[$val['site_id']]))
                $oldDbPreviousData[$val['site_id']] = $val;
            else
                $oldDbPreviousData[$val['site_id']]['total_request'] += $val['total_request'];
        }

        /* Previous Period new DB Sites Data */
        $newPreviousSitesData = $this->adOpsNewSites($oldStartDate, $oldEndDate, $clientPerNet, $clientPerGross);
        $newPreviousSitesData = $newPreviousSitesData->whereIn('site_id', $newDbSiteIds)->get()->toArray();
        $newPreviousSiteIds = array_column($newPreviousSitesData, 'site_id');
        $newDbPreviousReq = GamReport::whereBetween('date', [$oldStartDate, $oldEndDate])
            ->where('type', 'ADX')
            ->select('site_id', DB::raw('ifnull(SUM(total_request), 0) as total_request'))
            ->groupBy('site_id')->whereIn('site_id', $newPreviousSiteIds)->get()->toArray();
        $newBothPreviousArr = array_merge($newPreviousSitesData, $newDbPreviousReq);
        $newDbPreviousData = array();
        foreach ($newBothPreviousArr as $val) {
            if (!isset($newDbPreviousData[$val['site_id']]))
                $newDbPreviousData[$val['site_id']] = $val;
            else
                $newDbPreviousData[$val['site_id']]['total_request'] += $val['total_request'];
        }

        $bothPreviosData = array_replace_recursive(
            array_combine(array_column($oldDbPreviousData, "site_id"), $oldDbPreviousData),
            array_combine(array_column($newDbPreviousData, "site_id"), $newDbPreviousData)
        );
        return $bothPreviosData;
    }

    public function currDemandChannelData($startDate, $endDate, $adServer, $clientPer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        $data = DailyReport::query();
        $data =  $data->whereBetween('date', [$startDate, $endDate]);
        if ($adServer == 'ON') $data = $data->whereIn('site_id', $newAdServerIds);
        $data = $data
            ->join('dt_networks', 'im_daily_reports.network_id', '=', 'dt_networks.id')
            ->select(
                'dt_networks.network_name',
                'im_daily_reports.site_id',
                'im_daily_reports.network_id',
                DB::raw('SUM(im_daily_reports.impressions) as total_impressions'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as total_revenue'),
            )
            ->from('im_daily_reports')
            ->groupBy('network_id')
            ->get()->toArray();

        $oldData = OldDailyReport::query();
        if ($adServer == 'ON') $oldData = $oldData->whereIn('site_id', $oldAdServerIds);
        $oldData = $oldData->whereBetween('date', [$startDate, $endDate])
            ->where('site_id', '<=', 1000)
            ->join('im_networks', 'im_daily_reports.network_id', '=', 'im_networks.id')
            ->select(
                'im_networks.network_name',
                'im_daily_reports.site_id',
                'im_daily_reports.network_id',
                DB::raw('SUM(im_daily_reports.impressions) as total_impressions'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as total_revenue'),
            )
            ->from('im_daily_reports')
            ->groupBy('network_id')
            ->get()->toArray();

        $bothArr = array_merge($data, $oldData);
        $result = array();
        foreach ($bothArr as $val) {
            if (!isset($result[$val['network_id']]))
                $result[$val['network_id']] = $val;
            else {
                $result[$val['network_id']]['total_revenue'] += $val['total_revenue'];
                $result[$val['network_id']]['total_impressions'] += $val['total_impressions'];
            }
        }
        foreach ($result as $key => $sites) {
            $result[$key]['total_cpm'] = $sites['total_impressions'] ? ($sites['total_revenue'] / $sites['total_impressions']) * 1000 : 0;
        }

        return $result;
    }

    public function previousDemandChannelData($oldStartDate, $oldEndDate, $clientPer, $networkIds)
    {
        $previousData = DailyReport::query();
        $previousData =  $previousData->whereBetween('date', [$oldStartDate, $oldEndDate])->whereIn('network_id', $networkIds);
        $previousData = $previousData->select(
            'site_id',
            'network_id',
            DB::raw('SUM(impressions) as total_impressions'),
            DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue')
        )
            ->groupBy('network_id')
            ->get()->toArray();

        $previousOldData = OldDailyReport::query();
        $previousOldData = $previousOldData->where('site_id', '<=', 1000)->whereBetween('date', [$oldStartDate, $oldEndDate])->whereIn('network_id', $networkIds);
        $previousOldData = $previousOldData->select(
            'site_id',
            'network_id',
            DB::raw('SUM(impressions) as total_impressions'),
            DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue')
        )
            ->groupBy('network_id')
            ->get()->toArray();

        $bothPreviousArr = array_merge($previousData, $previousOldData);
        $previousResult = array();
        foreach ($bothPreviousArr as $val) {
            if (!isset($previousResult[$val['network_id']]))
                $previousResult[$val['network_id']] = $val;
            else {
                $previousResult[$val['network_id']]['total_revenue'] += $val['total_revenue'];
                $previousResult[$val['network_id']]['total_impressions'] += $val['total_impressions'];
            }
        }
        foreach ($previousResult as $key => $sites) {
            $previousResult[$key]['total_cpm'] = $sites['total_impressions'] ? ($sites['total_revenue'] / $sites['total_impressions']) * 1000 : 0;
        }
        return $previousResult;
    }

    public function adDemandChannelData($startDate, $endDate, $adServer, $oldStartDate, $oldEndDate, $clientPer)
    {
        $data = $this->currDemandChannelData($startDate, $endDate, $adServer, $clientPer);
        $networkIds = array_column($data, 'network_id');
        $previousChannelData = $this->previousDemandChannelData($oldStartDate, $oldEndDate, $clientPer, $networkIds);
        $previousChannelData = array_replace_recursive(array_combine(array_column($previousChannelData, "network_id"), $previousChannelData));

        foreach ($data as $key => $sites) {
            if (array_key_exists($sites['network_id'], $previousChannelData)) {
                $sitesPercentage = $previousChannelData[$sites['network_id']];
                $oldImpressions = $sitesPercentage['total_impressions'];
                $oldRevenue = (float) $sitesPercentage['total_revenue'];
                $oldCpm = (float) $sitesPercentage['total_cpm'];
                $data[$key]['impressions_percentage'] = $oldImpressions ? (($sites['total_impressions']) - ($oldImpressions)) * 100 / ($oldImpressions) : '0';
                $data[$key]['revenue_percentage'] = $oldRevenue ? (($sites['total_revenue']) - ($oldRevenue)) * 100 / ($oldRevenue) : '0';
                $data[$key]['total_cpm_percentage'] = $oldCpm ? (($sites['total_cpm']) - ($oldCpm)) * 100 / ($oldCpm) : '0';
            } else {
                $data[$key]['impressions_percentage'] = '0';
                $data[$key]['revenue_percentage'] = '0';
                $data[$key]['total_cpm_percentage'] = '0';
            }
        }
        return array_values($data);
    }
}
