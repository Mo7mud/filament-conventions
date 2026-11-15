<?php

namespace Mo7mud\FilamentConventions\Trash;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A record that goes to the trash when deleted: hidden
 * from its table, restorable, and deleted for good only when it has no
 * money history. What may be done to each kind is set in TrashRules.
 */
trait Trashable
{
    use SoftDeletes;
}
