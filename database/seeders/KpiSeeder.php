<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\KpiCategory;
use App\Models\Tasklist;
use Illuminate\Database\Seeder;

class KpiSeeder extends Seeder
{
    /** Dataset asli soal (Test_Programmer.xlsx) - format table_kpi_marketing. */
    public const DATA = [
        [1, 'Tasklist 1', 'Sales',  'Budi',  '2022-01-10', '2022-01-09'],
        [2, 'Tasklist 2', 'Sales',  'Budi',  '2022-01-10', '2022-01-08'],
        [3, 'Tasklist 3', 'Report', 'Budi',  '2022-01-10', '2022-01-07'],
        [4, 'Tasklist 4', 'Report', 'Budi',  '2022-01-10', '2022-01-12'],
        [5, 'Tasklist 5', 'Sales',  'Adi',   '2022-01-10', '2022-01-09'],
        [6, 'Tasklist 6', 'Sales',  'Adi',   '2022-01-10', '2022-01-12'],
        [7, 'Tasklist 7', 'Report', 'Adi',   '2022-01-10', '2022-01-07'],
        [8, 'Tasklist 8', 'Report', 'Adi',   '2022-01-10', '2022-01-07'],
        [9, 'Tasklist 9', 'Sales',  'Rara',  '2022-01-10', '2022-01-12'],
        [10, 'Tasklist 10', 'Sales',  'Rara',  '2022-01-10', '2022-01-09'],
        [11, 'Tasklist 11', 'Report', 'Rara',  '2022-01-10', '2022-01-12'],
        [12, 'Tasklist 12', 'Report', 'Doni',  '2022-01-10', '2022-01-09'],
        [13, 'Tasklist 13', 'Sales',  'Doni',  '2022-01-10', '2022-01-12'],
    ];

    public function run(): void
    {
        // Aturan KPI dari sheet "Bobot KPI Marketing"
        $categories = [
            'Sales'  => ['target' => 2, 'weight' => 0.50, 'late_penalty' => 0.07],
            'Report' => ['target' => 2, 'weight' => 0.50, 'late_penalty' => 0.05],
        ];
        $cat = [];
        foreach ($categories as $name => $rule) {
            $cat[$name] = KpiCategory::updateOrCreate(['name' => $name], $rule)->id;
        }

        $emp = [];
        foreach (['Budi', 'Adi', 'Rara', 'Doni'] as $name) {
            $emp[$name] = Employee::firstOrCreate(['name' => $name])->id;
        }

        foreach (self::DATA as [$id, $tasklist, $kpi, $karyawan, $deadline, $aktual]) {
            Tasklist::updateOrCreate(['id' => $id], [
                'name'            => $tasklist,
                'kpi_category_id' => $cat[$kpi],
                'employee_id'     => $emp[$karyawan],
                'deadline'        => $deadline,
                'actual_date'     => $aktual,
            ]);
        }
    }
}
