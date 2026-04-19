sail := "./vendor/bin/sail"
prod_image := env_var_or_default("APP_IMAGE", "ghcr.io/pascalkraupner/projectdesk")
prod_host := env_var_or_default("PROD_SSH_HOST", "vps1")
prod_path := env_var_or_default("PROD_PATH", "~/projects/projectdesk")

# List available commands
default:
    @just --list

# --- Initial Setup ---

# Full first-time setup (run this on a fresh clone)
setup:
    cp -n .env.example .env || true
    docker run --rm -v $(pwd):/opt -w /opt laravelsail/php84-composer:latest bash -c "composer install"
    {{sail}} up -d --wait
    {{sail}} artisan key:generate
    {{sail}} artisan migrate
    {{sail}} npm install
    {{sail}} npm run build

# --- Daily Use ---

# Start containers and Vite dev server
up:
    {{sail}} up -d
    {{sail}} npm run dev

# Start containers only (no Vite)
up-detached:
    {{sail}} up -d

# Stop containers
down:
    {{sail}} down

# Restart containers
restart:
    {{sail}} down
    {{sail}} up -d

# Run database migrations
migrate:
    {{sail}} artisan migrate

# Run tests
test:
    {{sail}} test

# Open a tinker REPL
tinker:
    {{sail}} tinker

# Build frontend assets for production
build:
    {{sail}} npm run build

# Start Vite dev server
dev:
    {{sail}} npm run dev

# Install npm dependencies
npm-install:
    {{sail}} npm install

# --- Maintenance ---

# Update PHP dependencies
composer-update:
    {{sail}} composer update

# Update npm dependencies
npm-update:
    {{sail}} npm update

# Fresh migrate (drops all tables)
migrate-fresh:
    {{sail}} artisan migrate:fresh

# Fresh migrate with seeders
migrate-fresh-seed:
    {{sail}} artisan migrate:fresh --seed

# Nuke everything (containers, images, volumes) and rebuild from scratch
nuke:
    {{sail}} down --rmi all --volumes || docker compose down --rmi all --volumes || true
    just setup

# --- Production ---

# Build and push a production image (CI normally does this)
prod-build TAG="latest":
    docker build -t {{prod_image}}:{{TAG}} .
    docker push {{prod_image}}:{{TAG}}

# Pull the latest image on the VPS, restart, and migrate
prod-deploy:
    ssh {{prod_host}} "cd {{prod_path}} && docker compose -f compose.prod.yaml pull && docker compose -f compose.prod.yaml up -d && docker compose -f compose.prod.yaml exec -T app php artisan migrate --force"

# Tail production logs
prod-logs:
    ssh {{prod_host}} "cd {{prod_path}} && docker compose -f compose.prod.yaml logs -f --tail=200"

# Open a shell in the production app container
prod-shell:
    ssh -t {{prod_host}} "cd {{prod_path}} && docker compose -f compose.prod.yaml exec app sh"

# Run an artisan command in production (usage: just prod-artisan migrate:status)
prod-artisan +CMD:
    ssh -t {{prod_host}} "cd {{prod_path}} && docker compose -f compose.prod.yaml exec app php artisan {{CMD}}"
