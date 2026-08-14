<?php

namespace App\Actions\User;

use App\DTOs\User\UserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    /**
     * Create a user (doctor or j4u) with a hashed password.
     *
     * Admin-provisioned accounts are auto-verified because the app's mail
     * driver is `log` (MAIL_MAILER=log) — verification emails cannot be
     * delivered, and every authenticated route requires `verified`.
     */
    public function execute(UserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'role' => $data->role,
            ]);

            // Not in $fillable, so set explicitly.
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        });
    }
}
