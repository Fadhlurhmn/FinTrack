<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringBill extends Model
{
    protected $fillable = [
    'user_id', 'category_id', 'name', 'amount',
    'currency', 'due_day', 'is_active', 'last_notified_at'
];

protected $casts = [
    'is_active' => 'boolean',
    'last_notified_at' => 'datetime',
];

public function user()
{
    return $this->belongsTo(User::class);
}

public function category()
{
    return $this->belongsTo(Category::class);
}
}
