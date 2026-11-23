<?php

namespace App\Http\Controllers;

use App\Services\KpiCalculator;

class PageController extends Controller
{
    public function __construct(private KpiCalculator $calc)
    {
    }

    public function overview()
    {
        $tasks = $this->calc->getEmployeeData();

        return view('pages.overview', [
            'kpi'  => $this->calc->calculateOverallSummary($this->calc->calculateAllKPI($tasks)),
            'time' => $this->calc->calculateOverallTimeliness($tasks),
        ]);
    }

    public function kpi()
    {
        return view('pages.kpi', ['rules' => $this->calc->rules(), 'employees' => $this->calc->employeeNames()]);
    }

    public function tasks()
    {
        return view('pages.tasks', ['rules' => $this->calc->rules(), 'employees' => $this->calc->employeeNames()]);
    }

    public function employees()
    {
        $tasks = $this->calc->getEmployeeData();
        $names = $this->calc->employeeNames();
        $time = collect($this->calc->calculateEmployeeSummary($tasks, $names))->keyBy('employee');

        return view('pages.employees', [
            'kpiRows' => $this->calc->calculateAllKPI($tasks, $names),
            'time'    => $time,
        ]);
    }

    public function settings()
    {
        return view('pages.settings', ['rules' => $this->calc->rules()]);
    }
}
