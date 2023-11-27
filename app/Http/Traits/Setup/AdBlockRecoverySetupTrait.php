<?php

namespace App\Http\Traits\Setup;

use App\Models\AdblockReports;
use App\Models\AdBlocks;
use App\Models\AdBlockSchedules;
use App\Models\AdBlockStyles;
use App\Models\PageviewsDailyReports;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

trait AdBlockRecoverySetupTrait
{

    public function checkPreview($request, $device, $widget_id = null)
    {
        $site_id = $request->get('site_id');
        $checkPreview = AdBlocks::query();
        $checkPreview = $checkPreview->where('hre_adblock.site_id', $site_id)->where('hre_adblock.status', 1);
        if($widget_id) $checkPreview = $checkPreview->where('widget_id', '!=', $widget_id);
        if ($request->get($device)) {
            $checkPreview = $checkPreview->where($device, 1);
        } else {
            $checkPreview = $checkPreview->where($device, 0);
        }
        $checkPreview = $checkPreview->get()->count();
        return $checkPreview;
    }

    public function checkStartImmediately($request, $site_id, $widget_id = null) {
        $whereCondition = [
            'start_immediately' => $request->get('start_immediately'),
            'start_with_enddate' => $request->get('start_with_enddate'),
            'status' => 1,
            'site_id' => $site_id
        ];
        $calendarData = AdBlocks::query();
        if($widget_id) $calendarData = $calendarData->where('widget_id', '!=', $widget_id);
        $calendarData = $calendarData->where($whereCondition)->exists();
        return $calendarData;
    }

    public function checkPriorEndDate($request, $site_id, $widget_id = null) {
        $priorEndDate = 0;
        if($request->get('prior_end_date')) {
            $priorEndDate = AdBlocks::query();
            if($widget_id) $priorEndDate = $priorEndDate->where('widget_id', '!=', $widget_id);
            $priorEndDate = $priorEndDate->where('site_id', $site_id)->where('status', 1)->whereDate('prior_end_date', '>=', $request->get('prior_end_date'))->exists();
        } else {
            $priorEndDate = 1;
        }
        return $priorEndDate;
    }

    public function checkSchedulePreset($request, $site_id, $widget_id = null) {
        $schedulePreset = $request->get('schedule_preset');
        $dateExists = 0;
        if(empty($schedulePreset)) $dateExists = 1;
        foreach ($schedulePreset as $schedule) {
            $scheduleData = AdBlockSchedules::query();
            if($widget_id) $scheduleData = $scheduleData->where('hre_adblock_schedules.widget_id', '!=', $widget_id);
            $scheduleData = $scheduleData->where('hre_adblock_schedules.site_id', $site_id)
                ->join('hre_adblock', 'hre_adblock.widget_id', '=', 'hre_adblock_schedules.widget_id')
                ->where('hre_adblock.status', 1)
                ->where(function ($query) use ($schedule) {
                    $query
                        ->orWhereBetween('start_date', [$schedule['startDate'], $schedule['endDate']])
                        ->orWhereBetween('end_date', [$schedule['startDate'], $schedule['endDate']]);
                })
                ->exists();

            $reportEndDate = AdBlocks::query();
                if($widget_id) $reportEndDate = $reportEndDate->where('widget_id', '!=', $widget_id);
                $reportEndDate = $reportEndDate
                    ->where([
                        ['hre_adblock.site_id', $site_id],
                        ['hre_adblock.status', 1],
                        ['hre_adblock.prior_end_date', '>=', [$schedule['endDate']]]
                    ])
                ->get()->count();
                    
            if ($scheduleData || $reportEndDate) $dateExists = 1;
        }
                    
        return $dateExists;
    }

    public function checkDateRange($request, $site_id, $widget_id = null) {
        // if($widget_id) return 0;
        $reportDate = AdBlocks::query();
        if($widget_id) {
            $reportDate = $reportDate->where('hre_adblock.widget_id', '!=', $widget_id);
        } else {
            if ($request->get('prior_end_date')) {
                $reportDate = $reportDate
                    ->whereDate('hre_adblock_schedules.start_date', '>=', date('Y-m-d'))
                    ->whereDate('hre_adblock_schedules.start_date', '<=', $request->get('prior_end_date'))
                    ->orWhereRaw('? between hre_adblock_schedules.start_date and hre_adblock_schedules.end_date', [$request->get('prior_end_date')]);
            }
        }
        $reportDate = $reportDate
            ->where('hre_adblock.site_id', $site_id)
            ->where('hre_adblock.status', 1)
            ->join('hre_adblock_schedules', 'hre_adblock_schedules.widget_id', '=', 'hre_adblock.widget_id')
            ->select('hre_adblock_schedules.end_date', 'hre_adblock_schedules.end_date', 'hre_adblock.widget_id', 'hre_adblock.prior_end_date')
            // ->whereRaw('? between hre_adblock_schedules.start_date and hre_adblock_schedules.end_date', [date('Y-m-d')])
            ->whereDate('hre_adblock_schedules.end_date', '>=', date('Y-m-d'))
            ->get()->count();

        $reportEndDate = AdBlocks::query();
        if($widget_id) $reportEndDate = $reportEndDate->where('widget_id', '!=', $widget_id);
        $reportEndDate = $reportEndDate
            ->where([
                ['hre_adblock.site_id', $site_id],
                ['hre_adblock.status', 1],
                ['hre_adblock.prior_end_date', '>=', date('Y-m-d')]
            ])
            ->get()->count();

        if($reportDate || $reportEndDate) return 1;
        else return 0; 
    }

    public function checkCountries($request, $site_id, $widget_id = null) {
        $countries = $request->get('countries');
        $countryQuery = AdBlocks::query();
        $countryQuery = $countryQuery->where('site_id', $site_id)->where('status', 1);
        if($widget_id) $countryQuery = $countryQuery->where('widget_id', '!=', $widget_id);
        $countryQuery = $countryQuery->where(function ($query) use ($countries) {
            foreach ($countries as $country) {
                $query->orWhereJsonContains('countries', $country);
            }
        });
        $countryQuery = $countryQuery->exists();
        return $countryQuery;
    }

    public function checkBrowsers($request, $site_id, $widget_id = null) {
        $browsers = $request->get('browsers');
        $browserQuery = AdBlocks::query();
        $browserQuery = $browserQuery->where('site_id', $site_id)->where('status', 1);
        if($widget_id) $browserQuery = $browserQuery->where('widget_id', '!=', $widget_id);
        $browserQuery = $browserQuery->where(function ($query) use ($browsers) {
            foreach ($browsers as $country) {
                $query->orWhereJsonContains('browsers', $country);
            }
        });
        $browserQuery = $browserQuery->get()->toArray();
        return $browserQuery;
    }

    public function checkEndDate($request, $site_id, $widget_id = null) {
        $whereCondition = [
            'start_immediately' => 1,
            'start_with_enddate' => 0,
            'site_id' => $site_id,
            'status' => 1
        ];
        $checkEndDate = AdBlocks::query();
        if($widget_id) $checkEndDate = $checkEndDate->where('widget_id', '!=', $widget_id);
        $checkEndDate = $checkEndDate->where($whereCondition)->exists();
        return $checkEndDate;
    }

    public function checkClosePreview($request, $site_id, $widget_id = null) {
        $whereCondition = [
            'desktop_preview' => 0,
            'tablet_preview' => 0,
            'mobile_preview' => 0,
            'site_id' => $site_id,
            'status' => 1
        ];
        $checkClosePreview = AdBlocks::query();
        if($widget_id) $checkClosePreview = $checkClosePreview->where('widget_id', '!=', $widget_id);
        $checkClosePreview = $checkClosePreview->where($whereCondition)->exists();
        return $checkClosePreview;
    }

    public function createPresetQuery($request)
    {
        $site_id = $request->get('site_id');
        /* first check start immediately and no end date */
        $calendarData = $this->checkStartImmediately($request, $site_id);

        /* check prior end date */
        $priorEndDate = $this->checkPriorEndDate($request, $site_id);

        /* check schedule preset date range */
        $dateExists = $this->checkSchedulePreset($request, $site_id);

        /* check countries data */
        $countries = $request->get('countries');
        $countryQuery = $this->checkCountries($request, $site_id);
        $allCountries = AdBlocks::where('site_id', $site_id)->whereJsonContains('countries', 'All')->exists();

        /* check browser data */
        $browsers = $request->get('browsers');
        $browserQuery = $this->checkBrowsers($request, $site_id);
        $allBrowsers = AdBlocks::where('site_id', $site_id)->whereJsonContains('browsers', 'All')->exists();

        /* check preview option */
        $desktop_preview = $this->checkPreview($request, 'desktop_preview');
        $tablet_preview = $this->checkPreview($request, 'tablet_preview');
        $mobile_preview = $this->checkPreview($request, 'mobile_preview');
        $checkClosePreview = $this->checkClosePreview($request, $site_id);
        /* check end date */
        $checkEndDate = $this->checkEndDate($request, $site_id);

        /* check schedule date and preset_end_date */
        $checkDateRange = $this->checkDateRange($request, $site_id);

        /* check exists or not */
        $is_exists = 0;
        // if (($calendarData || $checkEndDate) && ($request->get('start_with_enddate') == 0 || $priorEndDate) && ($dateExists || $checkEndDate)) {
        if (($calendarData || $checkEndDate || $checkDateRange) && ($priorEndDate || $checkEndDate || $checkDateRange) && ($dateExists || $checkEndDate)) {
            if (($countryQuery || in_array('All', $countries)) || $allCountries) {
                if (($browserQuery || in_array('All', $browsers)) || $allBrowsers) {
                    if (($request->get('desktop_preview') && $desktop_preview) || ($request->get('tablet_preview') && $tablet_preview) || ($request->get('mobile_preview') && $mobile_preview)) {
                        $is_exists = 1;
                    } else {
                        if ($checkClosePreview)  $is_exists = 1;
                        else $is_exists = 0;
                    }
                } else {
                    $is_exists = 0;
                }
            } else {
                $is_exists = 0;
            }
        } else {
            $is_exists = 0;
        }

        if($is_exists) {
            return response()->json(['message' => 'Preset already exists with this date range, country, browser and device configuration', 'status' => false], 500);
        }

        $widget_id = md5(time() . mt_rand(10000, 99999) . '');
        $post_data = array();
        $post_data['site_id'] = $request->get('site_id');
        $post_data['widget_id'] = $widget_id;
        $post_data['nick_name'] = $request->get('nick_name');
        $post_data['notice_text'] = $request->get('notice_text');
        $post_data['notice_text_language'] = $request->get('notice_text_language');
        $post_data['start_immediately'] = $request->get('start_immediately');
        $post_data['start_with_enddate'] = $request->get('start_with_enddate');
        $post_data['prior_end_date'] = $request->get('prior_end_date');
        $post_data['countries'] = $request->get('countries');
        $post_data['browsers'] = $request->get('browsers');
        $post_data['adblock_preview'] = $request->get('adblock_preview') ? $request->get('adblock_preview') : '0';
        $post_data['desktop_preview'] = $request->get('desktop_preview');
        $post_data['tablet_preview'] = $request->get('tablet_preview');
        $post_data['mobile_preview'] = $request->get('mobile_preview');
        $post_data['notice_location'] = $request->get('notice_location');
        $post_data['show_notice_after'] = $request->get('show_notice_after');
        $post_data['hide_notice_status'] = $request->get('hide_notice_status');
        $post_data['hide_notice'] = $request->get('hide_notice');
        $post_data['hide_notice_for'] = $request->get('hide_notice_for');
        $post_data['lock_access_status'] = $request->get('lock_access_status');
        $post_data['lock_access'] = $request->get('lock_access');
        $post_data['lock_access_for'] = $request->get('lock_access_for');
        $post_data['allow_close'] = $request->get('allow_close');
        $post_data['blur_content'] = $request->get('blur_content');
        $post_data['blur_content_percentage'] = $request->get('blur_content_percentage');
        $post_data['show_whitelist_instructions'] = $request->get('show_whitelist_instructions');
        $post_data['show_visits_left'] = $request->get('show_visits_left');
        $post_data['created_at'] = Carbon::now();

        $post_data_style = array();
        $post_data_style['site_id'] = $request->get('site_id');
        $post_data_style['widget_id'] = $widget_id;
        $post_data_style['notice_bg_color'] = $request->get('notice_bg_color');
        $post_data_style['notice_text_color'] = $request->get('notice_text_color');
        $post_data_style['notice_border_width'] = $request->get('notice_border_width');
        $post_data_style['notice_border_color'] = $request->get('notice_border_color');
        $post_data_style['link_color'] = $request->get('link_color');
        $post_data_style['link_hover_color'] = $request->get('link_hover_color');
        $post_data_style['close_btn_bg_color'] = $request->get('close_btn_bg_color');
        $post_data_style['close_btn_font_color'] = $request->get('close_btn_font_color');
        $post_data_style['whitelist_btn_color'] = $request->get('whitelist_btn_color');
        $post_data_style['whitelist_btn_font_color'] = $request->get('whitelist_btn_font_color');
        $post_data_style['whitelist_btn_location'] = $request->get('whitelist_btn_location');
        $post_data['created_at'] = Carbon::now();

        AdBlocks::create($post_data);
        AdBlockStyles::create($post_data_style);

        $schedulePreset = $request->get('schedule_preset');
        if (!empty($schedulePreset)) {
            foreach ($schedulePreset as $schedule) {
                AdBlockSchedules::create([
                    'site_id' => $request->get('site_id'),
                    'widget_id' => $widget_id,
                    'start_date' => $schedule['startDate'],
                    'end_date' => $schedule['endDate'],
                    'created_at' => Carbon::now(),
                ]);
            }
        }
        $preset = $this->getPresetByWidgetIdQuery($widget_id);
        $adBlockScript = $this->generateScriptQuery($widget_id);
        return response()->json(['message' => 'Preset create successfully', 'status' => true, 'preset' => $preset, 'scriptLink' => $adBlockScript]);
    }

    public function presetEditCheck($request, $widget_id) {

        $site_id = $request->get('site_id');
        /* first check start immediately and no end date */
        $calendarData = $this->checkStartImmediately($request, $site_id, $widget_id);

        /* check prior end date */
        $priorEndDate = $this->checkPriorEndDate($request, $site_id, $widget_id);

        /* check schedule preset date range */
        $dateExists = $this->checkSchedulePreset($request, $site_id, $widget_id);

        /* check countries data */
        $countries = $request->get('countries');
        $countryQuery = $this->checkCountries($request, $site_id, $widget_id);
        $allCountries = AdBlocks::where('site_id', $site_id)->where('widget_id', '!=', $widget_id)->whereJsonContains('countries', 'All')->exists();

        /* check browser data */
        $browsers = $request->get('browsers');
        $browserQuery = $this->checkBrowsers($request, $site_id, $widget_id);
        $allBrowsers = AdBlocks::where('site_id', $site_id)->where('widget_id', '!=', $widget_id)->whereJsonContains('browsers', 'All')->exists();

        /* check preview option */
        $desktop_preview = $this->checkPreview($request, 'desktop_preview', $widget_id);
        $tablet_preview = $this->checkPreview($request, 'tablet_preview', $widget_id);
        $mobile_preview = $this->checkPreview($request, 'mobile_preview', $widget_id);
        $checkClosePreview = $this->checkClosePreview($request, $site_id, $widget_id);
        /* check end date */
        $checkEndDate = $this->checkEndDate($request, $site_id, $widget_id);

        /* check schedule date and preset_end_date */
        $checkDateRange = $this->checkDateRange($request, $site_id, $widget_id);
        /* check exists or not */
        $is_exists = 0;
        // if (($calendarData || $checkEndDate) && ($request->get('start_with_enddate') == 0 || $priorEndDate) && ($dateExists || $checkEndDate)) {
        if (($calendarData || $checkEndDate || $checkDateRange) && ($priorEndDate || $checkEndDate || $checkDateRange) && ($dateExists || $checkEndDate)) {
            if (($countryQuery || in_array('All', $countries)) || $allCountries) {
                if (($browserQuery || in_array('All', $browsers)) || $allBrowsers) {
                    if (($request->get('desktop_preview') && $desktop_preview) || ($request->get('tablet_preview') && $tablet_preview) || ($request->get('mobile_preview') && $mobile_preview)) {
                        $is_exists = 1;
                    } else {
                        if ($checkClosePreview)  $is_exists = 1;
                        else $is_exists = 0;
                    }
                } else {
                    $is_exists = 0;
                }
            } else {
                $is_exists = 0;
            }
        } else {
            $is_exists = 0;
        }
        return $is_exists;
    }

    public function getAllPresetBySiteIdQuery($site_id)
    {
        $adBlockPreset = AdBlocks::query();
        $adBlockPreset = $adBlockPreset->where('site_id', $site_id)
            ->select(
                'site_id',
                'widget_id',
                'nick_name',
                'status'
            )
            ->get()->toArray();

        return $adBlockPreset;
    }

    public function getPresetByWidgetIdQuery($widget_id)
    {
        $adBlockPreset = AdBlocks::query();
        $adBlockPreset = $adBlockPreset->where('widget_id', $widget_id)
            ->with('schedule_preset')
            ->with('preset_style')
            ->select('*')->get()->toArray();
        return $adBlockPreset;
    }

    public function getScheduleTimingQuery($adBlockPreset)
    {
        $widgetId = array_column($adBlockPreset, 'widget_id');
        $scheduleTime = AdBlockSchedules::query();
        $scheduleTime = $scheduleTime->whereIn('widget_id', $widgetId)->get()->toArray();

        $newScheduleTime = [];
        foreach ($scheduleTime as $key => $val) {
            if (!isset($newScheduleTime[$val['widget_id']])) {
                $newScheduleTime[$val['widget_id']] = [$val];
            } else {
                array_push($newScheduleTime[$val['widget_id']], $val);
            }
        }

        foreach ($adBlockPreset as $key => $value) {
            if (array_key_exists($value['widget_id'], $newScheduleTime)) {
                $schedulePreset = $newScheduleTime[$value['widget_id']];
                $adBlockPreset[$key]['schedule_preset'] = $schedulePreset;
            } else {
                $adBlockPreset[$key]['schedule_preset'] = [];
            }
        }
        return $adBlockPreset;
    }

    public function editPresetQuery($widget_id, $input)
    {
        if (AdBlocks::where('widget_id',  $widget_id)->exists()) {
            $adblockData = AdBlocks::where('widget_id', $widget_id)
                ->update([
                    'nick_name' => $input['nick_name'],
                    'notice_text' => $input['notice_text'],
                    'notice_text_language' => $input['notice_text_language'],
                    'start_immediately' => $input['start_immediately'],
                    'start_with_enddate' => $input['start_with_enddate'],
                    'prior_end_date' => $input['prior_end_date'],
                    'countries' => $input['countries'],
                    'browsers' => $input['browsers'],
                    'desktop_preview' => $input['desktop_preview'],
                    'tablet_preview' => $input['tablet_preview'],
                    'mobile_preview' => $input['mobile_preview'],
                    'notice_location' => $input['notice_location'],
                    'show_notice_after' => $input['show_notice_after'],
                    'hide_notice_status' => $input['hide_notice_status'],
                    'hide_notice' => $input['hide_notice'],
                    'hide_notice_for' => $input['hide_notice_for'],
                    'lock_access_status' => $input['lock_access_status'],
                    'lock_access' => $input['lock_access'],
                    'lock_access_for' => $input['lock_access_for'],
                    'allow_close' => $input['allow_close'],
                    'blur_content' => $input['blur_content'],
                    'blur_content_percentage' => $input['blur_content_percentage'],
                    'show_whitelist_instructions' => $input['show_whitelist_instructions'],
                    'show_visits_left' => $input['show_visits_left'],
                ]);
            return $adblockData;
        } else {
            return 0;
        }
    }

    public function editPresetStyleQuery($widget_id, $input)
    {
        if (AdBlockStyles::where('widget_id',  $widget_id)->exists()) {
            $presetStyleData = AdBlockStyles::where('widget_id', $widget_id)
                ->update([
                    'notice_bg_color' => $input['notice_bg_color'],
                    'notice_text_color' => $input['notice_text_color'],
                    'notice_border_width' => $input['notice_border_width'],
                    'notice_border_color' => $input['notice_border_color'],
                    'link_color' => $input['link_color'],
                    'link_hover_color' => $input['link_hover_color'],
                    'close_btn_bg_color' => $input['close_btn_bg_color'],
                    'close_btn_font_color' => $input['close_btn_font_color'],
                    'whitelist_btn_color' => $input['whitelist_btn_color'],
                    'whitelist_btn_font_color' => $input['whitelist_btn_font_color'],
                    'whitelist_btn_location' => $input['whitelist_btn_location'],
                ]);
            return $presetStyleData;
        } else {
            return 0;
        }
    }

    public function editPresetSchedulesQuery($widget_id, $input)
    {
        AdBlockSchedules::where('widget_id',  $widget_id)->delete();
        $schedulePreset = $input['schedule_preset'];
        if (!empty($schedulePreset)) {
            foreach ($schedulePreset as $schedule) {
                AdBlockSchedules::create([
                    'site_id' => $input['site_id'],
                    'widget_id' => $widget_id,
                    'start_date' => $schedule['startDate'],
                    'end_date' => $schedule['endDate']
                ]);
            }
        }
        return 1;
    }

    public function updatePresetStatusQuery($request)
    {
        $widget_id = $request->get('widget_id');
        $status = $request->get('status');
        if (AdBlocks::where('widget_id',  $widget_id)->exists()) {
            $adBlockStyles = AdBlockStyles::where('widget_id', $widget_id)->first()->toArray();
            $adBlockSchedules = AdBlockSchedules::where('widget_id', $widget_id)->select('start_date as startDate', 'end_date as endDate')->get()->toArray();
            $adBlocks = AdBlocks::where('widget_id', $widget_id)->first()->toArray();
            $request->merge($adBlocks);
            $request->merge(['schedule_preset' => $adBlockSchedules]);
            $request->merge($adBlockStyles);

            $is_exists = $this->presetEditCheck($request, $widget_id);
            if($is_exists) {
                return response()->json(['message' => 'Preset already exists with this date range, country, browser and device configuration', 'status' => false], 500);
            }

            $presetStatus = AdBlocks::where('widget_id', $widget_id)->update(['status' => $status]);
            if ($presetStatus) {
                $preset = $this->getPresetByWidgetIdQuery($request->input('widget_id'));
                $this->getScriptBySiteIdQuery($adBlocks['site_id']);
                return response()->json(['status' => true, 'message' => 'Preset status update successfully.', 'data' => $preset]);
            } else {
                return response()->json(['status' => false, 'message' => 'Something went worng', 'data' => []], 500);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'Record not found with this id', 'data' => []], 500);
        }
    }

    public function getAdblockUserPreset($startDate, $endDate, $siteId, $widgetIds)
    {
        $adblock = AdblockReports::query();
        $adblock = $adblock->whereIn('widget_id', $widgetIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                'site_id', 'widget_id',
                DB::raw('ifnull(SUM(pageviews), 0) AS sum_adblock'),
            )
            ->groupBy('widget_id')
            ->get()->toArray();

        $totalPV = PageviewsDailyReports::query();
        $totalPV = $totalPV->where('site_id', $siteId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('site_id', DB::raw('SUM(pageviews + subscription_pageviews) as sum_pageview'))
            ->get();
        /* pageview and subscription page view */
        $pv_sub_pv = $totalPV->sum('sum_pageview');

        foreach ($adblock as $key => $sites) {
            $adblock[$key]['adblock_users'] = $pv_sub_pv ? ($sites['sum_adblock'] / ($pv_sub_pv + $sites['sum_adblock'])) * 100 : 0;
        }
        return $adblock;
    }

    public function presetComapareQuery($widgetIds, $siteId, $startDate, $endDate)
    {
        $adBlockPreset = AdBlocks::query();
        $adBlockPreset = $adBlockPreset
            ->with('schedule_preset')
            ->with('preset_style')
            ->whereIn('widget_id', $widgetIds)
            ->get()->toArray();

        $pageviews = $this->getAdblockUserPreset($startDate, $endDate, $siteId, $widgetIds);
        $pageviews = array_replace_recursive(array_combine(array_column($pageviews, "widget_id"), $pageviews));
        foreach($adBlockPreset as $key => $preset) {
            if (array_key_exists($preset['widget_id'], $pageviews)) {
                $pageview = $pageviews[$preset['widget_id']];
                $adBlockPreset[$key]['adblock_pv'] = (float) $pageview['sum_adblock'];
                $adBlockPreset[$key]['adblock_users'] = (float) $pageview['adblock_users'];
            } else {
                $adBlockPreset[$key]['adblock_pv'] = 0;
                $adBlockPreset[$key]['adblock_users'] = 0;
            }
        }
        return $adBlockPreset;
    }

    public function generateScriptQuery($widget_id)
    {
        $adBlockPreset = AdBlocks::query();
        $adBlockPreset = $adBlockPreset->where('widget_id', $widget_id)
            ->with('schedule_preset')
            ->with('preset_style')
            ->select('*')->get()->toArray();

        if (!empty($adBlockPreset)) {
            $site_id = $adBlockPreset[0]['site_id'];
            $widget_id = $adBlockPreset[0]['widget_id'];
            $nick_name = $adBlockPreset[0]['nick_name'];
            $notice_text = $adBlockPreset[0]['notice_text'];
            $notice_text = str_replace("\n", "", $notice_text);
            $notice_text = preg_replace('/\>\s+\</m', '><', $notice_text);
            $notice_bg_color = $adBlockPreset[0]['preset_style']['notice_bg_color'];
            $link_color = $adBlockPreset[0]['preset_style']['link_color'];
            $link_hover_color = $adBlockPreset[0]['preset_style']['link_hover_color'];
            $notice_text_color = $adBlockPreset[0]['preset_style']['notice_text_color'];
            $notice_border_color = $adBlockPreset[0]['preset_style']['notice_border_color'];
            $notice_border_width = $adBlockPreset[0]['preset_style']['notice_border_width'];
            $allow_close = $adBlockPreset[0]['allow_close'];
            $notice_location = $adBlockPreset[0]['notice_location'];
            $blur_content = $adBlockPreset[0]['blur_content'];
            $blur_content_percentage = ($adBlockPreset[0]['blur_content_percentage'] * 5) / 100;
            $show_notice_after = $adBlockPreset[0]['show_notice_after'];
            $hide_notice_status = $adBlockPreset[0]['hide_notice_status'];
            $hide_notice = $adBlockPreset[0]['hide_notice'];
            $hide_notice_for = $adBlockPreset[0]['hide_notice_for'];
            $show_visits_left = $adBlockPreset[0]['show_visits_left'];
            $lock_access_status = $adBlockPreset[0]['lock_access_status'];
            $lock_access = $adBlockPreset[0]['lock_access'];
            $lock_access_for = $adBlockPreset[0]['lock_access_for'];
            $close_btn_bg_color = $adBlockPreset[0]['preset_style']['close_btn_bg_color'];
            $close_btn_font_color = $adBlockPreset[0]['preset_style']['close_btn_font_color'];
            $show_whitelist_instructions = $adBlockPreset[0]['show_whitelist_instructions'];
            $whitelist_btn_location = $adBlockPreset[0]['preset_style']['whitelist_btn_location'];
            $whitelist_btn_color = $adBlockPreset[0]['preset_style']['whitelist_btn_color'];
            $whitelist_btn_font_color = $adBlockPreset[0]['preset_style']['whitelist_btn_font_color'];
            $adblock_location_margin_top = $adBlockPreset[0]['preset_style']['adblock_location_margin_top'];
            $adblock_location_margin_bottom = $adBlockPreset[0]['preset_style']['adblock_location_margin_bottom'];
            $adblockPreview = $adBlockPreset[0]['adblock_preview'];
            $font_color = '#000';
            $countries = $adBlockPreset[0]['countries'];
            $browsers = $adBlockPreset[0]['browsers'];
            $desktop_preview = $adBlockPreset[0]['desktop_preview'];
            $tablet_preview = $adBlockPreset[0]['tablet_preview'];
            $mobile_preview = $adBlockPreset[0]['mobile_preview'];
            $start_immediately = $adBlockPreset[0]['start_immediately'];
            $start_with_enddate = $adBlockPreset[0]['start_with_enddate'];
            $schedule_preset = $adBlockPreset[0]['schedule_preset'];
            $prior_end_date = $adBlockPreset[0]['prior_end_date'];

            // $adblockPath = '//'.S3_ADBLOCK.'.s3.amazonaws.com/whitelist/';
            $adblockPath = 'https://fkrkkmxsqeb5bj9r.s3.amazonaws.com/whitelist/';

            if ($blur_content_percentage < 0) {
                $blur_content_percentage = '2';
            }
            if ($allow_close < 1) {
                $close_string = "";
            } else {
                $close_string = '<span onclick=\"abpbclostbtn(`' . $widget_id . '`)\" class=\"abpbConsent-close-btn\">&times;</span>';
            }

            if ($notice_location == "top") {
                $location_margin_style = 'margin-top:' . $adblock_location_margin_top . 'px;';
            } else if ($notice_location == "bottom") {
                $location_margin_style = 'margin-bottom:' . $adblock_location_margin_bottom . 'px;';
            }

            if ($show_notice_after != 'No') {
                $string = null;
                if ($adblockPreview == 1) {
                    $string .= "var publirPageParams = (new URL(document.location)).searchParams;
                    var publir_preview = publirPageParams.get('publir_preview');
                    if(publir_preview===\"1\") {";
                }

                // $string .= "var oScriptElem = document.createElement('script');oScriptElem.type = 'text/javascript';oScriptElem.src = 'https://b.publir.com/platform/ads.js';document.getElementsByTagName('head')[0].appendChild(oScriptElem);\r\n";
                $string .= "var oScriptElem = document.createElement('script');oScriptElem.type = 'text/javascript';oScriptElem.src = '';document.getElementsByTagName('head')[0].appendChild(oScriptElem);\r\n";
                $string .= "async function firstAsync" . $widget_id . "() {
                    let promise = new Promise((res, rej) => {
                        setTimeout(() => res(), 1000)
                    });
                    let result = await promise;
                    var p_currentPage = window.location.href;
                    var p_siteId = '" . $site_id . "';
                    var p_widgetId = '" . $widget_id . "';
                    var publirSiteID = '" . $site_id . "';
                    var currentrootDomain = location.hostname.split('.').reverse().splice(0,2).reverse().join('.');
                
                    function setCookie(cname, cvalue, exdays) {
                        var d = new Date();
                        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
                        var expires = 'expires='+d.toUTCString();
                        document.cookie = cname + \"=\" + cvalue + \";\" + expires + \";path=/;domain=\" + currentrootDomain;
                    }

                    function getCookie(cname) {
                        var name = cname + '=';
                        var ca = document.cookie.split(';');
                        for(var i = 0; i < ca.length; i++) {
                            var c = ca[i];
                            while (c.charAt(0) == ' ') {
                                c = c.substring(1);
                            };
                            if (c.indexOf(name) == 0) {
                                return c.substring(name.length, c.length);
                            }
                        }
                        return '';
                    }

                    if(document.getElementById('adblock1122112')){
                        const cookie = getCookie('ab-consent-" . $widget_id . "');
                        console.log('cookie --->', cookie);
                        if(cookie > 1) {
                            const userType = getCookie('ab-consent-user-type-" . $widget_id . "');
                            if (userType != 'recoveredAdBlockUser') {
                                // var before_recovered_json = { p_currentPage, p_siteId, p_widgetId, p_userType: userType };
                                // storeEventData(before_recovered_json);
    
                                var recovered_json = { p_currentPage, p_siteId, p_widgetId, p_userType: 'recoveredAdBlockUser' };
                                storeEventData(recovered_json);
                            }
                            setCookie('ab-consent-user-type-" . $widget_id . "', 'recoveredAdBlockUser', 9999);
                        } else {
                            setCookie('ab-consent-user-type-" . $widget_id . "', 'nonAdBlockUser', 9999);
                        }
                    } else {
                        var p_json_final = { p_currentPage, p_siteId, p_widgetId };
                        setCookie('ab-consent-user-type-" . $widget_id . "', 'adBlockUser', 9999);
                        storeEventData(p_json_final);";
                // $string .= "var p_currentPage = window.location.href;\r\n";
                // $string .= "var p_siteId = '" . $site_id . "';\r\n";
                // $string .= "var p_widgetId = '" . $widget_id . "';\r\n";
                // $string .= "var p_json_final = { p_currentPage, p_siteId, p_widgetId};\r\n";
                // $string .= "var url = 'https://jhgr7v0dzc.execute-api.us-east-1.amazonaws.com/default/abAnalytics';\r\n";
                // $string .= "fetch(url, {method: 'POST', mode: 'no-cors',body: JSON.stringify(p_json_final),credentials: 'include',headers: {'Content-Type': 'text/plain',}});\r\n";

                if ($lock_access > 0 && $lock_access_status == 1) {
                    $string .= "if (!getCookie('ab-hide-" . $widget_id . "') ) {
                        var ablockchek = getCookie('ab-consent-" . $widget_id . "');
                        if(ablockchek > " . $show_notice_after . "){
                            setCookie('ab-consent-" . $widget_id . "'," . $show_notice_after . ",9999);
                        }
                    }\r\n";
                }

                $string .= "if (!getCookie('ab-consent-" . $widget_id . "') ) {
                    setCookie('ab-consent-" . $widget_id . "',1,9999);
                    var abpbVisitCount = 1
                } else {
                    var abpbVisitCount = getCookie('ab-consent-" . $widget_id . "')
                    abpbVisitCount++;
                    setCookie('ab-consent-" . $widget_id . "',abpbVisitCount,9999);
                }\r\n\n";

                $string .= "function abpbConsentFunction(widget_id) {\r\n";

                $string .= 'var xabpb = document.getElementById(`abpbConsentModal-${widget_id}`);';
                $string .= "\r\n";
                $string .= 'xabpb.style.display = "block";';
                $string .= "\r\n";

                if ($blur_content > 0) {
                    $string .= "\r\n";
                    $string .= "var abpbstyleelem = document.createElement('div');\r\n";
                    $string .= "abpbstyleelem.id=\"blurabpbstyle\"; \r\n";
                    $string .= "abpbstyleelem.innerHTML = \"<style>body > *:not(#abpbConsentModal-" . $widget_id . ",#publiradblockwhitelistmodel) { filter: blur(" . $blur_content_percentage . "px);-webkit-filter: blur(" . $blur_content_percentage . "px);} .abpbOverlay {position: fixed;width: 100%;height: 100%;z-index: 1000;top: 0px;left: 0px;opacity: .5; filter: alpha(opacity=50);} body{ overflow-y:hidden; } </style>\";\r\n";
                    $string .= "\r\ndocument.body.appendChild(abpbstyleelem);\r\n";
                    $string .= "\r\n var div= document.createElement(\"div\");";
                    $string .= "\r\n  div.className += \"abpbOverlay\";";
                    $string .= "\r\n document.body.appendChild(div);";
                }

                $string .= "}";


                $string .= "var abpbShowNoticeAfter = " . $show_notice_after . "\r\n";
                $string .= 'if ( abpbVisitCount > abpbShowNoticeAfter) {';
                $string .= "\r\n";
                if ($hide_notice_for > 0 && $hide_notice_status == 1) {
                    $string .= "\r\n";
                    $string .= "if (getCookie('ab-hide-" . $widget_id . "') ) {
                        var abhideforcount = getCookie('ab-hide-for-" . $widget_id . "');
                        abhideforcount++;
                        setCookie('ab-hide-for-" . $widget_id . "', abhideforcount, 9999);
                    }\r\n\r\n";

                    $string .= "if (!getCookie('ab-hide-" . $widget_id . "') || getCookie('ab-hide-for-" . $widget_id . "') <= " . $hide_notice . ") {";
                    $string .= "\r\n";
                    $hide_duration = $hide_notice_for / 1440;
                    $string .= "\r\n";
                    $string .= "if (getCookie('ab-hide-" . $widget_id . "') == '') {";
                    $string .= "\r\n";
                    $string .= "setCookie('ab-hide-" . $widget_id . "',1," . $hide_duration . ");\r\n";
                    $string .= "setCookie('ab-hide-for-" . $widget_id . "',1,9999);\r\n";
                    $string .= '}';
                    $string .= "\r\n";
                    $string .= "var abpbVisitCount = 1";
                    $string .= "\r\n";
                }


                $string .= "console.log(\"Publir Adblock visits: \" +abpbVisitCount + \" \");\r\n";
                // $string .= 'document.addEventListener("DOMContentLoaded", function(event) {';

                $string .= "\r\nvar xhttp = new XMLHttpRequest();\r\n";
                $string .= "xhttp.onreadystatechange = function() {\r\n";
                $string .= "if (this.readyState == 4 && this.status == 200) {\r\n";

                $show_whitelist_instructions_data = '';
                if ($show_whitelist_instructions > 0) {
                    $show_whitelist_instructions_data = "<div style=\"text-align:" . $whitelist_btn_location . ";\"><button id=\"publir_show_whitelist_instructions_btn\" class=\"publirProcutpublirwhitelistBtn_" . $site_id . "_" . $widget_id . "\" style=\"margin-top: 10px;background-color:" . $whitelist_btn_color . ";color:" . $whitelist_btn_font_color . ";padding: 12px 25px;border-radius: 2px;font-weight: bold;font-size: 16px;border:none;\">Whitelist Instructions</button></div>";
                }
                if ($notice_location == "popup") {
                    $string .= "\r\nvar abpbelem = document.createElement('div');\r\n";
                    $string .= "abpbelem.id=\"abpbConsentModal-" . $widget_id . "\"; \r\n";
                    $string .= "\r\nabpbelem.innerHTML += '<style>#abpbConsentModal-" . $widget_id . " { z-index: 999999999; display: none;position: fixed; padding-top: 50px;margin: 0 auto; left: 0; top: 0;width: 100%;height: 100%; background-color: rgba(0, 0, 0, 0.5); color: " . $notice_text_color . ";} #abpbConsentModal-" . $widget_id . " .abpbConsent-modal-content { color:" . $notice_text_color . "; font-family: \"Trebuchet MS\", sans-serif; font-size: 16px; font-weight: 500; line-height:24px;} #abpbConsentModal-" . $widget_id . " .abpbConsent-modal-content { position: relative; background-color:" . $notice_bg_color . "; border-color:" . $notice_border_color . "; border-width:" . $notice_border_width . "px; border-style:solid ;padding: 20px; margin: auto; width: auto;  -webkit-animation-name: animatetop;-webkit-animation-duration: 0.4s;animation-name: animatetop;animation-duration: 0.4s}.abpbConsent-close-btn { background-color:" . $close_btn_bg_color . " ;color:" . $close_btn_font_color . " ;font-size: 24px; font-weight: bold; position: absolute; right: 0px; top: 0; cursor: pointer;padding: 0px 5px 0px 5px;height:28px;line-height: 28px;}.abpbConsent-close-btn:hover {color: darkgray;}@-webkit-keyframes animatetop {from {top:-300px; opacity:0} to {top:0; opacity:1}}@keyframes animatetop {from {top:-300px; opacity:0}to {top:0; opacity:1}} #abpbConsentModal-" . $widget_id . " .abpbConsent-modal-content { position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important;} .ebpbvisitsleft { opacity: 0.5; font-size: 14px; }#abpbConsentModal-" . $widget_id . " a:link { color:" . $link_color . ";} #abpbConsentModal-" . $widget_id . " a:visited { color:" . $link_color . ";} #abpbConsentModal-" . $widget_id . " a:hover { color:" . $link_hover_color . "; }</style><div class=\"abpbConsent-modal-content\">" . $close_string . " " . str_replace("'", "\'", stripslashes($notice_text)) . "<br>" . $show_whitelist_instructions_data . "<div class=\"publiradpageviewsleft\"></div></div>';\r\n";
                    $string .= "\r\ndocument.body.appendChild(abpbelem);\r\n";
                } else {
                    $string .= "\r\nvar abpbelem = document.createElement('div');\r\n";
                    $string .= "abpbelem.id=\"abpbConsentModal-" . $widget_id . "\" \r\n";
                    $string .= "\r\nabpbelem.innerHTML += '<style> #abpbConsentModal-" . $widget_id . "{ position : fixed;" . $notice_location . "  : 0px; z-index : 999999999; padding: 15px;background-color:" . $notice_bg_color . "; border-color:" . $notice_border_color . "; border-width:" . $notice_border_width . "px  ; color: " . $notice_text_color . "; border-style:solid ; display: flex; " . $location_margin_style . ";right: 2px;left: 2px;} #abpbConsentModal-" . $widget_id . " .abpbConsent-modal-content {  font-family: \"Trebuchet MS\", sans-serif; font-size: 16px; font-weight: 500; line-height:24px; position: relative; padding: 5px 10px; margin: 10px 0 0 0; float: left; color:" . $font_color . "; width: 100%; } @media only screen and (max-width: 768px) {.abpbConsent-modal-content { overflow-y: scroll; padding-bottom: 10px; width: 90%; height: auto !important; }  } @media only screen and (max-width: 1024px) {#abpbConsentModal-" . $widget_id . " { flex-wrap: wrap;}} .abpbConsent-close-btn { background-color:" . $close_btn_bg_color . " ;color:" . $close_btn_font_color . " ;font-size: 24px; font-weight: bold; position: absolute; right: 0px; top: 0; cursor: pointer;padding: 0px 5px 0px 5px;height: 28px;line-height: 28px;}.abpbConsent-close-btn:hover {color: darkgray;} .ebpbvisitsleft { opacity: 0.5; font-size: 14px; }#abpbConsentModal-" . $widget_id . " a:visited { color:" . $link_color . ";} #abpbConsentModal-" . $widget_id . " a:hover { color:" . $link_hover_color . "; }</style>" . $close_string . "<div class=\"abpbConsent-modal-content\">" . str_replace("'", "\'", stripslashes($notice_text)) . "<br>" . $show_whitelist_instructions_data . "<div class=\"publiradpageviewsleft\"></div></div>';\r\n";
                    $string .= "\r\n if(document.body.clientWidth != window.innerWidth) {
                        var scrollbarWidth = window.innerWidth - document.body.clientWidth;
                    }";
                    $string .= "\r\ndocument.body.appendChild(abpbelem);\r\n";
                }

                $string .= "function isHidden(el) {  var style = window.getComputedStyle(el);  return (style.display === 'none'); }";
                if ($show_whitelist_instructions > 0) {
                    // whitelist model
                    $string .= "\r\n var publiradblockwhitelistmodel = document.createElement('div');\r\n";
                    $string .= "publiradblockwhitelistmodel.id=\"publiradblockwhitelistmodel-" . $widget_id . "\"; \r\n";
                    $string .= "\r\npubliradblockwhitelistmodel.innerHTML += '<style>.publirwhitelist-modal_" . $site_id . "_" . $widget_id . "{position:fixed;left:0;top:0;width:100%;height:100%;opacity:0;visibility:hidden;transform:scale(1.1);transition:visibility 0s linear .25s,opacity .25s 0s,transform .25s;z-index:999999999;background-color:rgba(0,0,0,.5)}.show-publirwhitelist-modal_" . $site_id . "_" . $widget_id . "{opacity:1;visibility:visible;transform:scale(1);transition:visibility 0s linear 0s,opacity .25s 0s,transform .25s}.publirwhitelist-modal_" . $site_id . "_" . $widget_id . "-content{position:absolute;top:40%;left:50%;transform:translate(-50%,-40%);width:70%;max-height:600px;text-align:left;overflow-y:auto;border-radius:0;background-color:#fff}.publirwhitelist-modal-close-button" . $site_id . "_" . $widget_id . "{float:right;padding:8px 10px;line-height:30px;font-size:30px;text-align:center;cursor:pointer;border-radius:.25rem;color:#000}.publir-publirwhitelist-model-header-title-" . $site_id . "{color:#000}.publir-publirwhitelist-model-header-title-" . $site_id . "{border-bottom:1px solid #eee;font-size:20px;padding:15px 10px;text-align:left;font-weight:700}.tab-" . $site_id . "{float:left;background-color:#f1f1f1;width:40%;height:320px;overflow-x:auto;font-family:inherit}.tab-" . $site_id . " .tablinks-" . $site_id . "-" . $widget_id . "{display:block;background-color:#f5f5f5;color:#000;padding:15px 16px;border:none;outline:0;text-align:left;cursor:pointer;font-size:17px;border-bottom:1px solid #eaeaea}.tab-" . $site_id . " .tablinks-" . $site_id . "-" . $widget_id . ":hover{background-color:#e6e6e6}.tab-" . $site_id . " .tablinks-" . $site_id . "-" . $widget_id . ".active-" . $site_id . "-" . $widget_id . "{background-color:#e6e6e6}.tabcontent-" . $site_id . "{padding:0 12px;border-left:none;height:300px;display:none;font-family:inherit} .blocker-icon{width:75%;} li, ul, ol {list-style:none;} ul {margin-top: 0px; padding-left: 0px;} .img {float:left; text-align:left;} .label{padding-top:10px;}.publirwhiteInPopupRightBlock h4{text-decoration: underline;margin-bottom: 0;}.publirwhiteInPopupRightBlock ol{padding-left: 18px;}.publirwhiteInPopupRightBlock ol li {list-style: auto !important;margin-bottom: 9px;padding: 0px;}</style><div class=\"publirwhitelist-modal_" . $site_id . "_" . $widget_id . "\"><div class=\"publirwhitelist-modal_" . $site_id . "_" . $widget_id . "-content\"> <span class=\"publirwhitelist-modal-close-button" . $site_id . "_" . $widget_id . "\">&times;</span><div class=\"publir-publirwhitelist-model-header-title-" . $site_id . " item-name\">Whitelist Instructions</div><div class=\"publir-publirwhitelist-model-content\"><div class=\"tab-" . $site_id . "\"> <ul><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . " active-" . $site_id . "-" . $widget_id . "\" data-ad=\"1\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "adblock-plus.png\"></div><div class=\"label\">Adblock Plus</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"2\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "adblock.png\"></div><div class=\"label\">Adblock</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"3\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "ublock-origin.png\"></div><div class=\"label\">uBlock Origin</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"4\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "ublock.png\"></div><div class=\"label\">uBlock</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"5\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "adguard.png\"></div><div class=\"label\">Adguard</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"6\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "brave.png\"></div><div class=\"label\">Brave</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"7\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "adremover.png\"></div><div class=\"label\">Adremover</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"8\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "adblock-genesis.png\"></div><div class=\"label\">Adblock Genesis</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"9\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "super-adblocker.png\"></div><div class=\"label\">Super Adblocker</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"10\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "altrablock.png\"></div><div class=\"label\">Ultrablock</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"11\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "ad-aware.png\"></div><div class=\"label\">Ad Aware</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"12\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "ghostery.png\"></div><div class=\"label\">Ghostery</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"13\"><div class=\"img\" style=\"width:47px;\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "trackingProtection.b2f4fbbf.svg\"></div><div class=\"label\">Firefox Tracking Protection</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"14\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "duck.png\"></div><div class=\"label\">Duck Duck Go</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"15\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "privacy.png\"></div><div class=\"label\">Privacy Badger</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"16\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "disconnect.png\"></div><div class=\"label\">Disconnect</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"17\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "blocker-icon.png\"></div><div class=\"label\">Opera</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"18\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "microsoft.png\"></div><div class=\"label\">Microsoft Edge</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"19\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "adblocker-ultimate.jpg\"></div><div class=\"label\">AdBlocker Ultimate</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"20\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "stands-fair-adblocker.jpg\"></div><div class=\"label\">Stands Fair AdBlocker</div></div></li><li><div class=\"tablinks-" . $site_id . "-" . $widget_id . "\" data-ad=\"21\"><div class=\"img\"><img class=\"blocker-icon\" src=\"" . $adblockPath . "poper-blocker.jpg\"></div><div class=\"label\">Poper Blocker</div></div></div></li></ul><div style=\"float: left;width: 58%;\" class=\"publirwhiteInPopupRightBlock\"><div id=\"adblocker1-" . $site_id . "\" class=\"tabcontent-" . $site_id . "\" style=\"display: block;\"><h4>Adblock Plus Instructions</h4><ol><li>Navigate to the extension bar and click the AdBlock Plus icon </li><li>Click the blue power button</li><li>Hit the refresh</li></ol></div></div></div></div></div>';\r\n";
                    $string .= "\r\ndocument.body.appendChild(publiradblockwhitelistmodel);\r\n";

                    $string .= 'var publirwhitelistmodal' . $site_id . '_' . $widget_id . ' = document.querySelector(".publirwhitelist-modal_' . $site_id . '_' . $widget_id . '");
                        var publirwhitelistcloseButton' . $site_id . ' = document.querySelector(".publirwhitelist-modal-close-button' . $site_id . '_' . $widget_id . '");
                        if(publirwhitelistcloseButton' . $site_id . '){
                            publirwhitelistcloseButton' . $site_id . '.addEventListener("click", publirwhitelisttoggleModal' . $site_id . '_' . $widget_id . ');
                        };
                        function publirwhitelistWindowOnClick' . $site_id . '_' . $widget_id . '(event) {
                            if (event.target === publirwhitelistmodal' . $site_id . '_' . $widget_id . ') {
                                publirwhitelisttoggleModal' . $site_id . '_' . $widget_id . '();
                            }
                        }
                        function publirwhitelisttoggleModal' . $site_id . '_' . $widget_id . '() {
                             publirwhitelistmodal' . $site_id . '_' . $widget_id . '.classList.toggle("show-publirwhitelist-modal_' . $site_id . '_' . $widget_id . '");
                        }
                        document.querySelectorAll(".publirProcutpublirwhitelistBtn_' . $site_id . '_' . $widget_id . '").forEach(item1 => {
                          item1.addEventListener("click", event => {
                            publirwhitelistmodal' . $site_id . '_' . $widget_id . '.classList.toggle("show-publirwhitelist-modal_' . $site_id . '_' . $widget_id . '");
                            window.addEventListener("click", publirwhitelistWindowOnClick' . $site_id . '_' . $widget_id . ');
                            const whitelistClickData = { p_currentPage, p_siteId, p_widgetId, p_whitelistClick: 1 };
							storeEventData(whitelistClickData);
                          });
                        });
                        document.querySelectorAll(".tablinks-' . $site_id . '-' . $widget_id . '").forEach(item2 => {
                        item2.addEventListener("click", event => {
                            document.getElementsByClassName("active-' . $site_id . '-' . $widget_id . '")[0].classList.remove("active-' . $site_id . '-' . $widget_id . '");
                            item2.className += " active-' . $site_id . '-' . $widget_id . '";
                            var whitelistcontent;
                            var cityName = item2.getAttribute("data-ad");
                              if(cityName == 1){
                                whitelistcontent = "<h4>Adblock Plus Instructions</h4><ol><li>Navigate to the extension bar and click the AdBlock Plus icon </li><li>Click the blue power button</li><li>Click the Refresh button</li></ol>";
                              }else if(cityName == 2){
                                whitelistcontent = "<h4>Adblock Instructions</h4><ol><li>Navigate to the extension bar and click the AdBlock icon</li><li>Click on \"Pause on this site\"</li></ol>";
                              }else if(cityName == 3){
                                whitelistcontent = "<h4>uBlock Origin Instructions</h4><ol><li>Navigate to the extension bar and click the uBlock Origin icon</li><li>Click on the first large blue round icon</li><li>Refresh the page</li></ol>";
                              }else if(cityName == 4){
                                whitelistcontent = "<h4>uBlock Instructions</h4><ol><li>Navigate to the extension bar and click on the uBlock icon</li><li>Click \"Allow Ads on this site\"</li></ol>";
                              }else if(cityName == 5){
                                whitelistcontent = "<h4>Adguard Instructions</h4><ol><li>Navigate to the extension bar and click on the Adguard icon</li><li>Hit on the toggle next to the Protection on this website text</li></ol>";
                              }else if(cityName == 6){
                                whitelistcontent = "<h4>Brave Instructions</h4><ol><li>Navigate to the right of the address bar and click on the orange lion icon</li><li>Click the toggle on the top right, shifting from Up to Down</li></ol>";
                              }else if(cityName == 7){
                                whitelistcontent = "<h4>Adremover Instructions</h4><ol><li>Navigate to the extension bar and click on the AdRemover icon</li><liClick the \"Disable on this website\" button</li></ol>";
                              }else if(cityName == 8){
                                whitelistcontent = "<h4>Adblock Genesis Instructions</h4><ol><li>Navigate to the extension bar and click on the Adblock Genesis icon</li><li>Click on the button that says \"Whitelist Website\"</li></ol>";
                              }else if(cityName == 9){
                                whitelistcontent = "<h4>Super Adblocker Instructions</h4><ol><li>Navigate to the extension bar and click on the Super Adblocker icon</li><li>Click the green button</li></ol>";
                              }else if(cityName == 10){
                                whitelistcontent = "<h4>Ultrablock Instructions</h4><ol><li>Navigate to the extension bar and click on the UltraBlock icon</li><li>Click on the “Disable UltraBlock for ‘domain name here’ ” checkbox</li></ol>";
                              }else if(cityName == 11){
                                whitelistcontent = "<h4>Ad Aware Instructions</h4><ol><li>Navigate to the extension bar and click on the AdAware icon</li><li>Click the large orange power button</li></ol>";
                              }else if(cityName == 12){
                                whitelistcontent = "<h4>Ghostery Instructions</h4><ol><li>Navigate to the extension bar and click on the Ghostery icon</li><li>Click the \"Trust Site\" button</li></ol>";
                              }else if(cityName == 13){
                                whitelistcontent = "<h4>Firefox Tracking Protection Instructions</h4><ol><li>Navigate to the left side of address bar and click on the shield icon</li><li>Click on the toggle that says \"Enhanced Tracking protection is ON for this site\"</li></ol>";
                              }else if(cityName == 14){
                                whitelistcontent = "<h4>Duck Duck Go Instructions</h4><ol><li>Click on the DuckDuckGo icon in the extension bar</li><li>Click on the toggle next to the words \"Protections are ON fo this site\"</li></ol>";
                              }else if(cityName == 15){
                                whitelistcontent = "<h4>Privacy Badger Instructions</h4><ol><li>Navigate to the extension bar and click on the Privacy Badger icon</li><li>Click on the button that says \"Disable Privacy Badger for this site\"</li></ol>";
                              }else if(cityName == 16){
                                whitelistcontent = "<h4>Disconnect Instructions</h4><ol><li>Navigate to the extension bar and click on the Disconnect icon</li><li>Click the button that says \"Unblock Site\"</li></ol>";
                              }else if(cityName == 17){
                                whitelistcontent = "<h4>Opera Instructions</h4><ol><li>Navigate to the address bar and click on the blue shield icon</li><li>Click the toggle next to \"Ad Block\"  3.Click the button to \"Turn off for this site\"</li></ol>";
                              }else if(cityName == 18){
                                whitelistcontent = "<h4>Microsoft Edge Instructions</h4><ol><li>Navigate to the address bar and click on the lock icon</li><li>Click the dropdown next to the domain name of the site and select \"Off\"</li></ol>";
                              }else if(cityName == 19){
                                whitelistcontent = "<h4>Adblock Ultimate Instructions</h4><ol><li>Navigate to the extension bar and click the Adblock Ultimate icon</li><li>Click on toggle next to \"Enable this site\"</li></ol>";
                              }else if(cityName == 20){
                                whitelistcontent = "<h4>Fair AdBlocker Instructions</h4><ol><li>Navigate to the extension bar and click the Fair AdBlocker icon</li><li>Click on toggle next to \"White this site\"</li></ol>";
                              }else if(cityName == 21){
                                whitelistcontent = "<h4>Poper Blocker Instructions</h4><ol><li>Navigate to the extension bar and click the Fair AdBlocker icon</li><li>Click on toggle next to \"No blocked distractions\"</li></ol>";
                              }
                              document.getElementsByClassName("tabcontent-' . $site_id . '")[0].innerHTML = whitelistcontent ;
                        });
                    });';
                    $string .= "\r\n";
                }
                if ($lock_access > 0 && $lock_access_status == 1) {
                    $string .= "\r\n";
                    $string .= "if (getCookie('ab-hide-" . $widget_id . "') ) {";
                    $string .= "\r\n";
                    $string .= "var abhideforcount = getCookie('ab-hide-for-" . $widget_id . "');";
                    $string .= "\r\n";
                    $string .= 'abhideforcount++;';
                    $string .= "\r\n";
                    $string .= "setCookie('ab-hide-for-" . $widget_id . "',abhideforcount,9999);\r\n";
                    $string .= "\r\n";
                    $string .= "}";
                    $string .= "\r\n\r\n";

                    $string .= "\r\n";
                    $hide_duration = $lock_access_for / 1440;
                    $string .= "\r\n";
                    $string .= "if (getCookie('ab-hide-" . $widget_id . "') == '') {";
                    $string .= "\r\n";
                    $string .= "setCookie('ab-hide-" . $widget_id . "',1," . $hide_duration . ");\r\n";
                    $string .= "setCookie('ab-hide-for-" . $widget_id . "',1,9999);\r\n";
                    $string .= "}";

                    $string .= "var abBlurDisallow = " . $lock_access . ";\r\n";
                    $string .= "var tlockaccess = parseInt(abpbVisitCount) - " . $show_notice_after . ";\r\n";
                    $string .= "var abpbVisitsLeft = parseInt(abBlurDisallow) - parseInt(tlockaccess);\r\n";
                    $string .= "if(abpbVisitsLeft > 0) { var abpblefttext = abpbVisitsLeft;\r\n";
                    //if( $allow_close == 0 ) {
                    $string .= " toggleDiv(); ";
                    //}   
                    $string .= "} else {var abpblefttext = 'no '}\r\n";
                    if ($show_visits_left > 0) {
                        $string .= "document.getElementsByClassName(\"publiradpageviewsleft\")[0].innerHTML += \"<div class='ebpbvisitsleft'> You have \" +abpblefttext + \" free visits left.  </div>\";\r\n";
                    }
                    $string .= 'if ( tlockaccess >= abBlurDisallow) {';
                    $string .= "\r\n";
                    $string .= "\r\n";
                    $string .= "var abpbstyleelem = document.createElement('div');\r\n";
                    $string .= "abpbstyleelem.id=\"blurabpbstyle\"; \r\n";

                    $string .= "abpbstyleelem.innerHTML = \"<style>body > *:not(#abpbConsentModal-" . $widget_id . ",#publiradblockwhitelistmodel-" . $widget_id . ") { filter: blur(2px);-webkit-filter: blur(2px);} .abpbOverlay {position: fixed;width: 100%;height: 100%;z-index: 1000;top: 0px;left: 0px;opacity: .5; filter: alpha(opacity=50);} body{ overflow-y:hidden; }</style>\";\r\n";
                    $string .= "\r\ndocument.body.appendChild(abpbstyleelem);\r\n";
                    $string .= "\r\n var div= document.createElement(\"div\");";
                    $string .= "\r\n  div.className += \"abpbOverlay\";";
                    $string .= "\r\n document.body.appendChild(div);";
                    $string .= "\r\n if(document.getElementsByClassName('abpbConsent-close-btn')[0])  document.getElementsByClassName('abpbConsent-close-btn')[0].style.visibility = 'hidden';\r\n";
                    $string .= "\r\n";
                }

                $string .= 'var zabpb = document.getElementById("abpbConsentModal-' . $widget_id . '");';
                $string .= "\r\n";
                $string .= 'zabpb.style.display = "block";';
                $string .= "\r\n";

                if ($lock_access > 0 && $lock_access_status == 1) {
                    $string .= 'if ( abpbVisitCount < abBlurDisallow) {';
                }

                if ($allow_close == 0) {
                    $string .= "\r\n window.addEventListener(\"click\", function(e) {";
                    $string .= "\r\n\t";
                    $string .= " var abpbConsentModaldiv = document.getElementById('abpbConsentModal-" . $widget_id . "');";
                    $string .= "\r\n\t var publiradblockwhitelistmodeldiv = document.getElementById('publiradblockwhitelistmodel-" . $widget_id . "');";
                    $string .= "\r\n\t if( abpbConsentModaldiv && ( abpbConsentModaldiv.style.display == \"block\" || abpbConsentModaldiv.style.visibility == \"visible\" ) ) {";
                    $string .= "\r\n\t if ((e.target !== abpbConsentModaldiv && !abpbConsentModaldiv.contains(e.target)) && (e.target !== publiradblockwhitelistmodeldiv && !publiradblockwhitelistmodeldiv.contains(e.target))) {";
                    $string .= "\r\n";
                    $string .= 'var vabpb = document.getElementById("abpbConsentModal-' . $widget_id . '");';
                    $string .= "\r\n";
                    $string .= 'vabpb.style.display = "none";';
                    $string .= "\r\n";

                    $string .= "document.getElementById(\"blurabpbstyle\").innerHTML = \" \";\r\n";
                    $string .= "\r\n";
                    $string .= "document.getElementsByClassName(\"abpbOverlay\")[0].remove();";
                    $string .= "\r\n";
                    $string .= "\r\n";
                    $string .= '}';
                    $string .= "\r\n";
                    $string .= '}';
                    $string .= "\r\n";
                    $string .= '});';
                    $string .= "\r\n";
                }

                if ($lock_access > 0 && $lock_access_status == 1) {
                    $string .= '}';
                    $string .= "};\r\n";
                }

                $string .= "abpbConsentFunction('" . $widget_id . "');\r\n";

                $string .= "}\r\n";
                $string .= "};\r\n";
                $string .= 'xhttp.open("GET", "https://fkrkkmxsqeb5bj9r.s3.amazonaws.com/mks.js", true);';
                $string .= "\r\nxhttp.send();\r\n";

                //$string .= '});';
                $string .= "\r\n";
                $string .= "} \r\n";

                $string .= "\r\n window.addEventListener('click', (e) => {
                    var abpbConsentModaldiv = document.getElementById('abpbConsentModal-" . $widget_id . "');
                    const contain = abpbConsentModaldiv.contains(e.target);
                    if(contain && e.target?.tagName.toLowerCase() == 'a') {
                        const linkClickData = { p_currentPage, p_siteId, p_widgetId, p_linkClick: 1 };
                        storeEventData(linkClickData);
                    }
                }, false)";

                if ($hide_notice_for > 0) {
                    $string .= "} \r\n";
                }
                $string .= "}}\r\n";

                $string .= "\r\n function storeEventData(formData) {
                    console.log('store click event function call', formData);
                    var url = 'https://jhgr7v0dzc.execute-api.us-east-1.amazonaws.com/default/abAnalytics';
                    fetch(url, {
                    	method: 'POST',
                    	mode: 'no-cors',
                    	body: JSON.stringify(formData),
                    	credentials: 'include',
                    	headers: { 'Content-Type': 'text/plain' },
                    });
                }";

                if ($lock_access > 0 && $lock_access_status == 1) {
                    $string .= "function toggleDiv() { \r\n\t window.addEventListener(\"click\", function(e) {
                        \r\n\t\t var abpbConsentModaldiv = document.getElementById('abpbConsentModal-" . $widget_id . "'); 
                        \r\n\t\t var publiradblockwhitelistmodeldiv = document.getElementById('publiradblockwhitelistmodel-" . $widget_id . "');";
                    $string .= "if( abpbConsentModaldiv && ( abpbConsentModaldiv.style.display == \"block\" || abpbConsentModaldiv.style.visibility == \"visible\" ) ) {";
                    if ($notice_location == "popup") {

                        $string .= " var publiradbmodal = document.getElementsByClassName('abpbConsent-modal-content')[0];
                            if ((e.target !== publiradbmodal && !publiradbmodal.contains(e.target)) && (e.target !== publiradblockwhitelistmodeldiv && !publiradblockwhitelistmodeldiv.contains(e.target))) { ";
                        $string .= "\r\n\t\t var vabpb = document.getElementById(\"abpbConsentModal-" . $widget_id . "\");
                                    \r\n\t\t vabpb.style.display = \"none\";
                                    if( document.getElementById(\"blurabpbstyle\") )
                                    \r\n\t\t\t document.getElementById(\"blurabpbstyle\").innerHTML = \" \";
                                    \r\t\t\t document.getElementsByClassName(\"abpbOverlay\")[0].remove(); }";
                    } else {
                        $string .= "\r\n\t\t if ((e.target !== abpbConsentModaldiv && !abpbConsentModaldiv.contains(e.target)) && (e.target !== publiradblockwhitelistmodeldiv && !publiradblockwhitelistmodeldiv.contains(e.target))) {
                            \r\n\t\t var vabpb = document.getElementById(\"abpbConsentModal-" . $widget_id . "\");
                            \r\n\t\t vabpb.style.display = \"none\";
                            \r\n\t\t if( document.getElementById(\"blurabpbstyle\") )
                            \r\n\t\t\t document.getElementById(\"blurabpbstyle\").innerHTML = \" \";
                            \r\t\t\t document.getElementsByClassName(\"abpbOverlay\")[0].remove();
                            \r\n\t\t }";
                    }
                    $string .= "}";
                    $string .= "\r\n }); } \r\n";
                }
                if ($lock_access == 0 && $lock_access_status == '0') {
                    $string .= "window.addEventListener(\"click\", function(e) {
                        \r\n\t\t var abpbConsentModaldiv = document.getElementById('abpbConsentModal-" . $widget_id . "'); 
                        \r\n\t\t var publiradblockwhitelistmodeldiv = document.getElementById('publiradblockwhitelistmodel-" . $widget_id . "');";
                    $string .= "\r\n\t\t if( abpbConsentModaldiv && ( abpbConsentModaldiv.style.display == \"block\" || abpbConsentModaldiv.style.visibility == \"visible\" ) ) {";
                    if ($notice_location == "popup") {
                        $string .= " var publiradbmodal = document.getElementsByClassName('abpbConsent-modal-content')[0];
                            if ((e.target !== publiradbmodal && !publiradbmodal.contains(e.target)) && (e.target !== publiradblockwhitelistmodeldiv && !publiradblockwhitelistmodeldiv.contains(e.target))) { ";
                        $string .= "\r\n\t\t var vabpb = document.getElementById(\"abpbConsentModal-" . $widget_id . "\");
                                    \r\n\t\t vabpb.style.display = \"none\";
                                    if( document.getElementById(\"blurabpbstyle\") )
                                    \r\n\t\t\t document.getElementById(\"blurabpbstyle\").innerHTML = \" \";
                                    \r\t\t\t document.getElementsByClassName(\"abpbOverlay\")[0].remove(); }";
                    } else {
                        $string .= "\r\n\t\t if ((e.target !== abpbConsentModaldiv && !abpbConsentModaldiv.contains(e.target)) && (e.target !== publiradblockwhitelistmodeldiv && !publiradblockwhitelistmodeldiv.contains(e.target))) {
                        \r\n\t\t var vabpb = document.getElementById(\"abpbConsentModal-" . $widget_id . "\");
                        \r\n\t\t vabpb.style.display = \"none\";
                        \r\n\t\t if( document.getElementById(\"blurabpbstyle\") )
                        \r\n\t\t\t document.getElementById(\"blurabpbstyle\").innerHTML = \" \";
                        \r\t\t\t document.getElementsByClassName(\"abpbOverlay\")[0].remove();
                        \r\n\t\t }";
                    }
                    $string .= "}";
                    $string .= "\r\n }); \r\n";
                }

                $string .= 'function abpbclostbtn(widget_id){';
                $string .= "\r\n";
                $string .= 'var uabpb = document.getElementById(`abpbConsentModal-${widget_id}`);';
                $string .= "\r\n";
                $string .= 'uabpb.style.display = "none";';
                $string .= "\r\n";
                $string .= "if( document.getElementById(\"blurabpbstyle\") ) {";
                $string .= 'document.getElementById("blurabpbstyle").innerHTML = " ";';
                $string .= '}';
                $string .= "\r\n";
                $string .= '}';
                $string .= "\r\n";
                $string .= "
                    async function getCountryName() 
                    {
                        let response = await fetch('https://ipinfo.io/json?token=1c0e9fdac23c75');
                        let data = await response.json()
                        return data;
                    }
                ";
                $string .= "
                    window.addEventListener('load', loadPreset, true);
                    async function loadPreset(){
                        var countries = " . json_encode($countries) . "
                        var browsers = " . json_encode($browsers) . "
                        var desktop_preview = " . $desktop_preview . "
                        var tablet_preview = " . $tablet_preview . "
                        var mobile_preview = " . $mobile_preview . "
                        var start_immediately = " . $start_immediately . "
                        var start_with_enddate = " . $start_with_enddate . "
                        var schedule_preset = " . json_encode($schedule_preset) . "
                        var prior_end_date = " . json_encode($prior_end_date) . "
                    
                        var countryData = await getCountryName();
                        var code = countryData.country;
                        console.log('code ->', code);
                        // const regionNames = new Intl.DisplayNames(['en'], {type: 'region'});
                        // var countryName = regionNames.of(code);

                        var browserName = (function (agent) {
                            switch (true) {
                                case agent.indexOf('edge') > -1: return 'Edge';
                                case agent.indexOf('edg/') > -1: return 'Chromium';
                                case agent.indexOf('opr/') > -1 && !!window.opr: return 'Opera';
                                case agent.indexOf('chrome/') > -1 && !!window.chrome: return 'Chrome';
                                case agent.indexOf('trident/') > -1: return 'Internet Explorer';
                                case agent.indexOf('firefox/') > -1: return 'Firefox';
                                case agent.indexOf('safari/') > -1: return 'Safari';
                                default: return 'other';
                            }
                        })(window.navigator.userAgent.toLowerCase());
                        console.log('browserName -->', browserName);

                        let details = navigator.userAgent.toLowerCase();
                        let regexp = /android|iphone|kindle|ipad/i;
                        let isMobileDevice = regexp.test(details);
                        let isDesktopDevice = !regexp.test(details);
                        let isTabletDevice = /(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/.test(details);

                        if((countries.includes(code) || countries.includes('All')) && (browsers.includes(browserName) || browsers.includes('All')) && (desktop_preview || tablet_preview || mobile_preview)) {
                            console.log(true);
                            var isValid = false;
                            if ( start_immediately == 1 && start_with_enddate == 0 ) {
                                isValid = true;
                            } else if ( start_immediately == 0 && schedule_preset.length ) {
                                const isValidDate = schedule_preset.filter(element => {
                                    const startDate = new Date(element.start_date).getTime();
                                    const endDate = new Date(element.end_date).getTime();
                                    if(new Date().getTime() >= startDate, new Date().getTime() <= endDate) return true;
                                })
                                if(isValidDate.length) isValid = true;
                            } else if ( start_immediately == 1 && start_with_enddate == 1 && prior_end_date ) {
                            if(new Date().getTime() <= new Date(prior_end_date).getTime() ) isValid = true;
                            const isValidDate = schedule_preset.filter(element => {
                                const startDate = new Date(element.start_date).getTime();
                                const endDate = new Date(element.end_date).getTime();
                                if(new Date().getTime() >= startDate, new Date().getTime() <= endDate) return true;
                            })
                            if(isValidDate.length) isValid = true;
                            } else { isValid = false; }

                            if(isValid) {
                                if(desktop_preview && desktop_preview == isDesktopDevice) firstAsync" . $widget_id . "();
                                if(tablet_preview && tablet_preview == isTabletDevice) firstAsync" . $widget_id . "();
                                if(mobile_preview && mobile_preview == isMobileDevice) firstAsync" . $widget_id . "();
                            }
                        } else {
                            console.log(false);
                        }
                    }";
                // widget preview
                if ($adblockPreview == 1) {
                    $string .= "};";
                }
            } else {
                $string = "";
            }

            // Storage::disk('public')->put("adblock_files/" . $site_id . "-" . $widget_id . ".js", $string);
            // $script_link = $this->getUrl("adblock_files/" . $site_id . "-" . $widget_id . ".js");
            
            $fileName = $site_id . "-" . $widget_id . ".js";
            $script_link = $this->storeFile($fileName, $string);

            $adBlock = AdBlocks::where('widget_id', $widget_id)->first();
            if ($adBlock) {
                if($adBlock['script_link']) {
                    // $this->deleteScriptFile($adBlock['script_link']);
                }
                AdBlocks::where('widget_id', $widget_id)->update(['script_link' => $script_link]);
            }
            $adBlockScript = $this->getScriptBySiteIdQuery($site_id);
            return $adBlockScript;
        }
    }

    public function getUrl($url)
    {
        return URL::to(Storage::url($url));
    }

    public function storeFile($fileName, $string)
    {
        Storage::disk('s3')->put($fileName, $string);
        $script_link = Storage::disk('s3')->url($fileName);
        return $script_link;
    }

    public function getScriptBySiteIdQuery($site_id)
    {
        $adBlockScript = AdBlocks::query();
        $adBlockScript = $adBlockScript->where([
            ['site_id', '=', $site_id],
            ['status', '=', 1],
        ])->pluck('script_link')->toArray();

        $string = "";
        $string = "function scriptLoader(path, callback)
        {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.src = path;
            script.onload = function(){
                if(typeof(callback) == 'function')
                {
                    callback();
                }
            }
            try
            {
                var scriptOne = document.getElementsByTagName('script')[0];
                scriptOne.parentNode.insertBefore(script, scriptOne);
            }
            catch(e)
            {
                document.getElementsByTagName('head')[0].appendChild(script);
            }
        }\r\n\n";
        foreach ($adBlockScript as $value) {
            $string .= "scriptLoader('" . $value . "')\r\n";
        }

        /* delete file by site id */
        // if(Storage::disk('public')->exists("adblock_files/" . $site_id . ".js")) {
        //     Storage::disk('public')->delete("adblock_files/" . $site_id . ".js");
        // }

        // Storage::disk('public')->put("adblock_files/" . $site_id . ".js", $string);
        // $script_link = $this->getUrl("adblock_files/" . $site_id . ".js");

        $fileName = $site_id . ".js";
        $script_link = $this->storeFile($fileName, $string);
        return $script_link;
    }

    public function deletePresetByWidgetIdQuery($widget_id)
    {
        $adBlock = AdBlocks::where('widget_id', $widget_id)->select('site_id', 'script_link')->first();
        if($adBlock) {
            $site_id = $adBlock['site_id'];
            $script_link = $adBlock['script_link'];
            if($script_link) {
                $this->deleteScriptFile($script_link);
            }
        }
        AdBlockStyles::where('widget_id', $widget_id)->delete();
        AdBlockSchedules::where('widget_id', $widget_id)->delete();
        $presetData = AdBlocks::where('widget_id', $widget_id)->delete();
        
        $this->getScriptBySiteIdQuery($site_id);
        return $presetData;
    }

    public function deleteScriptFile($script_link)
    {
        $file = explode("/", $script_link);
        $result = end($file);
        
        $s3 = Storage::disk('s3');
        if($s3->exists($result)) {
            $s3->delete($result);
        }
        // if(Storage::disk('public')->exists("adblock_files/" . $result )) {
        //     Storage::disk('public')->delete("adblock_files/" . $result );
        // }
    }
}
