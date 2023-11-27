<?php

namespace App\Http\Traits\AdOptimizationDashboard;

use App\Models\DailyReport;
use App\Models\GamReport;
use App\Models\OldDailyReport;
use App\Models\OldGamReport;
use Illuminate\Support\Facades\DB;

trait AdOptimizationTrait
{

    public function adOpsNewGamReportsQuery($startDate, $endDate)
    {
        $newGamReportData = GamReport::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'ADX')
            ->select('site_id', 'date', DB::raw('ifnull(SUM(total_request), 0) as request'));

        return $newGamReportData;
    }

    public function adOpsGamReportsQuery($startDate, $endDate)
    {
        $gamReportData = OldGamReport::whereBetween('date', [$startDate, $endDate])
            ->where('site_id', '<=', 1000)
            ->where('type', 'ADX')
            ->select('site_id', 'date', DB::raw('ifnull(SUM(total_request), 0) as request'));

        return $gamReportData;
    }

    public function adOptimizationOldSites($startDate, $endDate, $clientPerNet, $clientPerGross)
    {

        $oldDbSites = DB::table("imcustom2.im_sites");
        $oldDbSites = $oldDbSites->where([
            ['im_sites.status', '=', 1],
        ])
            ->join('imcustom2.im_daily_reports', function ($join) use ($startDate, $endDate) {
                $join->on('imcustom2.im_daily_reports.site_id', '=', 'im_sites.id');
                $join->whereBetween('imcustom2.im_daily_reports.date', [$startDate, $endDate]);
            })
            ->select(
                'im_sites.site_name',
                'imcustom2.im_daily_reports.site_id',
                DB::raw('SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(imcustom2.im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(imcustom2.im_daily_reports.impressions) as total_impressions'),
                DB::raw('0 as total_request'),
            )
            // ->having('gross_total_revenue', '>=', 1000)
            ->groupBy('imcustom2.im_daily_reports.site_id');

        return $oldDbSites;
    }

    public function adOptimizationNewSites($startDate, $endDate, $clientPerNet, $clientPerGross)
    {

        $sitesData = DB::table("dt_sites");
        $sitesData = $sitesData
            ->join('im_daily_reports', function ($join) use ($startDate, $endDate) {
                $join->on('im_daily_reports.site_id', '=', 'dt_sites.site_id');
                $join->whereBetween('im_daily_reports.date', [$startDate, $endDate]);
            })
            ->select(
                'dt_sites.site_name',
                'im_daily_reports.site_id',
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(im_daily_reports.impressions) as total_impressions'),
                DB::raw('0 as total_request'),
            )
            // ->having('gross_total_revenue', '>=', 1000)
            ->groupBy('im_daily_reports.site_id');

        return $sitesData;
    }

    public function adOpsOldSites($startDate, $endDate, $clientPerNet, $clientPerGross)
    {

        $oldPreviousSitesData = OldDailyReport::whereBetween('date', [$startDate, $endDate])
            ->where('site_id', '<=', 1000)
            ->select(
                'site_id',
                DB::raw('SUM(revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('0 as total_request'),
            )
            ->groupBy('site_id');

        return $oldPreviousSitesData;
    }

    public function adOpsNewSites($startDate, $endDate, $clientPerNet, $clientPerGross)
    {

        $newPreviousSitesData = DailyReport::whereBetween('date', [$startDate, $endDate])
            ->select(
                'site_id',
                DB::raw('SUM(revenue) * ' . $clientPerNet . ' as net_total_revenue'),
                DB::raw('SUM(revenue) * ' . $clientPerGross . ' as gross_total_revenue'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('0 as total_request'),
            )
            ->groupBy('site_id');

        return $newPreviousSitesData;
    }

    public function adOpsPercentageCalculate($sitesData, $bothPreviosData)
    {
        foreach ($sitesData as $sites) {
            if (array_key_exists($sites->site_id, $bothPreviosData)) {
                $sitesPercentage = $bothPreviosData[$sites->site_id];
                $oldNetRevenue = (float) $sitesPercentage['net_total_revenue'];
                $oldGrossRevenue = (float) $sitesPercentage['gross_total_revenue'];
                $oldImpressions = $sitesPercentage['total_impressions'];
                $oldTotalRequest = $sitesPercentage['total_request'];
                $sites->net_revenue_percentage = $oldNetRevenue ? (($sites->net_total_revenue) - ($oldNetRevenue)) * 100 / ($oldNetRevenue) : 0;
                $sites->gross_revenue_percentage = $oldGrossRevenue ? (($sites->gross_total_revenue) - ($oldGrossRevenue)) * 100 / ($oldGrossRevenue) : 0;
                $sites->impressions_percentage = $oldImpressions ? (($sites->total_impressions) - ($oldImpressions)) * 100 / ($oldImpressions) : 0;
                $sites->total_request_percentage = $oldTotalRequest ? (($sites->total_request) - ($oldTotalRequest)) * 100 / ($oldTotalRequest) : 0;
            } else {
                $sites->net_revenue_percentage = 0;
                $sites->gross_revenue_percentage = 0;
                $sites->impressions_percentage = 0;
                $sites->total_request_percentage = 0;
            }
        }
        return $sitesData;
    }

    public function getOldSitesReportQuery($startDate, $endDate, $clientPer, $userId)
    {
        $oldDbSites = DB::table("imcustom2.im_sites")
            ->whereRaw('FIND_IN_SET(' . $userId . ', im_sites.favourite_by_user_ids)')
            ->join('imcustom2.im_daily_reports', function ($join) use ($startDate, $endDate) {
                $join->on('imcustom2.im_daily_reports.site_id', '=', 'im_sites.id');
                $join->whereBetween('imcustom2.im_daily_reports.date', [$startDate, $endDate]);
            })
            ->select(
                'im_sites.site_name',
                'im_daily_reports.site_id',
                DB::raw('DATE_FORMAT(im_sites.updated_at, "%Y-%m-%d") as date'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as total_revenue'),
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', im_sites.favourite_by_user_ids), 0) as favourite')
            )
            ->groupBy('im_daily_reports.site_id');
        return $oldDbSites;
    }

    public function getNewSitesReportQuery($startDate, $endDate, $clientPer, $userId)
    {
        $sitesData = DB::table("dt_sites")
            ->whereRaw('FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids)')
            ->join('im_daily_reports', function ($join) use ($startDate, $endDate) {
                $join->on('im_daily_reports.site_id', '=', 'dt_sites.site_id');
                $join->whereBetween('im_daily_reports.date', [$startDate, $endDate]);
            })
            ->select(
                'dt_sites.site_name',
                'im_daily_reports.site_id',
                DB::raw('DATE_FORMAT(dt_sites.created_at, "%Y-%m-%d") as date'),
                DB::raw('SUM(im_daily_reports.revenue) * ' . $clientPer . ' as total_revenue'),
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite')
            )
            ->groupBy('im_daily_reports.site_id');
        return $sitesData;
    }
}