<?php

namespace App\Http\ValidationRules\Onboarding;

class AdOptimizationValidation
{
    
    /* create mcm invite validation */
    public static function mcmInviteRules()
    {
        return [
            'site_id' => 'required',
            'mcm_require' => 'required',
            'invite_status' => 'required',
            'comments' => 'required',
        ];
    }


    /*create ads txt validation */
    public static function adsTxtRules()
    {
        return [
            'site_id' => 'required',
            'updated_on_site' => 'required',
            'redirect_to_publir' => 'required',
            'comments' => 'required',
            'email' => 'required',
            'content' => 'required',
        ];
    }

    /*create prebid js validation */
    public static function createPrebidJsRules()
    {
        return [
            'site_id' => 'required',
            'status' => 'required',
            'prebid_version' => 'required',
            'across' => 'required',
            'media_online' => 'required',
            'dot_media' => 'required',
            'a4g' => 'required',
            'aax' => 'required',
            'ablida' => 'required',
            'acuity_ads' => 'required',
            'adwmg' => 'required',
            'adgaio' => 'required',
            'adasta_media' => 'required',
            'adbite' => 'required',
            'adblender' => 'required',
            'adbookpsp' => 'required',
            'addefend' => 'required',
            'adformopen_rtb' => 'required',
            'gdpr' => 'required',
            'gpp' => 'required',
            'us_privacy' => 'required',
            'first_party_data_enrichment' => 'required',
            'gdpr_enforcement' => 'required',
            'gpt_pre_auction' => 'required',
        ];
    }

    /*store ad tags validation */
    public static function adTagsRules()
    {
        return [
            'site_id' => 'required',
            'shared' => 'required',
            'implemented' => 'required',
            'comments' => 'required',
        ];
    }
}