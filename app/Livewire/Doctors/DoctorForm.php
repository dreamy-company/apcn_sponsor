<?php

namespace App\Livewire\Doctors;

use App\Actions\Doctor\CreateDoctorAction;
use App\Actions\Doctor\UpdateDoctorAction;
use App\DTOs\Doctor\DoctorData;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

class DoctorForm extends Component
{
    public ?User $doctor = null;

    public string $name = '';

    public string $phone = '';

    public function mount(?User $doctor = null): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        if ($doctor && ! $doctor->isDoctor()) {
            abort(404);
        }

        $this->doctor = $doctor;

        if ($doctor) {
            $this->name = $doctor->name;
            $this->phone = $doctor->phone ?? '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = new DoctorData(
            name: $validated['name'],
            phone: $validated['phone'] !== '' ? $validated['phone'] : null,
        );

        $this->doctor
            ? app(UpdateDoctorAction::class)->execute($this->doctor, $data)
            : app(CreateDoctorAction::class)->execute($data);

        session()->flash('status', $this->doctor ? 'Doctor updated.' : 'Doctor created.');

        $this->redirect(route('doctors.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.doctors.doctor-form');
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
