@extends('layouts.app')
@section('title', 'Task Monitoring')
@section('crumb', 'Task Monitoring')
@section('heading', 'Task Deadline Monitoring')

@section('filters')
    <label class="flex items-center gap-space-xs">
        <span class="material-symbols-outlined text-sm text-on-surface-variant">group</span>
        <select id="filter-employee" class="filter" aria-label="Filter karyawan">
            <option value="All">All Employees</option>
            @foreach ($employees as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
        </select>
    </label>
    <label class="flex items-center gap-space-xs">
        <span class="material-symbols-outlined text-sm text-on-surface-variant">filter_alt</span>
        <select id="filter-status" class="filter" aria-label="Filter status">
            <option value="ALL">All Status</option>
            <option value="ONTIME">ONTIME</option>
            <option value="LATE">LATE</option>
        </select>
    </label>
@endsection

@section('content')
<section class="w-full bg-surface-container-lowest rounded-xl shadow-sm p-space-md flex flex-col md:flex-row md:items-center justify-between gap-space-md">
    <div class="flex items-start md:items-center gap-space-sm">
        <div class="w-9 h-9 rounded-lg bg-surface-container-high flex items-center justify-center text-primary-container shrink-0"><span class="material-symbols-outlined text-lg">gavel</span></div>
        <p class="font-body-md text-body-md">
            <span class="font-semibold">Business rule:</span>
            Actual &le; Deadline &rarr; <span class="font-semibold text-secondary">ONTIME</span>;
            Actual &gt; Deadline &rarr; <span class="font-semibold text-error">LATE</span>.
            Satu tasklist = satu pekerjaan; tanggal tidak valid ditandai <span class="font-semibold">INVALID</span>, bukan ONTIME.
        </p>
    </div>
    <div id="validation-badge" class="shrink-0"></div>
</section>

<section id="summary-cards" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md"></section>

<section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
    <div class="lg:col-span-5 bg-surface-container-lowest rounded-xl shadow-sm p-space-lg">
        <div class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-secondary text-lg">donut_large</span>
            <h2 class="font-headline-sm text-headline-sm font-bold">Task Completion Distribution</h2></div>
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5 mb-space-md">ONTIME vs LATE</p>
        <div class="chart-box h-64"><canvas id="chart-donut" aria-label="Donut chart distribusi status"></canvas></div>
        <div id="donut-legend" class="mt-space-md space-y-1.5"></div>
    </div>
    <div class="lg:col-span-7 bg-surface-container-lowest rounded-xl shadow-sm p-space-lg">
        <div class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-secondary text-lg">bar_chart</span>
            <h2 class="font-headline-sm text-headline-sm font-bold">Employee Deadline Comparison</h2></div>
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5 mb-space-md">Jumlah tasklist ONTIME vs LATE per karyawan</p>
        <div class="chart-box h-80"><canvas id="chart-employee" aria-label="Grouped bar chart ontime dan late per karyawan"></canvas></div>
    </div>
</section>

<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="p-space-lg">
        <div class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-secondary text-xl">verified_user</span>
            <h2 class="font-headline-sm text-headline-sm font-bold">Employee Timeliness Scorecard</h2></div>
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Performance Status adalah klasifikasi visual tambahan: Excellent &ge; 90%, Good &ge; 75%, Needs Improvement &lt; 75%.</p>
    </div>
    <div class="w-full overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[760px]">
            <thead><tr class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                <th class="py-3 px-space-md">Employee</th><th class="py-3 px-space-sm text-right">Total Tasks</th>
                <th class="py-3 px-space-sm text-right">Ontime Tasks</th><th class="py-3 px-space-sm text-right">Late Tasks</th>
                <th class="py-3 px-space-sm">Ontime %</th><th class="py-3 px-space-sm text-right">Late %</th>
                <th class="py-3 px-space-md text-center">Performance Status</th></tr></thead>
            <tbody id="emp-tbody" class="divide-y divide-surface-container-low font-body-sm text-body-sm"></tbody>
            <tfoot id="emp-tfoot"></tfoot>
        </table>
    </div>
</section>

<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="p-space-lg flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
        <div>
            <div class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-secondary text-xl">list_alt</span>
                <h2 class="font-headline-sm text-headline-sm font-bold">Tasklist Detail &amp; Deadline Audit Log</h2></div>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Klik baris untuk melihat detail. Klik header Deadline untuk mengurutkan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-space-sm">
            <div id="status-tabs" class="flex bg-surface-container p-0.5 rounded-lg font-label-sm text-label-sm"></div>
            <label class="relative">
                <span class="material-symbols-outlined text-sm text-on-surface-variant absolute left-2 top-1/2 -translate-y-1/2">search</span>
                <input id="search" type="search" placeholder="Search tasklist / employee..." class="pl-8 pr-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface font-body-sm text-body-sm w-64 border-0">
            </label>
        </div>
    </div>
    <div class="w-full overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[860px]">
            <thead><tr class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                <th class="py-3 px-space-md">ID</th><th class="py-3 px-space-sm">Tasklist</th><th class="py-3 px-space-sm">Employee</th>
                <th class="py-3 px-space-sm">KPI Category</th>
                <th class="py-3 px-space-sm"><button id="sort-deadline" class="inline-flex items-center gap-1 uppercase font-semibold">Deadline <span id="sort-icon" class="material-symbols-outlined text-sm">arrow_upward</span></button></th>
                <th class="py-3 px-space-sm">Actual</th><th class="py-3 px-space-sm">Status</th><th class="py-3 px-space-md text-right">Days Difference</th></tr></thead>
            <tbody id="task-tbody" class="divide-y divide-surface-container-low font-body-sm text-body-sm"></tbody>
        </table>
    </div>
    <div class="p-space-md bg-surface-container-low flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm font-body-sm text-body-sm text-on-surface-variant">
        <span id="page-info"></span>
        <div id="pager" class="flex items-center gap-1"></div>
    </div>
</section>

{{-- Task detail modal --}}
<div id="task-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 p-space-md" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="bg-surface-container-lowest rounded-xl shadow-xl w-full max-w-md p-space-lg">
        <div class="flex items-start justify-between mb-space-md">
            <h3 id="modal-title" class="font-headline-md text-headline-md font-bold">Task Detail</h3>
            <button id="modal-close" class="p-1 rounded hover:bg-surface-container" aria-label="Tutup"><span class="material-symbols-outlined">close</span></button>
        </div>
        <dl id="modal-body" class="space-y-2 font-body-md text-body-md"></dl>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/tasks.js') }}"></script>
@endpush
