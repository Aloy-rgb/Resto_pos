<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiningTable extends Model
{
    protected $fillable = ['table_number', 'capacity', 'status'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
