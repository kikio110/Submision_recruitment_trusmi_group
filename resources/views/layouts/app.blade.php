<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Marketing Dashboard') | Trusmi Group</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    {{-- Tailwind (CDN) memakai token desain Google Stitch "Executive Precision" --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/tailwind-config.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-surface font-body-md text-on-surface">
@php
    $nav = [
        ['overview',  'Overview',             'dashboard'],
        ['kpi',       'KPI Performance',      'analytics'],
        ['employees', 'Employee Performance', 'group'],
        ['tasks',     'Task Monitoring',      'assignment_turned_in'],
        ['settings',  'Settings',             'settings'],
    ];
@endphp

{{-- Backdrop untuk sidebar mobile --}}
<div id="sidebar-backdrop" class="fixed inset-0 bg-black/30 z-40 hidden lg:hidden"></div>

<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-surface-container-lowest shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-50 flex flex-col justify-between -translate-x-full lg:translate-x-0 transition-transform">
    <div class="flex flex-col">
        <div class="h-20 px-space-lg flex items-center gap-space-sm">
            {{-- Placeholder logo Trusmi Group --}}
            <div class="h-9 w-9 rounded-lg bg-primary-container text-on-primary flex items-center justify-center font-bold text-lg">T</div>
            <div class="flex flex-col">
                <span class="font-headline-sm text-headline-sm text-on-surface leading-tight tracking-tight">Trusmi Group</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Enterprise BI</span>
            </div>
        </div>
        <nav class="px-space-md py-space-sm space-y-space-xs">
            @foreach ($nav as [$route, $label, $icon])
                @php $active = request()->routeIs($route); @endphp
                <a href="{{ route($route) }}"
                   @if($active) aria-current="page" @endif
                   class="flex items-center gap-space-sm px-space-md py-space-sm rounded-lg font-label-lg text-label-lg transition-all
                          {{ $active ? 'bg-primary-container text-on-primary shadow-sm border-r-2 border-secondary' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' }}">
                    <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
    </div>
    <div class="p-space-md m-space-md rounded-lg bg-surface-container-low flex items-center justify-between">
        <div class="flex items-center gap-space-xs">
            <span class="h-2 w-2 rounded-full bg-secondary animate-pulse"></span>
            <span class="font-label-sm text-label-sm text-on-surface-variant font-medium">Laravel {{ app()->version() }}</span>
        </div>
        <span class="material-symbols-outlined text-sm text-secondary">cloud_done</span>
    </div>
</aside>

<div class="lg:pl-64">
    <header class="fixed top-0 left-0 lg:left-64 right-0 min-h-[5rem] bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-30 flex items-center justify-between gap-space-md px-margin lg:px-gutter-desktop py-2">
        <div class="flex items-center gap-space-sm min-w-0">
            <button id="sidebar-toggle" class="lg:hidden p-space-sm rounded-lg bg-surface-container-lowest shadow-sm" aria-label="Menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="flex flex-col justify-center min-w-0">
                <div class="flex items-center gap-space-xs text-on-surface-variant font-label-sm text-label-sm">
                    <a href="{{ route('overview') }}" class="hover:text-on-surface">Dashboard</a>
                    <span class="material-symbols-outlined text-xs">chevron_right</span>
                    <span class="text-on-surface font-semibold">@yield('crumb')</span>
                </div>
                <h1 class="font-headline-sm text-headline-sm text-on-surface font-bold tracking-tight truncate">@yield('heading')</h1>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-space-sm">
            @yield('filters')
            <div class="hidden sm:flex items-center gap-space-sm pl-space-sm">
                <div class="w-8 h-8 rounded-full bg-secondary text-on-secondary flex items-center justify-center font-label-sm text-label-sm font-bold">SJ</div>
                <div class="hidden md:flex flex-col text-left">
                    <span class="font-label-lg text-label-lg font-semibold text-on-surface leading-tight">Sarah Jenkins</span>
                    <span class="font-label-sm text-label-sm text-on-surface-variant leading-none">Marketing Director</span>
                </div>
            </div>
        </div>
    </header>

    <main class="w-full pt-28 sm:pt-24 bg-surface min-h-screen px-margin lg:px-gutter-desktop py-space-lg">
        <div class="flex flex-col w-full space-y-space-lg pb-space-xl">
            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/common.js') }}"></script>
@stack('scripts')
</body>
</html>
