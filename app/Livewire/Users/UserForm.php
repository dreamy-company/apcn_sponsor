<?php

namespace App\Livewire\Users;

use App\Actions\User\CreateUserAction;
use App\Actions\User\UpdateUserAction;
use App\DTOs\User\UserData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Component;

class UserForm extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(?User $user = null): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->user = $user;

        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role->value;
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        // J4U must not demote/promote their own account (would lock themselves out).
        if ($this->user && $this->user->id === Auth::id() && $this->user->role->value !== $validated['role']) {
            $this->addError('role', 'You cannot change your own role.');

            return;
        }

        $data = new UserData(
            name: $validated['name'],
            email: $validated['email'],
            role: UserRole::from($validated['role']),
            password: $validated['password'] !== '' ? $validated['password'] : null,
        );

        $this->user
            ? app(UpdateUserAction::class)->execute($this->user, $data)
            : app(CreateUserAction::class)->execute($data);

        session()->flash('status', $this->user ? 'User updated.' : 'User created.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.users.user-form', [
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * @return array<string, list<string|Password|Rule>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => $this->user
                ? ['nullable', 'string', Password::default(), 'same:passwordConfirmation']
                : ['required', 'string', Password::default(), 'same:passwordConfirmation'],
        ];
    }
}
