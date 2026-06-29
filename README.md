# Verk

Site operations layer for ProcessWire. Not a generic PM — a tool built around PW's data model.

![Verk](assets/Verk.png)

Tasks are linked to PW Pages. Sprints group delivery work by week, month, or quarter. The Editorial Calendar reads real page date fields and task due dates. Content Audit runs PW selectors with field-path checks. Everything stays inside your PW install.

**Author:** Maxim Semenov  
**Website:** [smnv.org](https://smnv.org)  
**Email:** [maxim@smnv.org](mailto:maxim@smnv.org)

If this project helps your work, consider supporting future development: [GitHub Sponsors](https://github.com/sponsors/mxmsmnv) or [smnv.org/sponsor](https://smnv.org/sponsor/).

## What it does

| Section | Purpose |
|---|---|
| **Dashboard** | Open tasks, upcoming publications, audit alerts, and active sprint planning |
| **Tasks** | Create/assign tasks linked to specific PW pages — one click opens the page editor |
| **Calendar** | Month, week, and quarter views for page publications + task due dates |
| **Content Audit** | Run PW selectors and dot-notation field checks to find missing content |
| **Knowledge Base** | Rich editorial notes organized by category, searchable and exportable |
| **Sprints** | Sprint planning, quarter grouping, task assignment, DOCX export, and progress tracking |
| **Settings** | Calendar source, fiscal quarter start, assignee role scope, and configurable Page Editor Widget |

## Key features

- **Page Picker** — live search across PW pages when creating a task; task stores `page_id`
- **Edit in PW** — every task with a linked page shows a direct `/admin/page/edit/?id=X` button
- **View on site** — opens the front-end URL in a new tab
- **Page Editor Widget** — Verk injects a configurable task widget into ProcessPageEdit, with live settings preview
- **Assignee role scope** — optionally keep task assignee dropdowns and filters limited to selected ProcessWire roles
- **Audit to tasks** — create one task or bulk tasks from audit results; page context is prefilled
- **Calendar** — configurable publication source plus task due dates in month, week, and quarter views
- **Quarter planning** — fiscal quarter start month, sprint quarter filters, date planning helpers, and quarter labels on tasks
- **Rich text** — task descriptions, comments, note content, and sprint goals use TinyMCE when `InputfieldTinyMCE` is installed
- **DOCX exports** — task lists, notes, sprints, and knowledge base exports
- **Audit rules** — plain text config: `Label | Scope selector | Field path | Message | Users`, with dot-notation subfields; the optional `Users` column (comma-separated usernames) limits a rule to those users on the dashboard's "My Content Audit" card
- **Return-aware forms** — create/edit flows can preserve filtered list URLs and return users to the exact context they came from

## Requirements

- ProcessWire >= 3.0.200
- PHP >= 8.0
- InputfieldTinyMCE is optional but used automatically for rich text fields when installed

## Installation

1. Copy `Verk/` to `site/modules/`
2. Admin > Modules > Refresh > Install **Verk**
3. Go to Admin > Verk > Settings and configure:
   - Calendar template(s)
   - Calendar date field
   - Quarter start month
   - Optional assignee roles
   - Page Editor Widget display options

## Upgrade

After copying a new version into `site/modules/Verk/`, run **Admin > Modules > Refresh** so ProcessWire detects the module version bump. Version `1.3.3` is published as module version `133`; the upgrade hook runs `VerkDB::migrate()` and keeps existing Verk data intact.

## Versioning

Verk uses SemVer (`Major.Minor.Patch`). Every code update should include the matching module version bump and a short entry in `CHANGELOG.md`; patch releases are for fixes and small polish, minor releases are for new backwards-compatible features, and major releases are for breaking changes.

## Database tables

| Table | Contents |
|---|---|
| `vk_tasks` | Tasks with `page_id` FK to PW pages |
| `vk_comments` | Discussion threads on tasks |
| `vk_notes` | Knowledge base articles |
| `vk_sprints` | Sprint windows, goals, and status |
| `vk_time_logs` | Time entries linked to tasks |

## Audit rule format

```
# Comment lines are ignored
Products without images | template=product | images | No product images
Empty body text | template!=admin | body | Body field empty
Missing SEO title | template!=admin | seo.title | SEO title not set
Missing city | template=location | address.city | City is missing
Missing price amount | template=product | prices.*.amount | Price amount is empty
Needs clinical review | template=message, completed_reviews!=1 |  | Needs clinical review | john-smith,jane-brown
```

Columns:

1. `Label` — shown as the audit tab/result title.
2. `Scope selector` — standard ProcessWire selector used to find candidate pages.
3. `Field path` — ProcessWire field name or dot-notation subfield path to test.
4. `Message` — displayed when the field path is empty or unavailable.
5. `Users` — optional comma-separated ProcessWire usernames. When set, the rule appears only on those users' "My Content Audit" dashboard card; leave it empty for a rule visible to everyone. (The full Content Audit page always lists every rule.)

## Architecture decision

Tasks link to PW pages via `page_id` (integer). The page data itself lives in PW — Verk never duplicates it. If a page is deleted in PW, the task orphans gracefully (linked page disappears, task remains).

The page editor widget uses `hookAfter('ProcessPageEdit::buildForm')` — no template files modified. Widget output is controlled from Verk Settings and can show/hide status, priority, due date, quarter, assignee, empty state, and create link.

## License

MIT

## Author

Maxim Semenov  
https://smnv.org  
maxim@smnv.org
