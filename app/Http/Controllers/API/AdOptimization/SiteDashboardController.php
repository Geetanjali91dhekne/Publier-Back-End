<?php

namespace App\Http\Controllers\API\AdOptimization;

use App\Http\Controllers\Controller;
use App\Http\Traits\SitesTrait;
use App\Http\Traits\AdServerTrait;
use App\Models\Sites;
use App\Models\OldSites;
use App\Models\WriteAccessOldSites;
use App\Models\WriteAccessSites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use \Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SiteDashboardController extends Controller
{
    use SitesTrait;
    use AdServerTrait;
    /*
    **getAllSitesData
    **
    */
    public function ad_sites_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'time_interval' => 'required',
            'ad_server' => 'required',
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
        $oldDbSites = $this->getOldSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);

        /* New db sites data */
        $sitesData = $this->getNewSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
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

    /*
    **get favourite Sites Data
    **
    */
    public function ad_favourite_sites_data(Request $request)
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

        $userId = $request->get('userId');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $searchSite = $request->input('search_site');
        $adServer = $request->input('ad_server');

        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPerNet = config('app.client_per_val_net') / 100;
        $clientPerGross = config('app.client_per_val_gross') / 100;

        /* Old db sites data */
        $oldDbFavouriteSites = $this->getOldSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
        $oldDbFavouriteSites = $oldDbFavouriteSites->whereRaw('FIND_IN_SET(' . $userId . ', im_sites.favourite_by_user_ids)');

        /* New db sites data */
        $favouriteSitesData = $this->getNewSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
        $favouriteSitesData = $favouriteSitesData->whereRaw('FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids)')
            ->union($oldDbFavouriteSites)
            ->orderBy('gross_total_revenue', 'desc')->get();

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $oldDbSiteIds = [];
        $newDbSiteIds = [];
        foreach ($favouriteSitesData as $sites) {
            if ($sites->site_id >= 1000) array_push($newDbSiteIds, $sites->site_id);
            else array_push($oldDbSiteIds, $sites->site_id);
        }

        $favouriteSitesData = $this->getSiteRequestData($startDate, $endDate, $favouriteSitesData, $oldDbSiteIds, $newDbSiteIds);

        /* Previous Period Sites Data */
        $bothPreviosData = $this->getPreviousPeriodSiteWithRequest($oldStartDate, $oldEndDate, $newDbSiteIds, $oldDbSiteIds, $clientPerNet, $clientPerGross);

        $calculatedFavouriteData = $this->percentageCalculate($favouriteSitesData, $bothPreviosData);
        return response()->json(['message' => 'favourite sites data get successfully', 'status' => true, 'favouriteSitesData' => $calculatedFavouriteData]);
    }

    /*
    **get favourite Sites Data
    **
    */
    public function ad_recent_sites_data(Request $request)
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

        $userId = $request->get('userId');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $compare = $request->input('compare');
        $searchSite = $request->input('search_site');
        $adServer = $request->input('ad_server');

        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $clientPerNet = config('app.client_per_val_net') / 100;
        $clientPerGross = config('app.client_per_val_gross') / 100;

        /* New db sites data */
        $recentSitesData = $this->getNewSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
        $recentSitesData = $recentSitesData->orderBy('dt_sites.site_id', 'desc')
            ->orderBy('gross_total_revenue', 'desc')->limit(12)->get();

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');
        if ($compare) {
            $oldStartDate = Carbon::parse($request->input('compare_start_date'));
            $oldEndDate = Carbon::parse($request->input('compare_end_date'));
        }

        $newDbSiteIds = $recentSitesData->pluck('site_id');
        $recentSitesData = $this->getSiteRequestData($startDate, $endDate, $recentSitesData, $oldDbSiteIds = [], $newDbSiteIds);

        /* Previous Period Sites Data */
        $bothPreviosData = $this->getPreviousPeriodSiteWithRequest($oldStartDate, $oldEndDate, $newDbSiteIds, $oldDbSiteIds = [], $clientPerNet, $clientPerGross);
        $calculatedRecentData = $this->percentageCalculate($recentSitesData, $bothPreviosData);
        return response()->json(['message' => 'recent sites data get successfully', 'status' => true, 'recentSitesData' => $calculatedRecentData]);
    }
    /*
    **UpdatefavouriteData
    **
    */
    public function ad_favourite_unfavourite_data(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'site_id' => 'required',
            'favourite_flag' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $userId = $request->get('userId');

        $siteId = $request->input('site_id');
        $favouriteFlag   = $request->input('favourite_flag');

        if ($siteId <= 1000) {
            $sites = WriteAccessOldSites::where('id', $siteId);
        } else {
            $sites = WriteAccessSites::where('site_id', $siteId);
        }

        $siteDetails = $sites->first();
        $favouriteUserIds = $siteDetails->favourite_by_user_ids;
        $favouriteUserIds = $favouriteUserIds ? explode(',', $favouriteUserIds) : [];
        if (in_array($userId, $favouriteUserIds)) {
            $pos = array_search($userId, $favouriteUserIds);
            if ($favouriteFlag == 0) unset($favouriteUserIds[$pos]);
        } else {
            if ($favouriteFlag == 1) array_push($favouriteUserIds, $userId);
        }
        $newFavouriteUserIds = implode(",", $favouriteUserIds);
        $sites = $sites->update(['favourite_by_user_ids' => $newFavouriteUserIds]);
        $message = $favouriteFlag == 1 ? 'Site Added to Favourite' : 'Remove to Favourite site';

        return response()->json(['message' => $message, 'status' => true]);
    }

    /*
    *getSearch data based on site_id & site_name
    **
    */

    public function ad_search_data(Request $request, $site_name = null)
    {
        $userId = $request->get('userId');
        $adServer = $request->input('ad_server');
        $newAdServerIds = $this->newNoAdServerSiteIds();
        $oldAdServerIds = $this->oldNoAdServerSiteIds();

        $sitesData = Sites::query();
        if ($adServer == 'ON') $sitesData = $sitesData->whereIn('dt_sites.site_id', $newAdServerIds);
        $sitesData = $sitesData
            ->where("site_name", "like", "%" . $site_name . "%")
            ->where('status', 'Y')
            ->select(
                'site_id',
                'site_name',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', favourite_by_user_ids), 0) as favourite'),
                'no_adserver as gam'
            )
            ->get()
            ->map(function ($article) {
                if($article->gam == 'Y') $article->gam = str_replace('Y', 1, $article->gam);
                else $article->gam = str_replace('N', 0, $article->gam); 
                return $article;
            })->toArray();
        
        $oldsitesData = OldSites::query();
        if ($adServer == 'ON') $oldsitesData = $oldsitesData->whereIn('im_sites.id', $oldAdServerIds);
        $oldsitesData = $oldsitesData
            ->where("site_name", "like", "%" . $site_name . "%")
            ->where('status', 1)
            ->select(
                'id as site_id',
                'site_name',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', favourite_by_user_ids), 0) as favourite'),
                'no_adserver as gam'
            )
            ->get()->toArray();

        $bothArr = array_merge($sitesData, $oldsitesData);
        return response()->json(['status' => true, 'message' => 'Site name get successfully', 'sitesData' => $bothArr]);
    }

    /*
    **Export table data
    **
    */
    public function ad_export_site_table(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'revenue'  => 'required',
            'compare' => 'required',
            'compare_start_date' => 'required_if:compare,==,true',
            'compare_end_date' => 'required_if:compare,==,true',
            'table_type' => 'required',
            'time_interval' => 'required',
            'ad_server' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }
        $userId = $request->get('userId');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $revenue = $request->input('revenue');
        $compare = $request->input('compare');
        $searchSite = $request->input('search_site');
        $table_type = $request->input('table_type');
        $timeInterval = $request->input('time_interval');
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

        $clientPerNet = config('app.client_per_val_net') / 100;
        $clientPerGross = config('app.client_per_val_gross') / 100;

        if ($table_type == 'all') {
            if ($timeInterval != 'customDates') {
                $calculatePercentage = $this->getAllSiteTempReportsQuery($timeInterval, $searchSite, $userId, $adServer);
            } else {
                /* Old db sites data */
                $oldDbSites = $this->getOldSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
                /* New db sites data */
                $sitesData = $this->getNewSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
                $sitesData = $sitesData->union($oldDbSites)->orderBy('gross_total_revenue', 'desc')->get();

                $oldDbSiteIds = [];
                $newDbSiteIds = [];
                foreach ($sitesData as $sites) {
                    if ($sites->site_id >= 1000) array_push($newDbSiteIds, $sites->site_id);
                    else array_push($oldDbSiteIds, $sites->site_id);
                }

                $sitesData = $this->getSiteRequestData($startDate, $endDate, $sitesData, $oldDbSiteIds, $newDbSiteIds);

                /* Previous Period Sites Data */
                $bothPreviosData = $this->getPreviousPeriodSiteWithRequest($oldStartDate, $oldEndDate, $newDbSiteIds, $oldDbSiteIds, $clientPerNet, $clientPerGross);

                $calculatePercentage = $this->percentageCalculate($sitesData, $bothPreviosData);
            }
        } else if ($table_type == 'favorites') {
            /* Old db sites data */
            $oldDbFavouriteSites = $this->getOldSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
            $oldDbFavouriteSites = $oldDbFavouriteSites->whereRaw('FIND_IN_SET(' . $userId . ', im_sites.favourite_by_user_ids)');

            /* New db sites data */
            $sitesData = $this->getNewSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
            $sitesData = $sitesData->whereRaw('FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids)')
                ->union($oldDbFavouriteSites)
                ->orderBy('gross_total_revenue', 'desc')->get();


            $oldDbSiteIds = [];
            $newDbSiteIds = [];
            foreach ($sitesData as $sites) {
                if ($sites->site_id >= 1000) array_push($newDbSiteIds, $sites->site_id);
                else array_push($oldDbSiteIds, $sites->site_id);
            }

            $sitesData = $this->getSiteRequestData($startDate, $endDate, $sitesData, $oldDbSiteIds, $newDbSiteIds);

            /* Previous Period Sites Data */
            $bothPreviosData = $this->getPreviousPeriodSiteWithRequest($oldStartDate, $oldEndDate, $newDbSiteIds, $oldDbSiteIds, $clientPerNet, $clientPerGross);

            $calculatePercentage = $this->percentageCalculate($sitesData, $bothPreviosData);
        } else if ($table_type == 'recent') {
            /* New db sites data */
            $sitesData = $this->getNewSites($searchSite, $startDate, $endDate, $clientPerNet, $clientPerGross, $userId, $adServer);
            $sitesData = $sitesData->orderBy('dt_sites.created_at', 'desc')
                ->orderBy('gross_total_revenue', 'desc')->limit(12)->get();

            $newDbSiteIds = $sitesData->pluck('site_id');
            $sitesData = $this->getSiteRequestData($startDate, $endDate, $sitesData, $oldDbSiteIds = [], $newDbSiteIds);

            /* Previous Period Sites Data */
            $bothPreviosData = $this->getPreviousPeriodSiteWithRequest($oldStartDate, $oldEndDate, $newDbSiteIds, $oldDbSiteIds = [], $clientPerNet, $clientPerGross);
            $calculatePercentage = $this->percentageCalculate($sitesData, $bothPreviosData);
        } else {
            return response()->json(['message' => 'table data not found', 'status' => false], 403);
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Site Name');
        $sheet->setCellValue('B1', 'Ad Requests');
        $sheet->setCellValue('C1', 'Ad Requests Percentage');
        $sheet->setCellValue('D1', 'Monetized Impressions');
        $sheet->setCellValue('E1', 'Monetized Impressions Percentage');
        $sheet->setCellValue('F1', 'Revenue');
        $sheet->setCellValue('G1', 'Revenue Percentage');
        $sheet->setCellValue('H1', 'CPM');
        $sheet->setCellValue('I1', 'CPM Percentage');
        $sheet->setCellValue('J1', 'Fill Rate');
        $sheet->setCellValue('K1', 'Fill Rate Percentage');

        $rows = 2;
        foreach ($calculatePercentage as $sites) {
            $sheet->setCellValue('A' . $rows, $sites->site_name);
            $sheet->setCellValue('B' . $rows, $sites->total_request);
            $sheet->setCellValue('C' . $rows, $sites->total_request_percentage);
            $sheet->setCellValue('D' . $rows, $sites->total_impressions);
            $sheet->setCellValue('E' . $rows, $sites->impressions_percentage);
            if ($revenue == 'gross') {
                $sheet->setCellValue('F' . $rows, $sites->gross_total_revenue);
                $sheet->setCellValue('G' . $rows, $sites->gross_revenue_percentage);
                $sheet->setCellValue('H' . $rows, $sites->gross_total_cpms);
                $sheet->setCellValue('I' . $rows, $sites->gross_total_cpms_percentage);
            } else if ($revenue == 'net') {
                $sheet->setCellValue('F' . $rows, $sites->net_total_revenue);
                $sheet->setCellValue('G' . $rows, $sites->net_revenue_percentage);
                $sheet->setCellValue('H' . $rows, $sites->net_total_cpms);
                $sheet->setCellValue('I' . $rows, $sites->net_total_cpms_percentage);
            }
            $sheet->setCellValue('J' . $rows, $sites->total_fillrate);
            $sheet->setCellValue('K' . $rows, $sites->total_fillrate_percentage);
            $rows++;
        }

        $fileName = "sites.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save("/opt/lampp/htdocs/publir/storage/ad-reports/" . $fileName);
        header("Content-Type: application/vnd.ms-excel");
        $path = '/opt/lampp/htdocs/publir/storage/ad-reports/' . $fileName;
        return response()->download($path)->deleteFileAfterSend();
    }
}
