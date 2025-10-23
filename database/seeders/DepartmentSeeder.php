<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Computer Science',
                'code' => 'CSC',
                'description' => 'Department of Computer Science - Specializing in software development, algorithms, and computational theory.',
                'is_active' => true,
            ],
            [
                'name' => 'Mathematics',
                'code' => 'MTH',
                'description' => 'Department of Mathematics - Pure and applied mathematics, statistics, and mathematical modeling.',
                'is_active' => true,
            ],
            [
                'name' => 'Physics',
                'code' => 'PHY',
                'description' => 'Department of Physics - Theoretical and experimental physics, quantum mechanics, and astrophysics.',
                'is_active' => true,
            ],
            [
                'name' => 'Chemistry',
                'code' => 'CHM',
                'description' => 'Department of Chemistry - Organic, inorganic, physical, and analytical chemistry.',
                'is_active' => true,
            ],
            [
                'name' => 'Biology',
                'code' => 'BIO',
                'description' => 'Department of Biology - Molecular biology, genetics, ecology, and biotechnology.',
                'is_active' => true,
            ],
            [
                'name' => 'English Language',
                'code' => 'ENG',
                'description' => 'Department of English Language - Literature, linguistics, and creative writing.',
                'is_active' => true,
            ],
            [
                'name' => 'Economics',
                'code' => 'ECO',
                'description' => 'Department of Economics - Microeconomics, macroeconomics, and econometrics.',
                'is_active' => true,
            ],
            [
                'name' => 'Political Science',
                'code' => 'POL',
                'description' => 'Department of Political Science - Political theory, international relations, and public administration.',
                'is_active' => true,
            ],
            [
                'name' => 'History',
                'code' => 'HIS',
                'description' => 'Department of History - World history, African history, and historical research methods.',
                'is_active' => true,
            ],
            [
                'name' => 'Psychology',
                'code' => 'PSY',
                'description' => 'Department of Psychology - Clinical psychology, cognitive psychology, and behavioral studies.',
                'is_active' => true,
            ],
            [
                'name' => 'Sociology',
                'code' => 'SOC',
                'description' => 'Department of Sociology - Social theory, research methods, and community studies.',
                'is_active' => true,
            ],
            [
                'name' => 'Geography',
                'code' => 'GEO',
                'description' => 'Department of Geography - Physical geography, human geography, and GIS.',
                'is_active' => true,
            ],
            [
                'name' => 'Fine Arts',
                'code' => 'ART',
                'description' => 'Department of Fine Arts - Visual arts, sculpture, painting, and art history.',
                'is_active' => true,
            ],
            [
                'name' => 'Music',
                'code' => 'MUS',
                'description' => 'Department of Music - Music theory, composition, performance, and music education.',
                'is_active' => true,
            ],
            [
                'name' => 'Philosophy',
                'code' => 'PHI',
                'description' => 'Department of Philosophy - Logic, ethics, metaphysics, and critical thinking.',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}