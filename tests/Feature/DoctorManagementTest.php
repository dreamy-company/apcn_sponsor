<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\DealForm;
use App\Livewire\Doctors\DoctorForm;
use App\Livewire\Doctors\DoctorIndex;
use App\Models\Deal;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('doctors.index'))->assertRedirect(route('login'));
    }

    public function test_doctors_cannot_access_doctor_management(): void
    {
        $this->actingAs(User::factory()->doctor()->create());

        $this->get(route('doctors.index'))->assertForbidden();
        $this->get(route('doctors.create'))->assertForbidden();
    }

    public function test_j4u_can_access_doctor_management(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();

        $this->get(route('doctors.index'))->assertOk();
        $this->get(route('doctors.create'))->assertOk();
        $this->get(route('doctors.edit', $doctor))->assertOk();
    }

    public function test_editing_an_admin_via_doctors_route_is_not_found(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $admin = User::factory()->j4u()->create();

        $this->get(route('doctors.edit', $admin))->assertNotFound();
    }

    public function test_j4u_can_create_a_doctor_with_a_public_token(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(DoctorForm::class)
            ->set('name', 'Dr. Budi Santoso')
            ->set('phone', '+62 812 0000 1111')
            ->call('save');

        $doctor = User::doctors()->where('name', 'Dr. Budi Santoso')->first();

        $this->assertNotNull($doctor);
        $this->assertSame(UserRole::Doctor, $doctor->role);
        $this->assertNull($doctor->email);
        $this->assertNull($doctor->password);
        $this->assertNotEmpty($doctor->public_token);

        // The new doctor is selectable as an initiator on the deal form.
        Livewire::test(DealForm::class)
            ->assertViewHas('doctors', fn ($doctors) => $doctors->contains('id', $doctor->id));
    }

    public function test_j4u_can_edit_a_doctor(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create(['name' => 'Old', 'phone' => '111']);

        Livewire::test(DoctorForm::class, ['doctor' => $doctor])
            ->set('name', 'New Name')
            ->set('phone', '222')
            ->call('save');

        $doctor->refresh();
        $this->assertSame('New Name', $doctor->name);
        $this->assertSame('222', $doctor->phone);
    }

    public function test_doctor_with_deals_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();
        Deal::factory()->create(['doctor_id' => $doctor->id]);

        Livewire::test(DoctorIndex::class)->call('delete', $doctor->id);

        $this->assertDatabaseHas('users', ['id' => $doctor->id]);
    }

    public function test_doctor_without_deals_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();

        Livewire::test(DoctorIndex::class)->call('delete', $doctor->id);

        $this->assertDatabaseMissing('users', ['id' => $doctor->id]);
    }

    public function test_j4u_can_set_the_global_access_code(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(DoctorIndex::class)
            ->set('accessCode', 'NEWCODE99')
            ->call('saveAccessCode');

        $this->assertSame('NEWCODE99', Setting::get('public_access_code'));
    }
}
