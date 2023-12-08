<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    function login(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->messages()
            ], 422);
        }

        $input = $request->all();
        Auth::attempt($input);
        if(Auth::check()) {
            $user = Auth::user();
            $token = $user->createToken('HouseCall')->accessToken;
            return response()->json([
                'success' => true,
                'alert' => 'Login Successfully',
                'token' => $token
            ], 200)->withCookie(cookie('token', $token, 60));
        } else {
            return response()->json([
                'success' => false,
                'alert' => 'Login Failed. Please Check Credentials'
            ], 401);
        }
    }

    function register(Request $request) {
        if(!Auth::guard('api')->check()) {
            $validate = Validator::make($request->all(), [
                'email' => 'required|unique:users|email',
                'password' => 'required|min:6',
                'name' => 'required|min:3'
            ]);
    
            if($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validate->messages()
                ], 422);
            }
    
            $result = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password'))
            ]);
    
            if(isset($result['id'])) {
                return response()->json([
                    'success' => true,
                    'alert' => 'Registered Successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'alert' => 'Failed! Try Again.'
                ], 422);
            }
        } else {
            return response()->json([
                'success' => false,
                'alert' => 'Unauthorized'
            ], 401);
        }
    }

    function unauthorized() {
        return response()->json([
            'success' => false,
            'alert' => 'unauthorized'
        ], 401);
    }
}
