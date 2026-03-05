<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'officeLocations' => OfficeLocation::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'employee_id' => ['required', 'string', 'max:50', Rule::unique(User::class)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'office_location_id' => ['required', 'integer', 'exists:office_locations,id'],
            'share_location_ids' => ['nullable', 'array'],
            'share_location_ids.*' => ['integer', 'exists:office_locations,id'],
            'is_lender' => ['required', 'boolean'],
            'is_borrower' => ['required', 'boolean'],
            'agree_lender_guidelines' => ['nullable', 'accepted', 'required_if:is_lender,1'],
            'agree_borrower_guidelines' => ['nullable', 'accepted', 'required_if:is_borrower,1'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $name = trim($validated['first_name'].' '.$validated['last_name']);

        $user = User::query()->create([
            'name' => $name,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'employee_id' => $validated['employee_id'],
            'email' => $validated['email'],
            'office_location_id' => $validated['office_location_id'],
            'is_lender' => $validated['is_lender'],
            'is_borrower' => $validated['is_borrower'],
            'agree_lender_guidelines' => (bool) ($validated['agree_lender_guidelines'] ?? false),
            'agree_borrower_guidelines' => (bool) ($validated['agree_borrower_guidelines'] ?? false),
            'password' => Hash::make($validated['password']),
        ]);

        $user->shareLocations()->sync($validated['share_location_ids'] ?? []);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
