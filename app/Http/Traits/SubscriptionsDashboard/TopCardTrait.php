<?php

namespace App\Http\Traits\SubscriptionsDashboard;

use App\Models\OldPageviewsDailyReports;
use App\Models\OldSites;
use App\Models\OldSubUsers;
use App\Models\PageviewsDailyReports;
use App\Models\Sites;
use App\Models\Subscriptions;
use App\Models\SubUsers;
use Illuminate\Support\Facades\DB;

trait TopCardTrait
{
    public function getOldSubUsersQuery($startTime, $endTime, $siteId = null) {
        $oldDbReport = OldSubUsers::query();
        if($siteId) $oldDbReport = $oldDbReport->where('site_id', $siteId);
        $oldDbReport = $oldDbReport
            ->where([
                ['site_id', '<=', 1000],
                ['status', '=', 1],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
            ])
            ->whereBetween('date_signedup', [$startTime, $endTime]);
        return $oldDbReport;
    }

    public function getNewSubUsersQuery($startTime, $endTime, $siteId = null) {
        $adData = SubUsers::query();
        if($siteId) $adData = $adData->where('site_id', $siteId);
        $adData = $adData
            ->where([
                ['status', '=', 1],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
            ])
            ->whereBetween('date_signedup', [$startTime, $endTime]);
        return $adData;
    }

    public function getOldUnSubscriptionCount($startTime, $endTime, $siteId = null) {
        $oldDbUnSub = OldSites::query();
        if($siteId) $oldDbUnSub = $oldDbUnSub->where('im_sites.id', $siteId);
        $oldDbUnSub = $oldDbUnSub->
            join('im_subs_users', function ($join) use ($startTime, $endTime) {
                $join->on('im_subs_users.site_id', '=', 'im_sites.id');
                $join->where('im_subs_users.status', '=', '0');
                $join->where('im_subs_users.subs_mode', '=', 'live');
                $join->whereBetween('im_subs_users.cancel_date', [$startTime, $endTime]);
            })->select('im_subs_users.id')->count();
        return $oldDbUnSub;
    }

    public function getNewUnSubscriptionCount($startTime, $endTime, $siteId = null) {
        $newDbUnSub = Sites::query();
        if($siteId) $newDbUnSub = $newDbUnSub->where('dt_sites.site_id', $siteId);
        $newDbUnSub = $newDbUnSub->
            join('im_subs_users', function ($join) use ($startTime, $endTime) {
                $join->on('im_subs_users.site_id', '=', 'dt_sites.site_id');
                $join->where('im_subs_users.status', '=', '0');
                $join->where('im_subs_users.subs_mode', '=', 'live');
                $join->whereBetween('im_subs_users.cancel_date', [$startTime, $endTime]);
            })->select('im_subs_users.id')->count();
        return $newDbUnSub;
    }

    public function getOldActiveSubQuery($startTime, $endTime, $siteId = null) {
        $oldDbReport = OldSubUsers::query();
        if($siteId) $oldDbReport = $oldDbReport->where('site_id', $siteId);
        $oldDbReport = $oldDbReport
            ->where([
                ['site_id', '<=', 1000],
                ['status', '=', 1],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
                ['date_signedup', "<=", $endTime],
            ]);
            // ->whereBetween('date_signedup', [$startTime, $endTime]);
        return $oldDbReport;
    }

    public function getNewActiveSubQuery($startTime, $endTime, $siteId = null) {
        $adData = SubUsers::query();
        if($siteId) $adData = $adData->where('site_id', $siteId);
        $adData = $adData
            ->where([
                ['status', '=', 1],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
                ['date_signedup', "<=", $endTime],
            ]);
            // ->whereBetween('date_signedup', [$startTime, $endTime]);
        return $adData;
    }

    public function getSubscriptionPageviewData($startDate, $endDate, $siteId = null)
    {
        $newDbPageview = PageviewsDailyReports::query();
        if($siteId) $newDbPageview = $newDbPageview->where('site_id', $siteId);
        $newDbPageview = $newDbPageview->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('SUM(subscription_pageviews) as subspvs'))->groupBy('date')->get();

        $oldDbPageview = OldPageviewsDailyReports::query();
        if($siteId) $oldDbPageview = $oldDbPageview->where('site_id', $siteId);
        $oldDbPageview = $oldDbPageview->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('SUM(subscription_pageviews) as subspvs'))->groupBy('date')->get();
        $pageview = $newDbPageview->sum('subspvs') + $oldDbPageview->sum('subspvs');
        return $pageview;
    }

    public function getNewDbRevenue($startTime, $endTime, $clientPer, $siteId = null) 
    {
        $subNewAmount = 0;
        for ( $j = $startTime; $j <= $endTime; $j = $j + 86400 ) {
    		$totalamount = 0;
            $adData = SubUsers::query();
            if($siteId) $adData = $adData->where('site_id', $siteId);
            $adData = $adData
                ->where([
                    ['status', '=', 1],
                    ['subs_mode', '=', 'live'],
                    ['stripe_customerid', "like", "cus_" . "%"],
                ])
            ->whereRaw('? between date_signedup and next_charge_date', [$j])
            ->select('plan_type', 'date_signedup', 'amount')->get()->toArray();

    		foreach ($adData as $key => $value) {
    			if($value['plan_type'] == 'Monthly'){
    				$year = date('Y', $value['date_signedup']);
    				$month = date('m', $value['date_signedup']);
    				$number = cal_days_in_month(CAL_GREGORIAN, ''.$month.'', $year);
    				$totalamount +=  $value['amount']/$number;
    			}else if($value['plan_type'] == 'Yearly'){
    				$totalamount +=  $value['amount']/365;
    			}else if($value['plan_type'] == 'Life'){
    				$totalamount +=  $value['amount'];
    			}
    		}
    		$subNewAmount += $clientPer * number_format($totalamount, 2);
    	}
        return $subNewAmount;
    }

    public function getOldDbRevenue($startTime, $endTime, $clientPer, $siteId = null) 
    {
        $subOldAmount = 0;
        for ( $j = $startTime; $j <= $endTime; $j = $j + 86400 ) {
            $totalamount = 0;

            $oldDbRev = OldSubUsers::query();
            if($siteId) $oldDbRev = $oldDbRev->where('site_id', $siteId);
            $oldDbRev = $oldDbRev
                ->where([
                    ['status', '=', 1],
                    ['subs_mode', '=', 'live'],
                    ['stripe_customerid', "like", "cus_" . "%"],
                ])
            ->whereRaw('? between date_signedup and next_charge_date', [$j])
            ->select('plan_type', 'date_signedup', 'amount')->get()->toArray();

    		foreach ($oldDbRev as $key => $value) {
    			if($value['plan_type'] == 'Monthly'){
    				$year = date('Y', $value['date_signedup']);
    				$month = date('m', $value['date_signedup']);
    				$number = cal_days_in_month(CAL_GREGORIAN, ''.$month.'', $year);
    				$totalamount +=  $value['amount']/$number;
    			}else if($value['plan_type'] == 'Yearly'){
    				$totalamount +=  $value['amount']/365;
    			}else if($value['plan_type'] == 'Life'){
    				$totalamount +=  $value['amount'];
    			}
    		}
            $subOldAmount += $clientPer * number_format($totalamount, 2);
    	}
        return $subOldAmount;
    }

    public function getTopCardReportQuery($startDate, $endDate, $clientPer, $oldStartDate, $oldEndDate, $siteId)
    {
        $startTime 	= strtotime($startDate);
    	$endTime 	= strtotime($endDate);
        
        /* old db revenue and new db rev */
        $oldDbRev = $this->getOldDbRevenue($startTime, $endTime, $clientPer, $siteId);
        $newDbRev = $this->getNewDbRevenue($startTime, $endTime, $clientPer, $siteId);
        $rev = $newDbRev + $oldDbRev;

        /* Rpm data */
        $pageview = $this->getSubscriptionPageviewData($startDate, $endDate, $siteId);
        $currRpm = $pageview ? ($rev * 1000)/$pageview : '0';

        /* active sub */
        $oldDbActSub = $this->getOldActiveSubQuery($startTime, $endTime, $siteId)->count();
        $newDbActSub = $this->getNewActiveSubQuery($startTime, $endTime, $siteId)->count();
        $totalActiveSub = $oldDbActSub + $newDbActSub;

        /* new sub */
        $oldDbNewSub = $this->getOldSubUsersQuery($startTime, $endTime, $siteId)->count();
        $newDbNewSub = $this->getNewSubUsersQuery($startTime, $endTime, $siteId)->count();
        $totalNewSub = $oldDbNewSub + $newDbNewSub;

        /* un subs */
        $oldDbUnSub = $this->getOldUnSubscriptionCount($startTime, $endTime, $siteId);
        $newDbUnSub = $this->getNewUnSubscriptionCount($startTime, $endTime, $siteId);
        $totalUnSub = $oldDbUnSub + $newDbUnSub;

        /* previos period data */
        $oldStartTime = strtotime( $oldStartDate.' 00:00:00');
    	$oldEndTime = strtotime( $oldEndDate.' 23:59:59');

        /* previos revenue */
        $oldDbPreviosRev = $this->getOldDbRevenue($oldStartTime, $oldEndTime, $clientPer, $siteId);
        $newDbPreviosRev = $this->getNewDbRevenue($oldStartTime, $oldEndTime, $clientPer, $siteId);
        $oldRev = $oldDbPreviosRev + $newDbPreviosRev;

        /* previous RPM */
        $oldPageview = $this->getSubscriptionPageviewData($oldStartDate, $oldEndDate, $siteId);
        $oldRpm = $oldPageview ? ($oldRev * 1000)/$oldPageview : '0';

        /* previos active sub */
        $oldDbPreviousActSub = $this->getOldActiveSubQuery($oldStartTime, $oldEndTime, $siteId)->count();
        $newDbPreviousActSub = $this->getNewActiveSubQuery($oldStartTime, $oldEndTime, $siteId)->count();
        $totalPreviosActiveSub = $oldDbPreviousActSub + $newDbPreviousActSub;

        /* previos new sub */
        $oldDbPreviousNewSub = $this->getOldSubUsersQuery($oldStartTime, $oldEndTime, $siteId)->count();
        $newDbPreviousNewSub = $this->getNewSubUsersQuery($oldStartTime, $oldEndTime, $siteId)->count();
        $totalPreviosNewSub = $oldDbPreviousNewSub + $newDbPreviousNewSub;
        
        /* previous un subs */
        $oldDbPreviousUnSub = $this->getOldUnSubscriptionCount($oldStartTime, $oldEndTime, $siteId);
        $newDbPreviousUnSub = $this->getNewUnSubscriptionCount($oldStartTime, $oldEndTime, $siteId);
        $totalPreviousUnSub = $oldDbPreviousUnSub + $newDbPreviousUnSub;

        $revenuePer = $oldRev ? ($rev - $oldRev) * 100 / $oldRev : '0';
        $rpmPer = $oldRpm ? ($currRpm - $oldRpm) * 100 / $oldRpm : '0';
        $newSubPer = $totalPreviosNewSub ? ($totalNewSub - $totalPreviosNewSub) * 100 / $totalPreviosNewSub : '0';
        $activeSubPer = $totalPreviosActiveSub ? ($totalActiveSub - $totalPreviosActiveSub) * 100 / $totalPreviosActiveSub : '0';
        $unSubPer = $totalPreviousUnSub ? ($totalUnSub - $totalPreviousUnSub) * 100 / $totalPreviousUnSub : '0';

        $topCardData = [
            'total_revenue' => $rev, 'previous_total_revenue' => $oldRev, 'revenue_percentage' => $revenuePer,
            'total_rpm' => $currRpm, 'previous_total_rpm' => $oldRpm, 'rpm_percentage' => $rpmPer,
            'total_active_sub' => $totalActiveSub, 'previous_total_active_sub' => $totalPreviosActiveSub, 'active_sub_percentage' => $activeSubPer,
            'total_new_sub' => $totalNewSub, 'previous_total_new_sub' => $totalPreviosNewSub, 'new_sub_percentage' => $newSubPer,
            'total_unsub' => $totalUnSub, 'previous_total_unsub' => $totalPreviousUnSub, 'unsub_percentage' => $unSubPer,
        ];
        return $topCardData;
    }
}
