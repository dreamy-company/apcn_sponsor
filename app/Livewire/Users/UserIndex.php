<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class UserIndex extends Component
{
    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }

    public function delete(int $userId): void
    {
        $this->authorizeJ4u();

        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->dispatch('toast-show', slots: ['text' => 'You cannot delete your own account.'], dataset: ['variant' => 'danger']);

            return;
        }

        if ($user->deals()->exists()) {
            $this->dispatch('toast-show', slots: ['text' => 'This user has deals and cannot be deleted.'], dataset: ['variant' => 'danger']);

            return;
        }

        $user->delete();

        $this->dispatch('toast-show', slots: ['text' => 'User deleted.'], dataset: ['variant' => 'success']);
    }

    public function render(): View
    {
        return view('livewire.users.user-index', [
            'users' => User::query()
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
