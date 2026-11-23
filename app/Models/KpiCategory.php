<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiCategory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'target'       => 'integer',
        'weight'       => 'float',
        'late_penalty' => 'float',
    ];

    public function tasklists(): HasMany
    {
        return $this->hasMany(Tasklist::class);
    }
}
