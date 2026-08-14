<?php

namespace App\Actions\User;

use App\DTOs\User\UserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAction
{
    /**
     * Update user details. The password is only changed when a new one is provided.
     */
    public function execute(User $user, UserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->name = $data->name;
            $user->email = $data->email;
            $user->role = $data->role;

            if ($data->password !== null && $data->password !== '') {
                $user->password = $data->password;
            }

            $user->save();

            return $user;
        });
    }
}
