<?php

namespace App\Enums;

enum Currency: string
{
    case EUR = 'EUR';
    case USD = 'USD';

    public function symbol(): string
    {
        return match ($this) {
            self::EUR => '€',
            self::USD => '$',
        };
    }
}
