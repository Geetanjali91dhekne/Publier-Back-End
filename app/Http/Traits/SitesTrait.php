<?php

namespace App\Http\Traits;

use App\Models\DailyReport;
use App\Models\GamReport;
use App\Models\OldDailyReport;
use App\Models\OldGamReport;
use App\Models\OldSites;
use App\Models\Sites;
use App\Models\SiteTempReports;
use Illuminate\Support\Facades\DB;

trait SitesTrait {

    use AdServerTrait;

    public function getOldSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer)
    {
        $oldDbSites = DB::table("imcustom2.im_sites");
        if ($adServer == 'ON') $oldDbSites = $oldDbSites->where('no_adserver', 0);
            $oldDbSites = $oldDbSites->where([
                ['im_sites.site_name', 'like', "%" . $searchSite . "%"],
                ['im_sites.status', '=', 1],
            ])
                ->join('imcustom2.im_daily_reports', function ($join) use ($startDate, $endDate) {
                    $join->on('imcustom2.im_daily_reports.site_id', '=', 'im_sites.id');
                    $join->whereBetween('imcustom2.im_daily_reports.date', [$startDate, $endDate]);
                })
                ->select(
                    'im_sites.site_name',
                    'im_sites.no_adserver',
                    DB::raw('ifnull(FIND_IN_SET(' . $userId . ', im_sites.favourite_by_user_ids), 0) as favourite'),
                    'imcustom2.im_daily_reports.site_id',
                    DB::raw('SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                    DB::raw('SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                    DB::raw('SUM(imcustom2.im_daily_reports.impressions) as total_impressions'),
                    DB::raw('0 as total_request'),
                    DB::raw('1000 * (SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerNet . ')/SUM(imcustom2.im_daily_reports.impressions) as net_total_cpms'),
                    DB::raw('1000 * (SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerGross . ')/SUM(imcustom2.im_daily_reports.impressions) as gross_total_cpms'),
                    // DB::raw('100 * SUM(imcustom2.im_daily_reports.impressions)/SUM(imcustom2.im_daily_reports.total_request) as total_fillrate')
                )
                ->groupBy('im_daily_reports.site_id')
                ->having('gross_total_revenue', '>', 0);

        return $oldDbSites;
    }

    public function getGamReportsRequest($startDate, $endDate, $oldDbSiteIds) {
        $gamReportReq = OldGamReport::whereBetween('date', [$startDate, $endDate])
                ->where('type', 'ADX')
                ->where('site_id', '<=', 1000)
                ->whereIn('site_id', $oldDbSiteIds)
                ->groupBy('site_id')
                ->select(
                    'site_id',
                    DB::raw('ifnull(SUM(total_request), 0) as total_request'),
                );
        return $gamReportReq;
    }

    public function getNewGamReportsRequest($startDate, $endDate, $newDbSiteIds) {
        $gamReportReq = GamReport::whereBetween('date', [$startDate, $endDate])
                ->where('type', 'ADX')
                ->whereIn('site_id', $newDbSiteIds)
                ->groupBy('site_id')
                ->select(
                    'site_id',
                    DB::raw('ifnull(SUM(total_request), 0) as total_request'),
                );
        return $gamReportReq;
    }

    public function getNewSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer)
    {

        $sitesData = DB::table("dt_sites");
        if ($adServer == 'ON') $sitesData = $sitesData->where('no_adserver', 'N');
        $sitesData =  $sitesData->where('dt_sites.site_name', 'like', "%" . $searchSite . "%");
        $sitesData = $sitesData
            ->join('im_daily_reports', function ($join) use ($startDate, $endDate) {
                $join->on('im_daily_reports.site_id', '=', 'dt_sites.site_id');
                $join->whereBetween('im_daily_reports.date', [$startDate, $endDate]);
            })
            ->select(
                'dt_sites.site_name',
                'dt_sites.no_adserver',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite'),
                'im_daily_reports.site_id',
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(im_daily_reports.impressions) as total_impressions'),
                DB::raw('0 as total_request'),
                DB::raw('1000 * (SUM(im_daily_reports.revenue) * ' . $clientPerNet . ')/SUM(im_daily_reports.impressions) as net_total_cpms'),
                DB::raw('1000 * (SUM(im_daily_reports.revenue) * ' . $clientPerGross . ')/SUM(im_daily_reports.impressions) as gross_total_cpms'),
                // DB::raw('100 * SUM(im_daily_reports.impressions)/SUM(im_daily_reports.total_request) as total_fillrate')
            )
            ->groupBy('im_daily_reports.site_id')
            ->having('gross_total_revenue', '>', 0);

        return $sitesData;
    }

    public function percentageCalculate($sitesData, $bothPreviosData)
    {
        foreach ($sitesData as $sites) {
            if (array_key_exists($sites->site_id, $bothPreviosData)) {
                $sitesPercentage = $bothPreviosData[$sites->site_id];
                $oldNetRevenue = (float) $sitesPercentage['net_total_revenue'];
                $oldGrossRevenue = (float) $sitesPercentage['gross_total_revenue'];
                $oldImpressions = $sitesPercentage['total_impressions'];
                $oldTotalRequest = $sitesPercentage['total_request'];
                $oldNetTotalCpms = (float) $sitesPercentage['net_total_cpms'];
                $oldGrossTotalCpms = (float) $sitesPercentage['gross_total_cpms'];
                $oldTotalFillrate = $oldTotalRequest ? ($oldImpressions / $oldTotalRequest) * 100 : 0;
                $sites->total_fillrate = $sites->total_request ? ($sites->total_impressions / $sites->total_request) * 100 : 0;
                $sites->net_revenue_percentage = $oldNetRevenue ? (($sites->net_total_revenue) - ($oldNetRevenue)) * 100 / ($oldNetRevenue) : 0;
                $sites->gross_revenue_percentage = $oldGrossRevenue ? (($sites->gross_total_revenue) - ($oldGrossRevenue)) * 100 / ($oldGrossRevenue) : 0;
                $sites->impressions_percentage = $oldImpressions ? (($sites->total_impressions) - ($oldImpressions)) * 100 / ($oldImpressions) : 0;
                $sites->total_request_percentage = $oldTotalRequest ? (($sites->total_request) - ($oldTotalRequest)) * 100 / ($oldTotalRequest) : 0;
                $sites->net_total_cpms_percentage = $oldNetTotalCpms ? (($sites->net_total_cpms) - ($oldNetTotalCpms)) * 100 / ($oldNetTotalCpms) : 0;
                $sites->gross_total_cpms_percentage = $oldGrossTotalCpms ? (($sites->gross_total_cpms) - ($oldGrossTotalCpms)) * 100 / ($oldGrossTotalCpms) : 0;
                $sites->total_fillrate_percentage = $oldTotalFillrate != 0 ? (($sites->total_fillrate) - ($oldTotalFillrate)) * 100 / ($oldTotalFillrate) : 0;
            } else {
                $sites->total_fillrate = 0;
                $sites->net_revenue_percentage = 0;
                $sites->gross_revenue_percentage = 0;
                $sites->impressions_percentage = 0;
                $sites->total_request_percentage = 0;
                $sites->net_total_cpms_percentage = 0;
                $sites->gross_total_cpms_percentage = 0;
                $sites->total_fillrate_percentage = 0;
            }
        }
        return $sitesData;
    }

    public function getSiteTempReportsQuery($timeInterval, $searchSite, $userId, $adServer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();
        $bothAdServerIds = array_merge($newAdServerIds, $oldAdServerIds);
        $allSiteTemp = SiteTempReports::where('time_interval', $timeInterval);
        if ($adServer == 'ON') $allSiteTemp = $allSiteTemp->whereIn('site_id', $bothAdServerIds);
        $allSiteTemp = $allSiteTemp->where('site_name', 'like', "%" . $searchSite . "%")
            ->orderBy('gross_total_revenue', 'desc')
            ->paginate(10);

        foreach ($allSiteTemp as $key => $siteTemp) {
            if ($siteTemp->site_id >= 1000) {
                $data = Sites::where('site_id', $siteTemp->site_id)->select(DB::raw('ifnull(FIND_IN_SET(' . $userId . ', favourite_by_user_ids), 0) as favourite'))->first();
                $allSiteTemp[$key]['favourite'] = $data->favourite;
            } else {
                $oldData = OldSites::where('id', $siteTemp->site_id)->select(DB::raw('ifnull(FIND_IN_SET(' . $userId . ', favourite_by_user_ids), 0) as favourite'))->first();
                $allSiteTemp[$key]['favourite'] = $oldData->favourite;
            }
        }
        return $allSiteTemp;
    }

    public function getAllSiteTempReportsQuery($timeInterval, $searchSite, $userId, $adServer)
    {
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();
        $bothAdServerIds = array_merge($newAdServerIds, $oldAdServerIds);
        $allSiteTemp = SiteTempReports::where('time_interval', $timeInterval);
        if ($adServer == 'ON') $allSiteTemp = $allSiteTemp->whereIn('site_id', $bothAdServerIds);
        $allSiteTemp = $allSiteTemp->where('site_name', 'like', "%" . $searchSite . "%")
            ->orderBy('gross_total_revenue', 'desc')
            ->get();

        foreach ($allSiteTemp as $key => $siteTemp) {
            if ($siteTemp->site_id >= 1000) {
                $data = Sites::where('site_id', $siteTemp->site_id)->select(DB::raw('ifnull(FIND_IN_SET(' . $userId . ', favourite_by_user_ids), 0) as favourite'))->first();
                $allSiteTemp[$key]['favourite'] = $data->favourite;
            } else {
                $oldData = OldSites::where('id', $siteTemp->site_id)->select(DB::raw('ifnull(FIND_IN_SET(' . $userId . ', favourite_by_user_ids), 0) as favourite'))->first();
                $allSiteTemp[$key]['favourite'] = $oldData->favourite;
            }
        }
        return $allSiteTemp;
    }

    public function getSiteRequestData($startDate, $endDate, $sitesData, $oldDbSiteIds, $newDbSiteIds)
    {
        $oldSiteReq = $this->getGamReportsRequest($startDate, $endDate, $oldDbSiteIds)->get()->toArray();
        $oldSiteReq = array_replace_recursive(array_combine(array_column($oldSiteReq, "site_id"), $oldSiteReq));
        foreach ($sitesData as $sites) {
            if (array_key_exists($sites->site_id, $oldSiteReq)) {
                $sitesReq = $oldSiteReq[$sites->site_id];
                $sites->total_request += (float) $sitesReq['total_request'];
            }
        }

        $newSiteReq = $this->getNewGamReportsRequest($startDate, $endDate, $newDbSiteIds)->get()->toArray();
        $newSiteReq = array_replace_recursive(array_combine(array_column($newSiteReq, "site_id"), $newSiteReq));
        foreach ($sitesData as $sites) {
            if (array_key_exists($sites->site_id, $newSiteReq)) {
                $sitesReq = $newSiteReq[$sites->site_id];
                $sites->total_request += (float) $sitesReq['total_request'];
            }
        }
        return $sitesData;
    }

    public function getOldPreviousPeriodSites($oldStartDate, $oldEndDate, $oldDbSiteIds, $clientPerNet, $clientPerGross) {

        $oldPreviousSitesData = OldDailyReport::whereBetween('date', [$oldStartDate, $oldEndDate])->whereIn('site_id', $oldDbSiteIds)
                ->select(
                    'site_id',
                    DB::raw('SUM(revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                    DB::raw('SUM(revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                    DB::raw('SUM(impressions) as total_impressions'),
                    DB::raw('0 as total_request'),
                    DB::raw('1000 * (SUM(revenue) * ' . $clientPerNet . ')/SUM(impressions) as net_total_cpms'),
                    DB::raw('1000 * (SUM(revenue) * ' . $clientPerGross . ')/SUM(impressions) as gross_total_cpms'),
                    // DB::raw('100 * SUM(impressions)/SUM(total_request) as total_fillrate')
                )
                ->groupBy('site_id')
                ->get()->toArray();

        return $oldPreviousSitesData;
    }

    public function getNewPreviousPeriodSites($oldStartDate, $oldEndDate, $newDbSiteIds, $clientPerNet, $clientPerGross) {

        $newPreviousSitesData = DailyReport::whereBetween('date', [$oldStartDate, $oldEndDate])->whereIn('site_id', $newDbSiteIds)
                ->select(
                    'site_id',
                    DB::raw('SUM(revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                    DB::raw('SUM(revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                    DB::raw('SUM(impressions) as total_impressions'),
                    DB::raw('0 as total_request'),
                    DB::raw('1000 * (SUM(revenue) * ' . $clientPerNet . ')/SUM(impressions) as net_total_cpms'),
                    DB::raw('1000 * (SUM(revenue) * ' . $clientPerGross . ')/SUM(impressions) as gross_total_cpms'),
                    // DB::raw('100 * SUM(impressions)/SUM(total_request) as total_fillrate')
                )
                ->groupBy('site_id')
                ->get()->toArray();

        return $newPreviousSitesData;
    }

    public function getPreviousPeriodSiteWithRequest($oldStartDate, $oldEndDate, $newDbSiteIds, $oldDbSiteIds, $clientPerNet, $clientPerGross)
    {
        $oldPreviousSitesData = $this->getOldPreviousPeriodSites($oldStartDate, $oldEndDate, $oldDbSiteIds, $clientPerNet, $clientPerGross);
        $oldSitepreviousReq = $this->getGamReportsRequest($oldStartDate, $oldEndDate, $oldDbSiteIds)->get()->toArray();
        $oldSitepreviousReq = array_replace_recursive(array_combine(array_column($oldSitepreviousReq, "site_id"), $oldSitepreviousReq));
        foreach ($oldPreviousSitesData as $key => $sites) {
            if (array_key_exists($sites['site_id'], $oldSitepreviousReq)) {
                $sitePreviousReq = $oldSitepreviousReq[$sites['site_id']];
                $oldPreviousSitesData[$key]['total_request'] += (float) $sitePreviousReq['total_request'];
            }
        }

        $newPreviousSitesData = $this->getNewPreviousPeriodSites($oldStartDate, $oldEndDate, $newDbSiteIds, $clientPerNet, $clientPerGross);
        $newPreviousSiteReq = $this->getNewGamReportsRequest($oldStartDate, $oldEndDate, $newDbSiteIds)->get()->toArray();
        $newPreviousSiteReq = array_replace_recursive(array_combine(array_column($newPreviousSiteReq, "site_id"), $newPreviousSiteReq));
        foreach ($newPreviousSitesData as $key => $sites) {
            if (array_key_exists($sites['site_id'], $newPreviousSiteReq)) {
                $sitePreviousReq = $newPreviousSiteReq[$sites['site_id']];
                $newPreviousSitesData[$key]['total_request'] += (float) $sitePreviousReq['total_request'];
            }
        }

        $bothPreviosData = array_replace_recursive(
            array_combine(array_column($oldPreviousSitesData, "site_id"), $oldPreviousSitesData),
            array_combine(array_column($newPreviousSitesData, "site_id"), $newPreviousSitesData)
        );
        return $bothPreviosData;
    }

    public function getNewRecentSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer)
    {
        $siteData = Sites::query();
        $siteData =  $siteData->orderBy('dt_sites.created_at', 'desc')->limit(12);
        if ($adServer == 'ON') $siteData = $siteData->where('no_adserver', 'N');
        $siteData =  $siteData->where('dt_sites.site_name', 'like', "%" . $searchSite . "%");
        $siteData = $siteData->select(
                'dt_sites.site_id',
                'dt_sites.site_name',
                'dt_sites.no_adserver',
                DB::raw('DATE_FORMAT(dt_sites.created_at, "%Y-%m-%d") as date'),
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite')
            )
            ->groupBy('site_id')
            ->get()->toArray();
        $siteIds = array_column($siteData, 'site_id');

        $reportData = DailyReport::query();
        $reportData = $reportData->whereIn('site_id', $siteIds)->whereBetween('im_daily_reports.date', [$startDate, $endDate])
            ->select(
                'im_daily_reports.site_id',
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(im_daily_reports.impressions) as total_impressions'),
                DB::raw('0 as total_request'),
                DB::raw('1000 * (SUM(im_daily_reports.revenue) * ' . $clientPerNet . ')/SUM(im_daily_reports.impressions) as net_total_cpms'),
                DB::raw('1000 * (SUM(im_daily_reports.revenue) * ' . $clientPerGross . ')/SUM(im_daily_reports.impressions) as gross_total_cpms'),
            )
            ->groupBy('site_id')
            ->get()->toArray();
        $reportData = array_replace_recursive(array_combine(array_column($reportData, "site_id"), $reportData));

        $siteData = $this->mergeSiteAndReportData($siteData, $reportData);
        return $siteData;
    }

    public function getOldFavouriteSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer)
    {
        $oldDbSites = OldSites::query();
        if ($adServer == 'ON') $oldDbSites = $oldDbSites->where('no_adserver', 0);
        $oldDbSites = $oldDbSites->where([
                ['im_sites.site_name', 'like', "%" . $searchSite . "%"],
                ['im_sites.status', '=', 1],
            ]);
        $oldDbSites = $oldDbSites
            ->whereRaw('FIND_IN_SET(' . $userId . ', im_sites.favourite_by_user_ids)')
            ->select(
                'im_sites.id as site_id',
                'im_sites.site_name',
                'im_sites.no_adserver',
                DB::raw('DATE_FORMAT(im_sites.updated_at, "%Y-%m-%d") as date'),
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', im_sites.favourite_by_user_ids), 0) as favourite')
            )
            ->groupBy('site_id')
            ->get()->toArray();
        $siteIds = array_column($oldDbSites, 'site_id');

        $reportData = OldDailyReport::query();
        $reportData = $reportData->whereIn('site_id', $siteIds)->whereBetween('im_daily_reports.date', [$startDate, $endDate])
            ->select(
                'im_daily_reports.site_id',
                DB::raw('SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(imcustom2.im_daily_reports.impressions) as total_impressions'),
                DB::raw('0 as total_request'),
                DB::raw('1000 * (SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerNet . ')/SUM(imcustom2.im_daily_reports.impressions) as net_total_cpms'),
                DB::raw('1000 * (SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerGross . ')/SUM(imcustom2.im_daily_reports.impressions) as gross_total_cpms'),
            )
            ->groupBy('site_id')
            ->get()->toArray();
        $reportData = array_replace_recursive(array_combine(array_column($reportData, "site_id"), $reportData));
        $oldDbSites = $this->mergeSiteAndReportData($oldDbSites, $reportData);
        return $oldDbSites;
    }

    public function getNewFavouriteSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer)
    {
        $siteData = Sites::query();
        $siteData =  $siteData->orderBy('dt_sites.created_at', 'desc')->limit(12);
        if ($adServer == 'ON') $siteData = $siteData->where('no_adserver', 'N');
        $siteData =  $siteData
            ->where('dt_sites.site_name', 'like', "%" . $searchSite . "%")
            ->whereRaw('FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids)');
        $siteData = $siteData->select(
                'dt_sites.site_id',
                'dt_sites.site_name',
                'dt_sites.no_adserver',
                DB::raw('DATE_FORMAT(dt_sites.created_at, "%Y-%m-%d") as date'),
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite')
            )
            ->groupBy('site_id')
            ->get()->toArray();
        $siteIds = array_column($siteData, 'site_id');

        $reportData = DailyReport::query();
        $reportData = $reportData->whereIn('site_id', $siteIds)->whereBetween('im_daily_reports.date', [$startDate, $endDate])
            ->select(
                'im_daily_reports.site_id',
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(im_daily_reports.impressions) as total_impressions'),
                DB::raw('0 as total_request'),
                DB::raw('1000 * (SUM(im_daily_reports.revenue) * ' . $clientPerNet . ')/SUM(im_daily_reports.impressions) as net_total_cpms'),
                DB::raw('1000 * (SUM(im_daily_reports.revenue) * ' . $clientPerGross . ')/SUM(im_daily_reports.impressions) as gross_total_cpms'),
            )
            ->groupBy('site_id')
            ->get()->toArray();
        $reportData = array_replace_recursive(array_combine(array_column($reportData, "site_id"), $reportData));

        $siteData = $this->mergeSiteAndReportData($siteData, $reportData);
        return $siteData;
    }

    public function mergeSiteAndReportData($siteData, $reportData){
        foreach ($siteData as $key => $val) {
            if (array_key_exists($val['site_id'], $reportData)) {
                $reportRev = $reportData[$val['site_id']];
                $siteData[$key]['net_total_revenue'] = (float) $reportRev['net_total_revenue'];
                $siteData[$key]['gross_total_revenue'] = (float) $reportRev['gross_total_revenue'];
                $siteData[$key]['total_impressions'] = (float) $reportRev['total_impressions'];
                $siteData[$key]['total_request'] = (float) $reportRev['total_request'];
                $siteData[$key]['net_total_cpms'] = (float) $reportRev['net_total_cpms'];
                $siteData[$key]['gross_total_cpms'] = (float) $reportRev['gross_total_cpms'];
            } else {
                $siteData[$key]['net_total_revenue'] = (float) 0;
                $siteData[$key]['gross_total_revenue'] = (float) 0;
                $siteData[$key]['total_impressions'] = (float) 0;
                $siteData[$key]['total_request'] = (float) 0;
                $siteData[$key]['net_total_cpms'] = (float) 0;
                $siteData[$key]['gross_total_cpms'] = (float) 0;
            }
        }
        return $siteData;
    }

    ///Demand Sites Corresponding to networks
    public function getOldDemandSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer, $network)
    {
        $oldDbSites = DB::table("imcustom2.im_sites");
        if ($adServer == 'ON') $oldDbSites = $oldDbSites->where('no_adserver', 0);
        $oldDbSites = $oldDbSites->where([
                ['im_sites.site_name', 'like', "%" . $searchSite . "%"],
                ['im_sites.status', '=', 1],
            ])
            ->join('imcustom2.im_daily_reports', function ($join) use ($startDate, $endDate, $network) {
                $join->on('imcustom2.im_daily_reports.site_id', '=', 'im_sites.id');
                $join->whereBetween('imcustom2.im_daily_reports.date', [$startDate, $endDate]);
                $join->where('imcustom2.im_daily_reports.network_id', '=', $network);
            })
            ->select(
                'im_sites.site_name',
                'im_sites.no_adserver',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', im_sites.favourite_by_user_ids), 0) as favourite'),
                'imcustom2.im_daily_reports.site_id',
                DB::raw('SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(imcustom2.im_daily_reports.impressions) as total_impressions'),
                DB::raw('0 as total_request'),
                DB::raw('1000 * (SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerNet . ')/SUM(imcustom2.im_daily_reports.impressions) as net_total_cpms'),
                DB::raw('1000 * (SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerGross . ')/SUM(imcustom2.im_daily_reports.impressions) as gross_total_cpms'),
            )
            ->groupBy('im_daily_reports.site_id');
        return $oldDbSites;
    }
    public function getNewDemandSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer,$network)
    {
        $sitesData = DB::table("dt_sites");
        if ($adServer == 'ON') $sitesData = $sitesData->where('no_adserver', 'N');
        $sitesData =  $sitesData->where('dt_sites.site_name', 'like', "%" . $searchSite . "%");
        $sitesData = $sitesData
            ->join('im_daily_reports', function ($join) use ($startDate, $endDate, $network) {
                $join->on('im_daily_reports.site_id', '=', 'dt_sites.site_id');
                $join->whereBetween('im_daily_reports.date', [$startDate, $endDate]);
                $join->where('im_daily_reports.network_id', '=', $network);
            })
            ->select(
                'dt_sites.site_name',
                'dt_sites.no_adserver',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite'),
                'im_daily_reports.site_id',
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(im_daily_reports.impressions) as total_impressions'),
                DB::raw('0 as total_request'),
                DB::raw('1000 * (SUM(im_daily_reports.revenue) * ' . $clientPerNet . ')/SUM(im_daily_reports.impressions) as net_total_cpms'),
                DB::raw('1000 * (SUM(im_daily_reports.revenue) * ' . $clientPerGross . ')/SUM(im_daily_reports.impressions) as gross_total_cpms'),
            )
            ->groupBy('im_daily_reports.site_id');           
        return $sitesData;
    }
}