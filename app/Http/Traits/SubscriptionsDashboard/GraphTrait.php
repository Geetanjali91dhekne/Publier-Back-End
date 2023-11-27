<?php

namespace App\Http\Traits\SubscriptionsDashboard;

use App\Models\DailyReport;
use App\Models\OldDailyReport;
use App\Models\OldPageviewsDailyReports;
use App\Models\OldSubscriptionsReports;
use App\Models\OldSubUsers;
use App\Models\PageviewsDailyReports;
use App\Models\Subscriptions;
use App\Models\SubUsers;
use App\Models\SubscriptionsReports;
use Illuminate\Support\Facades\DB;

trait GraphTrait
{
    use TableDataTrait;

    public function getCountriesGraphQuery($startDate, $endDate, $siteId = null)
    {
        $subCountriesGraph = SubscriptionsReports::query();
        if ($siteId) $subCountriesGraph = $subCountriesGraph->where('site_id', $siteId);
        $subCountriesGraph = $subCountriesGraph->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'countries')->get()->toArray();

        $resData = [];
        foreach ($subCountriesGraph as $key => $value) {
            $domData = json_decode($value['countries'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($resData[$k])) {
                    $resData[$k]['country'] = $v['country'];
                    $resData[$k]['pageviews'] = (int) $v['pageviews'];
                } else {
                    $resData[$k]['pageviews'] += (int) $v['pageviews'];
                }
            }
        }

        $oldSubCountriesData = OldSubscriptionsReports::query();
        if($siteId) $oldSubCountriesData = $oldSubCountriesData->where('site_id', $siteId);
        $oldSubCountriesData = $oldSubCountriesData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'countries')->get()->toArray();
        $oldResData = [];
        foreach ($oldSubCountriesData as $key => $value) {
            $domData = json_decode($value['countries'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($oldResData[$k])) {
                    $oldResData[$k]['country'] = $v['country'];
                    $oldResData[$k]['pageviews'] = (int) $v['pageviews'];
                } else {
                    $oldResData[$k]['pageviews'] += (int) $v['pageviews'];
                }
            }
        }

        $bothArr = array_merge($resData, $oldResData);
        $currResData = array();
        foreach ($bothArr as $val) {
            if (!isset($currResData[$val['country']]))
                $currResData[$val['country']] = $val;
            else {
                $currResData[$val['country']]['pageviews'] += (int) $val['pageviews'];
            }
        }
        return array_values($currResData);
    }

    public function getDevicesGraphQuery($startDate, $endDate, $siteId = null)
    {
        $subDevicesGraph = SubscriptionsReports::query();
        if ($siteId) $subDevicesGraph = $subDevicesGraph->where('site_id', $siteId);
        $subDevicesGraph = $subDevicesGraph->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'devices')->get()->toArray();

        $resData = [];
        foreach ($subDevicesGraph as $key => $value) {
            $domData = json_decode($value['devices'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($resData[$k])) {
                    $resData[$k]['device'] = $v['device'];
                    $resData[$k]['pageviews'] = (int) $v['pageviews'];
                } else {
                    $resData[$k]['pageviews'] += (int) $v['pageviews'];
                }
            }
        }

        $oldSubDevicesData = OldSubscriptionsReports::query();
        if($siteId) $oldSubDevicesData = $oldSubDevicesData->where('site_id', $siteId);
        $oldSubDevicesData = $oldSubDevicesData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'devices')->get()->toArray();
        $oldResData = [];
        foreach ($oldSubDevicesData as $key => $value) {
            $domData = json_decode($value['devices'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($oldResData[$k])) {
                    $oldResData[$k]['device'] = $v['device'];
                    $oldResData[$k]['pageviews'] = (int) $v['pageviews'];
                } else {
                    $oldResData[$k]['pageviews'] += (int) $v['pageviews'];
                }
            }
        }

        $bothArr = array_merge($resData, $oldResData);
        $currResData = array();
        foreach ($bothArr as $val) {
            if (!isset($currResData[$val['device']]))
                $currResData[$val['device']] = $val;
            else {
                $currResData[$val['device']]['pageviews'] += (int) $val['pageviews'];
            }
        }
        return array_values($currResData);
    }

    public function oldRevenueAndRpmCalculationQuery($startTime, $endTime, $clientPer, $siteId = null)
    {
        $i = 0;
        for ( $j = $startTime; $j <= $endTime; $j = $j + 86400 ) {
            $totalamount = 0;
            $beginOfDay =  date('d-M-Y', $j);
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
            if($totalamount != ''){
    			$subsTamount = $clientPer * $totalamount;
    			$oldDbReport[$i]['sum_revenue'] = number_format($subsTamount, 2);
    			$oldDbReport[$i]['date'] = $beginOfDay;
    		}else{
    			$oldDbReport[$i]['sum_revenue'] = 0;
                $oldDbReport[$i]['date'] = $beginOfDay;
    		}
            $i++;
    	}
        return $oldDbReport;
    }

    public function newRevenueAndRpmCalculationQuery($startTime, $endTime, $clientPer, $siteId = null)
    {
        $i = 0;
        for ( $j = $startTime; $j <= $endTime; $j = $j + 86400 ) {
    		$totalamount = 0;
            $beginOfDay =  date('d-M-Y', $j);
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
            if($totalamount != ''){
    			$subsTamount = $clientPer * $totalamount;
    			$subRevData[$i]['sum_revenue'] = number_format($subsTamount, 2);
    			$subRevData[$i]['date'] = $beginOfDay;
    		}else{
    			$subRevData[$i]['sum_revenue'] = 0;
                $subRevData[$i]['date'] = $beginOfDay;
    		}
            $i++;
    	}
        return $subRevData;
    }

    public function revenueGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $oldDbReport = $this->oldRevenueAndRpmCalculationQuery($startTime, $endTime, $clientPer, $siteId);
        $subRevData = $this->newRevenueAndRpmCalculationQuery($startTime, $endTime, $clientPer, $siteId);
        $bothArr = array_merge($subRevData, $oldDbReport);
        $newSubRevData = array();
        foreach ($bothArr as $val) {
            if (!isset($newSubRevData[$val['date']])) {
                $newSubRevData[$val['date']] = $val;
            } else {
                $newSubRevData[$val['date']]['sum_revenue'] += $val['sum_revenue'];
            }
        }

        // Sort the array
        usort($newSubRevData, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $newSubRevData;
    }

    public function activeSubscriptionGraphData($startDate, $endDate, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $oldDbActiveSub = OldSubUsers::query();
        if($siteId) $oldDbActiveSub = $oldDbActiveSub->where('site_id', $siteId);
        $oldDbActiveSub = $oldDbActiveSub
            ->where([
                ['site_id', '<=', 1000],
                ['status', '=', 1],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
                ['date_signedup', "<=", $endTime],
            ])
            ->select('date_signedup', DB::raw('count(id) as count_activesub'))
            ->groupBy('date_signedup')
            ->get()->toArray();
        foreach ($oldDbActiveSub as $key => $activeSub) {
            $oldDbActiveSub[$key]['date'] = date('d-M-Y', $activeSub['date_signedup']);
        }

        $newDbActiveSub = SubUsers::query();
        if($siteId) $newDbActiveSub = $newDbActiveSub->where('site_id', $siteId);
        $newDbActiveSub = $newDbActiveSub
            ->where([
                ['status', '=', 1],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
                ['date_signedup', "<=", $endTime],
            ])
            ->select('date_signedup', DB::raw('count(id) as count_activesub'))
            ->groupBy('date_signedup')
            ->get()->toArray();
        foreach ($newDbActiveSub as $key => $activeSub) {
            $newDbActiveSub[$key]['date'] = date('d-M-Y', $activeSub['date_signedup']);
        }

        $bothArr = array_merge($newDbActiveSub, $oldDbActiveSub);
        $newActiveSubData = array();
        // $newActSubCount = 0;
        foreach ($bothArr as $val) {
            // $newActSubCount += $val['count_activesub'];
            if (!isset($newActiveSubData[$val['date']]))
                $newActiveSubData[$val['date']] = $val;
            else
                $newActiveSubData[$val['date']]['count_activesub'] += $val['count_activesub'];
        }

        // Sort the array
        usort($newActiveSubData, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $newActiveSubData;
    }

    public function newSubscriptionGraphData($startDate, $endDate, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $oldDbReport = OldSubUsers::query();
        if ($siteId) $oldDbReport = $oldDbReport->where('site_id', $siteId);
        $oldDbReport = $oldDbReport
            ->where([
                ['site_id', '<=', 1000],
                ['status', '=', 1],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
            ])
            ->whereBetween('date_signedup', [$startTime, $endTime])
            ->select('date_signedup', DB::raw('count(im_subs_users.id) as count_newsub'))
            ->groupBy('date_signedup')
            ->get()->toArray();

        foreach ($oldDbReport as $key => $newsub) {
            $oldDbReport[$key]['date'] = date('d-M-Y', $newsub['date_signedup']);
        }

        $newsubData = SubUsers::query();
        if ($siteId) $newsubData = $newsubData->where('site_id', $siteId);
        $newsubData = $newsubData
            ->where([
                ['status', '=', 1],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
            ])
            ->whereBetween('date_signedup', [$startTime, $endTime])
            ->select('date_signedup', DB::raw('count(im_subs_users.id) as count_newsub'))
            ->groupBy('date_signedup')
            ->get()->toArray();

        foreach ($newsubData as $key => $newsub) {
            $newsubData[$key]['date'] = date('d-M-Y', $newsub['date_signedup']);
        }

        $bothArr = array_merge($newsubData, $oldDbReport);
        $newSubData = array();
        // $newSubCount = 0;
        foreach ($bothArr as $val) {
            // $newSubCount += $val['count_newsub'];
            if (!isset($newSubData[$val['date']]))
                $newSubData[$val['date']] = $val;
            else
                $newSubData[$val['date']]['count_newsub'] += $val['count_newsub'];
        }

        // Sort the array
        usort($newSubData, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $newSubData;
    }

    public function unSubcribesGraphData($startDate, $endDate, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $oldDbUnSub = OldSubUsers::query();
        if ($siteId) $oldDbUnSub = $oldDbUnSub->where('site_id', $siteId);
        $oldDbUnSub = $oldDbUnSub
            ->where([
                ['site_id', '<=', 1000],
                ['status', '=', 0],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
            ])
            ->whereBetween('cancel_date', [$startTime, $endTime])
            ->select('cancel_date', DB::raw('count(im_subs_users.id) as count_unsub'))
            ->groupBy('cancel_date')
            ->get()->toArray();

        foreach ($oldDbUnSub as $key => $unsub) {
            $oldDbUnSub[$key]['date'] = date('d-M-Y', $unsub['cancel_date']);
        }

        $newDbUnSub = SubUsers::query();
        if ($siteId) $newDbUnSub = $newDbUnSub->where('site_id', $siteId);
        $newDbUnSub = $newDbUnSub
            ->where([
                ['status', '=', 0],
                ['subs_mode', '=', 'live'],
                ['stripe_customerid', "like", "cus_" . "%"],
            ])
            ->whereBetween('cancel_date', [$startTime, $endTime])
            ->select('cancel_date', DB::raw('count(im_subs_users.id) as count_unsub'))
            ->groupBy('cancel_date')
            ->get()->toArray();

        foreach ($newDbUnSub as $key => $unsub) {
            $newDbUnSub[$key]['date'] = date('d-M-Y', $unsub['cancel_date']);
        }

        $bothArr = array_merge($oldDbUnSub, $newDbUnSub);
        $unSubData = array();
        $unSubCount = 0;
        foreach ($bothArr as $val) {
            $unSubCount += $val['count_unsub'];
            if (!isset($unSubData[$val['date']]))
                $unSubData[$val['date']] = $val;
            else
                $unSubData[$val['date']]['count_unsub'] += $val['count_unsub'];
        }
        foreach ($unSubData as $key => $unsub) {
            $unSubData[$key]['date'] = $unsub['date'];
        }
        // Sort the array
        usort($unSubData, function ($element1, $element2) {
            $datetime1 = strtotime($element1['date']);
            $datetime2 = strtotime($element2['date']);
            return $datetime1 - $datetime2;
        });
        return $unSubData;
    }

    public function rpmGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $subRpmData = $this->revenueGraphData($startDate, $endDate, $clientPer, $siteId);
        
        $oldPageview = OldPageviewsDailyReports::query();
        if ($siteId) $oldPageview = $oldPageview->where('site_id', $siteId);
        $oldPageview = $oldPageview->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%d-%b-%Y") as date'),
                DB::raw('SUM(subscription_pageviews) as sum_pageview')
            )
            ->groupBy('date')
            ->get()->toArray();

        $newPageview = PageviewsDailyReports::query();
        if ($siteId) $newPageview = $newPageview->where('site_id', $siteId);
        $newPageview = $newPageview->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%d-%b-%Y") as date'),
                DB::raw('SUM(subscription_pageviews) as sum_pageview')
            )
            ->groupBy('date')
            ->get()->toArray();

        $bothArr = array_merge($oldPageview, $newPageview);
        $pageviewSubData = array();
        foreach ($bothArr as $val) {
            if (!isset($pageviewSubData[$val['date']]))
                $pageviewSubData[$val['date']] = $val;
            else
                $pageviewSubData[$val['date']]['sum_pageview'] += $val['sum_pageview'];
        }        
        foreach ($subRpmData as $key => $rpm) {
            if (array_key_exists($rpm['date'], $pageviewSubData)) {
                $pageviewArr = $pageviewSubData[$rpm['date']];
                $pageview = (float) $pageviewArr['sum_pageview'];
                $subRpmData[$key]['sum_rpm'] = $pageview ? ($rpm['sum_revenue'] * 1000) / $pageview : 0;
            } else {
                $subRpmData[$key]['sum_rpm'] = 0;
            }
        }
        return $subRpmData;
    }

    public function getTotalPageviewGraphQuery($startDate, $endDate, $siteIds)
    {
        $pageviewData = PageviewsDailyReports::whereBetween('date', [$startDate, $endDate])
            ->select('site_id', DB::raw('SUM(dt_pageviews_daily_reports.pageviews + dt_pageviews_daily_reports.subscription_pageviews) as total_pageview'))
            ->groupBy('site_id')->whereIn('site_id', $siteIds)->get()->toArray();
        $pageviewData = array_replace_recursive(array_combine(array_column($pageviewData, "site_id"), $pageviewData));
        return $pageviewData;
    }

    public function getDomainStatsGraphQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $newDbDomain = $this->getNewDbDomainsData($startDate, $endDate, $siteId);
        $oldDbDomain = $this->getOldDbDomainsData($startDate, $endDate, $siteId);
        $bothArr = array_merge($newDbDomain, $oldDbDomain);
        $currResData = array();
        $totalPageview = 0;
        $totalSubPageview = 0;
        foreach ($bothArr as $val) {
            $totalSubPageview += $val['sub_pageviews'];
            $totalPageview += $val['total_pageview'];
            if (!isset($currResData[$val['domain_name']]))
                $currResData[$val['domain_name']] = $val;
            else {
                $currResData[$val['domain_name']]['sub_pageviews'] += $val['sub_pageviews'];
                $currResData[$val['domain_name']]['total_pageview'] += $val['total_pageview'];
            }
        }
        foreach ($currResData as $key => $site) {
            $currResData[$key]['total_pvs_per'] = $totalPageview ? ($site['total_pageview'] / $totalPageview) * 100 : 0;
            $currResData[$key]['total_sub_pvs_per'] = $totalSubPageview ? ($site['sub_pageviews'] / $totalSubPageview) * 100 : 0;
        }

        $previousNewDbDomain = $this->getNewDbDomainsData($oldStartDate, $oldEndDate, $siteId);
        $previousOldDbDomain = $this->getOldDbDomainsData($oldStartDate, $oldEndDate, $siteId);
        $previousBothArr = array_merge($previousNewDbDomain, $previousOldDbDomain);
        $previousResData = array();
        foreach ($previousBothArr as $val) {
            if (!isset($previousResData[$val['domain_name']]))
                $previousResData[$val['domain_name']] = $val;
            else {
                $previousResData[$val['domain_name']]['sub_pageviews'] += $val['sub_pageviews'];
                $previousResData[$val['domain_name']]['total_pageview'] += $val['total_pageview'];
            }
        }

        foreach ($currResData as $key => $sites) {
            if (array_key_exists($sites['domain_name'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['domain_name']];
                $oldSubPV = (float) $sitesPercentage['sub_pageviews'];
                $oldTotalPV = (float) $sitesPercentage['total_pageview'];
                $currResData[$key]['sub_pageview_per'] = $oldSubPV ? ($sites['sub_pageviews'] - $oldSubPV) * 100 / $oldSubPV : 0;
                $currResData[$key]['pageview_per'] = $oldTotalPV ? ($sites['total_pageview'] - $oldTotalPV) * 100 / $oldTotalPV : 0;
            } else {
                $currResData[$key]['sub_pageview_per'] = 0;
                $currResData[$key]['pageview_per'] = 0;
            }
        }
        return array_values($currResData);
    }

    public function getCountryStatsGraphQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $newDbCountries = $this->getNewDbCountriesData($startDate, $endDate, $siteId);
        $oldDbCountries = $this->getOldDbCountriesData($startDate, $endDate, $siteId);
        $bothArr = array_merge($newDbCountries, $oldDbCountries);
        $currResData = array();
        $totalPageview = 0;
        $totalSubPageview = 0;
        foreach ($bothArr as $val) {
            $totalSubPageview += $val['sub_pageviews'];
            $totalPageview += $val['total_pageview'];
            if (!isset($currResData[$val['country']]))
                $currResData[$val['country']] = $val;
            else {
                $currResData[$val['country']]['sub_pageviews'] += $val['sub_pageviews'];
                $currResData[$val['country']]['total_pageview'] += $val['total_pageview'];
            }
        }
        foreach ($currResData as $key => $site) {
            $currResData[$key]['total_pvs_per'] = $totalPageview ? ($site['total_pageview'] / $totalPageview) * 100 : 0;
            $currResData[$key]['total_sub_pvs_per'] = $totalSubPageview ? ($site['sub_pageviews'] / $totalSubPageview) * 100 : 0;
        }

        $previousNewDbCountries = $this->getNewDbCountriesData($oldStartDate, $oldEndDate, $siteId);
        $previousOldDbCountries = $this->getOldDbCountriesData($oldStartDate, $oldEndDate, $siteId);
        $previousBothArr = array_merge($previousNewDbCountries, $previousOldDbCountries);
        $previousResData = array();
        foreach ($previousBothArr as $val) {
            if (!isset($previousResData[$val['country']]))
                $previousResData[$val['country']] = $val;
            else {
                $previousResData[$val['country']]['sub_pageviews'] += $val['sub_pageviews'];
                $previousResData[$val['country']]['total_pageview'] += $val['total_pageview'];
            }
        }

        foreach ($currResData as $key => $sites) {
            if (array_key_exists($sites['country'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['country']];
                $oldSubPV = (float) $sitesPercentage['sub_pageviews'];
                $oldTotalPV = (float) $sitesPercentage['total_pageview'];
                $currResData[$key]['sub_pageview_per'] = $oldSubPV ? ($sites['sub_pageviews'] - $oldSubPV) * 100 / $oldSubPV : '0';
                $currResData[$key]['pageview_per'] = $oldTotalPV ? ($sites['total_pageview'] - $oldTotalPV) * 100 / $oldTotalPV : '0';
            } else {
                $currResData[$key]['sub_pageview_per'] = '0';
                $currResData[$key]['pageview_per'] = '0';
            }
        }
        return array_values($currResData);
    }
}