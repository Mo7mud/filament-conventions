<?php

namespace Mo7mud\FilamentConventions\Tables;

use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mo7mud\FilamentConventions\Enums\ExportFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Traversable;

/**
 * Exports what a table shows: its visible columns, with the current
 * search, filters and sort, as the cells read on screen, to a file or to
 * the browser's print window (which saves it as PDF). The rows come from
 * the table's own query (or records), or from the selected rows, so the
 * tenant scope and the screen's permission hold as they do for the table.
 */
final class TableExport
{
    /**
     * The export button: one entry per format and one to print, for
     * holders of the table's permission only (or those a check lets in).
     */
    public static function actions(string|\Closure $permission, string $fileName): ActionGroup
    {
        $allowed = self::allowed($permission);

        $actions = array_map(
            fn (ExportFormat $format): Action => Action::make('export'.ucfirst($format->value))
                ->label($format->label())
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible($allowed)
                ->action(function (HasTable $livewire) use ($allowed, $format, $fileName): StreamedResponse {
                    abort_unless($allowed(), 403);

                    return self::download($livewire, $format, $fileName);
                }),
            ExportFormat::cases(),
        );

        $actions[] = Action::make('exportPrint')
            ->label(__('filament-conventions::exports.print'))
            ->icon(Heroicon::OutlinedPrinter)
            ->visible($allowed)
            ->action(function (HasTable $livewire) use ($allowed): void {
                abort_unless($allowed(), 403);

                self::print($livewire);
            });

        return self::group(ActionGroup::make($actions), $allowed);
    }

    /**
     * The same entries for the selected rows only.
     */
    public static function bulkActions(string|\Closure $permission, string $fileName): ActionGroup
    {
        $allowed = self::allowed($permission);

        $actions = array_map(
            fn (ExportFormat $format): BulkAction => BulkAction::make('exportSelected'.ucfirst($format->value))
                ->label($format->label())
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible($allowed)
                ->action(function (HasTable $livewire, Collection $records) use ($allowed, $format, $fileName): StreamedResponse {
                    abort_unless($allowed(), 403);

                    return self::download($livewire, $format, $fileName, $records);
                }),
            ExportFormat::cases(),
        );

        $actions[] = BulkAction::make('exportSelectedPrint')
            ->label(__('filament-conventions::exports.print'))
            ->icon(Heroicon::OutlinedPrinter)
            ->visible($allowed)
            ->action(function (HasTable $livewire, Collection $records) use ($allowed): void {
                abort_unless($allowed(), 403);

                self::print($livewire, $records);
            });

        return self::group(BulkActionGroup::make($actions), $allowed)->label(__('filament-conventions::exports.export_selected'));
    }

    /**
     * @template TGroup of ActionGroup
     *
     * @param  TGroup  $group
     * @return TGroup
     */
    private static function group(ActionGroup $group, \Closure $allowed): ActionGroup
    {
        return $group
            ->label(__('filament-conventions::exports.export'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->button()
            ->visible($allowed);
    }

    private static function allowed(string|\Closure $permission): \Closure
    {
        if ($permission instanceof \Closure) {
            return fn (): bool => (bool) $permission();
        }

        return fn (): bool => Auth::user()?->can($permission) ?? false;
    }

    /**
     * Opens the print window on a printable copy of the table, in a hidden
     * frame of the same page: no new tab, nothing stored, no library.
     *
     * @param  iterable<Model|array<string, mixed>>|null  $records
     */
    public static function print(HasTable $livewire, ?iterable $records = null): void
    {
        $html = json_encode(self::printHtml($livewire, $records), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if ($livewire instanceof Component) {
            $livewire->js(<<<JS
                (() => {
                    const frame = document.createElement('iframe');
                    frame.setAttribute('aria-hidden', 'true');
                    frame.style.cssText = 'position:fixed;inset-inline-end:0;bottom:0;width:0;height:0;border:0';
                    let printed = false;
                    frame.onload = () => {
                        // The empty first page (about:blank) loads too: print the table's page only, once.
                        if (printed || frame.contentWindow.location.href !== 'about:srcdoc') {
                            return;
                        }
                        printed = true;
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                        setTimeout(() => frame.remove(), 60000);
                    };
                    frame.srcdoc = {$html};
                    document.body.appendChild(frame);
                })()
                JS);
        }
    }

    /**
     * The printable page: a title, the date, and the table as shown.
     *
     * @param  iterable<Model|array<string, mixed>>|null  $records
     */
    public static function printHtml(HasTable $livewire, ?iterable $records = null): string
    {
        [$headers, $rows] = self::rows($livewire, $records);

        return view('filament-conventions::print-table', [
            'title' => self::title($livewire),
            'headers' => $headers,
            'rows' => $rows,
            'locale' => app()->getLocale(),
            'direction' => self::isRightToLeft() ? 'rtl' : 'ltr',
            'printedAt' => now()->format('Y-m-d H:i'),
        ])->render();
    }

    private static function isRightToLeft(): bool
    {
        return in_array(strtok(app()->getLocale(), '_-'), ['ar', 'fa', 'he', 'ur', 'ku', 'ps', 'sd', 'yi'], true);
    }

    private static function title(HasTable $livewire): string
    {
        $heading = $livewire->getTable()->getHeading();

        if (filled($heading)) {
            return self::text($heading);
        }

        return method_exists($livewire, 'getTitle') ? self::text($livewire->getTitle()) : '';
    }

    /**
     * @param  iterable<Model|array<string, mixed>>|null  $records
     */
    public static function download(HasTable $livewire, ExportFormat $format, string $fileName, ?iterable $records = null): StreamedResponse
    {
        [$headers, $rows] = self::rows($livewire, $records);
        $path = SpreadsheetFile::write($format, $headers, $rows, self::isRightToLeft());
        $name = $fileName.'-'.now()->format('Y-m-d-His').'.'.$format->value;

        return response()->streamDownload(function () use ($path): void {
            readfile($path);
            @unlink($path);
        }, $name, ['Content-Type' => $format->mimeType()]);
    }

    /**
     * The header and the rows of a table as shown, rows read lazily: the
     * given (selected) records, or everything the table finds.
     *
     * @param  iterable<Model|array<string, mixed>>|null  $records
     * @return array{0: list<string>, 1: iterable<list<string>>}
     */
    public static function rows(HasTable $livewire, ?iterable $records = null): array
    {
        $columns = self::columns($livewire);
        $headers = array_map(fn (Column $column): string => self::text($column->getLabel()), $columns);

        return [$headers, self::cells($livewire, $columns, $records)];
    }

    /**
     * @return list<Column>
     */
    private static function columns(HasTable $livewire): array
    {
        return array_values(array_filter(
            $livewire->getTable()->getColumns(),
            fn (Column $column): bool => ! $column->isHidden() && ! $livewire->isTableColumnToggledHidden($column->getName()),
        ));
    }

    /**
     * @param  list<Column>  $columns
     * @param  iterable<Model|array<string, mixed>>|null  $records
     * @return \Generator<int, list<string>>
     */
    private static function cells(HasTable $livewire, array $columns, ?iterable $records): \Generator
    {
        if ($records === null) {
            $query = $livewire->getFilteredSortedTableQuery();
            $records = $query !== null ? $query->lazy(500) : $livewire->getTableRecords();
        }

        foreach (is_array($records) || $records instanceof Traversable ? $records : [] as $record) {
            yield self::row($record, $columns);
        }
    }

    /**
     * @param  Model|array<string, mixed>  $record
     * @param  list<Column>  $columns
     * @return list<string>
     */
    private static function row(Model|array $record, array $columns): array
    {
        $cells = [];

        foreach ($columns as $column) {
            $column->record($record);
            $state = $column->getState();
            $cells[] = self::text($column instanceof TextColumn ? $column->formatState($state) : $state);
            $column->clearCachedState();
        }

        return $cells;
    }

    private static function text(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof Htmlable => trim(html_entity_decode(strip_tags($value->toHtml()), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            $value instanceof HasLabel => self::text($value->getLabel()),
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof CarbonInterface => $value->toDateTimeString(),
            is_bool($value) => $value ? __('filament-conventions::exports.yes') : __('filament-conventions::exports.no'),
            is_array($value) => implode('، ', array_map(fn (mixed $item): string => self::text($item), $value)),
            is_scalar($value) => trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            $value instanceof \Stringable => (string) $value,
            default => '',
        };
    }
}
