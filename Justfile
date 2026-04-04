sail := "./vendor/bin/sail"

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
