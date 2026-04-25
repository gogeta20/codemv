# ============================================================================
# IDENTITY - Test Commands
# ============================================================================
# Comandos reutilizables para ejecutar y analizar tests del proyecto Identity
#
# Uso:
#   make -f devops/mk/Identity/Test/index.mk <target>
#
# Requisitos:
#   - Docker corriendo con el contenedor identity.service
#   - Xdebug habilitado en el contenedor (para cobertura)
#
# Variables de entorno configurables:
#   IDENTITY_CONTAINER  - Nombre del contenedor Docker (default: identity.service)
#   IDENTITY_PROJECT    - Ruta al proyecto Identity en el host
# ============================================================================

SHELL := /bin/bash

# Configuración
IDENTITY_CONTAINER ?= identity.service
IDENTITY_PROJECT ?= /home/mauricio-vargas/projects/jotelulu/dev-environments/identity/php-bs-identity
PHPUNIT_BIN = vendor/bin/phpunit

# Colores
RED    = \033[31m
GREEN  = \033[32m
YELLOW = \033[33m
CYAN   = \033[36m
NC     = \033[0m

# ============================================================================
# Verificación
# ============================================================================

## Verificar que el contenedor Identity esté corriendo
identity-check:
	@printf "$(CYAN)Verificando contenedor $(IDENTITY_CONTAINER)...$(NC)\n"
	@STATUS=$$(docker ps --filter "name=$(IDENTITY_CONTAINER)" --format "{{.Names}} {{.Status}}" 2>/dev/null); \
	if [ -z "$$STATUS" ]; then \
		printf "$(RED)❌ Contenedor $(IDENTITY_CONTAINER) no está corriendo$(NC)\n"; \
		exit 1; \
	else \
		printf "$(GREEN)✅ $$STATUS$(NC)\n"; \
	fi

## Verificar que Xdebug esté disponible (necesario para cobertura)
identity-check-xdebug: identity-check
	@printf "$(CYAN)Verificando Xdebug...$(NC)\n"
	@if docker exec $(IDENTITY_CONTAINER) php -m 2>/dev/null | grep -qi xdebug; then \
		printf "$(GREEN)✅ Xdebug disponible$(NC)\n"; \
	else \
		printf "$(RED)❌ Xdebug no está instalado en el contenedor$(NC)\n"; \
		exit 1; \
	fi

# ============================================================================
# Ejecución de Tests
# ============================================================================

## Ejecutar todos los tests con testdox
identity-tests: identity-check
	@printf "\n$(GREEN)***** IDENTITY - RUNNING TESTS *****$(NC)\n\n"
	docker exec $(IDENTITY_CONTAINER) php $(PHPUNIT_BIN) \
		--testdox \
		--display-incomplete \
		--display-skipped \
		--display-deprecations \
		--display-errors \
		--display-notices \
		--display-warnings

## Ejecutar tests de un grupo específico (GROUP=user|oauth|organization|infrastructure)
identity-tests-group: identity-check
	@if [ -z "$(GROUP)" ]; then \
		printf "$(RED)❌ Especifica GROUP=<nombre>. Ej: make identity-tests-group GROUP=user$(NC)\n"; \
		exit 1; \
	fi
	@printf "\n$(GREEN)***** IDENTITY - RUNNING TESTS [$(GROUP)] *****$(NC)\n\n"
	docker exec $(IDENTITY_CONTAINER) php $(PHPUNIT_BIN) \
		--testdox \
		--display-errors \
		--display-warnings \
		--group $(GROUP)

## Ver detalle de errores y fallos (últimas 200 líneas)
identity-tests-errors: identity-check
	@printf "\n$(YELLOW)***** IDENTITY - TEST ERRORS DETAIL *****$(NC)\n\n"
	docker exec $(IDENTITY_CONTAINER) php $(PHPUNIT_BIN) \
		--display-errors \
		--display-warnings 2>&1 | tail -200

# ============================================================================
# Cobertura
# ============================================================================

## Mostrar cobertura en texto (resumen global)
identity-coverage-summary: identity-check-xdebug
	@printf "\n$(GREEN)***** IDENTITY - COVERAGE SUMMARY *****$(NC)\n\n"
	docker exec $(IDENTITY_CONTAINER) env XDEBUG_MODE=coverage php $(PHPUNIT_BIN) \
		--coverage-text 2>&1 | grep -A 6 "Summary:"

## Mostrar cobertura completa en texto
identity-coverage-text: identity-check-xdebug
	@printf "\n$(GREEN)***** IDENTITY - COVERAGE TEXT *****$(NC)\n\n"
	docker exec $(IDENTITY_CONTAINER) env XDEBUG_MODE=coverage php $(PHPUNIT_BIN) \
		--coverage-text

## Listar clases con cobertura < 100%
identity-coverage-gaps: identity-check-xdebug
	@printf "\n$(YELLOW)***** IDENTITY - COVERAGE GAPS (< 100%%) *****$(NC)\n\n"
	docker exec $(IDENTITY_CONTAINER) env XDEBUG_MODE=coverage php $(PHPUNIT_BIN) \
		--coverage-text 2>&1 | \
		awk '/^App\\/ { class=$$0 } /Lines:/ && !/100.00%/ && class { print class " → " $$0; class="" }'

## Generar reporte HTML de cobertura y abrirlo
identity-coverage-html: identity-check-xdebug
	@printf "\n$(GREEN)***** IDENTITY - GENERATING HTML COVERAGE REPORT *****$(NC)\n\n"
	docker exec $(IDENTITY_CONTAINER) env XDEBUG_MODE=coverage php $(PHPUNIT_BIN) \
		--coverage-html docs/test-coverage
	@printf "$(GREEN)✅ Reporte generado en $(IDENTITY_PROJECT)/docs/test-coverage/index.html$(NC)\n"
	@if command -v xdg-open >/dev/null 2>&1; then \
		xdg-open $(IDENTITY_PROJECT)/docs/test-coverage/index.html; \
	else \
		printf "$(YELLOW)Abre manualmente: $(IDENTITY_PROJECT)/docs/test-coverage/index.html$(NC)\n"; \
	fi

# ============================================================================
# Pipeline completo
# ============================================================================

## Ejecutar pipeline completo: tests + cobertura resumen + gaps
identity-test-pipeline: identity-tests identity-coverage-summary identity-coverage-gaps
	@printf "\n$(GREEN)✅ Pipeline de tests completado$(NC)\n"

# ============================================================================
# Ayuda
# ============================================================================

## Mostrar ayuda de los comandos de Identity Test
identity-test-help:
	@printf "\n$(GREEN)╔══════════════════════════════════════════════════════╗$(NC)\n"
	@printf "$(GREEN)║$(NC)   $(CYAN)IDENTITY - Test Commands$(NC)                           $(GREEN)║$(NC)\n"
	@printf "$(GREEN)╚══════════════════════════════════════════════════════╝$(NC)\n\n"
	@printf "  $(YELLOW)identity-check$(NC)            Verificar contenedor Docker\n"
	@printf "  $(YELLOW)identity-check-xdebug$(NC)     Verificar Xdebug disponible\n"
	@printf "\n"
	@printf "  $(YELLOW)identity-tests$(NC)            Ejecutar todos los tests\n"
	@printf "  $(YELLOW)identity-tests-group$(NC)      Tests por grupo (GROUP=user|oauth|...)\n"
	@printf "  $(YELLOW)identity-tests-errors$(NC)     Ver detalle de errores/fallos\n"
	@printf "\n"
	@printf "  $(YELLOW)identity-coverage-summary$(NC) Resumen de cobertura (texto)\n"
	@printf "  $(YELLOW)identity-coverage-text$(NC)    Cobertura completa (texto)\n"
	@printf "  $(YELLOW)identity-coverage-gaps$(NC)    Clases con cobertura < 100%%\n"
	@printf "  $(YELLOW)identity-coverage-html$(NC)    Generar y abrir reporte HTML\n"
	@printf "\n"
	@printf "  $(YELLOW)identity-test-pipeline$(NC)    Pipeline completo (tests+cobertura+gaps)\n"
	@printf "\n"

.PHONY: identity-check identity-check-xdebug identity-tests identity-tests-group \
        identity-tests-errors identity-coverage-summary identity-coverage-text \
        identity-coverage-gaps identity-coverage-html identity-test-pipeline \
        identity-test-help


# # Desde codemv
# cd ~/projects/personal/IA/codemv
# make -f devops/mk/Identity/Test/index.mk identity-test-help

# # Comandos principales:
# make -f devops/mk/Identity/Test/index.mk identity-tests              # Ejecutar tests
# make -f devops/mk/Identity/Test/index.mk identity-tests-group GROUP=user  # Tests por grupo
# make -f devops/mk/Identity/Test/index.mk identity-tests-errors       # Detalle de errores
# make -f devops/mk/Identity/Test/index.mk identity-coverage-summary   # Resumen cobertura
# make -f devops/mk/Identity/Test/index.mk identity-coverage-gaps      # Clases con gaps
# make -f devops/mk/Identity/Test/index.mk identity-coverage-html      # Reporte HTML + abrir
# make -f devops/mk/Identity/Test/index.mk identity-test-pipeline      # Pipeline completo
