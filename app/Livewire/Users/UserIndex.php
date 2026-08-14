<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

class UserIndex extends Component
{
    use Toast;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }

    public function delete(int $userId): void
    {
        $this->authorizeJ4u();

        $user = User::admins()->findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->error(__('You cannot delete your own account.'));

            return;
        }

        $user->delete();

        $this->success(__('Administrator deleted.'));
    }

    public function render(): View
    {
        return view('livewire.users.user-index', [
            'users' => User::admins()
                ->withCount('deals')
                ->when($this->search !== '', fn ($q) => $q->where(function ($q): void {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->orderBy('name')
                ->get(),
        ]);
    }

    protected function authorizeJ4u(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }
}
