<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['dining_table_id', 'user_id', 'customer_name', 'status', 'payment_status', 'invoice_number', 'total', 'paid_at'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function diningTable()
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getTableNumberAttribute(): ?int
    {
        return $this->diningTable?->table_number;
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
