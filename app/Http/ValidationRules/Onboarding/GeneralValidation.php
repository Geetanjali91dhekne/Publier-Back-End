<?php

namespace App\Http\ValidationRules\Onboarding;

class GeneralValidation
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public static function createMockUpRules()
    {
        return [
            'site_id' => 'required',
            'status' => 'required', // Y or N
            'email' => 'nullable',
            'message' => 'nullable',
            'upload_mockup' => 'nullable|array'
        ];
    }

    public static function createBillingRules()
    {
        return [
            'site_id' => 'required',
            'status' => 'required',
            'type' => 'nullable', // W8 or W9 or both
            'as_pdf' => 'nullable', // Y or N
            'as_excel' => 'nullable', // Y or N
            'email' => 'nullable',
            'message' => 'nullable'
        ];
    }

    public static function createAgreementRules()
    {
        return [
            'site_id' => 'required',
            'status' => 'required',
            'attachment_required' => 'required',
            'upload_agreement' => 'array|required_if:attachment_required,==,Y',
            'comment' => 'required',
        ];
    }

    public static function vettingGuidelinesRules()
    {
        return [
            'site_id' => 'required',
            'status' => 'required', // Y or N
        ];
    }

    public static function createCustomTaskRules($id)
    {
        return [
            'site_id' => 'required',
            'task_name' => $id ? 'required|unique:dt_general_custom,task_name,' . $id : 'required|unique:dt_general_custom',
            'global' => 'required',
            'complete' => 'required',
            'attachment_required' => 'required',
            'custom_document' => 'array|required_if:attachment_required,==,Y',
            'comment' => 'required',
        ];
    }
}