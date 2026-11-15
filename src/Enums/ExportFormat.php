<?php

namespace Mo7mud\FilamentConventions\Enums;

/**
 * The file formats a table can be exported in, all written by OpenSpout
 * (already installed with Filament, MIT).
 */
enum ExportFormat: string
{
    case Xlsx = 'xlsx';
    case Csv = 'csv';
    case Ods = 'ods';

    public function label(): string
    {
        return __('filament-conventions::exports.formats.'.$this->value);
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv => 'text/csv; charset=UTF-8',
            self::Ods => 'application/vnd.oasis.opendocument.spreadsheet',
        };
    }
}
