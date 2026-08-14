<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\DealForm;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserIndex;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_doctors_cannot_access_user_management(): void
    {
        $doctor = User::factory()->doctor()->create();
        $this->actingAs($doctor);

        $user = User::factory()->doctor()->create();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $user))->assertForbidden();
    }

    public function test_j4u_can_access_user_management(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        $user = User::factory()->doctor()->create();

        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();
        $this->get(route('users.edit', $user))->assertOk();
    }

    public function test_j4u_can_create_a_doctor_user(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(UserForm::class)
            ->set('name', 'Dr. Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('role', UserRole::Doctor->value)
            ->set('password', 'secretpass123')
            ->set('passwordConfirmation', 'secretpass123')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'name' => 'Dr. Budi Santoso',
            'email' => 'budi@example.com',
            'role' => UserRole::Doctor->value,
        ]);

        // Admin-provisioned accounts are auto-verified so they can log in.
        $this->assertNotNull(User::where('email', 'budi@example.com')->first()->email_verified_at);

        // The new doctor is available as an initiator on the deal form.
        Livewire::test(DealForm::class)
            ->assertViewHas('doctors', fn ($doctors) => $doctors->contains('email', 'budi@example.com'));
    }

    public function test_j4u_can_edit_a_user_and_change_role(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $user = User::factory()->doctor()->create(['name' => 'Dr. Lama', 'email' => 'lama@example.com']);

        Livewire::test(UserForm::class, ['user' => $user])
            ->set('name', 'Dr. Baru')
            ->set('email', 'baru@example.com')
            ->set('role', UserRole::J4U->value)
            ->set('password', '')
            ->set('passwordConfirmation', '')
            ->call('save');

        $user->refresh();

        $this->assertSame('Dr. Baru', $user->name);
        $this->assertSame('baru@example.com', $user->email);
        $this->assertSame(UserRole::J4U, $user->role);
        // Blank password on edit keeps the existing password.
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_j4u_can_reset_a_users_password(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $user = User::factory()->doctor()->create();

        Livewire::test(UserForm::class, ['user' => $user])
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('role', UserRole::Doctor->value)
            ->set('password', 'newsecret123')
            ->set('passwordConfirmation', 'newsecret123')
            ->call('save');

        $this->assertTrue(Hash::check('newsecret123', $user->fresh()->password));
    }

    public function test_email_must_be_unique(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        User::factory()->doctor()->create(['email' => 'taken@example.com']);

        Livewire::test(UserForm::class)
            ->set('name', 'Dr. X')
            ->set('email', 'taken@example.com')
            ->set('role', UserRole::Doctor->value)
            ->set('password', 'secretpass123')
            ->set('passwordConfirmation', 'secretpass123')
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertDatabaseCount('users', 2); // j4u + existing doctor
    }

    public function test_password_validation_on_create(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        // Too short + mismatch.
        Livewire::test(UserForm::class)
            ->set('name', 'Dr. X')
            ->set('email', 'x@example.com')
            ->set('role', UserRole::Doctor->value)
            ->set('password', 'short')
            ->set('passwordConfirmation', 'different')
            ->call('save')
            ->assertHasErrors('password');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_j4u_cannot_change_their_own_role(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        Livewire::test(UserForm::class, ['user' => $j4u])
            ->set('name', $j4u->name)
            ->set('email', $j4u->email)
            ->set('role', UserRole::Doctor->value)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(UserRole::J4U, $j4u->fresh()->role);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        Livewire::test(UserIndex::class)->call('delete', $j4u->id);

        $this->assertDatabaseHas('users', ['id' => $j4u->id]);
    }

    public function test_user_with_deals_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();
        Deal::factory()->create(['doctor_id' => $doctor->id]);

        Livewire::test(UserIndex::class)->call('delete', $doctor->id);

        $this->assertDatabaseHas('users', ['id' => $doctor->id]);
    }

    public function test_user_without_deals_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();

        Livewire::test(UserIndex::class)->call('delete', $doctor->id);

        $this->assertDatabaseMissing('users', ['id' => $doctor->id]);
    }
}
