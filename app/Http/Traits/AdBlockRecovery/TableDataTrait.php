<?php

namespace App\Http\Traits\AdBlockRecovery;

use App\Models\AdblockReports;
use App\Models\AdblockWidgetReports;
use App\Models\CrowdfundReports;
use App\Models\OldAdblockReports;
use App\Models\OldPageviewsDailyReports;
use App\Models\PageviewsDailyReports;
use App\Models\PaymentsInfo;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait TableDataTrait
{
    public function getAdblockDatewisePageview($startDate, $endDate, $siteId)
    {
        $adBlockData = PageviewsDailyReports::query();
        if($siteId) $adBlockData = $adBlockData->where('site_id', $siteId);
        $adBlockData = $adBlockData->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%d %b %y") as date'),
                DB::raw('ifnull(SUM(adblock_pageviews), 0) AS abpvs')
            )
            ->groupBy('date')
            ->get()->toArray();
        return $adBlockData;
    }

    public function getOldDbAdblockDatewisePageview($startDate, $endDate, $siteId)
    {
        $oldDbAdBlockData = OldPageviewsDailyReports::query();
        if($siteId) $oldDbAdBlockData = $oldDbAdBlockData->where('site_id', $siteId);
        $oldDbAdBlockData = $oldDbAdBlockData->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(date, "%d %b %y") as date'),
                DB::raw('ifnull(SUM(adblock_pageviews), 0) AS abpvs')
            )
            ->groupBy('date')
            ->get()->toArray();
        return $oldDbAdBlockData;
    }
    
    public function getAdBlockWidgetTableQuery($startDate, $endDate, $siteId = null, $widgetId = null)
    {
        if($widgetId) {
            $adBlockData = AdblockWidgetReports::query();
            $adBlockData = $adBlockData->where('site_id', $siteId)
                ->where('widget_id', $widgetId)
                ->whereBetween('date', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE_FORMAT(date, "%d %b %y") as date'),
                    DB::raw('ifnull(SUM(pageviews), 0) AS abpvs')
                )
                ->groupBy('date')
                ->get()->toArray();
            return $adBlockData;
        }
        $abPageviews = $this->getAdblockDatewisePageview($startDate, $endDate, $siteId, $widgetId);
        $oldDbAbPageviews = $this->getOldDbAdblockDatewisePageview($startDate, $endDate, $siteId, $widgetId);

        $bothAbPageviews = array_merge($abPageviews, $oldDbAbPageviews);
        $newAbPageviews = array();
        foreach ($bothAbPageviews as $val) {
            if (!isset($newAbPageviews[$val['date']]))
                $newAbPageviews[$val['date']] = $val;
            else
                $newAbPageviews[$val['date']]['abpvs'] += $val['abpvs'];
        }
        return array_values($newAbPageviews);
    }

    public function exportAdBlockWidgetTableQuery($startDate, $endDate, $siteId, $widgetId)
    {
        $abWidgetData = $this->getAdBlockWidgetTableQuery($startDate, $endDate, $siteId, $widgetId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Adblock PVs ');

        $rows = 2;
        foreach ($abWidgetData as $sites) {
            $sheet->setCellValue('A' . $rows, $sites['date']);
            $sheet->setCellValue('B' . $rows, $sites['abpvs']);
            $rows++;
        }

        $fileName = "ab_widget.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/" . $fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/' . $fileName;
        return response()->download($path)->deleteFileAfterSend();
    }

    public function getAdBlockBrowsersDataQuery($startDate, $endDate, $siteId, $widgetId)
    {
        /* current period data */
        $devicesPVs = AdblockReports::query();
        if ($siteId) $devicesPVs = $devicesPVs->where('site_id', $siteId);
        if ($widgetId) $devicesPVs = $devicesPVs->where('widget_id', $widgetId);
        $devicesPVs = $devicesPVs->whereBetween('date', [$startDate, $endDate])
            ->select('devices')
            ->get()->toArray();

        $resData = [];
        foreach ($devicesPVs as $key => $value) {
            $devicesData = json_decode($value['devices'], 1);
            foreach ($devicesData as $k => $v) {
                if (!isset($resData[$k])) {
                    $resData[$k]['browser'] = $v['device'];
                    $resData[$k]['count_browser'] = 1;
                } else {
                    $resData[$k]['count_browser'] += 1;
                }
            }
        }

        /* old db data */
        $oldDevicesPVs = OldAdblockReports::query();
        if ($siteId) $oldDevicesPVs = $oldDevicesPVs->where('site_id', $siteId);
        if ($widgetId) $oldDevicesPVs = $oldDevicesPVs->where('widget_id', $widgetId);
        $oldDevicesPVs = $oldDevicesPVs->whereBetween('date', [$startDate, $endDate])
            ->select('devices')
            ->get()->toArray();

        $oldDbDevice = [];
        foreach ($oldDevicesPVs as $key => $value) {
            $devicesData = json_decode($value['devices'], 1);
            foreach ($devicesData as $k => $v) {
                if (!isset($oldDbDevice[$k])) {
                    $oldDbDevice[$k]['browser'] = $v['device'];
                    $oldDbDevice[$k]['count_browser'] = 1;
                } else {
                    $oldDbDevice[$k]['count_browser'] += 1;
                }
            }
        }

        $bothArr = array_merge($resData, $oldDbDevice);
        $newResData = array();
        foreach ($bothArr as $val) {
            if (!isset($newResData[$val['browser']]))
                $newResData[$val['browser']] = $val;
            else {
                $newResData[$val['browser']]['count_browser'] += $val['count_browser'];
            }
        }

        return array_values($newResData);
    }

    public function getNewDbDomainData($startDate, $endDate, $siteId, $widgetId)
    {
        if($widgetId) {
	        $domainsPVs = AdblockWidgetReports::query();
        } else {
            $domainsPVs = AdblockReports::query();
        }
        if ($siteId) $domainsPVs = $domainsPVs->where('site_id', $siteId);
        if ($widgetId) $domainsPVs = $domainsPVs->where('widget_id', $widgetId);
        $domainsPVs = $domainsPVs->whereBetween('date', [$startDate, $endDate])
            ->select('domains')
            ->get()->toArray();

        $newDbDomains = [];
        foreach ($domainsPVs as $key => $value) {
            $devicesData = json_decode($value['domains'], 1);
            foreach ($devicesData as $k => $v) {
                if (!isset($newDbDomains[$k])) {
                    $newDbDomains[$k]['domain_name'] = $v['domain_name'];
                    $newDbDomains[$k]['ab_pageviews'] = (int) $v['pageviews'];
                } else {
                    $newDbDomains[$k]['ab_pageviews'] += (int) $v['pageviews'];
                }
            }
        }
        return $newDbDomains;
    }

    public function getOldDomainData($startDate, $endDate, $siteId, $widgetId)
    {
        if ($widgetId) return [];
        $domainsPVs = OldAdblockReports::query();
        if ($siteId) $domainsPVs = $domainsPVs->where('site_id', $siteId);
        $domainsPVs = $domainsPVs->whereBetween('date', [$startDate, $endDate])
            ->select('domains')
            ->get()->toArray();

        $oldDbDomains = [];
        foreach ($domainsPVs as $key => $value) {
            $devicesData = json_decode($value['domains'], 1);
            foreach ($devicesData as $k => $v) {
                if (!isset($oldDbDomains[$k])) {
                    $oldDbDomains[$k]['domain_name'] = $v['domain_name'];
                    $oldDbDomains[$k]['ab_pageviews'] = (int) $v['pageviews'];
                } else {
                    $oldDbDomains[$k]['ab_pageviews'] += (int) $v['pageviews'];
                }
            }
        }
        return $oldDbDomains;
    }

    public function getAdBlockDomainsTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null, $widgetId = null)
    {
        /* current period data */
        $newDbDomains = $this->getNewDbDomainData($startDate, $endDate, $siteId, $widgetId);
        $oldDbDomains = $this->getOldDomainData($startDate, $endDate, $siteId, $widgetId);

        $bothArr = array_merge($newDbDomains, $oldDbDomains);
        $resData = array();
        foreach ($bothArr as $val) {
            if (!isset($resData[$val['domain_name']]))
                $resData[$val['domain_name']] = $val;
            else {
                $resData[$val['domain_name']]['ab_pageviews'] += $val['ab_pageviews'];
            }
        }
        
        /* previous period data */
        $previousNewDbDomains = $this->getNewDbDomainData($oldStartDate, $oldEndDate, $siteId, $widgetId);
        $previousOldDbDomains = $this->getOldDomainData($oldStartDate, $oldEndDate, $siteId, $widgetId);

        $previousBothArr = array_merge($previousNewDbDomains, $previousOldDbDomains);
        $previousResData = array();
        foreach ($previousBothArr as $val) {
            if (!isset($previousResData[$val['domain_name']]))
                $previousResData[$val['domain_name']] = $val;
            else {
                $previousResData[$val['domain_name']]['ab_pageviews'] += $val['ab_pageviews'];
            }
        }

        /* percentage calculation */
        foreach ($resData as $key => $sites) {
            if (array_key_exists($sites['domain_name'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['domain_name']];
                $oldPVs = (float) $sitesPercentage['ab_pageviews'];
                $resData[$key]['ab_pageviews_percentage'] = $oldPVs ? ($sites['ab_pageviews'] - $oldPVs) * 100 / $oldPVs : 0;
            } else {
                $resData[$key]['ab_pageviews_percentage'] = 0;
            }
        }
        return array_values($resData);
    }

    public function getNewDbDeviceData($startDate, $endDate, $siteId, $widgetId)
    {
        if($widgetId) {
	        $devicesPVs = AdblockWidgetReports::query();
        } else {
            $devicesPVs = AdblockReports::query();
        }
        if ($siteId) $devicesPVs = $devicesPVs->where('site_id', $siteId);
        if ($widgetId) $devicesPVs = $devicesPVs->where('widget_id', $widgetId);
        $devicesPVs = $devicesPVs->whereBetween('date', [$startDate, $endDate])
            ->select('devices')
            ->get()->toArray();

        $newDbDevice = [];
        foreach ($devicesPVs as $key => $value) {
            $devicesData = json_decode($value['devices'], 1);
            foreach ($devicesData as $k => $v) {
                if (!isset($newDbDevice[$k])) {
                    $newDbDevice[$k]['device'] = $v['device'];
                    $newDbDevice[$k]['ab_pageviews'] = (int) $v['pageviews'];
                } else {
                    $newDbDevice[$k]['ab_pageviews'] += (int) $v['pageviews'];
                }
            }
        }
        return $newDbDevice;
    }

    public function getOldDeviceData($startDate, $endDate, $siteId, $widgetId)
    {
        if ($widgetId) return [];
        $devicesPVs = OldAdblockReports::query();
        if ($siteId) $devicesPVs = $devicesPVs->where('site_id', $siteId);
        $devicesPVs = $devicesPVs->whereBetween('date', [$startDate, $endDate])
            ->select('devices')
            ->get()->toArray();

        $oldDbDevice = [];
        foreach ($devicesPVs as $key => $value) {
            $devicesData = json_decode($value['devices'], 1);
            foreach ($devicesData as $k => $v) {
                if (!isset($oldDbDevice[$k])) {
                    $oldDbDevice[$k]['device'] = $v['device'];
                    $oldDbDevice[$k]['ab_pageviews'] = (int) $v['pageviews'];
                } else {
                    $oldDbDevice[$k]['ab_pageviews'] += (int) $v['pageviews'];
                }
            }
        }
        return $oldDbDevice;
    }

    public function getAdBlockDevicesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null, $widgetId = null)
    {
        /* current period data */
        $newDbDevices = $this->getNewDbDeviceData($startDate, $endDate, $siteId, $widgetId);
        $oldDbDevices = $this->getOldDeviceData($startDate, $endDate, $siteId, $widgetId);

        $bothArr = array_merge($newDbDevices, $oldDbDevices);
        $resData = array();
        foreach ($bothArr as $val) {
            if (!isset($resData[$val['device']]))
                $resData[$val['device']] = $val;
            else {
                $resData[$val['device']]['ab_pageviews'] += $val['ab_pageviews'];
            }
        }
        
        /* previous period data */
        $previousNewDbDevices = $this->getNewDbDeviceData($oldStartDate, $oldEndDate, $siteId, $widgetId);
        $previousOldDbDevices = $this->getOldDeviceData($oldStartDate, $oldEndDate, $siteId, $widgetId);

        $previousBothArr = array_merge($previousNewDbDevices, $previousOldDbDevices);
        $previousResData = array();
        foreach ($previousBothArr as $val) {
            if (!isset($previousResData[$val['device']]))
                $previousResData[$val['device']] = $val;
            else {
                $previousResData[$val['device']]['ab_pageviews'] += $val['ab_pageviews'];
            }
        }

        /* percentage calculation */
        foreach ($resData as $key => $sites) {
            if (array_key_exists($sites['device'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['device']];
                $oldPVs = (float) $sitesPercentage['ab_pageviews'];
                $resData[$key]['ab_pageviews_percentage'] = $oldPVs ? ($sites['ab_pageviews'] - $oldPVs) * 100 / $oldPVs : 0;
            } else {
                $resData[$key]['ab_pageviews_percentage'] = 0;
            }
        }
        return array_values($resData);
    }

    public function getNewDbCountriesData($startDate, $endDate, $siteId, $widgetId)
    {
        if($widgetId) {
	        $countryPVs = AdblockWidgetReports::query();
        } else {
            $countryPVs = AdblockReports::query();
        }
        if ($siteId) $countryPVs = $countryPVs->where('site_id', $siteId);
        if ($widgetId) $countryPVs = $countryPVs->where('widget_id', $widgetId);
        $countryPVs = $countryPVs->whereBetween('date', [$startDate, $endDate])
            ->select('countries')
            ->get()->toArray();

        $newDbCountry = [];
        foreach ($countryPVs as $key => $value) {
            $devicesData = json_decode($value['countries'], 1);
            foreach ($devicesData as $k => $v) {
                if (!isset($newDbCountry[$k])) {
                    $newDbCountry[$k]['country'] = $v['country'];
                    $newDbCountry[$k]['ab_pageviews'] = (int) $v['pageviews'];
                } else {
                    $newDbCountry[$k]['ab_pageviews'] += (int) $v['pageviews'];
                }
            }
        }
        return $newDbCountry;
    }

    public function getOldCountriesData($startDate, $endDate, $siteId, $widgetId)
    {
        if ($widgetId) return [];
        $countryPVs = OldAdblockReports::query();
        if ($siteId) $countryPVs = $countryPVs->where('site_id', $siteId);
        $countryPVs = $countryPVs->whereBetween('date', [$startDate, $endDate])
            ->select('countries')
            ->get()->toArray();

        $oldDbCountry = [];
        foreach ($countryPVs as $key => $value) {
            $devicesData = json_decode($value['countries'], 1);
            foreach ($devicesData as $k => $v) {
                if (!isset($oldDbCountry[$k])) {
                    $oldDbCountry[$k]['country'] = $v['country'];
                    $oldDbCountry[$k]['ab_pageviews'] = (int) $v['pageviews'];
                } else {
                    $oldDbCountry[$k]['ab_pageviews'] += (int) $v['pageviews'];
                }
            }
        }
        return $oldDbCountry;
    }

    public function getAdBlockCountriesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId = null, $widgetId = null)
    {
        /* current period data */
        $newDbCountry = $this->getNewDbCountriesData($startDate, $endDate, $siteId, $widgetId);
        $oldDbCountry = $this->getOldCountriesData($startDate, $endDate, $siteId, $widgetId);

        $bothArr = array_merge($newDbCountry, $oldDbCountry);
        $resData = array();
        foreach ($bothArr as $val) {
            if (!isset($resData[$val['country']]))
                $resData[$val['country']] = $val;
            else {
                $resData[$val['country']]['ab_pageviews'] += $val['ab_pageviews'];
            }
        }

        /* previous period data */
        $previousNewDbCountry = $this->getNewDbCountriesData($oldStartDate, $oldEndDate, $siteId, $widgetId);
        $previousOldDbCountry = $this->getOldCountriesData($oldStartDate, $oldEndDate, $siteId, $widgetId);

        $previousBothArr = array_merge($previousNewDbCountry, $previousOldDbCountry);
        $previousResData = array();
        foreach ($previousBothArr as $val) {
            if (!isset($previousResData[$val['country']]))
                $previousResData[$val['country']] = $val;
            else {
                $previousResData[$val['country']]['ab_pageviews'] += $val['ab_pageviews'];
            }
        }

        /* percentage calculation */
        foreach ($resData as $key => $sites) {
            if (array_key_exists($sites['country'], $previousResData)) {
                $sitesPercentage = $previousResData[$sites['country']];
                $oldPVs = (float) $sitesPercentage['ab_pageviews'];
                $resData[$key]['ab_pageviews_percentage'] = $oldPVs ? ($sites['ab_pageviews'] - $oldPVs) * 100 / $oldPVs : 0;
            } else {
                $resData[$key]['ab_pageviews_percentage'] = 0;
            }
        }
        return array_values($resData);
    }

    public function exportAbPVsTableDataQuery($startDate, $endDate, $tableType, $oldStartDate, $oldEndDate, $siteId = null, $widgetId = null)
    {
        if ($tableType == 'domains') {
            $subTableData = $this->getAdBlockDomainsTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId, $widgetId);
        } else if ($tableType == 'devices') {
            $subTableData = $this->getAdBlockDevicesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId, $widgetId);
        } else if ($tableType == 'countries') {
            $subTableData = $this->getAdBlockCountriesTableQuery($startDate, $endDate, $oldStartDate, $oldEndDate, $siteId, $widgetId);
        } else {
            return response()->json(['message' => 'Table type data not found', 'status' => false], 403);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if ($tableType == 'domains') $sheet->setCellValue('A1', 'Domains Name');
        if ($tableType == 'devices') $sheet->setCellValue('A1', 'Device Name');
        if ($tableType == 'countries') $sheet->setCellValue('A1', 'Country Name');
        $sheet->setCellValue('B1', 'Adblock PVs');
        $sheet->setCellValue('C1', 'Adblock PVs Percentage');

        $rows = 2;
        foreach ($subTableData as $data) {
            if ($tableType == 'domains') $sheet->setCellValue('A' . $rows, $data['domain_name']);
            if ($tableType == 'devices') $sheet->setCellValue('A' . $rows, $data['device']);
            if ($tableType == 'countries') $sheet->setCellValue('A' . $rows, $data['country']);
            $sheet->setCellValue('B' . $rows, $data['ab_pageviews']);
            $sheet->setCellValue('C' . $rows, $data['ab_pageviews_percentage']);
            $rows++;
        }

        $fileName = "ab-pvs-data.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/" . $fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/' . $fileName;
        return response()->download($path)->deleteFileAfterSend();;
    }
}
