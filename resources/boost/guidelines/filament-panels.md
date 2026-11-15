# Filament Panels

Standing rules from the project owner for every Filament panel of the team. Before writing Filament code you are not sure about, check "Verified Filament facts" below or read the installed source in `vendor/filament`; do not guess. Use only the APIs of the installed Filament version.

## Screens hold no business logic

- A Filament screen calls an action or service class; it does not call `save()`, `create()` or `DB` itself.
- Point a Filament action at a page method by name (`Action::make('save')->action('save')`) instead of a closure that calls `$this->save()`.

## Tables

- Every column that can be sorted is `sortable()`, including relationship columns (like `tenant.name`), dates and statuses.
- Every table has row checkboxes and bulk actions: at least export of the selected rows, plus a header action that exports the whole table.
- Add the bulk actions that fit the table (activate and deactivate, confirm payment, and similar). Bulk delete only when the owner decides so.
- A filter for every status, type or currency the table shows.
- Secondary columns are toggleable and hidden by default: `toggleable(isToggledHiddenByDefault: true)`.
- Clicking a row opens a useful view page when one exists, not an edit modal. Editing is a button in the row and in the header of the view page. On a resource list page, `->recordAction(null)` makes the row click use the view page URL.

## Tabs: client-side only

Every tab set is a client-side tab set: Alpine.js switches tabs at once in the browser, and the server does not draw the page again.

- Build tabs with `Filament\Schemas\Components\Tabs` and `Tab` inside the form or the page content, all in the header of one card, and keep the open tab in the address with `->persistTabInQueryString()`.
- Never bind tabs to a Livewire property (`->livewireProperty(...)`): every switch then becomes a server round trip that redraws the page.
- Clusters (`Filament\Clusters\Cluster`) and sub-navigation are forbidden, since they load a new page for every tab.
- A table inside a tab: the page `implements HasTable`, uses `InteractsWithTable`, and the tab holds `EmbeddedTable::make()`.
- A View or Edit page with more than one relation manager uses the `Mo7mud\FilamentConventions\Concerns\HasInstantRelationManagerTabs` trait. It loads every table with the page (hidden tabs never load lazily) and refreshes a tab's table through Livewire when the tab opens, so it shows changes made in other tabs.
- Tables update through Livewire (filters, actions, refreshes), never through a full page reload or a link to the same page.
- List page tabs (`getTabs()` on a List page) filter the table through Livewire and are allowed as they are.

## Themes: DaisyUI in every panel

- Every panel uses `osamanagi/filament-daisy-ui-themes` (MIT), with the theme switcher in the topbar. Install with `composer require osamanagi/filament-daisy-ui-themes:^1.1`, then `php artisan filament:assets`; it needs no npm or Tailwind build.
- Register it after `->colors()` in every panel provider, one instance per panel:

```php
use Nagi\FilamentDaisyUiThemes\FilamentDaisyUiThemesPlugin;

->plugin(FilamentDaisyUiThemesPlugin::make()->allThemes()->defaultTheme('corporate'))
```

- Options: `themes([...])`, `defaultTheme()`, `allThemes()`, `allLightThemes()`, `allDarkThemes()`, `themeSwitcher(false)`, `themeSwitcherHook()`.
- The switcher button sticks to the icons next to it, so every panel gives it a margin on both sides:

```css
/* The DaisyUI theme switcher sits at the end of the topbar; keep it off the neighbouring icons in both directions. */
[data-daisy-theme-switcher] {
    margin-inline: 10px;
}
```

- With a built Filament theme (`resources/css/filament/{panel}/theme.css`), add it at the end of that file. Without one, put it in a `<style>` tag in a Blade view loaded through `PanelsRenderHook::HEAD_END` in every panel.

## Dashboard stat cards

- Each card gets a color by its content (people, sessions, success, payments, problems...), and no color repeats on the same screen.
- The color goes in three places only: the card border, a side shadow, and the text and icon color inside it. The background never changes; it follows the theme (light, dark or DaisyUI).
- Add the classes with `Stat::extraAttributes([...], merge: true)` on the card root (`fi-wi-stats-overview-stat`), and style `-label`, `-value`, `-description` and the `.fi-icon` inside `-label-ctn`. Mirror the shadow for right-to-left (`[dir="rtl"]`).

## Forms

- Long lists (time zones, countries, currencies) are searchable, with translated names searchable in Arabic and English.
- Long texts customers read (instructions, terms) use `RichEditor`, with their images on the private disk.
- Every text goes through the translation files, and Arabic is the default language.

## Verified Filament facts (read from the installed source of Filament 5)

- Page content: `Filament\Pages\Page` renders `{{ $this->content }}`; `public function content(Schema $schema): Schema` defines it. Show the form inside it with `EmbeddedSchema::make('form')` (from `Filament\Schemas\Components`).
- Several schemas on a page: any method named after a schema that takes a `Schema` (like `kashierForm(Schema $schema)`) is discovered, and is used as `$this->kashierForm` or `EmbeddedSchema::make('kashierForm')`.
- A table inside a schema: `Filament\Schemas\Components\EmbeddedTable::make()` renders `$livewire->getTable()`, so the page must implement `HasTable`. `EmbeddedTable::make(SomeLivewire::class, [...])` returns a `Livewire` component.
- A Livewire component inside a schema: `Filament\Schemas\Components\Livewire::make($component, $data)`, with `lazy()`.
- Buttons inside a schema: `Filament\Schemas\Components\Actions::make([Action::make(...)])`.
- `Action::action('save')` (a string) calls the Livewire method by name and injects its parameters.
- Form wrapper: `Filament\Schemas\Components\Form::make([...])->livewireSubmitHandler('save')->footer([...])` renders a `<form wire:submit>`. Never put a table inside it (nested forms); use `Actions` buttons instead.
- Tabs: `Filament\Schemas\Components\Tabs::make()->tabs([...])`, with `persistTabInQueryString()` and `livewireProperty()` (the latter is forbidden here, see Tabs).
- Action schema: `Action::schema([...])` (`form()` is the old name); `using(Closure)` customizes saving, and `model(ModelClass::class)` sets the model of a create action on a table without a resource.
- Testing table actions: `TestAction::make('create')->table()` for a header action, `->table($record)` for a row action.
- Row click on a resource list page: `ListRecords` (and `ManageRecords`) picks the `view` row action, then `edit`, when they have no URL, so an `EditAction` in a modal opens on click. `->recordAction(null)` on the resource table uses the view page URL instead.
- View page: `ViewRecord::infolist()` calls `Resource::infolist()`. An `EditAction` in its header without an edit page opens a modal with `Resource::form()`.
- Page widgets: `getHeaderWidgets()` and `getFooterWidgets()` on `Page`. Record pages pass `['record' => $this->getRecord()]` to widgets through `getWidgetData()`, so the widget declares `public ?Model $record = null;`.
- Relation manager tabs on record pages are built in `Filament\Resources\Pages\Concerns\HasRelationManagers::getRelationManagersContentComponent()`; by default they are bound to the `activeRelationManager` Livewire property, which is why the shared trait replaces them.
- When a Filament panel has a tenant, Filament assigns the current tenant to new records of resource models on `creating`, also in tests; move a record to another tenant after creating it when a test needs that.
- Render hooks: `PanelsRenderHook::HEAD_END` adds a `<style>` to the head of every page of the panel.
