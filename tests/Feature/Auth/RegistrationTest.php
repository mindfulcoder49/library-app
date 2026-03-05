<?php

namespace Tests\Feature\Auth;

use App\Models\OfficeLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $office = OfficeLocation::query()->create(['name' => 'Degreed', 'is_virtual' => true]);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'employee_id' => 'EMP-9999',
            'email' => 'test@example.com',
            'office_location_id' => $office->id,
            'is_lender' => true,
            'is_borrower' => true,
            'agree_lender_guidelines' => true,
            'agree_borrower_guidelines' => true,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
