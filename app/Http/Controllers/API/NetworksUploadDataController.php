<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\OldNetworkSites;
use App\Models\OldNetworkSizes;
use Illuminate\Http\Request;
use App\Models\OldNetworks;
use App\Models\OldSites;
use App\Models\OldSizes;
use DB;


class NetworksUploadDataController extends Controller
{
    ////All Network Api///
    public function get_networks()
    {
        $networks = OldNetworks::select('id', 'network_name')->get();
        return response()->json(['message' => 'Network data get successfully', 'status' => true, 'networks' => $networks]);
    }

    ////get All Sites////
    public function get_all_sites(Request $request)
    {
        $all_sites = OldSites::select('id', 'site_name', 'site_url')->orderBy('site_name', 'asc')->get();
        return response()->json(['message' => 'All Sites Data', 'status' => true, 'all_sites' => $all_sites]);
    }

    ///get All Sizes////
    public function get_all_sizes(Request $request)
    {
        $all_sizes = OldSizes::select('id', 'size_name', 'dimensions')->orderBy('dimensions', 'asc')->get();
        return response()->json(['message' => 'All Sizes Data', 'status' => true, 'all_sizes' => $all_sizes]);
    }

    ///Get Sites and Sizes Corresponding to Network
    public function network_sites_sizes(Request $request)
    {
        $network_id    = $request->id;
        $site_id       = $request->site_id;
        if (isset($network_id)) {
            $oldNetworkSites = OldNetworkSites::select('id', 'network_id', 'site_id', 'site_alias')->where('network_id', $network_id);
            if (isset($site_id)) {
                $network_sites = $oldNetworkSites->where('site_id', $site_id)->get();
            } else {
                $network_sites = $oldNetworkSites->get();
            }
            $network_sizes = OldNetworkSizes::select('id', 'network_id', 'size_id', 'size_alias')->where('network_id', $network_id)->get();

            foreach ($network_sites as $ky => $val) {
                $site_id         = $val['site_id'];
                $check_site_name = OldSites::select('id', 'site_name')->where('id', $site_id)->first();
                if (isset($check_site_name->site_name)) {
                    $network_sites[$ky]['site_name'] = $check_site_name->site_name;
                } else {
                    $network_sites[$ky]['site_name'] = "";
                }
            }

            foreach ($network_sizes as $ky => $val) {
                $size_id         = $val['size_id'];
                $check_size_name = OldSizes::select('id', 'dimensions')->where('id', $size_id)->first();
                if (isset($check_size_name->dimensions)) {
                    $network_sizes[$ky]['size_name'] = $check_size_name->dimensions;
                } else {
                    $network_sizes[$ky]['size_name'] = "";
                }
            }

            return response()->json(['message' => 'Network Sites And Sizes', 'status' => true, 'sites' => $network_sites, 'sizes' => $network_sizes], 200);
        }

        return response()->json(['message' => 'Data not found', 'status' => false], 500);
    }

    ////Add New Site Corresponding to Network/////
    public function add_network_site(Request $request)
    {
        $network_id = $request->input('network_id');
        $site_alias = $request->input('site_alias');
        $site_id    = $request->input('site_id');

        $site = OldNetworkSites::where('network_id', '=', $network_id)->where('site_alias', '=', $site_alias)->where('site_id', '=', $site_id)->first();
        if ($site === null) {
            $insert_data['network_id'] = $network_id;
            $insert_data['site_id']    = $site_id;
            $insert_data['site_alias'] = $site_alias;
            $insert_data = OldNetworkSites::create($insert_data);
            if ($insert_data) {
                return response()->json(['message' => 'Site Added Successfully', 'status' => true], 200);
            } else {
                return response()->json(['message' => 'Site Not Added', 'status' => false], 500);
            }
        } else {
            return response()->json(['message' => 'Site Already Exist', 'status' => false], 500);
        }
    }

    ////Add New Size Corresponding to Network/////
    public function add_network_size(Request $request)
    {
        $network_id = $request->input('network_id');
        $size_alias = $request->input('size_alias');
        $size_id    = $request->input('size_id');

        $size = OldNetworkSizes::where('network_id', '=', $network_id)->where('size_alias', '=', $size_alias)->where('size_id', '=', $size_id)->first();
        if ($size === null) {
            $insert_data['network_id']  = $network_id;
            $insert_data['size_id']     = $size_id;
            $insert_data['size_alias']  = $size_alias;
            $insert_data = OldNetworkSizes::create($insert_data);
            if ($insert_data) {
                return response()->json(['message' => 'Size Added Successfully', 'status' => true], 200);
            } else {
                return response()->json(['message' => 'Size Not Added', 'status' => false], 500);
            }
        } else {
            return response()->json(['message' => 'Size Already Exist', 'status' => false], 500);
        }
    }

    ////Edit Site Corresponding to Network////
    public function edit_network_site(Request $request)
    {
        $id = $request->input('id');
        $network_id = $request->input('network_id');
        $site_alias = $request->input('site_alias');
        $site_id    = $request->input('site_id');

        $site = OldNetworkSites::where('network_id', '=', $network_id)->where('id', '=', $id)->where('site_id', '=', $site_id)->get();
        if ($site->count() > 0) {
            $insert_data['network_id'] = $network_id;
            $insert_data['site_id']    = $site_id;
            $insert_data['site_alias'] = $site_alias;
            $site_edit = OldNetworkSites::where('id', $id)->where('network_id', $network_id)->where('site_id', $site_id)->update(['site_alias' => $site_alias]);
            if ($site_edit) {
                return response()->json(['message' => 'Site Alias Updated Successfully', 'status' => true], 200);
            } else {
                return response()->json(['message' => 'Site Alias Not Updated', 'status' => false], 500);
            }
        } else {
            return response()->json(['message' => 'Site Alias Not Updated', 'status' => false], 500);
        }
    }

    ////Edit Size Corresponding to Network
    public function edit_network_size(Request $request)
    {
        $id = $request->input('id');
        $network_id = $request->input('network_id');
        $size_alias = $request->input('size_alias');
        $size_id    = $request->input('size_id');

        $size = OldNetworkSizes::where('network_id', '=', $network_id)->where('id', '=', $id)->where('size_id', '=', $size_id)->get();
        if ($size->count() > 0) {
            $insert_data['network_id'] = $network_id;
            $insert_data['size_id']    = $size_id;
            $insert_data['size_alias'] = $size_alias;
            $size_edit = OldNetworkSizes::where('id', $id)->where('network_id', $network_id)->where('size_id', $size_id)->update(['size_alias' => $size_alias]);
            if ($size_edit) {
                return response()->json(['message' => 'Size Alias Updated Successfully', 'status' => true], 200);
            } else {
                return response()->json(['message' => 'Size Alias Not Updated', 'status' => false], 500);
            }
        } else {
            return response()->json(['message' => 'Size Alias Not Updated', 'status' => false], 500);
        }
    }

    ////Delete Site Corresponding to Network////
    public function delete_network_site(Request $request)
    {
        $id = $request->input('id');
        $network_id = $request->input('network_id');
        $site_id    = $request->input('site_id');

        $site = OldNetworkSites::where('network_id', '=', $network_id)->where('id', '=', $id)->where('site_id', '=', $site_id)->get();
        if ($site->count() > 0) {
            $site_delete = OldNetworkSites::where('id', $id)->where('network_id', $network_id)->where('site_id', $site_id)->delete();
            if ($site_delete) {
                return response()->json(['message' => 'Site Alias Deleted Successfully', 'status' => true], 200);
            } else {
                return response()->json(['message' => 'Site Alias Not Deleted', 'status' => false], 500);
            }
        } else {
            return response()->json(['message' => 'Site Alias Not Deleted', 'status' => false], 500);
        }
    }

    ////Delete Size Corresponding to Network
    public function delete_network_size(Request $request)
    {
        $id = $request->input('id');
        $network_id = $request->input('network_id');
        $size_id    = $request->input('size_id');

        $size = OldNetworkSizes::where('network_id', '=', $network_id)->where('id', '=', $id)->where('size_id', '=', $size_id)->get();
        if ($size->count() > 0) {
            $size_delete = OldNetworkSizes::where('id', $id)->where('network_id', $network_id)->where('size_id', $size_id)->delete();
            if ($size_delete) {
                return response()->json(['message' => 'Size Alias Deleted Successfully', 'status' => true], 200);
            } else {
                return response()->json(['message' => 'Size Alias Not Deleted', 'status' => false], 500);
            }
        } else {
            return response()->json(['message' => 'Size Alias Not Deleted', 'status' => false], 500);
        }
    }
}
