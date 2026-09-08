<?php

namespace App\Livewire;

use App\Actions\Catalog\CreateItemAction;
use App\Actions\Catalog\CreatePackageAction;
use App\Actions\Deal\CreateDealAction;
use App\Actions\Deal\StoreDealAssetAction;
use App\Actions\Deal\UpdateDealAction;
use App\Actions\Doctor\CreateDoctorAction;
use App\DTOs\Catalog\ItemData;
use App\DTOs\Catalog\PackageData;
use App\DTOs\Deal\DealData;
use App\DTOs\Doctor\DoctorData;
use App\Enums\Currency;
use App\Enums\UserRole;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\User;
use App\Services\QuotaService;
use App\Support\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class DealForm extends Component
{
    use Toast, WithFileUploads;

    public const TOTAL_STEPS = 4;

    public ?Deal $deal = null;

    /** Current wizard step (1 Initiation · 2 Package & Items · 3 Payment Terms · 4 Summary). */
    public int $currentStep = 1;

    /** Furthest step reached — visited steps are clickable in the stepper. */
    public int $maxVisited = 1;

    public int $doctorId = 0;

    /** Legal entity (PT) the deal is signed with. */
    public string $companyName = '';

    /** Brand negotiated under that entity — exactly one per deal. */
    public string $brandName = '';

    public string $picName = '';

    public string $picContact = '';

    public ?int $packageId = null;

    /** Currency the deal is transacted in (IDR|USD). */
    public string $currency = Currency::IDR->value;

    /** What the sponsor gets on this deal — one block for the whole deal. */
    public string $inclusion = '';

    public string $finalPrice = '';

    /** Filters the item grid; never mutates $items, so hidden rows keep their state. */
    public string $itemSearch = '';

    /** @var array<int, array{item_id: int, name: string, type: string|null, quota: int|null, quantity: int, catalog_inclusion: string, is_addon: bool, checked: bool, custom_price: string}> */
    public array $items = [];

    /** @var array<int, array{id: int|null, description: string, due_date: string, amount: string, notes: string}> */
    public array $paymentTerms = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $assets = [];

    // --- Inline "create doctor" (shown when the typed name has no match) ---
    public bool $showDoctorModal = false;

    public string $newDoctorName = '';

    public string $newDoctorPhone = '';

    // --- Inline catalog creation ---
    public bool $showItemModal = false;

    public string $newItemName = '';

    public string $newItemType = '';

    public string $newItemInclusion = '';

    public string $newItemPriceIdr = '';

    public string $newItemPriceUsd = '';

    public string $newItemQuota = '';

    public bool $newItemRequiresMaterial = false;

    public bool $showPackageModal = false;

    public string $newPackageName = '';

    public string $newPackagePriceIdr = '';

    public string $newPackagePriceUsd = '';

    public string $newPackageQuota = '';

    /** Set once a saved draft has been pulled back in, so the UI can say so. */
    public bool $draftRestored = false;

    /**
     * Doctor options for the searchable combobox. Plain arrays, not models —
     * Livewire has to round-trip this between requests.
     *
     * @var array<int, array{id: int, name: string}>
     */
    public array $doctorOptions = [];

    public function mount(?Deal $deal = null): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->deal = $deal;

        if ($deal) {
            $deal->load(['sponsor', 'items']);

            $this->doctorId = $deal->doctor_id;
            $this->companyName = $deal->sponsor->company_name;
            $this->brandName = $deal->sponsor->brand_name ?? '';
            $this->picName = $deal->sponsor->pic_name;
            $this->picContact = $deal->sponsor->pic_contact;
            $this->packageId = $deal->package_id;
            $this->currency = $deal->currency->value;
            $this->inclusion = $deal->inclusion ?? '';
            $this->finalPrice = Money::plain($deal->final_price);

            $this->paymentTerms = $deal->paymentTerms()->get()
                ->map(fn ($term): array => [
                    'id' => $term->id,
                    'description' => $term->description,
                    'due_date' => $term->due_date->format('Y-m-d'),
                    'amount' => $term->amount,
                    'notes' => $term->notes ?? '',
                ])
                ->all();

            $this->rebuildItems($deal->items->mapWithKeys(function ($item): array {
                $pivot = $item->getAttribute('pivot');

                return [
                    $item->id => [
                        'is_addon' => (bool) $pivot->is_addon,
                        'quantity' => max(1, (int) $pivot->quantity),
                        'custom_price' => Money::plain($pivot->custom_price),
                    ],
                ];
            })->all());
        } else {
            $this->paymentTerms = [$this->emptyTerm()];
            $this->rebuildItems();
        }

        $this->refreshDoctorOptions();

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

    // ---------------------------------------------------------------- Money

    public function currencyEnum(): Currency
    {
        return Currency::tryFrom($this->currency) ?? Currency::IDR;
    }

    /**
     * BR-07: the accumulated rate-card value of the deal's scope — the base
     * package price plus every selected add-on. Items included in the package
     * contribute nothing, as the tier price already covers them.
     *
     * Informational only: final_price stays authoritative and manual (BR-02).
     */
    public function subtotal(): float
    {
        $subtotal = 0.0;

        if ($this->packageId) {
            $package = Package::find($this->packageId);
            $column = $this->currencyEnum()->priceColumn();
            $subtotal += (float) ($package?->getAttribute($column) ?? 0);
        }

        foreach ($this->items as $row) {
            if (! $row['checked'] || ! $row['is_addon']) {
                continue;
            }

            $unitPrice = (float) ($row['custom_price'] !== '' ? $row['custom_price'] : 0);
            $subtotal += $unitPrice * max(1, (int) $row['quantity']);
        }

        return $subtotal;
    }

    public function termsTotal(): float
    {
        return collect($this->paymentTerms)
            ->sum(fn (array $term): float => (float) ($term['amount'] ?: 0));
    }

    /**
     * Difference between the payment terms and the final price. Zero (within a
     * cent) means balanced — a prerequisite for finalizing (BR-08).
     */
    public function termsDifference(): float
    {
        return round($this->termsTotal() - (float) ($this->finalPrice ?: 0), 2);
    }

    public function termsBalanced(): bool
    {
        return abs($this->termsDifference()) < 0.005;
    }

    /**
     * Adopt the computed subtotal as the agreed price (one-click convenience —
     * the field stays editable).
     */
    public function useSubtotalAsFinalPrice(): void
    {
        $this->finalPrice = Money::plain($this->subtotal());
    }

    /**
     * Switching currency re-reads add-on prices from the matching catalog column,
     * since a price in one currency is meaningless in the other.
     */
    public function updatedCurrency(): void
    {
        $column = $this->currencyEnum()->priceColumn();
        $prices = Item::query()->pluck($column, 'id');

        $this->items = collect($this->items)->map(function (array $row) use ($prices): array {
            if ($row['is_addon']) {
                $row['custom_price'] = Money::plain($prices[$row['item_id']] ?? null);
            }

            return $row;
        })->all();

        if ($this->packageId) {
            $package = Package::find($this->packageId);
            $this->finalPrice = Money::plain($package?->getAttribute($column));
        }
    }

    // ------------------------------------------------------------- Doctors

    /**
     * Search callback for the doctor combobox (Mary x-choices searchable).
     */
    public function searchDoctors(string $value = ''): void
    {
        $matches = User::query()
            ->where('role', UserRole::Doctor->value)
            ->when($value !== '', fn ($q) => $q->where('name', 'like', "%{$value}%"))
            ->orderBy('name')
            ->take(20)
            ->get();

        // Keep the current selection present so the combobox can still label it.
        $this->doctorOptions = $this->withSelectedDoctor($this->toOptions($matches));
    }

    /**
     * Create a doctor inline. Contact is required so the record is usable
     * (doctors are non-login users reached via their public link).
     */
    public function createDoctor(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $validated = $this->validate([
            'newDoctorName' => ['required', 'string', 'max:255'],
            'newDoctorPhone' => ['required', 'string', 'max:50'],
        ]);

        $doctor = app(CreateDoctorAction::class)->execute(new DoctorData(
            name: $validated['newDoctorName'],
            phone: $validated['newDoctorPhone'],
        ));

        $this->doctorId = $doctor->id;
        $this->newDoctorName = '';
        $this->newDoctorPhone = '';
        $this->showDoctorModal = false;

        $this->refreshDoctorOptions();
        $this->success(__('Doctor added.'));
    }

    // ------------------------------------------------- Inline catalog create

    public function createItem(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $validated = $this->validate([
            'newItemName' => ['required', 'string', 'max:255'],
            'newItemType' => ['nullable', 'string', 'max:255'],
            'newItemInclusion' => ['nullable', 'string', 'max:2000'],
            'newItemPriceIdr' => ['nullable', 'numeric', 'min:0'],
            'newItemPriceUsd' => ['nullable', 'numeric', 'min:0'],
            'newItemQuota' => ['nullable', 'integer', 'min:0'],
        ]);

        $item = app(CreateItemAction::class)->execute(new ItemData(
            name: $validated['newItemName'],
            type: $validated['newItemType'] !== '' ? $validated['newItemType'] : null,
            inclusion: $validated['newItemInclusion'] !== '' ? $validated['newItemInclusion'] : null,
            quota: $validated['newItemQuota'] !== '' ? (int) $validated['newItemQuota'] : null,
            defaultPriceIdr: $validated['newItemPriceIdr'] !== '' ? $validated['newItemPriceIdr'] : null,
            defaultPriceUsd: $validated['newItemPriceUsd'] !== '' ? $validated['newItemPriceUsd'] : null,
            requiresMaterial: $this->newItemRequiresMaterial,
        ));

        // Rebuild the grid around the existing selection, then select the new item.
        $this->rebuildItems($this->currentSelectionMap());
        $this->setChecked($item->id, true);

        $this->reset(['newItemName', 'newItemType', 'newItemInclusion', 'newItemPriceIdr', 'newItemPriceUsd', 'newItemQuota', 'newItemRequiresMaterial']);
        $this->showItemModal = false;
        $this->success(__('Item added to the catalog.'));
    }

    public function createPackage(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $validated = $this->validate([
            'newPackageName' => ['required', 'string', 'max:255'],
            'newPackagePriceIdr' => ['nullable', 'numeric', 'min:0'],
            'newPackagePriceUsd' => ['nullable', 'numeric', 'min:0'],
            'newPackageQuota' => ['nullable', 'integer', 'min:0'],
        ]);

        // The new tier starts with whatever is currently selected as its contents.
        $selectedItems = [];

        foreach ($this->items as $row) {
            if ($row['checked']) {
                $selectedItems[(int) $row['item_id']] = max(1, (int) $row['quantity']);
            }
        }

        $package = app(CreatePackageAction::class)->execute(new PackageData(
            name: $validated['newPackageName'],
            defaultPriceIdr: $validated['newPackagePriceIdr'] !== '' ? $validated['newPackagePriceIdr'] : null,
            defaultPriceUsd: $validated['newPackagePriceUsd'] !== '' ? $validated['newPackagePriceUsd'] : null,
            quota: $validated['newPackageQuota'] !== '' ? (int) $validated['newPackageQuota'] : null,
            items: $selectedItems,
        ));

        $this->packageId = $package->id;
        $this->updatedPackageId();

        $this->reset(['newPackageName', 'newPackagePriceIdr', 'newPackagePriceUsd', 'newPackageQuota']);
        $this->showPackageModal = false;
        $this->success(__('Package added to the catalog.'));
    }

    // ---------------------------------------------------------------- Items

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
            $units = max(1, (int) $row['quantity']);

            if ($taken + $units > $row['quota']) {
                return __('Item ":name" needs :units unit(s) but only :remaining remain.', [
                    'name' => $row['name'],
                    'units' => $units,
                    'remaining' => max(0, $row['quota'] - $taken),
                ]);
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
                $row['quantity'] = $current[$row['item_id']]['quantity'];
            }

            return $row;
        })->all();

        if ($this->packageId) {
            $package = Package::find($this->packageId);
            $price = $package?->getAttribute($this->currencyEnum()->priceColumn());
            $this->finalPrice = $price !== null ? Money::plain($price) : $this->finalPrice;
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

    /**
     * Assign the still-unallocated balance to the given term, so the terms sum
     * to the final price and the deal can be finalized (BR-08).
     */
    public function balanceTerm(int $index): void
    {
        if (! isset($this->paymentTerms[$index])) {
            return;
        }

        $others = collect($this->paymentTerms)
            ->except([$index])
            ->sum(fn (array $term): float => (float) ($term['amount'] ?: 0));

        $remaining = round((float) ($this->finalPrice ?: 0) - $others, 2);

        $this->paymentTerms[$index]['amount'] = (string) max(0, $remaining);
    }

    /**
     * Whether this form should keep a local draft. Only new deals: an edit is
     * already persisted, and restoring a stale draft over it would be wrong.
     */
    public function keepsDraft(): bool
    {
        return $this->deal === null;
    }

    /**
     * Re-apply a draft snapshot taken from localStorage after an accidental
     * refresh. Everything is treated as untrusted: values are whitelisted, and
     * item selections are matched back to the live catalog by id so a draft
     * written before an item was added or removed cannot corrupt the form.
     *
     * @param  array<string, mixed>  $draft
     */
    public function restoreDraft(array $draft): void
    {
        if (! $this->keepsDraft()) {
            return;
        }

        foreach (['companyName', 'brandName', 'picName', 'picContact', 'inclusion', 'finalPrice'] as $field) {
            if (isset($draft[$field]) && is_scalar($draft[$field])) {
                $this->{$field} = (string) $draft[$field];
            }
        }

        if (isset($draft['doctorId']) && is_numeric($draft['doctorId'])) {
            $doctorId = (int) $draft['doctorId'];

            // Only keep it if that doctor still exists.
            if (User::query()->whereKey($doctorId)->where('role', UserRole::Doctor->value)->exists()) {
                $this->doctorId = $doctorId;
            }
        }

        if (isset($draft['currency']) && Currency::tryFrom((string) $draft['currency']) !== null) {
            $this->currency = (string) $draft['currency'];
        }

        $packageId = isset($draft['packageId']) && is_numeric($draft['packageId'])
            ? (int) $draft['packageId']
            : null;

        $this->packageId = $packageId !== null && Package::query()->whereKey($packageId)->exists()
            ? $packageId
            : null;

        $this->rebuildItems();
        $this->applyDraftItems(is_array($draft['items'] ?? null) ? $draft['items'] : []);
        $this->restoreDraftTerms(is_array($draft['paymentTerms'] ?? null) ? $draft['paymentTerms'] : []);

        $step = isset($draft['currentStep']) && is_numeric($draft['currentStep'])
            ? (int) $draft['currentStep']
            : 1;

        $this->currentStep = max(1, min($step, self::TOTAL_STEPS));
        $this->maxVisited = max($this->currentStep, 1);

        $this->draftRestored = true;
        $this->refreshDoctorOptions();
    }

    /**
     * Throw the draft away and start from an empty form.
     */
    public function discardDraft(): void
    {
        $this->reset([
            'doctorId', 'companyName', 'brandName', 'picName', 'picContact',
            'packageId', 'currency', 'inclusion', 'finalPrice', 'itemSearch',
            'currentStep', 'maxVisited', 'draftRestored',
        ]);

        $this->paymentTerms = [$this->emptyTerm()];
        $this->rebuildItems();
        $this->refreshDoctorOptions();

        $this->dispatch('deal-draft-cleared');
    }

    /**
     * Re-apply saved per-item choices onto the freshly rebuilt catalog rows.
     *
     * @param  array<mixed>  $saved
     */
    protected function applyDraftItems(array $saved): void
    {
        $byId = [];

        foreach ($saved as $row) {
            if (is_array($row) && isset($row['item_id']) && is_numeric($row['item_id'])) {
                $byId[(int) $row['item_id']] = $row;
            }
        }

        $this->items = collect($this->items)->map(function (array $row) use ($byId): array {
            $saved = $byId[(int) $row['item_id']] ?? null;

            if ($saved === null) {
                return $row;
            }

            $row['checked'] = (bool) ($saved['checked'] ?? $row['checked']);
            $row['quantity'] = max(1, (int) ($saved['quantity'] ?? $row['quantity']));

            if (isset($saved['custom_price']) && is_scalar($saved['custom_price'])) {
                $row['custom_price'] = (string) $saved['custom_price'];
            }

            return $row;
        })->all();
    }

    /**
     * @param  array<mixed>  $saved
     */
    protected function restoreDraftTerms(array $saved): void
    {
        $terms = [];

        foreach ($saved as $row) {
            if (! is_array($row)) {
                continue;
            }

            $terms[] = [
                'id' => null,
                'description' => (string) ($row['description'] ?? ''),
                'due_date' => (string) ($row['due_date'] ?? ''),
                'amount' => (string) ($row['amount'] ?? ''),
                'notes' => (string) ($row['notes'] ?? ''),
            ];
        }

        $this->paymentTerms = $terms !== [] ? $terms : [$this->emptyTerm()];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $items = collect($this->items)
            ->filter(fn (array $row): bool => $row['checked'])
            ->values()
            ->map(fn (array $row): array => [
                'item_id' => (int) $row['item_id'],
                'quantity' => max(1, (int) $row['quantity']),
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

        $terms = collect($this->paymentTerms)->map(fn (array $term): array => [
            'id' => $term['id'] !== null ? (int) $term['id'] : null,
            'description' => $term['description'],
            'due_date' => $term['due_date'],
            'amount' => $term['amount'],
            'notes' => $term['notes'] !== '' ? $term['notes'] : null,
        ])->values()->all();

        $data = new DealData(
            doctorId: (int) $validated['doctorId'],
            companyName: $validated['companyName'],
            brandName: $validated['brandName'],
            picName: $validated['picName'],
            picContact: $validated['picContact'],
            packageId: $validated['packageId'],
            currency: $this->currencyEnum()->value,
            subtotal: (string) $this->subtotal(),
            inclusion: trim($this->inclusion) !== '' ? $this->inclusion : null,
            finalPrice: $validated['finalPrice'],
            items: $items,
            paymentTerms: $terms,
        );

        $deal = $this->deal
            ? app(UpdateDealAction::class)->execute($this->deal, $data)
            : app(CreateDealAction::class)->execute($data);

        $uploaderId = auth()->id() !== null ? (int) auth()->id() : null;

        foreach ($this->assets as $file) {
            app(StoreDealAssetAction::class)->execute($deal, $file, $uploaderId);
        }

        // The deal is persisted now, so the local draft must not linger and be
        // restored over the next new deal.
        $this->dispatch('deal-draft-cleared');

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

        // Grouping is a render-order concern only: $items itself is never
        // filtered or re-sorted, because wire:model binds to its indexes.
        // Search narrows the "available" list only — a chosen item must stay visible.
        $search = trim($this->itemSearch);
        $selectedItemKeys = [];
        $availableItemKeys = [];

        foreach ($this->items as $index => $row) {
            if ($row['checked']) {
                $selectedItemKeys[] = $index;

                continue;
            }

            if ($search === '' || str_contains(mb_strtolower($row['name']), mb_strtolower($search))) {
                $availableItemKeys[] = $index;
            }
        }

        return view('livewire.deal-form', [
            'packages' => $packages,
            'itemsTaken' => $itemsTaken,
            'packagesTaken' => $packagesTaken,
            'selectedItemKeys' => $selectedItemKeys,
            'availableItemKeys' => $availableItemKeys,
            'currencyEnum' => $this->currencyEnum(),
            'subtotal' => $this->subtotal(),
            'termsTotal' => $this->termsTotal(),
            'termsDifference' => $this->termsDifference(),
        ]);
    }

    /**
     * Load the default (unfiltered) doctor options.
     */
    protected function refreshDoctorOptions(): void
    {
        $doctors = User::query()
            ->where('role', UserRole::Doctor->value)
            ->orderBy('name')
            ->take(20)
            ->get();

        $this->doctorOptions = $this->withSelectedDoctor($this->toOptions($doctors));
    }

    /**
     * @param  Collection<int, User>  $doctors
     * @return array<int, array{id: int, name: string}>
     */
    protected function toOptions(Collection $doctors): array
    {
        return $doctors
            ->map(fn (User $doctor): array => ['id' => $doctor->id, 'name' => $doctor->name])
            ->values()
            ->all();
    }

    /**
     * Keep the selected doctor in the option list, so the combobox can label it
     * even when the current search results no longer include it.
     *
     * @param  array<int, array{id: int, name: string}>  $options
     * @return array<int, array{id: int, name: string}>
     */
    protected function withSelectedDoctor(array $options): array
    {
        if ($this->doctorId <= 0 || collect($options)->contains('id', $this->doctorId)) {
            return $options;
        }

        $selected = User::find($this->doctorId);

        return $selected !== null
            ? array_merge([['id' => $selected->id, 'name' => $selected->name]], $options)
            : $options;
    }

    /**
     * @return array<int, array{is_addon: bool, custom_price: string}>
     */
    protected function currentSelectionMap(): array
    {
        return collect($this->items)
            ->filter(fn (array $row): bool => $row['checked'])
            ->mapWithKeys(fn (array $row): array => [
                $row['item_id'] => [
                    'is_addon' => $row['is_addon'],
                    'quantity' => $row['quantity'],
                    'custom_price' => $row['custom_price'],
                ],
            ])
            ->all();
    }

    protected function setChecked(int $itemId, bool $checked): void
    {
        $this->items = collect($this->items)->map(function (array $row) use ($itemId, $checked): array {
            if ((int) $row['item_id'] === $itemId) {
                $row['checked'] = $checked;
            }

            return $row;
        })->all();
    }

    /**
     * @param  array<int, array{is_addon: bool, custom_price: string, quantity?: int}>|null  $dealItemMap
     */
    protected function rebuildItems(?array $dealItemMap = null): void
    {
        // item id => units the chosen tier bundles (e.g. Diamond includes 5 booths).
        $packageQuantities = $this->packageId
            ? Package::findOrFail($this->packageId)->items
                ->mapWithKeys(fn (Item $item): array => [
                    $item->id => max(1, (int) $item->getAttribute('pivot')->quantity),
                ])->all()
            : [];

        $priceColumn = $this->currencyEnum()->priceColumn();

        $this->items = Item::orderBy('name')->get()
            ->map(function (Item $item) use ($packageQuantities, $dealItemMap, $priceColumn): array {
                $inDeal = $dealItemMap !== null && isset($dealItemMap[$item->id]);
                $inPackage = array_key_exists($item->id, $packageQuantities);

                $isAddon = $inDeal
                    ? $dealItemMap[$item->id]['is_addon']
                    : ! $inPackage;

                $catalogPrice = $item->getAttribute($priceColumn);

                return [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'quota' => $item->quota,
                    // Package items inherit the tier's bundled count; add-ons start at 1.
                    'quantity' => $inDeal
                        ? max(1, (int) ($dealItemMap[$item->id]['quantity'] ?? 1))
                        : ($inPackage ? $packageQuantities[$item->id] : 1),
                    'catalog_inclusion' => $item->inclusion ?? '',
                    'is_addon' => $isAddon,
                    'checked' => $inDeal || ! $isAddon,
                    'custom_price' => $inDeal
                        ? $dealItemMap[$item->id]['custom_price']
                        : ($isAddon ? Money::plain($catalogPrice) : ''),
                ];
            })
            ->all();
    }

    /**
     * @return array{id: int|null, description: string, due_date: string, amount: string, notes: string}
     */
    protected function emptyTerm(): array
    {
        return ['id' => null, 'description' => '', 'due_date' => '', 'amount' => '', 'notes' => ''];
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
            1 => Arr::only($rules, ['doctorId', 'companyName', 'brandName', 'picName', 'picContact']),
            2 => Arr::only($rules, ['packageId', 'currency', 'finalPrice', 'inclusion', 'items.*.checked', 'items.*.custom_price', 'items.*.quantity']),
            3 => Arr::only($rules, ['paymentTerms.*.description', 'paymentTerms.*.due_date', 'paymentTerms.*.amount', 'paymentTerms.*.notes']),
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
            'brandName' => ['required', 'string', 'max:255'],
            'picName' => ['required', 'string', 'max:255'],
            'picContact' => ['required', 'string', 'max:255'],
            'packageId' => ['nullable', 'integer', Rule::exists('packages', 'id')],
            'currency' => ['required', Rule::enum(Currency::class)],
            'inclusion' => ['nullable', 'string', 'max:5000'],
            'finalPrice' => ['required', 'numeric', 'min:0'],
            'items.*.checked' => ['boolean'],
            'items.*.custom_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'assets' => ['array'],
            'assets.*' => ['file', 'max:51200'],
            'paymentTerms.*.description' => ['required', 'string', 'max:255'],
            'paymentTerms.*.due_date' => ['required', 'date'],
            'paymentTerms.*.amount' => ['required', 'numeric', 'min:0'],
            'paymentTerms.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
