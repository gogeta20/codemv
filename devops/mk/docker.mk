DOCKER      = docker
DOCKER_COMP = docker compose

## Build Docker images
build:
	@$(DOCKER_COMP) build --pull --no-cache
	@echo "$(GREEN)✅ Build complete$(NC)"

## Start API + DB
up:
	@$(DOCKER_COMP) up -d
	@echo "$(GREEN)✅ codemv up$(NC)"
	@echo "   API: http://localhost:8280"
	@echo "   DB:  postgresql://localhost:15432/codemv"

## Start with frontend
up-all:
	@$(DOCKER_COMP) --profile frontend up -d
	@echo "$(GREEN)✅ codemv up (all)$(NC)"
	@echo "   API:      http://localhost:8280"
	@echo "   Frontend: http://localhost:25173"

## Stop containers
stop:
	@$(DOCKER_COMP) stop

## Stop and remove containers
down:
	@$(DOCKER_COMP) down --remove-orphans

## Restart
restart: down up

## Show live logs
logs:
	@$(DOCKER_COMP) logs --tail=50 --follow

## Open bash in API container
bash:
	@$(DOCKER) exec -it codemv-api bash

## Open psql in DB container
psql:
	@$(DOCKER) exec -it codemv-db psql -U codemv codemv
