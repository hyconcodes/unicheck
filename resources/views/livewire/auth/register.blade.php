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
    public string $level = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class, 'regex:/^[a-zA-Z]+\.[0-9]+@bouesti\.edu\.ng$/'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'department_id' => ['required', 'exists:departments,id'],
            'level' => ['required', 'in:100,200,300,400,500,600'],
        ], [
            'email.regex' => 'Email must be in the format: lastname.matric_no@bouesti.edu.ng',
            'department_id.required' => 'Please select your department.',
            'department_id.exists' => 'Selected department is invalid.',
            'level.required' => 'Please select your level.',
            'level.in' => 'Level must be between 100 and 600.',
        ]);

        // Extract matric_no from email (number after the lastname)
        preg_match('/\.([0-9]+)@/', $validated['email'], $matches);
        $validated['matric_no'] = $matches[1] ?? '';

        $validated['password'] = Hash::make($validated['password']);
        
        // Generate random 3D avatar
        $validated['avatar'] = User::generateRandomAvatar();

        $user = User::create($validated);
        
        // Assign student role by default
        $user->assignRole('student');

        event(new Registered($user));

        Auth::login($user);

        Session::regenerate();

        // Redirect to student dashboard
        $this->redirectIntended(route('student.dashboard', absolute: false), navigate: true);
    }

    public function with()
    {
        return [
            'departments' => Department::active()->orderBy('name')->get(),
            'levels' => [
                '100' => '100 Level',
                '200' => '200 Level', 
                '300' => '300 Level',
                '400' => '400 Level',
                '500' => '500 Level',
                '600' => '600 Level',
            ]
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

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
            placeholder="lastname.matricno@bouesti.edu.ng"
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

        <!-- Level -->
        <flux:select
            wire:model="level"
            :label="__('Level')"
            required
            placeholder="Select your level"
        >
            @foreach($levels as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
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
            <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Are you a lecturer?') }}</span>
        <flux:link :href="route('register.lecturer')" wire:navigate class="text-green-600 hover:text-green-700 font-medium">{{ __('Register as Lecturer') }}</flux:link>
    </div>
</div>
