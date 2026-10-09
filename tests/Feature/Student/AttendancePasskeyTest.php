<?php

use App\Models\ClassModel;
use App\Models\Department;
use App\Models\User;
use App\Models\WebauthnCredential;
use Livewire\Volt\Volt as LivewireVolt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('students with an existing passkey can register another passkey from attendance', function () {
    $department = Department::create([
        'name' => 'Test Department',
        'code' => 'TEST',
    ]);

    $student = User::factory()->create([
        'department_id' => $department->id,
        'level' => '100',
    ]);

    $lecturer = User::factory()->create();

    $class = ClassModel::create([
        'title' => 'Test Class',
        'lecturer_id' => $lecturer->id,
        'department_id' => $department->id,
        'level' => '100',
        'latitude' => 0,
        'longitude' => 0,
        'radius' => 30,
        'status' => 'active',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'attendance_open' => true,
    ]);

    WebauthnCredential::create([
        'user_id' => $student->id,
        'credential_id' => base64_encode('existing-device-credential'),
        'public_key' => 'existing-device-public-key',
        'signature_count' => 0,
    ]);

    $this->actingAs($student);

    LivewireVolt::test('student.mark-attendance', ['classId' => $class->id])
        ->assertSee('Scan Passkey')
        ->assertSee('Register passkey on this device');
});
