# Working Rules

Standing rules from the project owner for every project of the team.

## Flexibility: options, never one hardcoded choice

- Never hardcode a single business behavior. When customers might do something differently, build every option and let them choose in the panel, either at the moment of the action or as a setting (per company or per tenant where the project has tenants).
- When planning a feature, do not ask the owner to pick one behavior; build the options and the setting or action choice that switches between them. Ask only when the options themselves are unclear.

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
