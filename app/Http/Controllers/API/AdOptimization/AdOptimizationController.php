<?php

namespace App\Http\Controllers\API\AdOptimization;

use App\Http\Controllers\Controller;
use App\Http\Traits\AdOptimizationDashboard\AdOptimizationTrait;
use App\Http\Traits\AdOptimizationDashboard\GraphTrait;
use App\Http\Traits\AdOptimizationDashboard\TopCardTrait;
use App\Http\Traits\AdOptimizationDashboard\TableDataTrait;
use App\Http\Traits\AdServerTrait;
use App\Http\Traits\SitesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\DailyReport;
use App\Models\Sites;
use \Carbon\Carbon;


class AdOptimizationController extends Controller
{
    use AdOptimizationTrait;
    use TopCardTrait;
    use AdServerTrait;
    use GraphTrait;
    use TableDataTrait;
    use SitesTrait;
    /*
    ** Get Imp, request, fill rate Ad Optimization.
    **
    */
    public function ad_top_card(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date' => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'ad_server' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $compare = $request->input('compare');
        $adServer = $request->input('ad_server');

        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $topCard = $this->getTopCardData($startDate, $endDate, $oldStartDate, $oldEndDate, $adServer);
        return response()->json($topCard);
    }

    /*
    ** Get revenue, cpm Ad Optimization.
    **
    */
    public function ad_revenue_cmp_top_card(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date' => 'required',
            'revenue' => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'ad_server' => 'required',
        ]);

        //Validation
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $revenue = $request->input('revenue');
        $compare = $request->input('compare');
        $adServer = $request->input('ad_server');
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $revenueAndCmpData = $this->getTopCardRevenueCpmData($startDate, $endDate, $clientPer, $oldStartDate, $oldEndDate, $adServer);
        return response()->json($revenueAndCmpData);
    }

    /*
    **getFavouriteData
    **
    */
    public function ad_favourite_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'ad_server' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $userId = $request->get('userId');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $adServer = $request->input('ad_server');
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        /* Old db sites data */
        $oldDbFavouriteSites = $this->getOldSitesReportQuery($startDate, $endDate, $clientPer, $userId);
        if ($adServer == 'ON') $oldDbFavouriteSites = $oldDbFavouriteSites->whereIn('im_sites.id', $oldAdServerIds);
        
        /* New db sites data */
        $favouriteSitesData = $this->getNewSitesReportQuery($startDate, $endDate, $clientPer, $userId);
        if ($adServer == 'ON') $favouriteSitesData = $favouriteSitesData->whereIn('dt_sites.site_id', $newAdServerIds);
        $favouriteSitesData = $favouriteSitesData
            ->union($oldDbFavouriteSites)
            ->orderBy('total_revenue', 'desc')
            ->get();
        return response()->json(['message' => 'favourite data get successfully', 'status' => true, 'favouriteData' => $favouriteSitesData]);
    }

    /*
    **getRecentData
    **
    */
    public function ad_recent_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'ad_server' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $userId = $request->get('userId');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $adServer = $request->input('ad_server');
        $newAdServerIds = $this->newNoAdServerSiteIds();

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $siteData = $this->getRecentTabData($startDate, $endDate, $clientPer, $userId, $adServer, $newAdServerIds);
        return response()->json(['message' => 'recent data get successfully', 'status' => true, 'recentData' => $siteData]);
    }

    /*
    **getRevenueGraphData
    **
    */
    public function ad_revenue_graph_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'ad_server' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $adServer = $request->input('ad_server');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }
        $result = $this->revenueGraphData($startDate, $endDate, $adServer, $clientPer);

        $resData['lables'] = array_column($result, 'date');
        $resData['rev'] = array_column($result, 'total_revenue');
        return response()->json(['message' => 'Revenue graph data get successfully', 'status' => true, 'data' => $resData]);
    }

    /*
    **IMPs Graph
    **
    */
    public function ad_imps_graph(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'ad_server' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $adServer = $request->input('ad_server');

        $result = $this->impressionsGraphData($startDate, $endDate, $adServer);
        $resData['lables'] = array_column($result, 'date');
        $resData['imps'] = array_column($result, 'total_impressions');
        return response()->json([
            'message' => 'Imps graph Data Get Successfully', 'status' => true, 'data' => $resData
        ]);
    }

    /*
    **getAdRequestGraphData
    **
    */
    public function ad_request_graph_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'ad_server' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $adServer = $request->input('ad_server');

        $result = $this->requestGraphData($startDate, $endDate, $adServer);
        $resData['lables'] = array_column($result, 'date');
        $resData['request'] = array_column($result, 'request');
        return response()->json(['message' => 'ad Request Graph Data Get Successfully', 'status' => true, 'data' => $resData]);
    }

    /*
    **CPMs Graph
    **
    */
    public function ad_cpms_graph(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'ad_server' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $adServer = $request->input('ad_server');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $result = $this->cpmsGraphData($startDate, $endDate, $adServer, $clientPer);

        $resData['lables'] = array_column($result, 'date');
        $resData['cpms'] = array_column($result, 'total_cpms');
        return response()->json(['message' => 'Cpms graph Data Get Successfully', 'status' => true, 'data' => $resData]);
    }

    /*
    **getFillRateGraphData
    **
    */
    public function ad_fill_rate_graph_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'ad_server' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $adServer = $request->input('ad_server');

        $result = $this->fillrateGraphData($startDate, $endDate, $adServer);

        $resData['lables'] = array_column($result, 'date');
        $resData['fillrate'] = array_column($result, 'fillrate');
        return response()->json(['message' => 'ad fill rate graph Data Get Successfully', 'status' => true, 'data' => $resData]);
    }

    /*
    **getDemandChannelData
    **
    */
    public function ad_demand_channel_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'ad_server' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $compare = $request->input('compare');
        $adServer = $request->input('ad_server');
        
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $demandChannelData = $this->adDemandChannelData($startDate, $endDate, $adServer, $oldStartDate, $oldEndDate, $clientPer);
        return response()->json(['status' => true, 'message' => 'demand channel data get successfully', 'demandchannel' => $demandChannelData]);
    }

    /*
    **getTopTrendsData
    **
    */
    public function ad_top_trends_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'ad_server' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $adServer = $request->input('ad_server');

        $clientPerNet = config('app.client_per_val_net') / 100;
        $clientPerGross = config('app.client_per_val_gross') / 100;

        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $allTopTrends = $this->currentTopTrendData($startDate, $endDate, $clientPerNet, $clientPerGross, $adServer);

        $oldDbSiteIds = [];
        $newDbSiteIds = [];
        foreach ($allTopTrends as $sites) {
            if ($sites->site_id >= 1000) array_push($newDbSiteIds, $sites->site_id);
            else array_push($oldDbSiteIds, $sites->site_id);
        }

        $bothPreviosData = $this->previousPeriodTopTrendData($oldStartDate, $oldEndDate, $clientPerNet, $clientPerGross, $oldDbSiteIds, $newDbSiteIds);
        $calculatedAllData = $this->adOpsPercentageCalculate($allTopTrends, $bothPreviosData);
        return response()->json(['status' => true, 'message' => 'top trends data get successfully', 'topTrendsData' => $calculatedAllData]);
    }

    /*
    **Demand Sites For particular Network
    **
    */
    public function ad_demand_site_data(Request $request){
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'time_interval' => 'required',
            'ad_server' => 'required',
            'network_id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $userId = $request->get('userId');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $searchSite = $request->input('search_site');
        $timeInterval = $request->input('time_interval');
        $adServer = $request->input('ad_server');
        $network = $request->input('network_id');
        
        /* get value from SiteTempReports table */
        if ($timeInterval != 'customDates') {
            $allSiteTemp = $this->getSiteTempReportsQuery($timeInterval, $searchSite, $userId, $adServer);
            return response()->json(['message' => 'sites data get successfully', 'status' => true, 'sitesData' => $allSiteTemp]);
        }
        
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $clientPerNet = config('app.client_per_val_net') / 100;
        $clientPerGross = config('app.client_per_val_gross') / 100;
        
        /* Old db sites data */
        $oldDbSites = $this->getOldDemandSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer, $network);

        /* New db sites data */
        $sitesData = $this->getNewDemandSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer,$network);
        $sitesData = $sitesData->union($oldDbSites)->orderBy('gross_total_revenue', 'desc')->paginate(10);

        $oldDbSiteIds = [];
        $newDbSiteIds = [];
        foreach ($sitesData as $sites) {
            if ($sites->site_id >= 1000) array_push($newDbSiteIds, $sites->site_id);
            else array_push($oldDbSiteIds, $sites->site_id);
        }

        $sitesData = $this->getSiteRequestData($startDate, $endDate, $sitesData, $oldDbSiteIds, $newDbSiteIds);

        /* Previous Period Sites Data */
        $bothPreviosData = $this->getPreviousPeriodSiteWithRequest($oldStartDate, $oldEndDate, $newDbSiteIds, $oldDbSiteIds, $clientPerNet, $clientPerGross);

        $calculatedAllData = $this->percentageCalculate($sitesData, $bothPreviosData);
        return response()->json(['message' => 'sites data get successfully', 'status' => true, 'sitesData' => $calculatedAllData]);
    }
}
