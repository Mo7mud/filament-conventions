# Filament Panels

Standing rules from the project owner for every Filament panel of the team. Before writing Filament code you are not sure about, check "Verified Filament facts" below, use Boost `search-docs`, or read the installed source in `vendor/filament`; do not guess. Use only the APIs of the installed Filament version, never code from another major version. When something you needed was missing here, tell the owner so it is added to this package.

## Screens hold no business logic

- Business logic lives in action and service classes. Filament screens, Livewire components and API controllers are thin adapters that call them; a screen never calls `save()`, `create()` or `DB` itself.
- Point a Filament action at a page method by name (`Action::make('save')->action('save')`) instead of a closure that calls `$this->save()`.
- Shared rules are applied through shared components (a base class, trait or helper), never by copying the same code into every screen.

## Plugins

- As few Filament plugins as possible: free, actively maintained, and under a permissive license (MIT, BSD, Apache 2.0 or ISC; never GPL or AGPL). Every new plugin needs the owner's approval.

## Tables

- Every column that can be sorted is `sortable()`, including relationship columns (like `tenant.name`), dates and statuses. Text columns are `searchable()`.
- A filter for every status, type or currency the table shows.
- Secondary columns are toggleable and hidden by default: `toggleable(isToggledHiddenByDefault: true)`.
- Row checkboxes with bulk actions on every table: export of the selected rows, plus a header action that exports the whole table. Add the bulk actions that fit the table (activate and deactivate, confirm payment, and similar).
- Clicking a row opens a useful view page when one exists, not an edit modal. Editing is a button in the row and in the header of the view page. On a resource list page, `->recordAction(null)` makes the row click use the view page URL.
- Tables stay fast on tens of thousands of rows: server-side pagination, eager loading (no N+1), and indexes on the columns they sort and filter by.

## Delete and trash

- Every business table has delete on the row and as a bulk action, plus a "Trash" button with a trash icon above the table that shows the deleted rows only, with restore and permanent delete, on the row and in bulk.
- Delete is a soft delete first (`deleted_at`); permanent delete is the operator's choice from the trash.
- A record with work in progress cannot be deleted at all (an active subscription, upcoming sessions, a payment started or under review), and the reason is shown.
- A record with financial transactions is only ever soft deleted and can be restored; it is never deleted permanently. A record linked to money without transactions can be deleted permanently.
- `belongsTo` relations to a model with a trash read it `withTrashed()`, duplicate checks in code use `withTrashed()`, and internal deletes (deleting a tenant, replacing an invoice) use `forceDelete()`.

## Tabs: client-side only

Every tab set is a client-side tab set: Alpine.js switches tabs at once in the browser, and the server does not draw the page again.

- Build tabs with `Filament\Schemas\Components\Tabs` and `Tab` inside the form or the page content, all in the header of one card, and keep the open tab in the address with `->persistTabInQueryString()`.
- Never bind tabs to a Livewire property (`->livewireProperty(...)`): every switch then becomes a server round trip that redraws the page.
- Clusters (`Filament\Clusters\Cluster`) and sub-navigation are forbidden, since they load a new page for every tab.
- A table inside a tab: the page `implements HasTable`, uses `InteractsWithTable`, and the tab holds `EmbeddedTable::make()`.
- Relation tables on a View or Edit page are client-side tabs too, never the default relation manager tabs (bound to a Livewire property). Either use the `Mo7mud\FilamentConventions\Concerns\HasInstantRelationManagerTabs` trait on the page, or put each relation manager as a tab inside the page's own `Tabs` card with `Livewire::make(SomeRelationManager::class, ['ownerRecord' => $record, 'pageClass' => static::class])`.
- The trait loads every table with the page (lazy tables never load inside a hidden tab) and refreshes a tab's table through Livewire when the tab opens, so it shows changes made in other tabs.
- Tables update through Livewire (filters, actions, refreshes), never through a full page reload or a link to the same page.
- List page tabs (`getTabs()` on a List page) filter the table through Livewire and are allowed as they are.

## Forms and modals

- Cards side by side have about the same height: a short card next to a short one, a long one next to a long one, and a card with no match takes the full width.
- A modal with two cards in a row is widened (`modalWidth`), and its row becomes one column on small screens.
- One choice from several options is a `Radio` group, or a `Select` for long lists. Never `ToggleButtons`, buttons styled as options, or several checkboxes for one value. In custom (non-Filament) views, `<input type="radio">` inside a `label`.
- Long lists (time zones, countries, currencies) are searchable, with translated names searchable in Arabic and English.
- Long texts customers read (instructions, terms) use `RichEditor`, with their images on the private disk.
- Every text goes through the translation files, Arabic is the default language, and the direction follows the language.
- Accessibility: a label for every field, enough contrast, and full keyboard use.

## Livewire

- Every action goes through a policy. Every record ID in a component is protected with `#[Locked]`, or the full model is passed instead. Each component has IDOR tests.
- Pure interface interactions use Alpine.js. Fields reach the server on blur or with a debounce, never on every keystroke.
- Heavy parts load as lazy islands, and links use `wire:navigate`.

## Themes: DaisyUI in every panel

- Every panel uses `osamanagi/filament-daisy-ui-themes` (MIT), with the theme switcher in the topbar. Install with `composer require osamanagi/filament-daisy-ui-themes:^1.1`, then `php artisan filament:assets`; it needs no npm or Tailwind build.
- Register it after `->colors()` in every panel provider, one instance per panel. The theme list and the default theme live in one place (one class or shared panel defaults), not repeated per panel:

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
- Brand colors come from the appearance settings in one place (CSS variables) that both Filament and the custom views read.

## Dashboard stat cards

- Each card gets a color by its content (people, sessions, success, payments, problems...), and no color repeats on the same screen.
- The color goes in three places only: the card border, a side shadow, and the text and icon color inside it. The background never changes; it follows the theme (light, dark or DaisyUI).
- Build every card through one shared stat card helper. It adds the classes with `Stat::extraAttributes([...], merge: true)` on the card root (`fi-wi-stats-overview-stat`); style `-label`, `-value`, `-description` and the `.fi-icon` inside `-label-ctn`, and mirror the shadow for right-to-left (`[dir="rtl"]`).

## Tests

- Every screen has an automated test, alongside the tenant isolation and permission tests (allowed and refused) each feature needs.

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
- Relation manager tabs on record pages are built in `Filament\Resources\Pages\Concerns\HasRelationManagers::getRelationManagersContentComponent()`; by default they are bound to the `activeRelationManager` Livewire property, which is why the shared trait replaces them. Relation managers are lazy by default (`'lazy' => true` in `getDefaultProperties()`).
- When a Filament panel has a tenant, Filament assigns the current tenant to new records of resource models on `creating`, also in tests; move a record to another tenant after creating it when a test needs that.
- Render hooks: `PanelsRenderHook::HEAD_END` adds a `<style>` to the head of every page of the panel.
- Clusters (for reference only, forbidden here): `Filament\Clusters\Cluster`, `$subNavigationPosition` from `Filament\Pages\Enums\SubNavigationPosition`, `$cluster` on a page or resource, and `discoverClusters(in:, for:)` on the panel.
