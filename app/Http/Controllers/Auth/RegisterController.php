<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:owners,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_name' => ['required', 'string', 'max:255'],
            'mikrotik_host' => ['required', 'string'],
            'mikrotik_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mikrotik_username' => ['required', 'string'],
            'mikrotik_password' => ['required', 'string'],
        ]);

        $owner = Owner::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'business_name' => $validated['business_name'],
            'mikrotik_host' => $validated['mikrotik_host'],
            'mikrotik_port' => $validated['mikrotik_port'],
            'mikrotik_username' => $validated['mikrotik_username'],
            'mikrotik_password' => $validated['mikrotik_password'],
        ]);

        Auth::guard('owner')->login($owner);

        return redirect('/dashboard');
    }
}
