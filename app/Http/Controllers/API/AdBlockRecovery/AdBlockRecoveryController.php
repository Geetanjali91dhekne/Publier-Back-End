<?php

namespace App\Http\Controllers\API\AdBlockRecovery;

use App\Http\Controllers\Controller;
use App\Http\Traits\AdBlockRecovery\TableDataTrait;
use App\Http\Traits\AdBlockRecovery\TopCardTrait;
use App\Http\Traits\AdBlockRecovery\GraphTrait;
use App\Models\Sites;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AdBlockRecoveryController extends Controller
{
    use TopCardTrait;
    use TableDataTrait;
    use GraphTrait;
    //

    /*
    ** site list api
    **
    */
    public function get_new_sites_list(Request $request)
    {
        $sitesData = Sites::query();
        $sitesData = $sitesData->where('status', 'Y')->select('site_id', 'site_name')->get()->toArray();
        return response()->json(['status' => true, 'message' => 'New Site list get successfully', 'data' => $sitesData]);
    }

    /*
    ** Get top card
    **
    */
    public function ab_recovery_top_card(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'site_id' => 'nullable',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $topCardData = $this->getAdBlockTopCardQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId, $widgetId);
        return response()->json(['message' => 'Top card data get Successfully', 'status' => true, 'topcard' => $topCardData]);
    }

    /*
    ** Get widget table adblock.
    **
    */
    public function ab_recovery_widget_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $abWidget1Data = $this->getAdBlockWidgetTableQuery($startDate, $endDate, $siteId, $widgetId);
        return response()->json(['message' => 'Widget table data get Successfully', 'status' => true, 'data' => $abWidget1Data]);
    }

    /*
    ** Export Widget table data
    **
    */
    public function ab_recovery_export_widget_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $response = $this->exportAdBlockWidgetTableQuery($startDate, $endDate, $siteId, $widgetId);
        return $response;
    }

    /*
    ** browsers (devices) data
    **
    */
    public function ab_recovery_browsers_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $browsersData = $this->getAdBlockBrowsersDataQuery($startDate, $endDate, $siteId, $widgetId);
        return response()->json(['message' => 'Browsers data get Successfully', 'status' => true, 'data' => $browsersData]);
    }

    /*
    ** Domains table data
    **
    */
    public function ab_recovery_domains_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $compare = $request->input('compare');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $domainsData = $this->getAdBlockDomainsTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId, $widgetId);
        return response()->json(['message' => 'Domain table data get Successfully', 'status' => true, 'data' => $domainsData]);
    }

    /*
    ** Devices table data
    **
    */
    public function ab_recovery_devices_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $compare = $request->input('compare');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $devicesData = $this->getAdBlockDevicesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId, $widgetId);
        return response()->json(['message' => 'Devices table data get Successfully', 'status' => true, 'data' => $devicesData]);
    }

    /*
    ** Countries table data
    **
    */
    public function ab_recovery_countries_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $compare = $request->input('compare');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $countriesData = $this->getAdBlockCountriesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId, $widgetId);
        return response()->json(['message' => 'Countries table data get Successfully', 'status' => true, 'data' => $countriesData]);
    }

    /*
    **Export AdBlock pageview table data
    **
    */
    public function ab_pageview_export_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'table_type' => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'site_id' => 'nullable',
            'widget_id' => 'nullable',
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
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');
        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $response = $this->exportAbPVsTableDataQuery($startDate, $endDate, $tableType, $oldStartDate, $oldEndDate, $siteId, $widgetId);
        return $response;
    }

    /*
    **get AdBlockPvs GraphData
    **
    */
    public function adblock_pvs_graph_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $result = $this->adBlockPvsGraphData($startDate, $endDate, $siteId, $widgetId);
        $pageviewData['lables'] = array_column($result, 'date');
        $pageviewData['pvs'] = array_column($result, 'sum_pageview');
        return response()->json(['message' => 'AdBlockPvs graph data get successfully', 'status' => true, 'adblockPvsData' => $pageviewData]);
    }

    /*
    **get AdBlockUsers GraphData
    **
    */
    public function adblock_users_graph_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'site_id' => 'nullable',
            'widget_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $siteId = $request->input('site_id');
        $widgetId = $request->input('widget_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $result = $this->adBlockUsersGraphData($startDate, $endDate, $siteId, $widgetId);

        $userPageview['lables'] = array_column($result, 'date');
        $userPageview['adblockusers'] = array_column($result, 'adblock_users');
        return response()->json(['message' => 'AdblockUsers graph data get successfully', 'status' => true, 'adblockusersData' => $userPageview]);
    }
}
