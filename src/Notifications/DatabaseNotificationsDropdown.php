<?php

namespace Mo7mud\FilamentConventions\Notifications;

use Filament\Notifications\Livewire\DatabaseNotifications;
use Illuminate\Contracts\View\View;

/**
 * The notification bell of the panel as a dropdown under its button, like the user menu and the other topbar menus,
 * instead of Filament's slide-over that covers the side of the page. Everything else is Filament's: the unread
 * count, mark all as read, clear, polling, live updates and paging.
 *
 * Use it in each panel provider: ->databaseNotifications(livewireComponent: DatabaseNotificationsDropdown::class).
 */
class DatabaseNotificationsDropdown extends DatabaseNotifications
{
    public function render(): View
    {
        return view('filament-conventions::database-notifications-dropdown');
    }
}
