<?php

namespace App\Http\Controllers;

use Auth;
use Hash;
use Validator;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'email' => 'required|string|unique:users',
            'contact_no' => 'required|digits:10',
            'gender' => 'required',
            'address' => 'required|string',
            'barangay_id' => 'required',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->all()
            ], 400);
        }

        $user = new User([
            'username' => $request->username,
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'email' => $request->email,
            'contact_no' => $request->contact_no,
            'gender' => strtoupper($request->gender),
            'address' => $request->address,
            'barangay_id' => $request->barangay_id,
            'password' => Hash::make($request->password)
        ]);

        $user->save();

        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->all()
            ], 400);
        }

        $credentials = [
            "username" => $request->username,
            "password" => $request->password
        ];

        if(!Auth::attempt($credentials)) {
            $credentials = [
                "email" => $request->username,
                "password" => $request->password
            ];

            if(!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }
        }

        $user = $request->user();#dd($user);
       # dd($user);
        $tokenResult = $user->createToken('Personal Access Token');
       # dd($user->tokens);

        /* $token = $tokenResult->token;
        dd($token);
      #  dd($tokenResult);
        $accessToken = "";
        $expires = "";
        foreach($tokenResult->tokens as $row){

          #  $token = $row->token;
          #  dd($token);
          #  if ($request->remember_me) {
          #      $expires = Carbon::now()->addWeeks(1);
          #  }
          #  $row->save();
        #  dd($row);
           # $accessToken = $row->token;
        }
       # $token = $tokenResult->token; */





        return response()->json([
            'access_token' => $tokenResult->plainTextToken,
            'user' => $user,
            'token_type' => 'Bearer',
           # 'expires_at' => Carbon::parse(
           #     $expires
           # )->toDateTimeString()
        ]);
    }

    public function logout(Request $request) {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request){

        return response()->json($request->user());
    }
}
