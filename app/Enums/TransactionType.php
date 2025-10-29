<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
    case transferIn = 'transfer_in';
    case transferOut = 'transfer_out';
}
