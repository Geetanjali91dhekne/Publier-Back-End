<?php

namespace App\Http\Controllers\API\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Traits\Onboarding\GeneralOnboardingTrait;
use App\Http\ValidationRules\Onboarding\GeneralValidation;
use App\Models\Agreement;
use App\Models\Billing;
use App\Models\GeneralCustomTask;
use App\Models\MockUp;
use App\Models\VettingGuidelines;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeneralOnboardingController extends Controller
{
    use GeneralOnboardingTrait;

    /* site wise general section create mockup functionality  */
    public function createGeneralMockUp(Request $request)
    {
        $rules = GeneralValidation::createMockUpRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $mockupData = $this->storeGeneralMockUpQuery($request);
            return $this->sendResponse($mockupData, 'Mockup Create Successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    public function sendGeneralMockupMail(Request $request)
    {
        try {
            $mockupData = $this->sendGeneralMockupMailQuery($request);
            return $this->sendResponse($mockupData, 'Mockup Mail Send Successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section create billing functionality */
    public function createGeneralBilling(Request $request)
    {
        $rules = GeneralValidation::createBillingRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $billingData = $this->storeGeneralBillingQuery($request);
            return $this->sendResponse($billingData, 'Billing Created Successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    public function sendGeneralBillingMail(Request $request)
    {
        try {
            $billingData = $this->sendGeneralBillingMailQuery($request);
            return $this->sendResponse($billingData, 'Billing Mail Send Successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section create agreement functionality */
    public function createGeneralAgreement(Request $request)
    {
        $rules = GeneralValidation::createAgreementRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $agreementData = $this->createGeneralArgeementQuery($request);
            $agreementDocData = Agreement::where('id', $agreementData['id'])->with(['agreementDocuments'])->first();
            return $this->sendResponse($agreementDocData, 'Agreement Create Successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise get all general section data */
    public function getGeneralDataBySite(Request $request, $siteId)
    {
        try {
            $mockUp = MockUp::where('site_id', $siteId)->with(['mockupDocuments'])->get();
            $billingData = Billing::where('site_id', $siteId)->get();
            $agreementData = Agreement::where('site_id', $siteId)->with(['agreementDocuments'])->get();
            $vettingGuidelines = VettingGuidelines::where('site_id', $siteId)->get();
            $customTask = GeneralCustomTask::where('site_id', $siteId)->with(['customDocuments'])->get();

            return response()->json([
                'status' => true,
                'message' => 'Get General Tab Details.',
                'mockUp' => $mockUp,
                'billingData' => $billingData,
                'agreementData' => $agreementData,
                'vettingGuidelines' => $vettingGuidelines,
                'customTask' => $customTask,
            ]);
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section edit mockup functionality */
    public function editGeneralMockUp(Request $request, $id)
    {
        $rules = GeneralValidation::createMockUpRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $mockupData = $this->storeGeneralMockUpQuery($request, $id);
            return $this->sendResponse($mockupData, 'Mockup Update Successfully.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section edit billing functionality */
    public function editGeneralBilling(Request $request, $id)
    {
        $rules = GeneralValidation::createBillingRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $billingData = $this->storeGeneralBillingQuery($request, $id);
            return $this->sendResponse($billingData, 'Billing Details Updated.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section create agreement functionality */
    public function editGeneralAgreement(Request $request, $id)
    {
        $rules = GeneralValidation::createAgreementRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $agreementData = $this->editGeneralArgeementQuery($request, $id);
            $agreementDocData = Agreement::where('id', $id)->with(['agreementDocuments'])->first();
            return $this->sendResponse($agreementDocData, 'Agreement Details Updated.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section create vetting guidelines functionality */
    public function createGeneralVettingGuidelines(Request $request)
    {
        $rules = GeneralValidation::vettingGuidelinesRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $vettingData = $this->createGeneralVettingGuidelinesQuery($request);
            return $this->sendResponse($vettingData, 'Vetting Guidelines Created.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section edit vetting guidelines functionality */
    public function editGeneralVettingGuidelines(Request $request, $id)
    {
        $rules = GeneralValidation::vettingGuidelinesRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $vettingData = $this->editVettingGuidelineQuery($request, $id);
            return $this->sendResponse($vettingData, 'Vetting Guidelines Status Updated.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section create custom task functionality */
    public function createGeneralCustomTask(Request $request)
    {
        $rules = GeneralValidation::createCustomTaskRules($id = null);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $taskData = $this->createGeneralCustomTaskQuery($request);
            $taskDocData = GeneralCustomTask::where('id', $taskData['id'])->with(['customDocuments'])->first();
            return $this->sendResponse($taskDocData, 'Custom Task Created.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }

    /* site wise general section edit custom task functionality */
    public function editGeneralCustomTask(Request $request, $id)
    {
        $rules = GeneralValidation::createCustomTaskRules($id);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), [], 500);
        }

        try {
            $customData = $this->editGeneralCustomTaskQuery($request, $id);
            $taskDocData = GeneralCustomTask::where('id', $id)->with(['customDocuments'])->first();
            return $this->sendResponse($taskDocData, 'Custom Task Updated.');
        } catch (\Exception $e) {
            return $this->sendError(sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), [], 500);
        }
    }
}