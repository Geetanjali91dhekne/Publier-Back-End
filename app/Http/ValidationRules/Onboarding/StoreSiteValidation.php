<?php

namespace App\Http\ValidationRules\Onboarding;

class StoreSiteValidation
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public static function siteRules()
    {
        return [
            'status' => 'required', // Y or N
            'old_site_id' => 'nullable',
            'site_name' => 'required',
            'site_url' => 'required',
            'publisher_id' => 'required',
            'show_impressions_data' => 'required',  // Y or N
            'publir_products' => 'required',  // only ids
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
            'amazon_pub_id' => 'required',
            'amazon_ad_server' => 'required',
            'amazon_hb' => 'required',
            'taxonomy' => 'required',
            'pub_own_s3_bucket' => 'required',
            'aws_access_key' => 'nullable',
            'aws_secret_key' => 'nullable',
            's3_bucket_name' => 'nullable',
            'cloudflare_auth_email' => 'nullable',
            'cloudflare_auth_key' => 'nullable',
            'seller_id' => 'required',
            'seller_name' => 'required',
            'seller_domain' => 'required',
            'seller_type' => 'required',
            'seller_json_status' => 'required',
            'email_reports_status' => 'required'
        ];
    }
}
