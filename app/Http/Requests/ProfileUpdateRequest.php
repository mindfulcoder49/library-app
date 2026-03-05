<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'office_location_id' => ['required', 'integer', 'exists:office_locations,id'],
            'share_location_ids' => ['nullable', 'array'],
            'share_location_ids.*' => ['integer', 'exists:office_locations,id'],
            'is_lender' => ['required', 'boolean'],
            'is_borrower' => ['required', 'boolean'],
            'agree_lender_guidelines' => ['nullable', 'accepted', 'required_if:is_lender,1'],
            'agree_borrower_guidelines' => ['nullable', 'accepted', 'required_if:is_borrower,1'],
        ];
    }
}
