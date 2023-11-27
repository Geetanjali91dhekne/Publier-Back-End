<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdsSize;
use App\Models\DailyReport;
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

class AdManagerController extends Controller
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
            Dimension::AD_UNIT_ID,
        ]);
        
        $reportQuery->setColumns(
            [
                Column::AD_EXCHANGE_LINE_ITEM_LEVEL_REVENUE, // Ad Exchange Revenue
                Column::AD_EXCHANGE_LINE_ITEM_LEVEL_IMPRESSIONS, // Ad Exchange Impressions
                Column::AD_EXCHANGE_LINE_ITEM_LEVEL_CLICKS,
                Column::AD_EXCHANGE_TOTAL_REQUESTS, // Total Ad Requests
                Column::AD_EXCHANGE_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS,
                Column::AD_EXCHANGE_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS,
                Column::ADSENSE_LINE_ITEM_LEVEL_REVENUE, // AdSense Revenue
                Column::ADSENSE_LINE_ITEM_LEVEL_IMPRESSIONS, // AdSense Impressions
                Column::ADSENSE_LINE_ITEM_LEVEL_CLICKS,
                Column::ADSENSE_RESPONSES_SERVED,
                Column::ADSENSE_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS,
                Column::ADSENSE_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS,
                Column::AD_SERVER_ALL_REVENUE, // Ad Server CPM, CPC, CPD, and vCPM
                Column::AD_SERVER_IMPRESSIONS, // Ad server impressions
                Column::AD_SERVER_CLICKS,
                Column::TOTAL_AD_REQUESTS,
                Column::AD_SERVER_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS,
                Column::AD_SERVER_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS,
                Column::TOTAL_CODE_SERVED_COUNT,
                // Column::Yield_Group_estimated_revenue,
                // Column::Yield_Group_Impressions
            ]
        );

        // Set the ad unit view to hierarchical.
        $reportQuery->setAdUnitView(ReportQueryAdUnitView::HIERARCHICAL); // historical HIERARCHICAL FLAT TOP_LEVEL

        // Set the dynamic date range type or a custom start and end date.
        $reportQuery->setDateRangeType(DateRangeType::YESTERDAY);
        // $reportQuery->setDateRangeType(DateRangeType::CUSTOM_DATE);
        // $reportQuery->setStartDate(
        //     AdManagerDateTimes::fromDateTime(
        //         new DateTime(
        //             '2022-12-26',
        //             new DateTimeZone('America/New_York')
        //         )
        //     )
        //         ->getDate()
        // );
        // $reportQuery->setEndDate(
        //     AdManagerDateTimes::fromDateTime(
        //         new DateTime(
        //             '2022-12-27',
        //             new DateTimeZone('America/New_York')
        //         )
        //     )
        //         ->getDate()
        // );
        
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

        // self::add_revenue($filePath);
        // self::add_adsense_revenue($filePath);
        // self::add_adserver_revenue($filePath);
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

        $CDate = date("Y-m-d", strtotime('yesterday'));
        $count = 1;
        $post_data = array();
        while ($data = gzgets($handle, 4096)) {
            if ($count != 1) {
                $myArray = explode(',', $data);
                $dfpCodeData = $myArray[2];
                $dfpAdUnit = trim($dfpCodeData);

                if (in_array($dfpAdUnit, $dfpData)) {
                    $sizeId = $adsizearr[$dfpAdUnit];
                    $siteId = $adsizearr[$dfpAdUnit.'-1'];
                    $revenueVal = trim($myArray[4])/1000000;
                    $post_data['date'] = $CDate;
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
                    $post_data['elegible_impressions'] = trim($myArray[9]);
                    $post_data['code_served'] = trim($myArray[22]);
                    $post_data['unfilled_impressions'] = trim($myArray[7]) - trim($myArray[22]);

                    $checkReports = new DailyReport();
                    $checkReports =  $checkReports->where([
                            ['site_id', '=', $siteId],
                            ['size_id', '=', $sizeId],
                            ['network_id', '=', '16'],
                            ['date', '=', $CDate],
                            ['type', '=', 'ADX'],
                            // ['revenue', '=', number_format($revenueVal, 2)],
                        ])->get();

                    if($checkReports->isEmpty()) {
                        DailyReport::insert($post_data);
                    } else {
                        DailyReport::where([
                            ['site_id', '=', $siteId],
                            ['size_id', '=', $sizeId],
                            ['network_id', '=', '16'],
                            ['date', '=', $CDate],
                            ['type', '=', 'ADX'],
                            // ['revenue', '=', number_format($revenueVal, 2)]
                        ])->update($post_data);
                    }
                }
            }
            $count++;
        }
        gzclose($handle);
    }

    public static function add_adsense_revenue($filePath) {
        $handle = gzopen($filePath, 'r');
        $adsizearr = array();
        $dfpData = array();
        $adSizedata = AdsSize::select('ad_size_id', 'dfp_code', 'site_id')->get(); 
        foreach ($adSizedata as $askey => $asvalue) {
            $adsizearr[$asvalue['dfp_code']] = $asvalue['ad_size_id'];
            $adsizearr[$asvalue['dfp_code'].'-1'] = $asvalue['site_id'];
            $dfpData[] = $asvalue['dfp_code'];
        }

        $CDate = date("Y-m-d", strtotime('yesterday'));
        $count = 1;
        $post_data = array();
        while ($data = gzgets($handle, 4096)) {
            if ($count != 1) {
                $myArray = explode(',', $data);
                $dfpCodeData = $myArray[2];
                $dfpAdUnit = trim($dfpCodeData);

                if (in_array($dfpAdUnit, $dfpData)) {
                    $sizeId = $adsizearr[$dfpAdUnit];
                    $siteId = $adsizearr[$dfpAdUnit.'-1'];
                    $adsense_revenue = trim($myArray[10])/1000000;
                    $post_data['date'] = $CDate;
                    $post_data['size_id'] =  $sizeId;
                    $post_data['site_id'] =  $siteId;
                    $post_data['impressions'] = trim($myArray[11]);
                    $post_data['network_id'] = '16';
                    $post_data['revenue'] = $adsense_revenue;
                    $post_data['clicks'] = trim($myArray[12]);
                    $post_data['status'] = '1'; 
                    $post_data['manual_change'] = '0'; 
                    $post_data['type'] = 'AD_SENSE';
                    $post_data['total_request'] = trim($myArray[13]);
                    $post_data['measurable_impressions'] = trim($myArray[14]);
                    $post_data['elegible_impressions'] = trim($myArray[15]);
                    $post_data['code_served'] = trim($myArray[22]);
                    $post_data['unfilled_impressions'] = trim($myArray[13]) - trim($myArray[22]);

                    $checkReports = new DailyReport();
                    $checkReports =  $checkReports->where([
                            ['site_id', '=', $siteId],
                            ['size_id', '=', $sizeId],
                            ['network_id', '=', '16'],
                            ['date', '=', $CDate],
                            ['type', '=', 'AD_SENSE'],
                            // ['revenue', '=', $adsense_revenue],
                        ])->get();
                    
                    if($checkReports->isEmpty()) {
                        DailyReport::insert($post_data);
                    } else {
                        DailyReport::where([
                            ['site_id', '=', $siteId],
                            ['size_id', '=', $sizeId],
                            ['network_id', '=', '16'],
                            ['date', '=', $CDate],
                            ['type', '=', 'AD_SENSE'],
                            // ['revenue', '=', $adsense_revenue]
                        ])->update($post_data);
                    }
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

        $CDate = date("Y-m-d", strtotime('yesterday'));
        $count = 1;
        $post_data = array();
        while ($data = gzgets($handle, 4096)) {
            if ($count != 1) {
                $myArray = explode(',', $data);
                $dfpCodeData = $myArray[2];
                $dfpAdUnit = trim($dfpCodeData);

                if (in_array($dfpAdUnit, $dfpData)) {
                    $sizeId = $adsizearr[$dfpAdUnit];
                    $siteId = $adsizearr[$dfpAdUnit.'-1'];
                    $adserver_revenue = trim($myArray[16])/1000000;
                    $post_data['date'] = $CDate;
                    $post_data['size_id'] =  $sizeId;
                    $post_data['site_id'] =  $siteId;
                    $post_data['impressions'] = trim($myArray[17]);
                    $post_data['network_id'] = '16';
                    $post_data['revenue'] = $adserver_revenue;
                    $post_data['clicks'] = trim($myArray[18]);
                    $post_data['status'] = '1'; 
                    $post_data['manual_change'] = '0'; 
                    $post_data['type'] = 'AD_SERVER';
                    $post_data['total_request'] = trim($myArray[19]);
                    $post_data['measurable_impressions'] = trim($myArray[20]);
                    $post_data['elegible_impressions'] = trim($myArray[21]);
                    $post_data['code_served'] = trim($myArray[22]);
                    $post_data['unfilled_impressions'] = trim($myArray[19]) - trim($myArray[22]);

                    $checkReports = new DailyReport();
                    $checkReports =  $checkReports->where([
                            ['site_id', '=', $siteId],
                            ['size_id', '=', $sizeId],
                            ['network_id', '=', '16'],
                            ['date', '=', $CDate],
                            ['type', '=', 'AD_SERVER'],
                            // ['revenue', '=', $adserver_revenue],
                        ])->get();
                    
                    if($checkReports->isEmpty()) {
                        DailyReport::insert($post_data);
                    } else {
                        DailyReport::where([
                            ['site_id', '=', $siteId],
                            ['size_id', '=', $sizeId],
                            ['network_id', '=', '16'],
                            ['date', '=', $CDate],
                            ['type', '=', 'AD_SERVER'],
                            // ['revenue', '=', $adserver_revenue]
                        ])->update($post_data);
                    }
                }
            }
            $count++;
        }
        gzclose($handle);
    }
}
