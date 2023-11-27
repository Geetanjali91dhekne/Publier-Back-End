<?php

namespace App\Http\Traits\AdOptimizationDashboard;

use App\Http\Traits\AdServerTrait;
use App\Models\DailyReport;
use App\Models\OldDailyReport;
use App\Models\PageviewsDailyReports;
use Illuminate\Support\Facades\DB;

trait TopCardTrait
{
    use AdOptimizationTrait;
    use AdServerTrait;

    public function getTopCardData($startDate, $endDate, $oldStartDate, $oldEndDate, $adServer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        /* new db impressions and request */
        $adData = new DailyReport();
        if ($adServer == 'ON') $adData = $adData->whereIn('site_id', $newAdServerIds);
        $adData = $adData->whereBetween('date', [$startDate, $endDate])->groupBy('site_id')
            ->select(DB::raw('SUM(impressions) as impressions'), 'site_id')->get();
        // $newSiteIds = $adData->pluck('site_id')->toArray();
        $newTotalReq = $this->adOpsNewGamReportsQuery($startDate, $endDate);
        if ($adServer == 'ON') $newTotalReq = $newTotalReq->whereIn('site_id', $newAdServerIds);
        $newTotalReq = $newTotalReq->groupBy('site_id')->get();

        /* old db impression and request */
        $oldDbReport = OldDailyReport::where('site_id', '<=', 1000)->whereBetween('date', [$startDate, $endDate]);
        if ($adServer == 'ON') $oldDbReport = $oldDbReport->whereIn('site_id', $oldAdServerIds);
        $oldDbReport = $oldDbReport->groupBy('site_id')
            ->select('site_id', DB::raw('SUM(impressions) as impressions'))->get();
        // $dbSiteIds = $oldDbReport->pluck('site_id')->toArray();
        $oldTotalReq = $this->adOpsGamReportsQuery($startDate, $endDate);
        if ($adServer == 'ON') $oldTotalReq = $oldTotalReq->whereIn('site_id', $oldAdServerIds);
        $oldTotalReq = $oldTotalReq->groupBy('site_id')->get();

        /* some of both old and new impressions and request */
        $allImp = $adData->sum('impressions') + $oldDbReport->sum('impressions');
        $allReq = $newTotalReq->sum('request') + $oldTotalReq->sum('request');
        $fillrate = $allReq ? ($allImp / $allReq) * 100 : 0;

        /* previos period new db impression and request */
        $adOldData = DailyReport::whereBetween('date', [$oldStartDate, $oldEndDate]);
        if ($adServer == 'ON') $adOldData = $adOldData->whereIn('site_id', $newAdServerIds);
        $adOldData = $adOldData->groupBy('site_id')
            ->select(DB::raw('SUM(impressions) as impressions'), 'site_id')->get();

        // $newPreviosSiteIds = $adOldData->pluck('site_id')->toArray();
        $newPreviosTotalReq = $this->adOpsNewGamReportsQuery($startDate, $endDate);
        if ($adServer == 'ON') $newPreviosTotalReq = $newPreviosTotalReq->whereIn('site_id', $newAdServerIds);
        $newPreviosTotalReq = $newPreviosTotalReq->groupBy('site_id')->get();

        /* previos period old db impression and request */
        $oldDbPreviosReport = OldDailyReport::where('site_id', '<=', 1000)->whereBetween('date', [$oldStartDate, $oldEndDate]);
        if ($adServer == 'ON') $oldDbPreviosReport = $oldDbPreviosReport->whereIn('site_id', $oldAdServerIds);
        $oldDbPreviosReport = $oldDbPreviosReport->groupBy('site_id')
            ->select('site_id', DB::raw('SUM(impressions) as impressions'))->get();

        // $dbPreviosSiteIds = $oldDbReport->pluck('site_id')->toArray();
        $oldPreviosTotalReq = $this->adOpsGamReportsQuery($oldStartDate, $oldEndDate);
        if ($adServer == 'ON') $oldPreviosTotalReq = $oldPreviosTotalReq->whereIn('site_id', $oldAdServerIds);
        $oldPreviosTotalReq = $oldPreviosTotalReq->groupBy('site_id')->get();

        /* previos period old and new impressions and request */
        $allPreviosImp = $adOldData->sum('impressions') + $oldDbPreviosReport->sum('impressions');
        $allPreviosReq = $newPreviosTotalReq->sum('request') + $oldPreviosTotalReq->sum('request');
        $oldFillrate = $allPreviosReq ? ($allPreviosImp / $allPreviosReq) * 100 : 0;

        /* percentage calculate */
        $impressionsper = $allPreviosImp ? (($allImp) - ($allPreviosImp)) * 100 / ($allPreviosImp) : 0;
        $requestper = $allPreviosReq ? (($allReq) - ($allPreviosReq)) * 100 / ($allPreviosReq) : 0;
        $fillrateper = $oldFillrate ? (($fillrate) - ($oldFillrate)) * 100 / ($oldFillrate) : 0;

        /* calculate Pageview */
        $adPageData = new PageviewsDailyReports();
        $adPageData =  $adPageData->whereBetween('date', [$startDate, $endDate]);
        $pageview = $adPageData->sum('pageviews');

        $topCard = [
            'message' => 'Imp, request, fillrate Data Get Successfully', 'status' => true,
            'total_impressions' => $allImp, 'previous_total_impressions' => $allPreviosImp, 'total_impressions_percentage' => $impressionsper,
            'total_request' => $allReq, 'previous_total_request' => $allPreviosReq, 'total_request_percentage' => $requestper,
            'total_fill_rate' => $fillrate, 'previous_total_fill_rate' => $oldFillrate, 'total_fill_rate_percentage' => $fillrateper, 'total_pageview' => $pageview,
        ];
        return $topCard;
    }

    public function getTopCardRevenueCpmData($startDate, $endDate, $clientPer, $oldStartDate, $oldEndDate, $adServer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        /* old db revenue & cpms */
        $oldDbReport = OldDailyReport::query();
        if ($adServer == 'ON') $oldDbReport = $oldDbReport->whereIn('site_id', $oldAdServerIds);
        $oldDbReport = $oldDbReport
            ->where('site_id', '<=', 1000)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'), DB::raw('SUM(impressions) as sum_impressions'))
            ->groupBy('date')->get();

        /* new db revenue & cpms */
        $adData = new DailyReport();
        if ($adServer == 'ON') $adData = $adData->whereIn('site_id', $newAdServerIds);
        $adData = $adData->whereBetween('date', [$startDate, $endDate]);
        $adData = $adData->groupBy('date')
            ->select(DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'), DB::raw('SUM(impressions) as sum_impressions'))->get();

        /* old + new DB revenue and cpms */
        $rev = $adData->sum('sum_revenue') + $oldDbReport->sum('sum_revenue');
        $imp = $adData->sum('sum_impressions') + $oldDbReport->sum('sum_impressions');
        $cpms = $imp ? $rev / $imp * 1000 : 0;


        /* previos period data from old DB */
        $oldDbPreviosReport = OldDailyReport::query();
        if ($adServer == 'ON') $oldDbPreviosReport = $oldDbPreviosReport->whereIn('site_id', $oldAdServerIds);
        $oldDbPreviosReport = $oldDbPreviosReport
            ->where('site_id', '<=', 1000)
            ->whereBetween('date', [$oldStartDate, $oldEndDate])
            ->select(DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'), DB::raw('SUM(impressions) as sum_impressions'))
            ->groupBy('date')->get();

        $adOldData = DailyReport::whereBetween('date', [$oldStartDate, $oldEndDate]);
        if ($adServer == 'ON') $adOldData = $adOldData->whereIn('site_id', $newAdServerIds);
        $adOldData = $adOldData->groupBy('date')
            ->select(DB::raw('SUM(revenue) * ' . $clientPer . ' as sum_revenue'), DB::raw('SUM(impressions) as sum_impressions'))
            ->get();
        $oldRev = $adOldData->sum('sum_revenue') + $oldDbPreviosReport->sum('sum_revenue');
        $oldImp = $adOldData->sum('sum_impressions') + $oldDbPreviosReport->sum('sum_impressions');
        $oldCpms = $oldImp ? $oldRev / $oldImp * 1000 : 0;

        $revenueper = $oldRev ? (($rev) - ($oldRev)) * 100 / ($oldRev) : 0;
        $cpmsper = $oldCpms ? (($cpms) - ($oldCpms)) * 100 / ($oldCpms) : 0;

        $revenueAndCmpData = [
            'message' => 'Revenue and CPMs Data Get Successfully',
            'status' => true,
            'total_revenue' => $rev, 'previous_total_revenue' => $oldRev, 'revenue_percentage' => $revenueper,
            'total_cpms' => $cpms, 'previous_total_cpms' => $oldCpms, 'total_cpms_percentage' => $cpmsper,
        ];
        return $revenueAndCmpData;
    }
}
