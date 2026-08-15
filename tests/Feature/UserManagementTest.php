<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserIndex;
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
        $this->actingAs(User::factory()->doctor()->create());

        $admin = User::factory()->j4u()->create();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $admin))->assertForbidden();
    }

    public function test_j4u_can_access_user_management(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $admin = User::factory()->j4u()->create();

        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();
        $this->get(route('users.edit', $admin))->assertOk();
    }

    public function test_editing_a_doctor_via_users_route_is_not_found(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();

        $this->get(route('users.edit', $doctor))->assertNotFound();
    }

    public function test_j4u_can_create_an_administrator(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(UserForm::class)
            ->set('name', 'Committee Member')
            ->set('email', 'member@example.com')
            ->set('password', 'secretpass123')
            ->set('passwordConfirmation', 'secretpass123')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'name' => 'Committee Member',
            'email' => 'member@example.com',
            'role' => UserRole::J4U->value,
        ]);

        // Admin-provisioned accounts are auto-verified so they can log in.
        $this->assertNotNull(User::where('email', 'member@example.com')->first()->email_verified_at);
    }

    public function test_j4u_can_edit_an_administrator(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $admin = User::factory()->j4u()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        Livewire::test(UserForm::class, ['user' => $admin])
            ->set('name', 'New Name')
            ->set('email', 'new@example.com')
            ->set('password', '')
            ->set('passwordConfirmation', '')
            ->call('save');

        $admin->refresh();

        $this->assertSame('New Name', $admin->name);
        $this->assertSame('new@example.com', $admin->email);
        // Blank password on edit keeps the existing password.
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_j4u_can_reset_an_administrator_password(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $admin = User::factory()->j4u()->create();

        Livewire::test(UserForm::class, ['user' => $admin])
            ->set('name', $admin->name)
            ->set('email', $admin->email)
            ->set('password', 'newsecret123')
            ->set('passwordConfirmation', 'newsecret123')
            ->call('save');

        $this->assertTrue(Hash::check('newsecret123', $admin->fresh()->password));
    }

    public function test_email_must_be_unique(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        User::factory()->j4u()->create(['email' => 'taken@example.com']);

        Livewire::test(UserForm::class)
            ->set('name', 'Someone')
            ->set('email', 'taken@example.com')
            ->set('password', 'secretpass123')
            ->set('passwordConfirmation', 'secretpass123')
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertDatabaseCount('users', 2); // acting admin + existing admin
    }

    public function test_password_validation_on_create(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(UserForm::class)
            ->set('name', 'Someone')
            ->set('email', 'x@example.com')
            ->set('password', 'short')
            ->set('passwordConfirmation', 'different')
            ->call('save')
            ->assertHasErrors('password');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        Livewire::test(UserIndex::class)->call('delete', $j4u->id);

        $this->assertDatabaseHas('users', ['id' => $j4u->id]);
    }

    public function test_j4u_can_delete_another_administrator(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $other = User::factory()->j4u()->create();

        Livewire::test(UserIndex::class)->call('delete', $other->id);

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_user_index_only_lists_administrators(): void
    {
        $this->actingAs(User::factory()->j4u()->create(['name' => 'Admin Alice']));

        $doctor = User::factory()->doctor()->create(['name' => 'Dr Bob']);

        Livewire::test(UserIndex::class)
            ->assertSee('Admin Alice')
            ->assertDontSee('Dr Bob');
    }
}
