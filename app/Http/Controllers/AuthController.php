<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', strtolower(trim($request->email)))->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return back()->withInput()->with('error', 'Invalid email or password.');
        }

        session()->regenerate();
        session(['admin_id' => $admin->id, 'admin_name' => $admin->name]);

        return redirect()->route('dashboard')->with('success', 'Welcome back!');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->regenerate();

        return redirect()->route('login');
    }
}
