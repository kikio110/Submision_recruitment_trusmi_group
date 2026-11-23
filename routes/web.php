<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'overview'])->name('overview');
Route::get('/kpi', [PageController::class, 'kpi'])->name('kpi');
Route::get('/tasks', [PageController::class, 'tasks'])->name('tasks');
Route::get('/employees', [PageController::class, 'employees'])->name('employees');
Route::get('/settings', [PageController::class, 'settings'])->name('settings');

// JSON endpoint (read-only). Ditaruh di web.php agar tidak perlu `php artisan install:api`.
Route::prefix('api')->group(function () {
    Route::get('/kpi', [ApiController::class, 'kpi'])->name('api.kpi');
    Route::get('/tasks', [ApiController::class, 'tasks'])->name('api.tasks');
});
