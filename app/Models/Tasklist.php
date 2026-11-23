<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kolom deadline & actual_date sengaja TIDAK di-cast ke Carbon:
 * dibaca sebagai string 'YYYY-MM-DD' agar bebas masalah timezone.
 */
class Tasklist extends Model
{
    protected $guarded = [];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }
}
