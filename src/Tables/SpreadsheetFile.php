<?php

namespace Mo7mud\FilamentConventions\Tables;

use Illuminate\Support\Str;
use Mo7mud\FilamentConventions\Enums\ExportFormat;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\ODS\Writer as OdsWriter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Our only door to OpenSpout: writes a header and rows, one at a time, to
 * a temporary file in the chosen format, and returns its path. The caller
 * sends the file and deletes it.
 */
final class SpreadsheetFile
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<list<string>>  $rows
     */
    public static function write(ExportFormat $format, array $headers, iterable $rows, bool $rightToLeft): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'table-export-'.Str::random(40).'.'.$format->value;

        $writer = match ($format) {
            ExportFormat::Xlsx => new XlsxWriter,
            ExportFormat::Csv => new CsvWriter,
            ExportFormat::Ods => new OdsWriter,
        };

        try {
            $writer->openToFile($path);

            if ($writer instanceof XlsxWriter && $rightToLeft) {
                $writer->getCurrentSheet()->setSheetView((new SheetView)->setRightToLeft(true));
            }

            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
        } finally {
            $writer->close();
        }

        return $path;
    }
}
