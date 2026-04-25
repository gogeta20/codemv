# ============================================================================
# Identity ↔ Panel Event Messaging Pipeline (dev helpers)
# ============================================================================
# Flujo:  IDENTITY outbox → RabbitMQ → PANEL inbox → PANEL handler
#
# Containers:
#   identity.service  — php-bs-identity (Symfony 7.4, publisher)
#   core.panel        — panel.php (Symfony 6.2, consumer)
#   dep.queues-rabbitmq — RabbitMQ broker
# ============================================================================

PANEL_CONT     := docker exec core.panel
PANEL_PHP      := $(PANEL_CONT) php bin/console
IDENTITY_CONT  := docker exec identity.service
IDENTITY_PHP   := $(IDENTITY_CONT) php bin/console

# ── Full pipelines ──────────────────────────────────────────────────────────

## [pipeline] Run full event pipeline: identity outbox → RabbitMQ → panel inbox → process
pipeline: identity-outbox-publish panel-inbox-consume panel-inbox-process
	@echo "✅ Pipeline complete"

## [pipeline] Alias: same as 'pipeline'
panel-login-pipeline: pipeline

# ── Identity side (publisher) ───────────────────────────────────────────────

## [identity] Publish pending outbox messages to RabbitMQ
identity-outbox-publish:
	@echo "→ Publishing identity outbox → RabbitMQ..."
	@$(IDENTITY_PHP) event-messaging:outbox:publish identity

## [identity] Retry failed outbox messages
identity-outbox-retry:
	@echo "→ Retrying failed outbox messages..."
	@$(IDENTITY_PHP) event-messaging:outbox:publish identity --retry-failed

## [identity] Show outbox table status (pending/published/failed counts)
identity-outbox-status:
	@echo "── Identity outbox status ──"
	@$(IDENTITY_CONT) php -r "\
		\$$pdo = new PDO('pgsql:host=dep.db-postgres;dbname=identity_service', 'postgres', getenv('INTERNAL_PASSWORD') ?: 'internal'); \
		\$$r = \$$pdo->query(\"SELECT status, COUNT(*) as cnt FROM outbox GROUP BY status ORDER BY status\"); \
		while(\$$row = \$$r->fetch(PDO::FETCH_ASSOC)) echo \$$row['status'] . ': ' . \$$row['cnt'] . PHP_EOL;" 2>/dev/null \
	|| echo "(could not query outbox — check DB connection)"

## [identity] Show last N outbox messages (default 5)
identity-outbox-last:
	@$(IDENTITY_CONT) php -r "\
		\$$pdo = new PDO('pgsql:host=dep.db-postgres;dbname=identity_service', 'postgres', getenv('INTERNAL_PASSWORD') ?: 'internal'); \
		\$$r = \$$pdo->query(\"SELECT id, event_key, status, occurred_at FROM outbox ORDER BY occurred_at DESC LIMIT $(or $(N),5)\"); \
		echo sprintf('%-36s %-50s %-12s %s', 'ID', 'EVENT_KEY', 'STATUS', 'OCCURRED_AT') . PHP_EOL; \
		echo str_repeat('-', 120) . PHP_EOL; \
		while(\$$row = \$$r->fetch(PDO::FETCH_ASSOC)) echo sprintf('%-36s %-50s %-12s %s', \$$row['id'], \$$row['event_key'], \$$row['status'], \$$row['occurred_at']) . PHP_EOL;" 2>/dev/null \
	|| echo "(could not query outbox)"

# ── Panel side (consumer) ──────────────────────────────────────────────────

## [panel] Consume messages from RabbitMQ into panel inbox
panel-inbox-consume:
	@echo "→ Consuming RabbitMQ → panel inbox (core_from_identity)..."
	@$(PANEL_PHP) event-messaging:inbox:consume core_from_identity

## [panel] Process pending inbox messages (run handlers)
panel-inbox-process:
	@echo "→ Processing panel inbox → handlers..."
	@$(PANEL_PHP) event-messaging:inbox:process core

## [panel] Show inbox table status
panel-inbox-status:
	@echo "── Panel inbox status ──"
	@$(PANEL_CONT) php -r "\
		\$$pdo = new PDO('mysql:host=dep.db-maria;dbname=jotelulu_panel_corebs', 'jotelulu_user', getenv('INTERNAL_PASSWORD') ?: 'internal'); \
		\$$r = \$$pdo->query(\"SELECT status, COUNT(*) as cnt FROM inbox GROUP BY status ORDER BY status\"); \
		while(\$$row = \$$r->fetch(PDO::FETCH_ASSOC)) echo \$$row['status'] . ': ' . \$$row['cnt'] . PHP_EOL;" 2>/dev/null \
	|| echo "(could not query inbox — check DB connection)"

## [panel] Show last N inbox messages (default 5)
panel-inbox-last:
	@$(PANEL_CONT) php -r "\
		\$$pdo = new PDO('mysql:host=dep.db-maria;dbname=jotelulu_panel_corebs', 'jotelulu_user', getenv('INTERNAL_PASSWORD') ?: 'internal'); \
		\$$r = \$$pdo->query(\"SELECT id, event_key, status, created_at FROM inbox ORDER BY created_at DESC LIMIT $(or $(N),5)\"); \
		echo sprintf('%-36s %-50s %-12s %s', 'ID', 'EVENT_KEY', 'STATUS', 'CREATED_AT') . PHP_EOL; \
		echo str_repeat('-', 120) . PHP_EOL; \
		while(\$$row = \$$r->fetch(PDO::FETCH_ASSOC)) echo sprintf('%-36s %-50s %-12s %s', \$$row['id'], \$$row['event_key'], \$$row['status'], \$$row['created_at']) . PHP_EOL;" 2>/dev/null \
	|| echo "(could not query inbox)"

# ── RabbitMQ inspection ────────────────────────────────────────────────────

## [rabbit] List queues with message counts
rabbit-queues:
	@echo "── RabbitMQ queues ──"
	@docker exec dep.queues-rabbitmq rabbitmqctl list_queues name messages messages_ready messages_unacknowledged 2>/dev/null \
	|| echo "(RabbitMQ not reachable)"

## [rabbit] List exchanges
rabbit-exchanges:
	@echo "── RabbitMQ exchanges ──"
	@docker exec dep.queues-rabbitmq rabbitmqctl list_exchanges name type 2>/dev/null | grep -v "amq\." \
	|| echo "(RabbitMQ not reachable)"

## [rabbit] List bindings (exchange → queue)
rabbit-bindings:
	@echo "── RabbitMQ bindings ──"
	@docker exec dep.queues-rabbitmq rabbitmqctl list_bindings source_name destination_name routing_key 2>/dev/null | grep -v "^$$" \
	|| echo "(RabbitMQ not reachable)"

# ── Shortcuts ──────────────────────────────────────────────────────────────

## [shortcut] Show full status: identity outbox + panel inbox + rabbit queues
status: identity-outbox-status panel-inbox-status rabbit-queues

## [shortcut] Show recent messages: last 5 outbox + last 5 inbox
recent: identity-outbox-last panel-inbox-last

# ── Help ───────────────────────────────────────────────────────────────────

## Show this help
panel-help:
	@echo ""
	@echo "Identity ↔ Panel Event Messaging"
	@echo "================================"
	@echo ""
	@echo "Pipelines:"
	@echo "  make pipeline                  Full: outbox → rabbit → inbox → process"
	@echo ""
	@echo "Identity (publisher):"
	@echo "  make identity-outbox-publish   Publish pending to RabbitMQ"
	@echo "  make identity-outbox-retry     Retry failed messages"
	@echo "  make identity-outbox-status    Show outbox counts by status"
	@echo "  make identity-outbox-last      Last 5 messages (N=10 for more)"
	@echo ""
	@echo "Panel (consumer):"
	@echo "  make panel-inbox-consume       Consume RabbitMQ → inbox"
	@echo "  make panel-inbox-process       Process inbox → handlers"
	@echo "  make panel-inbox-status        Show inbox counts by status"
	@echo "  make panel-inbox-last          Last 5 messages (N=10 for more)"
	@echo ""
	@echo "RabbitMQ:"
	@echo "  make rabbit-queues             List queues + message counts"
	@echo "  make rabbit-exchanges          List exchanges"
	@echo "  make rabbit-bindings           List bindings (exchange → queue)"
	@echo ""
	@echo "Shortcuts:"
	@echo "  make status                    All statuses at once"
	@echo "  make recent                    Last messages from both sides"
	@echo ""
