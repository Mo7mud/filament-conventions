<?php

namespace Mo7mud\FilamentConventions;

use Illuminate\Support\ServiceProvider;

/**
 * Loads the views and translations of the shared table tools (export, print and trash) under the
 * "filament-conventions" namespace.
 */
class FilamentConventionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-conventions');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-conventions');
    }
}
