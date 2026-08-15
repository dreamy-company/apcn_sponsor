<?php

namespace App\Actions\Doctor;

use App\DTOs\Doctor\DoctorData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDoctorAction
{
    /**
     * Create a doctor: a non-login User (role=doctor, no email/password) with a
     * unique public share token for the read-only portal.
     */
    public function execute(DoctorData $data): User
    {
        return DB::transaction(fn (): User => User::create([
            'name' => $data->name,
            'phone' => $data->phone,
            'role' => UserRole::Doctor,
            'public_token' => Str::random(40),
        ]));
    }
}
