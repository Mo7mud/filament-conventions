# Working Rules

Standing rules from the project owner for every project of the team.

## Flexibility: everything from the panel

- No operational values in code: prices, countries, currencies, texts, colors, time limits, rules and links are settings in the panel, and their defaults come from seeders.
- Never hardcode a single business behavior. When customers might do something differently, build every option and let them choose in the panel, either at the moment of the action or as a setting (per company or per tenant where the project has tenants).
- When planning a feature, do not ask the owner to pick one behavior; build the options and the setting or action choice that switches between them. Ask only when the options themselves are unclear.

## Floating menus, not side panels

- Menus and lists opened from the top bar of any interface (notifications, account, themes, quick actions) open as a floating window under their button, like the account menu, never as a side panel that slides over the page. In Filament panels this is built with the shared tools (see Filament Panels, "Topbar menus").

## Dependencies and licenses

- Libraries only under MIT, BSD, Apache 2.0 or ISC; never GPL or AGPL. Fonts under OFL, icons under open licenses, and no images without a license.
- Every new dependency needs the owner's approval.

## Full features, not a minimal version

- Build each user role's area (staff, customers, field workers, admins) to the level of the best competitors, not as a bare minimum screen.
- Before building or declaring done any role-facing area, check the project's competitor comparison documents when they exist, and build to that level, or tell the owner up front exactly what is left out and why.
- Verify the result in a real browser (screenshots), not only with tests.

## Standard Arabic inside the project

- Every Arabic text inside the project is Modern Standard Arabic (فصحى), never colloquial: translation files, interface text, demo data, default texts in migrations, notification templates, tests and documentation files.
- Chat replies to the owner may stay colloquial; only what is written into the project must be فصحى.
- Preferred terms: هاتف (not موبايل)، رمز (not كود)، الراتب (not المرتب)، الأموال (not الفلوس)، الطابق (not الدور for floors).
- Code that parses what users type (keywords, yes/no words in importers) may also accept colloquial forms.
- Default texts already stored on a live server change through a data migration that updates them only when they are still unchanged.
