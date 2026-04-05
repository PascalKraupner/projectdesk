# Project Desk

Lightweight freelance project tracker. Manage clients, projects, log time, share progress.

## Stack

- **Backend:** Laravel 13, PHP 8.5, MySQL 8.4
- **Frontend:** Vue 3, Inertia.js 2, Tailwind CSS, shadcn-vue (Reka UI)
- **Dev:** Docker (Laravel Sail), Justfile, Vite 8
- **Mail:** Mailpit (dev)

## Commands

```bash
just setup              # First-time setup (fresh clone)
just up                 # Start containers + Vite dev server
just down               # Stop containers
just restart            # Restart containers
just test               # Run tests
just migrate            # Run migrations
just migrate-fresh-seed # Fresh migrate + seed
just nuke               # Destroy everything and rebuild
sail artisan make:model ModelName -mfs  # Model + migration + factory + seeder
sail artisan make:controller NameController
sail artisan make:request NameRequest
sail artisan make:service NameService  # If available, otherwise manual
```

## Architecture Rules

### Thin Controllers
Controllers handle HTTP only: receive request, call service, return response. No business logic.

```php
// GOOD
public function store(StoreClientRequest $request): RedirectResponse
{
    $this->clientService->create($request->validated());
    return redirect()->route('clients.index');
}

// BAD - business logic in controller
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([...]);
    $client = Client::create($validated);
    $client->projects()->create([...]);
    Mail::send(...);
    return redirect()->route('clients.index');
}
```

### Service Classes
One service per domain. Lives in `app/Services/`. Injected via constructor.

```
app/Services/
├── ClientService.php
├── ProjectService.php
└── DashboardService.php
```

### Form Request Validation
Always use Form Request classes. Never validate inline in controllers.

```
app/Http/Requests/
├── Client/
│   ├── StoreClientRequest.php
│   └── UpdateClientRequest.php
└── Project/
    ├── StoreProjectRequest.php
    └── UpdateProjectRequest.php
```

### Models
Models contain ONLY: relationships, scopes, accessors, mutators, casts. No business logic.

### Enums
Always use PHP backed enums for fields like status, type, etc. Cast them in models.
Backend enums live in `app/Enums/`. Frontend mirrors them in `resources/js/Enums/`.
Both sides must stay in sync.

```
app/Enums/
└── ProjectStatus.php

resources/js/Enums/
└── ProjectStatus.js
```

### DRY / KISS
- Extract shared logic into services or traits
- No premature abstraction — three similar lines > a premature helper
- Simple solutions first, refactor when patterns emerge
- Reusable Vue components for repeated UI patterns

### File Organization
```
app/
├── Http/
│   ├── Controllers/     # Thin, HTTP-only
│   └── Requests/        # Grouped by domain
├── Models/              # Relationships, scopes, casts only
└── Services/            # Business logic lives here

resources/js/
├── Components/          # Reusable UI components (shadcn-vue + custom)
├── Composables/         # Shared Vue composition functions
├── Layouts/             # Page layouts
└── Pages/               # Inertia pages (mapped to routes)
    ├── Auth/
    ├── Client/
    ├── Dashboard/
    └── Project/
```

### Migrations
- No `down()` methods. We never rollback — we migrate forward with new migrations.

### Code Style
- PHP: Laravel Pint (PSR-12)
- Vue: `<script setup>` with composition API, no options API
- Props: use `defineProps` with type declarations
- No comments unless logic is non-obvious
- No docblocks on self-documenting methods
- Frontend: use default/minimal Tailwind classes only — no fancy styling yet. Backend first, design later.

### Testing
- Feature tests for HTTP endpoints
- Service tests for business logic
- Run with `just test`

### Data Model

```
clients
  - id, name, email, timestamps

projects
  - id, client_id (FK), title, status (enum: active/paused/completed), timestamps
```

Client hasMany Projects. Project belongsTo Client.

Add fields only when a feature requires them.
