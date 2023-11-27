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
    $networks = OldNetworks::select('id', 'network_name')->where('api_status', '1')->get();
    return response()->json(['message' => 'Network data get successfully', 'status' => true, 'networks' => $networks]);
  }

  //Get Failed Data
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

    $id            = $request->input('id');
    $date          = $request->input('date');
    $failed_data   = OldNetworkReportsLogs::where('network_id', $id)->where('date', $date)->get();
    $uploaded_data = OldDailyReport::select('id', 'date', 'site_id', 'size_id', 'network_id', 'impressions', 'revenue', 'clicks', 'type', 'status', 'created_at', 'updated_at')->where('network_id', $id)->where('date', $date)->get();

    foreach ($uploaded_data as $ky => $val) {
      $site_id                         = $val['site_id'];
      $size_id                         = $val['size_id'];
      $sitename                        = self::site_alises($id, $site_id);
      $uploaded_data[$ky]['site_name'] = $sitename;
      $sizename                        = self::size_alises($id, $size_id);
      $uploaded_data[$ky]['size_name'] = $sizename;
    }
    return response()->json(['message' => 'Failed rows get successfully', 'status' => true, 'failed_data' => $failed_data, 'uploaded_data' => $uploaded_data]);
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

      $insert_data['date']        = $data[$i]['date'];
      $insert_data['network_id']  = $network_id;
      $insert_data['impressions'] = $data[$i]['impressions'];
      $insert_data['revenue']     = $data[$i]['revenue'];
      $insert_data['clicks']      = $data[$i]['clicks'];
      $insert_data['type']        = $data[$i]['type'];

      $site_exists = OldNetworkSites::select('site_id')->where('network_id', $network_id)->where('site_alias', $site_name)->get();
      $size_exists = OldNetworkSizes::select('size_id')->where('network_id', $network_id)->where('size_alias', $size_name)->get();
      $siteCount = $site_exists->count();
      $sizeCount = $size_exists->count();

      if ($siteCount > 0 && $sizeCount == 0) {
        $sites_sizes_status[$id]['site'] = 1;
        $sites_sizes_status[$id]['size'] = 0;
      } else if ($siteCount == 0 && $sizeCount > 0) {
        $sites_sizes_status[$id]['site'] = 0;
        $sites_sizes_status[$id]['size'] = 1;
      } else if ($siteCount > 0 && $sizeCount > 0) {
        $site_id = $site_exists[0]['site_id'];
        $size_id = $size_exists[0]['size_id'];

        if ($site_id > 0 && $size_id > 0) {
          $insert_data['site_id']     = $site_id;
          $insert_data['size_id']     = $size_id;
          $data_insert  = OldDailyReport::create($insert_data);
          if ($data_insert) {
            $sites_sizes_status[$id]['site'] = 1;
            $sites_sizes_status[$id]['size'] = 1;
            $delete_log_data = OldNetworkReportsLogs::where('id', $id)->where('network_id', $network_id)->update(['site_name' => $site_name, 'size_name' => $size_name, 'status' => '1']);
            $success_rows[] = 1;
          } else {
            // $sizes_status[] = $id;
            $failed_count = $failed_count + 1;
            $success_rows[] = 2;
          }
        } else {
          // $sizes_status[] = $id;
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
    if (in_array("2", $success_rows)) {
      return response()->json(['message' => $failed_count . ' row(s) failed to load', 'status' => true, 'sites_sizes_status' => $sites_sizes_status]);
    } else if (in_array("1", $success_rows)) {
      return response()->json(['message' => 'Data inserted successfully', 'status' => true]);
    } else {
      return response()->json(['message' => 'Data not inserted', 'status' => false, 'sites_sizes_status' => $sites_sizes_status]);
    }
  }

  //Upload failed data throught csv
  public function insert_failed_rows_csv(Request $request)
  {
    if ($request->hasFile('csvfile')) {
      $date       = $request->input('date');
      $networkid  = $request->input('network_id');
      $filename   = $request->file('csvfile')->getRealPath();
      $file       = fopen($filename, "r");
      $data_arr = array();
      $i = 0;
      while (($data1 = fgetcsv($file, 200, ",")) !== FALSE) {
        $data_arr[$i] = $data1;
        $i++;
      }

      $var = ['Name', 'Impressions', 'Revenue', 'Clicks'];
      $var1 = [];
      $data = [];
      foreach ($var as $i) {
        $index = array_search($i, $data_arr[0]);
        if ($index !== false) {
          $var1[$i] = $index;
        }
      }

      for ($i = 1; $i < count($data_arr); $i++) {
        $date        = $date;
        $network_id  = $networkid;

        if (isset($data_arr[$i][$var1['Name']])) {
          $site_size  = $data_arr[$i][$var1['Name']];
          $site_size = explode('_', $site_size);
        } else {
          $site_size = '';
        }
        if (isset($site_size[0])) {
          $site_name  = $site_size[0];
        } else {
          $site_name  = '';
        }

        if (isset($site_size[1])) {
          $size_name = $site_size[1];
        } else {
          $size_name = 0;
        }
        if (isset($data_arr[$i][$var1['Impressions']])) {
          $impressions = $data_arr[$i][$var1['Impressions']];
        } else {
          $impressions = 0;
        }
        if (isset($data_arr[$i][$var1['Revenue']])) {
          $revenue = $data_arr[$i][$var1['Revenue']];
        } else {
          $revenue = 0;
        }
        if (isset($data_arr[$i][$var1['Clicks']])) {
          $clicks = $data_arr[$i][$var1['Clicks']];
        } else {
          $clicks = 0;
        }
        $type        = $site_name . '-' . $size_name;
        $status      = 1;
        $data_ar = array('date' => $date, 'site_name' => $site_name, 'network_id' => $network_id, 'size_name' => $size_name, 'impressions' => $impressions, 'revenue' => $revenue, 'clicks' => $clicks, 'type' => $type, 'status' => $status);

        $data[$i - 1] = $data_ar;
      }
    }


    // echo "<pre>";
    // print_r($data[0]['site_name']);die;
    $data_count   = count($data);
    $failed_count = 0;
    $insert_data  = [];
    $success_rows = [];
    $sites_sizes_status = [];

    for ($i = 0; $i < $data_count; $i++) {
      // $id         = $data[$i]['id'];
      $site_name  = $data[$i]['site_name'];
      $size_name  = $data[$i]['size_name'];
      $network_id = $data[$i]['network_id'];

      $insert_data['date']        = $data[$i]['date'];
      $insert_data['network_id']  = $network_id;
      $insert_data['impressions'] = $data[$i]['impressions'];
      $insert_data['revenue']     = $data[$i]['revenue'];
      $insert_data['clicks']      = $data[$i]['clicks'];
      $insert_data['type']        = $data[$i]['type'];

      //////Insert Failed Data///////////////
      $insert_failed_data['date']        = $data[$i]['date'];
      $insert_failed_data['network_id']  = $network_id;
      $insert_failed_data['impressions'] = $data[$i]['impressions'];
      $insert_failed_data['revenue']     = $data[$i]['revenue'];
      $insert_failed_data['clicks']      = $data[$i]['clicks'];
      $insert_failed_data['type']        = $data[$i]['type'];
      $insert_failed_data['site_name']   = $site_name;
      $insert_failed_data['size_name']   = $size_name;

      $site_exists = OldNetworkSites::select('site_id')->where('network_id', $network_id)->where('site_alias', $site_name)->get();
      $size_exists = OldNetworkSizes::select('size_id')->where('network_id', $network_id)->where('size_alias', $size_name)->get();
      $siteCount = $site_exists->count();
      $sizeCount = $size_exists->count();

      if ($siteCount > 0 && $sizeCount > 0) {
        $site_id = $site_exists[0]['site_id'];
        $size_id = $size_exists[0]['size_id'];

        if ($site_id > 0 && $size_id > 0) {
          $insert_data['site_id']     = $site_id;
          $insert_data['size_id']     = $size_id;
          $data_insert  = OldDailyReport::create($insert_data);
          if ($data_insert) {
            $success_rows[] = 1;
          } else {
            // print_r($insert_failed_data);
            // $insert_failed_data['site_name']     = $site_name;
            // $insert_failed_data['size_name']     = $size_name;  
            // $insert_failed_data['type']        = $data[$i]['type'];
            $failed_log_data = OldNetworkReportsLogs::create($insert_failed_data);
            $failed_count = $failed_count + 1;
            $success_rows[] = 2;
          }
        } else {
          // print_r($insert_failed_data);
          // $insert_failed_data['site_name']     = $site_name;
          // $insert_failed_data['size_name']     = $size_name;  
          // $insert_failed_data['type']        = $data[$i]['type'];
          $failed_log_data = OldNetworkReportsLogs::create($insert_failed_data);
          $failed_count = $failed_count + 1;
          $success_rows[] = 2;
        }
      } else {
        // print_r($insert_failed_data);
        // $insert_failed_data['site_name']     = $site_name;
        // $insert_failed_data['size_name']     = $size_name;  
        $failed_log_data = OldNetworkReportsLogs::create($insert_failed_data);
        $failed_count = $failed_count + 1;
        $success_rows[] = 2;
      }
    }
    if (in_array("2", $success_rows)) {
      return response()->json(['message' => $failed_count . ' row(s) failed to load', 'status' => true, 'sites_sizes_status' => $sites_sizes_status]);
    } else if (in_array("1", $success_rows)) {
      return response()->json(['message' => 'Data inserted successfully', 'status' => true]);
    } else {
      return response()->json(['message' => 'Data not inserted', 'status' => false, 'sites_sizes_status' => $sites_sizes_status]);
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

  //Network Sizes
  // public function network_sizes(Request $request){
  //   $network_id = $request->id;
  //   $network_sizes = OldNetworkSizes::where('network_id',$network_id)->get();
  //   return response()->json(['message' => 'Network Sizes' ,'status' => true, 'sizes' => $network_sizes]);
  // }


  ///site_name collection function 
  public function site_alises($id, $site_id)
  {
    $site_name = '';
    $site_alises =  OldNetworkSites::select('site_alias')->where('network_id', $id)->where('site_id', $site_id)->get();
    foreach ($site_alises as $key => $vl) {
      $site_name = $vl['site_alias'];
    }
    return $site_name;
  }
  //size_name collection function 
  public function size_alises($id, $size_id)
  {
    $size_name = '';
    $size_alises  = OldNetworkSizes::select('size_alias')->where('network_id', $id)->where('size_id', $size_id)->get();
    foreach ($size_alises as $ky => $vl) {
      $size_name = $vl['size_alias'];
    }
    return $size_name;
  }
}
