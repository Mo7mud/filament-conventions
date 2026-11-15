<?php

namespace Mo7mud\FilamentConventions\Tables;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Mo7mud\FilamentConventions\Trash\TrashRecordsAction;

/**
 * The trash of a table, the same in every panel: delete a row or the selected rows to the trash, a trash button
 * above the table that shows the deleted rows only, and there restore them
 * or delete them for good. The page uses ShowsTrash, the table's query
 * passes through scope(), and the model is Trashable.
 *
 * Each permission is a permission name or a closure; deleting and
 * restoring take one, deleting for good another.
 */
final class TableTrash
{
    /**
     * The table's rows: the deleted ones only while the trash is shown.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function scope(Builder $query, mixed $livewire): Builder
    {
        return self::showing($livewire) ? $query->onlyTrashed() : $query; // @phpstan-ignore method.notFound
    }

    /**
     * The trash button above the table.
     */
    public static function toggle(string|Closure $delete): Action
    {
        $allowed = self::allowed($delete);

        return Action::make('trash')
            ->label(fn (mixed $livewire): string => __(self::showing($livewire) ? 'filament-conventions::trash.back' : 'filament-conventions::trash.show'))
            ->icon(fn (mixed $livewire): Heroicon => self::showing($livewire) ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedTrash)
            ->color(fn (mixed $livewire): string => self::showing($livewire) ? 'danger' : 'gray')
            ->visible(fn (mixed $livewire): bool => $allowed() && is_object($livewire) && method_exists($livewire, 'toggleTrash'))
            ->action(function (mixed $livewire) use ($allowed): void {
                abort_unless($allowed(), 403);

                if (is_object($livewire) && method_exists($livewire, 'toggleTrash')) {
                    $livewire->toggleTrash();
                }
            });
    }

    /**
     * Delete a row to the trash; in the trash, restore it or delete it for good.
     *
     * @return list<Action>
     */
    public static function recordActions(string|Closure $delete, string|Closure $purge): array
    {
        $canDelete = self::allowed($delete);
        $canPurge = self::allowed($purge);

        return [
            Action::make('trashRecord')
                ->label(__('filament-conventions::trash.delete'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('filament-conventions::trash.delete'))
                ->modalDescription(__('filament-conventions::trash.delete_help'))
                ->visible(fn (mixed $livewire): bool => $canDelete() && ! self::showing($livewire))
                ->action(fn (Model $record, TrashRecordsAction $handler) => self::report(
                    $canDelete,
                    fn (): array => $handler->trash([$record]),
                    'filament-conventions::trash.deleted_count',
                )),
            Action::make('restoreRecord')
                ->label(__('filament-conventions::trash.restore'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (mixed $livewire): bool => $canDelete() && self::showing($livewire))
                ->action(fn (Model $record, TrashRecordsAction $handler) => self::report(
                    $canDelete,
                    fn (): array => $handler->restore([$record]),
                    'filament-conventions::trash.restored_count',
                )),
            Action::make('purgeRecord')
                ->label(__('filament-conventions::trash.purge'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('filament-conventions::trash.purge'))
                ->modalDescription(__('filament-conventions::trash.purge_help'))
                ->visible(fn (mixed $livewire, Model $record): bool => $canPurge() && self::showing($livewire) && TrashRecordsAction::purgeable($record))
                ->action(fn (Model $record, TrashRecordsAction $handler) => self::report(
                    $canPurge,
                    fn (): array => $handler->purge([$record]),
                    'filament-conventions::trash.purged_count',
                )),
        ];
    }

    /**
     * The same for the selected rows.
     *
     * @return list<BulkAction>
     */
    public static function bulkActions(string|Closure $delete, string|Closure $purge): array
    {
        $canDelete = self::allowed($delete);
        $canPurge = self::allowed($purge);

        return [
            BulkAction::make('trashSelected')
                ->label(__('filament-conventions::trash.delete_selected'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription(__('filament-conventions::trash.delete_help'))
                ->visible(fn (mixed $livewire): bool => $canDelete() && ! self::showing($livewire))
                ->action(fn (Collection $records, TrashRecordsAction $handler) => self::report(
                    $canDelete,
                    fn (): array => $handler->trash($records),
                    'filament-conventions::trash.deleted_count',
                ))
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('restoreSelected')
                ->label(__('filament-conventions::trash.restore_selected'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (mixed $livewire): bool => $canDelete() && self::showing($livewire))
                ->action(fn (Collection $records, TrashRecordsAction $handler) => self::report(
                    $canDelete,
                    fn (): array => $handler->restore($records),
                    'filament-conventions::trash.restored_count',
                ))
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('purgeSelected')
                ->label(__('filament-conventions::trash.purge_selected'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription(__('filament-conventions::trash.purge_help'))
                ->visible(fn (mixed $livewire): bool => $canPurge() && self::showing($livewire))
                ->action(fn (Collection $records, TrashRecordsAction $handler) => self::report(
                    $canPurge,
                    fn (): array => $handler->purge($records),
                    'filament-conventions::trash.purged_count',
                ))
                ->deselectRecordsAfterCompletion(),
        ];
    }

    public static function showing(mixed $livewire): bool
    {
        return is_object($livewire) && property_exists($livewire, 'trashOnly') && $livewire->trashOnly === true;
    }

    /**
     * @param  Closure(): array{done: int, kept: list<string>}  $run
     */
    private static function report(Closure $allowed, Closure $run, string $done): void
    {
        abort_unless($allowed(), 403);

        $result = $run();
        $notification = Notification::make()->title(__($done, ['count' => $result['done']]));

        if ($result['kept'] !== []) {
            $notification->warning()->body(__('filament-conventions::trash.kept_list', ['list' => implode(' | ', $result['kept'])]))->persistent();
        } else {
            $notification->success();
        }

        $notification->send();
    }

    /**
     * @return Closure(): bool
     */
    private static function allowed(string|Closure $permission): Closure
    {
        if ($permission instanceof Closure) {
            return fn (): bool => (bool) $permission();
        }

        return fn (): bool => Auth::user()?->can($permission) ?? false;
    }
}
