<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OldNetworks;
use App\Models\OldNetworkReportsLogs;
use Illuminate\Support\Facades\Validator;
use App\Models\OldDailyReport;
use App\Models\Sites;
use App\Models\OldNetworkSites;
use App\Models\OldNetworkSizes;

class PrebidUploadDataController extends Controller
{
  /**
   * Prebid Apis.
   */
  //Get Network apis
  public function network_api()
  {
    $networks = OldNetworks::select('id', 'network_name')->where('status', '1')->get();
    return response()->json(['message' => 'Network data get successfully', 'status' => true, 'networks' => $networks]);
  }

  public function get_failed_rows(Request $request)
  {
    $validator  = Validator::make($request->all(), [
      'id'      => 'required',
      'date'    => 'required',
    ]);

    if ($validator->fails()) {
      $errors = $validator->errors();
      return response()->json(['message' => $errors, 'status' => false], 403);
    }

    $id = $request->input('id');
    $date = $request->input('date');
    $site_alias_id = $request->input('site_alias_id');
    $site_alias = explode(',',$site_alias_id);
    $size_alias_id = $request->input('size_alias_id');
    $size_alias = explode(',', $size_alias_id);

    // print_r($size_alias);die;
    $total_impressions = 0;
    $total_revenue = 0;
    $total_clicks = 0;
    $sites_sizes_status = [];

    //Get sum of Impressions, Revenue and Clicks
    $total_impressions = OldDailyReport::where('network_id', $id)->where('date', $date)->sum('impressions');
    $total_revenue = OldDailyReport::where('network_id', $id)->where('date', $date)->sum('revenue');
    $total_clicks = OldDailyReport::where('network_id', $id)->where('date', $date)->sum('clicks');

    $im_daily_reports = OldDailyReport::select('id', 'date', 'site_id', 'size_id', 'site_alias_id', 'size_alias_id', 'network_id', 'impressions', 'revenue', 'clicks', 'type', 'status', 'created_at', 'updated_at')->where('network_id', $id)->where('date', $date);

    $dt_network_reports_logs = OldNetworkReportsLogs::where('network_id', $id)->where('date', $date)->where('status', '0');

    $site_alias_id = [];
    if(isset($site_alias) && !empty($site_alias)){
      foreach($site_alias as $site => $val){
        $site_alias_exists = OldNetworkSites::select('id')->where('network_id', $id)->where('site_alias', $val);
        $site_alias_exists_count = $site_alias_exists->count();
        // print_r($site_alias_exists_count);
        // $site_alias_exists = ;
        // echo $site.'=>'.$val;echo "<br>";
        if($site_alias_exists_count > 0){
          $site_alias_id[] = $site_alias_exists->first()->id;
          // echo $site_alias_id;
        }
      }
    }
    else{
      $site_alias_id = 0;
    }

    // print_r($site_alias_id);die;

    $size_alias_id = [];
    if(isset($size_alias) && !empty($size_alias)){
      foreach($size_alias as $size => $val){
        // echo $size.'=>'.$val;echo "<br>";
        $size_alias_exists = OldNetworkSizes::select('id')->where('network_id', $id)->where('size_alias', $val);
        // $size_alias_exists = ;
        if($size_alias_exists->count() > 0){
          $size_alias_id[] = $size_alias_exists->first()->id;
        }
      }
    }
    else{
      $size_alias_id = 0;
    }
    // print_r($size_alias_id);die;
    
    if(!empty($site_alias_id) && !empty($size_alias_id)){
      $im_daily_reports = $im_daily_reports->whereIn('site_alias_id', $site_alias_id)->whereIn('size_alias_id', $size_alias_id);
      $dt_network_reports_logs = $dt_network_reports_logs->whereIn('site_alias_id', $site_alias_id)->whereIn('size_alias_id', $size_alias_id);
    }
    else if(!empty($site_alias_id) && empty($size_alias_id)){
      $im_daily_reports = $im_daily_reports->whereIn('site_alias_id', $site_alias_id);
      $dt_network_reports_logs = $dt_network_reports_logs->whereIn('site_alias_id', $site_alias_id);
    }
    else if(empty($site_alias_id) && !empty($size_alias_id)){
      $im_daily_reports = $im_daily_reports->whereIn('size_alias_id', $size_alias_id);
      $dt_network_reports_logs = $dt_network_reports_logs->whereIn('size_alias_id', $size_alias_id);
    }

    $failed_data = $dt_network_reports_logs->orderBy('site_id', 'asc')->orderBy('size_id', 'asc')->get();
    $uploaded_data = $im_daily_reports->get();

    // echo "<pre>"; print_r($uploaded_data); die;

    foreach ($uploaded_data as $ky => $val) {
      $site_id                         = $val['site_id'];
      $size_id                         = $val['size_id'];
      $site_alias_id                   = $val['site_alias_id'];
      $size_alias_id                   = $val['size_alias_id'];
      $sitename                        = self::site_alises($id, $site_id, $site_alias_id);
      $uploaded_data[$ky]['site_name'] = $sitename;
      $sizename                        = self::size_alises($id, $size_id, $size_alias_id);
      $uploaded_data[$ky]['size_name'] = $sizename;
    }

    foreach ($failed_data as $ky => $val) {
      $site_id = $val['site_id'];
      $size_id = $val['size_id'];
      if ($site_id <= 0 && $size_id > 0) {
        $sites_sizes_status[$val->id]['site'] = 0;
        $sites_sizes_status[$val->id]['size'] = 1;
      } else if ($site_id > 0 && $size_id <= 0) {
        $sites_sizes_status[$val->id]['site'] = 1;
        $sites_sizes_status[$val->id]['size'] = 0;
      }
      else if ($site_id <= 0 && $size_id <= 0) {
        $sites_sizes_status[$val->id]['site'] = 0;
        $sites_sizes_status[$val->id]['size'] = 0;
      }
      else{
        $sites_sizes_status[$val->id]['site'] = 1;
        $sites_sizes_status[$val->id]['size'] = 1;
      }
    }

    return response()->json(['message' => 'Failed rows get successfully', 'status' => true, 'failed_data' => $failed_data, 'uploaded_data' => $uploaded_data, 'sites_sizes_status' => $sites_sizes_status, 'total_impressions' => $total_impressions, 'total_revenue' => $total_revenue, 'total_clicks' => $total_clicks]);
  }

  //Upload failed data
  public function insert_failed_rows(Request $request)
  {
    $data         = $request->all();
    $data_count   = count($data);
    $failed_count = 0;
    $insert_data  = [];
    $success_rows = [];
    $sites_sizes_status = [];

    for ($i = 0; $i < $data_count; $i++) {
      $id         = $data[$i]['id'];
      $site_name  = $data[$i]['site_name'];
      $size_name  = $data[$i]['size_name'];
      $network_id = $data[$i]['network_id'];
      $status = $data[$i]['status'];
      $site_alias_id = $data[$i]['site_alias_id'];
      $size_alias_id = $data[$i]['size_alias_id'];

      if($status == '0'){
        $insert_data['date']        = $data[$i]['date'];
        $insert_data['network_id']  = $network_id;
        $insert_data['impressions'] = $data[$i]['impressions'];
        $insert_data['revenue']     = $data[$i]['revenue'];
        $insert_data['clicks']      = $data[$i]['clicks'];
        $insert_data['type']        = $data[$i]['type'];

        $site_exists = OldNetworkSites::select('id','site_id')->where('network_id', $network_id)->where('site_alias', $site_name)->get();
        $size_exists = OldNetworkSizes::select('id','size_id')->where('network_id', $network_id)->where('size_alias', $size_name)->get();
        $siteCount = $site_exists->count();
        $sizeCount = $size_exists->count();

        if ($siteCount <= 0 && $sizeCount > 0) {
          $sites_sizes_status[$id]['site'] = 0;
          $sites_sizes_status[$id]['size'] = 1;
          $failed_count = $failed_count + 1;
          $success_rows[] = 2;
        } else if ($siteCount > 0 && $sizeCount <= 0) {
          $sites_sizes_status[$id]['site'] = 1;
          $sites_sizes_status[$id]['size'] = 0;
          $failed_count = $failed_count + 1;
          $success_rows[] = 2;
        } else if ($siteCount > 0 && $sizeCount > 0) {
          $site_id = $site_exists[0]['site_id'];
          $size_id = $size_exists[0]['size_id'];
          $site_alias_id = $site_exists[0]['id'];
          $size_alias_id = $size_exists[0]['id'];

          if ($site_id > 0 && $size_id > 0) {
            //Check data is already existing in im daily reports or not
            $check_data_exists = OldDailyReport::select('id')->where('date', $insert_data['date'])->where('network_id', $network_id)->where('site_id', $site_id)->where('size_id', $size_id)->where('site_alias_id', $site_alias_id)->where('size_alias_id', $size_alias_id)->get();
            $checkDataCount = $check_data_exists->count();
            if ($checkDataCount > 0) {
              $overwrite_existing_data = OldDailyReport::where('date', $insert_data['date'])->where('network_id', $network_id)->where('site_id', $site_id)->where('size_id', $size_id)->where('site_alias_id', $site_alias_id)->where('size_alias_id', $size_alias_id)->update(['impressions' => $insert_data['impressions'], 'clicks' => $insert_data['clicks'], 'revenue' => $insert_data['revenue']]);
              if ($overwrite_existing_data) {
                $delete_log_data = OldNetworkReportsLogs::where('id', $id)->where('network_id', $network_id)->update(['site_name' => $site_name, 'size_name' => $size_name, 'site_id' => $site_id, 'size_id' => $size_id, 'site_alias_id' => $site_alias_id, 'size_alias_id' => $size_alias_id, 'status' => '1']);
                $success_rows[] = 1;
              } else {
                $failed_count = $failed_count + 1;
                $success_rows[] = 2;
              }
            } else {
              if ($site_id > 0 && $size_id > 0) {
                $insert_data['site_id']     = $site_id;
                $insert_data['size_id']     = $size_id;
                $insert_data['site_alias_id']     = $site_alias_id;
                $insert_data['size_alias_id']     = $size_alias_id;
                $data_insert  = OldDailyReport::create($insert_data);
                if ($data_insert) {
                  $sites_sizes_status[$id]['site'] = 1;
                  $sites_sizes_status[$id]['size'] = 1;
                  $delete_log_data = OldNetworkReportsLogs::where('id', $id)->where('network_id', $network_id)->update(['site_name' => $site_name, 'size_name' => $size_name, 'site_id' => $site_exists[0]['site_id'], 'size_id' => $size_exists[0]['size_id'], 'site_alias_id' => $site_exists[0]['id'], 'size_alias_id' => $size_exists[0]['id'], 'status' => '1']);
                  $success_rows[] = 1;
                } else {
                  $failed_count = $failed_count + 1;
                  $success_rows[] = 2;
                }
              }
            }
          } else {
            $failed_count = $failed_count + 1;
            $success_rows[] = 2;
          }
        } else {
          $delete_log_data = OldNetworkReportsLogs::where('id', $id)->where('network_id', $network_id)->update(['site_name' => $site_name, 'size_name' => $size_name, 'status' => '0']);
          $sites_sizes_status[$id]['site'] = 0;
          $sites_sizes_status[$id]['size'] = 0;
          $failed_count = $failed_count + 1;
          $success_rows[] = 2;
        }
      }
    }
    
    if (in_array("2", $success_rows)) {
      return response()->json(['message' => $failed_count . ' row(s) failed to load', 'status' => false, 'sites_sizes_status' => $sites_sizes_status]);
    } else if (in_array("1", $success_rows)) {
      return response()->json(['message' => 'Data inserted successfully', 'status' => true]);
    } else {
      return response()->json(['message' => 'Data not inserted', 'status' => false, 'sites_sizes_status' => $sites_sizes_status]);
    }
  }

  //Upload data through CSV file
  public function insert_failed_rows_csv(Request $request)
  {
    $result = OldNetworks::where('status', '1')->where('upload_form', '1')->get();
    for ($i = 0; $i < $result->count(); $i++) {
      $networks_upload_name[$result[$i]['id']] = $result[$i]['upload_name'];
      $networks_upload_imp[$result[$i]['id']] = $result[$i]['upload_imp_column'];
      $networks_upload_rev[$result[$i]['id']] = $result[$i]['upload_rev_column'];
      $networks_upload_rev_split[$result[$i]['id']] = $result[$i]['net_revenue_split'];
      $networks_upload_click[$result[$i]['id']] = $result[$i]['upload_click_column'];
      $networks_upload_separator[$result[$i]['id']] = $result[$i]['upload_separator'];
      $networks_upload_site[$result[$i]['id']] = $result[$i]['upload_site_column'];
      $networks_upload_site_separator[$result[$i]['id']] = $result[$i]['upload_site_separator'];
      $networks_upload_site_string[$result[$i]['id']] = $result[$i]['upload_site_string'];
      $networks_upload_size[$result[$i]['id']] = $result[$i]['upload_size_column'];
      $networks_upload_size_separator[$result[$i]['id']] = $result[$i]['upload_size_separator'];
      $networks_upload_size_string[$result[$i]['id']] = $result[$i]['upload_size_string'];

      $result2 = OldNetworkSites::where('network_id', $result[$i]['id'])->get();
      for ($j = 0; $j < $result2->count(); $j++) {
        $networks_sites_array[$result[$i]['id']][$result2[$j]['site_alias']] = $result2[$j]['site_id'];
        $networks_sites_alias_array[$result[$i]['id']][$result2[$j]['site_alias']] = $result2[$j]['id'];
      }

      $result3 = OldNetworkSizes::where('network_id', $result[$i]['id'])->get();
      for ($k = 0; $k < $result3->count(); $k++) {
        $networks_sizes_array[$result[$i]['id']][$result3[$k]['size_alias']] = $result3[$k]['size_id'];
        $networks_sizes_alias_array[$result[$i]['id']][$result3[$k]['size_alias']] = $result3[$k]['id'];
      }
    }

    if ($request->hasFile('csvfile')) {
      $file = $request->file('csvfile')->getClientOriginalName();
      $file_id = array_search($file, $networks_upload_name);
      $network_id = $file_id;
      $networkid  = $request->input('network_id');
      $date = $request->input('date');
      if ($network_id != $networkid) {
        return response()->json(['message' => 'Please make sure the upload ' . $file . ' is a valid file for this network', 'status' => false], 500);
      }
      if ($file_id == "") {
        return response()->json(['message' => 'Please make sure the upload ' . $file . ' is a valid file for network', 'status' => false], 500);
      } else {
        $type = explode(".", $file);
        if (strtolower(end($type)) != 'csv') {
          return response()->json(['message' => 'Please make sure the upload ' . $file . ' is a csv file', 'status' => false], 500);
        } else {
          $allocated_imps = 0;
          $allocated_rev = 0;
          $allocated_clicks = 0;
          $im_impressions = 0;
          $im_revenue = 0;
          $im_clicks = 0;

          $networkid  = $request->input('network_id');
          $filename   = $request->file('csvfile')->getRealPath();
          $result      = fopen($filename, "r");
          $row_array = array();
          $i = 0;
          while (($data1 = fgetcsv($result, 200, ",")) !== FALSE) {
            $row_array[$i] = $data1;
            $i++;
          }

          $failed_count = 0;
          $success_count = 0;
          $insert_data  = [];
          $success_rows = [];

          for ($j = 0; $j < count($row_array); $j++) {
            $allocated_imps = 0;
            $allocated_rev = 0;
            $allocated_clicks = 0;
            $im_impressions = 0;
            $im_revenue = 0;
            $im_clicks = 0;

            if ($j > 0) {
              if ($networks_upload_site[$file_id] - 1 >= 0) {
                $disp_cell = trim(preg_replace('/\s+/', ' ', $row_array[$j][$networks_upload_site[$file_id] - 1]));
                if (isset($networks_upload_site_separator[$file_id]) and $networks_upload_site_separator[$file_id] != "none") {
                  $site_size_data = explode($this->separator_values_array($networks_upload_site_separator[$file_id]), $disp_cell);
                  $req_count = 7 - count($site_size_data);
                  for ($k = 0; $k < $req_count; $k++) {
                    array_push($site_size_data, "");
                  }
                  $site_data_str = $networks_upload_site_string[$file_id];
                  $site_data1 = eval('return ' . $site_data_str . ';');
                  $site_data = strtolower($site_data1);
                } else {
                  $site_data = $site_data1 = $disp_cell;
                }
                $all_network_keys = array_change_key_case($networks_sites_array[$file_id]);
                if (array_key_exists($site_data, $all_network_keys)) {
                  $site_id_p = $all_network_keys[trim($site_data)];
                } else {
                  $site_id_p = 0;
                }
                
                $all_network_site_alias_keys = array_change_key_case($networks_sites_alias_array[$file_id]);
                if (array_key_exists($site_data, $all_network_site_alias_keys)) {
                  $site_alias_id_p = $all_network_site_alias_keys[trim($site_data)];
                } else {
                  $site_alias_id_p = 0;
                }
              }
              if ($networks_upload_size[$file_id] - 1 >= 0) {
                $disp_cell = trim(preg_replace('/\s+/', ' ', $row_array[$j][$networks_upload_size[$file_id] - 1]));
                if (isset($networks_upload_size_separator[$file_id]) and $networks_upload_size_separator[$file_id] != "none") {
                  $site_size_data = explode($this->separator_values_array($networks_upload_size_separator[$file_id]), $disp_cell);
                  $req_count = 7 - count($site_size_data);

                  for ($k = 0; $k < $req_count; $k++) {
                    array_push($site_size_data, "");
                  }

                  $size_data_str = $networks_upload_size_string[$file_id];
                  $size_data = eval('return ' . $size_data_str . ';');
                } else {
                  $size_data = $disp_cell;
                }
                if (array_key_exists($size_data, $networks_sizes_array[$file_id])) {
                  $size_id_p = $networks_sizes_array[$file_id][trim($size_data)];
                } else {
                  $size_id_p = 0;
                }
                if (array_key_exists($size_data, $networks_sizes_alias_array[$file_id])) {
                  $size_alias_id_p = $networks_sizes_alias_array[$file_id][trim($size_data)];
                } else {
                  $size_alias_id_p = 0;
                }
              }
              if ($networks_upload_imp[$file_id] - 1 >= 0) {
                $im_impressions = $row_array[$j][$networks_upload_imp[$file_id] - 1];
                $im_impressions = str_replace('"', '', $im_impressions);
                $im_impressions = str_replace(',', '', $im_impressions);
              }
              if ($networks_upload_rev[$file_id] - 1 >= 0) {
                $im_revenue = trim($row_array[$j][$networks_upload_rev[$file_id] - 1]);
                $im_revenue = str_replace('US $', '', $im_revenue);
                $im_revenue = str_replace('$', '', $im_revenue);
                $im_revenue = str_replace(',', '', $im_revenue);
                $im_revenue = (float)($im_revenue);
                $im_revenue = round(($im_revenue * $networks_upload_rev_split[$file_id]) / 100, 2);
              }
              if ($networks_upload_click[$file_id] - 1 >= 0) {
                if ($networks_upload_click[$file_id] > 0) {
                  $im_clicks = $row_array[$j][$networks_upload_click[$file_id] - 1];
                } else {
                  $im_clicks = 0;
                }
                $im_clicks = str_replace('"', '', $im_clicks);
                $im_clicks = str_replace(',', '', $im_clicks);
              }

              $site_name  = $site_data1;
              $size_name  = $size_data;
              $network_id = $file_id;
              $insert_data['date']        = $date;
              $insert_data['network_id']  = $network_id;
              $insert_data['impressions'] = $im_impressions;
              $insert_data['revenue']     = $im_revenue;
              $insert_data['clicks']      = $im_clicks;
              $insert_data['type']        = $site_name . '-' . $size_name;
              $insert_data['site_name']   = $site_name;
              $insert_data['size_name']   = $size_name;

              //////Insert Failed Data///////////////
              $insert_failed_data['date']        = $date;
              $insert_failed_data['network_id']  = $network_id;
              $insert_failed_data['impressions'] = $im_impressions;
              $insert_failed_data['revenue']     = $im_revenue;
              $insert_failed_data['clicks']      = $im_clicks;
              $insert_failed_data['type']        = $site_name . '-' . $size_name;
              $insert_failed_data['site_name']   = $site_name;
              $insert_failed_data['size_name']   = $size_name;

              $site_exists = OldNetworkSites::select('id','site_id')->where('network_id', $network_id)->where('site_alias', $site_name)->get();
              $size_exists = OldNetworkSizes::select('id','size_id')->where('network_id', $network_id)->where('size_alias', $size_name)->get();
              $siteCount = $site_exists->count();
              $sizeCount = $size_exists->count();

              if ($siteCount > 0 && $sizeCount > 0) {
                $site_id = $site_exists[0]['site_id'];
                $size_id = $size_exists[0]['size_id'];
                $site_alias_id = $site_exists[0]['id'];
                $size_alias_id = $size_exists[0]['id'];

                $insert_failed_data['site_id'] = $site_id;
                $insert_failed_data['size_id'] = $size_id;
                $insert_failed_data['site_alias_id'] = $site_alias_id;
                $insert_failed_data['size_alias_id'] = $size_alias_id;
               
                //Check data is already existing in network reports logs or not
                $check_data_exists = OldNetworkReportsLogs::select('id')->where('date', $insert_data['date'])->where('network_id', $network_id)->where('site_id', $site_exists[0]['site_id'])->where('size_id', $size_exists[0]['size_id'])->where('site_alias_id', $site_exists[0]['id'])->where('size_alias_id', $size_exists[0]['id'])->get();
                $checkDataCount = $check_data_exists->count();
                if ($checkDataCount > 0) {
                  $overwrite_existing_data = OldNetworkReportsLogs::where('date', $insert_data['date'])->where('network_id', $network_id)->where('site_id', $site_exists[0]['site_id'])->where('size_id', $size_exists[0]['size_id'])->where('site_alias_id', $site_exists[0]['id'])->where('size_alias_id', $size_exists[0]['id'])->update(['impressions' => $insert_data['impressions'], 'clicks' => $insert_data['clicks'], 'revenue' => $insert_data['revenue'], 'site_name' => $insert_data['site_name'], 'size_name' => $insert_data['size_name']]);
                  if ($overwrite_existing_data) {
                    $success_count += 1;
                  }
                  $success_rows[] = 2;
                } 
                else if ($site_id > 0 && $size_id > 0) {
                  $data_insert  = OldNetworkReportsLogs::create($insert_failed_data);
                  if ($data_insert) {
                    $success_rows[] = 1;
                    $success_count += 1;
                  } else {
                    $failed_log_data = OldNetworkReportsLogs::create($insert_failed_data);
                    $success_count = $success_count + 1;
                    $success_rows[] = 2;
                  }
                }
                else {
                  $failed_log_data = OldNetworkReportsLogs::create($insert_failed_data);
                  $success_count = $success_count + 1;
                  $success_rows[] = 2;
                }
              } else {
                $insert_failed_data['site_id'] = $site_id_p;
                $insert_failed_data['size_id'] = $size_id_p;
                $insert_failed_data['site_alias_id'] = $site_alias_id_p;
                $insert_failed_data['size_alias_id'] = $size_alias_id_p;

                //Check failed data is already existing in failed table or not
                $check_failed_data_exists = OldNetworkReportsLogs::select('id')->where('date', $insert_failed_data['date'])->where('network_id', $network_id)->where('type', $insert_failed_data['type'])->where('site_id', $insert_failed_data['site_id'])->where('size_id', $insert_failed_data['size_id'])->where('site_alias_id', $insert_failed_data['site_alias_id'])->where('size_alias_id', $insert_failed_data['size_alias_id'])->get();
                $checkFailedDataCount = $check_failed_data_exists->count();
                if ($checkFailedDataCount <= 0) {
                  $failed_log_data = OldNetworkReportsLogs::create($insert_failed_data);
                  $success_count = $success_count + 1;
                  $success_rows[] = 2;
                } else {
                  $success_count = $success_count + 1;
                  $success_rows[] = 2;
                }
              }
            }
          }

          $message = "";
          if ($success_count > 0) {
            $message .= $success_count . ' rows loaded for review in pending status. Review and Upload.';
          }

          if (in_array("2", $success_rows)) {
            return response()->json(['message' => $message, 'status' => false], 500);
          } else if (in_array("1", $success_rows)) {
            return response()->json(['message' => $message, 'status' => true], 200);
          } else {
            return response()->json(['message' => 'Data not inserted', 'status' => false], 500);
          }
        }
      }
    } else {
      return response()->json(['message' => 'Please select valid CSV file', 'status' => false], 500);
    }
  }

  //Network Sites
  public function network_sites_sizes(Request $request)
  {
    $network_id    = $request->id;
    $network_sites = OldNetworkSites::where('network_id', $network_id)->get();
    $network_sizes = OldNetworkSizes::where('network_id', $network_id)->get();
    return response()->json(['message' => 'Network Sites And Sizes', 'status' => true, 'sites' => $network_sites, 'sizes' => $network_sizes]);
  }

  //site_name collection function 
  public function site_alises($id, $site_id, $site_alias_id)
  {
    $site_alias_qry = OldNetworkSites::select('site_alias')->where('network_id', $id)->where('site_id', $site_id)->where('id', $site_alias_id);
    $site_alises =  $site_alias_qry->first();
    $site_alias_count = $site_alias_qry->count();
    if($site_alias_count > 0){
      $site_name = $site_alises->site_alias;
    }
    else{
      $site_name = '';
    }
    return $site_name;
  }

  //size_name collection function 
  public function size_alises($id, $size_id, $size_alias_id)
  {
    $size_alias_qry = OldNetworkSizes::select('size_alias')->where('network_id', $id)->where('size_id', $size_id)->where('id', $size_alias_id);
    $size_alises =  $size_alias_qry->first();
    $size_alias_count = $size_alias_qry->count();
    if($size_alias_count > 0){
      $size_name = $size_alises->size_alias;
    }
    else{
      $size_name = '';
    }
    return $size_name;
  }

  //Insert New Row
  public function insert_new_data_row(Request $request)
  {
    $network_id = $request->network_id;
    $date = $request->date;
    $site_name = $request->site_alias;
    $size_name = $request->size_alias;
    $impressions = $request->impressions;
    $revenue = $request->revenue;
    $clicks = $request->clicks;

    if (isset($impressions)) {
      $impressions = $impressions;
    } else {
      $impressions = 0;
    }
    if (isset($revenue)) {
      $revenue = $revenue;
    } else {
      $revenue = 0;
    }
    if (isset($clicks)) {
      $clicks = $clicks;
    } else {
      $clicks = 0;
    }

    $insert_data['date']        = $date;
    $insert_data['network_id']  = $network_id;
    $insert_data['impressions'] = $impressions;
    $insert_data['revenue']     = $revenue;
    $insert_data['clicks']      = $clicks;

    $site_exists = OldNetworkSites::select('site_id')->where('network_id', $network_id)->where('site_alias', $site_name)->get();
    $size_exists = OldNetworkSizes::select('size_id')->where('network_id', $network_id)->where('size_alias', $size_name)->get();
    $siteCount = $site_exists->count();
    $sizeCount = $size_exists->count();

    if ($siteCount > 0 && $sizeCount > 0) {
      $site_id = $site_exists[0]['site_id'];
      $size_id = $size_exists[0]['size_id'];

      //Check data is already existing in daily reports or not
      $check_data_exists = OldDailyReport::select('id')->where('date', $insert_data['date'])->where('network_id', $network_id)->where('site_id', $site_id)->where('size_id', $size_id)->get();
      $checkDataCount = $check_data_exists->count();
      if ($checkDataCount > 0) {
        $overwrite_existing_data = OldDailyReport::where('date', $insert_data['date'])->where('network_id', $network_id)->where('site_id', $site_id)->where('size_id', $size_id)->update(['impressions' => $insert_data['impressions'], 'clicks' => $insert_data['clicks'], 'revenue' => $insert_data['revenue']]);
        return response()->json(['message' => 'Data updated successfully', 'status' => true], 200);
      } else {
        if ($site_id > 0 && $size_id > 0) {
          $insert_data['site_id']     = $site_id;
          $insert_data['size_id']     = $size_id;
          $data_insert  = OldDailyReport::create($insert_data);
          if ($data_insert) {
            return response()->json(['message' => 'Data inserted successfully', 'status' => true], 200);
          } else {
            return response()->json(['message' => 'Data not inserted', 'status' => false], 500);
          }
        } else {
          return response()->json(['message' => 'Data not inserted', 'status' => false], 500);
        }
      }
    } else {
      return response()->json(['message' => 'Data not inserted', 'status' => false], 500);
    }
  }

  public function csv_string_to_array($str)
  {
    $expr = "/,(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/";
    $results = preg_split($expr, trim($str));
    return preg_replace("/^\"(.*)\"$/", "$1", $results);
  }

  public function separator_values_array($str)
  {
    if ($str == "dash") {
      return "-";
    }
    if ($str == "underscore") {
      return "_";
    }
    if ($str == "space") {
      return " ";
    }
  }

  public function delete_multiple_records(Request $request)
  {
    $row_ids = $request->row_ids;

    $delete_data = [];
    $success_count = 0;
    $failed_count = 0;
    foreach ($row_ids as $key => $val) {
      $record = OldDailyReport::where('id', '=', $val)->get();
      if ($record->count() > 0) {
        $record_delete = OldDailyReport::where('id', $val)->delete();
        if ($record_delete) {
          $delete_data[] = 1;
          $success_count += 1;
        } else {
          $delete_data[] = 2;
          $failed_count += 1;
        }
      } else {
        $delete_data[] = 2;
        $failed_count += 1;
      }
    }

    $message = "";
    if ($failed_count > 0) {
      $message .= $failed_count . ' row(s) failed to delete. ';
    }
    if ($success_count > 0) {
      $message .= $success_count . ' row(s) successfully deleted.';
    }

    if (in_array("2", $delete_data)) {
      return response()->json(['message' => $message, 'status' => false], 500);
    } else if (in_array("1", $delete_data)) {
      return response()->json(['message' => $message, 'status' => true], 200);
    } else {
      return response()->json(['message' => 'Data not Deleted', 'status' => false], 500);
    }
  }

  public function delete_all_rows(Request $request)
  {
    $date = $request->input('date');
    $network_id = $request->input('network_id');

    $report = OldDailyReport::where('network_id', '=', $network_id)->where('date', '=', $date)->get();
    $report1 = OldNetworkReportsLogs::where('date', $date)->where('network_id', $network_id)->get();
    if ($report->count() > 0 || $report1->count() > 0) {
      $data_delete = OldDailyReport::where('date', $date)->where('network_id', $network_id)->delete();
      $record_delete = OldNetworkReportsLogs::where('date', $date)->where('network_id', $network_id)->delete();
      if ($data_delete || $record_delete) {
        return response()->json(['message' => 'Data Deleted Successfully', 'status' => true], 200);
      } else {
        return response()->json(['message' => 'Data Not Deleted', 'status' => false], 500);
      }
    } else {
      return response()->json(['message' => 'Data not exists', 'status' => false], 500);
    }
  }

  public function run_api(Request $request){
    $date = $request->input('date');
    $network_id = $request->input('network_id');

    $api_url = "";
    $api_url1 = "";
    $api_base_url = "https://dev.publir.com/app/crons/";
    if($network_id == '243'){
      $api_url = $api_base_url."cron_appnexus_tam.php?date=".$date;
    }
    else if($network_id == '158'){
      $api_url = $api_base_url."cron_appnexus.php?date=".$date;
    }
    else if($network_id == '201'){
      $api_url = $api_base_url."cron_connatix.php?date=".$date;
    }
    else if($network_id == '41'){
      $api_url = $api_base_url."cron_media_net.php?date=".$date;
    }
    else if($network_id == '242'){
      $api_url = $api_base_url."cron_media_net_tam.php?date=".$date;
    }
    else if($network_id == '161'){
      $api_url = $api_base_url."cron_next_millennium.php?date=".$date;
    }
    else if($network_id == '74'){
      $api_url = $api_base_url."cron_revcontent.php?date=".$date;
    }
    else if($network_id == '164'){
      $api_url = $api_base_url."cron_sharethrough.php?date=".$date;
    }
    else if($network_id == '148'){
      $api_url = $api_base_url."cron_sovrn.php?date=".$date;
    }
    else if($network_id == '233'){
      $api_url = $api_base_url."cron_topple.php?date=".$date;
    }
    else if($network_id == '170'){
      $api_url = $api_base_url."cron_underdog.php?date=".$date;
    }
    else if($network_id == '238'){
      $api_url = $api_base_url."cron_vdo_ai.php?date=".$date;
    }
    else if($network_id == '124'){
      $api_url = $api_base_url."cron_vidazoo.php?date=".$date;
    }
    else if($network_id == '219'){
      $api_url = $api_base_url."cron_triplelift_tam.php?date=".$date;
    }
    else if($network_id == '137'){
      $api_url = $api_base_url."cron_triplelift.php?date=".$date;
    }
    else if($network_id == '52'){
      $api_url = $api_base_url."cron_criteo.php?date=".$date;
    }
    else if($network_id == '245'){
      $rise_api_url = $api_base_url."cron_rise.php?date=".$date;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, false); 
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
      curl_setopt($ch, CURLOPT_URL, $rise_api_url);
      $data = curl_exec($ch);
      curl_close($ch);
      $rise_response = json_decode($data, true);
      if($rise_response['status'] == true){
        $api_url = $api_base_url."cron_rise_report.php?date=".$date;
      }
      else{
        return response()->json(['message' => 'Report ID not generated yet', 'status' => false], 500);
      }
    }
    if($api_url != ""){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, false); 
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
      curl_setopt($ch, CURLOPT_URL, $api_url);
      $data = curl_exec($ch);
      curl_close($ch);
      $response = json_decode($data, true);
      if($response['status'] == true){
        return response()->json(['message' => 'Network API Executed Successfully', 'status' => true], 200);
      }
      else{
        return response()->json(['message' => 'Network File Not Executed', 'status' => false], 500);
      }
    }
    else{
      return response()->json(['message' => 'Network File Not Found', 'status' => false], 500);
    }
  }
}