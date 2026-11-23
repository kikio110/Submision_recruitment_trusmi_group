<?php

namespace App\Http\Controllers;

use App\Services\KpiCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function __construct(private KpiCalculator $calc)
    {
    }

    /** Whitelist nama karyawan agar input filter tidak dipercaya mentah-mentah. */
    private function employeeParam(Request $request, array $names): string
    {
        $e = (string) $request->query('employee', 'All');

        return in_array($e, $names, true) ? $e : 'All';
    }

    /** GET /api/kpi?employee=Budi */
    public function kpi(Request $request): JsonResponse
    {
        $names = $this->calc->employeeNames();
        $employee = $this->employeeParam($request, $names);
        $selected = $employee === 'All' ? $names : [$employee];

        $tasks = $this->calc->getEmployeeData(['employee' => $employee]);
        $rows = $this->calc->calculateAllKPI($tasks, $selected);

        return response()->json(KpiCalculator::roundForDisplay([
            'filter'    => ['employee' => $employee],
            'employees' => $names,
            'rules'     => $this->calc->rules(),
            'summary'   => $this->calc->calculateOverallSummary($rows),
            'rows'      => $rows,
            'period'    => $this->period($tasks),
        ]));
    }

    /** GET /api/tasks?employee=&status=&search=&sort=asc&page=1&per_page=8 */
    public function tasks(Request $request): JsonResponse
    {
        $names = $this->calc->employeeNames();
        $employee = $this->employeeParam($request, $names);
        $status = strtoupper((string) $request->query('status', 'ALL'));
        $status = in_array($status, ['ALL', 'ONTIME', 'LATE', 'INVALID'], true) ? $status : 'ALL';
        $sort = $request->query('sort') === 'desc' ? 'desc' : 'asc';
        $perPage = max(5, min(50, (int) $request->query('per_page', 8)));
        $page = max(1, (int) $request->query('page', 1));
        $search = (string) $request->query('search', '');

        // SATU set data terfilter (employee + status) dipakai summary, chart & tabel ringkasan.
        $filtered = $this->calc->getEmployeeData(['employee' => $employee, 'status' => $status]);
        $overall = $this->calc->calculateOverallTimeliness($filtered);
        $selected = $employee === 'All' ? $names : [$employee];

        // Tabel detail = set yang sama + pencarian teks, lalu urut deadline.
        $detail = $this->calc->getEmployeeData(['employee' => $employee, 'status' => $status, 'search' => $search]);
        usort($detail, function ($a, $b) use ($sort) {
            $c = [$a['deadline'] ?? '', $a['id']] <=> [$b['deadline'] ?? '', $b['id']];

            return $sort === 'desc' ? -$c : $c;
        });
        $total = count($detail);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return response()->json(KpiCalculator::roundForDisplay([
            'filter'     => compact('employee', 'status', 'sort', 'search'),
            'employees'  => $names,
            'summary'    => $overall,
            'by_employee' => $this->calc->calculateEmployeeSummary($filtered, $selected),
            'validation' => $this->calc->validateTimeliness($filtered, $overall),
            'tasks'      => array_slice($detail, ($page - 1) * $perPage, $perPage),
            'meta'       => ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'last_page' => $lastPage],
            'period'     => $this->period($filtered),
        ]));
    }

    private function period(array $tasks): ?array
    {
        $dates = array_filter(array_column($tasks, 'deadline'));

        return $dates ? ['from' => min($dates), 'to' => max($dates)] : null;
    }
}
