<?php

namespace App\Http\Traits\AdOptimizationDashboard;

use App\Http\Traits\AdServerTrait;
use App\Models\DailyReport;
use App\Models\OldDailyReport;
use Illuminate\Support\Facades\DB;

trait GraphTrait
{
    use AdOptimizationTrait;
    use AdServerTrait;

    public function revenueGraphData($startDate, $endDate, $adServer, $clientPer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        $oldDbReport = OldDailyReport::query();
        if ($adServer == 'ON') $oldDbReport = $oldDbReport->whereIn('site_id', $oldAdServerIds);
        $oldDbReport = $oldDbReport
            ->where('site_id', '<=', 1000)->whereBetween('date', [$startDate, $endDate])
            ->select('date', DB::raw(DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue')))
            ->groupBy('date')
            ->get()->toArray();
        $adRevData = DailyReport::query();
        if ($adServer == 'ON') $adRevData = $adRevData->whereIn('site_id', $newAdServerIds);
        $adRevData = $adRevData
            ->whereBetween('date', [$startDate, $endDate])
            ->select('date', DB::raw(DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue')))
            ->groupBy('date')
            ->get()->toArray();
        // ->union($oldDbReport);

        $bothArr = array_merge($adRevData, $oldDbReport);
        $result = array();
        foreach ($bothArr as $val) {
            if (!isset($result[$val['date']]))
                $result[$val['date']] = $val;
            else
                $result[$val['date']]['total_revenue'] += $val['total_revenue'];
        }

        // Sort the array
        usort($result, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $result;
    }

    public function impressionsGraphData($startDate, $endDate, $adServer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        $oldDbReport = OldDailyReport::query();
        if ($adServer == 'ON') $oldDbReport = $oldDbReport->whereIn('site_id', $oldAdServerIds);
        $oldDbReport = $oldDbReport
            ->where('site_id', '<=', 1000)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('date', DB::raw(DB::raw('SUM(impressions) as total_impressions')))
            ->groupBy('date')
            ->get()->toArray();
        $adImpData = DailyReport::query();
        if ($adServer == 'ON') $adImpData = $adImpData->whereIn('site_id', $newAdServerIds);
        $adImpData = $adImpData
            ->whereBetween('date', [$startDate, $endDate])
            ->select('date', DB::raw(DB::raw('SUM(impressions) as total_impressions')))
            ->groupBy('date')
            ->get()->toArray();
        // ->union($oldDbReport);

        $bothArr = array_merge($adImpData, $oldDbReport);
        $result = array();
        foreach ($bothArr as $val) {
            if (!isset($result[$val['date']]))
                $result[$val['date']] = $val;
            else
                $result[$val['date']]['total_impressions'] += $val['total_impressions'];
        }
        // Sort the array
        usort($result, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $result;
    }

    public function requestGraphData($startDate, $endDate, $adServer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        $oldAdRequestData = $this->adOpsGamReportsQuery($startDate, $endDate);
        if ($adServer == 'ON') $oldAdRequestData = $oldAdRequestData->whereIn('site_id', $oldAdServerIds);
        $oldAdRequestData = $oldAdRequestData->groupBy('date')->get()->toArray();

        $adRequestData = $this->adOpsNewGamReportsQuery($startDate, $endDate);
        if ($adServer == 'ON') $adRequestData = $adRequestData->whereIn('site_id', $newAdServerIds);
        $adRequestData = $adRequestData->groupBy('date')->get()->toArray();

        $bothArr = array_merge($oldAdRequestData, $adRequestData);
        $result = array();
        foreach ($bothArr as $val) {
            if (!isset($result[$val['date']]))
                $result[$val['date']] = $val;
            else
                $result[$val['date']]['request'] += $val['request'];
        }

        // Sort the array
        usort($result, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $result;
    }

    public function cpmsGraphData($startDate, $endDate, $adServer, $clientPer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        $oldDbReport = OldDailyReport::query();
        if ($adServer == 'ON') $oldDbReport = $oldDbReport->whereIn('site_id', $oldAdServerIds);
        $oldDbReport = $oldDbReport
            ->where('site_id', '<=', 1000)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('date', DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'), DB::raw('SUM(impressions) as sum_impressions'))
            ->groupBy('date')
            ->get()->toArray();

        $adCpmsData = DailyReport::query();
        if ($adServer == 'ON') $adCpmsData = $adCpmsData->whereIn('site_id', $newAdServerIds);
        $adCpmsData = $adCpmsData
            ->whereBetween('date', [$startDate, $endDate])
            ->select('date', DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'), DB::raw('SUM(impressions) as sum_impressions'))
            ->groupBy('date')
            ->get()->toArray();

        $bothArr = array_merge($adCpmsData, $oldDbReport);
        $result = array();
        foreach ($bothArr as $val) {
            if (!isset($result[$val['date']]))
                $result[$val['date']] = $val;
            else {
                $result[$val['date']]['sum_revenue'] += $val['sum_revenue'];
                $result[$val['date']]['sum_impressions'] += $val['sum_impressions'];
            }
        }
        foreach ($result as $key => $sites) {
            $result[$key]['total_cpms'] = $sites['sum_impressions'] ? ($sites['sum_revenue'] / $sites['sum_impressions']) * 1000 : 0;
        }
        // Sort the array
        usort($result, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $result;
    }

    public function fillrateGraphData($startDate, $endDate, $adServer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        /* old db impression and request */
        $oldAdFillrateImp = OldDailyReport::query();
        if ($adServer == 'ON') $oldAdFillrateImp = $oldAdFillrateImp->whereIn('site_id', $oldAdServerIds);
        $oldAdFillrateImp = $oldAdFillrateImp
            ->where('site_id', '<=', 1000)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('date', 'site_id', DB::raw('SUM(impressions) as total_impressions'), DB::raw('0 as request'))
            ->groupBy('date')
            ->get()->toArray();
        // $dbSiteIds = array_column($oldAdFillrateImp, 'site_id');
        $oldAdFillrateReq = $this->adOpsGamReportsQuery($startDate, $endDate);
        if ($adServer == 'ON') $oldAdFillrateReq = $oldAdFillrateReq->whereIn('site_id', $oldAdServerIds);
        $oldAdFillrateReq = $oldAdFillrateReq->groupBy('date')->get()->toArray();

        $oldBothArr = array_merge($oldAdFillrateImp, $oldAdFillrateReq);
        $oldAdFillrateData = array();
        foreach ($oldBothArr as $val) {
            if (!isset($oldAdFillrateData[$val['date']]))
                $oldAdFillrateData[$val['date']] = $val;
            else
                $oldAdFillrateData[$val['date']]['request'] += $val['request'];
        }

        /* new db impression and request */
        $adFillrateImp = DailyReport::query();
        if ($adServer == 'ON') $adFillrateImp = $adFillrateImp->whereIn('site_id', $newAdServerIds);
        $adFillrateImp = $adFillrateImp
            ->whereBetween('date', [$startDate, $endDate])
            ->select('date', 'site_id', DB::raw('SUM(impressions) as total_impressions'), DB::raw('0 as request'))
            ->groupBy('date')
            ->get()->toArray();
        // $newDBSiteIds = array_column($adFillrateImp, 'site_id');
        $adRequestData = $this->adOpsNewGamReportsQuery($startDate, $endDate);
        if ($adServer == 'ON') $adRequestData = $adRequestData->whereIn('site_id', $newAdServerIds);
        $adRequestData = $adRequestData->groupBy('date')->get()->toArray();
        $newBothArr = array_merge($adFillrateImp, $adRequestData);
        $adFillrateData = array();
        foreach ($newBothArr as $val) {
            if (!isset($adFillrateData[$val['date']]))
                $adFillrateData[$val['date']] = $val;
            else
                $adFillrateData[$val['date']]['request'] += $val['request'];
        }

        $bothArr = array_merge(array_values($oldAdFillrateData), array_values($adFillrateData));
        $result = array();
        foreach ($bothArr as $val) {
            if (!isset($result[$val['date']])) {
                $result[$val['date']] = $val;
            } else {
                $result[$val['date']]['total_impressions'] += $val['total_impressions'];
                $result[$val['date']]['request'] += $val['request'];
            }
        }

        // Sort the array
        usort($result, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });

        foreach ($result as $key => $sites) {
            $result[$key]['fillrate'] = $sites['request'] ? ($sites['total_impressions'] / $sites['request']) * 100 : 0;
        }
        return $result;
    }
}
