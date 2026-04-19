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

Auto-deployed via GitHub Actions to a self-hosted VPS behind Traefik.

### Deploy flow

Every push to `main` triggers `.github/workflows/deploy.yml`:

1. **test** — runs the PHPUnit suite on a disposable SQLite DB
2. **build** — builds the multi-stage `Dockerfile` and pushes `ghcr.io/<owner>/<repo>:latest` + `:sha-<commit>`
3. **deploy** — SSHes to the server, pulls the new image, runs migrations, and warms the app caches

Manual trigger: **Actions → Deploy → Run workflow**.

### One-time server bootstrap

Prereqs on the server: Docker, a running Traefik instance attached to an external network named `proxy`, and an ACME certresolver named `lets-encr`. Adjust the labels in `compose.prod.yaml` if your Traefik setup uses different names.

1. Point your domain's A record at the server's IP.
2. Create the project directory on the server:
   ```bash
   ssh <user>@<server> 'mkdir -p ~/projects/projectdesk'
   ```
3. Copy `compose.prod.yaml` and `.env.production.example` into that directory, rename the latter to `.env`, and fill in:
   - `APP_DOMAIN` and `APP_URL` — your domain
   - `APP_KEY` — generate with `docker run --rm ghcr.io/<owner>/<repo>:latest php artisan key:generate --show`
   - `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `ADMIN_PASSWORD` — random strings
4. If the GHCR package is private, log Docker into GHCR so the server can pull (public packages don't need this):
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
| `DEPLOY_SSH_HOST` | Public host or IP of the server (workflows can't read your local `~/.ssh/config`) |
| `DEPLOY_SSH_USER` | SSH user on the server |
| `DEPLOY_SSH_KEY` | Private half of a deploy keypair whose public half lives in the server user's `~/.ssh/authorized_keys` |

### Just commands (from your laptop)

```bash
just prod-deploy      # Pull the latest image on the server + run migrations (skip waiting for CI)
just prod-logs        # Tail prod logs
just prod-shell       # Shell into the prod app container
just prod-artisan ... # Run an artisan command on prod (e.g. just prod-artisan migrate:status)
just prod-build TAG   # Build + push a prod image from your laptop (CI normally does this)
```

Host, path, and image are env-overridable so the same commands work for any deployment:

| Variable | Used by | Meaning |
|---|---|---|
| `PROD_SSH_HOST` | the `just` commands | SSH host (resolved via your local `~/.ssh/config`) |
| `PROD_PATH` | the `just` commands | Directory on the server containing `compose.prod.yaml` + `.env` |
| `APP_IMAGE` | the `just` commands and the deploy workflow | Image to pull, e.g. `ghcr.io/<owner>/<repo>` |

Override per invocation, e.g.: `PROD_SSH_HOST=my-server.example.com just prod-logs`.
