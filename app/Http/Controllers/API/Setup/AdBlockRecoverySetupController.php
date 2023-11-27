<?php

namespace App\Http\Controllers\API\Setup;

use App\Http\Controllers\Controller;
use App\Http\Traits\Setup\AdBlockRecoverySetupTrait;
use App\Models\AdBlocks;
use App\Models\AdBlockSchedules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdBlockRecoverySetupController extends Controller
{
    use AdBlockRecoverySetupTrait;
    /*
    ** create new ad block preset
    **
    */
    public function create_ad_block(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'site_id' => 'required',
            'nick_name' => 'required',
            'notice_text'  => 'required',
            'notice_text_language' => 'required',
            'start_immediately' => 'required',
            'start_with_enddate' => 'required',
            'prior_end_date' => 'required_if:start_with_enddate,==,1',
            'schedule_preset' => 'required_if:start_immediately,==,0',
            'countries' => 'required',
            'browsers' => 'required',
            'desktop_preview' => 'required',
            'tablet_preview' => 'required',
            'mobile_preview' => 'required',
            'notice_location' => 'required',
            'show_notice_after' => 'required',
            'hide_notice_status' => 'required',
            'hide_notice' => 'required',
            'hide_notice_for' => 'required',
            'lock_access_status' => 'required',
            'lock_access' => 'required',
            'lock_access_for' => 'required',
            'allow_close' => 'required',
            'blur_content' => 'required',
            'blur_content_percentage' => 'required',
            'show_whitelist_instructions' => 'required',
            'show_visits_left' => 'required',
            'notice_bg_color' => 'required',
            'notice_text_color' => 'required',
            'notice_border_width' => 'required',
            'notice_border_color' => 'required',
            'link_color' => 'required',
            'link_hover_color' => 'required',
            'close_btn_bg_color' => 'required',
            'close_btn_font_color' => 'required',
            'whitelist_btn_color' => 'required',
            'whitelist_btn_font_color' => 'required',
            'whitelist_btn_location' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        $preset = $this->createPresetQuery($request);
        return $preset;
    }

    /*
    ** ad block script
    **
    */
    public function ad_block_script(Request $request, $widget_id)
    {
        $script = $this->generateScriptQuery($widget_id);
        return response()->json(['message' => 'Preset script generate successfully', 'status' => true, 'preset' => $script]);
    }

    /*
    ** get ad block all preset by site_id
    **
    */
    public function get_ad_block_all_preset(Request $request, $site_id)
    {
        $adBlockPreset = $this->getAllPresetBySiteIdQuery($site_id);
        return response()->json(['message' => 'All Preset get successfully', 'status' => true, 'preset' => $adBlockPreset]);
    }

    /*
    ** get ad block preset
    **
    */
    public function get_ad_block_preset(Request $request, $widget_id)
    {
        $adBlockPreset = $this->getPresetByWidgetIdQuery($widget_id);
        return response()->json(['message' => 'Preset get successfully', 'status' => true, 'preset' => $adBlockPreset]);
    }

    /*
    ** edit new ad block preset
    **
    */
    public function edit_ad_block(Request $request, $widget_id)
    {
        $validator  = Validator::make($request->all(), [
            "site_id" => 'required',
            'nick_name' => 'required',
            'notice_text'  => 'required',
            'notice_text_language' => 'required',
            'start_immediately' => 'required',
            'start_with_enddate' => 'required',
            'prior_end_date' => 'required_if:start_with_enddate,==,1',
            'schedule_preset' => 'required_if:start_immediately,==,0',
            'countries' => 'required',
            'browsers' => 'required',
            'desktop_preview' => 'required',
            'tablet_preview' => 'required',
            'mobile_preview' => 'required',
            'notice_location' => 'required',
            'show_notice_after' => 'required',
            'hide_notice_status' => 'required',
            'hide_notice' => 'required',
            'hide_notice_for' => 'required',
            'lock_access_status' => 'required',
            'lock_access' => 'required',
            'lock_access_for' => 'required',
            'allow_close' => 'required',
            'blur_content' => 'required',
            'blur_content_percentage' => 'required',
            'show_whitelist_instructions' => 'required',
            'show_visits_left' => 'required',
            'notice_bg_color' => 'required',
            'notice_text_color' => 'required',
            'notice_border_width' => 'required',
            'notice_border_color' => 'required',
            'link_color' => 'required',
            'link_hover_color' => 'required',
            'close_btn_bg_color' => 'required',
            'close_btn_font_color' => 'required',
            'whitelist_btn_color' => 'required',
            'whitelist_btn_font_color' => 'required',
            'whitelist_btn_location' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        $checkEditStatus = $this->presetEditCheck($request, $widget_id);
        if($checkEditStatus) {
            return response()->json(['message' => 'Preset already exists with this date range, country, browser and device configuration', 'status' => false], 500);
        }

        $input = $request->all();
        $adblockData = $this->editPresetQuery($widget_id, $input);
        $presetStyleData = $this->editPresetStyleQuery($widget_id, $input);
        $adblockSchedulesData = $this->editPresetSchedulesQuery($widget_id, $input);

        if ($adblockData && $presetStyleData && $adblockSchedulesData) {
            $adBlockScript = $this->generateScriptQuery($widget_id);
            $preset = $this->getPresetByWidgetIdQuery($widget_id);
            return response()->json(['status' => true, 'message' => 'AdBlock preset update successfully.', 'preset' => $preset, 'scriptLink' => $adBlockScript]);
        } else {
            return response()->json(['status' => false, 'message' => 'Something went worng', 'data' => []]);
        }
    }

    /*
    ** Update preset status api
    **
    */
    public function update_ad_block_preset_status(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            "widget_id" => 'required',
            "status" => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        $adBlockPreset = $this->updatePresetStatusQuery($request);
        return $adBlockPreset;
    }

    /*
    ** Compare preset api
    **
    */
    public function compare_ad_block_preset(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'  => 'required',
            'widget_ids' => 'required',
            'site_id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 500);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $widgetIds = $request->input('widget_ids');
        $siteId = $request->input('site_id');
        $startDate = Carbon::parse($startDate . '00:00:00');
        $endDate = Carbon::parse($endDate . '23:59:59');

        $adBlockPreset = $this->presetComapareQuery($widgetIds, $siteId, $startDate, $endDate);
        return response()->json(['message' => 'Compare data get successfully', 'status' => true, 'preset' => $adBlockPreset]);
    }

    /*
    ** delete preset data based on widget id
    **
    */
    public function delete_preset(Request $request, $widget_id)
    {
        $deletePreset = $this->deletePresetByWidgetIdQuery($widget_id);
        return response()->json(['message' => 'Preset delete successfully', 'status' => true, 'preset' => $deletePreset]);
    }
}
