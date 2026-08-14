<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_root_redirects_authenticated_users_to_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('home'))->assertRedirect(route('dashboard'));
    }
}
