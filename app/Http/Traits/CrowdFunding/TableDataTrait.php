<?php

namespace App\Http\Traits\CrowdFunding;

use App\Models\CrowdfundReports;
use App\Models\PaymentsInfo;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait TableDataTrait
{
    public function getWidgetTableQuery($startDate, $endDate, $clientPer, $siteId = null)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        
        /* get earning data */
        $fundTotalEar = Sites::query();
        if($siteId) $fundTotalEar = $fundTotalEar->where('dt_sites.site_id', $siteId);
        $fundTotalEar = $fundTotalEar
            ->join('dt_payments_info', function ($join) use ($startTime, $endTime) {
                $join->on('dt_payments_info.site_id', '=', 'dt_sites.site_id');
                $join->where('dt_payments_info.type', '=', 'live');
                $join->whereBetween('dt_payments_info.time_stamp', [$startTime, $endTime]);
            })
            ->select(
                'dt_payments_info.time_stamp',
                DB::raw('SUM(dt_payments_info.amount) * ' . $clientPer . ' as total_earnings'),
                DB::raw('COUNT(dt_payments_info.amount) as total_donors'),
            )
            ->groupBy('dt_payments_info.time_stamp')
            ->get()->toArray();

        foreach ($fundTotalEar as $key => $rev) {
            $fundTotalEar[$key]['date'] = date('Y-m-d', $rev['time_stamp']);
        }

        /* earning date wise data */
        $newEarningsData = array();
        foreach ($fundTotalEar as $val) {
            if (!isset($newEarningsData[$val['date']])) {
                $newEarningsData[$val['date']] = $val;
            } else {
                $newEarningsData[$val['date']]['total_earnings'] += $val['total_earnings'];
                $newEarningsData[$val['date']]['total_donors'] += $val['total_donors'];
            }
        }

        /* pageview and fundraiser view calculation */
        $reportData = CrowdfundReports::query();
        if($siteId) $reportData = $reportData->where('site_id', $siteId);
        $reportData = $reportData->whereBetween('date', [$startDate, $endDate])
            ->select('domains', 'date')->get()->toArray();
        foreach ($reportData as $key => $value) {
            $fundViews = 0;
            $pageViews = 0;
            $domData = json_decode($value['domains'], 1);
            foreach ($domData as $k => $vd) {
                $fundViews += (int) $vd['widgetviews'];
                $pageViews += (int) $vd['pageviews'];
            }
            // $countryData = json_decode($value['countries'], 1);
            // foreach ($countryData as $k => $vc) {
            //     $fundViews += (int) $vc['widgetviews'];
            //     $pageViews += (int) $vc['pageviews'];
            // }
            // $deviceData = json_decode($value['devices'], 1);
            // foreach ($deviceData as $k => $vd) {
            //     $fundViews += (int) $vd['widgetviews'];
            //     $pageViews += (int) $vd['pageviews'];
            // }
            // $pageData = json_decode($value['popular_pags'], 1);
            // foreach ($pageData as $k => $vp) {
            //     $fundViews += (int) $vp['widgetviews'];
            //     $pageViews += (int) $vp['pageviews'];
            // }

            $reportData[$key]['pageview'] = $pageViews;
            $reportData[$key]['fundraiser_views'] = $fundViews;
        }
        $newPageviewData = array();
        foreach ($reportData as $val) {
            if (!isset($newPageviewData[$val['date']])) {
                $newPageviewData[$val['date']]['pageview'] = $val['pageview'];
                $newPageviewData[$val['date']]['fundraiser_views'] = $val['fundraiser_views'];
                $newPageviewData[$val['date']]['date'] = $val['date'];
            } else {
                $newPageviewData[$val['date']]['pageview'] += $val['pageview'];
                $newPageviewData[$val['date']]['fundraiser_views'] += $val['fundraiser_views'];
            }
        }

        /* both calculation earning and pageview */
        foreach ($newPageviewData as $key => $val) {
            if (array_key_exists($val['date'], $newEarningsData)) {
                $crowdFund = $newEarningsData[$val['date']];
                $newPageviewData[$key]['time_stamp'] = $crowdFund['time_stamp'];
                $newPageviewData[$key]['total_earnings'] = $crowdFund['total_earnings'];
                $newPageviewData[$key]['total_donors'] = $crowdFund['total_donors'];
            } else {
                $newPageviewData[$key]['time_stamp'] = 0;
                $newPageviewData[$key]['total_earnings'] = 0;
                $newPageviewData[$key]['total_donors'] = 0;
            }
        }
        return array_values($newPageviewData);
    }

    public function exportCfWidgetTableQuery($startDate, $endDate, $clientPer, $siteId)
    {
        $newEarningsData = $this->getWidgetTableQuery($startDate, $endDate, $clientPer, $siteId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Earnings');
        $sheet->setCellValue('C1', 'Page Views');
        $sheet->setCellValue('D1', 'Fundraiser Views');

        $rows = 2;
        foreach ($newEarningsData as $sites) {
            $sheet->setCellValue('A' . $rows, $sites['date']);
            $sheet->setCellValue('B' . $rows, $sites['total_earnings']);
            $sheet->setCellValue('C' . $rows, $sites['pageview']);
            $sheet->setCellValue('D' . $rows, $sites['fundraiser_views']);
            $rows++;
        }

        $fileName = "cf_widget.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/" . $fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/' . $fileName;
        return response()->download($path)->deleteFileAfterSend();
    }

    public function getDbDomainData($startDate, $endDate, $siteId)
    {
        $cfDomainData = CrowdfundReports::query();
        if($siteId) $cfDomainData = $cfDomainData->where('site_id', $siteId);
        $cfDomainData = $cfDomainData->whereBetween('date', [$startDate, $endDate])
            ->select('domains')->get()->toArray();

        $domainData = [];
        foreach ($cfDomainData as $key => $value) {
            $domData = json_decode($value['domains'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($domainData[$k])) {
                    $domainData[$k]['domain_name'] = $v['domain_name'];
                    $domainData[$k]['pageviews'] = $v['pageviews'];
                    $domainData[$k]['fundraiser_views'] = $v['widgetviews'];
                } else {
                    $domainData[$k]['pageviews'] += $v['pageviews'];
                    $domainData[$k]['fundraiser_views'] += $v['widgetviews'];
                }
            }
        }
        return $domainData;
    }

    public function getCfDomainTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $resData = $this->getDbDomainData($startDate, $endDate, $siteId);
        $previousResData = $this->getDbDomainData($oldStartDate, $oldEndDate, $siteId);
        
        foreach ($resData as $key => $val) {
            if (array_key_exists($val['domain_name'], $previousResData)) {
                $domainPercentage = $previousResData[$val['domain_name']];
                $oldPageview = (float) $domainPercentage['pageviews'];
                $oldFundViews = (float) $domainPercentage['fundraiser_views'];
                $resData[$key]['pageview_per'] = $oldPageview ? ($val['pageviews'] - $oldPageview) * 100 / $oldPageview : 0;
                $resData[$key]['fundraiser_views_per'] = $oldFundViews ? ($val['fundraiser_views'] - $oldFundViews) * 100 / $oldFundViews : '0';
            } else {
                $resData[$key]['pageview_per'] = 0;
                $resData[$key]['fundraiser_views_per'] = 0;
            }
        }
        return array_values($resData);
    }

    public function getDbCountriesData($startDate, $endDate, $siteId)
    {
        $cfDomainData = CrowdfundReports::query();
        if($siteId) $cfDomainData = $cfDomainData->where('site_id', $siteId);
        $cfDomainData = $cfDomainData->whereBetween('date', [$startDate, $endDate])
            ->select('countries')->get()->toArray();

        $domainData = [];
        foreach ($cfDomainData as $key => $value) {
            $domData = json_decode($value['countries'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($domainData[$k])) {
                    $domainData[$k]['countries'] = $v['countries'];
                    $domainData[$k]['pageviews'] = $v['pageviews'];
                    $domainData[$k]['fundraiser_views'] = $v['widgetviews'];
                } else {
                    $domainData[$k]['pageviews'] += $v['pageviews'];
                    $domainData[$k]['fundraiser_views'] += $v['widgetviews'];
                }
            }
        }
        return $domainData;
    }

    public function getCfCountriesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $resData = $this->getDbCountriesData($startDate, $endDate, $siteId);
        $previousResData = $this->getDbCountriesData($oldStartDate, $oldEndDate, $siteId);
        
        foreach ($resData as $key => $val) {
            if (array_key_exists($val['countries'], $previousResData)) {
                $countryPercentage = $previousResData[$val['countries']];
                $oldPageview = (float) $countryPercentage['pageviews'];
                $oldFundViews = (float) $countryPercentage['fundraiser_views'];
                $resData[$key]['pageview_per'] = $oldPageview ? ($val['pageviews'] - $oldPageview) * 100 / $oldPageview : 0;
                $resData[$key]['fundraiser_views_per'] = $oldFundViews ? ($val['fundraiser_views'] - $oldFundViews) * 100 / $oldFundViews : '0';
            } else {
                $resData[$key]['pageview_per'] = 0;
                $resData[$key]['fundraiser_views_per'] = 0;
            }
        }
        return array_values($resData);
    }

    public function getDbDevicesData($startDate, $endDate, $siteId)
    {
        $cfDomainData = CrowdfundReports::query();
        if($siteId) $cfDomainData = $cfDomainData->where('site_id', $siteId);
        $cfDomainData = $cfDomainData->whereBetween('date', [$startDate, $endDate])
            ->select('devices')->get()->toArray();

        $domainData = [];
        foreach ($cfDomainData as $key => $value) {
            $domData = json_decode($value['devices'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($domainData[$k])) {
                    $domainData[$k]['device'] = $v['device'];
                    $domainData[$k]['pageviews'] = $v['pageviews'];
                    $domainData[$k]['fundraiser_views'] = $v['widgetviews'];
                } else {
                    $domainData[$k]['pageviews'] += $v['pageviews'];
                    $domainData[$k]['fundraiser_views'] += $v['widgetviews'];
                }
            }
        }
        return $domainData;
    }

    public function getCfDevicesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $resData = $this->getDbDevicesData($startDate, $endDate, $siteId);
        $previousResData = $this->getDbDevicesData($oldStartDate, $oldEndDate, $siteId);
        
        foreach ($resData as $key => $val) {
            if (array_key_exists($val['device'], $previousResData)) {
                $devicePercentage = $previousResData[$val['device']];
                $oldPageview = (float) $devicePercentage['pageviews'];
                $oldFundViews = (float) $devicePercentage['fundraiser_views'];
                $resData[$key]['pageview_per'] = $oldPageview ? ($val['pageviews'] - $oldPageview) * 100 / $oldPageview : 0;
                $resData[$key]['fundraiser_views_per'] = $oldFundViews ? ($val['fundraiser_views'] - $oldFundViews) * 100 / $oldFundViews : '0';
            } else {
                $resData[$key]['pageview_per'] = 0;
                $resData[$key]['fundraiser_views_per'] = 0;
            }
        }
        return array_values($resData);
    }

    public function getDbPagesData($startDate, $endDate, $siteId)
    {
        $cfDomainData = CrowdfundReports::query();
        if($siteId) $cfDomainData = $cfDomainData->where('site_id', $siteId);
        $cfDomainData = $cfDomainData->whereBetween('date', [$startDate, $endDate])
            ->select('popular_pags')->get()->toArray();

        $domainData = [];
        foreach ($cfDomainData as $key => $value) {
            $domData = json_decode($value['popular_pags'], 1);
            foreach ($domData as $k => $v) {
                if (!isset($domainData[$k])) {
                    $domainData[$k]['page'] = $v['page'];
                    $domainData[$k]['pageviews'] = $v['pageviews'];
                    $domainData[$k]['fundraiser_views'] = $v['widgetviews'];
                } else {
                    $domainData[$k]['pageviews'] += $v['pageviews'];
                    $domainData[$k]['fundraiser_views'] += $v['widgetviews'];
                }
            }
        }
        return $domainData;
    }

    public function getCfPagesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null)
    {
        $resData = $this->getDbPagesData($startDate, $endDate, $siteId);
        $previousResData = $this->getDbPagesData($oldStartDate, $oldEndDate, $siteId);
        
        foreach ($resData as $key => $val) {
            if (array_key_exists($val['page'], $previousResData)) {
                $pagesPercentage = $previousResData[$val['page']];
                $oldPageview = (float) $pagesPercentage['pageviews'];
                $oldFundViews = (float) $pagesPercentage['fundraiser_views'];
                $resData[$key]['pageview_per'] = $oldPageview ? ($val['pageviews'] - $oldPageview) * 100 / $oldPageview : 0;
                $resData[$key]['fundraiser_views_per'] = $oldFundViews ? ($val['fundraiser_views'] - $oldFundViews) * 100 / $oldFundViews : '0';
            } else {
                $resData[$key]['pageview_per'] = 0;
                $resData[$key]['fundraiser_views_per'] = 0;
            }
        }
        return array_values($resData);
    }

    public function exportCfPVsFundViewsTableDataQuery($startDate, $endDate, $tableType, $oldStartDate, $oldEndDate, $siteId)
    {
        if ($tableType == 'domains') {
            $subTableData = $this->getCfDomainTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        } else if ($tableType == 'countries') {
            $subTableData = $this->getCfCountriesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        } else if ($tableType == 'devices') {
            $subTableData = $this->getCfDevicesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        } else if ($tableType == 'pages') {
            $subTableData = $this->getCfPagesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId);
        } else {
            return response()->json(['message' => 'Table type data not found', 'status' => false], 403);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($tableType == 'domains') $sheet->setCellValue('A1', 'Domains');
        if ($tableType == 'countries') $sheet->setCellValue('A1', 'Country');
        if ($tableType == 'devices') $sheet->setCellValue('A1', 'Device');
        if ($tableType == 'pages') $sheet->setCellValue('A1', 'Page');
        $sheet->setCellValue('B1', 'Pageview');
        $sheet->setCellValue('C1', 'Pageview Percentage');
        $sheet->setCellValue('D1', 'Fundraiser views');
        $sheet->setCellValue('E1', 'Fundraiser views Percentage');

        $rows = 2;
        foreach ($subTableData as $data) {
            if ($tableType == 'domains') $sheet->setCellValue('A' . $rows, $data['domain_name']);
            if ($tableType == 'countries') $sheet->setCellValue('A' . $rows, $data['countries']);
            if ($tableType == 'devices') $sheet->setCellValue('A' . $rows, $data['device']);
            if ($tableType == 'pages') $sheet->setCellValue('A' . $rows, $data['page']);
            $sheet->setCellValue('B' . $rows, $data['pageviews']);
            $sheet->setCellValue('C' . $rows, $data['pageview_per']);
            $sheet->setCellValue('D' . $rows, $data['fundraiser_views']);
            $sheet->setCellValue('E' . $rows, $data['fundraiser_views_per']);
            $rows++;
        }

        $fileName = "fund-view-data.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/" . $fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/' . $fileName;
        return response()->download($path)->deleteFileAfterSend();
    }
}
