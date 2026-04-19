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

## Production

Lives at [projectdesk.kraupner.me](https://projectdesk.kraupner.me), fronted by Traefik on `vps1`.

### Deploy flow

Every push to `main` triggers `.github/workflows/deploy.yml`:

1. **test** — runs the PHPUnit suite on a disposable SQLite DB
2. **build** — builds the multi-stage `Dockerfile` and pushes `ghcr.io/<owner>/projectdesk:latest` + `:sha-<commit>`
3. **deploy** — SSHes to `vps1`, pulls the new image, restarts the stack, runs migrations, and warms the app caches

Manual trigger: **Actions → Deploy → Run workflow**.

### One-time VPS bootstrap

1. Point `projectdesk.kraupner.me` A record at vps1's IP
2. `ssh vps1 'mkdir -p ~/projects/projectdesk'`
3. Copy `compose.prod.yaml` and `.env.production.example` to the server as `.env`; fill in secrets (`APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `ADMIN_PASSWORD`). Generate `APP_KEY` with:
   ```bash
   docker run --rm ghcr.io/<owner>/projectdesk:latest php artisan key:generate --show
   ```
4. Log into GHCR so the server can pull:
   ```bash
   docker login ghcr.io -u <github-user> -p <PAT-with-read:packages>
   ```
5. First boot:
   ```bash
   docker compose -f compose.prod.yaml up -d
   docker compose -f compose.prod.yaml exec app php artisan migrate --force
   ```
6. Traefik issues the Let's Encrypt cert on the first HTTPS hit.

### Required GitHub secrets

| Secret | Value |
|---|---|
| `DEPLOY_SSH_HOST` | Public host or IP of vps1 (workflows don't read your local `~/.ssh/config`) |
| `DEPLOY_SSH_USER` | VPS username |
| `DEPLOY_SSH_KEY` | Private half of a deploy keypair added to vps1's `~/.ssh/authorized_keys` |

### Just commands

```bash
just prod-deploy      # Pull latest image on vps1 + migrate (run from local if you don't want to wait for CI)
just prod-logs        # Tail prod logs
just prod-shell       # Shell into the prod app container
just prod-artisan ... # Run an artisan command on prod (e.g. just prod-artisan migrate:status)
just prod-build TAG   # Build + push a prod image from your laptop (CI normally does this)
```

Host/path/image all come from env: `PROD_SSH_HOST`, `PROD_PATH`, `APP_IMAGE`.
