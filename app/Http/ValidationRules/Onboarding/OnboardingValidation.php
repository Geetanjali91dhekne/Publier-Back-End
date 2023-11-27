<?php

namespace App\Http\ValidationRules\Onboarding;

class OnboardingValidation
{
    /* create and edit publisher validation */
    public static function publisherRules($id)
    {
        return [
            'full_name' => 'required', // Y or N
            'business_name' => $id ? 'required|unique:dt_publisher,business_name,' . $id : 'required|unique:dt_publisher',
            'email' => $id ? 'required|unique:dt_publisher,email,' . $id : 'required|unique:dt_publisher',
            'same_mcm_email' => 'required', // Y or N
            'mcm_email' => 'required',
            'password' => 'required',
            'access_type' => 'required', // 'Dashboard'/ 'Setup' / 'All'
            'show_network_level_data' => 'required', // Y or N
            'parent_gam_id' => 'nullable',
            'gam_api_name' => 'nullable',
            'gam_api_email' => 'nullable',
            'gam_api_passcode' => 'nullable',
            'gam_api_status' => 'nullable', // Y or N
            'status' => 'required' // Y or N
        ];
    }

    /* create and edit site validation */
    public static function siteRules()
    {
        return [
            'status' => 'required', // Y or N
            'old_site_id' => 'nullable',
            'site_name' => 'required',
            'site_url' => 'required',
            'publisher_id' => 'required',
            'show_impressions_data' => 'required',  // Y or N
            'publir_products' => 'nullable',  // only ids
            'integrated_wp_plugin' => 'required',  // Y or N
            'integrated_js_code' => 'required',  // Y or N
            'react_site' => 'required',  // Y or N    Integrate React Plugin
            'prebid_version' => 'required',
            'prebid_timeout' => 'required',
            'prebid_failsafe' => 'required',
            'id5_id' => 'nullable',
            'liveramp_id' => 'nullable',
            'sticky_mode_only' => 'required', // Y or N
            'prebid_debug_mode' => 'required', // Y or N
            'prebid_gdpr' => 'required',
            'gam_api_status' => 'required',
            'disable_init_load' => 'required',
            'restricted_urls' => 'nullable',
            'amazon_pub_id' => 'nullable',
            'amazon_ad_server' => 'nullable',
            'amazon_hb' => 'required',
            'taxonomy' => 'required',
            'pub_own_s3_bucket' => 'required',
            'aws_access_key' => 'nullable',
            'aws_secret_key' => 'nullable',
            's3_bucket_name' => 'nullable',
            'cloudflare_auth_email' => 'nullable',
            'cloudflare_auth_key' => 'nullable',
            'seller_id' => 'nullable',
            'seller_name' => 'required',
            'seller_domain' => 'required',
            'seller_type' => 'required',
            'seller_json_status' => 'required',
            'email_reports_status' => 'required',
            'account_manager_id' => 'nullable',
            'track_page_views' => 'required',
            'track_clicks' => 'required',
            'google_auto_ads' => 'required',
        ];
    }

    /* assign manager validation */
    public static function assignManagerRules()
    {
        return [
            'site_id' => 'required',
            'account_manager_id' => 'required',
        ];
    }
}