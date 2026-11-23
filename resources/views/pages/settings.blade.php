@extends('layouts.app')
@section('title', 'Settings')
@section('crumb', 'Settings')
@section('heading', 'KPI Rules & Data Model')

@section('content')
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="p-space-lg">
        <h2 class="font-headline-sm text-headline-sm font-bold">Aturan KPI (tabel <code>kpi_categories</code>)</h2>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Read-only. Ubah nilai langsung di database / seeder; seluruh dashboard otomatis mengikuti.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left font-body-md text-body-md">
            <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                <tr><th class="py-3 px-space-lg">KPI</th><th class="py-3 px-space-md text-right">Target</th><th class="py-3 px-space-md text-right">Bobot</th><th class="py-3 px-space-lg text-right">Pengurangan jika Late</th></tr>
            </thead>
            <tbody class="divide-y divide-surface-container-low">
                @foreach ($rules as $name => $r)
                    <tr><td class="py-3 px-space-lg font-semibold">{{ $name }}</td>
                        <td class="py-3 px-space-md text-right tabular-nums">{{ number_format($r['target'], 0) }}</td>
                        <td class="py-3 px-space-md text-right tabular-nums">{{ number_format($r['weight'] * 100, 0) }}%</td>
                        <td class="py-3 px-space-lg text-right tabular-nums text-error">&minus;{{ number_format($r['late_penalty'], 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
<section class="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg font-body-md text-body-md space-y-2">
    <h2 class="font-headline-sm text-headline-sm font-bold">Model data (hasil normalisasi, soal #5)</h2>
    <p><code>employees</code> (id, name) &larr; <code>tasklists</code> (id, name, employee_id, kpi_category_id, deadline, actual_date) &rarr; <code>kpi_categories</code> (id, name, target, weight, late_penalty).</p>
    <p>View <code>table_kpi_marketing</code> mempertahankan struktur tabel asli soal sehingga query SQL soal #1 dan #2 dapat dijalankan tanpa perubahan (lihat <code>docs/sql</code>).</p>
</section>
@endsection
