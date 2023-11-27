<?php

namespace App\Http\Traits\Onboarding;

use App\Models\AdsTxt;
use App\Models\AdTags;
use App\Models\CreatePrebidJs;
use App\Models\McmInvite;
use Illuminate\Support\Facades\Mail;

trait AdOptimizationOnboardingTrait
{

    public function storeMcmInviteQuery($request, $id = null)
    {
        $mcmData = array();
        $mcmData['site_id'] = $request->get('site_id');
        $mcmData['mcm_require'] = $request->get('mcm_require');
        $mcmData['invite_status'] = $request->get('invite_status');
        $mcmData['comments'] = $request->get('comments');
        
        if($id) {
            $mcmInviteData = McmInvite::where('id', $id)->update($mcmData);
            return McmInvite::find($id);
        } else {
            $mcmInviteData = McmInvite::create($mcmData);
            return $mcmInviteData;
        }
    }

    public function storeAdsTxtQuery($request, $id = null)
    {
        $adsData = array();
        $adsData['site_id'] = $request->get('site_id');
        $adsData['updated_on_site'] = $request->get('updated_on_site');
        $adsData['redirect_to_publir'] = $request->get('redirect_to_publir');
        $adsData['comments'] = $request->get('comments');
        $adsData['email'] = $request->get('email');
        $adsData['content'] = $request->get('content');

        if($id) {
            $adsTxtData = AdsTxt::where('id', $id)->update($adsData);
            return AdsTxt::find($id);
        } else {
            $adsTxtData = AdsTxt::create($adsData);
            return  $adsTxtData;
        }
    }

    public function sendAdOpsAdsTxtMailQuery($request, $id)
    {
        $mockupData = AdsTxt::find($id);
        $data["email"] = explode(',', $mockupData['email']);
        $data["title"] = "AdsTxt Mail";
        $data["content"] = $mockupData['content'];

        Mail::send('mail.adstxt-mail', $data, function($message)use($data) {
            $message->to($data["email"])->subject($data["title"]);
        });
        return "success";
    }

    public function storePrebidJsQuery($request, $id = null)
    {
        $prebidData = array();
        $prebidData['site_id'] = $request->get('site_id');
        $prebidData['status'] = $request->get('status');
        $prebidData['prebid_version'] = $request->get('prebid_version');
        $prebidData['across'] = $request->get('across');
        $prebidData['media_online'] = $request->get('media_online');
        $prebidData['dot_media'] = $request->get('dot_media');
        $prebidData['a4g'] = $request->get('a4g');
        $prebidData['aax'] = $request->get('aax');
        $prebidData['ablida'] = $request->get('ablida');
        $prebidData['acuity_ads'] = $request->get('acuity_ads');
        $prebidData['adwmg'] = $request->get('adwmg');
        $prebidData['adgaio'] = $request->get('adgaio');
        $prebidData['adasta_media'] = $request->get('adasta_media');
        $prebidData['adbite'] = $request->get('adbite');
        $prebidData['adblender'] = $request->get('adblender');
        $prebidData['adbookpsp'] = $request->get('adbookpsp');
        $prebidData['addefend'] = $request->get('addefend');
        $prebidData['adformopen_rtb'] = $request->get('adformopen_rtb');
        $prebidData['gdpr'] = $request->get('gdpr');
        $prebidData['gpp'] = $request->get('gpp');
        $prebidData['us_privacy'] = $request->get('us_privacy');
        $prebidData['first_party_data_enrichment'] = $request->get('first_party_data_enrichment');
        $prebidData['gdpr_enforcement'] = $request->get('gdpr_enforcement');
        $prebidData['gpt_pre_auction'] = $request->get('gpt_pre_auction');

        if($id) {
            $prebidData = CreatePrebidJs::where('id', $id)->update($prebidData);
            return CreatePrebidJs::find($id);
        } else {
            $prebidData = CreatePrebidJs::create($prebidData);
            return $prebidData;
        }
    }

    public function storeAdTagsJsQuery($request, $id = null)
    {
        $adTagsData = array();
        $adTagsData['site_id'] = $request->get('site_id');
        $adTagsData['shared'] = $request->get('shared');
        $adTagsData['implemented'] = $request->get('implemented');
        $adTagsData['comments'] = $request->get('comments');

        if($id) {
            $adTagsRes = AdTags::where('id', $id)->update($adTagsData);
            return AdTags::find($id);
        } else {
            $adTagsRes = AdTags::create($adTagsData);
            return  $adTagsRes;
        }
    }
}
