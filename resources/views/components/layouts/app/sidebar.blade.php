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

        </flux:navlist>

        <flux:spacer />

        <flux:navlist variant="outline">
            {{-- <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item> --}}

            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            </flux:radio.group>
        </flux:navlist>

        <!-- Logout Button - Always Visible -->
        <div class="mt-4 px-2">
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:button 
                    type="submit" 
                    variant="ghost" 
                    size="sm"
                    class="w-full justify-start text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                    data-test="sidebar-logout-button"
                >
                    <flux:icon.arrow-right-start-on-rectangle class="size-4 mr-2" />
                    {{ __('Log Out') }}
                </flux:button>
            </form>
        </div>

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <div class="flex items-center gap-2 p-2 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg" data-test="sidebar-menu-button">
                <img src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-8 w-8 rounded-lg">
                <div class="flex-1 text-start">
                    <div class="font-semibold text-sm">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-zinc-500 truncate">{{ auth()->user()->email }}</div>
                    <div class="text-xs text-blue-600 dark:text-blue-400 font-medium capitalize">{{ auth()->user()->getRoleNames()->first() ?? 'No Role' }}</div>
                </div>
                <flux:icon name="chevrons-up-down" class="h-4 w-4 text-zinc-400" />
            </div>

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

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden px-3 sm:px-4">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <div class="flex items-center gap-2 p-2 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg">
                <img src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg">
                <flux:icon name="chevron-down" class="h-3 w-3 sm:h-4 sm:w-4 text-zinc-400" />
            </div>

            <flux:menu class="w-[200px] sm:w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <img src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg">

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
