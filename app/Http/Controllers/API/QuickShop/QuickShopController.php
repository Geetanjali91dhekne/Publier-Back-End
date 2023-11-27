<?php

namespace App\Http\Controllers\API\QuickShop;

use App\Http\Controllers\Controller;
use App\Http\Traits\QuickShop\TopCardTrait;
use App\Http\Traits\QuickShop\GraphTrait;
use App\Http\Traits\QuickShop\TableDataTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class QuickShopController extends Controller
{
    use GraphTrait;
    use TopCardTrait;
    use TableDataTrait;

    /*
    ** Get top card
    **
    */
    public function quick_top_card(Request $request)
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

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'))->format('Y-m-d');
            $oldEndDate = Carbon::parse($request->input('compare_end_date'))->format('Y-m-d');
        }

        $topCardData = $this->getQuickShopTopCardQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $clientPer, $siteId);
        return response()->json(['message' => 'Top card data get Successfully', 'status' => true, 'topcard' => $topCardData]);
    }


    /*
    ** Get topItems table quickshop.
    **
    */
    public function quick_top_items_table(Request $request)
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

        $quickTopItemsData = $this->getTopItemsTableQuery($startDate, $endDate, $clientPer, $siteId);
        return response()->json(['message' => 'TopItems table data get Successfully', 'status' => true, 'quickTopItemsData' => $quickTopItemsData]);
    }


    /*
    **Export QuickShoop topitems table data
    **
    */
    public function quick_top_items_export_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'table_type' => 'required',
            'site_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $tableType = $request->input('table_type');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $response = $this->exportQuickTopItemsTableDataQuery($startDate, $endDate, $clientPer, $tableType, $siteId);
        return $response;
    }

    /*
    ** Get QuickShoop Countries graph.
    **
    */
    public function quick_country_graph(Request $request)
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

        $quickCountriesGraph = $this->getCountriesGraphQuery($startDate, $endDate, $clientPer, $siteId);
        return response()->json(['message' => 'Country graph data get Successfully', 'status' => true, 'quickCountriesGraph' => $quickCountriesGraph]);
    }


    /*
    **get TotalEranings GraphData
    **
    */
    public function quick_total_eranings_graph_data(Request $request)
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
        $quickEarn['lables'] = array_column($result, 'date');
        $quickEarn['totalearnings'] = array_column($result, 'sum_earnings');
        return response()->json(['message' => 'TotalEarnings graph data get successfully', 'status' => true, 'quickEarnData' => $quickEarn]);
    }

    /*
    **get Items Sold GraphData
    **
    */
    public function quick_items_sold_graph_data(Request $request)
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

        $result = $this->itemSoldGraphData($startDate, $endDate, $siteId);
        $quickItems['lables'] = array_column($result, 'date');
        $quickItems['itemsold'] = array_column($result, 'sum_items');
        return response()->json(['message' => 'Items Sold graph data get successfully', 'status' => true, 'quickItemsData' => $quickItems]);
    }

    /*
    **get Avg Purchase value GraphData
    **
    */
    public function quick_purchase_value_graph_data(Request $request)
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

        $result = $this->purchaseValueGraphData($startDate, $endDate, $clientPer, $siteId);
        $quickPurchase['lables'] = array_column($result, 'date');
        $quickPurchase['avgpurchase'] = array_column($result, 'total_avg_purchase');
        return response()->json(['message' => 'Purchase Vakue graph data get successfully', 'status' => true, 'quickPurchase' => $quickPurchase]);
    }

    /*
    **get Product Pvs GraphData
    **
    */
    public function quick_product_pvs_graph_data(Request $request)
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

        $result = $this->productPvsGraphData($startDate, $endDate, $siteId);
        $quickProduct['lables'] = array_column($result, 'date');
        $quickProduct['prdoductpvs'] = array_column($result, 'sum_product_pvs');
        return response()->json(['message' => 'Product Pvs graph data get successfully', 'status' => true, 'quickProduct' => $quickProduct]);
    }

    /*
    **get Converstion Ratio GraphData
    **
    */
    public function quick_converstion_ratio_graph_data(Request $request)
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

        $result = $this->converstionRatioGraphData($startDate, $endDate, $clientPer, $siteId);
        $quickConverstion['lables'] = array_column($result, 'date');
        $quickConverstion['converstionratio'] = array_column($result, 'total_converstion_ratio');
        return response()->json(['message' => 'Converstion Ratio graph data get successfully', 'status' => true, 'quickConverstion' => $quickConverstion]);
    }
}