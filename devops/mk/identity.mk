IDENTITY_CONT = docker exec -it identity.service
IDENTITY_PHP  = $(IDENTITY_CONT) php

## [identity] Open bash in identity container
identity-bash:
	@$(IDENTITY_CONT) bash

## [identity] Tail identity dev log (last 50 lines, follow)
identity-logs:
	@$(IDENTITY_CONT) tail -f -n 50 var/log/dev.log

## [identity] Search password recovery logs
identity-logs-recovery:
	@$(IDENTITY_CONT) grep -a "IDENTITY.*Password recovery\|recoveryUrl" var/log/dev.log | tail -20

## [identity] Open psql in identity DB
identity-psql:
	@docker exec -it identity.db psql -U identity identity

## [identity] Clear identity cache
identity-cache-clear:
	@$(IDENTITY_PHP) bin/console cache:clear
