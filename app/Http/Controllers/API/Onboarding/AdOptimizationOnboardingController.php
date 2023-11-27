<?php

namespace App\Http\Controllers\API\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Traits\Onboarding\AdOptimizationOnboardingTrait;
use App\Http\ValidationRules\Onboarding\AdOptimizationValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdOptimizationOnboardingController extends Controller
{
    use AdOptimizationOnboardingTrait;

    /* site wise adOptimization section create MCM invite functionality */
    public function createAdOpsMcmInvite(Request $request)
    {
        $rules = AdOptimizationValidation::mcmInviteRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }
        try {
            $mcmData = $this->storeMcmInviteQuery($request);
            return $this->sendResponse($mcmData, 'MCM invite create successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise adOptimization section edit MCM invite functionality */
    public function editAdOpsMcmInvite(Request $request, $id)
    {
        $rules = AdOptimizationValidation::mcmInviteRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }
        try {
            $mcmData = $this->storeMcmInviteQuery($request, $id);
            return $this->sendResponse($mcmData, 'MCM invite update successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }
    
    /* site wise adOptimization section create Ads.txt functionality */
    public function createAdOpsAdsTxt(Request $request)
    {
        $rules = AdOptimizationValidation::adsTxtRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }
        try {
            $adsData = $this->storeAdsTxtQuery($request);
            return $this->sendResponse($adsData, 'Ads.txt create successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise adOptimization section edit Ads.txt functionality */
    public function editAdOpsAdsTxt(Request $request, $id)
    {
        $rules = AdOptimizationValidation::adsTxtRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }
        try {
            $adsData = $this->storeAdsTxtQuery($request, $id);
            return $this->sendResponse($adsData, 'Ads.txt updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise adOptimization section Ads.txt mail functionality */
    public function sendAdOpsAdsTxtMail(Request $request, $id)
    {
        try {
            $mockupData = $this->sendAdOpsAdsTxtMailQuery($request, $id);
            return $this->sendResponse($mockupData, 'Ads.txt Mail Send Successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }
    
    /* site wise adOptimization section create prebidjs functionality */
    public function createAdOpsPrebidJs(Request $request)
    {
        $rules = AdOptimizationValidation::createPrebidJsRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }
        try {
            $prebidData = $this->storePrebidJsQuery($request);
            return response()->json(['status' => true, 'message' => 'create prebid js successfully.', 'data' => $prebidData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 403);
        }
    }

    /* site wise adOptimization section edit prebidjs functionality */
    public function editAdOpsPrebidJs(Request $request, $id)
    {
        $rules = AdOptimizationValidation::createPrebidJsRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }
        try {
            $prebidData = $this->storePrebidJsQuery($request, $id);
            return response()->json(['status' => true, 'message' => 'prebid js update successfully.', 'data' => $prebidData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 403);
        }
    }

    /* site wise adOptimization section create ad tags functionality */
    public function createAdOpsAdTags(Request $request)
    {
        $rules = AdOptimizationValidation::adTagsRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }
        try {
            $prebidData = $this->storeAdTagsJsQuery($request);
            return response()->json(['status' => true, 'message' => 'Create ad tags successfully.', 'data' => $prebidData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 403);
        }
    }

    /* site wise adOptimization section edit ad tags functionality */
    public function editAdOpsAdTags(Request $request, $id)
    {
        $rules = AdOptimizationValidation::adTagsRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }
        try {
            $prebidData = $this->storeAdTagsJsQuery($request, $id);
            return response()->json(['status' => true, 'message' => 'Ad tags update successfully.', 'data' => $prebidData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 403);
        }
    }
}
