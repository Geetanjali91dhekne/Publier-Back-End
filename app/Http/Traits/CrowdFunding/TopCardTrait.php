<?php

namespace App\Http\Traits\CrowdFunding;

use App\Models\CrowdfundReports;
use App\Models\DailyReport;
use App\Models\PaymentsInfo;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;

trait TopCardTrait
{
    public function getfundTotalEarningsData($startTime, $endTime, $clientPer, $siteId)
    {
        $fundTotalEar = Sites::query();
        if($siteId) $fundTotalEar = $fundTotalEar->where('dt_sites.site_id', $siteId);
        $fundTotalEar = $fundTotalEar
            ->join('dt_payments_info', function ($join) use ($startTime, $endTime) {
                $join->on('dt_payments_info.site_id', '=', 'dt_sites.site_id');
                $join->where('dt_payments_info.type', '=', 'live');
                $join->whereBetween('dt_payments_info.time_stamp', [$startTime, $endTime]);
            })
            ->select(
                'dt_payments_info.site_id',
                DB::raw('dt_payments_info.amount * ' . $clientPer . ' as total_earnings'),
            )
            ->get();
        return $fundTotalEar;
    }

    public function getFundraiserViewsData($startDate, $endDate, $siteId)
    {
        $reportData = CrowdfundReports::query();
        if($siteId) $reportData = $reportData->where('site_id', $siteId);
        $reportData = $reportData->whereBetween('date', [$startDate, $endDate])
            ->select('id', 'site_id', 'domains')->get()->toArray();
        $fundViews = 0;
        foreach ($reportData as $key => $value) {
            $domData = json_decode($value['domains'], 1);
            foreach ($domData as $k => $vd) {
                $fundViews += (int) $vd['widgetviews'];
            }
            // $countryData = json_decode($value['countries'], 1);
            // foreach ($countryData as $k => $vc) {
            //     $fundViews += (int) $vc['widgetviews'];
            // }
            // $deviceData = json_decode($value['devices'], 1);
            // foreach ($deviceData as $k => $vd) {
            //     $fundViews += (int) $vd['widgetviews'];
            // }
            // $pageData = json_decode($value['popular_pags'], 1);
            // foreach ($pageData as $k => $vp) {
            //     $fundViews += (int) $vp['widgetviews'];
            // }
        }
        return $fundViews;
    }

    public function getCrowdfundsTopCardQuery($startDate, $endDate, $clientPer, $oldStartDate, $oldEndDate, $siteId)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $currFundTotalEar = $this->getfundTotalEarningsData($startTime, $endTime, $clientPer, $siteId);
        $siteIds = $currFundTotalEar->pluck('site_id')->toArray();
        $totalEar = $currFundTotalEar->sum('total_earnings');
        $totalDonors = $currFundTotalEar->count();
        $averageDonation = $totalDonors ? $totalEar / $totalDonors : 0;
        
        $fundViews = $this->getFundraiserViewsData($startDate, $endDate, $siteId);
        $fundEcpm = $fundViews ? ($totalEar / $fundViews) * 1000 : 0;

        /* previous period data */
        $oldStartTime = strtotime( $oldStartDate.' 00:00:00');
    	$oldEndTime = strtotime( $oldEndDate.' 23:59:59');
        $previousFundTotalEar = $this->getfundTotalEarningsData($oldStartTime, $oldEndTime, $clientPer, $siteId);
        $previousSiteIds = $currFundTotalEar->pluck('site_id')->toArray();
        $previousTotalEar = $previousFundTotalEar->sum('total_earnings');
        $previousTotalDonors = $previousFundTotalEar->count();
        $previousAverageDonation = $previousTotalDonors ? $previousTotalEar / $previousTotalDonors : 0;
        
        $previousFundViews = $this->getFundraiserViewsData($oldStartDate, $oldEndDate, $siteId);
        $previousFundEcpm = $previousFundViews ? ($previousTotalEar / $previousFundViews) * 1000 : 0;

        $earningsPer = $previousTotalEar ? ($totalEar - $previousTotalEar) * 100 / $previousTotalEar : 0;
        $donorsPer = $previousTotalDonors ? ($totalDonors - $previousTotalDonors) * 100 / $previousTotalDonors : 0;
        $averageDonationPer = $previousAverageDonation ? ($averageDonation - $previousAverageDonation) * 100 / $previousAverageDonation : 0;
        $fundViewsPer = $previousFundViews ? ($fundViews - $previousFundViews) * 100 / $previousFundViews : 0;
        $fundEcpmPer = $previousFundEcpm ? ($fundEcpm - $previousFundEcpm) * 100 / $previousFundEcpm : 0;

        $topCardData = [
            'total_earnings' => $totalEar, 'previous_total_earnings' => $previousTotalEar, 'earnings_percentage' => $earningsPer,
            'total_donors' => $totalDonors, 'previous_total_donors' => $previousTotalDonors, 'donors_percentage' => $donorsPer,
            'total_fund_views' => $fundViews, 'previous_total_fund_views' => $previousFundViews, 'fund_views_percentage' => $fundViewsPer,
            'average_donation' => $averageDonation, 'previous_average_donation' => $previousAverageDonation, 'average_donation_percentage' => $averageDonationPer,
            'fund_ecpm' => $fundEcpm, 'previous_fund_ecpm' => $previousFundEcpm, 'fund_ecpm_percentage' => $fundEcpmPer,
        ];
        return $topCardData;
    }
}
