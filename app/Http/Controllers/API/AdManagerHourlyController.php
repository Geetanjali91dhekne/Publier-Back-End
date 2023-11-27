<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdsSize;
use App\Models\HourlyReport;
use App\Models\PageviewsHourlyReports;
use App\Models\Sites;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\AdManagerSessionBuilder;
use Google\AdsApi\AdManager\Util\v202211\AdManagerDateTimes;
use Google\AdsApi\AdManager\Util\v202211\ReportDownloader;
use Google\AdsApi\AdManager\v202211\Column;
use Google\AdsApi\AdManager\v202211\DateRangeType;
use Google\AdsApi\AdManager\v202211\Dimension;
use Google\AdsApi\AdManager\v202211\ExportFormat;
use Google\AdsApi\AdManager\v202211\ReportJob;
use Google\AdsApi\AdManager\v202211\ReportQuery;
use Google\AdsApi\AdManager\v202211\ReportQueryAdUnitView;
use Google\AdsApi\AdManager\v202211\ServiceFactory;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Illuminate\Support\Facades\Log;

class AdManagerHourlyController extends Controller
{
    /* This function use only local testing not for server */
    public static function index(Request $request)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile(base_path('adsapi_php.ini'))
            ->build();

        // Construct an API session configured from a properties file and the OAuth2
        // credentials above.
        $session = (new AdManagerSessionBuilder())
            ->fromFile(base_path('adsapi_php.ini'))
            ->withOAuth2Credential($oAuth2Credential)
            ->build();

        // Get a service.
        $serviceFactory = new ServiceFactory();
        $networkService = $serviceFactory->createNetworkService($session);

        // Make a request
        $network = $networkService->getCurrentNetwork();
        printf(
            "Network with code %d and display name '%s' was found.\n",
            $network->getNetworkCode(),
            $network->getDisplayName()
        );

        self::get_report(
            new ServiceFactory(),
            $session,
        );
    }

    public static function get_report(ServiceFactory $serviceFactory, AdManagerSession $session) {
        
        $reportService = $serviceFactory->createReportService($session);

        // Create report query.
        $reportQuery = new ReportQuery();
        $reportQuery->setDimensions([
            Dimension::HOUR,
            Dimension::DATE,
            Dimension::AD_UNIT_ID,
        ]);
        
        $reportQuery->setColumns(
            [
                Column::AD_EXCHANGE_LINE_ITEM_LEVEL_REVENUE, // Ad Exchange Revenue
                Column::AD_EXCHANGE_LINE_ITEM_LEVEL_IMPRESSIONS, // Ad Exchange Impressions
                Column::AD_EXCHANGE_LINE_ITEM_LEVEL_CLICKS,
                Column::AD_EXCHANGE_TOTAL_REQUESTS, // Total Ad Requests
                Column::AD_EXCHANGE_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS,
                // Column::AD_EXCHANGE_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS,
                // Column::AD_SERVER_ALL_REVENUE, // Ad Server CPM, CPC, CPD, and vCPM
                Column::TOTAL_LINE_ITEM_LEVEL_CPM_AND_CPC_REVENUE,
                Column::AD_SERVER_IMPRESSIONS, // Ad server impressions
                Column::AD_SERVER_CLICKS,
                // Column::TOTAL_AD_REQUESTS,
                // Column::AD_SERVER_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS,
                // Column::AD_SERVER_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS,
                Column::TOTAL_CODE_SERVED_COUNT,
                Column::TOTAL_INVENTORY_LEVEL_UNFILLED_IMPRESSIONS,
            ]
        );

        // Set the ad unit view to hierarchical.
        $reportQuery->setAdUnitView(ReportQueryAdUnitView::HIERARCHICAL); // historical HIERARCHICAL FLAT TOP_LEVEL

        // Set the dynamic date range type or a custom start and end date.
        $currHour = date('H');
        if($currHour == 00)
            $reportQuery->setDateRangeType(DateRangeType::YESTERDAY);
        else
            $reportQuery->setDateRangeType(DateRangeType::TODAY);
        
        // Create report job.
        $reportJob = new ReportJob();
        $reportJob->setReportQuery($reportQuery);
        
        // Run report job.
        $reportJob = $reportService->runReportJob($reportJob);

        $reportDownloader = new ReportDownloader(
            $reportService,
            $reportJob->getId()
        );
        if ($reportDownloader->waitForReportToFinish()) {
            // Write to system temp directory by default.
            $filePath = sprintf(
                '%s.csv.gz',
                tempnam('/opt/lampp/htdocs/publir/storage/ad-reports/', 'custom-field-report-'),
            );
            printf("Downloading report to %s ...%s", $filePath, PHP_EOL);
            // Download the report.
            $reportDownloader->downloadReport(
                ExportFormat::CSV_DUMP,
                $filePath
            );
            print "done.\n";
        } else {
            print "Report failed.\n";
        }

        self::add_revenue($filePath);
        self::add_adserver_revenue($filePath);
        self::ad_pageview_hourly_report();

        $previousday = now()->subDay(2);
        HourlyReport::where('created_at', '<=', $previousday)->delete();
        PageviewsHourlyReports::where('created_time', '<=', $previousday)->delete();
        print "Done to create report";
    }

    public static function add_revenue($filePath) {
        $handle = gzopen($filePath, 'r');
        $adsizearr = array();
        $dfpData = array();
        $adSizedata = AdsSize::select('ad_size_id', 'dfp_code', 'site_id')->get(); 
        foreach ($adSizedata as $askey => $asvalue) {
            $adsizearr[$asvalue['dfp_code']] = $asvalue['ad_size_id'];
            $adsizearr[$asvalue['dfp_code'].'-1'] = $asvalue['site_id'];
            $dfpData[] = $asvalue['dfp_code'];
        }

        $count = 1;
        $post_data = array();
        while ($data = gzgets($handle, 4096)) {
            if ($count != 1) {
                $myArray = explode(',', $data);
                $dfpCodeData = $myArray[3];
                $dfpAdUnit = trim($dfpCodeData);
                $hour = $myArray[0] + 1 .':00:00';
                $CDate = $myArray[1];
                if (in_array($dfpAdUnit, $dfpData)) {
                    $sizeId = $adsizearr[$dfpAdUnit];
                    $siteId = $adsizearr[$dfpAdUnit.'-1'];
                    $revenueVal = trim($myArray[4])/1000000;
                    $post_data['date'] = $CDate;
                    $post_data['hour'] = $hour;
                    $post_data['size_id'] =  $sizeId;
                    $post_data['site_id'] =  $siteId;
                    $post_data['impressions'] = trim($myArray[5]);
                    $post_data['network_id'] = '16';
                    $post_data['revenue'] = number_format($revenueVal, 2);
                    $post_data['clicks'] = trim($myArray[6]); 
                    $post_data['status'] = '1'; 
                    $post_data['manual_change'] = '0'; 
                    $post_data['type'] = 'ADX';
                    $post_data['total_request'] = trim($myArray[7]);
                    $post_data['measurable_impressions'] = trim($myArray[8]);
                    $post_data['elegible_impressions'] = '0';
                    $post_data['code_served'] = trim($myArray[12]);
                    $post_data['unfilled_impressions'] = trim($myArray[7]) - trim($myArray[12]);
                    $post_data['created_at'] = Carbon::now();

                    $checkReports = new HourlyReport();
                    $checkReports =  $checkReports->where([
                            ['site_id', '=', $siteId],
                            ['size_id', '=', $sizeId],
                            ['network_id', '=', '16'],
                            ['date', '=', $CDate],
                            ['type', '=', 'ADX'],
                            ['hour', '=', $hour],
                        ])->get();

                    if($checkReports->isEmpty()) {
                        HourlyReport::insert($post_data);
                    }
                    // else {
                    //     HourlyReport::where([
                    //         ['site_id', '=', $siteId],
                    //         ['size_id', '=', $sizeId],
                    //         ['network_id', '=', '16'],
                    //         ['date', '=', $CDate],
                    //         ['type', '=', 'ADX'],
                    //         ['hour', '=', $hour]
                    //     ])->update($post_data);
                    // }
                }
            }
            $count++;
        }
        gzclose($handle);
    }

    public static function add_adserver_revenue($filePath) {
        $handle = gzopen($filePath, 'r');
        $adsizearr = array();
        $dfpData = array();
        $adSizedata = AdsSize::select('ad_size_id', 'dfp_code', 'site_id')->get(); 
        foreach ($adSizedata as $askey => $asvalue) {
            $adsizearr[$asvalue['dfp_code']] = $asvalue['ad_size_id'];
            $adsizearr[$asvalue['dfp_code'].'-1'] = $asvalue['site_id'];
            $dfpData[] = $asvalue['dfp_code'];
        }

        $count = 1;
        $post_data = array();
        while ($data = gzgets($handle, 4096)) {
            if ($count != 1) {
                $myArray = explode(',', $data);
                $dfpCodeData = $myArray[3];
                $dfpAdUnit = trim($dfpCodeData);
                $hour = $myArray[0] + 1 . ':00:00';
                $CDate = $myArray[1];
                if (in_array($dfpAdUnit, $dfpData)) {
                    $sizeId = $adsizearr[$dfpAdUnit];
                    $siteId = $adsizearr[$dfpAdUnit.'-1'];
                    $adserver_revenue = trim($myArray[9])/1000000;
                    $post_data['date'] = $CDate;
                    $post_data['hour'] = $hour;
                    $post_data['size_id'] =  $sizeId;
                    $post_data['site_id'] =  $siteId;
                    $post_data['impressions'] = trim($myArray[10]);
                    $post_data['network_id'] = '16';
                    $post_data['revenue'] = $adserver_revenue;
                    $post_data['clicks'] = trim($myArray[11]);
                    $post_data['status'] = '1'; 
                    $post_data['manual_change'] = '0'; 
                    $post_data['type'] = 'AD_SERVER';
                    $post_data['total_request'] = trim($myArray[12]) + trim($myArray[13]);
                    $post_data['measurable_impressions'] = '0';
                    $post_data['elegible_impressions'] = '0';
                    $post_data['code_served'] = trim($myArray[12]);
                    $post_data['unfilled_impressions'] = trim($myArray[13]);
                    $post_data['created_at'] = Carbon::now();

                    $checkReports = new HourlyReport();
                    $checkReports =  $checkReports->where([
                            ['site_id', '=', $siteId],
                            ['size_id', '=', $sizeId],
                            ['network_id', '=', '16'],
                            ['date', '=', $CDate],
                            ['type', '=', 'AD_SERVER'],
                            ['hour', '=', $hour],
                        ])->get();
                    
                    if($checkReports->isEmpty()) {
                        HourlyReport::insert($post_data);
                    }
                    // else {
                    //     HourlyReport::where([
                    //         ['site_id', '=', $siteId],
                    //         ['size_id', '=', $sizeId],
                    //         ['network_id', '=', '16'],
                    //         ['date', '=', $CDate],
                    //         ['type', '=', 'AD_SERVER'],
                    //         ['hour', '=', $hour],
                    //     ])->update($post_data);
                    // }
                }
            }
            $count++;
        }
        gzclose($handle);
    }

    public static function ad_pageview_hourly_report(){

        $siteIds = Sites::where('status', 'Y')->select('site_id')->get();
        foreach ($siteIds as $key => $value) {
            $site_id = $value['site_id'];
            date_default_timezone_set('EST');

            // $hour = date('H', time() - 3600);
            $CDate = date('Y-m-d');
            $currHour = date('H', time() - 7200);
            if(date('H') <= $currHour) $CDate = date('Y-m-d', strtotime("-1 days"));
            // if ($hour == 00) {
            //     $CDate = date('Y-m-d', strtotime("-1 days"));
            //     $currHour = '24';
            // }

            $beginOfDay= $CDate . '-' . $currHour;
            // echo 'date - ' . $CDate . ' - hour - ' . $currHour . ' - begin of day - ' . $beginOfDay;
            $dataPrebid = self::getDataFromApi('Pageviews', 'domain_hourly', $beginOfDay, $beginOfDay, 'day', $site_id);
            $pageViews = 0;
            if(!empty($dataPrebid)) {
                foreach ($dataPrebid as $key => $value) {
                  $pageViews = $pageViews + $value['pageviewsSum']['$numberInt'];
                }
            }

            $post_data = array();
            $post_data['date'] = $CDate;
            $post_data['hour'] = $currHour + 1 . ':00:00';
            $post_data['site_id'] =  $site_id;
            $post_data['pageviews'] = $pageViews;
            $post_data['created_time'] = Carbon::now();
            PageviewsHourlyReports::insert($post_data);
        }
    }

    public static function getDataFromApi($d_name,$c_name,$s_time,$e_time,$type,$site_id) {
        $url = 'https://webhooks.mongodb-stitch.com/api/client/v2.0/app/publiranalyticsapi-uyatl/service/httpService/incoming_webhook/webhook0?d_name='.$d_name.'&c_name='.$c_name.'&s_time='.$s_time.'&e_time='.$e_time.'&d_type='.$type.'&s_id='.$site_id.'';

    	$curlSession = curl_init();
    	curl_setopt($curlSession, CURLOPT_URL, $url);
    	curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
    	curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
    	return json_decode(curl_exec($curlSession),true);
    	curl_close($curlSession);
    }

}
