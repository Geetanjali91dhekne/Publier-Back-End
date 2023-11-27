<?php

namespace App\Console\Commands;

use App\Http\Traits\SitesTrait;
use App\Models\SiteTempReports;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiteTempReportsCron extends Command
{
    use SitesTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitetempreports:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'All site temp reports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $last7Day = Carbon::parse($today)->subDays(7)->format('Y-m-d');
        $last30Day = Carbon::parse($today)->subDays(30)->format('Y-m-d');
        
        Log::info("Cron is working fine!");
        SiteTempReports::query()->truncate();
    
        $this->insertSiteData($yesterday, $yesterday, 'last1days');
        $this->insertSiteData($last7Day, $yesterday, 'last7days');
        $this->insertSiteData($last30Day, $yesterday, 'last30days');
    }

    public function insertSiteData($startDate, $endDate, $interval) {

        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '00:00:00');

        $days = $startDate->diffInDays($endDate);
        $oldStartDate = Carbon::parse($startDate)->subDays($days + 1)->format('Y-m-d');
        $oldEndDate = Carbon::parse($oldStartDate)->addDays($days)->format('Y-m-d');

        Log::info("old date");
        Log::info($oldStartDate);
        Log::info($oldEndDate);
        $clientPerNet = config('app.client_per_val_net') / 100;
        $clientPerGross = config('app.client_per_val_gross') / 100;

        /* Old db sites data */
        $oldDbSites = $this->getOldSites($searchSite = '', $startDate, $endDate, $clientPerNet, $clientPerGross, $userId = 0, $adServer = 'OFF');
        $oldDbSites = $oldDbSites->get()->toArray();

        /* New db sites data */
        $sitesData = $this->getNewSites($searchSite = '', $startDate, $endDate, $clientPerNet, $clientPerGross, $userId = 0, $adServer = 'OFF');
        $sitesData = $sitesData->get()->toArray();

        $oldDbSiteIds = array_column($oldDbSites, 'site_id');
        $newDbSiteIds = array_column($sitesData, 'site_id');

        /* old db site request */
        $oldSiteReq = $this->getGamReportsRequest($startDate, $endDate, $oldDbSiteIds)->get()->toArray();
        $oldSiteReq = array_replace_recursive(array_combine(array_column($oldSiteReq, "site_id"), $oldSiteReq));
        foreach ($oldDbSites as $key => $sites) {
            if (array_key_exists($sites->site_id, $oldSiteReq)) {
                $sitesReq = $oldSiteReq[$sites->site_id];
                $sites->total_request += (float) $sitesReq['total_request'];
            }
        }

        /* new db site req */
        $newSiteReq = $this->getNewGamReportsRequest($startDate, $endDate, $newDbSiteIds)->get()->toArray();
        $newSiteReq = array_replace_recursive(array_combine(array_column($newSiteReq, "site_id"), $newSiteReq));
        foreach ($sitesData as $sites) {
            if (array_key_exists($sites->site_id, $newSiteReq)) {
                $sitesReq = $newSiteReq[$sites->site_id];
                $sites->total_request += (float) $sitesReq['total_request'];
            }
        }

        /* Previous Period Sites Data */
        $oldPreviousSitesData = $this->getOldPreviousPeriodSites($oldStartDate, $oldEndDate, $oldDbSiteIds, $clientPerNet, $clientPerGross);
        $oldSitepreviousReq = $this->getGamReportsRequest($oldStartDate, $oldEndDate, $oldDbSiteIds)->get()->toArray();
        $oldSitepreviousReq = array_replace_recursive(array_combine(array_column($oldSitepreviousReq, "site_id"), $oldSitepreviousReq));
        foreach ($oldPreviousSitesData as $key => $sites) {
            if (array_key_exists($sites['site_id'], $oldSitepreviousReq)) {
                $sitePreviousReq = $oldSitepreviousReq[$sites['site_id']];
                $oldPreviousSitesData[$key]['total_request'] += (float) $sitePreviousReq['total_request'];
            }
        }

        $newPreviousSitesData = $this->getNewPreviousPeriodSites($oldStartDate, $oldEndDate, $newDbSiteIds, $clientPerNet, $clientPerGross);
        $newPreviousSiteReq = $this->getNewGamReportsRequest($oldStartDate, $oldEndDate, $newDbSiteIds)->get()->toArray();
        $newPreviousSiteReq = array_replace_recursive(array_combine(array_column($newPreviousSiteReq, "site_id"), $newPreviousSiteReq));
        foreach ($newPreviousSitesData as $key => $sites) {
            if (array_key_exists($sites['site_id'], $newPreviousSiteReq)) {
                $newSitePreviousReq = $newPreviousSiteReq[$sites['site_id']];
                $newPreviousSitesData[$key]['total_request'] += (float) $newSitePreviousReq['total_request'];
            }
        }
        $bothPreviosData = array_replace_recursive(
            array_combine(array_column($oldPreviousSitesData, "site_id"), $oldPreviousSitesData),
            array_combine(array_column($newPreviousSitesData, "site_id"), $newPreviousSitesData)
        );

        $bothCureArr = array_merge($sitesData, $oldDbSites);

        $allSiteTemp = $this->percentageCalculate($bothCureArr, $bothPreviosData);

        // Log::info(count($allSiteTemp));
        $post_data = array();
        foreach($allSiteTemp as $site) {
            $post_data['site_name'] = $site->site_name;
            $post_data['favourite_by_users'] = $site->favourite;
            $post_data['site_id'] = $site->site_id;
            $post_data['total_request'] = $site->total_request ? $site->total_request : 0;
            $post_data['total_request_percentage'] = $site->total_request_percentage;
            $post_data['total_impressions'] = $site->total_impressions;
            $post_data['impressions_percentage'] = $site->impressions_percentage;
            $post_data['net_total_revenue'] = $site->net_total_revenue;
            $post_data['net_revenue_percentage'] = $site->net_revenue_percentage;
            $post_data['gross_total_revenue'] = $site->gross_total_revenue;
            $post_data['gross_revenue_percentage'] = $site->gross_revenue_percentage;
            $post_data['net_total_cpms'] = $site->net_total_cpms ? $site->net_total_cpms : 0;
            $post_data['net_total_cpms_percentage'] = $site->net_total_cpms_percentage;
            $post_data['gross_total_cpms'] = $site->gross_total_cpms ? $site->gross_total_cpms : 0;
            $post_data['gross_total_cpms_percentage'] = $site->gross_total_cpms_percentage;
            $post_data['total_fillrate'] = $site->total_fillrate ? $site->total_fillrate : 0;
            $post_data['total_fillrate_percentage'] = $site->total_fillrate_percentage;
            $post_data['time_interval'] = $interval;
            SiteTempReports::insert($post_data);
        }
        Log::info($interval);
    }
}
