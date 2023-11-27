<?php

namespace App\Http\Controllers\API\AdOptimization;

use App\Http\Controllers\Controller;
use App\Http\Traits\SiteDetailsGraphTrait;
use App\Http\Traits\SiteDetailsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use \Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SiteDetailsController extends Controller
{
    use SiteDetailsTrait;
    use SiteDetailsGraphTrait;
    /*
    **get Revenue Graph Data by site
    **
    */
    public function ad_revenue_graph_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $compare = $request->input('compare');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $resData = $this->getRevenueGraphQuery($startDate, $endDate, $site_id, $clientPer, $oldStartDate, $oldEndDate);
        return response()->json(['message' => 'Revenue graph data by site get successfully', 'status' => true, 'revenue' => $resData]);
    }

    /*
    **get Requests Graph Data by site
    **
    */
    public function ad_requests_graph_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
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

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $resData = $this->getRequestsGraphQuery($startDate, $endDate, $site_id, $oldStartDate, $oldEndDate);        
        return response()->json(['message' => 'Requests graph data by site get successfully', 'status' => true, 'requests' => $resData]);
    }

    /*
    **get CPM Graph Data by site
    **
    */
    public function ad_cpm_graph_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue' => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $compare = $request->input('compare');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $resData= $this->getCpmsGraphQuery($startDate, $endDate, $site_id, $clientPer, $oldStartDate, $oldEndDate);
        return response()->json(['message' => 'CPM graph data by site get successfully', 'status' => true, 'cpm' => $resData]);
    }

    /*
    **get Impression Graph Data by site
    **
    */
    public function ad_impression_graph_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $compare = $request->input('compare');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $resData = $this->getImpressionGraphQuery($startDate, $endDate, $site_id, $oldStartDate, $oldEndDate);
        return response()->json(['message' => 'Impression graph data by site get successfully', 'status' => true, 'impression' => $resData]);
    }

    /*
    **Metrics, Fill, Unfilled, Unrendered graph api
    **
    */
    public function ad_fill_comparison_graph_data(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $adReportData = $this->fillComparisonGraphQuery($startDate, $endDate, $site_id);
        return response()->json(['message' => 'Comparison graph data by site get successfully', 'status' => true, 'fill' => $adReportData]);
    }

    /*
    **GetAll Date table Data
    **
    */
    public function ad_date_table_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue'  => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }
        
        $adDateData = $this->getDateReportQuery($startDate, $endDate, $site_id, $clientPer);
        return response()->json(['message' => 'Date table data by site get Successfully', 'status' => true, 'adDateData' => $adDateData]);
    }

    /*
    **GetAl Networks table  Data
    **
    */
    public function ad_networks_table_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue'  => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $adNetworkData = $this->getNetworkTableReportQuery($startDate, $endDate, $site_id, $clientPer);

        return response()->json(['status' => true, 'message' => 'Network table data by site get Successfully', 'adNetworkData' => $adNetworkData,]);
    }

    /*
    **GetAl Size table  Data
    **
    */
    public function ad_sizes_table_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue'  => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPer = config('app.client_per_val_net') / 100;
        if ($revenue == 'gross') {
            $clientPer = config('app.client_per_val_gross') / 100;
        }

        $adSizeData = $this->getSizesReportQuery($startDate, $endDate, $site_id, $clientPer);
        return response()->json(['status' => true, 'message' => 'Sizes data get successfully', 'adSizeData' => $adSizeData,]);
    }

    /*
    **Get DemandChannel StatsData
    **
    */
    public function ad_demand_channel_stats_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $compare = $request->input('compare');
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

        /* current period data */
        $demandChannelStats = $this->getDemandChannelGraphQuery($startDate, $endDate, $site_id, $clientPer);

        /* previos period data */
        $networkIds = $demandChannelStats->pluck('network_id')->toArray();
        $previosDemandData = $this->getOldNetworkDemandGraphQuery($oldStartDate, $oldEndDate, $site_id, $networkIds, $clientPer);

        $demandChannelData = $this->demandChannelPercentage($demandChannelStats, $previosDemandData);
        return response()->json([
            'status' => true, 'message' => 'Demand channel stats data get successfully', 'addemandChannelStatsData' => $demandChannelData,
        ]);
    }

    /*
    **Get DemandChannel StatsData
    **
    */
    public function ad_size_stats_data_by_site(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $compare = $request->input('compare');
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

        $sizesStats = $this->getSizesReportGraphQuery($startDate, $endDate, $site_id, $clientPer);

        /* previos period data */
        $sizeIds = $sizesStats->pluck('size_id')->toArray();
        $oldSizesData = $this->getSizesReportGraphQuery($oldStartDate, $oldEndDate, $site_id, $clientPer)->whereIn('size_id', $sizeIds);
        $oldSizesData = $oldSizesData->toArray();
        $previosSizesData = array_replace_recursive(
            array_combine(array_column($oldSizesData, "size_id"), $oldSizesData)
        );

        $sizesGraphData = $this->sizeStatsPercentage($sizesStats, $previosSizesData);
        return response()->json(['status' => true, 'message' => 'Sizes stats data get successfully', 'adSizesStatsData' => $sizesGraphData]);
    }

    /*
    **Export table data
    **
    */
    public function ad_export_site_details(Request $request, $site_id)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue'  => 'required',
            'table_type' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
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

        if ($table_type == 'date') {
            $adSiteData = $this->getDateReportQuery($startDate, $endDate, $site_id, $clientPer);
        } else if ($table_type == 'network') {
            $adSiteData = $this->getNetworkTableReportQuery($startDate, $endDate, $site_id, $clientPer);
        } else if ($table_type == 'size') {
            $adSiteData = $this->getSizesReportQuery($startDate, $endDate, $site_id, $clientPer);
        } else {
            return response()->json(['message' => 'table data not found', 'status' => false], 403);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($table_type == 'date') $sheet->setCellValue('A1', 'Date');
        if ($table_type == 'network') $sheet->setCellValue('A1', 'Network Name');
        if ($table_type == 'size') $sheet->setCellValue('A1', 'Size');
        $sheet->setCellValue('B1', 'Ad Request');
        $sheet->setCellValue('C1', 'Monetized Impression');
        $sheet->setCellValue('D1', 'Revenue');
        $sheet->setCellValue('E1', 'CPM');
        $sheet->setCellValue('F1', 'Fill Rate');
        $sheet->setCellValue('G1', 'RPM');
        if ($table_type == 'date') $sheet->setCellValue('H1', 'Pageview');

        $rows = 2;
        foreach($adSiteData as $sites){
            if ($table_type == 'date') $sheet->setCellValue('A' . $rows, $sites['date']);
            if ($table_type == 'network') $sheet->setCellValue('A' . $rows, $sites['network_name']);
            if ($table_type == 'size') $sheet->setCellValue('A' . $rows, $sites['dimensions']);
            $sheet->setCellValue('B' . $rows, $sites['sum_ad_request']);
            $sheet->setCellValue('C' . $rows, $sites['sum_impressions']);
            $sheet->setCellValue('D' . $rows, $sites['sum_revenue']);
            $sheet->setCellValue('E' . $rows, $sites['sum_cpms']);
            $sheet->setCellValue('F' . $rows, $sites['sum_fillrate']);
            $sheet->setCellValue('g' . $rows, $sites['sum_rpm']);
            if ($table_type == 'date') $sheet->setCellValue('h' . $rows, $sites['pageview']);
            $rows++;
        }

        $fileName = $table_type."-".$site_id.".xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/".$fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/'.$fileName;
        return response()->download($path)->deleteFileAfterSend();
    }
}