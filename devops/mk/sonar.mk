# SonarQube Configuration
# Remote: https://10.35.10.20 (VPN required)
# Local:  http://localhost:9100 (docker compose)

# --- Remote server (empresa) ---
SONAR_URL        = https://10.35.10.20
SONAR_TOKEN      = sqa_70a36c4ae3a1fd52d687d839bf0db9b4c1f529e4
SONAR_AUTH       = -sk -u "$(SONAR_TOKEN):"
SONAR_KEY_IDENTITY = jotelulu_devs-core_php-bs-identity_881f8c64-a154-4b0b-9b2f-25079e71a002

# --- Local server (docker) ---
SONAR_LOCAL_URL     = http://localhost:9100
SONAR_LOCAL_TOKEN   = squ_b7921e6451c1375e6bd848585fe0bf6707f50680
IDENTITY_SRC        = $(HOME)/projects/jotelulu/dev-environments/identity/php-bs-identity
SONAR_LOCAL_NETWORK = php-bs-identity_sonarnet
SONAR_SCANNER_IMAGE = sonarsource/sonar-scanner-cli:latest

# ===========================
# Remote SonarQube (empresa)
# ===========================

## [sonar] List all identity issues (Code Smells, Open)
sonar-identity:
	@curl $(SONAR_AUTH) "$(SONAR_URL)/api/issues/search?componentKeys=$(SONAR_KEY_IDENTITY)&scopes=MAIN&issueStatuses=CONFIRMED,OPEN&types=CODE_SMELL&ps=50" | jq '{total: .total, issues: [.issues[] | {message: .message, file: (.component | split(":")[1]), line: .line, severity: .severity}]}'

## [sonar] Identity issues summary by severity
sonar-identity-summary:
	@curl $(SONAR_AUTH) "$(SONAR_URL)/api/issues/search?componentKeys=$(SONAR_KEY_IDENTITY)&scopes=MAIN&issueStatuses=CONFIRMED,OPEN&types=CODE_SMELL&ps=1&facets=severities" | jq '.facets[0].values'

## [sonar] Identity CRITICAL issues only
sonar-identity-critical:
	@curl $(SONAR_AUTH) "$(SONAR_URL)/api/issues/search?componentKeys=$(SONAR_KEY_IDENTITY)&scopes=MAIN&issueStatuses=CONFIRMED,OPEN&types=CODE_SMELL&severities=CRITICAL&ps=50" | jq '[.issues[] | {message: .message, file: (.component | split(":")[1]), line: .line, effort: .effort}]'

## [sonar] Identity MAJOR issues only
sonar-identity-major:
	@curl $(SONAR_AUTH) "$(SONAR_URL)/api/issues/search?componentKeys=$(SONAR_KEY_IDENTITY)&scopes=MAIN&issueStatuses=CONFIRMED,OPEN&types=CODE_SMELL&severities=MAJOR&ps=50" | jq '[.issues[] | {message: .message, file: (.component | split(":")[1]), line: .line, effort: .effort}]'

## [sonar] Identity MINOR issues only
sonar-identity-minor:
	@curl $(SONAR_AUTH) "$(SONAR_URL)/api/issues/search?componentKeys=$(SONAR_KEY_IDENTITY)&scopes=MAIN&issueStatuses=CONFIRMED,OPEN&types=CODE_SMELL&severities=MINOR&ps=50" | jq '[.issues[] | {message: .message, file: (.component | split(":")[1]), line: .line, effort: .effort}]'

## [sonar] Identity issues for a specific file (usage: make sonar-identity-file FILE=src/User/...)
sonar-identity-file:
	@curl $(SONAR_AUTH) "$(SONAR_URL)/api/issues/search?componentKeys=$(SONAR_KEY_IDENTITY):$(FILE)&scopes=MAIN&issueStatuses=CONFIRMED,OPEN&ps=50" | jq '[.issues[] | {message: .message, line: .line, severity: .severity, type: .type}]'

## [sonar] Remote SonarQube server status
sonar-status:
	@curl $(SONAR_AUTH) "$(SONAR_URL)/api/system/status" | jq '.'

# ===========================
# Local SonarQube (docker)
# ===========================

## [sonar-local] Start local SonarQube
sonar-local-up:
	@cd $(IDENTITY_SRC) && docker compose up -d sonarqube
	@echo "SonarQube starting at $(SONAR_LOCAL_URL) ..."
	@echo "Wait ~30s for it to be ready, then run: make sonar-local-status"

## [sonar-local] Stop local SonarQube
sonar-local-down:
	@cd $(IDENTITY_SRC) && docker compose stop sonarqube

## [sonar-local] Local SonarQube status
sonar-local-status:
	@curl -s "$(SONAR_LOCAL_URL)/api/system/status" | jq '.'

## [sonar-local] Run analysis on identity (local)
sonar-local-scan:
	@docker run --rm \
		--network=$(SONAR_LOCAL_NETWORK) \
		-v "$(IDENTITY_SRC)/src:/usr/src/src" \
		$(SONAR_SCANNER_IMAGE) \
		-Dsonar.host.url=http://sonarqube:9000 \
		-Dsonar.projectKey=php-bs-identity-local \
		-Dsonar.sources=src \
		-Dsonar.language=php \
		-Dsonar.sourceEncoding=UTF-8 \
		-Dsonar.projectBaseDir=/usr/src

## [sonar-local] Show local analysis results summary
sonar-local-summary:
	@curl -s "$(SONAR_LOCAL_URL)/api/issues/search?componentKeys=php-bs-identity-local&types=CODE_SMELL&ps=1&facets=severities" | jq '{total: .total, severities: .facets[0].values}'

## [sonar-local] Show local CRITICAL issues
sonar-local-critical:
	@curl -s "$(SONAR_LOCAL_URL)/api/issues/search?componentKeys=php-bs-identity-local&types=CODE_SMELL&severities=CRITICAL&ps=50" | jq '[.issues[] | {message: .message, file: (.component | split(":")[1]), line: .line}]'

## [sonar-local] Show local Organization issues
sonar-local-organization:
	@curl -s "$(SONAR_LOCAL_URL)/api/issues/search?componentKeys=php-bs-identity-local&types=CODE_SMELL&ps=100" | jq '[.issues[] | select(.component | contains("Organization")) | {message: .message, file: (.component | split(":")[1]), line: .line, severity: .severity}]'
