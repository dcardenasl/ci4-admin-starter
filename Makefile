.DEFAULT_GOAL := help

# ── Formatting ─────────────────────────────────────────────────────────────────
GREEN  := \033[0;32m
YELLOW := \033[0;33m
RESET  := \033[0m

.PHONY: help serve css install test test-cov analyse quality lint fix docker-up docker-down setup

help: ## Show this help message
	@echo ""
	@echo "  CI4 Admin Starter — available commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-14s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ── Development ────────────────────────────────────────────────────────────────

serve: ## Start the PHP dev server on port 8082
	php spark serve --port 8082

css: ## Start Tailwind CSS watcher (run in a separate terminal)
	npm run dev:css

install: ## Install all dependencies (Composer + npm)
	composer install
	npm install

setup: ## Run interactive project setup script
	bash install.sh

# ── Testing ────────────────────────────────────────────────────────────────────

test: ## Run the full test suite (no coverage)
	vendor/bin/phpunit --no-coverage

test-cov: ## Run tests with HTML coverage report
	vendor/bin/phpunit

test-unit: ## Run unit tests only
	vendor/bin/phpunit tests/unit

test-feature: ## Run feature tests only
	vendor/bin/phpunit tests/feature

# ── Code Quality ───────────────────────────────────────────────────────────────

analyse: ## Run PHPStan static analysis (level 8)
	vendor/bin/phpstan analyse

lint: ## Check PHP code style (dry-run) and lint JS
	vendor/bin/php-cs-fixer fix --dry-run --diff
	npm run lint:js

fix: ## Auto-fix PHP code style
	vendor/bin/php-cs-fixer fix

quality: ## Run PHPStan + CS Fixer check
	composer quality

ci: ## Full CI suite: tests + quality checks
	composer ci

# ── Docker ─────────────────────────────────────────────────────────────────────

docker-up: ## Start Docker containers (app + web)
	docker compose up -d

docker-down: ## Stop Docker containers
	docker compose down

docker-build: ## Rebuild Docker images
	docker compose build --no-cache

docker-logs: ## Tail container logs
	docker compose logs -f
