# Project Desk

Lightweight freelance project tracker. Manage projects, log time, share progress with clients.

**Stack:** Laravel 13 + Vue.js 3 + Inertia.js + Tailwind CSS + MySQL

## Prerequisites

- Docker & Docker Compose
- Git
- [just](https://github.com/casey/just) (command runner)

## Initial Setup

Clone and run the full setup:

```bash
git clone git@github.com:PascalKraupner/projectdesk.git
cd projectdesk
just setup
```

This will:
1. Copy `.env.example` to `.env`
2. Install PHP dependencies via Docker (no local PHP needed)
3. Start the containers
4. Generate the app key
5. Run database migrations
6. Install npm dependencies
7. Build frontend assets

Open [http://localhost](http://localhost).

Mailpit (email testing) is available at [http://localhost:8025](http://localhost:8025).

## Daily Use

```bash
just up       # Start containers + Vite dev server
just down     # Stop containers
just test     # Run tests
just tinker   # Open REPL
just migrate  # Run migrations
```

## Maintenance

```bash
just composer-update      # Update PHP dependencies
just npm-update           # Update npm dependencies
just migrate-fresh        # Drop all tables and re-migrate
just migrate-fresh-seed   # Drop all tables, re-migrate, and seed
just nuke                 # Destroy everything and rebuild from scratch
```

## All Commands

Run `just` to see all available commands.

## Sail Alias

Optional shortcut for running Sail commands directly:

```bash
alias sail='./vendor/bin/sail'
```

Then: `sail artisan ...`, `sail npm run dev`, etc.
