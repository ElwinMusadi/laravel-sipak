<?php

namespace App;

enum BapCancellationReason: string
{
    case Cancelled = 'cancelled';
    case Damaged = 'damaged';
    case NetworkError = 'network_error';
    case PrinterError = 'printer_error';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Cancelled => 'Batal',
            self::Damaged => 'Rusak',
            self::NetworkError => 'Jaringan Error',
            self::PrinterError => 'Printer Error',
            self::Custom => 'Isi Sendiri',
        };
    }

    public function requiresDescription(): bool
    {
        return $this === self::Custom;
    }

    /**
     * Returns the reasons available for new cancellation entries in the unified BAP form.
     *
     * @return list<self>
     */
    public static function forNewEntry(): array
    {
        return [self::NetworkError, self::PrinterError, self::Custom];
    }
}
