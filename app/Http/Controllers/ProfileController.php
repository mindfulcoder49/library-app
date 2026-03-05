<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\OfficeLocation;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'officeLocations' => OfficeLocation::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'employee_id' => $validated['employee_id'],
            'email' => $validated['email'],
            'office_location_id' => $validated['office_location_id'],
            'is_lender' => (bool) $validated['is_lender'],
            'is_borrower' => (bool) $validated['is_borrower'],
            'agree_lender_guidelines' => (bool) ($validated['agree_lender_guidelines'] ?? false),
            'agree_borrower_guidelines' => (bool) ($validated['agree_borrower_guidelines'] ?? false),
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $user->shareLocations()->sync($validated['share_location_ids'] ?? []);

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
