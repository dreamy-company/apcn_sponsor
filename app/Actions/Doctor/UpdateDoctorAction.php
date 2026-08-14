<?php

namespace App\Actions\Doctor;

use App\DTOs\Doctor\DoctorData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateDoctorAction
{
    /**
     * Update a doctor's details. Backfills a public token if one is missing.
     */
    public function execute(User $doctor, DoctorData $data): User
    {
        return DB::transaction(function () use ($doctor, $data): User {
            $doctor->name = $data->name;
            $doctor->phone = $data->phone;

            if ($doctor->public_token === null) {
                $doctor->public_token = Str::random(40);
            }

            $doctor->save();

            return $doctor;
        });
    }
}
