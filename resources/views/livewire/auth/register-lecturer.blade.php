<?php

use App\Models\User;
use App\Models\Department;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $department_id = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class, 'regex:/^[a-zA-Z.]+@bouesti\.edu\.ng$/'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'department_id' => ['required', 'exists:departments,id'],
        ], [
            'email.regex' => 'Email must be in the format: name@bouesti.edu.ng',
            'department_id.required' => 'Please select your department.',
            'department_id.exists' => 'Selected department is invalid.',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        
        // Generate random 3D avatar
        $validated['avatar'] = User::generateRandomAvatar();

        $user = User::create($validated);
        
        // Assign lecturer role by default
        $user->assignRole('lecturer');

        event(new Registered($user));

        Auth::login($user);

        Session::regenerate();

        // Redirect to lecturer dashboard
        $this->redirectIntended(route('lecturer.dashboard', absolute: false), navigate: true);
    }

    public function with()
    {
        return [
            'departments' => Department::active()->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create lecturer account')" :description="__('Enter your details below to create your lecturer account')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <flux:input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autofocus
            autocomplete="name"
            :placeholder="__('Full name')"
        />

        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autocomplete="email"
            placeholder="name@bouesti.edu.ng"
        />

        <!-- Department -->
        <flux:select
            wire:model="department_id"
            :label="__('Department')"
            required
            placeholder="Select your department"
        >
            @foreach($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
            @endforeach
        </flux:select>

        <!-- Password -->
        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
            viewable
        />

        <!-- Confirm Password -->
        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm password')"
            viewable
        />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full" data-test="register-lecturer-button">
                {{ __('Create lecturer account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Are you a student?') }}</span>
        <flux:link :href="route('register')" wire:navigate class="text-green-600 hover:text-green-700 font-medium">{{ __('Register as Student') }}</flux:link>
    </div>
</div>