<?php

namespace App\Livewire\Doctors;

use App\Models\Setting;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

class DoctorIndex extends Component
{
    use Toast;

    #[Url]
    public string $search = '';

    public string $accessCode = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->accessCode = Setting::get('public_access_code', '') ?? '';
    }

    public function saveAccessCode(): void
    {
        $this->authorizeJ4u();

        $this->validate(['accessCode' => ['required', 'string', 'min:4', 'max:100']]);

        Setting::set('public_access_code', $this->accessCode);

        $this->success(__('Public access code updated.'));
    }

    public function delete(int $doctorId): void
    {
        $this->authorizeJ4u();

        $doctor = User::doctors()->findOrFail($doctorId);

        if ($doctor->deals()->exists()) {
            $this->error(__('This doctor has deals and cannot be deleted.'));

            return;
        }

        $doctor->delete();

        $this->success(__('Doctor deleted.'));
    }

    public function render(): View
    {
        return view('livewire.doctors.doctor-index', [
            'doctors' => User::doctors()
                ->withCount('deals')
                ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    protected function authorizeJ4u(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }
}
