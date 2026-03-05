<?php

namespace Tests\Feature;

use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();
        $office = OfficeLocation::query()->create(['name' => 'Test Office']);
        $shareOffice = OfficeLocation::query()->create(['name' => 'Share Office']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'employee_id' => 'E100',
                'email' => 'test@example.com',
                'office_location_id' => $office->id,
                'share_location_ids' => [$shareOffice->id],
                'is_lender' => true,
                'is_borrower' => true,
                'agree_lender_guidelines' => true,
                'agree_borrower_guidelines' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertSame('E100', $user->employee_id);
        $this->assertSame($office->id, $user->office_location_id);
        $this->assertTrue($user->is_lender);
        $this->assertTrue($user->is_borrower);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame([$shareOffice->id], $user->shareLocations()->pluck('office_locations.id')->all());
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();
        $office = OfficeLocation::query()->create(['name' => 'Test Office']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'employee_id' => 'E200',
                'email' => $user->email,
                'office_location_id' => $office->id,
                'share_location_ids' => [],
                'is_lender' => true,
                'is_borrower' => true,
                'agree_lender_guidelines' => true,
                'agree_borrower_guidelines' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
