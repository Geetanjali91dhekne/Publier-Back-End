<?php

namespace App\Http\Middleware;

use App\Models\Publisher;
use Closure;
use Illuminate\Http\Request;

class CheckStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        //return 123;

        $key = $request->header->get('key');



        if ($key != NULL) {

            $dbKey = Publisher::select('publisher_type')->where('remember_token', $key)->first();

            if (!empty($dbKey)) {
                if ($dbKey->publisher_type == 'super_admin')
                    return $next($request);
            }
            return response()->json(['status' => 'failed', 'code' => 401, 'msg' => 'Wrong secret key']);
        }
    }
}