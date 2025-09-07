<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function authentication(Request $request)
    {
        $credenential = $request->validate(
            [
                'name' => 'required',
                'password' => 'required'
            ],
            [
                'name.required' => 'Kode akses diperlukan!',
                'password.required' => 'Kode akses diperlukan!'
            ]
        );

        if (Auth::attempt($credenential)) {
            $request->session()->regenerate();
            return response()->json(['success' => 'Berhasil Login']);
        }

        return response()->json(['message' => 'Kesalahan pada kode akses!'])->setStatusCode(400);
    }
    public function register(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        // simpan credential untuk user yg sudah login
        $request->user()->webauthnKeys()->create([
            'name' => $request->name,
        ]);

        return response()->json(['message' => 'Face ID registered successfully']);
    }

    // Login menggunakan Face ID
    public function login(Request $request)
    {
        if (Auth::attemptWebAuthn($request)) {
            $request->session()->regenerate();

            return response()->json([
                'status' => 'success',
                'message' => 'Login success'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Login failed'
        ], 401);
    }
}
