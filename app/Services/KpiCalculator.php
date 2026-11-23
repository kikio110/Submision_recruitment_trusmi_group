<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\KpiCategory;
use App\Models\Tasklist;

/**
 * Seluruh business logic KPI & Task Monitoring berada di sini (single source of truth).
 * Semua halaman / endpoint memakai hasil class ini, sehingga angka tidak pernah berbeda.
 *
 * - Tidak ada pembulatan di setiap langkah; pembulatan hanya di roundForDisplay().
 * - Tanggal diproses sebagai 'YYYY-MM-DD' -> nomor hari UTC (bebas masalah timezone).
 * - Tasklist dengan tanggal tidak valid berstatus INVALID (tidak dianggap ONTIME / LATE).
 */
class KpiCalculator
{
    public const SALES = 'Sales';
    public const REPORT = 'Report';

    public const ONTIME = 'ONTIME';
    public const LATE = 'LATE';
    public const INVALID = 'INVALID';

    /** @var array<string, array{target: float, weight: float, late_penalty: float}> */
    private array $rules;

    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? $this->loadRules();
    }

    /* ------------------------------------------------------------------
     |  Aturan KPI (dari tabel kpi_categories)
     * ------------------------------------------------------------------ */

    private function loadRules(): array
    {
        return KpiCategory::query()->get()->mapWithKeys(fn ($c) => [
            $c->name => [
                'target'       => (float) $c->target,
                'weight'       => (float) $c->weight,
                'late_penalty' => (float) $c->late_penalty,
            ],
        ])->all();
    }

    public function rules(): array
    {
        return $this->rules;
    }

    private function rule(string $category): array
    {
        return $this->rules[$category] ?? ['target' => 0.0, 'weight' => 0.0, 'late_penalty' => 0.0];
    }

    /* ------------------------------------------------------------------
     |  Tanggal & status task
     * ------------------------------------------------------------------ */

    public static function isValidDate(?string $date): bool
    {
        if ($date === null || $date === '') {
            return false;
        }
        $d = substr($date, 0, 10);
        $dt = \DateTime::createFromFormat('!Y-m-d', $d, new \DateTimeZone('UTC'));

        return $dt !== false && $dt->format('Y-m-d') === $d;
    }

    /** Nomor hari sejak epoch (UTC). */
    private static function dayNumber(string $date): int
    {
        [$y, $m, $d] = array_map('intval', explode('-', substr($date, 0, 10)));

        return intdiv(gmmktime(0, 0, 0, $m, $d, $y), 86400);
    }

    /** ONTIME jika aktual <= deadline, LATE jika aktual > deadline, INVALID jika tanggal tidak valid. */
    public static function calculateTaskStatus(?string $deadline, ?string $actual): string
    {
        if (!self::isValidDate($deadline) || !self::isValidDate($actual)) {
            return self::INVALID;
        }

        return self::dayNumber($actual) <= self::dayNumber($deadline) ? self::ONTIME : self::LATE;
    }

    /** Actual - Deadline dalam hari. Negatif = lebih awal, 0 = tepat, positif = terlambat. */
    public static function calculateDaysDifference(?string $deadline, ?string $actual): ?int
    {
        if (!self::isValidDate($deadline) || !self::isValidDate($actual)) {
            return null;
        }

        return self::dayNumber($actual) - self::dayNumber($deadline);
    }

    /* ------------------------------------------------------------------
     |  Pengambilan data
     * ------------------------------------------------------------------ */

    /** @return string[] nama karyawan berurutan sesuai id. */
    public function employeeNames(): array
    {
        return Employee::query()->orderBy('id')->pluck('name')->all();
    }

    /**
     * getEmployeeData: tasklist (sudah dilengkapi status & selisih hari).
     *
     * @param  array{employee?: ?string, status?: ?string, search?: ?string}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getEmployeeData(array $filters = []): array
    {
        $employee = $filters['employee'] ?? null;
        $status = $filters['status'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        $query = Tasklist::query()->with(['employee', 'category'])->orderBy('id');
        if ($employee && $employee !== 'All') {
            $query->whereHas('employee', fn ($q) => $q->where('name', $employee));
        }

        $rows = [];
        foreach ($query->get() as $t) {
            $deadline = $t->deadline ? substr((string) $t->deadline, 0, 10) : null;
            $actual = $t->actual_date ? substr((string) $t->actual_date, 0, 10) : null;
            $rows[] = [
                'id'        => (int) $t->id,
                'tasklist'  => $t->name,
                'employee'  => $t->employee->name,
                'kpi'       => $t->category->name,
                'deadline'  => $deadline,
                'actual'    => $actual,
                'status'    => self::calculateTaskStatus($deadline, $actual),
                'days_diff' => self::calculateDaysDifference($deadline, $actual),
            ];
        }

        if ($status && strtoupper($status) !== 'ALL') {
            $rows = array_values(array_filter($rows, fn ($r) => $r['status'] === strtoupper($status)));
        }
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, fn ($r) => str_contains(
                mb_strtolower($r['tasklist'] . ' ' . $r['employee'] . ' ' . $r['kpi'] . ' ' . $r['id']),
                $needle
            )));
        }

        return $rows;
    }

    /* ------------------------------------------------------------------
     |  DASHBOARD 1 : KPI
     * ------------------------------------------------------------------ */

    /**
     * calculateEmployeeKPI: KPI satu karyawan dari tasklist miliknya.
     * Setiap tasklist late dikurangi tepat SATU kali (dihitung per tasklist).
     */
    public function calculateEmployeeKPI(string $employee, array $tasks): array
    {
        $mine = array_filter($tasks, fn ($t) => $t['employee'] === $employee);
        $out = ['employee' => $employee];
        $total = 0.0;

        foreach ([self::SALES => 'sales', self::REPORT => 'report'] as $category => $key) {
            $r = $this->rule($category);
            $ofCategory = array_filter($mine, fn ($t) => $t['kpi'] === $category);

            $actual = count($ofCategory);
            $late = count(array_filter($ofCategory, fn ($t) => $t['status'] === self::LATE));
            $achievement = $r['target'] > 0 ? $actual / $r['target'] : 0.0;
            $base = $achievement * $r['weight'];
            $deduction = $late * $r['late_penalty'];
            $final = $base - $deduction;

            $out += [
                "{$key}_target"      => $r['target'],
                "{$key}_actual"      => $actual,
                "{$key}_achievement" => $achievement,
                "{$key}_weight"      => $r['weight'],
                "{$key}_base"        => $base,
                "{$key}_late"        => $late,
                "{$key}_penalty"     => $r['late_penalty'],
                "{$key}_deduction"   => $deduction,
                "{$key}_final"       => $final,
            ];
            $total += $final;
        }
        $out['total_kpi'] = $total;

        return $out;
    }

    /** calculateAllKPI: rekap seluruh karyawan (urut sesuai $employees). */
    public function calculateAllKPI(array $tasks, ?array $employees = null): array
    {
        $employees ??= $this->employeeNames();

        return array_map(fn ($name) => $this->calculateEmployeeKPI($name, $tasks), $employees);
    }

    /** calculateOverallSummary (KPI). Aman untuk data kosong. */
    public function calculateOverallSummary(array $kpiRows): array
    {
        $n = count($kpiRows);
        $avg = fn (string $k) => $n > 0 ? array_sum(array_column($kpiRows, $k)) / $n : 0.0;
        $sum = fn (string $k) => array_sum(array_column($kpiRows, $k));

        $top = null;
        foreach ($kpiRows as $row) {
            if ($top === null || $row['total_kpi'] > $top['total_kpi']) {
                $top = $row;
            }
        }

        return [
            'total_employees'       => $n,
            'avg_total_kpi'         => $avg('total_kpi'),
            'avg_sales_achievement' => $avg('sales_achievement'),
            'avg_report_achievement' => $avg('report_achievement'),
            'avg_sales_final'       => $avg('sales_final'),
            'avg_report_final'      => $avg('report_final'),
            'avg_sales_base'        => $avg('sales_base'),
            'avg_report_base'       => $avg('report_base'),
            'total_tasklists'       => $sum('sales_actual') + $sum('report_actual'),
            'sales_tasklists'       => $sum('sales_actual'),
            'report_tasklists'      => $sum('report_actual'),
            'sales_late'            => $sum('sales_late'),
            'report_late'           => $sum('report_late'),
            'total_deduction'       => $sum('sales_deduction') + $sum('report_deduction'),
            'top_performer'         => $top['employee'] ?? null,
            'top_score'             => $top['total_kpi'] ?? null,
        ];
    }

    /* ------------------------------------------------------------------
     |  DASHBOARD 2 : Task / Deadline monitoring
     * ------------------------------------------------------------------ */

    /** Statistik ketepatan deadline dari satu set tasklist. Aman dari pembagian nol. */
    public function calculateOverallTimeliness(array $tasks): array
    {
        $total = count($tasks);
        $ontime = count(array_filter($tasks, fn ($t) => $t['status'] === self::ONTIME));
        $late = count(array_filter($tasks, fn ($t) => $t['status'] === self::LATE));
        $invalid = count(array_filter($tasks, fn ($t) => $t['status'] === self::INVALID));

        $ontimePct = $total > 0 ? 100.0 * $ontime / $total : 0.0;
        $latePct = $total > 0 ? 100.0 * $late / $total : 0.0;

        return [
            'total_tasklist'    => $total,
            'total_ontime'      => $ontime,
            'total_late'        => $late,
            'total_invalid'     => $invalid,
            'persentase_ontime' => $ontimePct,
            'persentase_late'   => $latePct,
            'performance_status' => self::performanceStatus($ontimePct, $total),
        ];
    }

    /** calculateEmployeeSummary: statistik per karyawan. */
    public function calculateEmployeeSummary(array $tasks, ?array $employees = null): array
    {
        $employees ??= $this->employeeNames();

        return array_map(function ($name) use ($tasks) {
            $mine = array_values(array_filter($tasks, fn ($t) => $t['employee'] === $name));

            return ['employee' => $name] + $this->calculateOverallTimeliness($mine);
        }, $employees);
    }

    /** Klasifikasi visual tambahan (bukan ketentuan asli soal). */
    public static function performanceStatus(float $ontimePct, int $total): string
    {
        if ($total === 0) {
            return 'No Data';
        }
        if ($ontimePct >= 90) {
            return 'Excellent';
        }

        return $ontimePct >= 75 ? 'Good' : 'Needs Improvement';
    }

    /** Pemeriksaan konsistensi data (DATA VALIDATION). */
    public function validateTimeliness(array $tasks, array $overall): array
    {
        $statusCounts = array_count_values(array_column($tasks, 'status'));
        $pctSum = $overall['persentase_ontime'] + $overall['persentase_late'];

        return [
            'counts_add_up'  => $overall['total_ontime'] + $overall['total_late'] + $overall['total_invalid'] === $overall['total_tasklist'],
            'percent_is_100' => $overall['total_invalid'] > 0 || $overall['total_tasklist'] === 0 || abs($pctSum - 100) < 1e-9,
            'single_status'  => array_sum($statusCounts) === count($tasks),
            'invalid_tasks'  => $overall['total_invalid'],
        ];
    }

    /* ------------------------------------------------------------------
     |  Util tampilan
     * ------------------------------------------------------------------ */

    /** Pembulatan hanya untuk output (rekursif). */
    public static function roundForDisplay(mixed $value, int $precision = 4): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => self::roundForDisplay($v, $precision), $value);
        }

        return is_float($value) ? round($value, $precision) : $value;
    }
}
