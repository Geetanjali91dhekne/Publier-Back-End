<?php

namespace App\Http\Traits\QuickShop;

use App\Models\MerchReports;
use App\Models\MrOrders;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait TableDataTrait
{
    public function getTopItemsTableQuery($startDate, $endDate, $clientPer, $siteId = null)
    {
        $quickEarnData = MrOrders::query();
        if ($siteId) $quickEarnData = $quickEarnData->where('dt_mr_orders.site_id', $siteId);
        $quickEarnData = $quickEarnData->whereBetween('dt_mr_orders.created_at', [$startDate, $endDate])
            ->join('dt_sites', 'dt_mr_orders.site_id', '=', 'dt_sites.site_id')
            ->select(
                'dt_mr_orders.site_id',
                'dt_sites.site_url',
                DB::raw('SUM(amount) * ' . $clientPer . ' as sum_revenue'),
                DB::raw('COUNT(quantity) as sum_items_sold'),
            )
            ->groupBy('dt_mr_orders.site_id')
            ->get()->toArray();

        $siteIds = array_column($quickEarnData, 'site_id');
        $quickProductData = MerchReports::query();
        if ($siteId) $quickProductData = $quickProductData->where('site_id', $siteId);
        $quickProductData = $quickProductData->whereIn('site_id', $siteIds)->whereBetween('dt_merch_reports.date', [$startDate, $endDate])
            ->select(
                'site_id',
                DB::raw('SUM(product_pageviews) as sum_page_view'),
            )
            ->groupBy('site_id')
            ->get()->toArray();

        $quickProductData = array_replace_recursive(array_combine(array_column($quickProductData, "site_id"), $quickProductData));

        foreach ($quickEarnData as $key => $val) {
            if (array_key_exists($val['site_id'], $quickProductData)) {
                $reportPv = $quickProductData[$val['site_id']];
                $quickEarnData[$key]['sum_page_view'] = $reportPv['sum_page_view'];
            } else {
                $quickEarnData[$key]['sum_page_view'] = (float) 0;
            }
        }

        foreach ($quickEarnData as $key => $ratio) {
            $quickEarnData[$key]['total_converstion_ratio'] = $ratio['sum_page_view'] ? (($ratio['sum_revenue'] * 100) / $ratio['sum_page_view']) : 0;
        }
        return $quickEarnData;
    }

    public function exportQuickTopItemsTableDataQuery($startDate, $endDate, $clientPer, $tableType, $siteId = null)
    {
        if ($tableType == 'topitems') {
            $quickTableData = $this->getTopItemsTableQuery($startDate, $endDate, $clientPer, $siteId);
        } else {
            return response()->json(['message' => 'Table type data not found', 'status' => false], 403);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($tableType == 'topitems') $sheet->setCellValue('A1', 'Items Name');
        $sheet->setCellValue('B1', 'Earnings');
        $sheet->setCellValue('C1', 'Page Views');
        $sheet->setCellValue('D1', 'Items Sold');
        $sheet->setCellValue('E1', 'Converstion Ratio');

        $rows = 2;
        foreach ($quickTableData as $data) {
            if ($tableType == 'topitems') $sheet->setCellValue('A' . $rows, $data['site_url']);
            $sheet->setCellValue('B' . $rows, $data['sum_revenue']);
            $sheet->setCellValue('C' . $rows, $data['sum_page_views']);
            $sheet->setCellValue('D' . $rows, $data['sum_items_sold']);
            $sheet->setCellValue('E' . $rows, $data['sum_converstion_ratio']);
            $rows++;
        }

        $fileName = "topitem.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/" . $fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/' . $fileName;
        return response()->download($path)->deleteFileAfterSend();;
    }
}
