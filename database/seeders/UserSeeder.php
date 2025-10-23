<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all departments
        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            $this->command->error('No departments found. Please run DepartmentSeeder first.');
            return;
        }

        // Student names and corresponding matric numbers
        $students = [
            ['name' => 'John Adebayo', 'lastname' => 'adebayo', 'matric' => '20210001'],
            ['name' => 'Mary Okafor', 'lastname' => 'okafor', 'matric' => '20210002'],
            ['name' => 'David Ogundimu', 'lastname' => 'ogundimu', 'matric' => '20210003'],
            ['name' => 'Sarah Bello', 'lastname' => 'bello', 'matric' => '20210004'],
            ['name' => 'Michael Adesanya', 'lastname' => 'adesanya', 'matric' => '20210005'],
            ['name' => 'Grace Okoro', 'lastname' => 'okoro', 'matric' => '20210006'],
            ['name' => 'Emmanuel Yakubu', 'lastname' => 'yakubu', 'matric' => '20210007'],
            ['name' => 'Blessing Eze', 'lastname' => 'eze', 'matric' => '20210008'],
            ['name' => 'Joseph Aliyu', 'lastname' => 'aliyu', 'matric' => '20210009'],
            ['name' => 'Faith Ogbonna', 'lastname' => 'ogbonna', 'matric' => '20210010'],
            ['name' => 'Samuel Uche', 'lastname' => 'uche', 'matric' => '20220001'],
            ['name' => 'Mercy Danjuma', 'lastname' => 'danjuma', 'matric' => '20220002'],
            ['name' => 'Daniel Nwosu', 'lastname' => 'nwosu', 'matric' => '20220003'],
            ['name' => 'Joy Abdullahi', 'lastname' => 'abdullahi', 'matric' => '20220004'],
            ['name' => 'Peter Okwu', 'lastname' => 'okwu', 'matric' => '20220005'],
            ['name' => 'Esther Musa', 'lastname' => 'musa', 'matric' => '20220006'],
            ['name' => 'James Oladele', 'lastname' => 'oladele', 'matric' => '20220007'],
            ['name' => 'Ruth Aminu', 'lastname' => 'aminu', 'matric' => '20220008'],
            ['name' => 'Benjamin Chukwu', 'lastname' => 'chukwu', 'matric' => '20220009'],
            ['name' => 'Hannah Garba', 'lastname' => 'garba', 'matric' => '20220010'],
            ['name' => 'Victor Ojo', 'lastname' => 'ojo', 'matric' => '20230001'],
            ['name' => 'Patience Kalu', 'lastname' => 'kalu', 'matric' => '20230002'],
            ['name' => 'Anthony Sule', 'lastname' => 'sule', 'matric' => '20230003'],
            ['name' => 'Deborah Idris', 'lastname' => 'idris', 'matric' => '20230004'],
            ['name' => 'Francis Emeka', 'lastname' => 'emeka', 'matric' => '20230005'],
        ];

        // Lecturer names
        $lecturers = [
            'Dr. Adebola Adeyemi',
            'Prof. Chinedu Okechukwu',
            'Dr. Fatima Hassan',
            'Prof. Olumide Ajayi',
            'Dr. Khadijah Ibrahim',
            'Prof. Emeka Nnamdi',
            'Dr. Aisha Bello',
            'Prof. Tunde Ogunleye',
            'Dr. Zainab Yusuf',
            'Prof. Chioma Nneka',
            'Dr. Abdullahi Musa',
            'Prof. Folake Adebayo',
            'Dr. Usman Garba',
            'Prof. Ngozi Okafor',
            'Dr. Suleiman Ahmed',
        ];

        // Available levels for students
        $levels = ['100', '200', '300', '400', '500', '600'];

        // Create students with proper email format: lastname.matric_no@bouesti.edu.ng
        foreach ($students as $student) {
            $department = $departments->random();
            $level = $levels[array_rand($levels)];
            
            User::create([
                'name' => $student['name'],
                'email' => $student['lastname'] . '.' . $student['matric'] . '@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'matric_no' => $student['matric'],
                'department_id' => $department->id,
                'level' => $level,
                'avatar' => User::generateRandomAvatar(),
            ])->assignRole('student');
        }

        // Create lecturers with proper email format: name@bouesti.edu.ng
        foreach ($lecturers as $lecturer) {
            $department = $departments->random();
            
            // Convert name to email format (remove titles and spaces, lowercase)
            $emailName = strtolower(str_replace(['Dr. ', 'Prof. ', ' '], ['', '', '.'], $lecturer));
            
            User::create([
                'name' => $lecturer,
                'email' => $emailName . '@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'department_id' => $department->id,
                'avatar' => User::generateRandomAvatar(),
            ])->assignRole('lecturer');
        }

        $this->command->info('Created ' . count($students) . ' students and ' . count($lecturers) . ' lecturers with random department assignments.');
    }
}