RED    = \033[31m
GREEN  = \033[32m
YELLOW = \033[33m
NC     = \033[0m

help:
	@echo 'Usage: make <target>'
	@awk '/^[a-zA-Z\-0-9]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")-1); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf "  $(YELLOW)%-20s$(NC) $(GREEN)%s$(NC)\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)

include devops/mk/docker.mk
include devops/mk/symfony.mk
include devops/mk/identity.mk
include devops/mk/event-messaging.mk
include devops/mk/sonar.mk
include devops/mk/Identity/Test/index.mk
