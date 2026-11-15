<?php

namespace Mo7mud\FilamentConventions\Trash;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Moves records to the trash, restores them, or deletes them for good
 * each record on its own: one that may not be touched
 * is named in "kept" with its reason, and the rest go on.
 *
 * Deleting for good is refused for a record with money history, and for
 * one the database's own links still hold (the whole delete is put back).
 */
final class TrashRecordsAction
{
    /**
     * @param  iterable<Model>  $records
     * @return array{done: int, kept: list<string>}
     */
    public function trash(iterable $records): array
    {
        return $this->each($records, function (Model $record): ?string {
            if (self::inTrash($record)) {
                return null;
            }

            $reason = TrashRules::blocker($record);

            if ($reason !== null) {
                return $reason;
            }

            DB::transaction(function () use ($record): void {
                TrashRules::trashing($record);
                $record->delete();
            });

            return '';
        });
    }

    /**
     * @param  iterable<Model>  $records
     * @return array{done: int, kept: list<string>}
     */
    public function restore(iterable $records): array
    {
        return $this->each($records, function (Model $record): ?string {
            if (! self::inTrash($record)) {
                return null;
            }

            if (method_exists($record, 'restore')) {
                $record->restore();
            }

            return '';
        });
    }

    /**
     * @param  iterable<Model>  $records
     * @return array{done: int, kept: list<string>}
     */
    public function purge(iterable $records): array
    {
        return $this->each($records, function (Model $record): ?string {
            if (! self::inTrash($record)) {
                return null;
            }

            $kept = TrashRules::kept($record);

            if ($kept !== null) {
                return $kept;
            }

            try {
                DB::transaction(fn () => TrashRules::purge($record));
            } catch (QueryException) {
                return TrashRules::reason('filament-conventions::trash.kept_linked');
            }

            return '';
        });
    }

    /**
     * Whether the record is deleted for good: it has money history, so it never is.
     */
    public static function purgeable(Model $record): bool
    {
        return self::inTrash($record) && TrashRules::kept($record) === null;
    }

    public static function inTrash(Model $record): bool
    {
        return method_exists($record, 'trashed') && $record->trashed();
    }

    /**
     * @param  iterable<Model>  $records
     * @param  \Closure(Model): ?string  $apply  '' when done, a reason when kept, null when skipped
     * @return array{done: int, kept: list<string>}
     */
    private function each(iterable $records, \Closure $apply): array
    {
        $done = 0;
        $kept = [];

        foreach ($records as $record) {
            $result = $apply($record);

            if ($result === '') {
                $done++;
            } elseif ($result !== null) {
                $kept[] = TrashRules::name($record).': '.$result;
            }
        }

        return ['done' => $done, 'kept' => $kept];
    }
}
