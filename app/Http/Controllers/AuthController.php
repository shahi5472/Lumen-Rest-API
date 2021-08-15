<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        if (empty($email) or empty($password)) {
            return response()->json(['status' => 'error', 'message' => 'All field are required'], 404);
        }

        $credentials = request(['email', 'password']);

        if ($token = JWTAuth::attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json([
            'message' => 'Unauthorized',
            'status' => '401',
        ], 401);
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return response()->json($validator->errors()->toArray(), 422);

        //To save user information into database
        $user = new User();

        $user->name =  $request['name'];
        $user->email =  $request['email'];
        $user->password =  Hash::make($request['password']);

        DB::beginTransaction();

        try {
            $user->save();
            DB::commit();

            return $this->login($request);
            //return response()->json(['message' => 'Successfully created user!', 'status' => '201'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'status' => $e->getCode(),
            ], $e->getCode());
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => auth()->user(),
        ], 200);
    }
}
