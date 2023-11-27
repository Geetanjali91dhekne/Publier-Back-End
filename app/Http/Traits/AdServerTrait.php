<?php

namespace App\Http\Traits;

use App\Models\OldSites;
use App\Models\Sites;
use Illuminate\Support\Facades\DB;

trait AdServerTrait
{
    public function newNoAdServerSiteIds()
    {
        $noSiteIds = Sites::where('no_adserver', 'N')->select('site_id')->pluck('site_id')->toArray();
        return $noSiteIds;
    }

    public function oldNoAdServerSiteIds()
    {
        $noSiteIds = OldSites::where('no_adserver', 0)->select('id')->pluck('id')->toArray();
        return $noSiteIds;
    }
}