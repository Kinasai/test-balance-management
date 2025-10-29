<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'related_user_id',
        'type',
        'amount',
        'comment',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionType::class
    ];

}
