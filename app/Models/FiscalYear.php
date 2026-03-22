<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends Model
{
    protected $fillable = ['year'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
