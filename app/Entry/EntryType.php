<?php

namespace App\Entry;

enum EntryType: string
{
    case EXPENSE    = 'expense';
    case INCOME     = 'income';
    case ADJUSTMENT = 'adjustment';
}
