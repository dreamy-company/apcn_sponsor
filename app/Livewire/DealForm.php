<?php

namespace App\Livewire;

use App\Actions\Deal\CreateDealAction;
use App\Actions\Deal\StoreDealAssetAction;
use App\Actions\Deal\UpdateDealAction;
use App\DTOs\Deal\DealData;
use App\Enums\UserRole;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class DealForm extends Component
{
    use WithFileUploads;

    public const TOTAL_STEPS = 4;

    public ?Deal $deal = null;

    /** Current wizard step (1 Initiation · 2 Package & Items · 3 Payment Terms · 4 Summary). */
    public int $currentStep = 1;

    /** Furthest step reached — visited steps are clickable in the stepper. */
    public int $maxVisited = 1;

    public int $doctorId = 0;

    public string $companyName = '';

    public string $picName = '';

    public string $picContact = '';

    public ?int $packageId = null;

    public string $finalPrice = '';

    /** @var array<int, array{item_id: int, name: string, type: string|null, quota: int|null, is_addon: bool, checked: bool, custom_price: string}> */
    public array $items = [];

    /** @var array<int, array{description: string, due_date: string, amount: string}> */
    public array $paymentTerms = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $assets = [];

    public function mount(?Deal $deal = null): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->deal = $deal;

        if ($deal) {
            $deal->load(['sponsor', 'items']);

            $this->doctorId = $deal->doctor_id;
            $this->companyName = $deal->sponsor->company_name;
            $this->picName = $deal->sponsor->pic_name;
            $this->picContact = $deal->sponsor->pic_contact;
            $this->packageId = $deal->package_id;
            $this->finalPrice = $deal->final_price;

            $this->paymentTerms = $deal->paymentTerms()->get()
                ->map(fn ($term): array => [
                    'description' => $term->description,
                    'due_date' => $term->due_date->format('Y-m-d'),
                    'amount' => $term->amount,
                ])
                ->all();

            $this->rebuildItems($deal->items->mapWithKeys(function ($item): array {
                $pivot = $item->getAttribute('pivot');

                return [
                    $item->id => [
                        'is_addon' => (bool) $pivot->is_addon,
                        'custom_price' => $pivot->custom_price ?? '',
                    ],
                ];
            })->all());
        } else {
            $this->paymentTerms = [$this->emptyTerm()];
            $this->rebuildItems();
        }

        // When editing, every step is already valid — allow free navigation.
        if ($deal) {
            $this->maxVisited = self::TOTAL_STEPS;
        }
    }

    /**
     * Validate the current step, then advance. Step 2 also requires ≥1 item.
     */
    public function nextStep(): void
    {
        $rules = $this->rulesForStep($this->currentStep);

        if ($rules !== []) {
            $this->validate($rules);
        }

        if ($this->currentStep === 2) {
            if ($this->checkedItemCount() === 0) {
                $this->addError('items', __('Select at least one item.'));

                return;
            }

            if (($violation = $this->quotaViolation()) !== null) {
                $this->addError('items', $violation);

                return;
            }
        }

        $this->currentStep = min($this->currentStep + 1, self::TOTAL_STEPS);
        $this->maxVisited = max($this->maxVisited, $this->currentStep);
    }

    public function previousStep(): void
    {
        $this->currentStep = max(1, $this->currentStep - 1);
    }

    /**
     * Jump to an already-visited step (used by the stepper).
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->maxVisited) {
            $this->currentStep = $step;
        }
    }

    protected function checkedItemCount(): int
    {
        return collect($this->items)->filter(fn (array $row): bool => $row['checked'])->count();
    }

    /**
     * First quota problem among the chosen package / selected items, if any.
     * Quota is consumed by finalized deals only, so a full item can never be
     * added to a new deal (it could never be finalized).
     */
    protected function quotaViolation(): ?string
    {
        $quota = app(QuotaService::class);

        if ($this->packageId) {
            $package = Package::find($this->packageId);

            if ($package && $package->quota !== null) {
                $taken = $quota->packageTakenCount($package->id, $this->deal?->id);

                if ($quota->isFull($package->quota, $taken)) {
                    return __('Package ":name" is at full quota.', ['name' => $package->name]);
                }
            }
        }

        foreach ($this->items as $row) {
            if (! $row['checked'] || ($row['quota'] ?? null) === null) {
                continue;
            }

            $taken = $quota->itemTakenCount((int) $row['item_id'], $this->deal?->id);

            if ($quota->isFull($row['quota'], $taken)) {
                return __('Item ":name" is at full quota.', ['name' => $row['name']]);
            }
        }

        return null;
    }

    public function updatedPackageId(): void
    {
        // Snapshot current choices, then rebuild base/add-on flags for the new
        // package (base items become pre-checked — SRS FR-12).
        $current = collect($this->items)->mapWithKeys(fn (array $row): array => [$row['item_id'] => $row])->all();

        $this->rebuildItems();

        // Preserve the user's prior choice only for items that remain add-ons;
        // base items of the newly selected package stay pre-checked.
        $this->items = collect($this->items)->map(function (array $row) use ($current): array {
            if ($row['is_addon'] && isset($current[$row['item_id']])) {
                $row['checked'] = $current[$row['item_id']]['checked'];
                $row['custom_price'] = $current[$row['item_id']]['custom_price'];
            }

            return $row;
        })->all();

        if ($this->packageId) {
            $package = Package::find($this->packageId);
            $this->finalPrice = (string) ($package->default_price ?? $this->finalPrice);
        }
    }

    public function addPaymentTerm(): void
    {
        $this->paymentTerms[] = $this->emptyTerm();
    }

    public function removePaymentTerm(int $index): void
    {
        unset($this->paymentTerms[$index]);
        $this->paymentTerms = array_values($this->paymentTerms);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $items = collect($this->items)
            ->filter(fn (array $row): bool => $row['checked'])
            ->values()
            ->map(fn (array $row): array => [
                'item_id' => (int) $row['item_id'],
                'is_addon' => (bool) $row['is_addon'],
                'custom_price' => $row['custom_price'] !== '' ? (string) $row['custom_price'] : null,
            ])
            ->all();

        if ($items === []) {
            $this->addError('items', 'Select at least one item.');

            return;
        }

        if (($violation = $this->quotaViolation()) !== null) {
            $this->addError('items', $violation);

            return;
        }

        $data = new DealData(
            doctorId: (int) $validated['doctorId'],
            companyName: $validated['companyName'],
            picName: $validated['picName'],
            picContact: $validated['picContact'],
            packageId: $validated['packageId'],
            finalPrice: $validated['finalPrice'],
            items: $items,
            paymentTerms: array_values($this->paymentTerms),
        );

        $deal = $this->deal
            ? app(UpdateDealAction::class)->execute($this->deal, $data)
            : app(CreateDealAction::class)->execute($data);

        $uploaderId = auth()->id() !== null ? (int) auth()->id() : null;

        foreach ($this->assets as $file) {
            app(StoreDealAssetAction::class)->execute($deal, $file, $uploaderId);
        }

        session()->flash('status', $this->deal ? 'Deal updated.' : 'Deal created.');

        $this->redirect(route('deals.show', $deal), navigate: true);
    }

    public function render(): View
    {
        $quota = app(QuotaService::class);

        // taken (finalized) counts keyed by item / package id, excluding this deal.
        $itemsTaken = collect($this->items)
            ->filter(fn (array $row): bool => ($row['quota'] ?? null) !== null)
            ->mapWithKeys(fn (array $row): array => [
                $row['item_id'] => $quota->itemTakenCount((int) $row['item_id'], $this->deal?->id),
            ])
            ->all();

        $packages = Package::with('items')->orderBy('name')->get();

        $packagesTaken = $packages
            ->filter(fn (Package $p): bool => $p->quota !== null)
            ->mapWithKeys(fn (Package $p): array => [
                $p->id => $quota->packageTakenCount($p->id, $this->deal?->id),
            ])
            ->all();

        return view('livewire.deal-form', [
            'packages' => $packages,
            'doctors' => User::where('role', UserRole::Doctor->value)->orderBy('name')->get(),
            'itemsTaken' => $itemsTaken,
            'packagesTaken' => $packagesTaken,
        ]);
    }

    /**
     * @param  array<int, array{is_addon: bool, custom_price: string}>|null  $dealItemMap
     */
    protected function rebuildItems(?array $dealItemMap = null): void
    {
        $packageItemIds = $this->packageId
            ? Package::findOrFail($this->packageId)->items()->pluck('items.id')->all()
            : [];

        $this->items = Item::orderBy('name')->get()
            ->map(function (Item $item) use ($packageItemIds, $dealItemMap): array {
                $inDeal = $dealItemMap !== null && isset($dealItemMap[$item->id]);

                $isAddon = $inDeal
                    ? $dealItemMap[$item->id]['is_addon']
                    : ! in_array($item->id, $packageItemIds, true);

                return [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'quota' => $item->quota,
                    'is_addon' => $isAddon,
                    'checked' => $inDeal || ! $isAddon,
                    'custom_price' => $inDeal ? $dealItemMap[$item->id]['custom_price'] : '',
                ];
            })
            ->all();
    }

    /**
     * @return array{description: string, due_date: string, amount: string}
     */
    protected function emptyTerm(): array
    {
        return ['description' => '', 'due_date' => '', 'amount' => ''];
    }

    /**
     * The subset of rules validated when advancing from a given step.
     *
     * @return array<string, list<string|Exists>>
     */
    protected function rulesForStep(int $step): array
    {
        $rules = $this->rules();

        return match ($step) {
            1 => Arr::only($rules, ['doctorId', 'companyName', 'picName', 'picContact']),
            2 => Arr::only($rules, ['packageId', 'finalPrice', 'items.*.checked', 'items.*.custom_price']),
            3 => Arr::only($rules, ['paymentTerms.*.description', 'paymentTerms.*.due_date', 'paymentTerms.*.amount']),
            default => [],
        };
    }

    /**
     * @return array<string, list<string|Exists>>
     */
    protected function rules(): array
    {
        return [
            'doctorId' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Doctor->value)],
            'companyName' => ['required', 'string', 'max:255'],
            'picName' => ['required', 'string', 'max:255'],
            'picContact' => ['required', 'string', 'max:255'],
            'packageId' => ['nullable', 'integer', Rule::exists('packages', 'id')],
            'finalPrice' => ['required', 'numeric', 'min:0'],
            'items.*.checked' => ['boolean'],
            'items.*.custom_price' => ['nullable', 'numeric', 'min:0'],
            'assets' => ['array'],
            'assets.*' => ['file', 'max:51200'],
            'paymentTerms.*.description' => ['required', 'string', 'max:255'],
            'paymentTerms.*.due_date' => ['required', 'date'],
            'paymentTerms.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
