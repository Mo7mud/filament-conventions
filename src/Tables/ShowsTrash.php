<?php

namespace Mo7mud\FilamentConventions\Tables;

/**
 * A page whose table has a trash: the trash button above the table
 * shows only the deleted rows, and pressing it again shows the rest.
 */
trait ShowsTrash
{
    public bool $trashOnly = false;

    public function toggleTrash(): void
    {
        $this->trashOnly = ! $this->trashOnly;

        $this->resetPage($this->getTablePaginationPageName());
    }
}
