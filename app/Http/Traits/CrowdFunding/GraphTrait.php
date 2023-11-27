<?php

namespace App\Http\Traits\CrowdFunding;

use App\Models\CrowdfundReports;
use App\Models\DailyReport;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;

use DeviceDetector\DeviceDetector;
use danielme85\Geoip2\Facade\Reader;

trait GraphTrait
{
    public function getEarningData($startTime, $endTime, $clientPer, $siteId)
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
                DB::raw('dt_payments_info.amount * ' . $clientPer . ' as sum_earnings'),
                'user_agent',
                'ip',
            )
            ->get()->toArray();
        return $fundTotalEar;
    }

    public function getEarningCountryGraphQuery($startDate, $endDate, $clientPer, $siteId)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $fundTotalEar = $this->getEarningData($startTime, $endTime, $clientPer, $siteId);
        foreach($fundTotalEar as $key => $log) {
            $reader = Reader::connect();
            $record = $reader->city($log['ip']);
            $fundTotalEar[$key]['city'] = $record->city->name;
            $fundTotalEar[$key]['country'] = $record->country->name;
        }

        $countryEarn = [];
        foreach ($fundTotalEar as $val) {
            if (!isset($countryEarn[$val['country']])) {
                $countryEarn[$val['country']] = $val;
            } else {
                $countryEarn[$val['country']]['sum_earnings'] += $val['sum_earnings'];

            }
        }
        return array_values($countryEarn);
    }

    public function getDonorsCountryGraphQuery($startDate, $endDate, $clientPer, $siteId)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $fundTotalEar = $this->getEarningData($startTime, $endTime, $clientPer, $siteId);
        foreach($fundTotalEar as $key => $log) {
            $reader = Reader::connect();
            $record = $reader->city($log['ip']);
            $fundTotalEar[$key]['country'] = $record->country->name;
        }

        $countryDonors = [];
        foreach ($fundTotalEar as $val) {
            if (!isset($countryDonors[$val['country']])) {
                $countryDonors[$val['country']] = $val;
                $countryDonors[$val['country']]['donors'] = 1;
            } else {
                $countryDonors[$val['country']]['sum_earnings'] += $val['sum_earnings'];
                $countryDonors[$val['country']]['donors'] += 1;

            }
        }
        return array_values($countryDonors);
    }

    public function getEarningDevicesGraphQuery($startDate, $endDate, $clientPer, $siteId)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $fundTotalEar = $this->getEarningData($startTime, $endTime, $clientPer, $siteId);
        foreach($fundTotalEar as $key => $log) {
            $dd = new DeviceDetector($log['user_agent']);
            $dd->parse();
            if ($dd->isBot()) {
                $botInfo = $dd->getBot();
                $fundTotalEar[$key]['device'] = '';
            } else {
                $device = $dd->getDeviceName();
                $fundTotalEar[$key]['device'] = ucfirst($device);
            }
        }

        $devicesEarn = [];
        foreach ($fundTotalEar as $val) {
            if (!isset($devicesEarn[$val['device']])) {
                $devicesEarn[$val['device']] = $val;
            } else {
                $devicesEarn[$val['device']]['sum_earnings'] += $val['sum_earnings'];
            }
        }
        return array_values($devicesEarn);
    }

    public function getDonorsDevicesGraphQuery($startDate, $endDate, $clientPer, $siteId)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $fundTotalEar = $this->getEarningData($startTime, $endTime, $clientPer, $siteId);
        foreach($fundTotalEar as $key => $log) {
            $dd = new DeviceDetector($log['user_agent']);
            $dd->parse();
            if ($dd->isBot()) {
                $botInfo = $dd->getBot();
                $fundTotalEar[$key]['device'] = '';
            } else {
                $device = $dd->getDeviceName();
                $fundTotalEar[$key]['device'] = ucfirst($device);
            }
        }

        $devicesEarn = [];
        foreach ($fundTotalEar as $val) {
            if (!isset($devicesEarn[$val['device']])) {
                $devicesEarn[$val['device']] = $val;
                $devicesEarn[$val['device']]['donors'] = 1;
            } else {
                $devicesEarn[$val['device']]['sum_earnings'] += $val['sum_earnings'];
                $devicesEarn[$val['device']]['donors'] += 1;

            }
        }
        return array_values($devicesEarn);
    }

    public function totalEarningsGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $crowEarnData = Sites::query();
        if ($siteId) $crowEarnData = $crowEarnData->where('dt_sites.site_id', $siteId);
        $crowEarnData = $crowEarnData
            ->join('dt_payments_info', function ($join) use ($startTime, $endTime) {
                $join->on('dt_payments_info.site_id', '=', 'dt_sites.site_id');
                $join->where('dt_payments_info.type', '=', 'live');
                $join->whereBetween('dt_payments_info.time_stamp', [$startTime, $endTime]);
            })
            ->select(
                'time_stamp',
                DB::raw('SUM(dt_payments_info.amount) * ' . $clientPer . ' as sum_earnings'),
            )
            ->groupBy('dt_payments_info.time_stamp')
            ->get()->toArray();

        foreach ($crowEarnData as $key => $earn) {
            $crowEarnData[$key]['date'] = date('Y-m-d', $earn['time_stamp']);
        }

        $crowEarn = [];
        foreach ($crowEarnData as $val) {
            if (!isset($crowEarn[$val['date']])) {
                $crowEarn[$val['date']] = $val;
            } else {
                $crowEarn[$val['date']]['sum_earnings'] += $val['sum_earnings'];
            }
        }
        return $crowEarn;
    }

    public function totalDonorsGraphData($startDate, $endDate, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $crowdonorsData = Sites::query();
        if ($siteId) $crowdonorsData = $crowdonorsData->where('dt_sites.site_id', $siteId);
        $crowdonorsData = $crowdonorsData
            ->join('dt_payments_info', function ($join) use ($startTime, $endTime) {
                $join->on('dt_payments_info.site_id', '=', 'dt_sites.site_id');
                $join->where('dt_payments_info.type', '=', 'live');
                $join->whereBetween('dt_payments_info.time_stamp', [$startTime, $endTime]);
            })
            ->select(
                'time_stamp',
                DB::raw('COUNT(dt_payments_info.amount) as total_donors')
            )
            ->groupBy('dt_payments_info.time_stamp')
            ->get()->toArray();

        foreach ($crowdonorsData as $key => $fund) {
            $crowdonorsData[$key]['date'] = date('Y-m-d', $fund['time_stamp']);
        }

        $crowDonors = [];
        foreach ($crowdonorsData as $val) {
            if (!isset($crowDonors[$val['date']])) {
                $crowDonors[$val['date']] = $val;
            } else {
                $crowDonors[$val['date']]['total_donors'] += $val['total_donors'];
            }
        }
        return $crowDonors;
    }

    public function fundraiserViewsGraphData($startDate, $endDate, $siteId = null)
    {
        $crowfundData = CrowdfundReports::query();
        if ($siteId) $crowfundData = $crowfundData->where('site_id', $siteId);
        $crowfundData = $crowfundData
            ->whereBetween('date', [$startDate, $endDate])
            ->select('date', 'domains')->get()->toArray();

        foreach ($crowfundData as $key => $value) {
            $fundViews = 0;
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
            $crowfundData[$key]['fundraiser_views'] = $fundViews;
        }

        $fundraiserviews = array();
        foreach ($crowfundData as $val) {
            if (!isset($fundraiserviews[$val['date']])) {
                $fundraiserviews[$val['date']] = $val;
            } else {
                $fundraiserviews[$val['date']]['fundraiser_views'] += $val['fundraiser_views'];
            }
        }

        return array_values($fundraiserviews);
    }
    public function averageDonationGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $startTime     = strtotime($startDate);
        $endTime     = strtotime($endDate);

        $crowEarnData = Sites::query();
        if ($siteId) $crowEarnData = $crowEarnData->where('dt_sites.site_id', $siteId);
        $crowEarnData = $crowEarnData
            ->join('dt_payments_info', function ($join) use ($startTime, $endTime) {
                $join->on('dt_payments_info.site_id', '=', 'dt_sites.site_id');
                $join->where('dt_payments_info.type', '=', 'live');
                $join->whereBetween('dt_payments_info.time_stamp', [$startTime, $endTime]);
            })
            ->select(
                'time_stamp',
                DB::raw('dt_payments_info.amount * ' . $clientPer . ' as total_earnings'),
            )
            ->get()->toArray();

        foreach ($crowEarnData as $key => $earn) {
            $crowEarnData[$key]['date'] = date('Y-m-d', $earn['time_stamp']);
        }
        $avgDonation = [];
        foreach ($crowEarnData as $val) {
            if (!isset($avgDonation[$val['date']])) {
                $avgDonation[$val['date']] = $val;
                $avgDonation[$val['date']]['total_donors'] = 1;
            } else {
                $avgDonation[$val['date']]['total_earnings'] += $val['total_earnings'];
                $avgDonation[$val['date']]['total_donors'] += 1;
            }
        }
        foreach ($avgDonation as $key => $val) {
            $avgDonation[$key]['average_donation'] = $val['total_donors'] ? ($val['total_earnings'] / $val['total_donors']) : 0;   
        }
        return $avgDonation;
    }

    public function fundEcpmGraphData($startDate, $endDate, $clientPer, $siteId = null)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $crowEarnData = Sites::query();
        if ($siteId) $crowEarnData = $crowEarnData->where('dt_sites.site_id', $siteId);
        $crowEarnData = $crowEarnData
            ->join('dt_payments_info', function ($join) use ($startTime, $endTime) {
                $join->on('dt_payments_info.site_id', '=', 'dt_sites.site_id');
                $join->where('dt_payments_info.type', '=', 'live');
                $join->whereBetween('dt_payments_info.time_stamp', [$startTime, $endTime]);
            })
            ->select('time_stamp', DB::raw('dt_payments_info.amount * ' . $clientPer . ' as total_earnings'))
            ->get()->toArray();

        foreach ($crowEarnData as $key => $earn) {
            $crowEarnData[$key]['date'] = date('Y-m-d', $earn['time_stamp']);
        }
        $earningData = [];
        foreach ($crowEarnData as $val) {
            if (!isset($earningData[$val['date']])) {
                $earningData[$val['date']] = $val;
            } else {
                $earningData[$val['date']]['total_earnings'] += $val['total_earnings'];
            }
        }

        $reportData = CrowdfundReports::query();
        if($siteId) $reportData = $reportData->where('site_id', $siteId);
        $reportData = $reportData->whereBetween('date', [$startDate, $endDate])
            ->select('date', 'views')->get()->toArray();

        $widgetViewData = [];
        foreach ($reportData as $key => $value) {
            $domData = json_decode($value['views'], 1);
            if (!isset($widgetViewData[$value['date']])) {
                $widgetViewData[$value['date']]['widgetviews'] = $domData['widgetviews'];;
            } else {
                $widgetViewData[$value['date']]['widgetviews'] += $domData['widgetviews'];;
            }
        }

        foreach ($earningData as $key => $val) {
            if (array_key_exists($val['date'], $widgetViewData)) {
                $widgetViewArr = $widgetViewData[$val['date']];
                $widgetView = (float) $widgetViewArr['widgetviews'];
                $earningData[$key]['fund_ecpm'] = $widgetView ? ($val['total_earnings'] / $widgetView) * 1000 : 0;
            } else {
                $earningData[$key]['fund_ecpm'] = 0;
            }
        }
        return $earningData;
    }
}
