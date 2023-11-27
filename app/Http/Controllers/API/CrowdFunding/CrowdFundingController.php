<?php

namespace App\Http\Controllers\API\CrowdFunding;

use App\Http\Controllers\Controller;
use App\Http\Traits\CrowdFunding\TableDataTrait;
use App\Http\Traits\CrowdFunding\TopCardTrait;
use App\Http\Traits\CrowdFunding\GraphTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class CrowdFundingController extends Controller
{
    use TopCardTrait;
    use TableDataTrait;
    use GraphTrait;
    //

    /*
    ** Get top card
    **
    */
    public function cf_top_card(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $compare = $request->input('compare');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $topCardData = $this->getCrowdfundsTopCardQuery($startDate, $endDate, $clientPer, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Top card data get Successfully', 'status' => true, 'topcard' => $topCardData]);
    }

    /*
    ** Get earning country graph data
    **
    */
    public function cf_earning_country_graph(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $earningGraph = $this->getEarningCountryGraphQuery($startDate, $endDate, $clientPer, $siteId);
        return response()->json(['message' => 'Country earning graph data get Successfully', 'status' => true, 'data' => $earningGraph]);
    }

    /*
    ** Get donors country graph data
    **
    */
    public function cf_donors_country_graph(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $donorsCountryGraph = $this->getDonorsCountryGraphQuery($startDate, $endDate, $clientPer, $siteId);
        return response()->json(['message' => 'Country donors graph data get Successfully', 'status' => true, 'data' => $donorsCountryGraph]);
    }

    /*
    ** Get earning devices graph data
    **
    */
    public function cf_earning_devices_graph(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $earningGraph = $this->getEarningDevicesGraphQuery($startDate, $endDate, $clientPer, $siteId);
        return response()->json(['message' => 'Devices earning graph data get Successfully', 'status' => true, 'data' => $earningGraph]);
    }

    /*
    ** Get donors devices graph data
    **
    */
    public function cf_donors_devices_graph(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $donorsDeviceGraph = $this->getDonorsDevicesGraphQuery($startDate, $endDate, $clientPer, $siteId);
        return response()->json(['message' => 'Device donors graph data get Successfully', 'status' => true, 'data' => $donorsDeviceGraph]);
    }

    /*
    ** Get widget table crowdfunding.
    **
    */
    public function cf_widget_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $subWidget1Data = $this->getWidgetTableQuery($startDate, $endDate, $clientPer, $siteId);
        return response()->json(['message' => 'Widget table data get Successfully', 'status' => true, 'data' => $subWidget1Data]);
    }

    /*
    ** export widget table crowdfunding.
    **
    */
    public function cf_export_widget_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $response = $this->exportCfWidgetTableQuery($startDate, $endDate, $clientPer, $siteId);
        return $response;
    }

    /*
    ** Get Domains table pageview and fundraiser views.
    **
    */
    public function cf_domain_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }
        $cfDomainData = $this->getCfDomainTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Domains table data get Successfully', 'status' => true, 'data' => $cfDomainData]);
    }

    /*
    ** Get Countries table pageview and fundraiser views.
    **
    */
    public function cf_countries_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $cfCountriesData = $this->getCfCountriesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Countries table data get Successfully', 'status' => true, 'data' => $cfCountriesData]);
    }

    /*
    ** Get Devices table pageview and fundraiser views.
    **
    */
    public function cf_devices_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $cfDevicesData = $this->getCfDevicesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Devices table data get Successfully', 'status' => true, 'data' => $cfDevicesData]);
    }

    /*
    ** Get Pages table pageview and fundraiser views.
    **
    */
    public function cf_pages_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $subPagesData = $this->getCfPagesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Pages table data get Successfully', 'status' => true, 'data' => $subPagesData]);
    }

    /*
    **Export pageview and fundraiser views table data
    **
    */
    public function cf_pageview_fundraiser_export_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'table_type' => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'site_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $tableType = $request->input('table_type');
        $compare = $request->input('compare');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $response = $this->exportCfPVsFundViewsTableDataQuery($startDate, $endDate, $tableType, $oldStartDate, $oldEndDate, $siteId);
        return $response;
    }

    /*
    **get TotalEranings GraphData
    **
    */
    public function crow_total_eranings_graph_data(Request $request)
    {

        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }
        $result = $this->totalEarningsGraphData($startDate, $endDate, $clientPer, $siteId);

        $crowEarn['lables'] = array_column($result, 'date');
        $crowEarn['totalearnings'] = array_column($result, 'sum_earnings');
        return response()->json(['message' => 'TotalEarnings graph data get successfully', 'status' => true, 'crowEarnData' => $crowEarn]);
    }

    /*
    **get TotalDonors GraphData
    **
    */
    public function crow_total_donors_graph_data(Request $request)
    {

        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $result = $this->totalDonorsGraphData($startDate, $endDate, $siteId);

        $crowDonors['lables'] = array_column($result, 'date');
        $crowDonors['totaldonors'] = array_column($result, 'total_donors');
        return response()->json(['message' => 'TotalDonors graph data get successfully', 'status' => true, 'crowdonorsData' => $crowDonors]);
    }

    /*
    **get FundraiserViews GraphData
    **
    */
    public function crow_fundraiser_views_graph_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $result = $this->fundraiserViewsGraphData($startDate, $endDate, $siteId);

        $fundraiserviews['lables'] = array_column($result, 'date');
        $fundraiserviews['fundraiserViews'] = array_column($result, 'fundraiser_views');
        return response()->json(['message' => 'FundraiserViews graph data get successfully', 'status' => true, 'fundViews' => $fundraiserviews]);
    }

    /*
    **get average donation GraphData
    **
    */
    public function crow_average_donation_graph_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $result = $this->averageDonationGraphData($startDate, $endDate, $clientPer, $siteId);
        $averageDonation['lables'] = array_column($result, 'date');
        $averageDonation['averageDonation'] = array_column($result, 'average_donation');
        return response()->json(['message' => 'Average Donation graph data get successfully', 'status' => true, 'data' => $averageDonation]);
    }

    /*
    **get fund Ecpm GraphData
    **
    */
    public function crow_fund_ecpm_graph_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $result = $this->fundEcpmGraphData($startDate, $endDate, $clientPer, $siteId);
        $fundEcpm['lables'] = array_column($result, 'date');
        $fundEcpm['fundEcpm'] = array_column($result, 'fund_ecpm');
        return response()->json(['message' => 'fund Ecpm graph data get successfully', 'status' => true, 'data' => $fundEcpm]);
    }
}
