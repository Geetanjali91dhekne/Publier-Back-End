<?php

namespace App\Http\Controllers\API\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Traits\Onboarding\FilterDataTrait;
use Illuminate\Http\Request;

class FilterDataController extends Controller
{
    use FilterDataTrait;

    /*
    ** get sites list
    **
    */
    public function getSitesList(Request $request)
    {
        try {
            $sitesList = $this->getSitesListQuery();
            return response()->json(['message' => 'Sites list get successfully', 'status' => true, 'data' => $sitesList]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
        
    }

    /*
    ** get product list publisher
    **
    */
    public function getLiveProductList(Request $request)
    {
        try {
            $productList = $this->getLiveProductListQuery();
            return response()->json(['message' => 'Product list get successfully', 'status' => true, 'data' => $productList]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    ** get list publisher name
    **
    */
    public function getPublisherList(Request $request)
    {
        try {
            $nameList = $this->getPublisherListQuery();
            return response()->json(['message' => 'Publisher name list get successfully', 'status' => true, 'data' => $nameList]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    ** get prebid version list
    **
    */
    public function getPrebidVersionList(Request $request)
    {
        try {
            $prebidtList = $this->getPrebidVersionListQuery();
            return response()->json(['message' => 'Prebid version list get successfully', 'status' => true, 'data' => $prebidtList]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }

    /*
    ** get account manager list
    **
    */
    public function getAccountManagerList(Request $request)
    {
        try {
            $accounttList = $this->getAccountListQuery();
            return response()->json(['message' => 'Account manager list get successfully', 'status' => true, 'data' => $accounttList]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => sprintf("Error: %s, Line: %s, File: %s", $e->getMessage(), $e->getLine(), $e->getFile()), 'data' => []], 500);
        }
    }
}