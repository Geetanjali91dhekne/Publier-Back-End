<?php

namespace App\Http\Traits\AdBlockRecovery;

use App\Models\AdblockReports;
use App\Models\AdblockWidgetReports;
use App\Models\CrowdfundReports;
use App\Models\OldPageviewsDailyReports;
use App\Models\PageviewsDailyReports;
use App\Models\PaymentsInfo;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;

trait TopCardTrait
{
    public function getAdblockPageviewData($startDate, $endDate, $siteId)
    {
        $adBlockData = PageviewsDailyReports::query();
        if($siteId) $adBlockData = $adBlockData->where('site_id', $siteId);
        $adBlockData = $adBlockData->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('ifnull(SUM(pageviews), 0) AS prebidpvs'),
                DB::raw('ifnull(SUM(subscription_pageviews), 0) AS subspvs'),
                DB::raw('ifnull(SUM(adblock_pageviews), 0) AS abpvs'),
                DB::raw('SUM(pageviews + subscription_pageviews + adblock_pageviews) as total_pageview'),
            )
            ->get();
        return $adBlockData;
    }
    public function getOldDbAdblockPageviewData($startDate, $endDate, $siteId)
    {
        $oldDbAdBlockData = OldPageviewsDailyReports::query();
        if($siteId) $oldDbAdBlockData = $oldDbAdBlockData->where('site_id', $siteId);
        $oldDbAdBlockData = $oldDbAdBlockData->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('ifnull(SUM(pageviews), 0) AS prebidpvs'),
                DB::raw('ifnull(SUM(subscription_pageviews), 0) AS subspvs'),
                DB::raw('ifnull(SUM(adblock_pageviews), 0) AS abpvs'),
                DB::raw('SUM(pageviews + subscription_pageviews + adblock_pageviews) as total_pageview'),
            )
            ->get();
        return $oldDbAdBlockData;
    }

    public function getAdblockByWidgetId($startDate, $endDate, $siteId, $widgetId)
    {
        $adblock = AdblockWidgetReports::query();
        $adblock = $adblock->where('site_id', $siteId)
            ->where('widget_id', $widgetId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                'site_id', 'widget_id',
                DB::raw('ifnull(SUM(pageviews), 0) AS abpvs'),
                DB::raw('ifnull((SUM(whitelist_click) + SUM(link_click)) * 100 / SUM(pageviews), 0) AS  notice_engagement_rate'),
                DB::raw('ifnull(SUM(recovered_adblock_user) * 100 / SUM(pageviews), 0) AS removal_rate'),
            )
            ->get();

        // $totalPV = PageviewsDailyReports::query();
        // $totalPV = $totalPV->where('site_id', $siteId)
        //     ->whereBetween('date', [$startDate, $endDate])
        //     ->select(DB::raw('SUM(pageviews + subscription_pageviews) as total_pageview'))
        //     ->get();
        // $total_pageview = $totalPV->sum('total_pageview');
        $abpvs = $adblock->sum('abpvs');
        $engagement_rate = $adblock->sum('notice_engagement_rate');
        $removal_rate = $adblock->sum('removal_rate');
        return ['abpv'=> $abpvs, 'pv_sub_pv'=> $abpvs, 'engagement_rate' => $engagement_rate, 'removal_rate' => $removal_rate];
    }

    public function getAdBlockTopCardQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null, $widgetId = null)
    {
        if($widgetId) {
            $adblock = $this->getAdblockByWidgetId($startDate, $endDate, $siteId, $widgetId);
            $previousAdblock = $this->getAdblockByWidgetId($oldStartDate, $oldEndDate, $siteId, $widgetId);

            $abUserPercentage = $adblock['pv_sub_pv'] ? 100 * ($adblock['abpv'] / ($adblock['pv_sub_pv'])) : 0;
            $previousAbUserPercentage = $previousAdblock['pv_sub_pv'] ? 100 * ($previousAdblock['abpv'] / ($previousAdblock['pv_sub_pv'])) : 0;
            
            $pvsAdblockPer = $previousAdblock['abpv'] ? ($adblock['abpv'] - $previousAdblock['abpv']) * 100 / $previousAdblock['abpv'] : 0;
            $abUserPer = $previousAbUserPercentage ? ($abUserPercentage - $previousAbUserPercentage) * 100 / $previousAbUserPercentage : 0;
            $engagementRatePer = $previousAdblock['engagement_rate'] ? ($adblock['engagement_rate'] - $previousAdblock['engagement_rate']) * 100 / $previousAdblock['engagement_rate'] : 0;
            $removalRatePer = $previousAdblock['removal_rate'] ? ($adblock['removal_rate'] - $previousAdblock['removal_rate']) * 100 / $previousAdblock['removal_rate'] : 0;

            return $topCardData = [
                'total_ab_pageview' => $adblock['abpv'], 'previous_total_ab_pageview' => $previousAdblock['abpv'], 'ab_pageview_percentage' => $pvsAdblockPer,
                'ab_user_per' => $abUserPercentage, 'previous_ab_user_per' => $previousAbUserPercentage, 'ab_user_per_percentage' => $abUserPer,
                'engagement_rate' => $adblock['engagement_rate'], 'previous_engagement_rate' => $previousAdblock['engagement_rate'], 'engagement_rate_percentage' => $engagementRatePer,
                'removal_rate' => $adblock['removal_rate'], 'previous_removal_rate' => $previousAdblock['removal_rate'], 'removal_rate_percentage' => $removalRatePer
            ];
        }

        $adBlockData = $this->getAdblockPageviewData($startDate, $endDate, $siteId);
        $oldDbAdBlockData = $this->getOldDbAdblockPageviewData($startDate, $endDate, $siteId);
        
        $pvsAdblock = $adBlockData->sum('abpvs') + $oldDbAdBlockData->sum('abpvs');
        $toalPvsAdblock = $adBlockData->sum('total_pageview') + $oldDbAdBlockData->sum('total_pageview');
        $abUserPercentage = $toalPvsAdblock ? 100 * ($pvsAdblock / $toalPvsAdblock) : 0;

        /* previous period data */
        $previousAdBlockData = $this->getAdblockPageviewData($oldStartDate, $oldEndDate, $siteId);
        $previousOldDbAdBlockData = $this->getOldDbAdblockPageviewData($oldStartDate, $oldEndDate, $siteId);
        
        $previousPvsAdblock = $previousAdBlockData->sum('abpvs') + $previousOldDbAdBlockData->sum('abpvs');
        $previousToalPvsAdblock = $previousAdBlockData->sum('total_pageview') + $previousOldDbAdBlockData->sum('total_pageview');
        $previousAbUserPercentage = $previousToalPvsAdblock ? 100 * ($previousPvsAdblock / $previousToalPvsAdblock) : 0;

        /* percentage calculation */
        $pvsAdblockPer = $previousPvsAdblock ? ($pvsAdblock - $previousPvsAdblock) * 100 / $previousPvsAdblock : 0;
        $abUserPer = $previousAbUserPercentage ? ($abUserPercentage - $previousAbUserPercentage) * 100 / $previousAbUserPercentage : 0;

        $topCardData = [
            'total_ab_pageview' => $pvsAdblock, 'previous_total_ab_pageview' => $previousPvsAdblock, 'ab_pageview_percentage' => $pvsAdblockPer,
            'ab_user_per' => $abUserPercentage, 'previous_ab_user_per' => $previousAbUserPercentage, 'ab_user_per_percentage' => $abUserPer,
            'engagement_rate' => 0, 'previous_engagement_rate' => 0, 'engagement_rate_percentage' => 0,
            'removal_rate' => 0, 'previous_removal_rate' => 0, 'removal_rate_percentage' => 0
        ];
        return $topCardData;
    }
}
