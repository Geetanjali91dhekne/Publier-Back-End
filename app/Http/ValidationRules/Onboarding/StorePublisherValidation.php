<?php

namespace App\Http\ValidationRules\Onboarding;

class StorePublisherValidation
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public static function publisherRules($id)
    {
        return [
            'full_name' => 'required', // Y or N
            'business_name' => 'required',
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
}