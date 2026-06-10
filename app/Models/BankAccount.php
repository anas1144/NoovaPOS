<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform payout bank account (central), tagged by country.
 */
class BankAccount extends Model
{
    use HasFactory, CentralConnection;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'country',
        'bank_name',
        'account_title',
        'account_number',
        'iban',
        'swift',
        'currency',
        'instructions',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
