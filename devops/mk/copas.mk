COPAS_SYNC_CMD := PYTHONPATH=.python-agent python3 backend/agent/steps/sync_copas_uefa.py

.PHONY: copas-sync
copas-sync:
	@$(COPAS_SYNC_CMD)

.PHONY: copas-sync-date
copas-sync-date:
	@test -n "$(FECHA)" || (echo "Uso: make copas-sync-date FECHA=YYYY-MM-DD" && exit 1)
	@$(COPAS_SYNC_CMD) --fecha $(FECHA)
