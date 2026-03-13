## Meeting Planner Bot — Makefile

.PHONY: setup migrate seed webhook deploy logs

# ── First-time setup (Requires PHP locally) ──────────────────────────────────
setup:
	@if [ ! -f .env ]; then cp .env.example .env; echo "✅ .env created — edit it now!"; fi
	@touch database/database.sqlite
	php artisan key:generate
	php artisan migrate --force
	php artisan db:seed --force
	@echo "✅ Setup complete."

# ── First-time setup (Using ONLY Docker) ──────────────────────────────────────
docker-setup:
	@if [ ! -f .env ]; then cp .env.example .env; echo "✅ .env created!"; fi
	docker compose build
	docker compose up -d app
	docker compose exec app php artisan key:generate
	docker compose exec app touch database/database.sqlite
	docker compose exec app chown www-data:www-data database/database.sqlite
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan db:seed --force
	docker compose up -d
	@echo "✅ Docker setup complete. App running at http://localhost"

# ── Local dev ─────────────────────────────────────────────────────────────────
dev:
	php artisan serve

# ── Database (Via Docker) ─────────────────────────────────────────────────────
migrate:
	docker compose exec app php artisan migrate --force

seed:
	docker compose exec app php artisan db:seed --force

# ── Telegram webhook (Via Docker) ─────────────────────────────────────────────
# Usage: make webhook URL=https://your-domain.com
webhook:
	docker compose exec app php artisan nutgram:hook:set $(URL)/telegram/webhook
	@echo "✅ Webhook registered."

webhook-info:
	docker compose exec app php artisan nutgram:hook:info

webhook-remove:
	docker compose exec app php artisan nutgram:hook:remove

# ── Docker deployment ─────────────────────────────────────────────────────────
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

deploy:
	git pull
	docker compose build app queue scheduler
	docker compose up -d
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan db:seed --force
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	@echo "✅ Deployed."

logs:
	docker compose logs -f

# ── Test reminder manually (Via Docker) ───────────────────────────────────────
test-reminders:
	docker compose exec app php artisan reminders:send
