<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComputerScienceStudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Computer Science department
        $cscDepartment = Department::where('code', 'CSC')->first();
        
        if (!$cscDepartment) {
            $this->command->error('Computer Science department not found. Please run DepartmentSeeder first.');
            return;
        }

        // 50 Computer Science students for 100 level
        $students = [
            ['name' => 'Adebayo Olumide', 'lastname' => 'adebayo', 'matric' => '20240001'],
            ['name' => 'Adesanya Kemi', 'lastname' => 'adesanya', 'matric' => '20240002'],
            ['name' => 'Adeyemi Tunde', 'lastname' => 'adeyemi', 'matric' => '20240003'],
            ['name' => 'Agbaje Folake', 'lastname' => 'agbaje', 'matric' => '20240004'],
            ['name' => 'Ajayi Seun', 'lastname' => 'ajayi', 'matric' => '20240005'],
            ['name' => 'Akande Bisi', 'lastname' => 'akande', 'matric' => '20240006'],
            ['name' => 'Alabi Yemi', 'lastname' => 'alabi', 'matric' => '20240007'],
            ['name' => 'Aluko Dayo', 'lastname' => 'aluko', 'matric' => '20240008'],
            ['name' => 'Aminu Fatima', 'lastname' => 'aminu', 'matric' => '20240009'],
            ['name' => 'Anikulapo Fela', 'lastname' => 'anikulapo', 'matric' => '20240010'],
            ['name' => 'Balogun Wale', 'lastname' => 'balogun', 'matric' => '20240011'],
            ['name' => 'Bamidele Toyin', 'lastname' => 'bamidele', 'matric' => '20240012'],
            ['name' => 'Bello Aisha', 'lastname' => 'bello', 'matric' => '20240013'],
            ['name' => 'Chukwu Emeka', 'lastname' => 'chukwu', 'matric' => '20240014'],
            ['name' => 'Danjuma Musa', 'lastname' => 'danjuma', 'matric' => '20240015'],
            ['name' => 'Egbuna Chidi', 'lastname' => 'egbuna', 'matric' => '20240016'],
            ['name' => 'Eze Ngozi', 'lastname' => 'eze', 'matric' => '20240017'],
            ['name' => 'Falade Gbenga', 'lastname' => 'falade', 'matric' => '20240018'],
            ['name' => 'Garba Hauwa', 'lastname' => 'garba', 'matric' => '20240019'],
            ['name' => 'Hassan Zainab', 'lastname' => 'hassan', 'matric' => '20240020'],
            ['name' => 'Ibrahim Aliyu', 'lastname' => 'ibrahim', 'matric' => '20240021'],
            ['name' => 'Idris Amina', 'lastname' => 'idris', 'matric' => '20240022'],
            ['name' => 'Jegede Ayo', 'lastname' => 'jegede', 'matric' => '20240023'],
            ['name' => 'Kalu Chioma', 'lastname' => 'kalu', 'matric' => '20240024'],
            ['name' => 'Lawal Rasheed', 'lastname' => 'lawal', 'matric' => '20240025'],
            ['name' => 'Musa Halima', 'lastname' => 'musa', 'matric' => '20240026'],
            ['name' => 'Nnamdi Kelechi', 'lastname' => 'nnamdi', 'matric' => '20240027'],
            ['name' => 'Nwosu Ifeanyi', 'lastname' => 'nwosu', 'matric' => '20240028'],
            ['name' => 'Okafor Chinedu', 'lastname' => 'okafor', 'matric' => '20240029'],
            ['name' => 'Okoro Blessing', 'lastname' => 'okoro', 'matric' => '20240030'],
            ['name' => 'Oladele Funmi', 'lastname' => 'oladele', 'matric' => '20240031'],
            ['name' => 'Olatunji Biodun', 'lastname' => 'olatunji', 'matric' => '20240032'],
            ['name' => 'Olusegun Tayo', 'lastname' => 'olusegun', 'matric' => '20240033'],
            ['name' => 'Onwuka Obinna', 'lastname' => 'onwuka', 'matric' => '20240034'],
            ['name' => 'Oyedepo David', 'lastname' => 'oyedepo', 'matric' => '20240035'],
            ['name' => 'Salami Kehinde', 'lastname' => 'salami', 'matric' => '20240036'],
            ['name' => 'Sanni Lukman', 'lastname' => 'sanni', 'matric' => '20240037'],
            ['name' => 'Sule Abdullahi', 'lastname' => 'sule', 'matric' => '20240038'],
            ['name' => 'Taiwo Kehinde', 'lastname' => 'taiwo', 'matric' => '20240039'],
            ['name' => 'Uche Chinonso', 'lastname' => 'uche', 'matric' => '20240040'],
            ['name' => 'Udoh Ime', 'lastname' => 'udoh', 'matric' => '20240041'],
            ['name' => 'Umar Sadiq', 'lastname' => 'umar', 'matric' => '20240042'],
            ['name' => 'Uzoma Chukwuma', 'lastname' => 'uzoma', 'matric' => '20240043'],
            ['name' => 'Williams Tope', 'lastname' => 'williams', 'matric' => '20240044'],
            ['name' => 'Yakubu Amina', 'lastname' => 'yakubu', 'matric' => '20240045'],
            ['name' => 'Yusuf Abubakar', 'lastname' => 'yusuf', 'matric' => '20240046'],
            ['name' => 'Adamu Hadiza', 'lastname' => 'adamu', 'matric' => '20240047'],
            ['name' => 'Babatunde Segun', 'lastname' => 'babatunde', 'matric' => '20240048'],
            ['name' => 'Chikezie Nneka', 'lastname' => 'chikezie', 'matric' => '20240049'],
            ['name' => 'Durojaiye Femi', 'lastname' => 'durojaiye', 'matric' => '20240050'],
        ];

        // Create 50 Computer Science students at 100 level
        foreach ($students as $student) {
            User::create([
                'name' => $student['name'],
                'email' => $student['lastname'] . '.' . $student['matric'] . '@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'matric_no' => $student['matric'],
                'department_id' => $cscDepartment->id,
                'level' => '100',
                'avatar' => User::generateRandomAvatar(),
            ])->assignRole('student');
        }

        $this->command->info('Created 50 Computer Science students at 100 level.');
    }
}