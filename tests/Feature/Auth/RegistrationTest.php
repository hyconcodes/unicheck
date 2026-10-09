<?php

use App\Models\Department;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    Role::findOrCreate('student', 'web');

    $department = Department::create([
        'name' => 'Computer Science',
        'code' => 'CSC',
        'is_active' => true,
    ]);

    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@gmail.com')
        ->set('matric_no', '123456')
        ->set('department_id', (string) $department->id)
        ->set('level', '200')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('student.dashboard', absolute: false));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'test@gmail.com',
        'matric_no' => '123456',
    ]);
});

test('school email registration continues to fill the matric number from the address', function () {
    Role::findOrCreate('student', 'web');

    $department = Department::create([
        'name' => 'Computer Science',
        'code' => 'CSC',
        'is_active' => true,
    ]);

    Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'testuser.654321@bouesti.edu.ng')
        ->set('department_id', (string) $department->id)
        ->set('level', '200')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'testuser.654321@bouesti.edu.ng',
        'matric_no' => '654321',
    ]);
});