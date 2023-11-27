<?php

namespace App\Http\Middleware;

use App\Models\Publisher;
use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
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
        $header = $request->header('authorization');
        $token = explode(' ', $header);
        if(count($token) <= 1) {
            return response()->json(['message' => 'Authorization failed!!', 'status' => false], 403);
        }
        
        $user = Publisher::select('id')->where('jwt_token', $token[1])->first();
        if(!$user) return response()->json(['message' => 'Authorization token not valid or user not found in DB', 'status' => false], 403);
        $jsondata = json_decode($user, true);
        $request->attributes->set('userId', $jsondata['id']);

        return $next($request);
    }
}
