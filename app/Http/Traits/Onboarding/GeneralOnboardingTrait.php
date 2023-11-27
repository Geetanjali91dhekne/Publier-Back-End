<?php

namespace App\Http\Traits\Onboarding;

use App\Models\Agreement;
use App\Models\AgreementDocument;
use App\Models\Billing;
use App\Models\GeneralCustomTask;
use App\Models\GeneralCustomDocument;
use App\Models\MockUp;
use App\Models\MockUpDocument;
use App\Models\VettingGuidelines;
use Illuminate\Support\Facades\Mail;

trait GeneralOnboardingTrait
{
    public function storeGeneralMockUpQuery($request, $id = null)
    {
        // check based on site id mockup data exist in DB
        $checkMockup = MockUp::where('site_id', $request->get('site_id'))->first();
        if($checkMockup) $id = $checkMockup['id'];

        // store mockup
        $mockupData = [];
        $mockupData['site_id'] = $request->get('site_id');
        $mockupData['status'] = $request->get('status');
        $mockupData['email'] = $request->get('email');
        $mockupData['message'] = $request->get('message');
        if ($id) {
            $mockupRes = MockUp::where('id', $id)->update($mockupData);
        } else {
            $mockupRes = MockUp::create($mockupData);
            $id = $mockupRes['id'];
        }

        // store uploaded mockup
        $uploadMockup = $request->get('upload_mockup');
        if (!empty($uploadMockup)) {
            for ($i = 0; $i < count($uploadMockup); $i++) {

                if(isset($uploadMockup[$i]['id']) && $uploadMockup[$i]['id']) {
                    /* update existing document */
                    MockUpDocument::where('id', $uploadMockup[$i]['id'])->update([
                        'status' => $uploadMockup[$i]['status'],
                        'doc_check' => $uploadMockup[$i]['doc_check'],
                    ]);
                } else {
                    /* store doc file to public folder */
                    $docName = time() . mt_rand(100, 999).'.'.$request->upload_mockup[$i]['document']->getClientOriginalExtension();;
                    $request->upload_mockup[$i]['document']->move(public_path('documents/'), $docName);
                    
                    $mockupDocData = [];
                    $mockupDocData['site_id'] = $request->get('site_id');
                    $mockupDocData['mockup_id'] = $id;
                    $mockupDocData['status'] = $uploadMockup[$i]['status'];
                    $mockupDocData['doc_check'] = $uploadMockup[$i]['doc_check'];
                    $mockupDocData['document'] = 'documents/'.$docName;
                    MockUpDocument::create($mockupDocData);
                }
            }
        }
        $deleteDoc = explode(',', $request->get('deleted_documents'));
        $files = MockUpDocument::whereIn('id', $deleteDoc)->pluck('document')->toArray();
        foreach ($files as $doc) {
            if(file_exists(public_path($doc)))
            unlink(public_path($doc));
        }
        MockUpDocument::whereIn('id', $deleteDoc)->delete();

        $mockupData = MockUp::where('id', $id)->with(['mockupDocuments'])->first();
        return $mockupData;
    }

    public function sendGeneralMockupMailQuery($request)
    {
        $mockup_id = $request->get('mockup_id');
        $mockupData = $this->storeGeneralMockUpQuery($request, $mockup_id);
        $id = $mockupData['id'];

        $mockupData = MockUp::find($id);
        $data["email"] = explode(',', $mockupData['email']);
        $data["title"] = "Mockup Mail";
        $data["content"] = $mockupData['message'];

        $mockupDocs = MockUpDocument::where([
                ['mockup_id', '=', $id],
                ['doc_check', '=', 1]
            ])->get()->toArray();
        Mail::send('mail.billing-mail', $data, function($message)use($data, $mockupDocs) {
            $message->to($data["email"])->subject($data["title"]);
            foreach ($mockupDocs as $docs){
                $message->attach(public_path($docs['document']));
            }
        });
        return "success";
    }

    public function storeGeneralBillingQuery($request, $id = null)
    {
        // check based on site id billing data exist in DB
        $checkBilling = Billing::where('site_id', $request->get('site_id'))->first();
        if($checkBilling) $id = $checkBilling['id'];

        // data for dt_billing table
        $billingData = [];
        $billingData['site_id'] = $request->get('site_id');
        $billingData['type'] = $request->get('type');
        $billingData['as_pdf'] = $request->get('as_pdf');
        $billingData['as_excel'] = $request->get('as_excel');
        $billingData['email'] = $request->get('email');
        $billingData['message'] = $request->get('message');
        $billingData['status'] = $request->get('status');

        if ($id) {
            $billingUpdate = Billing::where('id', $id)->update($billingData);
            return Billing::find($id);
        } else {
            $billingRes = Billing::create($billingData);
            return $billingRes;
        }
    }

    public function sendGeneralBillingMailQuery($request)
    {
        $billing_id = $request->get('billing_id');
        $billingData = $this->storeGeneralBillingQuery($request, $billing_id);

        $data["email"] = explode(',', $billingData['email']);
        $data["title"] = "Billing Mail";
        $data["content"] = $billingData['message'];

        $files = [];
        if($billingData["type"] == 'both' || $billingData["type"] == 'W8') {
            if($billingData["as_pdf"] == 'Y') array_push($files, public_path('/storage/general-billing/fw8.pdf'));
            if($billingData["as_excel"] == 'Y') array_push($files, public_path('/storage/general-billing/fw8.pdf'));
        }
        if ($billingData["type"] == 'both' || $billingData["type"] == 'W9') {
            if($billingData["as_pdf"] == 'Y') array_push($files, public_path('/storage/general-billing/fw9.pdf'));
            if($billingData["as_excel"] == 'Y') array_push($files, public_path('/storage/general-billing/fw9.pdf'));
        }

        Mail::send('mail.billing-mail', $data, function($message)use($data, $files) {
            $message->to($data["email"])->subject($data["title"]);
            foreach ($files as $file){
                $message->attach($file);
            }
        });
        return "success";
    }

    public function createGeneralArgeementQuery($request)
    {
        // create data in dt_agreement table
        $agreementDocs = [];
        $agreementDocs['site_id'] = $request->get('site_id');
        $agreementDocs['status'] = $request->get('status');
        $agreementDocs['attachment_required'] = $request->get('attachment_required');
        $agreementDocs['comment'] = $request->get('comment');
        $agreementRes = Agreement::create($agreementDocs);

        // store uploaded agreement
        $uploadAgreement = $request->get('upload_agreement');
        if (!empty($uploadAgreement)) {
            for ($i = 0; $i < count($uploadAgreement); $i++) {

                /* store doc file to public folder */
                $docName = time() . mt_rand(100, 999).'.'.$request->upload_agreement[$i]['document']->getClientOriginalExtension();;
                $request->upload_agreement[$i]['document']->move(public_path('documents/'), $docName);
                
                $agreementDocData = [];
                $agreementDocData['site_id'] = $request->get('site_id');
                $agreementDocData['agreement_id'] = $agreementRes['id'];
                $agreementDocData['status'] = $uploadAgreement[$i]['status'];
                $agreementDocData['doc_check'] = $uploadAgreement[$i]['doc_check'];
                $agreementDocData['document'] = 'documents/'.$docName;
                AgreementDocument::create($agreementDocData);
            }
        }
        return $agreementRes;
    }

    public function editGeneralArgeementQuery($request, $id)
    {
        // update agreement
        $agreementRes = Agreement::where('id', $id)->update([
            'site_id' => $request->get('site_id'),
            'status' => $request->get('status'),
            'attachment_required' => $request->get('attachment_required'),
            'comment' => $request->get('comment'),
        ]);

        // store uploaded agreement
        $uploadAgreement = $request->get('upload_agreement');
        if (!empty($uploadAgreement)) {
            for ($i = 0; $i < count($uploadAgreement); $i++) {

                if(isset($uploadAgreement[$i]['id']) && $uploadAgreement[$i]['id']) {
                    /* update existing document */
                    AgreementDocument::where('id', $uploadAgreement[$i]['id'])->update([
                        'status' => $uploadAgreement[$i]['status'],
                        'doc_check' => $uploadAgreement[$i]['doc_check'],
                    ]);
                } else {
                    /* store doc file to public folder */
                    $docName = time() . mt_rand(100, 999).'.'.$request->upload_agreement[$i]['document']->getClientOriginalExtension();;
                    $request->upload_agreement[$i]['document']->move(public_path('documents/'), $docName);

                    $agreementDocData = [];
                    $agreementDocData['site_id'] = $request->get('site_id');
                    $agreementDocData['agreement_id'] = $id;
                    $agreementDocData['status'] = $uploadAgreement[$i]['status'];
                    $agreementDocData['doc_check'] = $uploadAgreement[$i]['doc_check'];
                    $agreementDocData['document'] = 'documents/'.$docName;
                    AgreementDocument::create($agreementDocData);
                }
            }
        }
        $deleteDoc = explode(',', $request->get('deleted_documents'));
        $files = AgreementDocument::whereIn('id', $deleteDoc)->pluck('document')->toArray();
        foreach ($files as $doc) {
            if(file_exists(public_path($doc)))
                unlink(public_path($doc));
        }
        AgreementDocument::whereIn('id', $deleteDoc)->delete();
        return $agreementRes;
    }

    public function createGeneralVettingGuidelinesQuery($request)
    {
        $vettingData = VettingGuidelines::create([
            'site_id' => $request->get('site_id'),
            'status' => $request->get('status'),
        ]);
        return $vettingData;
    }

    public function editVettingGuidelineQuery($request, $id)
    {
        $vettingRes = VettingGuidelines::where('id', $id)->update([
            'site_id' => $request->get('site_id'),
            'status' => $request->get('status'),
        ]);
        return $vettingRes;
    }

    public function createGeneralCustomTaskQuery($request)
    {
        // create data in dt_general_custom_document table
        $customTaskRes = GeneralCustomTask::create([
            'site_id' => $request->get('site_id'),
            'task_name' => $request->get('task_name'),
            'global' => $request->get('global'),
            'complete' => $request->get('complete'),
            'attachment_required' => $request->get('attachment_required'),
            'comment' => $request->get('comment'),
        ]);

        // store uploaded custom document
        $customDocument = $request->get('custom_document');
        if (!empty($customDocument)) {
            for ($i = 0; $i < count($customDocument); $i++) {
                /* store doc file to public folder */
                $docName = time() . mt_rand(100, 999).'.'.$request->custom_document[$i]['document']->getClientOriginalExtension();;
                $request->custom_document[$i]['document']->move(public_path('documents/'), $docName);
                
                GeneralCustomDocument::create([
                    'site_id' => $request->get('site_id'),
                    'general_custom_id' => $customTaskRes['id'],
                    'status' => $customDocument[$i]['status'],
                    'doc_check' => $customDocument[$i]['doc_check'],
                    'document' => 'documents/'.$docName,
                ]);
            }
        }
        return $customTaskRes;
    }

    public function editGeneralCustomTaskQuery($request, $id)
    {
        // update custom task
        $customTaskRes = GeneralCustomTask::where('id', $id)->update([
            'site_id' => $request->get('site_id'),
            'task_name' => $request->get('task_name'),
            'global' => $request->get('global'),
            'complete' => $request->get('complete'),
            'attachment_required' => $request->get('attachment_required'),
            'comment' => $request->get('comment'),
        ]);

        // update uploaded custom task document
        $customDocument = $request->get('custom_document');
        if (!empty($customDocument)) {
            for ($i = 0; $i < count($customDocument); $i++) {
                if(isset($customDocument[$i]['id']) && $customDocument[$i]['id']) {
                    /* update existing document */
                    GeneralCustomDocument::where('id', $customDocument[$i]['id'])->update([
                        'status' => $customDocument[$i]['status'],
                        'doc_check' => $customDocument[$i]['doc_check'],
                    ]);
                } else {
                    /* store doc file to public folder */
                    $docName = time() . mt_rand(100, 999).'.'.$request->custom_document[$i]['document']->getClientOriginalExtension();;
                    $request->custom_document[$i]['document']->move(public_path('documents/'), $docName);

                    $customDocData = [];
                    $customDocData['site_id'] = $request->get('site_id');
                    $customDocData['general_custom_id'] = $id;
                    $customDocData['status'] = $customDocument[$i]['status'];
                    $customDocData['doc_check'] = $customDocument[$i]['doc_check'];
                    $customDocData['document'] = 'documents/'.$docName;
                    GeneralCustomDocument::create($customDocData);
                }
            }
        }
        $deleteDoc = explode(',', $request->get('deleted_documents'));
        $files = GeneralCustomDocument::whereIn('id', $deleteDoc)->pluck('document')->toArray();
        foreach ($files as $doc) {
            if(file_exists(public_path($doc)))
                unlink(public_path($doc));
        }
        GeneralCustomDocument::whereIn('id', $deleteDoc)->delete();
        return $customTaskRes;
    }
}
