<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'weight' => 'required|numeric|min:1|max:500',
            'height' => 'required|numeric|min:1|max:300',
            'age' => 'required|integer|min:1|max:120',
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json(['message' => 'Status saved', 'user' => $user]);
    }
}
