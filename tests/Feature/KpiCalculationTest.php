<?php

namespace Tests\Feature;

use App\Services\KpiCalculator;
use Database\Seeders\KpiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Validasi hasil PHP == hasil query SQL == nilai yang diharapkan pada soal.
 * Jalankan: php artisan test
 */
class KpiCalculationTest extends TestCase
{
    use RefreshDatabase;

    private KpiCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(KpiSeeder::class);
        $this->calc = new KpiCalculator();
    }

    public function test_dataset_has_13_tasklists(): void
    {
        $this->assertSame(13, DB::table('table_kpi_marketing')->count());
        $this->assertSame(7, DB::table('table_kpi_marketing')->where('kpi', 'Sales')->count());
        $this->assertSame(6, DB::table('table_kpi_marketing')->where('kpi', 'Report')->count());
    }

    public function test_kpi_per_employee_matches_expected(): void
    {
        $rows = collect($this->calc->calculateAllKPI($this->calc->getEmployeeData()))->keyBy('employee');
        $expected = [
            'Adi'  => [0.43, 0.50, 0.93],
            'Budi' => [0.50, 0.45, 0.95],
            'Doni' => [0.18, 0.25, 0.43],
            'Rara' => [0.43, 0.20, 0.63],
        ];
        foreach ($expected as $name => [$sales, $report, $total]) {
            $this->assertEqualsWithDelta($sales, $rows[$name]['sales_final'], 1e-9, "$name sales");
            $this->assertEqualsWithDelta($report, $rows[$name]['report_final'], 1e-9, "$name report");
            $this->assertEqualsWithDelta($total, $rows[$name]['total_kpi'], 1e-9, "$name total");
        }
    }

    public function test_late_counts(): void
    {
        $s = $this->calc->calculateOverallSummary($this->calc->calculateAllKPI($this->calc->getEmployeeData()));
        $this->assertSame(4, $s['total_employees']);
        $this->assertSame(13, $s['total_tasklists']);
        $this->assertSame(3, $s['sales_late']);
        $this->assertSame(2, $s['report_late']);
        $this->assertEqualsWithDelta(0.735, $s['avg_total_kpi'], 1e-9);
        $this->assertEqualsWithDelta(0.875, $s['avg_sales_achievement'], 1e-9);
        $this->assertEqualsWithDelta(0.75, $s['avg_report_achievement'], 1e-9);
    }

    public function test_timeliness_overall_and_per_employee(): void
    {
        $tasks = $this->calc->getEmployeeData();
        $o = $this->calc->calculateOverallTimeliness($tasks);
        $this->assertSame([13, 8, 5], [$o['total_tasklist'], $o['total_ontime'], $o['total_late']]);
        $this->assertEqualsWithDelta(61.5385, $o['persentase_ontime'], 1e-3);
        $this->assertEqualsWithDelta(38.4615, $o['persentase_late'], 1e-3);

        $by = collect($this->calc->calculateEmployeeSummary($tasks))->keyBy('employee');
        $this->assertSame([4, 3, 1], [$by['Adi']['total_tasklist'], $by['Adi']['total_ontime'], $by['Adi']['total_late']]);
        $this->assertSame([2, 1, 1], [$by['Doni']['total_tasklist'], $by['Doni']['total_ontime'], $by['Doni']['total_late']]);
        $this->assertEqualsWithDelta(33.3333, $by['Rara']['persentase_ontime'], 1e-3);
        $this->assertSame('Good', $by['Budi']['performance_status']);
        $this->assertSame('Needs Improvement', $by['Rara']['performance_status']);
    }

    public function test_status_and_days_difference(): void
    {
        $this->assertSame('ONTIME', KpiCalculator::calculateTaskStatus('2022-01-10', '2022-01-10'));
        $this->assertSame('ONTIME', KpiCalculator::calculateTaskStatus('2022-01-10', '2022-01-09'));
        $this->assertSame('LATE', KpiCalculator::calculateTaskStatus('2022-01-10', '2022-01-12'));
        $this->assertSame('INVALID', KpiCalculator::calculateTaskStatus('2022-01-10', null));
        $this->assertSame('INVALID', KpiCalculator::calculateTaskStatus('2022-02-31', '2022-03-01'));
        $this->assertSame(-1, KpiCalculator::calculateDaysDifference('2022-01-10', '2022-01-09'));
        $this->assertSame(0, KpiCalculator::calculateDaysDifference('2022-01-10', '2022-01-10'));
        $this->assertSame(2, KpiCalculator::calculateDaysDifference('2022-01-10', '2022-01-12'));
        $this->assertNull(KpiCalculator::calculateDaysDifference('2022-01-10', null));
    }

    public function test_original_sql_queries_return_same_result_via_view(): void
    {
        $sql = file_get_contents(base_path('docs/sql/soal1_kpi_per_karyawan.sql'));
        $rows = collect(DB::select($sql))->keyBy('nama');
        $php = collect($this->calc->calculateAllKPI($this->calc->getEmployeeData()))->keyBy('employee');
        foreach ($php as $name => $r) {
            $this->assertEqualsWithDelta($r['total_kpi'], (float) $rows[$name]->total_kpi, 1e-4, $name);
        }
    }

    public function test_api_endpoints_and_filters(): void
    {
        $this->getJson('/api/kpi')->assertOk()->assertJsonPath('summary.total_employees', 4);
        $this->getJson('/api/kpi?employee=Budi')->assertOk()
            ->assertJsonPath('summary.total_employees', 1)->assertJsonPath('rows.0.total_kpi', 0.95);
        $this->getJson('/api/tasks')->assertOk()->assertJsonPath('summary.total_late', 5)->assertJsonPath('meta.total', 13);
        $this->getJson('/api/tasks?status=LATE')->assertOk()->assertJsonPath('summary.total_tasklist', 5);
        $this->getJson('/api/tasks?employee=Rara&status=LATE')->assertOk()->assertJsonPath('summary.total_late', 2);
        $this->getJson('/api/tasks?search=tasklist 13')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/tasks?employee=HACKER')->assertOk()->assertJsonPath('filter.employee', 'All');
    }

    public function test_pages_render(): void
    {
        foreach (['/', '/kpi', '/tasks', '/employees', '/settings'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
