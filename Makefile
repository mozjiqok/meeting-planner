## Meeting Planner Bot — Makefile

.PHONY: setup migrate seed webhook deploy logs

# ── First-time setup ──────────────────────────────────────────────────────────
setup:
	@if [ ! -f .env ]; then cp .env.example .env; echo "✅ .env created — edit it now!"; fi
	@touch database/database.sqlite
	php artisan key:generate
	php artisan migrate --force
	php artisan db:seed --force
	@echo "✅ Setup complete."

# ── Local dev ─────────────────────────────────────────────────────────────────
dev:
	php artisan serve

# ── Database ──────────────────────────────────────────────────────────────────
migrate:
	php artisan migrate --force

seed:
	php artisan db:seed --force

# ── Telegram webhook ──────────────────────────────────────────────────────────
# Usage: make webhook URL=https://your-domain.com
webhook:
	php artisan nutgram:hook:set $(URL)/telegram/webhook
	@echo "✅ Webhook registered."

webhook-info:
	php artisan nutgram:hook:info

webhook-remove:
	php artisan nutgram:hook:remove

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

# ── Test reminder manually ────────────────────────────────────────────────────
test-reminders:
	php artisan reminders:send
