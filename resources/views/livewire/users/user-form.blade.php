<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $user ? __('Edit User') : __('New User') }}</flux:heading>
                <flux:text class="mt-1">{{ __('A user represents a doctor or a J4U committee member.') }}</flux:text>
            </div>
            <flux:button variant="ghost" href="{{ route('users.index') }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
        @endif

        <flux:card>
            <form wire:submit="save" class="space-y-4">
                <flux:fieldset>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Name') }}</flux:label>
                            <flux:input wire:model="name" placeholder="{{ __('Dr. Budi Santoso') }}" />
                            <flux:error name="name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Email') }}</flux:label>
                            <flux:input wire:model="email" type="email" placeholder="budi@example.com" />
                            <flux:error name="email" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Role') }}</flux:label>
                            <flux:select wire:model="role" placeholder="{{ __('Select role...') }}">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="role" />
                        </flux:field>

                        <flux:field>
                            <flux:label>
                                {{ __('Password') }}
                                <flux:text class="text-xs">
                                    {{ $user ? __('leave blank to keep current') : __('min 8 characters') }}
                                </flux:text>
                            </flux:label>
                            <flux:input wire:model="password" type="password" autocomplete="new-password" placeholder="••••••••" />
                            <flux:error name="password" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Confirm Password') }}</flux:label>
                            <flux:input wire:model="passwordConfirmation" type="password" autocomplete="new-password" placeholder="••••••••" />
                            <flux:error name="password" />
                        </flux:field>
                    </div>
                </flux:fieldset>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" href="{{ route('users.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ $user ? __('Save Changes') : __('Create User') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</section>
