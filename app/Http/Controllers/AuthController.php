<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout', 'updateProfile');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

   

    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('success', 'Вы успешно вошли!');
    }

    return back()->withErrors([
        'email' => 'These credentials do not match our records.',
    ])->onlyInput('email');
}


    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
{
  
    $data = $request->validated();
    
    if ($request->hasFile('avatar')) {
        $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
    }

    $data['password'] = Hash::make($data['password']);
    User::create($data);

    return redirect()->route('login')->with('success', 'Аккаунт создан! Теперь войдите.');
}


public function updateProfile(UpdateProfileRequest $request)
{
    Log::info('updateProfile called', ['user_id' => Auth::id()]);

    $user = Auth::user();
    if (!$user) {
        Log::error('User not found!');
        return redirect()->back()->withErrors(['error' => 'User not found.']);
    }

    $data = $request->validated();
    Log::info('Validated data', $data);

    if ($request->hasFile('avatar')) {
        if ($user->avatar) {
            Log::info('Deleting old avatar', ['avatar' => $user->avatar]);
            Storage::disk('public')->delete($user->avatar);
        }
        $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        Log::info('New avatar uploaded', ['avatar' => $data['avatar']]);
    }

    if (isset($data['password']) && $data['password']) {
        $data['password'] = Hash::make($data['password']);
    } else {
        unset($data['password']);
    }

    dd($data);

    $user->update($data);
    Log::info('User updated successfully', ['user_id' => $user->id]);

    return redirect()->back()->with('success', 'Profile updated!');
}


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
