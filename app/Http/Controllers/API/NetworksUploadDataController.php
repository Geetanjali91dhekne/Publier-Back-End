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
        $all_sites = OldSites::get();
        return response()->json(['message' => 'All Sites Data', 'status' => true, 'all_sites' => $all_sites]);
    }

    ///get All Sizes////
    public function get_all_sizes(Request $request)
    {
        $all_sizes = OldSizes::get();
        return response()->json(['message' => 'All Sizes Data', 'status' => true, 'all_sizes' => $all_sizes]);
    }

    ///Get Sites and Sizes Corresponding to Network
    public function network_sites_sizes(Request $request)
    {
        $network_id    = $request->id;
        $network_sites = OldNetworkSites::where('network_id', $network_id)->get();
        $network_sizes = OldNetworkSizes::where('network_id', $network_id)->get();
        return response()->json(['message' => 'Network Sites And Sizes', 'status' => true, 'sites' => $network_sites, 'sizes' => $network_sizes]);
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
                return response()->json(['message' => 'Sites Added Successfully', 'status' => true]);
            } else {
                return response()->json(['message' => 'Sites Not Added Successfully', 'status' => false]);
            }
        } else {
            return response()->json(['message' => 'Site Already Exist', 'status' => false]);
        }
    }

    ////Add New Size Corresponding to Network/////
    public function add_network_size(Request $request)
    {
        $network_id = $request->input('network_id');
        $size_alias = $request->input('size_alias');
        $size_id    = $request->input('size_id');

        $size = OldNetworkSites::where('network_id', '=', $network_id)->where('size_alias', '=', $size_alias)->where('size_id', '=', $size_id)->first();
        if ($size === null) {
            $insert_data['network_id']  = $network_id;
            $insert_data['size_id']     = $size_id;
            $insert_data['size_alias']  = $size_alias;
            $insert_data = OldNetworkSizes::create($insert_data);
            if ($insert_data) {
                return response()->json(['message' => 'Sizes Added Successfully', 'status' => true]);
            } else {
                return response()->json(['message' => 'Sizes Not Added Successfully', 'status' => false]);
            }
        } else {
            return response()->json(['message' => 'Size Already Exist', 'status' => false]);
        }
    }
}
