<?php

namespace App\Http\Controllers\API\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Traits\Onboarding\AdOptimizationOnboardingTrait;
use App\Http\ValidationRules\Onboarding\StorePublisherValidation;
use App\Http\ValidationRules\Onboarding\StoreSiteValidation;
use App\Models\Publisher;
use App\Models\Sites;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdOptimizationOnboardingController extends Controller
{
    use AdOptimizationOnboardingTrait;

    /*
    ** get all sites site
    **
    */
    public function getAllSitesList(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'search_site' => 'nullable',
            'site_ids' => 'array',
            'publisher_ids' => 'array',
            'status' => 'array',
            'account_manager' => 'array',
            'publisher_version' => 'array',
            'live_product' => 'array'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try {
            $userId = $request->get('userId');
            $adAllSites = $this->getAllSitesListQuery($userId, $request);
            return response()->json(['message' => 'All Sites get successfully', 'status' => true, 'sites' => $adAllSites]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    ** get recent site
    **
    */
    public function getRecentSitesList(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'search_site' => 'nullable',
            'site_ids' => 'array',
            'publisher_ids' => 'array',
            'status' => 'array',
            'account_manager' => 'array',
            'publisher_version' => 'array',
            'live_product' => 'array'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try {
            $userId = $request->get('userId');
            $adRecent = $this->getRecentListQuery($userId, $request);
            return response()->json(['message' => 'Recent get successfully', 'status' => true, 'recent' => $adRecent]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    ** get favourites site
    **
    */
    public function getFavouritesSitesList(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'search_site' => 'nullable',
            'site_ids' => 'array',
            'publisher_ids' => 'array',
            'status' => 'array',
            'account_manager' => 'array',
            'publisher_version' => 'array',
            'live_product' => 'array'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try{
            $userId = $request->get('userId');
            $adFavorites = $this->getFavouritesListQuery($userId, $request);
            $adFavorites = $adFavorites->orderBy('dt_sites.site_id', 'desc')->limit(10)->get();
            return response()->json(['message' => 'Favorites get successfully', 'status' => true, 'favorites' => $adFavorites]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }


    /*
    ** get archive site
    **
    */
    public function getArchiveSitesList(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'search_site' => 'nullable',
            'site_ids' => 'array',
            'publisher_ids' => 'array',
            'status' => 'array',
            'account_manager' => 'array',
            'publisher_version' => 'array',
            'live_product' => 'array'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try {
            $userId = $request->get('userId');
            $adArchive = $this->getArchiveListQuery($userId, $request);
            $adArchive = $adArchive->get();
            return response()->json(['message' => 'Archive get successfully', 'status' => true, 'archive' => $adArchive]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    * get site details
    **
    */
    public function getSiteDetail(Request $request, $site_id)
    {
        try {
            $sitesData = $this->getSiteDetailQuery($site_id);
            return response()->json(['status' => true, 'message' => 'site details get successfully.', 'data' => $sitesData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    * get publisher details
    **
    */
    public function getPublisherDetail(Request $request, $id)
    {
        try {
            $publisherData = Publisher::where('id', $id)->first();
            return response()->json(['status' => true, 'message' => 'Publisher details get successfully.', 'data' => $publisherData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    * edit site details
    **
    */
    public function editSiteDetail(Request $request, $site_id)
    {
        $rules = StoreSiteValidation::siteRules($site_id);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try {
            $sitesData = $this->editSiteDetailQuery($request, $site_id);
            return response()->json(['status' => true, 'message' => 'site update successfully.', 'data' => $sitesData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    ** edit new publisher name
    **
    */
    public function editPublisherDetail(Request $request, $id)
    {
        $rules = StorePublisherValidation::publisherRules($id);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try {
            $request->merge(['password' => hash('sha256',  $request->get('password'))]);
            $publisherData = Publisher::where('id', $id)
                ->update($request->all());
            return response()->json(['status' => true, 'message' => 'Publisher update successfully.', 'data' => $publisherData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    * update site status api
    **
    */
    public function updateSiteStatus(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            "site_id" => 'required',
            "status" => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try {
            $siteData = Sites::where('site_id', $request['site_id'])
                ->update(['status' => $request['status']]);
            return response()->json(['status' => true, 'message' => 'site status update successfully.', 'data' => $siteData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    ** Create new publisher
    **
    */
    public function createPublisher(Request $request)
    {
        $rules = StorePublisherValidation::publisherRules($id = null);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try {
            $request->merge([
                'password' => hash('sha256', $request->get('password')),
                'publisher_id' => 'PR' . time() . mt_rand(100, 999),
                'created_at' => Carbon::now()
            ]);
            $publisherData = Publisher::create($request->all());
            return response()->json(['status' => true, 'message' => 'create publisher successfully.', 'data' => $publisherData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    ** Create sites
    **
    */
    public function createSites(Request $request)
    {
        $rules = StoreSiteValidation::siteRules($siteId = null);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        try {
            $sitesData = $this->createSitesQuery($request);
            $site = $this->getSiteDetailQuery($sitesData['site_id']);
            return response()->json(['status' => true, 'message' => 'create site successfully.', 'data' => $site]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }
}
