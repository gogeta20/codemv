SYMFONY = docker exec codemv-api php bin/console
COMPOSER = docker exec codemv-api composer

## Run doctrine migrations
migrate:
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✅ Migrations done$(NC)"

## Create a new migration
migration:
	@$(SYMFONY) doctrine:migrations:diff

## Validate doctrine schema
schema-validate:
	@$(SYMFONY) doctrine:schema:validate

## Clear symfony cache
cache-clear:
	@$(SYMFONY) cache:clear

## Run composer install
composer-install:
	@$(COMPOSER) install

## Load seeds (initial data)
seeds:
	@$(SYMFONY) app:seeds:load
	@echo "$(GREEN)✅ Seeds loaded$(NC)"

## Show routes
routes:
	@$(SYMFONY) debug:router

## Full setup: migrate + seeds
setup: migrate seeds
	@echo "$(GREEN)✅ Setup complete$(NC)"
