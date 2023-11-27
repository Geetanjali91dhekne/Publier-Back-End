<?php

namespace App\Http\Traits\SubscriptionsDashboard;

use App\Models\DailyReport;
use App\Models\OldDailyReport;
use App\Models\OldPageviewsDailyReports;
use App\Models\OldSites;
use App\Models\OldSubscriptionsReports;
use App\Models\OldSubUsers;
use App\Models\OldSubUsersLogs;
use App\Models\PageviewsDailyReports;
use App\Models\Sites;
use App\Models\SubscriptionsReports;
use App\Models\SubUsers;
use App\Models\SubUsersLogs;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;

use danielme85\Geoip2\Facade\Reader;

trait TableDataTrait
{
    public function getWidget1TableReportQuery($startDate, $endDate, $clientPer, $siteId = null)
    {
        $subWidget1Data = DailyReport::query();
        if($siteId) $subWidget1Data = $subWidget1Data->where('im_daily_reports.site_id', $siteId);
        $subWidget1Data = $subWidget1Data->whereBetween('im_daily_reports.date', [$startDate, $endDate])
            // ->join('dt_subscriptions', 'im_daily_reports.site_id', '=', 'dt_subscriptions.site_id')
            ->select(
                'im_daily_reports.date',
                // 'dt_subscriptions.subscription_pricing',
                // 'dt_subscriptions.notice_location',
                DB::raw('SUM(im_daily_reports.impressions) as total_impressions'),
                DB::raw('SUM(im_daily_reports.clicks) as total_clicks'),
                DB::raw('0 as subscriptions'),
                DB::raw('0 as converstions_ratio'),
            )
            ->from('im_daily_reports')
            ->groupBy('im_daily_reports.date')
            ->get();

        return $subWidget1Data;
    }

    public function getUnsubscribingReasonQuery($startDate, $endDate, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $oldDbUnSub = OldSubUsers::query();
        if($siteId) $oldDbUnSub = $oldDbUnSub->where('im_subs_users.site_id', $siteId);
        $oldDbUnSub = $oldDbUnSub
            ->where([
                ['im_subs_users.site_id', '<=', 1000],
                ['im_subs_users.status', '=', 0],
                ['im_subs_users.subs_mode', '=', 'live'],
            ])
            ->whereBetween('im_subs_users.cancel_date', [$startTime, $endTime])
            ->join('im_sites', function ($join) {
                $join->on('im_sites.id', '=', 'im_subs_users.site_id');
            })
            ->select('im_subs_users.reason_for_cancel', DB::raw('count(im_subs_users.id) as count_unsub'))
            ->groupBy('im_subs_users.reason_for_cancel')
            ->get()->toArray();

        $newDbUnSub = SubUsers::query();
        if($siteId) $newDbUnSub = $newDbUnSub->where('im_subs_users.site_id', $siteId);
        $newDbUnSub = $newDbUnSub
            ->where([
                ['im_subs_users.status', '=', 0],
                ['im_subs_users.subs_mode', '=', 'live'],
            ])
            ->whereBetween('im_subs_users.cancel_date', [$startTime, $endTime])
            ->join('dt_sites', function ($join) {
                $join->on('dt_sites.site_id', '=', 'im_subs_users.site_id');
            })
            ->select('im_subs_users.reason_for_cancel', DB::raw('count(im_subs_users.id) as count_unsub'))
            ->groupBy('im_subs_users.reason_for_cancel')
            ->get()->toArray();

        $bothArr = array_merge($oldDbUnSub, $newDbUnSub);
        $unSubData = array();
        $unSubCount = 0;
        foreach ($bothArr as $val) {
            $unSubCount += $val['count_unsub'];
            if (!isset($unSubData[$val['reason_for_cancel']]))
                $unSubData[$val['reason_for_cancel']] = $val;
            else
                $unSubData[$val['reason_for_cancel']]['count_unsub'] += $val['count_unsub'];
        }

        foreach ($unSubData as $key => $sites) {
            $unSubData[$key]['percentage'] = $unSubCount ? ($sites['count_unsub'] / $unSubCount) * 100 : 0;
        }
        return ['total_unsub' => $unSubCount, 'unsub_data' => array_values($unSubData)];
    }

    public function getTotalPageviewQuery($startDate, $endDate, $siteIds)
    {
        $pageviewData = PageviewsDailyReports::whereBetween('date', [$startDate, $endDate])
            ->select('site_id', DB::raw('SUM(dt_pageviews_daily_reports.pageviews + dt_pageviews_daily_reports.subscription_pageviews) as total_pageview'))
            ->groupBy('site_id')->whereIn('site_id', $siteIds)->get()->toArray();
        $pageviewData = array_replace_recursive(array_combine(array_column($pageviewData, "site_id"), $pageviewData));
        return $pageviewData;
    }

    public function getOldTotalPageviewQuery($startDate, $endDate, $siteIds)
    {
        $oldPageviewData = OldPageviewsDailyReports::whereBetween('date', [$startDate, $endDate])
            ->select('site_id', DB::raw('SUM(dt_pageviews_daily_reports.pageviews + dt_pageviews_daily_reports.subscription_pageviews) as total_pageview'))
            ->groupBy('site_id')->whereIn('site_id', $siteIds)->get()->toArray();
        $oldPageviewData = array_replace_recursive(array_combine(array_column($oldPageviewData, "site_id"), $oldPageviewData));
        return $oldPageviewData;
    }

    public function getNewDbDomainsData($startDate, $endDate, $siteId)
    {
        $subDomainData = SubscriptionsReports::query();
        if($siteId) $subDomainData = $subDomainData->where('site_id', $siteId);
        $subDomainData = $subDomainData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'domains')->get()->toArray();
        $dbSiteIds = array_column($subDomainData, 'site_id');
        $domainPageview = $this->getTotalPageviewQuery($startDate, $endDate, array_unique($dbSiteIds));

        foreach ($subDomainData as $key => $domain) {
            if (array_key_exists($domain['site_id'], $domainPageview)) {
                $pageviewArr = $domainPageview[$domain['site_id']];
                $subDomainData[$key]['total_pageview'] = (float) $pageviewArr['total_pageview'];
            } else {
                $subDomainData[$key]['total_pageview'] = 0;
            }
        }

        $resData = [];
        foreach ($subDomainData as $key => $value) {
            $domData = json_decode($value['domains'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($resData[$k])) {
                    $resData[$k]['domain_name'] = $v['domain_name'];
                    $resData[$k]['sub_pageviews'] = (float) $v['pageviews'];
                    $resData[$k]['total_pageview'] = (float) $value['total_pageview'];
                } else {
                    $resData[$k]['sub_pageviews'] += (float) $v['pageviews'];
                    $resData[$k]['total_pageview'] += (float) $value['total_pageview'];;
                }
            }
        }
        return $resData;
    }

    public function getOldDbDomainsData($startDate, $endDate, $siteId)
    {
        $oldSubDomainData = OldSubscriptionsReports::query();
        if($siteId) $oldSubDomainData = $oldSubDomainData->where('site_id', $siteId);
        $oldSubDomainData = $oldSubDomainData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'domains')->get()->toArray();
        $dbSiteIds = array_column($oldSubDomainData, 'site_id');
        $domainPageview = $this->getOldTotalPageviewQuery($startDate, $endDate, array_unique($dbSiteIds));

        foreach ($oldSubDomainData as $key => $domain) {
            if (array_key_exists($domain['site_id'], $domainPageview)) {
                $pageviewArr = $domainPageview[$domain['site_id']];
                $oldSubDomainData[$key]['total_pageview'] = (float) $pageviewArr['total_pageview'];
            } else {
                $oldSubDomainData[$key]['total_pageview'] = 0;
            }
        }

        $oldResData = [];
        foreach ($oldSubDomainData as $key => $value) {
            $domData = json_decode($value['domains'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($oldResData[$k])) {
                    $oldResData[$k]['domain_name'] = $v['domain_name'];
                    $oldResData[$k]['sub_pageviews'] = $v['pageviews'];
                    $oldResData[$k]['total_pageview'] = $value['total_pageview'];
                } else {
                    $oldResData[$k]['sub_pageviews'] += $v['pageviews'];
                    $oldResData[$k]['total_pageview'] += $value['total_pageview'];
                }
            }
        }
        return $oldResData;
    }

    public function getDomainTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $newDbDomain = $this->getNewDbDomainsData($startDate, $endDate, $siteId);
        $oldDbDomain = $this->getOldDbDomainsData($startDate, $endDate, $siteId);
        $bothArr = array_merge($newDbDomain, $oldDbDomain);
        $currResData = array();
        foreach ($bothArr as $val) {
            if (!isset($currResData[$val['domain_name']]))
                $currResData[$val['domain_name']] = $val;
            else {
                $currResData[$val['domain_name']]['sub_pageviews'] += $val['sub_pageviews'];
                $currResData[$val['domain_name']]['total_pageview'] += $val['total_pageview'];
            }
        }
        /* Percentage Subscription Page Views */
        foreach($currResData as $key => $value){
            $currResData[$key]['subscription_pvs_per'] = $value['total_pageview'] ? ($value['sub_pageviews'] * 100) / $value['total_pageview'] : 0 ;
        }

        $previousNewDbDomain = $this->getNewDbDomainsData($oldStartDate, $oldEndDate, $siteId);
        $previousOldDbDomain = $this->getOldDbDomainsData($oldStartDate, $oldEndDate, $siteId);
        $previousBothArr = array_merge($previousNewDbDomain, $previousOldDbDomain);
        $previousResData = array();
        foreach ($previousBothArr as $val) {
            if (!isset($previousResData[$val['domain_name']]))
                $previousResData[$val['domain_name']] = $val;
            else {
                $previousResData[$val['domain_name']]['sub_pageviews'] += (float) $val['sub_pageviews'];
                $previousResData[$val['domain_name']]['total_pageview'] += (float) $val['total_pageview'];
            }
        }
        /* previous Percentage Subscription Page Views */
        foreach($previousResData as $key => $value){
            $previousResData[$key]['subscription_pvs_per'] = $value['total_pageview'] ? ($value['sub_pageviews'] * 100) / $value['total_pageview'] : 0 ;
        }

        foreach ($currResData as $key => $sites) {
            if (array_key_exists($sites['domain_name'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['domain_name']];
                $oldSubPV = (float) $sitesPercentage['sub_pageviews'];
                $oldTotalPV = (float) $sitesPercentage['total_pageview'];
                $oldPerSubPV = (float) $sitesPercentage['subscription_pvs_per'];
                $currResData[$key]['sub_pageview_percentage'] = $oldSubPV ? ($sites['sub_pageviews'] - $oldSubPV) * 100 / $oldSubPV : 0;
                $currResData[$key]['total_pageview_percentage'] = $oldTotalPV ? ($sites['total_pageview'] - $oldTotalPV) * 100 / $oldTotalPV : 0;
                $currResData[$key]['subscription_pvs_percentage'] = $oldPerSubPV ? ($sites['subscription_pvs_per'] - $oldPerSubPV) * 100 / $oldPerSubPV : 0;
            } else {
                $currResData[$key]['sub_pageview_percentage'] = 0;
                $currResData[$key]['total_pageview_percentage'] = 0;
                $currResData[$key]['subscription_pvs_percentage'] = 0;
            }
        }
        return array_values($currResData);
    }

    public function getNewDbCountriesData($startDate, $endDate, $siteId)
    {
        $subCountriesData = SubscriptionsReports::query();
        if($siteId) $subCountriesData = $subCountriesData->where('site_id', $siteId);
        $subCountriesData = $subCountriesData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'countries')->get()->toArray();
        $dbSiteIds = array_column($subCountriesData, 'site_id');
        $countryPageview = $this->getTotalPageviewQuery($startDate, $endDate, array_unique($dbSiteIds));

        foreach ($subCountriesData as $key => $country) {
            if (array_key_exists($country['site_id'], $countryPageview)) {
                $pageviewArr = $countryPageview[$country['site_id']];
                $subCountriesData[$key]['total_pageview'] = (float) $pageviewArr['total_pageview'];
            } else {
                $subCountriesData[$key]['total_pageview'] = 0;
            }
        }

        $resData = [];
        foreach ($subCountriesData as $key => $value) {
            $domData = json_decode($value['countries'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($resData[$k])) {
                    $resData[$k]['country'] = $v['country'];
                    $resData[$k]['sub_pageviews'] = $v['pageviews'];
                    $resData[$k]['total_pageview'] = $value['total_pageview'];
                } else {
                    $resData[$k]['sub_pageviews'] += $v['pageviews'];
                    $resData[$k]['total_pageview'] += $value['total_pageview'];
                }
            }
        }
        return $resData;
    }

    public function getOldDbCountriesData($startDate, $endDate, $siteId)
    {
        $oldSubCountriesData = OldSubscriptionsReports::query();
        if($siteId) $oldSubCountriesData = $oldSubCountriesData->where('site_id', $siteId);
        $oldSubCountriesData = $oldSubCountriesData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'countries')->get()->toArray();
        $dbSiteIds = array_column($oldSubCountriesData, 'site_id');
        $countryPageview = $this->getOldTotalPageviewQuery($startDate, $endDate, array_unique($dbSiteIds));

        foreach ($oldSubCountriesData as $key => $country) {
            if (array_key_exists($country['site_id'], $countryPageview)) {
                $pageviewArr = $countryPageview[$country['site_id']];
                $oldSubCountriesData[$key]['total_pageview'] = (float) $pageviewArr['total_pageview'];
            } else {
                $oldSubCountriesData[$key]['total_pageview'] = 0;
            }
        }

        $oldResData = [];
        foreach ($oldSubCountriesData as $key => $value) {
            $domData = json_decode($value['countries'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($oldResData[$k])) {
                    $oldResData[$k]['country'] = $v['country'];
                    $oldResData[$k]['sub_pageviews'] = $v['pageviews'];
                    $oldResData[$k]['total_pageview'] = $value['total_pageview'];
                } else {
                    $oldResData[$k]['sub_pageviews'] += $v['pageviews'];
                    $oldResData[$k]['total_pageview'] += $value['total_pageview'];
                }
            }
        }
        return $oldResData;
    }

    public function getCountriesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $newDbCountries = $this->getNewDbCountriesData($startDate, $endDate, $siteId);
        $oldDbCountries = $this->getOldDbCountriesData($startDate, $endDate, $siteId);
        $bothArr = array_merge($newDbCountries, $oldDbCountries);
        $resData = array();
        foreach ($bothArr as $val) {
            if (!isset($resData[$val['country']]))
                $resData[$val['country']] = $val;
            else {
                $resData[$val['country']]['sub_pageviews'] += $val['sub_pageviews'];
                $resData[$val['country']]['total_pageview'] += $val['total_pageview'];
            }
        }
        /* Percentage Subscription Page Views */
        foreach($resData as $key => $value){
            $resData[$key]['subscription_pvs_per'] = $value['total_pageview'] ? ($value['sub_pageviews'] * 100) / $value['total_pageview'] : 0 ;
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
        /* previous Percentage Subscription Page Views */
        foreach($previousResData as $key => $value){
            $previousResData[$key]['subscription_pvs_per'] = $value['total_pageview'] ? ($value['sub_pageviews'] * 100) / $value['total_pageview'] : 0 ;
        }

        foreach ($resData as $key => $sites) {
            if (array_key_exists($sites['country'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['country']];
                $oldSubPV = (float) $sitesPercentage['sub_pageviews'];
                $oldTotalPV = (float) $sitesPercentage['total_pageview'];
                $oldPerSubPV = (float) $sitesPercentage['subscription_pvs_per'];
                $resData[$key]['sub_pageview_percentage'] = $oldSubPV ? ($sites['sub_pageviews'] - $oldSubPV) * 100 / $oldSubPV : '0';
                $resData[$key]['total_pageview_percentage'] = $oldTotalPV ? ($sites['total_pageview'] - $oldTotalPV) * 100 / $oldTotalPV : '0';
                $resData[$key]['subscription_pvs_percentage'] = $oldPerSubPV ? ($sites['subscription_pvs_per'] - $oldPerSubPV) * 100 / $oldPerSubPV : '0';
            } else {
                $resData[$key]['sub_pageview_percentage'] = 0;
                $resData[$key]['total_pageview_percentage'] = 0;
                $resData[$key]['subscription_pvs_percentage'] = 0;
            }
        }
        return array_values($resData);
    }

    public function getNewDbDevicesData($startDate, $endDate, $siteId)
    {
        $subDevicesData = SubscriptionsReports::query();
        if($siteId) $subDevicesData = $subDevicesData->where('site_id', $siteId);
        $subDevicesData = $subDevicesData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'devices')->get()->toArray();
        $dbSiteIds = array_column($subDevicesData, 'site_id');
        $devicePageview = $this->getTotalPageviewQuery($startDate, $endDate, array_unique($dbSiteIds));

        foreach ($subDevicesData as $key => $device) {
            if (array_key_exists($device['site_id'], $devicePageview)) {
                $pageviewArr = $devicePageview[$device['site_id']];
                $subDevicesData[$key]['total_pageview'] = (float) $pageviewArr['total_pageview'];
            } else {
                $subDevicesData[$key]['total_pageview'] = 0;
            }
        }

        $resData = [];
        foreach ($subDevicesData as $key => $value) {
            $domData = json_decode($value['devices'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($resData[$k])) {
                    $resData[$k]['device'] = $v['device'];
                    $resData[$k]['sub_pageviews'] = $v['pageviews'];
                    $resData[$k]['total_pageview'] = $value['total_pageview'];
                } else {
                    $resData[$k]['sub_pageviews'] += $v['pageviews'];
                    $resData[$k]['total_pageview'] += $value['total_pageview'];
                }
            }
        }
        return $resData;
    }

    public function getOldDbDevicesData($startDate, $endDate, $siteId)
    {
        $oldSubDevicesData = OldSubscriptionsReports::query();
        if($siteId) $oldSubDevicesData = $oldSubDevicesData->where('site_id', $siteId);
        $oldSubDevicesData = $oldSubDevicesData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'devices')->get()->toArray();
        $dbSiteIds = array_column($oldSubDevicesData, 'site_id');
        $devicePageview = $this->getOldTotalPageviewQuery($startDate, $endDate, array_unique($dbSiteIds));

        foreach ($oldSubDevicesData as $key => $device) {
            if (array_key_exists($device['site_id'], $devicePageview)) {
                $pageviewArr = $devicePageview[$device['site_id']];
                $oldSubDevicesData[$key]['total_pageview'] = (float) $pageviewArr['total_pageview'];
            } else {
                $oldSubDevicesData[$key]['total_pageview'] = 0;
            }
        }

        $oldResData = [];
        foreach ($oldSubDevicesData as $key => $value) {
            $domData = json_decode($value['devices'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($oldResData[$k])) {
                    $oldResData[$k]['device'] = $v['device'];
                    $oldResData[$k]['sub_pageviews'] = $v['pageviews'];
                    $oldResData[$k]['total_pageview'] = $value['total_pageview'];
                } else {
                    $oldResData[$k]['sub_pageviews'] += $v['pageviews'];
                    $oldResData[$k]['total_pageview'] += $value['total_pageview'];
                }
            }
        }
        return $oldResData;
    }

    public function getDevicesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $newDbDevices = $this->getNewDbDevicesData($startDate, $endDate, $siteId);
        $oldDbDevices = $this->getOldDbDevicesData($startDate, $endDate, $siteId);
        $bothArr = array_merge($newDbDevices, $oldDbDevices);
        $resData = array();
        foreach ($bothArr as $val) {
            if (!isset($resData[$val['device']]))
                $resData[$val['device']] = $val;
            else {
                $resData[$val['device']]['sub_pageviews'] += $val['sub_pageviews'];
                $resData[$val['device']]['total_pageview'] += $val['total_pageview'];
            }
        }
        /* Percentage Subscription Page Views */
        foreach($resData as $key => $value){
            $resData[$key]['subscription_pvs_per'] = $value['total_pageview'] ? ($value['sub_pageviews'] * 100) / $value['total_pageview'] : 0 ;
        }

        $previousNewDbDevices = $this->getNewDbDevicesData($oldStartDate, $oldEndDate, $siteId);
        $previousOldDbDevices = $this->getOldDbDevicesData($oldStartDate, $oldEndDate, $siteId);
        $previousBothArr = array_merge($previousNewDbDevices, $previousOldDbDevices);
        $previousResData = array();
        foreach ($previousBothArr as $val) {
            if (!isset($previousResData[$val['device']]))
                $previousResData[$val['device']] = $val;
            else {
                $previousResData[$val['device']]['sub_pageviews'] += $val['sub_pageviews'];
                $previousResData[$val['device']]['total_pageview'] += $val['total_pageview'];
            }
        }
        /* previous Percentage Subscription Page Views */
        foreach($previousResData as $key => $value){
            $previousResData[$key]['subscription_pvs_per'] = $value['total_pageview'] ? ($value['sub_pageviews'] * 100) / $value['total_pageview'] : 0 ;
        }

        foreach ($resData as $key => $sites) {
            if (array_key_exists($sites['device'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['device']];
                $oldSubPV = (float) $sitesPercentage['sub_pageviews'];
                $oldTotalPV = (float) $sitesPercentage['total_pageview'];
                $oldPerSubPV = (float) $sitesPercentage['subscription_pvs_per'];
                $resData[$key]['sub_pageview_percentage'] = $oldSubPV ? ($sites['sub_pageviews'] - $oldSubPV) * 100 / $oldSubPV : 0;
                $resData[$key]['total_pageview_percentage'] = $oldTotalPV ? ($sites['total_pageview'] - $oldTotalPV) * 100 / $oldTotalPV : 0;
                $resData[$key]['subscription_pvs_percentage'] = $oldPerSubPV ? ($sites['subscription_pvs_per'] - $oldPerSubPV) * 100 / $oldPerSubPV : 0;
            } else {
                $resData[$key]['sub_pageview_percentage'] = 0;
                $resData[$key]['total_pageview_percentage'] = 0;
                $resData[$key]['subscription_pvs_percentage'] = 0;
            }
        }
        return array_values($resData);
    }

    public function getNewDbPagesData($startDate, $endDate, $siteId)
    {
        $subPagesData = SubscriptionsReports::query();
        if($siteId) $subPagesData = $subPagesData->where('site_id', $siteId);
        $subPagesData = $subPagesData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'popular_pags')->get()->toArray();
        $dbSiteIds = array_column($subPagesData, 'site_id');
        $pagePageview = $this->getTotalPageviewQuery($startDate, $endDate, array_unique($dbSiteIds));

        foreach ($subPagesData as $key => $page) {
            if (array_key_exists($page['site_id'], $pagePageview)) {
                $pageviewArr = $pagePageview[$page['site_id']];
                $subPagesData[$key]['total_pageview'] = (float) $pageviewArr['total_pageview'];
            } else {
                $subPagesData[$key]['total_pageview'] = 0;
            }
        }

        $resData = [];
        foreach ($subPagesData as $key => $value) {
            $domData = json_decode($value['popular_pags'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($resData[$k])) {
                    $resData[$k]['page'] = $v['page'];
                    $resData[$k]['sub_pageviews'] = $v['pageviews'];
                    $resData[$k]['total_pageview'] = $value['total_pageview'];
                } else {
                    $resData[$k]['sub_pageviews'] += $v['pageviews'];
                    $resData[$k]['total_pageview'] += $value['total_pageview'];
                }
            }
        }
        return $resData;
    }

    public function getOldDbPagesData($startDate, $endDate, $siteId)
    {
        $oldSubPagesData = OldSubscriptionsReports::query();
        if($siteId) $oldSubPagesData = $oldSubPagesData->where('site_id', $siteId);
        $oldSubPagesData = $oldSubPagesData->whereBetween('dt_subscription_reports.date', [$startDate, $endDate])
            ->select('id', 'site_id', 'popular_pags')->get()->toArray();
        $dbSiteIds = array_column($oldSubPagesData, 'site_id');
        $pagePageview = $this->getOldTotalPageviewQuery($startDate, $endDate, array_unique($dbSiteIds));

        foreach ($oldSubPagesData as $key => $page) {
            if (array_key_exists($page['site_id'], $pagePageview)) {
                $pageviewArr = $pagePageview[$page['site_id']];
                $oldSubPagesData[$key]['total_pageview'] = (float) $pageviewArr['total_pageview'];
            } else {
                $oldSubPagesData[$key]['total_pageview'] = 0;
            }
        }

        $oldResData = [];
        foreach ($oldSubPagesData as $key => $value) {
            $domData = json_decode($value['popular_pags'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($oldResData[$k])) {
                    $oldResData[$k]['page'] = $v['page'];
                    $oldResData[$k]['sub_pageviews'] = $v['pageviews'];
                    $oldResData[$k]['total_pageview'] = $value['total_pageview'];
                } else {
                    $oldResData[$k]['sub_pageviews'] += $v['pageviews'];
                    $oldResData[$k]['total_pageview'] += $value['total_pageview'];
                }
            }
        }
        return $oldResData;
    }

    public function getPagesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $newDbPages = $this->getNewDbPagesData($startDate, $endDate, $siteId);
        $oldDbPages = $this->getOldDbPagesData($startDate, $endDate, $siteId);
        $bothArr = array_merge($newDbPages, $oldDbPages);
        $resData = array();
        foreach ($bothArr as $val) {
            if (!isset($resData[$val['page']]))
                $resData[$val['page']] = $val;
            else {
                $resData[$val['page']]['sub_pageviews'] += $val['sub_pageviews'];
                $resData[$val['page']]['total_pageview'] += $val['total_pageview'];
            }
        }
        /* Percentage Subscription Page Views */
        foreach($resData as $key => $value){
            $resData[$key]['subscription_pvs_per'] = $value['total_pageview'] ? ($value['sub_pageviews'] * 100) / $value['total_pageview'] : 0 ;
        }

        $previousNewDbPages = $this->getNewDbPagesData($oldStartDate, $oldEndDate, $siteId);
        $previousOldDbPages = $this->getOldDbPagesData($oldStartDate, $oldEndDate, $siteId);
        $previousBothArr = array_merge($previousNewDbPages, $previousOldDbPages);
        $previousResData = array();
        foreach ($previousBothArr as $val) {
            if (!isset($previousResData[$val['page']]))
                $previousResData[$val['page']] = $val;
            else {
                $previousResData[$val['page']]['sub_pageviews'] += $val['sub_pageviews'];
                $previousResData[$val['page']]['total_pageview'] += $val['total_pageview'];
            }
        }
        /* previous Percentage Subscription Page Views */
        foreach($previousResData as $key => $value){
            $previousResData[$key]['subscription_pvs_per'] = $value['total_pageview'] ? ($value['sub_pageviews'] * 100) / $value['total_pageview'] : 0 ;
        }

        foreach ($resData as $key => $sites) {
            if (array_key_exists($sites['page'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['page']];
                $oldSubPV = (float) $sitesPercentage['sub_pageviews'];
                $oldTotalPV = (float) $sitesPercentage['total_pageview'];
                $oldPerSubPV = (float) $sitesPercentage['subscription_pvs_per'];
                $resData[$key]['sub_pageview_percentage'] = $oldSubPV ? ($sites['sub_pageviews'] - $oldSubPV) * 100 / $oldSubPV : 0;
                $resData[$key]['total_pageview_percentage'] = $oldTotalPV ? ($sites['total_pageview'] - $oldTotalPV) * 100 / $oldTotalPV : 0;
                $resData[$key]['subscription_pvs_percentage'] = $oldPerSubPV ? ($sites['subscription_pvs_per'] - $oldPerSubPV) * 100 / $oldPerSubPV : 0;
            } else {
                $resData[$key]['sub_pageview_percentage'] = 0;
                $resData[$key]['total_pageview_percentage'] = 0;
                $resData[$key]['subscription_pvs_percentage'] = 0;
            }
        }
        return array_values($resData);
    }

    public function getSubscribersTableReportQuery($startDate, $endDate, $siteId = null)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        
        $oldDbSub = OldSubUsers::query();
        if($siteId) $oldDbSub = $oldDbSub->where('im_subs_users.site_id', $siteId);
        $oldDbSub = $oldDbSub
            ->where([
                ['im_subs_users.site_id', '<=', 1000],
                ['im_subs_users.status', '=', 1],
                ['im_subs_users.subs_mode', '=', 'live'],
                ['im_subs_users.stripe_customerid', "like", "cus_" . "%"],
                ['im_subs_users.date_signedup', "<=", $endTime],
            ])
            // ->whereBetween('im_subs_users.date_signedup', [$startTime, $endTime])
            ->join('im_sites', function ($join) {
                $join->on('im_sites.id', '=', 'im_subs_users.site_id');
            })
            ->select(
                // 'im_sites.id as site_id',
                'im_sites.site_name',
                'im_subs_users.*',
            )
            ->get()->toArray();

        $newDbSub = SubUsers::query();
        if($siteId) $newDbSub = $newDbSub->where('im_subs_users.site_id', $siteId);
        $newDbSub = $newDbSub
            ->where([
                ['im_subs_users.status', '=', 1],
                ['im_subs_users.subs_mode', '=', 'live'],
                ['im_subs_users.stripe_customerid', "like", "cus_" . "%"],
                ['date_signedup', "<=", $endTime],
            ])
            // ->whereBetween('im_subs_users.date_signedup', [$startTime, $endTime])
            ->join('dt_sites', function ($join) {
                $join->on('dt_sites.site_id', '=', 'im_subs_users.site_id');
            })
            ->select(
                // 'dt_sites.site_id as site_id',
                'dt_sites.site_name',
                'im_subs_users.*',
            )
            ->get()->toArray();

        $bothArr = array_merge($oldDbSub, $newDbSub);
        foreach ($bothArr as $key => $sites) {
            $bothArr[$key]['date'] = date('Y-m-d, h:i A', $sites['date_signedup']);
        }
        return $bothArr;
    }

    public function getSubscriberLogTableQuery($siteId, $id)
    {
        if ($siteId <= 1000) {
            $logReport = OldSubUsersLogs::where('user_id', $id);
        } else {
            $logReport = SubUsersLogs::where('user_id', $id);
        }

        $logReport = $logReport->get()->toArray();
        foreach($logReport as $key => $log) {
            $dd = new DeviceDetector($log['user_agent']);
            $dd->parse();
            if ($dd->isBot()) {
                $botInfo = $dd->getBot();
                $logReport[$key]['device'] = '';
                $logReport[$key]['browser-os'] = '';
            } else {
                $device = $dd->getDeviceName();
                $osInfo = $dd->getOs();
                $clientInfo = $dd->getClient();
                $logReport[$key]['device'] = ucfirst($device);
                $logReport[$key]['browser-os'] = $osInfo['name'] . ' ' . $osInfo['version'] . ' / ' . $clientInfo['name'] . ' ' . $clientInfo['version'];
            }
            $reader = Reader::connect();
            $record = $reader->city($log['ip']);
            $logReport[$key]['location'] = $record->city->name . ", " . $record->mostSpecificSubdivision->isoCode;
            $logReport[$key]['date'] = date("Y-m-d, h:i A", $log['date_logged_in']);
        }
        return $logReport;
    }

    public function exportTableViewDataQuery($startDate, $endDate, $tableType, $oldStartDate, $oldEndDate, $siteId = null)
    {
        if ($tableType == 'domains') {
            $subTableData = $this->getDomainTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        } else if ($tableType == 'countries') {
            $subTableData = $this->getCountriesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        } else if ($tableType == 'devices') {
            $subTableData = $this->getDevicesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        } else if ($tableType == 'pages') {
            $subTableData = $this->getPagesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        } else if ($tableType == 'subscribers') {
            $subTableData = $this->getSubscribersTableReportQuery($startDate, $endDate, $siteId);
        } else {
            return response()->json(['message' => 'Table type data not found', 'status' => false], 403);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($tableType == 'subscribers') {
            $sheet->setCellValue('A1', 'Site Name');
            $sheet->setCellValue('B1', 'Subscriber Name');
            $sheet->setCellValue('C1', 'Subscriber Email');
            $sheet->setCellValue('D1', 'Plan Type');
            $sheet->setCellValue('E1', 'Date');
        } else {
            if ($tableType == 'domains') $sheet->setCellValue('A1', 'Domains Name');
            if ($tableType == 'countries') $sheet->setCellValue('A1', 'Countrie Name');
            if ($tableType == 'devices') $sheet->setCellValue('A1', 'Device Name');
            if ($tableType == 'pages') $sheet->setCellValue('A1', 'Page Name');
            $sheet->setCellValue('B1', 'Pages Views');
            $sheet->setCellValue('C1', 'Pages Views Percentage');
        }

        $rows = 2;
        foreach ($subTableData as $data) {
            if ($tableType == 'subscribers') {
                $sheet->setCellValue('A' . $rows, $data['site_name']);
                $sheet->setCellValue('B' . $rows, $data['name']);
                $sheet->setCellValue('C' . $rows, $data['email']);
                $sheet->setCellValue('D' . $rows, $data['plan_type']);
                $sheet->setCellValue('E' . $rows, $data['date']);
            } else {
                if ($tableType == 'domains') $sheet->setCellValue('A' . $rows, $data['domain_name']);
                if ($tableType == 'countries') $sheet->setCellValue('A' . $rows, $data['country']);
                if ($tableType == 'devices') $sheet->setCellValue('A' . $rows, $data['device']);
                if ($tableType == 'pages') $sheet->setCellValue('A' . $rows, $data['page']);
                $sheet->setCellValue('B' . $rows, $data['pageviews']);
                $sheet->setCellValue('C' . $rows, $data['pageview_percentage']);
            }
            $rows++;
        }

        $fileName = "table-data.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/" . $fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/' . $fileName;
        return response()->download($path)->deleteFileAfterSend();;
    }

    public function getSubscribersSearchReportQuery()
    {
        $oldDbSub = OldSubUsers::query();
        $oldDbSub = $oldDbSub
            ->where([
                ['im_subs_users.site_id', '<=', 1000],
                ['im_subs_users.stripe_customerid', "like", "cus_" . "%"],
            ])
            ->select('im_subs_users.id', 'im_subs_users.name', 'im_subs_users.email')
            ->get()->toArray();

        $newDbSub = SubUsers::query();
        $newDbSub = $newDbSub
            ->where([
                ['im_subs_users.stripe_customerid', "like", "cus_" . "%"],
            ])
            ->select('im_subs_users.id', 'im_subs_users.name', 'im_subs_users.email')
            ->get()->toArray();

        $subSubscribersData = array_merge($newDbSub, $oldDbSub);
        return $subSubscribersData;
    }
}
