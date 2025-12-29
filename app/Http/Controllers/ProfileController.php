<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the profile.
     */
    public function edit(): View
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $disk = config('filesystems.default');
            $directory = 'users/avatars';
            
            // Delete old avatar if exists
            if ($user->avatar_path) {
                Storage::disk($disk)->delete($user->avatar_path);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store($directory, $disk);
            $validated['avatar_path'] = $avatarPath;
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar_path' => $validated['avatar_path'] ?? $user->avatar_path,
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Profile updated successfully.');
    }
}
