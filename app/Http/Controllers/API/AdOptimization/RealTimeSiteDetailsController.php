<?php

namespace App\Http\Controllers\API\AdOptimization;

use App\Http\Controllers\Controller;
use App\Http\Traits\RealTimeSiteDetailsTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RealTimeSiteDetailsController extends Controller
{
    use RealTimeSiteDetailsTrait;

    /*
    ** pageview and impression graph
    **
    */
    public function ad_realtime_pageview_impression_graph_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'time_interval' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $time_interval = $request->input('time_interval');
        $now = Carbon::now();
        $hoursAgo = $now->copy()->subHours($time_interval);

        $newPageviewArr = $this->getRealtimePageviewImpressionGraphQuery($hoursAgo, $now, $site_id, $time_interval);
        // Sort the array
        foreach($newPageviewArr as $key => $value) {
            $hour = date("G", strtotime($value['hour']));
            $newPageviewArr[$key]['new_hour'] = $hour == 0 ? "24" : $hour ;
        }
        usort($newPageviewArr, function ($a, $b) {
            return $b['new_hour'] < $a['new_hour'] ? 1 : -1;
        });
        $resData['hour'] = array_column($newPageviewArr, 'hour');
        $resData['page_view'] = array_column($newPageviewArr, 'pageview');
        $resData['total_page_view'] = array_sum($resData['page_view']);
        return response()->json(['message' => 'Pageview and Impression graph Data Get Successfully', 'status' => true, 'data' => $resData ]);
    }

    /*
    ** revenue and request graph
    **
    */
    public function ad_realtime_revenue_request_graph_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'time_interval' => 'required',
            'revenue' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $revenue = $request->input('revenue');
        $time_interval = $request->input('time_interval');
        $now = Carbon::now();
        $hoursAgo = $now->copy()->subHours($time_interval);
        
        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $resData = $this->getRealtimeRevenueRequestGraphQuery($hoursAgo, $now, $clientPer, $site_id);
        return response()->json(['message' => 'Revenue and request graph Data Get Successfully', 'status' => true, 'data' => $resData]);
    }

    /*
    ** cpm graph
    **
    */
    public function ad_realtime_cpm_graph_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'time_interval' => 'required',
            'revenue' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $revenue = $request->input('revenue');
        $time_interval = $request->input('time_interval');
        $now = Carbon::now();
        $hoursAgo = $now->copy()->subHours($time_interval);
        
        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $resData = $this->getRealtimeCpmGraphQuery($hoursAgo, $now, $clientPer, $site_id);
        return response()->json(['message' => 'Cpms graph Data Get Successfully', 'status' => true, 'data' => $resData]);
    }

    /*
    ** RPM graph
    **
    */
    public function ad_realtime_rpm_graph_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'time_interval' => 'required',
            'revenue' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $revenue = $request->input('revenue');
        $time_interval = $request->input('time_interval');
        $now = Carbon::now();
        $hoursAgo = $now->copy()->subHours($time_interval);
        // echo $now . ' - ' . $hoursAgo;
        
        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $resData = $this->getRealtimeRpmGraphQuery($hoursAgo, $now, $clientPer, $site_id, $time_interval);
        return response()->json(['message' => 'RPM graph Data Get Successfully', 'status' => true, 'data' => $resData]);
    }

    /*
    **getTopSizesSData
    **
    */
    public function ad_top_sizes_table_data_by_site(Request $request, $site_id)
    {
        $validator = Validator::make($request->all(), [
            'time_interval' => 'required',
            'revenue' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $revenue = $request->input('revenue');
        $time_interval = $request->input('time_interval');
        $now = Carbon::now();
        $hoursAgo = $now->copy()->subHours($time_interval);

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $sizeData = $this->getSizeTableReportQuery($now, $hoursAgo, $site_id, $clientPer);
        return response()->json(['status' => true, 'message' => 'Top Size data by site get Successfully', 'sizeData' => $sizeData,]);
    }

    /*
    **getTopNetworksSData
    **
    */
    public function ad_top_networks_table_data_by_site(Request $request, $site_id)
    {
        $validator = Validator::make($request->all(), [
            'time_interval' => 'required',
            'revenue' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }
        $revenue = $request->input('revenue');
        $time_interval = $request->input('time_interval');
        $now = Carbon::now();
        $hoursAgo = $now->copy()->subHours($time_interval);

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }
        $networkData = $this->getNetworksTableReportQuery($now, $hoursAgo, $site_id, $clientPer);
        return response()->json(['status' => true, 'message' => 'Top Network data by site get Successfully', 'networkData' => $networkData,]);
    }

    /*
    **getPopularPagesSData
    **
    */
    public function ad_top_popularpages_table_data_by_site(Request $request, $site_id)
    {
        $validator = Validator::make($request->all(), [
            'time_interval' => 'required',
            'revenue' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }
        $revenue = $request->input('revenue');
        $time_interval = $request->input('time_interval');
        $now = Carbon::now();
        $hoursAgo = $now->copy()->subHours($time_interval);

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $adPopularData = $this->getPopularpagesTableReportQuery($now, $hoursAgo, $site_id, $clientPer, $time_interval);
        return response()->json(['status' => true, 'message' => 'Top Popularpages data by site get Successfully', 'popularPagesData' => $adPopularData]);
    }
}