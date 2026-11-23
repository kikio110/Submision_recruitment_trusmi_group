<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $guarded = [];

    public function tasklists(): HasMany
    {
        return $this->hasMany(Tasklist::class);
    }
}
