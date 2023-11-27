<?php

namespace App\Http\Traits\Onboarding;

use App\Models\ManageSellers;
use App\Models\Publisher;
use App\Models\Sites;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait OnboardingTrait
{
    public function filterQuery($request)
    {
        $searchSite = $request->input('search_site');
        $siteIds = $request->input('site_ids');
        $publisherIds = $request->input('publisher_ids');
        $status = $request->input('status');
        $version = $request->input('publisher_version');
        $manager = $request->input('account_manager');
        $liveProduct = $request->input('live_product');

        $siteData = Sites::query();
        $siteData = $siteData->where('dt_sites.site_name', 'like', "%" . $searchSite . "%");
        if($siteIds) $siteData->whereIn('site_id', $siteIds);
        if($publisherIds) $siteData->whereIn('dt_sites.publisher_id', $publisherIds);
        if($status) $siteData->whereIn('dt_sites.status', $status);
        if($version) $siteData->whereIn('dt_sites.prebid_version', $version);
        if($manager) $siteData->whereIn('dt_sites.account_manager_id', $manager);
        if($liveProduct) $siteData->where(function ($q) use ($liveProduct) {
            foreach ($liveProduct as $product) {
               $q->orWhere("dt_sites.publir_products", "like", "%" . $product . "%");
            }
        });
        return $siteData;
    }

    public function getAllSitesListQuery($userId, $request)
    {
        $siteData = $this->filterQuery($request);
        $siteData =  $siteData
            ->join('dt_publisher', 'dt_publisher.publisher_id', '=', 'dt_sites.publisher_id')
            ->with('accountManager')
            ->where('dt_sites.status', '!=','AR')
            ->select(
                'dt_sites.site_id',
                'dt_sites.site_name',
                'dt_sites.site_url',
                'dt_sites.prebid_version',
                'dt_sites.status',
                'dt_sites.publir_products',
                'dt_sites.account_manager_id',
                'dt_publisher.id as pub_main_id',
                'dt_publisher.publisher_id',
                'dt_publisher.full_name as publisher_name',
                'dt_publisher.business_name',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite'),
                DB::raw('DATE_FORMAT(dt_sites.updated_at, "%Y-%m-%d %h:%i %p") as date_updated'),
            )
            ->orderBy('date_updated', 'desc')
            ->get()->toArray();
        return $siteData;
    }


    public function getRecentListQuery($userId, $request)
    {
        $recentData = $this->filterQuery($request);
        $recentData = $recentData
            ->join('dt_publisher', 'dt_sites.publisher_id', '=', 'dt_publisher.publisher_id')
            ->with('accountManager')
            ->where('dt_sites.status', '!=','AR')
            ->select(
                'dt_sites.site_id',
                'dt_sites.site_name',
                'dt_sites.site_url',
                'dt_sites.prebid_version',
                'dt_sites.status',
                'dt_sites.publir_products',
                'dt_sites.account_manager_id',
                'dt_publisher.id as pub_main_id',
                'dt_publisher.publisher_id',
                'dt_publisher.full_name as publisher_name',
                'dt_publisher.business_name',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite'),
                DB::raw('DATE_FORMAT(dt_sites.created_at, "%Y-%m-%d %h:%i %p") as date_created'),
            )
            ->groupBy('dt_sites.site_id')
            ->orderBy('dt_sites.site_id', 'desc')->limit(12)->get()->toArray();

        return $recentData;
    }

    public function getFavouritesListQuery($userId, $request)
    {
        $favoritesData = $this->filterQuery($request);
        $favoritesData = $favoritesData
            ->join('dt_publisher', 'dt_sites.publisher_id', '=', 'dt_publisher.publisher_id')
            ->with('accountManager')
            ->where('dt_sites.status', '!=','AR')
            ->whereRaw('FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids)')
            ->select(
                'dt_sites.site_id',
                'dt_sites.site_name',
                'dt_sites.site_url',
                'dt_sites.prebid_version',
                'dt_sites.status',
                'dt_sites.publir_products',
                'dt_sites.account_manager_id',
                'dt_publisher.id as pub_main_id',
                'dt_publisher.publisher_id',
                'dt_publisher.full_name as publisher_name',
                'dt_publisher.business_name',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite')
            )
            ->groupBy('dt_sites.site_id')
            ->orderBy('dt_sites.site_id', 'desc')->limit(10)->get();

        return $favoritesData;
    }

    public function getArchiveListQuery($userId, $request)
    {
        $archiveData = $this->filterQuery($request);
        $archiveData = $archiveData
            ->join('dt_publisher', 'dt_sites.publisher_id', '=', 'dt_publisher.publisher_id')
            ->with('accountManager')
            ->where('dt_sites.status', 'AR')
            ->select(
                'dt_sites.site_id',
                'dt_sites.site_name',
                'dt_sites.site_url',
                'dt_sites.prebid_version',
                'dt_sites.status',
                'dt_sites.publir_products',
                'dt_sites.account_manager_id',
                'dt_publisher.id as pub_main_id',
                'dt_publisher.publisher_id',
                'dt_publisher.full_name as publisher_name',
                'dt_publisher.business_name',
                DB::raw('ifnull(FIND_IN_SET(' . $userId . ', dt_sites.favourite_by_user_ids), 0) as favourite'),
                DB::raw('DATE_FORMAT(dt_sites.updated_at, "%Y-%m-%d %h:%i %p") as date_updated'),
            )
            ->orderBy('date_updated', 'desc')
            ->groupBy('dt_sites.site_id')
            ->get();

        return $archiveData;
    }

    public function getSiteDetailQuery($site_id)
    {
        return Sites::where('site_id', $site_id)
            ->with(['sellerData', 'publisherDetails.publisherSites'])
            ->select([
                'site_id', 'status', 'old_site_id', 'site_name', 'site_url', 'publisher_id',
                'show_impressions_data', 'publir_products', 'integrated_wp_plugin', 'integrated_js_code',
                'react_site', 'prebid_version', 'prebid_timeout', 'prebid_failsafe', 'id5_id',
                'liveramp_id', 'sticky_mode_only', 'prebid_debug_mode', 'prebid_gdpr', 'gam_api_status',
                'disable_init_load', 'restricted_urls', 'amazon_pub_id', 'amazon_ad_server', 'amazon_hb',
                'taxonomy', 'pub_own_s3_bucket', 'aws_access_key', 'aws_secret_key', 's3_bucket_name',
                'cloudflare_auth_email', 'cloudflare_auth_key', 'email_reports_status', 'account_manager_id',
                'track_page_views', 'track_clicks', 'google_auto_ads',
                DB::raw('DATE_FORMAT(dt_sites.created_at, "%Y-%m-%d %h:%i %p") as joined_on'),
                DB::raw('DATE_FORMAT(dt_sites.updated_at, "%Y-%m-%d %h:%i %p") as went_live_on'),
            ])
            ->first();
    }

    public function storeSiteDetailQuery($request, $site_id)
    {
        $sitesData = array(); //create data for dt_sites table
        $sitesData['publisher_id'] = $request->get('publisher_id');
        $sitesData['old_site_id'] = $request->get('old_site_id');
        $sitesData['status'] = $request->get('status');
        $sitesData['site_name'] = $request->get('site_name');
        $sitesData['site_url'] = $request->get('site_url');
        $sitesData['show_impressions_data'] = $request->get('show_impressions_data');
        $sitesData['publir_products'] = $request->get('publir_products');
        $sitesData['integrated_wp_plugin'] = $request->get('integrated_wp_plugin');
        $sitesData['integrated_js_code'] = $request->get('integrated_js_code');
        $sitesData['react_site'] = $request->get('react_site');
        $sitesData['prebid_version'] = $request->get('prebid_version');
        $sitesData['prebid_timeout'] = $request->get('prebid_timeout');
        $sitesData['prebid_failsafe'] = $request->get('prebid_failsafe');
        $sitesData['id5_id'] = $request->get('id5_id');
        $sitesData['liveramp_id'] = $request->get('liveramp_id');
        $sitesData['sticky_mode_only'] = $request->get('sticky_mode_only');
        $sitesData['prebid_debug_mode'] = $request->get('prebid_debug_mode');
        $sitesData['prebid_gdpr'] = $request->get('prebid_gdpr');
        $sitesData['gam_api_status'] = $request->get('gam_api_status');
        $sitesData['disable_init_load'] = $request->get('disable_init_load');
        $sitesData['restricted_urls'] = $request->get('restricted_urls');
        $sitesData['amazon_pub_id'] = $request->get('amazon_pub_id') ? $request->get('amazon_pub_id') : 0 ;
        $sitesData['amazon_ad_server'] = $request->get('amazon_ad_server') ? $request->get('amazon_ad_server') : 0 ;
        $sitesData['amazon_hb'] = $request->get('amazon_hb');
        $sitesData['taxonomy'] = $request->get('taxonomy');
        $sitesData['pub_own_s3_bucket'] = $request->get('pub_own_s3_bucket');
        $sitesData['aws_access_key'] = $request->get('aws_access_key');
        $sitesData['aws_secret_key'] = $request->get('aws_secret_key');
        $sitesData['s3_bucket_name'] = $request->get('s3_bucket_name');
        $sitesData['cloudflare_auth_email'] = $request->get('cloudflare_auth_email');
        $sitesData['cloudflare_auth_key'] = $request->get('cloudflare_auth_key');
        $sitesData['email_reports_status'] = $request->get('email_reports_status');
        $sitesData['account_manager_id'] = $request->get('account_manager_id');
        $sitesData['track_page_views'] = $request->get('track_page_views');
        $sitesData['track_clicks'] = $request->get('track_clicks');
        $sitesData['google_auto_ads'] = $request->get('google_auto_ads');
        
        //Manage Sellers JSON
        $sellerData = array();
        $sellerData['seller_name'] = $request->get('seller_name');
        $sellerData['seller_domain'] = $request->get('seller_domain');
        $sellerData['seller_type'] = $request->get('seller_type');
        $sellerData['seller_json_status'] = $request->get('seller_json_status');
        
        if($site_id) {
            $siteUpdateRes = Sites::where('site_id', $site_id)->update($sitesData);
        
            $sellerData['seller_id'] = $request->get('seller_id');
            ManageSellers::where('site_id', $site_id)->update($sellerData);
            return $siteUpdateRes;

        } else {
            $siteResData = Sites::create($sitesData);

            $sellerData['site_id'] = $siteResData['site_id'];
            $sellerData['seller_id'] = '9792'.$siteResData['site_id'];
            ManageSellers::create($sellerData);
            return $siteResData;
        }
    }

    public function createPublisherQuery($request)
    {
        $publisherData = array();
        $publisherData['password'] = hash('sha256', $request->get('password'));
        $publisherData['publisher_id'] = 'PR' . time() . mt_rand(100, 999);
        $publisherData['full_name'] = $request->get('full_name');
        $publisherData['business_name'] = $request->get('business_name');
        $publisherData['email'] = $request->get('email');
        $publisherData['same_mcm_email'] = $request->get('same_mcm_email');
        $publisherData['mcm_email'] = $request->get('mcm_email');
        $publisherData['password'] = $request->get('password');
        $publisherData['access_type'] = $request->get('access_type');
        $publisherData['show_network_level_data'] = $request->get('show_network_level_data');
        $publisherData['parent_gam_id'] = time() . mt_rand(100, 999);
        $publisherData['gam_api_name'] = $request->get('gam_api_name');
        $publisherData['gam_api_email'] = $request->get('gam_api_email');
        $publisherData['gam_api_passcode'] = $request->get('gam_api_passcode');
        $publisherData['gam_api_status'] = $request->get('gam_api_status');
        $publisherData['status'] = $request->get('status');
        $publisherData['created_at'] = Carbon::now();
        $publisherRes = Publisher::create($publisherData);
        return $publisherRes;
    }
}
