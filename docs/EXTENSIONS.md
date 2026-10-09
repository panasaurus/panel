# Building extensions for Panasaurus

Panasaurus ships with the **Blueprint extension framework** pre-installed — no patching, no
manual file merging. Extensions written for Blueprint (and therefore stock Pterodactyl)
install and run out of the box.

## How it works

```
your-extension.blueprint        ← zip archive with a conf.yml + assets
        │  panasaurus -install <file>
        ▼
.blueprint/extensions/<id>/     ← installed extension lives here
        │  symlinked into
        ├── public/extensions/<id>        (public files)
        ├── public/assets/extensions/<id> (styles/images)
        ├── resources/views/blueprint/admin/wrappers/... (admin UI)
        └── resources/scripts/blueprint/...              (client UI, rebuilt)
```

## CLI reference

The CLI lives inside the container as both `panasaurus` and `blueprint`:

| Command | Description |
|---|---|
| `panasaurus -install <name>` | Install/update an extension from a `.blueprint` file in the panel root |
| `panasaurus -remove <name>` | Remove an installed extension |
| `panasaurus -query <name>` | Show information about an installed extension |
| `panasaurus -list` | List installed extensions |
| `panasaurus -init` | Scaffold a new extension project (developer mode) |
| `panasaurus -build` | Build an extension from source |

## Extension surface in Panasaurus

### Admin (Blade)
- `/admin/extensions` — extension manager (list, configure, update)
- `/admin/extensions/marketplace` — built-in marketplace with the live Blueprint directory
- Admin sidebar **Extensions** section
- `blueprint.admin.wrappers` — extension blade wrappers render into every admin page

### Client (React 19)
Extension components mount around the panel's UI through placeholder files in
`resources/scripts/blueprint/components/`:

| Placeholder | Renders |
|---|---|
| `Dashboard/Global/BeforeSection,AfterSection` | Around the entire dashboard |
| `Dashboard/Serverlist/BeforeContent,AfterContent` | Around the server list |
| `Navigation/NavigationBar/Before,AfterNavigation` | Around the dashboard sidebar nav |
| `Navigation/SubNavigation/AdditionalAccountItems` | Extra account nav items |
| `Server/Terminal/Before,AfterContent,AdditionalPowerButtons,CommandRow` | Console page |
| `Server/Backups,Databases,Files,Network,Settings,Startup,Users/Before,After` | Around each server page |
| `Account/API,Overview,SSH/Before,After` | Account pages |

Routes are registered through `resources/scripts/blueprint/extends/routers/routes.ts`
(account + server routes, with per-route permissions and admin-only flags).

### Backend (PHP)
- `app/BlueprintFramework/Libraries/ExtensionLibrary/` — `dbGet/dbSet`, template helpers, console APIs
- `routes/blueprint/{application,client,web}.php` — auto-loaded extension route directories
- `GetExtensionSchedules` — register artisan schedules from extensions
- Database settings via the `blueprint::` settings store

## Quick start scaffold

```bash
panasaurus -init
# follow the prompts, then
panasaurus -build
```

Full developer documentation lives at https://blueprint.zip/guides.

## Versioning your extension

The admin extension manager shows update badges by comparing your local version
against `blueprint.zip`'s remote metadata (fetched nightly by `php artisan bp:meta`).
Make sure your `conf.yml` has a `version` and the identifier matches your
marketplace listing.
