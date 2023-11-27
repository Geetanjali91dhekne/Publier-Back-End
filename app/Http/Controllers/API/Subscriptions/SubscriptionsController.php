<?php

namespace App\Http\Controllers\API\Subscriptions;

use App\Http\Traits\SubscriptionsDashboard\TableDataTrait;
use App\Http\Traits\SubscriptionsDashboard\GraphTrait;
use App\Http\Controllers\Controller;
use App\Http\Traits\SubscriptionsDashboard\TopCardTrait;
use App\Models\DailyReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SubscriptionsController extends Controller
{
    use TableDataTrait;
    use TopCardTrait;
    use GraphTrait;

    /*
    ** Get top card Subscriptions.
    **
    */
    public function sub_top_card(Request $request)
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

        $topCardData = $this->getTopCardReportQuery($startDate, $endDate, $clientPer, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Top card data get Successfully', 'status' => true, 'topcard' => $topCardData]);
    }

    /*
    ** Get Pageview Countries graph.
    **
    */
    public function sub_countries_graph(Request $request)
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

        $subCountriesGraph = $this->getCountriesGraphQuery($startDate, $endDate, $siteId);
        return response()->json(['message' => 'Countries graph data get Successfully', 'status' => true, 'data' => $subCountriesGraph]);
    }

    /*
    ** Get Pageview Devices graph.
    **
    */
    public function sub_devices_graph(Request $request)
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

        $subDevicesGraph = $this->getDevicesGraphQuery($startDate, $endDate, $siteId);
        return response()->json(['message' => 'Devices graph data get Successfully', 'status' => true, 'data' => $subDevicesGraph]);
    }

    /*
    ** Get widget1 table Subscriptions.
    **
    */
    public function sub_widget1_table(Request $request)
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
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $subWidget1Data = $this->getWidget1TableReportQuery($startDate, $endDate, $clientPer, $siteId);
        return response()->json(['message' => 'Widget1 table data get Successfully', 'status' => true, 'subWidget1Data' => $subWidget1Data]);
    }

    /*
    ** Get top card Subscriptions.
    **
    */
    public function reason_for_unsubscribing(Request $request)
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
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $siteId = $request->input('site_id');
        $reasonData = $this->getUnsubscribingReasonQuery($startDate, $endDate, $siteId);
        return response()->json(['message' => 'Unsubscribing reason data get Successfully', 'status' => true, 'data' => $reasonData]);
    }

    /*
    ** Get Domains table pageview.
    **
    */
    public function sub_domain_table(Request $request)
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
        $subDomainData = $this->getDomainTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Domains table data get Successfully', 'status' => true, 'data' => $subDomainData]);
    }

    /*
    ** Get Countries table pageview.
    **
    */
    public function sub_countries_table(Request $request)
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

        $subCountriesData = $this->getCountriesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Countries table data get Successfully', 'status' => true, 'data' => $subCountriesData]);
    }

    /*
    ** Get Devices table pageview.
    **
    */
    public function sub_devices_table(Request $request)
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

        $subDevicesData = $this->getDevicesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Devices table data get Successfully', 'status' => true, 'data' => $subDevicesData]);
    }

    /*
    ** Get Pages table pageview.
    **
    */
    public function sub_pages_table(Request $request)
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

        $subPagesData = $this->getPagesTableReportQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['message' => 'Pages table data get Successfully', 'status' => true, 'data' => $subPagesData]);
    }

    /*
    ** Get Subscribers table pageview.
    **
    */
    public function sub_subscribers_table(Request $request)
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

        $subSubscribersData = $this->getSubscribersTableReportQuery($startDate, $endDate, $siteId);
        return response()->json(['message' => 'Subscribers table data get Successfully', 'status' => true, 'data' => $subSubscribersData]);
    }

    /*
    ** Get Subscriber log table pageview.
    **
    */
    public function sub_subscriber_log_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'site_id' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $siteId = $request->input('site_id');
        $id = $request->input('id');

        $subscriberLog = $this->getSubscriberLogTableQuery($siteId, $id);
        return response()->json(['message' => 'Subscriber log table data get Successfully', 'status' => true, 'data' => $subscriberLog]);
    }

    /*
    **Export pageview and subscriptions table data
    **
    */
    public function pageview_and_subscriptions_export_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'table_type' => 'required',
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

        $response = $this->exportTableViewDataQuery($startDate, $endDate, $tableType, $oldStartDate, $oldEndDate, $siteId);
        return $response;
    }


    /*
    **getRevenueGraphData
    **
    */
    public function sub_revenue_graph_data(Request $request)
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
        $result = $this->revenueGraphData($startDate, $endDate, $clientPer, $siteId);
        $resData['lables'] = array_column($result, 'date');
        $resData['rev'] = array_column($result, 'sum_revenue');
        return response()->json(['message' => 'Revenue graph data get successfully', 'status' => true, 'data' => $resData]);
    }

    /*
    **get ActiveSubscription GraphData
    **
    */
    public function sub_active_subscription_graph_data(Request $request)
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

        $result = $this->activeSubscriptionGraphData($startDate, $endDate, $siteId);
        $activeSubData['lables'] = array_column($result, 'date');
        $activeSubData['newsub'] = array_column($result, 'count_activesub');
        return response()->json(['message' => 'Active Subscription graph data get successfully', 'status' => true, 'activeSubData' => $activeSubData]);
    }

    /*
    **get NewSubscription GraphData
    **
    */
    public function sub_new_subscription_graph_data(Request $request)
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
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $siteId = $request->input('site_id');

        $result = $this->newSubscriptionGraphData($startDate, $endDate, $siteId);

        $newSubData['lables'] = array_column($result, 'date');
        $newSubData['newsub'] = array_column($result, 'count_newsub');
        return response()->json(['message' => 'New Subscription graph data get successfully', 'status' => true, 'newSubData' => $newSubData]);
    }

    /*
    **get UnSubscription GraphData
    **
    */
    public function sub_unsubcribes_graph_data(Request $request)
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
        $result = $this->unSubcribesGraphData($startDate, $endDate, $siteId);

        $unSubData['lables'] = array_column($result, 'date');
        $unSubData['unsub'] = array_column($result, 'count_unsub');
        return response()->json(['message' => 'UnSubscription graph data get successfully', 'status' => true, 'unSubData' => $unSubData]);
    }

    /*
    **getRPMGraphData
    **
    */
    public function sub_rpm_graph_data(Request $request)
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
        $result = $this->rpmGraphData($startDate, $endDate, $clientPer, $siteId);
        $rpmSubData['lables'] = array_column($result, 'date');
        $rpmSubData['rpm'] = array_column($result, 'sum_rpm');

        return response()->json(['message' => 'Rpm graph data get successfully', 'status' => true, 'rpmSubData' => $rpmSubData]);
    }

    /*
    **Get Domains StatsData
    **
    */
    public function sub_domain_stats_data(Request $request)
    {
        // echo "test";
        // exit;
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
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');
        $siteId = $request->input('site_id');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $domainStats = $this->getDomainStatsGraphQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['status' => true, 'message' => 'Domain stats data get successfully', 'data' => $domainStats]);
    }

    /*
    **Get Country StatsData
    **
    */
    public function sub_country_stats_data(Request $request)
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
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');
        $siteId = $request->input('site_id');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $countryStats = $this->getCountryStatsGraphQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        return response()->json(['status' => true, 'message' => 'Country stats data get successfully', 'data' => $countryStats]);
    }


    /*
    **Export Widget table data
    **
    */
    public function sub_widget_export_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue'  => 'required',
            'table_type' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'message' => $errors, 'status' => false
            ], 403);
        }
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $table_type = $request->input('table_type');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        if ($table_type == 'widget1') {
            $subWidgetData = $this->getWidget1TableReportQuery($startDate, $endDate, $clientPer);
        } else {
            return response()->json(['message' => 'table data not found', 'status' => false], 403);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($table_type == 'widget1') $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Impression');
        $sheet->setCellValue('C1', 'Clicks');
        $sheet->setCellValue('D1', 'Subscriptions');
        $sheet->setCellValue('E1', 'Converstion Ratio');

        $rows = 2;
        foreach ($subWidgetData as $sites) {
            if ($table_type == 'widget1') $sheet->setCellValue('A' . $rows, $sites['date']);
            if ($table_type == 'widget2') $sheet->setCellValue('A' . $rows, $sites['notice_location']);
            if ($table_type == 'widget3') $sheet->setCellValue('A' . $rows, $sites['subscription_pricing']);
            $sheet->setCellValue('B' . $rows, $sites['total_impressions']);
            $sheet->setCellValue('C' . $rows, $sites['total_clicks']);
            $sheet->setCellValue('D' . $rows, $sites['subscriptions']);
            $sheet->setCellValue('E' . $rows, $sites['converstions_ratio']);
            $rows++;
        }

        $fileName = "widget.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/" . $fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/' . $fileName;
        return response()->download($path)->deleteFileAfterSend();
    }

    /*
    *getSearch Subscribers List
    **
    */

    public function sub_search_data(Request $request)
    {
        $subSubscribersData = $this->getSubscribersSearchReportQuery();
        return response()->json(['status' => true, 'message' => 'Subscribers list get successfully', 'subscribersData' => $subSubscribersData]);
    }
}
