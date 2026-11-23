@extends('layouts.app')
@section('title', 'Employee Performance')
@section('crumb', 'Employee Performance')
@section('heading', 'Employee Performance')

@section('content')
<section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-space-md">
    @foreach ($kpiRows as $r)
        @php $t = $time[$r['employee']]; @endphp
        <div class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg">
            <div class="flex items-center gap-space-sm">
                <span class="w-10 h-10 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold">{{ strtoupper(substr($r['employee'], 0, 2)) }}</span>
                <div>
                    <div class="font-headline-sm text-headline-sm font-bold">{{ $r['employee'] }}</div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $t['total_tasklist'] }} tasklists</div>
                </div>
            </div>
            <div class="mt-space-md text-headline-lg font-bold tabular-nums {{ $r['total_kpi'] >= 0.75 ? 'text-secondary' : ($r['total_kpi'] < 0.5 ? 'text-error' : '') }}">
                {{ number_format($r['total_kpi'] + 1e-9, 2) }}
                <span class="text-body-md font-medium text-on-surface-variant">Total KPI</span>
            </div>
            <dl class="mt-space-md space-y-1.5 font-body-sm text-body-sm tabular-nums">
                <div class="flex justify-between"><dt>Sales final</dt><dd>{{ number_format($r['sales_final'] + 1e-9, 2) }} <span class="text-error">({{ $r['sales_late'] }} late)</span></dd></div>
                <div class="flex justify-between"><dt>Report final</dt><dd>{{ number_format($r['report_final'] + 1e-9, 2) }} <span class="text-error">({{ $r['report_late'] }} late)</span></dd></div>
                <div class="flex justify-between border-t border-surface-container pt-1.5"><dt>Ontime rate</dt><dd class="font-semibold">{{ number_format($t['persentase_ontime'] + 1e-9, 2) }}%</dd></div>
                <div class="flex justify-between"><dt>Status</dt><dd class="font-semibold">{{ $t['performance_status'] }}</dd></div>
            </dl>
        </div>
    @endforeach
</section>
<p class="font-body-sm text-body-sm text-on-surface-variant">Detail lengkap tersedia di
    <a class="underline" href="{{ route('kpi') }}">KPI Performance</a> dan <a class="underline" href="{{ route('tasks') }}">Task Monitoring</a>.</p>
@endsection
