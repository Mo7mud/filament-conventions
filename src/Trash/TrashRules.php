<?php

namespace Mo7mud\FilamentConventions\Trash;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * What the trash may do to each kind of record, set by the code that owns
 * its meaning (usually a service provider's boot method):
 *
 *  - blocker: why it cannot go to the trash now (an active subscription,
 *    upcoming lessons, a payment in progress), or null.
 *  - kept: why it stays in the trash for good: true for money history, or
 *    its own reason; false or null when it may be deleted for good.
 *  - trashing: done in the same transaction before it goes to the trash.
 *  - purging: deletes it for good in place of a plain forceDelete.
 *
 * A record with no rules goes to the trash, and is deleted for good unless
 * the database's own links refuse it.
 */
final class TrashRules
{
    /** @var array<class-string<Model>, array{blocker: Closure|null, kept: Closure|null, trashing: Closure|null, purging: Closure|null}> */
    private static array $rules = [];

    /**
     * @param  class-string<Model>  $class
     * @param  (Closure(Model): ?string)|null  $blocker
     * @param  (Closure(Model): (bool|string|null))|null  $kept
     * @param  (Closure(Model): void)|null  $trashing
     * @param  (Closure(Model): void)|null  $purging
     */
    public static function define(string $class, ?Closure $blocker = null, ?Closure $kept = null, ?Closure $trashing = null, ?Closure $purging = null): void
    {
        self::$rules[$class] = ['blocker' => $blocker, 'kept' => $kept, 'trashing' => $trashing, 'purging' => $purging];
    }

    public static function blocker(Model $record): ?string
    {
        $rule = self::rule($record)['blocker'] ?? null;
        $reason = $rule instanceof Closure ? $rule($record) : null;

        return is_string($reason) && $reason !== '' ? $reason : null;
    }

    /**
     * Why the record is never deleted for good, or null when it may be.
     */
    public static function kept(Model $record): ?string
    {
        $rule = self::rule($record)['kept'] ?? null;
        $kept = $rule instanceof Closure ? $rule($record) : null;

        if ($kept === true) {
            return self::reason('filament-conventions::trash.kept_money');
        }

        return is_string($kept) && $kept !== '' ? $kept : null;
    }

    public static function trashing(Model $record): void
    {
        $rule = self::rule($record)['trashing'] ?? null;

        if ($rule instanceof Closure) {
            $rule($record);
        }
    }

    public static function purge(Model $record): void
    {
        $rule = self::rule($record)['purging'] ?? null;

        if ($rule instanceof Closure) {
            $rule($record);

            return;
        }

        $record->forceDelete();
    }

    /**
     * A translated reason for a rule, always a string.
     */
    public static function reason(string $key): string
    {
        $text = __($key);

        return is_string($text) ? $text : $key;
    }

    /**
     * How a record is named in the trash's messages.
     */
    public static function name(Model $record): string
    {
        foreach (['name', 'title', 'code', 'email'] as $key) {
            if (! array_key_exists($key, $record->getAttributes())) {
                continue;
            }

            $value = $record->getAttribute($key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '#'.$record->getKey();
    }

    /**
     * @return array{blocker: Closure|null, kept: Closure|null, trashing: Closure|null, purging: Closure|null}|null
     */
    private static function rule(Model $record): ?array
    {
        foreach (self::$rules as $class => $rule) {
            if ($record instanceof $class) {
                return $rule;
            }
        }

        return null;
    }
}
