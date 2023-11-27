<?php

use App\Http\Controllers\API\AdBlockRecovery\AdBlockRecoveryController;
use App\Http\Controllers\API\AdManagerController;
use App\Http\Controllers\API\AdManagerHourlyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckStatus;

use App\Http\Controllers\API\LoginController;

use App\Http\Controllers\API\AdOptimization\AdOptimizationController;
use App\Http\Controllers\API\AdOptimization\RealTimeSiteDetailsController;
use App\Http\Controllers\API\AdOptimization\SiteDashboardController;
use App\Http\Controllers\API\AdOptimization\SiteDetailsController;
use App\Http\Controllers\API\CrowdFunding\CrowdFundingController;
use App\Http\Controllers\API\Subscriptions\SubscriptionsController;
use App\Http\Controllers\API\QuickShop\QuickShopController;
use App\Http\Controllers\API\Setup\AdBlockRecoverySetupController;
use App\Http\Controllers\API\PrebidUploadDataController;
use App\Http\Controllers\API\NetworksUploadDataController;
use App\Http\Controllers\API\Onboarding\FilterDataController;
use App\Http\Controllers\API\Onboarding\OnboardingController;
use App\Http\Controllers\API\Onboarding\GeneralOnboardingController;
use App\Http\Controllers\API\Onboarding\AdOptimizationOnboardingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('login', [LoginController::class, 'Login']);

Route::middleware(['auth.token'])->group(function () {

    Route::prefix('optimization')->group(function () {

        /* SuperAdmin dashboard */
        Route::post('topcard', [AdOptimizationController::class, 'ad_top_card']);
        Route::post('revenue/topcard', [AdOptimizationController::class, 'ad_revenue_cmp_top_card']);
        Route::post('favourites', [AdOptimizationController::class, 'ad_favourite_data']);
        Route::post('recents', [AdOptimizationController::class, 'ad_recent_data']);
        Route::post('revenue/graph', [AdOptimizationController::class, 'ad_revenue_graph_data']);
        Route::post('imps/graph', [AdOptimizationController::class, 'ad_imps_graph']);
        Route::post('request/graph', [AdOptimizationController::class, 'ad_request_graph_data']);
        Route::post('cpms/graph', [AdOptimizationController::class, 'ad_cpms_graph']);
        Route::post('rate/fill/graph', [AdOptimizationController::class, 'ad_fill_rate_graph_data']);
        Route::post('demand/channel', [AdOptimizationController::class, 'ad_demand_channel_data']);
        Route::post('trend', [AdOptimizationController::class, 'ad_top_trends_data']);

        /* Site dashboard */
        Route::post('all/sites', [SiteDashboardController::class, 'ad_sites_data']);
        Route::post('favourites/sites', [SiteDashboardController::class, 'ad_favourite_sites_data']);
        Route::post('recents/sites', [SiteDashboardController::class, 'ad_recent_sites_data']);
        Route::post('favourite/unfavourite', [SiteDashboardController::class, 'ad_favourite_unfavourite_data']);
        Route::get('search/site/{site_name?}', [SiteDashboardController::class, 'ad_search_data']);
        Route::post('export/table', [SiteDashboardController::class, 'ad_export_site_table']);

        /* Site details */
        Route::post('revenue/graph/{site_id}', [SiteDetailsController::class, 'ad_revenue_graph_data_by_site']);
        Route::post('request/graph/{site_id}', [SiteDetailsController::class, 'ad_requests_graph_data_by_site']);
        Route::post('cpm/graph/{site_id}', [SiteDetailsController::class, 'ad_cpm_graph_data_by_site']);
        Route::post('imps/graph/{site_id}', [SiteDetailsController::class, 'ad_impression_graph_data_by_site']);
        Route::post('fill/comparison/graph/{site_id}', [SiteDetailsController::class, 'ad_fill_comparison_graph_data']);
        Route::post('date/table/{site_id}', [SiteDetailsController::class, 'ad_date_table_data_by_site']);
        Route::post('networks/table/{site_id}', [SiteDetailsController::class, 'ad_networks_table_data_by_site']);
        Route::post('sizes/table/{site_id}', [SiteDetailsController::class, 'ad_sizes_table_data_by_site']);
        Route::post('demamd/channel/stats/{site_id}', [SiteDetailsController::class, 'ad_demand_channel_stats_data_by_site']);
        Route::post('size/stats/{site_id}', [SiteDetailsController::class, 'ad_size_stats_data_by_site']);
        Route::post('export/table/{site_id}', [SiteDetailsController::class, 'ad_export_site_details']);

        /* realtime site details */
        Route::post('realtime/pageview/imp/graph/{site_id}', [RealTimeSiteDetailsController::class, 'ad_realtime_pageview_impression_graph_by_site']);
        Route::post('realtime/rev/req/graph/{site_id}', [RealTimeSiteDetailsController::class, 'ad_realtime_revenue_request_graph_by_site']);
        Route::post('realtime/cpm/graph/{site_id}', [RealTimeSiteDetailsController::class, 'ad_realtime_cpm_graph_by_site']);
        Route::post('realtime/rpm/graph/{site_id}', [RealTimeSiteDetailsController::class, 'ad_realtime_rpm_graph_by_site']);

        Route::post('realtime/sizes/table/{site_id}', [RealTimeSiteDetailsController::class, 'ad_top_sizes_table_data_by_site']);
        Route::post('realtime/networks/table/{site_id}', [RealTimeSiteDetailsController::class, 'ad_top_networks_table_data_by_site']);
        Route::post('realtime/popularpages/table/{site_id}', [RealTimeSiteDetailsController::class, 'ad_top_popularpages_table_data_by_site']);

        // Route::get('google-ads', [AdManagerController::class, 'index']); /* Only for testing GAM script in local */
        Route::get('google/ads/hourly', [AdManagerHourlyController::class, 'index']); /* Only for testing GAM script in local */
        Route::get('google/ads/hourly/pageview', [AdManagerHourlyController::class, 'ad_pageview_hourly_report']); /* Only for testing GAM script in local */

        //Demand Sites Data Corresponding to Networks
        Route::post('demand/site', [AdOptimizationController::class, 'ad_demand_site_data']);
    });

    Route::prefix('subscriptions')->group(function () {

        /* Subscriptions dashboard */
        Route::post('topcard', [SubscriptionsController::class, 'sub_top_card']);
        Route::post('countries/graph', [SubscriptionsController::class, 'sub_countries_graph']);
        Route::post('devices/graph', [SubscriptionsController::class, 'sub_devices_graph']);
        Route::post('reason/unsub', [SubscriptionsController::class, 'reason_for_unsubscribing']);
        Route::post('domain/table', [SubscriptionsController::class, 'sub_domain_table']);
        Route::post('countries/table', [SubscriptionsController::class, 'sub_countries_table']);
        Route::post('devices/table', [SubscriptionsController::class, 'sub_devices_table']);
        Route::post('pages/table', [SubscriptionsController::class, 'sub_pages_table']);
        Route::post('subscribers/table', [SubscriptionsController::class, 'sub_subscribers_table']);
        Route::post('subscriber/log/table', [SubscriptionsController::class, 'sub_subscriber_log_table']);
        Route::post('revenue/graph', [SubscriptionsController::class, 'sub_revenue_graph_data']);
        Route::post('active/graph', [SubscriptionsController::class, 'sub_active_subscription_graph_data']);
        Route::post('new/graph', [SubscriptionsController::class, 'sub_new_subscription_graph_data']);
        Route::post('unsubcribes/graph', [SubscriptionsController::class, 'sub_unsubcribes_graph_data']);
        Route::post('rpm/graph', [SubscriptionsController::class, 'sub_rpm_graph_data']);
        Route::post('domain/stats', [SubscriptionsController::class, 'sub_domain_stats_data']);
        Route::post('country/stats', [SubscriptionsController::class, 'sub_country_stats_data']);

        Route::post('pageview/sub/export/table', [SubscriptionsController::class, 'pageview_and_subscriptions_export_table']);
        Route::post('widget1/table', [SubscriptionsController::class, 'sub_widget1_table']);
        Route::post('widget/export/table', [SubscriptionsController::class, 'sub_widget_export_table']);
        Route::get('subscribers/search', [SubscriptionsController::class, 'sub_search_data']);
    });

    Route::prefix('crowdfunds')->group(function () {

        /* Crowdfunds dashboard */
        Route::post('topcard', [CrowdFundingController::class, 'cf_top_card']);
        Route::post('earning/country/graph', [CrowdFundingController::class, 'cf_earning_country_graph']);
        Route::post('donors/country/graph', [CrowdFundingController::class, 'cf_donors_country_graph']);
        Route::post('earning/devices/graph', [CrowdFundingController::class, 'cf_earning_devices_graph']);
        Route::post('donors/devices/graph', [CrowdFundingController::class, 'cf_donors_devices_graph']);
        Route::post('widget/table', [CrowdFundingController::class, 'cf_widget_table']);
        Route::post('widget/export/table', [CrowdFundingController::class, 'cf_export_widget_table']);
        Route::post('eranings/graph', [CrowdFundingController::class, 'crow_total_eranings_graph_data']);
        Route::post('donors/graph', [CrowdFundingController::class, 'crow_total_donors_graph_data']);
        Route::post('fundraiser/graph', [CrowdFundingController::class, 'crow_fundraiser_views_graph_data']);
        Route::post('average/donation/graph', [CrowdFundingController::class, 'crow_average_donation_graph_data']);
        Route::post('fund/ecpm/graph', [CrowdFundingController::class, 'crow_fund_ecpm_graph_data']);
        Route::post('domain/table', [CrowdFundingController::class, 'cf_domain_table']);
        Route::post('countries/table', [CrowdFundingController::class, 'cf_countries_table']);
        Route::post('devices/table', [CrowdFundingController::class, 'cf_devices_table']);
        Route::post('pages/table', [CrowdFundingController::class, 'cf_pages_table']);
        Route::post('fundviews/export/table', [CrowdFundingController::class, 'cf_pageview_fundraiser_export_table']);
    });

    Route::prefix('adblock')->group(function () {

        /* Crowdfunds dashboard */
        Route::get('new/sites', [AdBlockRecoveryController::class, 'get_new_sites_list']);
        Route::post('topcard', [AdBlockRecoveryController::class, 'ab_recovery_top_card']);
        Route::post('widget/table', [AdBlockRecoveryController::class, 'ab_recovery_widget_table']);
        Route::post('widget/export/table', [AdBlockRecoveryController::class, 'ab_recovery_export_widget_table']);
        Route::post('browser/ratio', [AdBlockRecoveryController::class, 'ab_recovery_browsers_data']);
        Route::post('device/table', [AdBlockRecoveryController::class, 'ab_recovery_devices_table']);
        Route::post('countries/table', [AdBlockRecoveryController::class, 'ab_recovery_countries_table']);
        Route::post('domain/table', [AdBlockRecoveryController::class, 'ab_recovery_domains_table']);
        Route::post('pvs/export/table', [AdBlockRecoveryController::class, 'ab_pageview_export_table']);
        Route::post('pageviews/graph', [AdBlockRecoveryController::class, 'adblock_pvs_graph_data']);
        Route::post('users/graph', [AdBlockRecoveryController::class, 'adblock_users_graph_data']);
    });

    Route::prefix('quickshop')->group(function () {

        /* QuickShop dashboard */
        Route::post('topcard', [QuickShopController::class, 'quick_top_card']);
        Route::post('topitems/table', [QuickShopController::class, 'quick_top_items_table']);
        Route::post('topitems/export/table', [QuickShopController::class, 'quick_top_items_export_table']);
        Route::post('country/graph', [QuickShopController::class, 'quick_country_graph']);
        Route::post('eranings/graph', [QuickShopController::class, 'quick_total_eranings_graph_data']);
        Route::post('item/graph', [QuickShopController::class, 'quick_items_sold_graph_data']);
        Route::post('purchase/graph', [QuickShopController::class, 'quick_purchase_value_graph_data']);
        Route::post('product/graph', [QuickShopController::class, 'quick_product_pvs_graph_data']);
        Route::post('converstion/graph', [QuickShopController::class, 'quick_converstion_ratio_graph_data']);
    });

    Route::prefix('setup')->group(function () {
        Route::get('adblock/{site_id}', [AdBlockRecoverySetupController::class, 'get_ad_block_all_preset']);
        Route::get('adblock/preset/{widget_id}', [AdBlockRecoverySetupController::class, 'get_ad_block_preset']);
        Route::post('adblock/create', [AdBlockRecoverySetupController::class, 'create_ad_block']);
        Route::post('adblock/edit/{widget_id}', [AdBlockRecoverySetupController::class, 'edit_ad_block']);
        Route::post('adblock/update/status', [AdBlockRecoverySetupController::class, 'update_ad_block_preset_status']);
        Route::post('adblock/compare', [AdBlockRecoverySetupController::class, 'compare_ad_block_preset']);
        Route::get('adblock/script/{widget_id}', [AdBlockRecoverySetupController::class, 'ad_block_script']);
        Route::get('delete/{widget_id}', [AdBlockRecoverySetupController::class, 'delete_preset']);
        // Route::get('event/log', [AdBlockRecoverySetupController::class, 'test_data']); /* Only for testing cron script in local */
        // Route::get('pageview/log', [AdBlockRecoverySetupController::class, 'getAdblockPageviewData']); /* Only for testing cron script in local */
    });

    Route::prefix('onboarding')->group(function () {
        /* Adoptimization onboarding */
        Route::get('adoptimization/siteslist', [FilterDataController::class, 'getSitesList']);
        Route::get('adoptimization/productlist', [FilterDataController::class, 'getLiveProductList']);
        Route::get('adoptimization/publisherlist', [FilterDataController::class, 'getPublisherList']);
        Route::get('adoptimization/prebidlist', [FilterDataController::class, 'getPrebidVersionList']);
        Route::get('adoptimization/accountlist', [FilterDataController::class, 'getAccountManagerList']);

        Route::post('adoptimization/allsites', [OnboardingController::class, 'getAllSitesList']);
        Route::post('adoptimization/recent', [OnboardingController::class, 'getRecentSitesList']);
        Route::post('adoptimization/favourites', [OnboardingController::class, 'getFavouritesSitesList']);
        Route::post('adoptimization/archivesites', [OnboardingController::class, 'getArchiveSitesList']);

        Route::get('adoptimization/site/{site_id}', [OnboardingController::class, 'getSiteDetail']);
        Route::get('adoptimization/publisher/{id}', [OnboardingController::class, 'getPublisherDetail']);
        Route::put('adoptimization/site/{site_id}', [OnboardingController::class, 'editSiteDetail']);
        Route::put('adoptimization/publisher/{id}', [OnboardingController::class, 'editPublisherDetail']);
        Route::put('adoptimization/update/status', [OnboardingController::class, 'updateSiteStatus']);
        Route::post('adoptimization/site/create', [OnboardingController::class, 'createSites']);
        Route::post('adoptimization/publisher/create', [OnboardingController::class, 'createPublisher']);
        Route::post('adoptimization/assignmanager', [OnboardingController::class, 'assignAccountManager']);

        /*Genral onboarding */
        Route::get('general/{siteId}', [GeneralOnboardingController::class, 'getGeneralDataBySite']);
        Route::post('general/mockup', [GeneralOnboardingController::class, 'createGeneralMockUp']);
        Route::post('general/mockup/mail', [GeneralOnboardingController::class, 'sendGeneralMockupMail']);
        Route::post('general/billing', [GeneralOnboardingController::class, 'createGeneralBilling']);
        Route::post('general/billing/mail', [GeneralOnboardingController::class, 'sendGeneralBillingMail']);
        Route::post('general/agreement', [GeneralOnboardingController::class, 'createGeneralAgreement']);
        Route::post('general/mockup/{id}', [GeneralOnboardingController::class, 'editGeneralMockUp']);
        Route::post('general/billing/{id}', [GeneralOnboardingController::class, 'editGeneralBilling']);
        Route::post('general/agreement/{id}', [GeneralOnboardingController::class, 'editGeneralAgreement']);
        Route::post('general/vetting', [GeneralOnboardingController::class, 'createGeneralVettingGuidelines']);
        Route::put('general/vetting/{id}', [GeneralOnboardingController::class, 'editGeneralVettingGuidelines']);
        Route::post('general/custom/task', [GeneralOnboardingController::class, 'createGeneralCustomTask']);
        Route::post('general/custom/task/{id}', [GeneralOnboardingController::class, 'editGeneralCustomTask']);

        /* ad Optimization */
        Route::post('adoptimization/mcminvite', [AdOptimizationOnboardingController::class, 'createAdOpsMcmInvite']);
        Route::put('adoptimization/mcminvite/{id}', [AdOptimizationOnboardingController::class, 'editAdOpsMcmInvite']);
        Route::post('adoptimization/adstxt', [AdOptimizationOnboardingController::class, 'createAdOpsAdsTxt']);
        Route::put('adoptimization/adstxt/{id}', [AdOptimizationOnboardingController::class, 'editAdOpsAdsTxt']);
        Route::get('adoptimization/adstxt/mail/{id}', [AdOptimizationOnboardingController::class, 'sendAdOpsAdsTxtMail']);
        Route::post('adoptimization/prebid', [AdOptimizationOnboardingController::class, 'createAdOpsPrebidJs']);
        Route::put('adoptimization/prebid/{id}', [AdOptimizationOnboardingController::class, 'editAdOpsPrebidJs']);
        Route::post('adoptimization/adtags', [AdOptimizationOnboardingController::class, 'createAdOpsAdTags']);
        Route::put('adoptimization/adtags/{id}', [AdOptimizationOnboardingController::class, 'editAdOpsAdTags']);
    });

    Route::prefix('prebid')->group(function () {
        Route::get('network', [PrebidUploadDataController::class, 'network_api']);
        Route::post('faileddata', [PrebidUploadDataController::class, 'get_failed_rows']);
        Route::post('insertfaileddata', [PrebidUploadDataController::class, 'insert_failed_rows']);
        Route::post('networksitessizes', [PrebidUploadDataController::class, 'network_sites_sizes']);
        Route::post('insertfaileddatacsv', [PrebidUploadDataController::class, 'insert_failed_rows_csv']);
        Route::post('insertnewdatarow', [PrebidUploadDataController::class, 'insert_new_data_row']);
        // Route::post('deletemultiplerecords', [PrebidUploadDataController::class, 'delete_multiple_records']);
        Route::post('deleteallrecords', [PrebidUploadDataController::class, 'delete_all_rows']);
        Route::get('runapi', [PrebidUploadDataController::class, 'run_api']);
        Route::get('sampleapi', [PrebidUploadDataController::class, 'sample_api']);
    });

    Route::prefix('networks')->group(function () {
        Route::get('network', [NetworksUploadDataController::class, 'get_networks']);
        Route::get('allsites', [NetworksUploadDataController::class, 'get_all_sites']);
        Route::get('allsizes', [NetworksUploadDataController::class, 'get_all_sizes']);
        Route::post('networksitesandsizes', [NetworksUploadDataController::class, 'network_sites_sizes']);
        Route::post('addnetworksite', [NetworksUploadDataController::class, 'add_network_site']);
        Route::post('addnetworksize', [NetworksUploadDataController::class, 'add_network_size']);
        // Route::post('networksizes',[PrebidUploadDataController::class,'network_sizes']);
        Route::post('editnetworksite', [NetworksUploadDataController::class, 'edit_network_site']);
        Route::post('editnetworksize', [NetworksUploadDataController::class, 'edit_network_size']);
        Route::post('deletenetworksite', [NetworksUploadDataController::class, 'delete_network_site']);
        Route::post('deletenetworksize', [NetworksUploadDataController::class, 'delete_network_size']);
    });
});
