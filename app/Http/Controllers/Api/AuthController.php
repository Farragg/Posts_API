<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegisterResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use function Laravel\Prompts\password;

class AuthController extends Controller
{
//    public function __construct()
//    {
//        $this->middleware('auth:api', ['except' => 'login', 'register']);
//    }
//
//    public function login(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|string',
//            'password' => 'required|string|min:6',
//        ]);
//
//        if ($validator->fails()){
//            return response()->json($validator->errors(), 422);
//        }
//
//        if (!$token = auth()->attempt($validator->validated())) {
//            return response()->json(['error' => 'Unauthorized'], 401);
//        }
//
//        return $this->createNewToken($token);
//    }
//
//    public function register(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|string',
//            'phone' => 'required|numeric',
//            'password' => 'required|string|confirmed|min:6',
//        ]);
//
//        if ($validator->fails()){
//            return response()->json($validator->errors()->toJson(), 400);
//        }
//
//        $user = User::create(array_merge(
//            $validator->validated(),
//            ['password' => bcrypt($request->password)]
//        ));
//
//        return response()->json([
//            'message' => 'User successfully registered',
//            'user' => $user
//        ], 201);
//    }
//
//    public function logout()
//    {
//        auth()->logout();
//
//        return response()->json(['message' => 'User successfully signed out']);
//    }
//
//    public function refresh()
//    {
//        return $this->createNewToken(auth()->refresh());
//    }
//
//    public function userProfile(){
//        return response()->json(auth()->user());
//    }
//
//    protected function createNewToken ($token)
//    {
//        return response()->json([
//            'access_token' => $token,
//            'token_type' => 'bearer',
//            'expires_in' => auth()->factory()->getTTl() * 60,
//            'user' => auth()->user()
//        ]);
//    }

// Registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|numeric',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $verificationCode = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'verification_code' => $verificationCode,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer']);
    }

    // Login
    public function login(Request $request)
    {
        if (!auth()->attempt($request->only('name', 'password'))) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = User::where(['name'=> $request->name, 'is_verified'=> true])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        if (!$user) {
            return back()->withErrors(['verification_code' => 'Not verified account']);
        }

        return response()->json(['message' => 'Login successful', 'user' => $user, 'access_token' => $token, 'token_type' => 'Bearer']);
    }

    // Verified accounts
    public function verifyUser(Request $request)
    {

        if (!$request->only('verification_code')) {
            return response()->json(['message' => 'Enter valid code'], 401);
        }

        $user = User::where('verification_code', $request->verification_code)->firstOrFail();

        // Mark the user as verified
        $user->update([
            'verification_code' => null,
            'is_verified' => true,
        ]);

        return response()->json(['message' => 'Account verified successfully.']);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }


}
