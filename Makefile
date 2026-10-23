# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) run --rm php

# Executables
PHP = $(PHP_CONT) php

# Misc
.DEFAULT_GOAL = help
.PHONY        = help build up start down logs sh composer vendor sf cc

## —— Help 🎵 ———————————————————————————————————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
docker-build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

php: ## Connect to the PHP FPM container
	@$(PHP_CONT) sh

## —— Project 🐝 ———————————————————————————————————————————————————————————————
install: ## Install project
	@$(PHP_CONT) composer install

## —— Doc 📖 ————————————————————————————————————————————————————————————————————
AUTOMATE_VERSION ?= $(shell git describe --tags --abbrev=0 2>/dev/null)

doc-build: ## Build the doc website and its search index into doc/_site
	AUTOMATE_VERSION=$(AUTOMATE_VERSION) cecil build doc --config=cecil.yml
	npx -y pagefind@1.3.0 --site doc/_site

doc-serve: ## Serve the doc website locally (without the search index)
	AUTOMATE_VERSION=$(AUTOMATE_VERSION) cecil serve doc --config=cecil.yml

## —— CI ✨ ————————————————————————————————————————————————————————————————————
ci: cs phpstan rector test ## Run all all checks

cs: ## Run tests
	$(PHP) vendor/bin/php-cs-fixer fix

phpstan: ## Run phpstan
	$(PHP) vendor/bin/phpstan  --memory-limit=1G

rector: ## Run rector
	$(PHP) vendor/bin/rector

test: ## Run tests
	$(PHP) vendor/bin/phpunit