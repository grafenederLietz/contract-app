<?php

declare(strict_types=1);

function is_valid_contract_date(string $date): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed instanceof DateTime && $parsed->format('Y-m-d') === $date;
}

function calculate_contract_end_date(string $contractStart, int $durationMonths): string
{
    $date = DateTime::createFromFormat('!Y-m-d', $contractStart);
    if (!$date instanceof DateTime) {
        throw new InvalidArgumentException('Invalid contract start date');
    }

    $date->modify('+' . $durationMonths . ' months');
    return $date->format('Y-m-d');
}
