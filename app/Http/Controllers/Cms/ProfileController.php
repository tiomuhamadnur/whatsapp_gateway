<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('cms.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
        ]);

        $user->save();

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = $validated['password'];
        $user->save();

        return back()->with('status', 'Password updated successfully.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:1024', 'mimes:jpeg,png,jpg,webp'], // 1MB max
        ]);

        try {
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($user->profile_photo_path) {
                    Storage::disk('public')->delete($user->profile_photo_path);
                }

                // Generate unique filename with timestamp and random string
                $file = $request->file('photo');
                $filename = 'profile_photos/' . uniqid('photo_') . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Store file
                $path = $file->storeAs('/', $filename, 'public');
                
                if ($path) {
                    $user->profile_photo_path = $filename;
                    $user->save();
                    
                    return back()->with('status', 'Profile photo updated successfully.');
                }
            }

            return back()->withErrors(['photo' => 'Failed to upload photo. Please try again.']);
        } catch (\Exception $e) {
            \Log::error('Photo upload error: ' . $e->getMessage());
            return back()->withErrors(['photo' => 'An error occurred while uploading. Please try again.']);
        }
    }
}
