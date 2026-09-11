<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['role' => ['required', 'in:Owner,Staff'], 'username' => ['required', 'string'], 'password' => ['required', 'string']]);
        $user = DB::table('users')->where('active', true)->where('role', $data['role'])->where(function ($query) use ($data) {
            $query->where('email', $data['username'])->orWhere('name', $data['username']);
        })->first();
        abort_unless($user && Hash::check($data['password'], $user->password), 401, 'Invalid credentials.');
        $token = Str::random(60);
        DB::table('users')->where('id', $user->id)->update(['api_token' => $token, 'updated_at' => now()]);
        return ['user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'token' => $token], 'token' => $token, 'message' => 'Authenticated'];
    }
}
