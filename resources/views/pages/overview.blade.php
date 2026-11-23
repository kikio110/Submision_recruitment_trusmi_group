@extends('layouts.app')
@section('title', 'Overview')
@section('crumb', 'Overview')
@section('heading', 'Marketing Division Overview')

@section('content')
@php
    $stat = fn ($label, $value, $sub, $icon, $cls = 'text-on-surface') => compact('label', 'value', 'sub', 'icon', 'cls');
    $cards = [
        $stat('Total Employees', $kpi['total_employees'], $kpi['total_tasklists'].' tasklists', 'group'),
        $stat('Average KPI Score', number_format($kpi['avg_total_kpi'] + 1e-9, 2), 'Top: '.($kpi['top_performer'] ?? '-'), 'target', 'text-secondary'),
        $stat('Avg Sales Achievement', number_format($kpi['avg_sales_achievement'] * 100 + 1e-9, 1).'%', $kpi['sales_late'].' late', 'trending_up'),
        $stat('Avg Report Achievement', number_format($kpi['avg_report_achievement'] * 100 + 1e-9, 1).'%', $kpi['report_late'].' late', 'fact_check'),
        $stat('Total Tasklists', $time['total_tasklist'], 'Seluruh pekerjaan', 'assignment'),
        $stat('Total Ontime', $time['total_ontime'], number_format($time['persentase_ontime'] + 1e-9, 2).'%', 'check_circle', 'text-secondary'),
        $stat('Total Late', $time['total_late'], number_format($time['persentase_late'] + 1e-9, 2).'%', 'warning', 'text-error'),
        $stat('Overall Ontime Rate', number_format($time['persentase_ontime'] + 1e-9, 2).'%', $time['performance_status'], 'speed'),
    ];
@endphp
<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
    @foreach ($cards as $c)
        <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg">
            <div class="flex items-start justify-between">
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">{{ $c['label'] }}</span>
                <span class="material-symbols-outlined text-on-surface-variant">{{ $c['icon'] }}</span>
            </div>
            <div class="mt-1 text-headline-lg font-bold tabular-nums {{ $c['cls'] }}">{{ $c['value'] }}</div>
            <div class="mt-space-sm font-body-sm text-body-sm text-on-surface-variant">{{ $c['sub'] }}</div>
        </div>
    @endforeach
</section>
<section class="grid grid-cols-1 md:grid-cols-2 gap-space-lg">
    <a href="{{ route('kpi') }}" class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg hover:shadow-md transition-shadow">
        <span class="material-symbols-outlined text-secondary">analytics</span>
        <h2 class="font-headline-sm text-headline-sm font-bold mt-1">KPI Performance</h2>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Pencapaian Sales &amp; Report, penalti late, dan Total KPI per karyawan.</p>
    </a>
    <a href="{{ route('tasks') }}" class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg hover:shadow-md transition-shadow">
        <span class="material-symbols-outlined text-secondary">assignment_turned_in</span>
        <h2 class="font-headline-sm text-headline-sm font-bold mt-1">Task Monitoring</h2>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Ketepatan deadline ONTIME vs LATE dan audit log tasklist.</p>
    </a>
</section>
@endsection
