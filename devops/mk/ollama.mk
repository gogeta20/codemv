OLLAMA_BASE_DIR      ?= $(CURDIR)/.local/ollama
OLLAMA_BIN           ?= $(OLLAMA_BASE_DIR)/bin/ollama
OLLAMA_HOME          ?= $(CURDIR)/.local/ollama-home
OLLAMA_MODELS        ?= $(CURDIR)/.local/ollama-models
OLLAMA_HOST          ?= 127.0.0.1:11434
OLLAMA_PID_FILE      ?= /tmp/codemv-ollama.pid
OLLAMA_LOG_FILE      ?= /tmp/codemv-ollama.log
OLLAMA_DEFAULT_MODEL ?= gemma3:1b
MODEL                ?= $(OLLAMA_DEFAULT_MODEL)
PROMPT               ?= Responde en una frase: di hola desde Ollama local.

## Download Ollama locally into this repo
ollama-install:
	@mkdir -p "$(OLLAMA_BASE_DIR)"
	@mkdir -p "$(OLLAMA_HOME)"
	@mkdir -p "$(OLLAMA_MODELS)"
	@if [ -x "$(OLLAMA_BIN)" ]; then \
		echo "$(GREEN)Ollama already installed at $(OLLAMA_BIN)$(NC)"; \
	else \
		echo "Downloading Ollama to $(OLLAMA_BASE_DIR)"; \
		curl --fail --show-error --location https://ollama.com/download/ollama-linux-amd64.tar.zst | zstd -d | tar -xf - -C "$(OLLAMA_BASE_DIR)"; \
		echo "$(GREEN)Ollama installed locally$(NC)"; \
	fi

## Start Ollama only when needed
ollama-start: ollama-install
	@if [ -f "$(OLLAMA_PID_FILE)" ] && kill -0 "$$(cat "$(OLLAMA_PID_FILE)")" 2>/dev/null; then \
		echo "$(YELLOW)Ollama already running on $(OLLAMA_HOST)$(NC)"; \
	else \
		echo "Starting Ollama on $(OLLAMA_HOST)"; \
		OLLAMA_HOME="$(OLLAMA_HOME)" OLLAMA_MODELS="$(OLLAMA_MODELS)" OLLAMA_HOST="$(OLLAMA_HOST)" nohup "$(OLLAMA_BIN)" serve >"$(OLLAMA_LOG_FILE)" 2>&1 & echo $$! > "$(OLLAMA_PID_FILE)"; \
		sleep 2; \
		if kill -0 "$$(cat "$(OLLAMA_PID_FILE)")" 2>/dev/null; then \
			echo "$(GREEN)Ollama running$(NC)"; \
			echo "  API: http://$(OLLAMA_HOST)"; \
			echo "  Log: $(OLLAMA_LOG_FILE)"; \
		else \
			echo "$(RED)Ollama failed to start. Check $(OLLAMA_LOG_FILE)$(NC)"; \
			exit 1; \
		fi; \
	fi

## Stop Ollama local service
ollama-stop:
	@if [ -f "$(OLLAMA_PID_FILE)" ] && kill -0 "$$(cat "$(OLLAMA_PID_FILE)")" 2>/dev/null; then \
		kill "$$(cat "$(OLLAMA_PID_FILE)")"; \
		rm -f "$(OLLAMA_PID_FILE)"; \
		echo "$(GREEN)Ollama stopped$(NC)"; \
	else \
		rm -f "$(OLLAMA_PID_FILE)"; \
		echo "$(YELLOW)Ollama is not running$(NC)"; \
	fi

## Show Ollama status
ollama-status:
	@if [ -f "$(OLLAMA_PID_FILE)" ] && kill -0 "$$(cat "$(OLLAMA_PID_FILE)")" 2>/dev/null; then \
		echo "$(GREEN)Ollama is running$(NC)"; \
		echo "  PID: $$(cat "$(OLLAMA_PID_FILE)")"; \
		echo "  API: http://$(OLLAMA_HOST)"; \
		echo "  Models dir: $(OLLAMA_MODELS)"; \
	else \
		echo "$(YELLOW)Ollama is stopped$(NC)"; \
	fi

## Download a model locally, e.g. make ollama-pull MODEL=gemma3:1b
ollama-pull: ollama-start
	@OLLAMA_HOME="$(OLLAMA_HOME)" OLLAMA_MODELS="$(OLLAMA_MODELS)" OLLAMA_HOST="$(OLLAMA_HOST)" "$(OLLAMA_BIN)" pull "$(MODEL)"

## Run a quick prompt against the local model
ollama-test: ollama-start
	@OLLAMA_HOME="$(OLLAMA_HOME)" OLLAMA_MODELS="$(OLLAMA_MODELS)" OLLAMA_HOST="$(OLLAMA_HOST)" "$(OLLAMA_BIN)" run "$(MODEL)" "$(PROMPT)"

## List models installed in this repo
ollama-list: ollama-start
	@OLLAMA_HOME="$(OLLAMA_HOME)" OLLAMA_MODELS="$(OLLAMA_MODELS)" OLLAMA_HOST="$(OLLAMA_HOST)" "$(OLLAMA_BIN)" list
