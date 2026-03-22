<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountCategory extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
