<?php

namespace Mo7mud\FilamentConventions\Concerns;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Arr;
use Illuminate\Support\Js;

/**
 * Relation manager tabs that switch in the browser, like the tabs of a form, instead of asking the server to draw the
 * page again. Every table loads with the page, and refreshes itself when its tab opens again, so it shows what changed
 * in the other tabs. The open tab is kept in the address.
 */
trait HasInstantRelationManagerTabs
{
    public function getRelationManagersContentComponent(): Component
    {
        $managers = $this->getCachedRelationManagers();

        if (count($managers) < 2) {
            return parent::getRelationManagersContentComponent();
        }

        $tabs = [];

        foreach ($managers as $key => $manager) {
            $tab = $manager instanceof RelationGroup
                ? $this->getRelationManagerTabComponent($key)
                : $this->instantRelationManagerTab($manager);

            if ($tab) {
                $tabs[] = $tab->extraAttributes(fn (Tab $component): array => [
                    'x-init' => '$watch(\'tab\', (value) => value === '.Js::from($component->getKey(isAbsolute: false))
                        .' && window.Livewire.all().find((manager) => $el.contains(manager.el))?.$wire.$refresh())',
                ]);
            }
        }

        return Tabs::make()
            ->key('relationManagerTabs')
            ->contained(false)
            ->persistTabInQueryString('relation')
            ->tabs($tabs);
    }

    /**
     * The tab of one relation manager, rendered with the page instead of lazily, since hidden tabs never load lazily.
     */
    private function instantRelationManagerTab(string|RelationManagerConfiguration $manager): Tab
    {
        $managerClass = $this->normalizeRelationManagerClass($manager);
        $properties = $manager instanceof RelationManagerConfiguration
            ? [...$manager->relationManager::getDefaultProperties(), ...$manager->getProperties()]
            : $managerClass::getDefaultProperties();

        return $managerClass::getTabComponent($this->getRecord(), static::class)
            ->schema(fn (): array => [
                Livewire::make($managerClass, [...$this->getRelationManagerLivewireData(), ...Arr::except($properties, 'lazy')])
                    ->key($managerClass),
            ]);
    }
}
