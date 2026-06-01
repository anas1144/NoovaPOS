<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'business_name',
        'business_type',
        'message',
        'status',
        'ip_address',
        'country',
        'contacted_at',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    // Scopes
    public function scopeNew($query)     { return $query->where('status', 'new'); }
    public function scopeNew_count($query) { return $query->where('status', 'new')->count(); }
}
