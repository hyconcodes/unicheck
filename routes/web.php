<?php

// use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
// use Illuminate\Support\Facades\Auth;
// use Barryvdh\DomPDF\Facade\Pdf;



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
        Volt::route('superadmin/dashboard', 'dashboard.superadmin')
            ->middleware('permission:can.access.superadmin')
            ->name('superadmin.dashboard');
        Volt::route('superadmin/roles-permissions', 'admin.role-permission-manager')
            ->middleware('permission:can.manage.permissions')
            ->name('admin.role-permission-manager');
        Volt::route('superadmin/account-manager', 'admin.account-manager')
            ->middleware('permission:can.manage.students|can.manage.lecturers')
            ->name('superadmin.account-manager');
        Volt::route('superadmin/class-manager', 'admin.class-manager')
            ->middleware('permission:can.manage.locations|can.manage.classes')
            ->name('superadmin.class-manager');
        Volt::route('superadmin/department-manager', 'admin.department-manager')
            ->middleware('permission:can.manage.departments')
            ->name('superadmin.department-manager');
        Volt::route('superadmin/level-promotion-manager', 'admin.level-promotion-manager')
            ->middleware('permission:can.promote.students')
            ->name('superadmin.level-promotion-manager');
        // For superadmin to view profile
        Volt::route('student/profile/{user}', 'profiles.student')
            ->middleware('permission:can.view.other.profiles')
            ->name('student.profile');
        Volt::route('lecturer/profile/{user}', 'profiles.lecturer')
            ->middleware('permission:can.view.other.profiles')
            ->name('lecturer.profile');
    });

    Route::middleware(['role:lecturer'])->group(function () {
        Volt::route('lecturer/dashboard', 'dashboard.lecturer')
            ->middleware('permission:can.access.lecturer.dashboard')
            ->name('lecturer.dashboard');
        Volt::route('lecturer/classes', 'lecturer.class-manager')
            ->middleware('permission:can.view.classes|can.manage.classes')
            ->name('lecturer.classes');
        Volt::route('lecturer/classes/create', 'lecturer.class-manager')
            ->middleware('permission:can.create.classes')
            ->name('lecturer.classes.create');
        Volt::route('lecturer/students', 'lecturer.student-manager')
            ->middleware('permission:can.view.student.list')
            ->name('lecturer.students');
        Volt::route('lecturer/classes/{class}/manual-attendance', 'lecturer.manual-attendance')
            ->middleware('permission:can.manage.class.attendance')
            ->name('lecturer.manual-attendance');
    });

    Route::middleware(['role:student'])->group(function () {
        Volt::route('student/dashboard', 'dashboard.student')
            ->middleware('permission:can.access.student.dashboard')
            ->name('student.dashboard');
        Volt::route('student/classes', 'student.class-dashboard')
            ->middleware('permission:can.view.classes')
            ->name('student.classes');
        Volt::route('student/classes/{classId}/attendance', 'student.mark-attendance')
            ->middleware('permission:can.mark.attendance')
            ->name('student.mark-attendance');
        Volt::route('student/complaints', 'student.complaints')
            ->middleware('permission:can.create.complaints|can.view.complaints')
            ->name('student.complaints');
    });

    // Complaint management routes
    Route::middleware(['role:superadmin'])->group(function () {
        Volt::route('superadmin/complaints', 'admin.complaints')
            ->middleware('permission:can.manage.complaints')
            ->name('superadmin.complaints');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')
        ->middleware('permission:can.view.own.profile|can.edit.own.profile')
        ->name('profile.edit');
    Volt::route('settings/password', 'settings.password')
        ->middleware('permission:can.edit.own.profile')
        ->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')
        ->middleware('permission:can.edit.own.profile')
        ->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(array_merge([
            'permission:can.edit.own.profile'
        ], when(
            Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
            ['password.confirm'],
            []
        )))
        ->name('two-factor.show');
});

require __DIR__.'/auth.php';
// PDF