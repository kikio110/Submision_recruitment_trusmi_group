@extends('layouts.app')
@section('title', 'KPI Performance')
@section('crumb', 'KPI Performance')
@section('heading', 'Marketing KPI Performance')

@section('filters')
    <div class="hidden md:flex items-center gap-space-sm bg-surface-container-lowest px-space-md py-space-xs rounded-lg shadow-sm font-label-md text-label-md">
        <span class="material-symbols-outlined text-sm text-on-surface-variant">calendar_today</span>
        <span id="period-label">&mdash;</span>
    </div>
    <label class="flex items-center gap-space-xs font-label-md text-label-md">
        <span class="material-symbols-outlined text-sm text-on-surface-variant">group</span>
        <select id="filter-employee" class="filter" aria-label="Filter karyawan">
            <option value="All">All Employees</option>
            @foreach ($employees as $name)
                <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </select>
    </label>
@endsection

@section('content')
{{-- Policy banner: angka diambil dari tabel kpi_categories --}}
<section class="w-full bg-surface-container-lowest rounded-xl shadow-sm p-space-md flex flex-col md:flex-row md:items-center justify-between gap-space-md">
    <div class="flex items-start md:items-center gap-space-sm">
        <div class="w-9 h-9 rounded-lg bg-surface-container-high flex items-center justify-center text-primary-container shrink-0">
            <span class="material-symbols-outlined text-lg">info</span>
        </div>
        <div>
            <div class="flex items-center gap-space-xs font-label-sm text-label-sm uppercase tracking-wider text-secondary font-bold">Policy Governance</div>
            <p class="font-body-md text-body-md">
                <span class="font-semibold">Formula:</span>
                Total KPI = Sales Final (50% &times; achievement &minus; late) + Report Final (50% &times; achievement &minus; late).
                Penalti per tasklist late:
                <span class="font-semibold text-error">Sales &minus;{{ number_format($rules['Sales']['late_penalty'], 2) }}</span>,
                <span class="font-semibold text-error">Report &minus;{{ number_format($rules['Report']['late_penalty'], 2) }}</span>.
            </p>
        </div>
    </div>
    <span class="font-label-md text-label-md text-on-surface-variant bg-surface-container px-space-sm py-space-xs rounded-lg shrink-0">
        Target {{ $rules['Sales']['target'] }} / {{ $rules['Report']['target'] }} &middot; Weight 50 / 50
    </span>
</section>

{{-- Summary cards --}}
<section id="summary-cards" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md"></section>

{{-- Charts --}}
<section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
    <div class="lg:col-span-7 bg-surface-container-lowest rounded-xl shadow-sm p-space-lg">
        <div class="flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-secondary text-lg">bar_chart</span>
            <h2 class="font-headline-sm text-headline-sm font-bold">KPI Performance: Sales vs Report</h2>
        </div>
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5 mb-space-md">Achievement = Actual / Target per karyawan</p>
        <div class="chart-box h-72"><canvas id="chart-achievement" aria-label="Grouped bar chart achievement"></canvas></div>
        <div id="insight" class="mt-space-md p-space-sm bg-surface-container-low rounded-lg font-body-sm text-body-sm"></div>
    </div>
    <div class="lg:col-span-5 bg-surface-container-lowest rounded-xl shadow-sm p-space-lg">
        <div class="flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-secondary text-lg">leaderboard</span>
            <h2 class="font-headline-sm text-headline-sm font-bold">Employee KPI Comparison</h2>
        </div>
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5 mb-space-md">Total KPI Score (maks. 1.00)</p>
        <div class="chart-box h-72"><canvas id="chart-total" aria-label="Horizontal bar chart total KPI"></canvas></div>
        <div id="audit" class="mt-space-md p-space-sm bg-surface-container rounded-lg border-l-4 border-secondary font-body-sm text-body-sm"></div>
    </div>
</section>

{{-- Employee detail --}}
<section class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm mb-space-md">
        <div class="flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-secondary text-xl">person_search</span>
            <h2 class="font-headline-sm text-headline-sm font-bold">Employee Detail</h2>
        </div>
        <div id="detail-tabs" class="flex flex-wrap gap-1 bg-surface-container p-0.5 rounded-lg"></div>
    </div>
    <div id="employee-detail"></div>
</section>

{{-- KPI table --}}
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="p-space-lg">
        <div class="flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-secondary text-xl">table_chart</span>
            <h2 class="font-headline-sm text-headline-sm font-bold tracking-tight">Employee KPI Scorecard &amp; Deduction Breakdown</h2>
        </div>
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Klik nama karyawan untuk melihat detail. Pembulatan hanya pada tampilan.</p>
    </div>
    <div class="w-full overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1280px]">
            <thead>
                <tr class="bg-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                    <th class="py-2.5 px-space-md font-bold">Employee</th>
                    <th class="py-2.5 px-space-md text-center font-bold bg-surface-container-high/60" colspan="6">Sales KPI (50%)</th>
                    <th class="py-2.5 px-space-md text-center font-bold" colspan="6">Report KPI (50%)</th>
                    <th class="py-2.5 px-space-md text-center font-bold bg-surface-container-high/60">Final</th>
                </tr>
                <tr class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                    <th class="py-3 px-space-md">Employee Name</th>
                    <th class="py-3 px-space-sm text-right">Target</th><th class="py-3 px-space-sm text-right">Actual</th>
                    <th class="py-3 px-space-sm text-right">Achievement</th><th class="py-3 px-space-sm text-right">Base Weight</th>
                    <th class="py-3 px-space-sm text-right">Late</th><th class="py-3 px-space-sm text-right text-primary-container">Final Score</th>
                    <th class="py-3 px-space-sm text-right">Target</th><th class="py-3 px-space-sm text-right">Actual</th>
                    <th class="py-3 px-space-sm text-right">Achievement</th><th class="py-3 px-space-sm text-right">Base Weight</th>
                    <th class="py-3 px-space-sm text-right">Late</th><th class="py-3 px-space-sm text-right text-primary-container">Final Score</th>
                    <th class="py-3 px-space-md text-right text-primary-container">Total KPI</th>
                </tr>
            </thead>
            <tbody id="kpi-tbody" class="divide-y divide-surface-container-low font-body-sm text-body-sm"></tbody>
            <tfoot id="kpi-tfoot"></tfoot>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/kpi.js') }}"></script>
@endpush
