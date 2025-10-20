<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
// use Illuminate\Support\Facades\Auth;


Route::get('/', function () {
    return view('welcome');
})->name('home');

// Role-based dashboard routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect based on user role
    Route::get('dashboard', function () {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin')) {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->hasRole('lecturer')) {
            return redirect()->route('lecturer.dashboard');
        } else {
            return redirect()->route('student.dashboard');
        }
    })->name('dashboard');

    // Role-specific dashboards
    Route::middleware(['role:superadmin'])->group(function () {
        Volt::route('superadmin/dashboard', 'dashboard.superadmin')->name('superadmin.dashboard');
        Volt::route('superadmin/roles-permissions', 'admin.role-permission-manager')->name('admin.role-permission-manager');
    });

    Route::middleware(['role:lecturer'])->group(function () {
        Volt::route('lecturer/dashboard', 'dashboard.lecturer')->name('lecturer.dashboard');
    });

    Route::middleware(['role:student'])->group(function () {
        Volt::route('student/dashboard', 'dashboard.student')->name('student.dashboard');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

require __DIR__.'/auth.php';
