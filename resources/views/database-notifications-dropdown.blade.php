@php
    use Filament\Support\Icons\Heroicon;
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Support\View\Components\BadgeComponent;
    use Illuminate\Contracts\Pagination\Paginator;

    $notifications = $this->getNotifications();
    $unreadNotificationsCount = $this->getUnreadNotificationsCount();
    $hasNotifications = $notifications->count();
    $isPaginated = $notifications instanceof Paginator && $notifications->hasPages();
    $pollingInterval = $this->getPollingInterval();
    // The panel sets the bell button while it serves a page; Filament's topbar button stands in otherwise.
    $trigger = $this->getTrigger() ?? view('filament-panels::components.topbar.database-notifications-trigger');
@endphp

{{-- The bell as a dropdown under its button, like the user menu (Mo7mud\FilamentConventions\Notifications\DatabaseNotificationsDropdown). The panel is not teleported, so Livewire keeps its content current while it is open. --}}
<div
    class="fi-no-database fi-cv-notifications"
    @if ($pollingInterval) wire:poll.{{ $pollingInterval }} @endif
>
    <style>
        .fi-cv-notifications .fi-dropdown-panel { padding: 0; overflow: hidden; display: flex; flex-direction: column; }
        .fi-cv-notifications-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .75rem 1rem; border-bottom: 1px solid var(--gray-200); }
        .dark .fi-cv-notifications-header, .dark .fi-cv-notifications-footer { border-color: var(--gray-700); }
        .fi-cv-notifications-heading { display: flex; align-items: center; gap: .5rem; font-weight: 600; font-size: .95rem; color: var(--gray-950); }
        .dark .fi-cv-notifications-heading { color: #fff; }
        .fi-cv-notifications-header .fi-ac { display: flex; gap: .5rem; flex-wrap: wrap; justify-content: flex-end; }
        .fi-cv-notifications-list { overflow-y: auto; overscroll-behavior: contain; }
        .fi-cv-notifications-list > [role="listitem"] + [role="listitem"] { border-top: 1px solid var(--gray-100); }
        .dark .fi-cv-notifications-list > [role="listitem"] + [role="listitem"] { border-color: var(--gray-800); }
        .fi-cv-notifications-footer { padding: .5rem 1rem; border-top: 1px solid var(--gray-200); }
        .fi-cv-notifications-empty { display: flex; flex-direction: column; align-items: center; text-align: center; gap: .35rem; padding: 2rem 1.5rem; color: var(--gray-500); }
        .fi-cv-notifications-empty .fi-icon { width: 2.25rem; height: 2.25rem; color: var(--gray-400); }
        .fi-cv-notifications-empty strong { color: var(--gray-950); font-weight: 600; }
        .dark .fi-cv-notifications-empty strong { color: #fff; }
    </style>

    @if ($trigger)
        <x-filament::dropdown placement="bottom-end" width="md" max-height="min(34rem, calc(100vh - 5rem))" :menu="false" wire:key="database-notifications-dropdown">
            <x-slot name="trigger">
                {{ $trigger->with(['unreadNotificationsCount' => $unreadNotificationsCount]) }}
            </x-slot>

            <div class="fi-cv-notifications-header">
                <h2 id="database-notifications.heading" class="fi-cv-notifications-heading">
                    {{ __('filament-notifications::database.modal.heading') }}

                    @if ($unreadNotificationsCount)
                        <span
                            {{
                                (new FilamentComponentAttributeBag)->color(BadgeComponent::class, 'primary')->class([
                                    'fi-badge fi-size-xs',
                                ])
                            }}
                        >
                            {{ $unreadNotificationsCount }}
                        </span>
                    @endif
                </h2>

                @if ($hasNotifications)
                    <div class="fi-ac">
                        @if ($unreadNotificationsCount && $this->markAllNotificationsAsReadAction?->isVisible())
                            {{ $this->markAllNotificationsAsReadAction }}
                        @endif

                        @if ($this->clearNotificationsAction?->isVisible())
                            {{ $this->clearNotificationsAction }}
                        @endif
                    </div>
                @endif
            </div>

            @if ($hasNotifications)
                <div
                    aria-labelledby="database-notifications.heading"
                    role="list"
                    class="fi-no-notifications fi-cv-notifications-list"
                >
                    @foreach ($notifications as $notification)
                        <div
                            role="listitem"
                            wire:key="{{ $notification->getKey() }}.database-notifications.ctn"
                            @class([
                                'fi-no-notification-read-ctn' => ! $notification->unread(),
                                'fi-no-notification-unread-ctn' => $notification->unread(),
                            ])
                        >
                            @if ($notification->unread())
                                <span class="fi-sr-only">
                                    {{ __('filament-notifications::database.modal.unread_label') }}
                                </span>
                            @endif

                            {{ $this->getNotification($notification)->inline() }}
                        </div>
                    @endforeach
                </div>

                @if ($isPaginated)
                    <div class="fi-cv-notifications-footer">
                        <x-filament::pagination :paginator="$notifications" />
                    </div>
                @endif
            @else
                <div class="fi-cv-notifications-empty">
                    <x-filament::icon :icon="Heroicon::OutlinedBellSlash" />
                    <strong>{{ __('filament-notifications::database.modal.empty.heading') }}</strong>
                    <span>{{ __('filament-notifications::database.modal.empty.description') }}</span>
                </div>
            @endif
        </x-filament::dropdown>
    @endif

    @if ($broadcastChannel = $this->getBroadcastChannel())
        @script
            <script>
                setUpFilamentBroadcastNotifications({
                    $wire,
                    broadcastChannel: @js($broadcastChannel),
                    event: '.database-notifications.sent',
                    onNotification: () => $wire.call('$refresh'),
                })
            </script>
        @endscript
    @endif
</div>
