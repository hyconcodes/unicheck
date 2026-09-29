<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse mb-4 sm:mb-6" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate class="text-sm sm:text-base">{{ __('Dashboard') }}</flux:navlist.item>

                @can('can.view.roles')
                <flux:navlist.item icon="key" :href="route('admin.role-permission-manager')" :current="request()->routeIs('admin.role-permission-manager')"
                    wire:navigate class="text-sm sm:text-base">{{ __('Roles & Permission') }}</flux:navlist.item>
                @endcan

                @role('superadmin')
                <flux:navlist.item icon="users" :href="route('superadmin.account-manager')" :current="request()->routeIs('superadmin.account-manager')"
                    wire:navigate class="text-sm sm:text-base">{{ __('Account Management') }}</flux:navlist.item>
                <flux:navlist.item icon="presentation-chart-bar" :href="route('superadmin.class-manager')" :current="request()->routeIs('superadmin.class-manager')"
                    wire:navigate class="text-sm sm:text-base">{{ __('Class Manager') }}</flux:navlist.item>
                <flux:navlist.item icon="building-office" :href="route('superadmin.department-manager')" :current="request()->routeIs('superadmin.department-manager')"
                    wire:navigate class="text-sm sm:text-base">{{ __('Department Manager') }}</flux:navlist.item>
                <flux:navlist.item icon="academic-cap" :href="route('superadmin.level-promotion-manager')" :current="request()->routeIs('superadmin.level-promotion-manager')"
                    wire:navigate class="text-sm sm:text-base">{{ __('Level Promotion') }}</flux:navlist.item>
                <flux:navlist.item icon="chat-bubble-left-ellipsis" :href="route('superadmin.complaints')" :current="request()->routeIs('superadmin.complaints')"
                    wire:navigate class="text-sm sm:text-base">{{ __('Manage Complaints') }}</flux:navlist.item>
                @endrole

                @role('lecturer')
                <flux:navlist.item icon="presentation-chart-bar" :href="route('lecturer.classes')" :current="request()->routeIs('lecturer.classes')"
                    wire:navigate class="text-sm sm:text-base">{{ __('My Classes') }}</flux:navlist.item>
                @endrole

                @role('student')
                <flux:navlist.item icon="academic-cap" :href="route('student.classes')" :current="request()->routeIs('student.classes', 'student.mark-attendance')"
                    wire:navigate class="text-sm sm:text-base">{{ __('My Classes') }}</flux:navlist.item>
                <flux:navlist.item icon="exclamation-triangle" :href="route('student.complaints')" :current="request()->routeIs('student.complaints')"
                    wire:navigate class="text-sm sm:text-base">{{ __('My Complaints') }}</flux:navlist.item>
                @endrole
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Account')" class="grid">
                <flux:navlist.item icon="user-circle" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'password.edit', 'appearance.edit', 'two-factor.show')"
                    wire:navigate class="text-sm sm:text-base">{{ __('Profile Settings') }}</flux:navlist.item>
            </flux:navlist.group>

        </flux:navlist>

        <flux:spacer />

        {{-- Theme toggle --}}
        <div class="px-2 pb-2">
            <flux:radio.group variant="segmented" x-model="$flux.appearance" class="w-full flex">
                <flux:radio value="light" icon="sun" class="flex-1 justify-center">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon" class="flex-1 justify-center">{{ __('Dark') }}</flux:radio>
            </flux:radio.group>
        </div>

        {{-- Logout --}}
        <div class="px-2 pb-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:navlist.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                    {{ __('Log Out') }}
                </flux:navlist.item>
            </form>
        </div>

        {{-- Desktop User Menu --}}
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                :avatar="auth()->user()->getAvatarUrl()"
                data-test="sidebar-menu-button"
            />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <img src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-8 w-8 rounded-lg">

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                <span class="truncate text-xs text-blue-600 dark:text-blue-400 font-medium capitalize">{{ auth()->user()->getRoleNames()->first() ?? 'No Role' }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="text-sm">{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-sm"
                        data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile header: sidebar toggle + avatar dropdown -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="bottom" align="end">
            <flux:profile
                :initials="auth()->user()->initials()"
                :avatar="auth()->user()->getAvatarUrl()"
                icon:trailing="chevrons-up-down"
            />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <img src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-8 w-8 rounded-lg">

                            <div class="grid flex-1 text-start text-sm leading-tight min-w-0">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                <span class="truncate text-xs text-blue-600 dark:text-blue-400 font-medium capitalize">{{ auth()->user()->getRoleNames()->first() ?? 'No Role' }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="text-sm">{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-sm"
                        data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    <!-- Global Toast Notification -->
    <x-toast-notification />

    @fluxScripts
</body>

</html>
