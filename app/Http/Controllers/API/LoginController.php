<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Publisher;
use App\Models\WriteAccessPublisher;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{

    public function Login(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'email' => 'required',
            'password'  => 'required',
        ]);

        if($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['message' => $errors, 'status' => false], 403);
        }

        $email = $request->input('email');
        $pass  = $request->input('password');
        $pass  = hash("sha256", trim($pass));
        // $dbData = Publisher::select('id', 'email', 'password', 'publisher_type')->where('email', $email)->first();
        $dbData = WriteAccessPublisher::select('id', 'email', 'password', 'publisher_type')->where('email', $email)->first();
        $dbPass = $dbData && $dbData->password;
        if ($dbData == null || $dbData == '') {
            return response()->json(['message' => 'Invalid user'], 400);
        } else if ($pass != $dbPass) {
            return response()->json(['message' => 'Invalid Credentials'], 400);
        } else {
            // $dbUser = Publisher::find($dbData->id);
            $dbUser = WriteAccessPublisher::find($dbData->id);
            // $success['token'] =  $dbUser->createToken('MyApp')->accessToken;

            /* disable new token generate */
            if(!$dbUser['jwt_token']) {
                $success['token'] = Str::uuid()->toString();
                $dbUser->jwt_token = $success['token'];
                $dbUser->update();
            }
            $success['token'] = $dbUser['jwt_token'];
            return response()->json(['jwt_token' => $success, 'user' => $dbUser]);
        }
    }
}