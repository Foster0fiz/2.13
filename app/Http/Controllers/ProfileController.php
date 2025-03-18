<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
{
    $user = Auth::user();


    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'current_password' => 'nullable|min:6',
        'password' => 'nullable|min:6|confirmed',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ], [
        'email.unique' => 'This email is already taken.',
        'current_password.min' => 'Current password must be at least 6 characters.',
        'password.min' => 'New password must be at least 6 characters.',
        'password.confirmed' => 'New passwords do not match.',
    ]);

    if (!empty($data['current_password'])) {
        if (!Hash::check($data['current_password'], $user->password)) {
            return redirect()->route('profile.edit')->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);

            $user->update($data);

            Auth::logout();
            return redirect()->route('login')->with('success', 'Password changed successfully. Please log in again.');
        }
    } else {
        unset($data['password']);
    }

    if ($request->hasFile('avatar')) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
    }
    dd($data);

    $user->update($data);

    return redirect()->route('dashboard')->with('success', 'Profile updated successfully!');
}
}
