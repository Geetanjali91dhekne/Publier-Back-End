<?php

namespace App\Http\Traits;

use App\Models\HourlyReport;
use Illuminate\Support\Facades\DB;
use Exception;
use MongoDB\Client;
use MongoDB\Driver\ServerApi;
use MongoDB\Driver\Manager;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Query;

trait RealTimeSiteDetailsTrait
{
    public function getRealtimePageviewImpressionGraphQuery($hoursAgo, $now, $site_id, $time_interval)
    {
        date_default_timezone_set('UTC');
        try {
            $m = new Client("mongodb+srv://publirMegaTron:ehT54VBP6O4r3oyb@cluster0-w19id.mongodb.net/test?retryWrites=true&w=majority");
	        $manager = new Manager("mongodb+srv://publirMegaTron:ehT54VBP6O4r3oyb@cluster0-w19id.mongodb.net/test?retryWrites=true&w=majority");
        } catch (Exception $error) {
            echo $error->getMessage();die(1);
        }

        $utc_minutes = date('i', strtotime(gmdate("Y-m-d\TH:i:s\Z"))); // add minutes to start
        $time_period = ($time_interval * 60) + $utc_minutes;  // 60 minutes means 1 hour
        $diff = $time_period * 60 * 1000;
        $mongotime = new UTCDateTime((microtime(true) * 1000) - $diff);

        $pageviews_filter = [
            'timeStamp' => ['$gte' => $mongotime],
            'siteId' => $site_id
        ];

        $pageviews_options = [
            'sort' => ['timeStamp' => -1],
        ];

        $pageviews_rangeQuery = new Query($pageviews_filter, $pageviews_options);
        $pageviews_cursor = $manager->executeQuery('Pageviews.logs', $pageviews_rangeQuery);

        $total_pageview = 0;
        $newPageviewArr = array();
        foreach($pageviews_cursor as $key => $document) {
            $total_pageview++;

            $document = (array) $document;
            $num = (array) $document['timeStamp'];
            $timeStamp = round($num['milliseconds'] / 1000);
            $min_date = date("Y-m-d H:i:s", $timeStamp);

            $newDatetime = strtotime($min_date);
            date_default_timezone_set('EST');
            $newDatetime = date('Y-m-d H:i:s', $newDatetime);
            date_default_timezone_set('UTC');

            $hour = date("g A", strtotime($newDatetime));
            if (!isset($newPageviewArr[$hour])) {
                $newPageviewArr[$hour]['hour'] = $hour;
                $newPageviewArr[$hour]['pageview'] = 1;
            } else {
                $newPageviewArr[$hour]['pageview']++;
            }
        }
        return $newPageviewArr;
    }

    public function getRealtimeRevenueRequestGraphQuery($hoursAgo, $now, $clientPer, $site_id)
    {
        $adData = HourlyReport::query();
        $adData = $adData
            ->whereBetween('created_at', [$hoursAgo, $now])
            ->where('site_id', $site_id)
            ->select(
                'date', 'hour',
                DB::raw('time_format(hour, "%l %p") as new_hour'),
                DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue'),
                DB::raw('SUM(total_request) as ad_request')
            )
            ->groupBy('hour')
            ->get();

        $sum_revenue = $adData->sum('total_revenue');
        $sum_request = $adData->sum('ad_request');
        $resData['hour'] = $adData->pluck('new_hour')->toArray();
        $resData['revenue'] = $adData->pluck('total_revenue')->toArray();
        $resData['request'] = $adData->pluck('ad_request')->toArray();
        $resData['total_revenue'] = $sum_revenue;
        $resData['total_request'] = $sum_request;
        return $resData;
    }

    public function getRealtimeCpmGraphQuery($hoursAgo, $now, $clientPer, $site_id)
    {
        $adCpmsData = HourlyReport::query();
        $adCpmsData = $adCpmsData
            ->whereBetween('created_at', [$hoursAgo, $now])
            ->where('site_id', $site_id)
            ->select(
                'date', 'hour',
                DB::raw('time_format(hour, "%l %p") as new_hour'),
                DB::raw('1000 * (SUM(revenue) * ' . $clientPer . ')/SUM(impressions) as cpms'),
                DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue'),
                DB::raw('SUM(impressions) as total_impressions'),
            )
            ->groupBy('hour')
            ->get();

        $total_revenue = $adCpmsData->sum('total_revenue');
        $total_impressions = $adCpmsData->sum('total_impressions');
        $totalCpm = $total_impressions ? 1000 * ($total_revenue / $total_impressions) : '0';

        $resData['hour'] = $adCpmsData->pluck('new_hour')->toArray();
        $resData['cpms'] = $adCpmsData->pluck('cpms')->toArray();
        $resData['total_cpms'] = $totalCpm;
        return $resData;
    }

    public function getRealtimeRpmGraphQuery($hoursAgo, $now, $clientPer, $site_id, $time_interval)
    {
        $adRpmData = HourlyReport::query();
        $adRpmData = $adRpmData
            ->whereBetween('created_at', [$hoursAgo, $now])
            ->where('im_gam_hourly_reports.site_id', $site_id)
            ->select(
                'im_gam_hourly_reports.date', 'im_gam_hourly_reports.hour',
                DB::raw('time_format(im_gam_hourly_reports.hour, "%l %p") as new_hour'),
                DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue'),
            )
            ->groupBy('im_gam_hourly_reports.hour')
            ->get();
        
        $total_revenue = $adRpmData->sum('total_revenue');        
        $pageviewArr = $this->getRealtimePageviewImpressionGraphQuery($hoursAgo, $now, $site_id, $time_interval);

        foreach($adRpmData as $key => $val) {
            if (array_key_exists($val['new_hour'], $pageviewArr)) {
                $hourPv = $pageviewArr[$val['new_hour']];
                $adRpmData[$key]['rpm'] = $hourPv['pageview'] ? (1000 * $val['total_revenue']) / $hourPv['pageview'] : 0;
            } else {
                $adRpmData[$key]['rpm'] = 0;
            }
        }
        $total_pageview = array_sum(array_column($pageviewArr, 'pageview'));
        $sum_rpm = $total_pageview ? (1000 * $total_revenue) / $total_pageview : 0;

        $resData['hour'] = $adRpmData->pluck('new_hour')->toArray();
        $resData['rpm'] = $adRpmData->pluck('rpm')->toArray();
        $resData['total_rpm'] = $sum_rpm;
        return $resData;
    }

    public function getSizeTableReportQuery($now, $hoursAgo, $site_id, $clientPer)
    {
        $sizeData = HourlyReport::where('site_id', $site_id);
        $sizeData = $sizeData->whereBetween('created_at', [$hoursAgo, $now])
            ->join('dt_sizes', 'im_gam_hourly_reports.size_id', '=', 'dt_sizes.id')
            ->select(
                DB::raw('time_format(hour, "%l %p") as new_hour'),
                'dt_sizes.dimensions',
                'site_id',
                'hour',
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('(1000 * SUM(revenue) * ' . $clientPer . ' )/SUM(impressions) as cpms')
            )
            ->groupBy('hour')
            ->get();

        return $sizeData;
    }

    public function getNetworksTableReportQuery($now, $hoursAgo, $site_id, $clientPer)
    {
        $networkData = HourlyReport::where('site_id', $site_id);
        $networkData = $networkData->whereBetween('created_at', [$hoursAgo, $now])
            ->join('dt_networks', 'im_gam_hourly_reports.network_id', '=', 'dt_networks.id')
            ->select(
                DB::raw('time_format(hour, "%l %p") as new_hour'),
                'dt_networks.network_name',
                'network_id',
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('0 as latency'),
                DB::raw('(1000 * SUM(revenue) * ' . $clientPer . ' )/SUM(impressions) as cpms')
            )
            ->groupBy('network_id')
            ->get();

        return $networkData;
    }

    public function getPopularpagesTableReportQuery($now, $hoursAgo, $site_id, $clientPer, $time_interval)
    {
        $adPopularData = HourlyReport::where('im_gam_hourly_reports.site_id', $site_id);
        $adPopularData = $adPopularData->whereBetween('im_gam_hourly_reports.created_at', [$hoursAgo, $now])
            ->join('dt_sites', 'im_gam_hourly_reports.site_id', '=', 'dt_sites.site_id')
            ->select(
                'dt_sites.site_url as url',
                'im_gam_hourly_reports.date',
                DB::raw('time_format(im_gam_hourly_reports.hour, "%l %p") as new_hour'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(revenue) * ' . $clientPer . ' as total_revenue'),
                DB::raw('0 as ads_pv'),
            )
            ->groupBy('im_gam_hourly_reports.site_id')
            ->get()->toArray();

        $pageviewArr = $this->getRealtimePageviewImpressionGraphQuery($hoursAgo, $now, $site_id, $time_interval);
        $pageview = array_sum(array_column($pageviewArr, 'pageview'));
        foreach ($adPopularData as $key => $value) {
            $adPopularData[$key]['pvs'] = $pageview;
            $adPopularData[$key]['rpm'] = $pageview ? (1000 * $value['total_revenue']) / $pageview : 0;
        }
        return $adPopularData;
    }
}