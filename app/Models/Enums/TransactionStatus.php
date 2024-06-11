<?php

namespace App\Models\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';

    case Completed = 'completed';

    case Failed = 'failed';
}
